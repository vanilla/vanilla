<?php
/**
 * Gdn_Dirtycache
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Cache Layer: Dirty.
 *
 * This is a cache implementation that caches nothing and always reports cache misses.
 */
class Gdn_Dirtycache extends Gdn_Cache {

    /** @var array  */
    protected $cache = [];

    /**
     *
     */
    public function __construct() {
        parent::__construct();
        $this->cacheType = Gdn_Cache::CACHE_TYPE_NULL;
    }

    public function addContainer($options) {
        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    public function add($key, $value, $options = []) {
        return $this->store($key, $value, $options);
    }

    public function store($key, $value, $options = []) {
        $this->cache[$key] = $value;
        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    public function exists($key) {
        return Gdn_Cache::CACHEOP_FAILURE;
    }

    public function get($key, $options = []) {
        if (is_array($key)) {
            $result = [];
            foreach ($key as $k) {
                if (isset($this->cache[$k])) {
                    $result[$k] = $this->cache[$k];
                }
            }
            return $result;
        } else {
            if (array_key_exists($key, $this->cache)) {
                return $this->cache[$key];
            } else {
                return Gdn_Cache::CACHEOP_FAILURE;
            }
        }
    }

    public function remove($key, $options = []) {
        unset($this->cache[$key]);

        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    public function replace($key, $value, $options = []) {
        $this->cache[$key] = $value;
        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    public function increment($key, $amount = 1, $options = []) {
        $value = array_key_exists($key, $this->cache) ? intval($this->cache[$key]) : 0;
        $value += $amount;
        $this->cache[$key] = $value;
        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    public function decrement($key, $amount = 1, $options = []) {
        $value = array_key_exists($key, $this->cache) ? intval($this->cache[$key]) : 0;
        $value -= $amount;
        $this->cache[$key] = $value;
        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    public function flush() {
        return true;
    }
}
