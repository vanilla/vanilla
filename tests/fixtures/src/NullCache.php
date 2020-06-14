<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use \Gdn_Cache;

/**
 * Class NullCache.
 *
 * A real nullcache object. Gdn_DirtyCache needs to be kept as is for some weird reason.
 * This class is what Gdn_DirtyCache should be.
 */
class NullCache extends Gdn_Cache {

    /**
     * NullCache constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->cacheType = Gdn_Cache::CACHE_TYPE_NULL;
    }

    /**
     * @param $options
     * @return bool
     */
    public function addContainer($options) {
        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param array $options
     * @return bool
     */
    public function add($key, $value, $options = []) {
        return $this->store($key, $value, $options);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param array $options
     * @return bool
     */
    public function store($key, $value, $options = []) {
        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     * @param $key
     * @return bool
     */
    public function exists($key) {
        return Gdn_Cache::CACHEOP_FAILURE;
    }

    /**
     * @param $key
     * @param array $options
     * @return mixed
     */
    public function get($key, $options = []) {
        if (is_array($key)) {
            return [];
        } else {
            return Gdn_Cache::CACHEOP_FAILURE;
        }
    }

    /**
     * @param $key
     * @param array $options
     * @return bool
     */
    public function remove($key, $options = []) {
        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     * @param $key
     * @param $value
     * @param array $options
     * @return bool
     */
    public function replace($key, $value, $options = []) {
        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     * @param $key
     * @param int $amount
     * @param array $options
     * @return bool
     */
    public function increment($key, $amount = 1, $options = []) {
        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     * @param $key
     * @param int $amount
     * @param array $options
     * @return bool
     */
    public function decrement($key, $amount = 1, $options = []) {
        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     * @return bool
     */
    public function flush() {
        return true;
    }
}
