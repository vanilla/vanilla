<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Adapters;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Simple\MemcachedCache;
use Symfony\Component\Cache\Simple\NullCache;

/**
 * Adapt SimpleCache from Gdn_Cache.
 *
 * This is a backwards-compatibility class so that we can use a standards-based cache class in new code.
 */
class SimpleCacheAdapter {
    public static function fromGdnCache(\Gdn_Cache $cache): CacheInterface {
        switch (get_class($cache)) {
            case \Gdn_Memcached::class:
                /* @var \Gdn_Memcached $cache */
                $result = new MemcachedCache($cache->getMemcached());
                break;
            default:
                $result = new NullCache();
        }
        return $result;
    }
}
