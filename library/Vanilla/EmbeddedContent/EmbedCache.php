<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent;

use Gdn_Cache;

/**
 * Cache management for embeds.
 */
class EmbedCache {

    /** Page info caching expiry (24 hours). */
    const CACHE_EXPIRY = 24 * 60 * 60;

    /** @var Gdn_Cache */
    private $cache;

    /**
     * Constructor for DI.
     *
     * @param Gdn_Cache $cache
     */
    public function __construct(Gdn_Cache $cache) {
        $this->cache = $cache;
    }

    /**
     * Store an embed in the cache.
     *
     * @param AbstractEmbed $embed The embed instance.
     */
    public function cacheEmbed(AbstractEmbed $embed) {
        $cacheKey = $this->cacheKeyForUrl($embed->getUrl());
        $this->cache->store($cacheKey, $embed, [Gdn_Cache::FEATURE_EXPIRY => self::CACHE_EXPIRY]);
    }

    /**
     * Attempt to get an embed out of the cache.
     *
     * @param string $url The URL of the embed.
     *
     * @return AbstractEmbed|null
     */
    public function getCachedEmbed(string $url): ?AbstractEmbed {
        $cacheKey =  $this->cacheKeyForUrl($url);
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData instanceof AbstractEmbed) {
            return $cachedData;
        } else {
            return null;
        }
    }

    /**
     * Generate a cachekey for an embed url.
     *
     * @param string $url
     * @return string
     */
    private function cacheKeyForUrl(string $url): string {
        return 'EmbedService.'.md5($url);
    }
}
