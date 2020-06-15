<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cache;

use Gdn_Cache;
use Psr\SimpleCache\CacheInterface;

/**
 * An adapter from `Gdn_Cache` to `Psr\SimpleCache\CacheInterface`.
 *
 * This class adapts the functionality, but not all of the validation of the `CacheInterface`. If you want to validate
 * the keys going into and out of the cache then use the `ValidatingCacheCacheAdapter` class.
 */
class CacheCacheAdapter implements CacheInterface {
    /**
     * @var Gdn_Cache
     */
    private $cache;

    /**
     * CacheCacheAdapter constructor.
     *
     * @param Gdn_Cache $cache
     */
    public function __construct(Gdn_Cache $cache) {
        $this->cache = $cache;
    }

    /**
     * Convert a TTL to seconds.
     *
     * @param int|\DateInterval $ttl
     * @return int|null Returns a number of seconds or **null** on failture.
     */
    protected function ttlToSeconds($ttl): ?int {
        if (is_numeric($ttl)) {
            return (int)$ttl;
        } elseif (is_object($ttl) && $ttl instanceof \DateInterval) {
            return $ttl->s + 60 * $ttl->i + 3600 * $ttl->h + 86400 * $ttl->d;
        } else {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null) {
        $value = $this->cache->get($key, [Gdn_Cache::FEATURE_DEFAULT => $default]);
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null) {
        $options = [];
        if (is_int($ttl) || (is_object($ttl) && $ttl instanceof \DateInterval)) {
            $secs = $this->ttlToSeconds($ttl);
            // An already expired TTL should remove the item.
            if ($secs <= 0) {
                $this->delete($key);
                return true;
            }
            $options[\Gdn_Cache::FEATURE_EXPIRY] = $secs;
        } elseif (!is_null($ttl)) {
            throw new InvalidArgumentException("The TTL must be an integer or a DateInterval.", 500);
        }
        return $this->cache->store($key, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key) {
        // Gdn_Cache returns `false` if the item isn't removed while `CacheInterface` only wants false on error.
        try {
            $this->cache->remove($key);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null) {
        if (is_object($keys) && $keys instanceof \Traversable) {
            $keys = iterator_to_array($keys, false);
        } elseif (!is_array($keys)) {
            throw new InvalidArgumentException('CacheInterface::getMultiple() expects $keys to be an array or Traversable.', 500);
        }

        $r = $this->cache->get($keys) + array_fill_keys($keys, $default);

        return $r;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null) {
        $success = true;
        foreach ($values as $key => $value) {
            $success = $success && $this->set($key, $value, $ttl);
        }
        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys) {
        $success = true;
        foreach ($keys as $key) {
            $success = $success && $this->delete($key);
        }
        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key) {
        return $this->cache->exists($key);
    }


    /**
     * @inheritDoc
     */
    public function clear() {
        return $this->cache->flush();
    }
}
