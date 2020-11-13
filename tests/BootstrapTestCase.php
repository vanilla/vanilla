<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

/**
 * A base class for tests that require Vanilla's bootstrap.
 *
 * Use this as a base class if you are writing functional tests that need to use objects together, but don't need a copy
 * of Vanilla installed. This is usually the case if the following is true:
 *
 * 1. You aren't testing functionality that requires the database.
 * 2. You are testing generic database functionality and not specific application tables.
 */
class BootstrapTestCase extends VanillaTestCase {
    use BootstrapTrait, SetupTraitsTrait;

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
