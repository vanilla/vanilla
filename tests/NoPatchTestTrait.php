<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use PHPUnit\Framework\TestCase;

/**
 * @package VanillaTestsUse this trait on `AbstractResourceTest` classes that don't have a `PATCH` endpoint.
 */
trait NoPatchTestTrait {
    /**
     * {@inheritDoc}
     */
    public function testPatchFull() {
        TestCase::markTestSkipped("The resource doesn't have a PATCH endpoint.");
    }

    /**
     * {@inheritDoc}
     */
    public function providePatchFields() {
        return [];
    }
}
