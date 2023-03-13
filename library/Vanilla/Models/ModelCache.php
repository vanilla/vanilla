<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\Psr16Adapter;
use Symfony\Contracts\Cache\ItemInterface;
use Vanilla\Cache\CacheCacheAdapter;
use Vanilla\CurrentTimeStamp;
use Vanilla\FeatureFlagHelper;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\CallbackJob;
use Vanilla\Scheduler\SchedulerInterface;

/**
 * Cache for records out of various models.
 *
 * Features:
 * - Can give an invalidation processor for the pipeline model.
 * - Uses symfony cache contracts:
 *   - A lock is acquired when calculating a cache value to prevent instances contending for resources to generate the same value.
 *   - Values may be recalculated early on random requests to prevent the value from expiring.
 */
class ModelCache
{
    /** @var int When we hit this size of incrementing key, we reset from 0. */
    const MAX_INCREMENTING_KEY = 1000000;

    /** @var string */
    const INCREMENTING_KEY_NAMESPACE = "vanillaIncrementingKey";

    const GLOBAL_DEFAULT_OPTIONS = [
        \Gdn_Cache::FEATURE_EXPIRY => 600,
    ];

    /** @var int Amount of time to allow a scheduled hydration to wait before deleting the cache key. */
    const RESCHEDULE_THRESHOLD = 30;

    const DISABLE_FEATURE_FLAG = "DisableNewModelCaching";

    public const OPT_TTL = \Gdn_Cache::FEATURE_EXPIRY;
    public const OPT_DEFAULT = \Gdn_Cache::FEATURE_DEFAULT;
    public const OPT_SCHEDULER = "scheduler";

    /** @var string */
    private $cacheNameSpace;

    /** @var array */
    private $defaultCacheOptions;

    /** @var CacheInterface */
    private $cache;

    /** @var \Symfony\Contracts\Cache\CacheInterface */
    private $cacheContract;

    /** @var bool */
    private $isFeatureDisabled;

    /** @var callable */
    private $onInvalidate;

    /**
     * Constructor.
     *
     * @param string $cacheNameSpace Namespace to use in the cache.
     * @param \Gdn_Cache $cache The cache instance.
     * @param array $defaultCacheOptions Default options to apply for storing cache values.
     */
    public function __construct(string $cacheNameSpace, \Gdn_Cache $cache, array $defaultCacheOptions = [])
    {
        $this->cache = new CacheCacheAdapter($cache);
        $this->cacheContract = new Psr16Adapter($this->cache, $cacheNameSpace);

        // By default symfony has a wrapped that takes locks to prevent concurrent calculation of a cache key.
        // Unfortunately we've had extensive deadlocking issues on infrastrucutre and increased latency with this.
        // As a result we've decided to disable this feature altogether. https://higher-logic-llc.slack.com/archives/G010E9CKJ1H/p1648759680021669
        // We attempted multiple iterations with:
        // - The built-in lockstore
        // - A lock store using `symfony/lock` and the `FlockStore`
        // - A lock store using `symfony/lock` and the `MemcachedStore`.
        //
        // We have other mechanisms for prevent concurrent computation.
        // Things that need a lock (like siteTotal computation) can use deferred hydration and their own lock.
        $this->cacheContract->setCallbackWrapper(null);

        $this->cacheNameSpace = $cacheNameSpace;
        $this->defaultCacheOptions = array_merge(self::GLOBAL_DEFAULT_OPTIONS, $defaultCacheOptions ?? []);
        $this->isFeatureDisabled = FeatureFlagHelper::featureEnabled(self::DISABLE_FEATURE_FLAG);
    }

    /**
     * @param callable $onInvalidate
     */
    public function setOnInvalidate(callable $onInvalidate): void
    {
        $this->onInvalidate = $onInvalidate;
    }

    /**
     * Create a cache key for some parameters.
     *
     * @param array $keyArgs Some arguments to generate the cache key from.
     * @param bool $excludeNamespace Set this to true to remove the namespace from the generated key.
     *
     * @return string
     */
    public function createCacheKey(array $keyArgs, bool $excludeNamespace = false): string
    {
        $jsonEncoded = json_encode($keyArgs);
        $key = $this->getIncrementingKey() . "-" . sha1($jsonEncoded);
        if (!$excludeNamespace) {
            // Make the key in the same why the symfony cache contract does.
            $key = $this->cacheNameSpace . "_" . $key;
        }
        return $key;
    }

    /**
     * Try to get a cached record.
     *
     * If the record can't be found, we hydrate it with the $hydrate callable and return it.
     *
     * @param array $args The arguments to build the cache key.
     * @param callable $hydrate A callable to hydrate the cache.
     * @param array $cacheOptions Options for the cache storage.
     *
     * @return mixed
     */
    public function getCachedOrHydrate(array $args, callable $hydrate, array $cacheOptions = [])
    {
        if ($this->isFeatureDisabled) {
            if (empty($args)) {
                return $hydrate();
            } else {
                return call_user_func_array($hydrate, $args);
            }
        }

        $ttl = $cacheOptions[self::OPT_TTL] ?? $this->defaultCacheOptions[self::OPT_TTL];
        $contractKey = $this->createCacheKey($args, true);
        $result = $this->cacheContract->get($contractKey, function (ItemInterface $item) use (
            $args,
            $hydrate,
            $ttl,
            $cacheOptions
        ) {
            $item->expiresAfter($ttl);

            $scheduler =
                $cacheOptions[self::OPT_SCHEDULER] ?? ($this->defaultCacheOptions[self::OPT_SCHEDULER] ?? null);
            $default = $cacheOptions[self::OPT_DEFAULT] ?? null;
            if ($scheduler instanceof SchedulerInterface) {
                // Defer hydration.
                $fullKey = $this->createCacheKey($args);
                $this->scheduleHydration($scheduler, $fullKey, $hydrate, array_values($args));
                // Return the default for now.
                return serialize($default);
            } else {
                // Hydrate immediately.
                $result = call_user_func_array($hydrate, array_values($args));
                $result = serialize($result);
                return $result;
            }
        });
        $result = unserialize($result);
        return $result;
    }

    /**
     * Hydrate a cached value after the request completes.
     *
     * @param SchedulerInterface $scheduler
     * @param string $cacheKey
     * @param callable $hydrate
     * @param array $args
     * @return void
     */
    private function scheduleHydration(SchedulerInterface $scheduler, string $cacheKey, callable $hydrate, array $args)
    {
        $time = CurrentTimeStamp::get();

        $scheduler->addJobDescriptor(
            new NormalJobDescriptor(CallbackJob::class, [
                "callback" => function () use ($cacheKey, $hydrate, $args, $time) {
                    // If the threshold has been reached, we delete the cache key and the hydration
                    // will run on the next request.
                    $newTime = CurrentTimeStamp::get();
                    if ($newTime - $time > self::RESCHEDULE_THRESHOLD) {
                        $this->cache->delete($cacheKey);
                        return;
                    }

                    $result = call_user_func_array($hydrate, $args);
                    $result = serialize($result);
                    $this->cache->set($cacheKey, $result);
                },
            ])
        );
    }

    /**
     * Invalidate all cached results for this cache.
     */
    public function invalidateAll()
    {
        $this->rolloverIncrementingKey();
        if (is_callable($this->onInvalidate)) {
            call_user_func($this->onInvalidate);
        }
    }

    /**
     * Create a pipeline processor for invalidating the entire cache on every record.
     *
     * @return ModelCacheInvalidationProcessor
     */
    public function createInvalidationProcessor(): ModelCacheInvalidationProcessor
    {
        return new ModelCacheInvalidationProcessor($this);
    }

    /**
     * Get an incrementing key that can be rolled over everytime the whole cache is invalidated.
     *
     * @return int
     */
    private function getIncrementingKey(): int
    {
        if ($this->isFeatureDisabled) {
            return 0;
        }

        $incrementKeyCacheKey = self::INCREMENTING_KEY_NAMESPACE . "-" . $this->cacheNameSpace;
        $result = $this->cache->get($incrementKeyCacheKey, 0);
        return $result;
    }

    /**
     * Update the incrementing key.
     */
    private function rolloverIncrementingKey(): void
    {
        if ($this->isFeatureDisabled) {
            return;
        }

        $incrementKeyCacheKey = self::INCREMENTING_KEY_NAMESPACE . "-" . $this->cacheNameSpace;
        $existingKey = $this->getIncrementingKey();
        $newKey = $existingKey + 1;
        if ($newKey > self::MAX_INCREMENTING_KEY) {
            // Restart from 0.
            $newKey = 0;
        }
        $this->cache->set($incrementKeyCacheKey, $newKey);
    }
}
