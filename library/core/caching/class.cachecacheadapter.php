<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Class CacheCacheAdapter
 */
class CacheCacheAdapter implements \Vanilla\CacheInterface {

    /**
     * @var Gdn_Cache
     */
    private $cacheObject;

    /**
     * CacheCacheAdapter constructor.
     *
     * @param Gdn_Cache $cacheObject
     */
    public function __construct(Gdn_Cache $cacheObject) {
        $this->cacheObject = $cacheObject;
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
        $value = $this->cacheObject->get($key);
        if ($value === false) {
            $value = $default;
        }
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null) {
        $options = [];
        if ($ttl !== null) {
            $options[\Gdn_Cache::FEATURE_EXPIRY] = $this->ttlToSeconds($ttl);
        }
        return $this->cacheObject->store($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key) {
        return $this->cacheObject->remove($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null) {
        if (is_object($keys) && $keys instanceof iterable) {
            $keys = iterator_to_array($keys);
        }

        $result = $this->cacheObject->get($keys);
        if (count($keys) >= count($result)) {
            $result += array_fill_keys($keys, $default);
        }

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null) {
        $success = true;
        foreach ($values as $key => $value) {
            if ($this->set($key, $value, $ttl) === false) {
                $success = false;
                break;
            }
        }
        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys) {
        $success = true;
        foreach ($keys as $key) {
            if ($this->delete($key) === false) {
                $success = false;
                break;
            }
        }
        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key) {
        return $this->cacheObject->exists($key);
    }


    /**
     * @inheritDoc
     */
    public function clear() {
        return $this->cacheObject->flush();
    }
}
