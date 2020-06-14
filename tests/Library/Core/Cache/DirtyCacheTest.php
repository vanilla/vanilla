<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core\Caching;

use Cache\IntegrationTests\SimpleCacheTest;
use Vanilla\Cache\CacheCacheAdapter;
use Vanilla\Cache\ValidatingCacheCacheAdapter;

/**
 * Integration tests for `Gdn_DirtyCache`.
 */
class DirtyCacheTest extends SimpleCacheTest {
    protected const SKIP_TTL = 'Gdn_DirtyCache doesn\'t support TTL';

    /**
     * {@inheritDoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);

        $this->skippedTests = [
            'testSetTtl' => self::SKIP_TTL,
            'testSetExpiredTtl' => self::SKIP_TTL,
            'testSetMultipleTtl' => self::SKIP_TTL,
            'testSetMultipleExpiredTtl' => self::SKIP_TTL,
            'testSetInvalidTtl' => self::SKIP_TTL,
            'testSetMultipleInvalidTtl' => self::SKIP_TTL,
        ];
    }

    /**
     * @inheritDoc
     */
    public function createSimpleCache() {
        $cache = new ValidatingCacheCacheAdapter(new \Gdn_Dirtycache());
        return $cache;
    }
}
