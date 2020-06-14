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
class NonValidatingDirtyCacheTest extends DirtyCacheTest {
    protected const SKIP_VALIDATE = 'The CacheCacheAdapter doesn\'t validate keys.';

    public const SKIP_VALIDATE_ALL = [
        'testGetInvalidKeys' => self::SKIP_VALIDATE,
        'testGetMultipleInvalidKeys' => self::SKIP_VALIDATE,
        'testSetInvalidKeys' => self::SKIP_VALIDATE,
        'testSetMultipleInvalidKeys' => self::SKIP_VALIDATE,
        'testSetMultipleNoIterable' => self::SKIP_VALIDATE,
        'testHasInvalidKeys' => self::SKIP_VALIDATE,
        'testDeleteInvalidKeys' => self::SKIP_VALIDATE,
        'testDeleteMultipleInvalidKeys' => self::SKIP_VALIDATE,
        'testDeleteMultipleNoIterable' => self::SKIP_VALIDATE,
    ];

    /**
     * NonValidatingDirtyCacheTest constructor.
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);

        $this->skippedTests += self::SKIP_VALIDATE_ALL;
    }

    /**
     * @inheritDoc
     */
    public function createSimpleCache() {
        $cache = new CacheCacheAdapter(new \Gdn_Dirtycache());
        return $cache;
    }
}
