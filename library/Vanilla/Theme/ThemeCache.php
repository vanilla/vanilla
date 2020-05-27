<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

use Vanilla\Contracts\ConfigurationInterface;

/**
 * Implement caching for the theme service.
 */
class ThemeCache {

    /** @var string Cache key for holding other cache keys for easy invalidation. */
    const CACHE_HOLDER_KEY = 'singleThemeCacheHolder';

    /** @var string */
    const CACHE_KEY = "theme";

    /** @var int 10 minute cache interval. */
    const CACHE_TTL = 600;

    /** @var \Gdn_Cache */
    private $cache;

    /** @var bool */
    private $isEnabled;

    /**
     * DI.
     *
     * @param \Gdn_Cache $cache
     * @param ConfigurationInterface $config
     */
    public function __construct(\Gdn_Cache $cache, ConfigurationInterface $config) {
        $this->cache = $cache;

        // Disable caching while in debug mode.
        $this->isEnabled = !$config->get('Debug', false);
    }

    /**
     * Try to fetch a theme from the cache.
     *
     * @param string $key
     * @return array|null
     */
    public function get(string $key): ?Theme {
        if (!$this->isEnabled) {
            return null;
        }
        $cacheResult = $this->cache->get($key);
        if ($cacheResult) {
            $theme = unserialize($cacheResult) ?: null;
            if ($theme instanceof Theme) {
                $theme->setIsCacheHit(true);
            }
            return $theme;
        } else {
            return null;
        }
    }

    /**
     * Cache a value.
     *
     * @param string $key
     * @param Theme $theme
     */
    public function set(string $key, Theme $theme) {
        $this->addCacheKey($key);
        $this->cache->store($key, serialize($theme), [
            \Gdn_Cache::FEATURE_EXPIRY => self::CACHE_TTL,
        ]);
    }

    /**
     * Clear all known caches of navigation.
     */
    public function clear() {
        $allCacheKeys = $this->cache->get(self::CACHE_HOLDER_KEY) ?: [];

        foreach ($allCacheKeys as $cacheKey) {
            $result = $this->cache->remove($cacheKey);
            if ($result === \Gdn_Cache::CACHEOP_FAILURE) {
                trigger_error('Failed to clear theme cache', E_USER_NOTICE);
            }
        }
        $this->cache->remove(self::CACHE_HOLDER_KEY);
    }

    /**
     * Generate a cache key.
     *
     * @param string|int $themeID
     * @param array $args
     * @return string
     */
    public function cacheKey($themeID, array $args = []): string {
        // Put args in a stable order.
        ksort($args);

        $cacheKey = self::CACHE_KEY . '_' . $themeID.md5(json_encode($args));

        return $cacheKey;
    }

    /**
     * Add a cache key to the aggregate known cache keys.
     *
     * @param string $key The cache key to hold.
     */
    private function addCacheKey(string $key) {
        $existingKeys = $this->cache->get(self::CACHE_HOLDER_KEY, []);
        $existingKeys[] = $key;
        $this->cache->store(self::CACHE_HOLDER_KEY, $existingKeys);
    }
}
