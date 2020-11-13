<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\FeatureFlagHelper;
use Vanilla\InjectableInterface;

/**
 * Cache for records out of various models.
 *
 * Particularly good support for PipelineModel.
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

    /** @var int */
    private $incrementingKey;

    /** @var \Gdn_Cache */
    private $cache;

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
        $this->setCache($cache);
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
     * @param array $keyArgs The arguments to build the cache key.
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

        $key = $this->createCacheKey($args);
        $result = $this->cache->get($key);

        if ($result === \Gdn_Cache::CACHEOP_FAILURE) {
            if (empty($args)) {
                $result = $hydrate();
            } else {
                $result = call_user_func_array($hydrate, $args);
            }
            $options = array_merge($this->defaultCacheOptions, $cacheOptions);
            $this->cache->store($key, serialize($result), $options);
        } else {
            $result = unserialize($result);
        }

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
     * @param \Gdn_Cache $cache
     */
    public function setCache(\Gdn_Cache $cache): void {
        $this->cache = $cache;
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

        if ($this->incrementingKey === null) {
            $incrementKeyCacheKey = self::INCREMENTING_KEY_NAMESPACE . '-' . $this->cacheNameSpace;
            $result = $this->cache->get($incrementKeyCacheKey);

            if ($result === \Gdn_Cache::CACHEOP_FAILURE) {
                $result = 0;
            }
            $this->incrementingKey = $result;

            $this->cache->store($incrementKeyCacheKey, $this->incrementingKey);
        }

        return $this->incrementingKey;
    }

    /**
     * Update the incrementing key.
     */
    private function rolloverIncrementingKey(): void {
        if ($this->isFeatureDisabled) {
            return;
        }
        $key = $this->getIncrementingKey();

        $newKey = $key + 1;
        if ($newKey > self::MAX_INCREMENTING_KEY) {
            // Restart from 0.
            $newKey = 0;
        }

        $incrementKeyCacheKey = self::INCREMENTING_KEY_NAMESPACE . '-' . $this->cacheNameSpace;
        $this->incrementingKey = $newKey;
        $this->cache->store($incrementKeyCacheKey, $newKey);
    }
}
