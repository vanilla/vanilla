<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
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
     * @inheritDoc
     */
    public function get($key, $default = null) {
        $value = $this->cacheObject->get($key);
        if ($value === false) {
            $value = $default;
        }
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null) {
        $options = [];
        if ($ttl !== null) {
            $options[FEATURE_EXPIRY] = $ttl;
        }
        return $this->cacheObject->store($key, $value, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function delete($key) {
        return $this->cacheObject->remove($key);
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null) {
        $result = [];
        foreach($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null) {
        $success = true;
        foreach($values as $key => $value) {
            if ($this->set($key, $value, $ttl) === false) {
                $success = false;
                break;
            }
        }
        return $success;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys) {
        $success = true;
        foreach($values as $key) {
            if ($this->delete($key) === false) {
                $success = false;
                break;
            }
        }
        return $success;
    }

    /**
     * @inheritDoc
     */
    public function has($key) {
        return $this->cacheObject->exists($key);
    }


}
