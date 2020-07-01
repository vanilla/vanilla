<?php
/**
 * Gdn_Dirtycache
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

/**
 * Cache Layer: Dirty.
 *
 * This is a cache implementation that caches values in memory only for the time of the request.
 */
class Gdn_Dirtycache extends Gdn_Cache {

    /** @var array  */
    protected $cache = [];

    /**
     * Class constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->cacheType = Gdn_Cache::CACHE_TYPE_NULL;
    }

    /**
     * {@inheritDoc}
     */
    public function addContainer($options) {
        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     * {@inheritDoc}
     */
    public function add($key, $value, $options = []) {
        return $this->store($key, $value, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function store($key, $value, $options = []) {
        if (is_object($value)) {
            // Objects should store in the cache as separate copies.
            $value = clone $value;
        }
        $this->cache[$key] = $value;
        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key) {
        return array_key_exists($key, $this->cache);
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $options = []) {
        if ($hasDefault = array_key_exists(self::FEATURE_DEFAULT, $options)) {
            $default = $options[self::FEATURE_DEFAULT];
        } else {
            $default = self::CACHEOP_FAILURE;
        }

        if (is_array($key)) {
            $result = [];
            foreach ($key as $k) {
                if (isset($this->cache[$k])) {
                    $result[$k] = $this->cache[$k];
                } elseif ($hasDefault) {
                    $result[$k] = $default;
                }
            }
            return $result;
        } else {
            if (array_key_exists($key, $this->cache)) {
                return $this->cache[$key];
            } else {
                return $default;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function remove($key, $options = []) {
        unset($this->cache[$key]);

        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     * {@inheritDoc}
     */
    public function replace($key, $value, $options = []) {
        $this->cache[$key] = $value;
        return Gdn_Cache::CACHEOP_SUCCESS;
    }

    /**
     * {@inheritDoc}
     */
    public function increment($key, $amount = 1, $options = []) {
        $options += [
            self::FEATURE_INITIAL => 0
        ];

        if (array_key_exists($key, $this->cache)) {
            $result = $this->cache[$key] += $amount;
        } elseif ($options[self::FEATURE_INITIAL] != 0) {
            $result = $this->cache[$key] = $options[self::FEATURE_INITIAL];
        } else {
            $result = self::CACHEOP_FAILURE;
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function decrement($key, $amount = 1, $options = []) {
        $options += [
            self::FEATURE_INITIAL => 0
        ];

        if (array_key_exists($key, $this->cache)) {
            $result = $this->cache[$key] -= $amount;
        } elseif ($options[self::FEATURE_INITIAL] != 0) {
            $result = $this->cache[$key] = $options[self::FEATURE_INITIAL];
        } else {
            $result = self::CACHEOP_FAILURE;
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function flush() {
        $this->cache = [];
        return true;
    }
}
