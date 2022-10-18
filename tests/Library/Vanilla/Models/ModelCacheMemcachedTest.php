<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Models;

use VanillaTests\MemcachedTestTrait;

/**
 * Tests for the `ModelCache` class with memcached enabled.
 */
class ModelCacheMemcachedTest extends ModelCacheTest
{
    use MemcachedTestTrait;
}
