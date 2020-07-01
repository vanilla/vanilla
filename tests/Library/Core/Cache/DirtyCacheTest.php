<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core\Cache;

use Vanilla\Cache\ValidatingCacheCacheAdapter;

/**
 * Integration tests for `Gdn_DirtyCache`.
 */
class DirtyCacheTest extends SimpleCacheTest {
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
    public function createSimpleCache() {
        $cache = new ValidatingCacheCacheAdapter($this->createLegacyCache());
        return $cache;
    }

    /**
     * @inheritDoc
     */
    protected function createLegacyCache(): \Gdn_Cache {
        return new \Gdn_Dirtycache();
    }
}
