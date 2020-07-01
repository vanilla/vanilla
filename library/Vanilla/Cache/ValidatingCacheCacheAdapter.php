<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cache;

/**
 * An adapter from `Gdn_Cache` to `Psr\SimpleCache\CacheInterface`.
 *
 * This class validates the keys going into and out of the cache as per the PSR-16 spec. This functionality is split
 * into a separate class because Vanilla's current implementation does not validate keys, making a true transition
 * difficult.
 */
class ValidatingCacheCacheAdapter extends CacheCacheAdapter {
    /**
     * Convert a value to a string to display it in an exception.
     *
     * @param $key
     * @return string
     */
    protected final static function valueToString($key): string {
        if (is_scalar($key)) {
            return (string)$key;
        } elseif (is_object($key)) {
            if (method_exists($key, '__toString')) {
                $key->__toString();
            } else {
                return get_class($key);
            }
        } else {
            return gettype($key);
        }
    }

    /**
     * Validate a cache key according to the PSR-16 spec.
     *
     * @param mixed $key The key to validate.
     * @param bool $isArrayKey True if the key is an array key to one of the various multi methods. Array keys allow integers.
     * @return bool
     */
    public static function isKeyInvalid($key, bool $isArrayKey = false): bool {
        if (is_int($key)) {
            return !$isArrayKey;
        } elseif (!is_string($key) || $key === '') {
            return true;
        } else {
            return 1 === preg_match('`[{}()/\\\\@:]`', $key);
        }
    }

    /**
     * Validate a cache key and throw an exception if it isn't valid.
     *
     * @param mixed $key
     * @param bool $isArrayKey
     * @throws InvalidArgumentException
     */
    protected final static function validateCacheKey($key, bool $isArrayKey = false): void {
        if (static::isKeyInvalid($key, $isArrayKey)) {
            throw new InvalidArgumentException('Invalid cache key: ' . static::valueToString($key), 500);
        }
    }

    /**
     * Validate multiple cache keys and throw an exception if they aren't valid.
     *
     * @param mixed $keys The keys to test.
     * @return string[] Returns an array of valid keys.
     * @throws InvalidArgumentException
     */
    protected final static function validateCacheKeys($keys): array {
        if (is_object($keys) && $keys instanceof \Traversable) {
            $keys = iterator_to_array($keys, false);
        } elseif (!is_array($keys)) {
            throw new InvalidArgumentException('CacheInterface::getMultiple() expects $keys to be an array or Traversable.', 500);
        }

        $invalid = array_filter($keys, [static::class, 'isKeyInvalid']);
        if (!empty($invalid)) {
            throw new InvalidArgumentException('Invalid cache keys: '.implode(', ', array_map([self::class, 'valueToString'], $invalid)), 500);
        }
        return $keys;
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null) {
        self::validateCacheKey($key);
        return parent::get($key, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple($keys, $default = null) {
        $keys = self::validateCacheKeys($keys);
        return parent::getMultiple($keys, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = null) {
        self::validateCacheKey($key);
        return parent::set($key, $value, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple($values, $ttl = null) {
        if (!(is_object($values) && $values instanceof \Traversable) && !is_array($values)) {
            throw new InvalidArgumentException('CacheInterface::getMultiple() expects $keys to be an array or Traversable.', 500);
        }
        $isArrayKey = is_array($values);

        $success = true;
        foreach ($values as $key => $value) {
            self::validateCacheKey($key, $isArrayKey);
            if (parent::set($key, $value, $ttl) === false) {
                $success = false;
                break;
            }
        }
        return $success;
    }

    /**
     * {@inheritDoc}
     */
    public function has($key) {
        self::validateCacheKey($key);
        return parent::has($key);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key) {
        self::validateCacheKey($key);
        return parent::delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple($keys) {
        $keys = self::validateCacheKeys($keys);
        return parent::deleteMultiple($keys);
    }
}
