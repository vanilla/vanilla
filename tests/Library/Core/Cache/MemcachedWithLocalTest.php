<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core\Cache;

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
}
