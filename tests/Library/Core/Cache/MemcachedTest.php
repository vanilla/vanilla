<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core\Cache;

use Vanilla\Cache\ValidatingCacheCacheAdapter;
use Vanilla\Contracts\ConfigurationInterface;
use VanillaTests\BootstrapTrait;

class MemcachedTest extends SimpleCacheTest {
    use BootstrapTrait;

    /**
     * @var \Gdn_Memcached
     */
    protected static $memcached;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        self::setUpBeforeClassBootstrap();

        $host = getenv('TEST_MEMCACHED_HOST');
        if (!empty($host)) {
            self::container()->call(function (
                ConfigurationInterface $config
            ) use ($host) {
                $config->saveToConfig('Cache.Memcached.Store', [$host], false);
                $config->saveToConfig('Cache.Memcached.Option.' . \Memcached::OPT_COMPRESSION, true, false);
                $config->saveToConfig('Cache.Memcached.Option.' . \Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT, false);
                $config->saveToConfig('Cache.Memcached.Option.' . \Memcached::OPT_LIBKETAMA_COMPATIBLE, true, false);
                $config->saveToConfig('Cache.Memcached.Option.' . \Memcached::OPT_NO_BLOCK, true, false);
                $config->saveToConfig('Cache.Memcached.Option.' . \Memcached::OPT_TCP_NODELAY, true, false);
                $config->saveToConfig('Cache.Memcached.Option.' . \Memcached::OPT_CONNECT_TIMEOUT, 1000, false);
                $config->saveToConfig('Cache.Memcached.Option.' . \Memcached::OPT_SERVER_FAILURE_LIMIT, 2, false);
                $config->saveToConfig('Cache.Memcached.Option.' . \Memcached::OPT_SERVER_FAILURE_LIMIT, 2, false);
            });
            self::$memcached = self::createMemcached();
        } else {
            self::$memcached = null;
        }
    }

    /**
     * Create and configure a cached object for tests.
     *
     * @param bool $useLocalCache
     * @return \Gdn_Memcached
     */
    protected static function createMemcached(bool $useLocalCache = false) {
        $cache = new \Gdn_Memcached();
        $cache->setStoreDefault(\Gdn_Cache::FEATURE_LOCAL, $useLocalCache);
        $cache->autorun();
        return $cache;
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        if (self::$memcached !== null) {
            self::$memcached->flush();
        } else {
            $this->markTestSkipped("Memcached is not set up for testing.");
        }
        $this->setupBoostrapTrait();
        parent::setUp();
    }

    /**
     * @inheritDoc
     */
    public function createSimpleCache() {
        return new ValidatingCacheCacheAdapter(self::$memcached);
    }

    /**
     * @inheritDoc
     */
    protected function createLegacyCache(): \Gdn_Cache {
        return self::$memcached;
    }

    /**
     * Keys should be able to be busted into shards.
     */
    public function testDataSharding(): void {
        $cache = $this->createLegacyCache();
        $data = array_fill(0, 1000, 'foo');
        $stored = $cache->store(__FUNCTION__, $data, [\Gdn_Cache::FEATURE_SHARD => true]);
        $this->assertNotSame(\Gdn_Cache::CACHEOP_FAILURE, $stored);

        $actual = $cache->get(__FUNCTION__);
        $this->assertSame($data, $actual);

        $actual2 = $cache->get([__FUNCTION__]);
        $this->assertSame($data, $actual2[__FUNCTION__]);
    }
}
