<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers;

use League\Uri\Http;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Tests for the `EntryController` class.
 *
 * These tests aren't exhaustive. If more tests are added then we may need to tweak this class to use the `SiteTestTrait`.
 */
class EntryControllerTest extends VanillaTestCase {
    use SiteTestTrait, SetupTraitsTrait;

    /**
     * @var \EntryController
     */
    private $controller;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->setUpTestTraits();

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
     * Test Target as an empty string.
     */
    public function testEmptyTarget(): void {
        $expected = url('/', true);
        $this->controller->Request->setQuery(['target' => '']);
        $actual = $this->controller->target(false);
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

    /**
     * Test a basic registration flow.
     */
    public function testRegisterBasic(): void {
        $this->runWithConfig(['Garden.Registration.Method' => 'Basic'], function () {
            $user = self::sprintfCounter([
                'Name' => 'test%s',
                'Email' => 'test%s@example.com',
                'Password' => __FUNCTION__,
                'PasswordMatch' => __FUNCTION__,
                'TermsOfService' => '1',
            ]);

            $r = $this->bessy()->post('/entry/register', $user);
            $welcome = $this->assertEmailSentTo($user['Email']);

            // The user has registered. Let's simulate clicking on the confirmation email.
            $emailUrl = Http::createFromString($welcome->template->getButtonUrl());
            $this->assertStringContainsString('/entry/emailconfirm', $emailUrl->getPath());

            parse_str($emailUrl->getQuery(), $query);
            $this->assertArraySubsetRecursive(
                [
                    'vn_medium' => 'email',
                    'vn_campaign' => 'welcome',
                    'vn_source' => 'register',
                ],
                $query
            );

            $r2 = $this->bessy()->get($welcome->template->getButtonUrl(), [], []);
            $this->assertTrue($r2->data('EmailConfirmed'));
            $this->assertSame((int)$r->data('UserID'), \Gdn::session()->UserID);
        });
    }
}
