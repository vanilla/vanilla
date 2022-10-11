<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use PHPUnit\Framework\SkippedTestSuiteError;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Trait for applying memcached to a test suite.
 */
trait MemcachedTestTrait
{
    /**
     * @var \Gdn_Memcached
     */
    protected static $memcached;

    /**
     * Static setup for memcached.
     * @psalm-suppress UndefinedClass
     */
    public static function setUpBeforeClassMemcachedTestTrait(): void
    {
        $host = getenv("TEST_MEMCACHED_HOST");
        if (!empty($host)) {
            self::container()->call(function (ConfigurationInterface $config) use ($host) {
                $config->saveToConfig("Cache.Memcached.Store", [$host], false);
                $config->saveToConfig("Cache.Memcached.Option." . \Memcached::OPT_COMPRESSION, true, false);
                $config->saveToConfig(
                    "Cache.Memcached.Option." . \Memcached::OPT_DISTRIBUTION,
                    \Memcached::DISTRIBUTION_CONSISTENT,
                    false
                );
                $config->saveToConfig("Cache.Memcached.Option." . \Memcached::OPT_LIBKETAMA_COMPATIBLE, true, false);
                $config->saveToConfig("Cache.Memcached.Option." . \Memcached::OPT_NO_BLOCK, true, false);
                $config->saveToConfig("Cache.Memcached.Option." . \Memcached::OPT_TCP_NODELAY, true, false);
                $config->saveToConfig("Cache.Memcached.Option." . \Memcached::OPT_CONNECT_TIMEOUT, 1000, false);
                $config->saveToConfig("Cache.Memcached.Option." . \Memcached::OPT_SERVER_FAILURE_LIMIT, 2, false);
                $config->saveToConfig("Cache.Memcached.Option." . \Memcached::OPT_SERVER_FAILURE_LIMIT, 2, false);
                $config->saveToConfig("Cache.LockStore", "memcached", false);
            });
            self::$memcached = self::createMemcached();
            self::container()->setInstance(\Gdn_Cache::class, self::$memcached);
        } else {
            throw new SkippedTestSuiteError("Memcached is not set up for testing.");
        }
        self::container()
            ->rule(\Gdn_Cache::class)
            ->setClass(\Gdn_Memcached::class);
    }

    /**
     * Create and configure a cached object for tests.
     *
     * @param bool $useLocalCache
     * @return \Gdn_Memcached
     */
    protected static function createMemcached(bool $useLocalCache = false)
    {
        $cache = new \Gdn_Memcached();
        $cache->setStoreDefault(\Gdn_Cache::FEATURE_LOCAL, $useLocalCache);
        $cache->autorun();
        return $cache;
    }

    /**
     * @return void
     */
    public function setUpMemcachedTestTrait()
    {
        self::$memcached->flush();
    }
}
