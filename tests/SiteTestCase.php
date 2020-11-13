<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

/**
 * A base class for tests that require Vanilla to be installed.
 *
 * Use this test case if you are writing integration tests on the application. Each test class will get a fresh copy
 * of Vanilla installed for all of your tests. Here is the basic procedure:
 *
 * 1. Subclass the `SiteTestCase` class.
 * 2. If you want to test with non-standard addons then override the `getAddons()` method and return an array of addon
 * keys that you would like to test with.
 * 3. If you need to add any custom set up or tear down methods then make sure to call parent.
 * 4. You can include other test traits and they should set up and tear down automatically.
 *
 */
class SiteTestCase extends VanillaTestCase {
    use SiteTestTrait, SetupTraitsTrait;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        static::setUpBeforeClassTestTraits();
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->setUpTestTraits();
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void {
        parent::tearDown();
        $this->tearDownTestTraits();
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void {
        parent::tearDownAfterClass();
        static::tearDownAfterClassTestTraits();
    }
}
