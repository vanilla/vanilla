<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * Test our memcached adaptor with the local cache enabled.
 */
class MemcachedWithLocalTest extends MemcachedTest {
    /**
     * {@inheritDoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);
        $this->skippedTests = self::SKIP_TTL;
    }

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        if (self::$memcached !== null) {
            self::$memcached->setStoreDefault(\Gdn_Cache::FEATURE_LOCAL, true);
        }
    }

    /**
     * The container should fulfill the cache.
     *
     * This is as close as we can get to testing the actual container integration.
     */
    public function testContainerIntegration(): void {
        $this->container()->call(function (CacheInterface $cache) {
            $this->assertNotNull($cache);
        });
    }

    /**
     * Test that our local cache is primed when getting values.
     */
    public function testLocalWorks() {
        \Gdn_Cache::trace(true);
        $memcached = self::$memcached;
        $memcached->store('localKey', 'hello world', [\Gdn_Cache::FEATURE_LOCAL => false]);
        $val = $memcached->get('localKey');
        $this->assertEquals('hello world', $val);
        $memcached->get('localKey');
        $memcached->get('localKey');
        $this->assertEquals(1, \Gdn_Cache::$trackGets, "Only one actual memcached get should be done. Rest should come from local.");
    }
}
