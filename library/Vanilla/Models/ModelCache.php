<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\Psr16Adapter;
use Symfony\Contracts\Cache\ItemInterface;
use Vanilla\Cache\CacheCacheAdapter;
use Vanilla\FeatureFlagHelper;
use Vanilla\InjectableInterface;

/**
 * Cache for records out of various models.
 *
 * Features:
 * - Can give an invalidation processor for the pipeline model.
 * - Uses symfony cache contracts:
 *   - A lock is aquired when calculating a cache value to prevent instances contending for resources to generate the same value.
 *   - Values may be recalculated early on random requests to prevent the value from expiring.
 */
class ModelCache implements InjectableInterface {

    /** @var int When we hit this size of incrementing key, we reset from 0. */
    const MAX_INCREMENTING_KEY = 1000000;

    /** @var string */
    const INCREMENTING_KEY_NAMESPACE = 'vanillaIncrementingKey';

    const GLOBAL_DEFAULT_OPTIONS = [
        \Gdn_Cache::FEATURE_EXPIRY => 600,
    ];

    const DISABLE_FEATURE_FLAG = "DisableNewModelCaching";

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

    /**
     * Constructor.
     *
     * @param string $cacheNameSpace Namespace to use in the cache.
     * @param \Gdn_Cache $cache The cache instance.
     * @param array $defaultCacheOptions Default options to apply for storing cache values.
     */
    public function __construct(string $cacheNameSpace, \Gdn_Cache $cache, array $defaultCacheOptions = []) {
        $this->cache = new CacheCacheAdapter($cache);
        $cacheContract = new Psr16Adapter($this->cache, $cacheNameSpace);
        $this->setCacheContract($cacheContract);
        $this->cacheNameSpace = $cacheNameSpace;
        $this->defaultCacheOptions = array_merge(self::GLOBAL_DEFAULT_OPTIONS, $defaultCacheOptions ?? []);
        $this->isFeatureDisabled = FeatureFlagHelper::featureEnabled(self::DISABLE_FEATURE_FLAG);
    }

    /**
     * Create a cache key for some parameters.
     *
     * @param array $keyArgs Some arguments to generate the cache key from.
     *
     * @return string
     */
    public function createCacheKey(array $keyArgs): string {
        $key = $this->cacheNameSpace . '-' . $this->getIncrementingKey() . '-' . sha1(json_encode($keyArgs));
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
    public function getCachedOrHydrate(array $args, callable $hydrate, array $cacheOptions = []) {
        if ($this->isFeatureDisabled) {
            if (empty($args)) {
                return $hydrate();
            } else {
                return call_user_func_array($hydrate, $args);
            }
        }
        $ttl = $cacheOptions[\Gdn_Cache::FEATURE_EXPIRY] ?? $this->defaultCacheOptions[\Gdn_Cache::FEATURE_EXPIRY];
        $key = $this->createCacheKey($args);
        $result = $this->cacheContract->get($key, function (ItemInterface $item) use ($args, $hydrate, $ttl) {
            if (empty($args)) {
                $result = $hydrate();
            } else {
                $result = call_user_func_array($hydrate, $args);
            }
            $result = serialize($result);
            $item->expiresAfter($ttl);
            return $result;
        });
        $result = unserialize($result);
        return $result;
    }

    /**
     * Invalidate all cached results for this cache.
     */
    public function invalidateAll() {
        $this->rolloverIncrementingKey();
    }

    /**
     * Create a pipeline processor for invalidating the entire cache on every record.
     *
     * @return ModelCacheInvalidationProcessor
     */
    public function createInvalidationProcessor(): ModelCacheInvalidationProcessor {
        return new ModelCacheInvalidationProcessor($this);
    }

    /**
     * @param \Symfony\Contracts\Cache\CacheInterface $cacheContract
     */
    public function setCacheContract(\Symfony\Contracts\Cache\CacheInterface $cacheContract): void {
        $this->cacheContract = $cacheContract;
    }

    /**
     * Get an incrementing key that can be rolled over everytime the whole cache is invalidated.
     *
     * @return int
     */
    private function getIncrementingKey(): int {
        if ($this->isFeatureDisabled) {
            return 0;
        }

        $incrementKeyCacheKey = self::INCREMENTING_KEY_NAMESPACE . '-' . $this->cacheNameSpace;
        $result = $this->cache->get($incrementKeyCacheKey, 0);
        return $result;
    }

    /**
     * Update the incrementing key.
     */
    private function rolloverIncrementingKey(): void {
        if ($this->isFeatureDisabled) {
            return;
        }

        $incrementKeyCacheKey = self::INCREMENTING_KEY_NAMESPACE . '-' . $this->cacheNameSpace;
        $existingKey = $this->getIncrementingKey();
        $newKey = $existingKey + 1;
        if ($newKey > self::MAX_INCREMENTING_KEY) {
            // Restart from 0.
            $newKey = 0;
        }
        $this->cache->set($incrementKeyCacheKey, $newKey);
    }
}
