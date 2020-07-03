<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers;

use PHPUnit\Framework\TestCase;
use VanillaTests\BootstrapTrait;
use VanillaTests\SetupTraitsTrait;

/**
 * Tests for the `EntryController` class.
 *
 * These tests aren't exhaustive. If more tests are added then we may need to tweak this class to use the `SiteTestTrait`.
 */
class EntryControllerTest extends TestCase {
    use BootstrapTrait, SetupTraitsTrait;

    /**
     * @var \EntryController
     */
    private $controller;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->setupTestTraits();

        $this->controller = $this->container()->get(\EntryController::class);
        $this->controller->getImports();
        $this->controller->Request = $this->container()->get(\Gdn_Request::class);
        $this->controller->initialize();
    }

    /**
     * Target URLs should be checked for safety and UX.
     *
     * @param string|false $url
     * @param string $expected
     * @dataProvider provideTargets
     */
    public function testTarget($url, string $expected): void {
        $expected = url($expected, true);

        $actual = $this->controller->target($url);
        $this->assertSame($expected, $actual);
    }

    /**
     * The querystring and form should control the target.
     */
    public function testTargetFallback(): void {
        $target = url('/foo', true);
        $this->controller->Request->setQuery(['target' => $target]);

        $this->assertSame($target, $this->controller->target());

        $target2 = url('/bar', true);
        $this->controller->Form->setFormValue('Target', $target2);
        $this->assertSame($target2, $this->controller->target());
    }

    /**
     * Provide some sign out target tests.
     *
     * @return array
     */
    public function provideTargets(): array {
        $r = [
            ['/foo', '/foo'],
            ['entry/signin', '/'],
            ['entry/signout?foo=bar', '/'],
            ['/entry/autosignedout', '/'],
            ['/entry/autosignedout234', '/entry/autosignedout234'],
            ['https://danger.test/hack', '/'],
            [false, '/'],
        ];

        return array_column($r, null, 0);
    }
}
