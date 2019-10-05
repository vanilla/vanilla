<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Adapters;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Simple\ArrayCache;
use Symfony\Component\Cache\Simple\ChainCache;
use Symfony\Component\Cache\Simple\MemcachedCache;

/**
 * Adapt SimpleCache from Gdn_Cache.
 *
 * This is a backwards-compatibility class so that we can use a standards-based cache class in new code.
 */
final class SimpleCacheAdapter {
    /**
     * Protect the class from instantiation.
     */
    private function __construct() {
    }

    /**
     * Create a CacheInterface from a Gdn_Cache object.
     *
     * @param \Gdn_Cache $cache
     * @return CacheInterface
     */
    public static function fromGdnCache(\Gdn_Cache $cache): CacheInterface {
        switch (get_class($cache)) {
            case \Gdn_Memcached::class:
                /* @var \Gdn_Memcached $cache */
                $result = new ChainCache([
                    new ArrayCache(),
                    new MemcachedCache($cache->getMemcached()),
                ]);

                break;
            default:
                $result = new ArrayCache();
        }
        return $result;
    }
}
