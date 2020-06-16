<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core\Cache;

/**
 * The base cache integration test with some useful constants.
 */
abstract class SimpleCacheTest extends \Cache\IntegrationTests\SimpleCacheTest {
    protected const MSG_TTL = 'Gdn_DirtyCache doesn\'t support TTL';
    protected const MSG_VALIDATE = 'The cache adaptor doesn\'t validate keys.';

    public const SKIP_TTL = [
        'testSetTtl' => self::MSG_TTL,
        'testSetExpiredTtl' => self::MSG_TTL,
        'testSetMultipleTtl' => self::MSG_TTL,
        'testSetMultipleExpiredTtl' => self::MSG_TTL,
        'testSetInvalidTtl' => self::MSG_TTL,
        'testSetMultipleInvalidTtl' => self::MSG_TTL,
    ];

    public const SKIP_VALIDATE_ALL = [
        'testGetInvalidKeys' => self::MSG_VALIDATE,
        'testGetMultipleInvalidKeys' => self::MSG_VALIDATE,
        'testSetInvalidKeys' => self::MSG_VALIDATE,
        'testSetMultipleInvalidKeys' => self::MSG_VALIDATE,
        'testSetMultipleNoIterable' => self::MSG_VALIDATE,
        'testHasInvalidKeys' => self::MSG_VALIDATE,
        'testDeleteInvalidKeys' => self::MSG_VALIDATE,
        'testDeleteMultipleInvalidKeys' => self::MSG_VALIDATE,
        'testDeleteMultipleNoIterable' => self::MSG_VALIDATE,
    ];

    /**
     * Create a legacy cache object for testing.
     *
     * @return \Gdn_Cache
     */
    protected abstract function createLegacyCache(): \Gdn_Cache;

    /**
     * Test a basic increment.
     */
    public function testIncrement(): void {
        $cache = $this->createLegacyCache();

        $this->assertFalse($cache->exists('aa'));
        $val = $cache->increment('aa', 1, [\Gdn_Cache::FEATURE_INITIAL => 5]);
        $this->assertSame(5, $val);
        $val2 = $cache->get('aa');
        $this->assertSame($val, $val2);
        $this->assertSame($val + 2, $cache->increment('aa', 2));
    }

    /**
     * Test a basic decrement.
     */
    public function testDecrement(): void {
        $cache = $this->createLegacyCache();

        $this->assertFalse($cache->exists('aa'));
        $val = $cache->decrement('aa', 1, [\Gdn_Cache::FEATURE_INITIAL => 5]);
        $this->assertSame(5, $val);
        $val2 = $cache->decrement('aa', 2);
        $this->assertSame($val - 2, $val2);
    }
}
