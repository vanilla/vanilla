<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers;

use AccessTokenModel;
use BanModel;
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

    private $userData;
    
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
        $this->userData = $this->insertDummyUser();
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

    /**
     * If account has been banned by a ban rule.
     */
    public function testBannedAutomaticSignin(): void {
        $postBody = ['Email' => $this->userData['Email'], 'Password' => $this->userData['Email'], 'RememberMe' => 1];

        $this->userData = $this->userModel->getID($this->userData['UserID'], DATASET_TYPE_ARRAY);
        $banned = val('Banned', $this->userData, 0);
        $userData = [
            "UserID" => $this->userData['UserID'],
            "Banned" => BanModel::setBanned($banned, true, BanModel::BAN_AUTOMATIC)
        ];
        $this->userModel->save($userData);

        $this->expectExceptionMessage(t('This account has been banned.'));
        $r = $this->bessy()->post('/entry/signin', $postBody);
    }

    /**
     * If account has been banned manually.
     */
    public function testBannedManualSignin(): void {
        $postBody = ['Email' => $this->userData['Email'], 'Password' => $this->userData['Email'], 'RememberMe' => 1];

        $this->userData = $this->userModel->getID($this->userData['UserID'], DATASET_TYPE_ARRAY);
        $banned = val('Banned', $this->userData, 0);
        $userData = [
            "UserID" => $this->userData['UserID'],
            "Banned" => BanModel::setBanned($banned, true, BanModel::BAN_MANUAL)
        ];
        $this->userModel->save($userData);

        $this->expectExceptionMessage(t('This account has been banned.'));
        $r = $this->bessy()->post('/entry/signin', $postBody);
    }

    /**
     * If account has been banned by the "Warnings and notes" plugin or similar.
     */
    public function testBannedWarningSignin(): void {
        $postBody = ['Email' => $this->userData['Email'], 'Password' => $this->userData['Email'], 'RememberMe' => 1];

        $this->userData = $this->userModel->getID($this->userData['UserID'], DATASET_TYPE_ARRAY);
        $banned = val('Banned', $this->userData, 0);
        $userData = [
            "UserID" => $this->userData['UserID'],
            "Banned" => BanModel::setBanned($banned, true, BanModel::BAN_WARNING)
        ];
        $this->userModel->save($userData);

        $this->expectExceptionMessage(t('This account has been temporarily banned.'));
        $r = $this->bessy()->post('/entry/signin', $postBody);
    }

    /**
     * Test checkAccessToken().
     *
     * @param string $path
     * @param bool $valid
     * @dataProvider providePathData
     */
    public function testTokenAuthentication(string $path, bool $valid): void {
        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start([1]);
        $userID = $this->createUserFixture(VanillaTestCase::ROLE_MEMBER);
        /** @var \AccessTokenModel $tokenModel */
        $tokenModel = $this->container()->get(\AccessTokenModel::class);
        $tokenModel->issue($userID);
        $accessToken = $tokenModel->getWhere(['UserID' => $userID])->firstRow(DATASET_TYPE_ARRAY);
        $signedToken = $tokenModel->signTokenRow($accessToken);
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer '.$signedToken;
        $session->end();
        \Gdn::request()->setPath($path);
        /** @var \Gdn_Auth $auth */
        $auth = $this->container()->get(\Gdn_Auth::class);
        $auth->startAuthenticator();
        if ($valid) {
            $this->assertEquals($userID, \Gdn::session()->UserID);
        } else {
            $this->assertEquals(0, \Gdn::session()->UserID);
        }
    }

    /**
     * Provide path data.
     *
     * @return array
     */
    public function providePathData(): array {
        return [
            'valid-path' => ['api/v2', true],
            'valid-path-subc' => ['subc/api/v2', true],
            'invalid-path' => ['/invalid', false],
            'invalid-path-subc' => ['/subc1/subc2/api/v2', false],
        ];
    }
}
