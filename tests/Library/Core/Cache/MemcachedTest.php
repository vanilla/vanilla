<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core\Cache;

use PHPUnit\Framework\SkippedTestSuiteError;
use Vanilla\Cache\ValidatingCacheCacheAdapter;
use Vanilla\Contracts\ConfigurationInterface;
use VanillaTests\BootstrapTrait;
use VanillaTests\MemcachedTestTrait;

class MemcachedTest extends SimpleCacheTest {
    use BootstrapTrait;
    use MemcachedTestTrait;

    /**
     * {@inheritDoc}
     * @psalm-suppress UndefinedClass
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        self::setUpBeforeClassBootstrap();
        self::setUpBeforeClassMemcachedTestTrait();
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        $this->setUpBootstrap();
        $this->setUpMemcachedTestTrait();
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
     *
     * @dataProvider provideSomeData
     */
    public function testDataSharding($data): void {
        $cache = $this->createLegacyCache();
        $stored = $cache->store(__FUNCTION__, $data, [\Gdn_Cache::FEATURE_SHARD => true]);
        $this->assertNotSame(\Gdn_Cache::CACHEOP_FAILURE, $stored);

        $actual = $cache->get(__FUNCTION__);
        $this->assertSame($data, $actual);

        $actual2 = $cache->get([__FUNCTION__], null);
        $this->assertSame($data, $actual2[__FUNCTION__]);
    }

    /**
     * Provide some different types of data.
     *
     * @return array
     */
    public function provideSomeData(): array {
        $r = [
            'array' => [array_fill(0, 100, 'foo')],
            'string' => ['foo'],
            'int' => [123],
        ];
        return $r;
    }
}
