<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core\Cache;


use Vanilla\Cache\CacheCacheAdapter;

/**
 * Test the dirty cache without validation.
 */
class NonValidatingDirtyCacheTest extends SimpleCacheTest {
    /**
     * NonValidatingDirtyCacheTest constructor.
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);

        $this->skippedTests = self::SKIP_TTL + self::SKIP_VALIDATE_ALL;
    }

    /**
     * @inheritDoc
     */
    public function createSimpleCache() {
        $cache = new CacheCacheAdapter($this->createLegacyCache());
        return $cache;
    }

    /**
     * @inheritDoc
     */
    protected function createLegacyCache(): \Gdn_Cache {
        return new \Gdn_Dirtycache();
    }
}
