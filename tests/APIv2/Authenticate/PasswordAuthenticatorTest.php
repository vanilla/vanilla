<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2\Authenticate;

use VanillaTests\APIv2\AbstractAPIv2Test;

class PasswordAuthenticatorTest extends AbstractAPIv2Test {

    private $currentUser;

    private $baseUrl = '/authenticate';

    /**
     * Assert that a given user has a session.
     *
     * @param int|null $expected The expected user or **null** for the current user.
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function assertSessionUserID(int $expected = null) {
        if ($expected === null) {
            $expected = $this->currentUser['userID'];
        }

        /* @var \Gdn_Session $session */
        $session = $this->container()->get(\Gdn_Session::class);
        $this->assertEquals($expected, $session->UserID);
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        parent::setUp();

        $uniqueID = self::randomUsername('pa');
        $userData = [
            'name' => $uniqueID,
            'email' => $uniqueID.'@example.com',
            'password' => 'pwd_'.$uniqueID,
        ];

        /** @var \UsersApiController $usersAPIController */
        $usersAPIController = $this->container()->get('UsersAPIController');
        $userFragment = $usersAPIController->post($userData)->getData();
        $this->currentUser = array_merge($userFragment, $userData);

        /** @var \Gdn_Session $session */
        $session = $this->container()->get(\Gdn_Session::class);
        $session->end();
    }

    /**
     * @inheritdoc
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get(\Gdn_Configuration::class);
        $config->set('Feature.'.\AuthenticateApiController::FEATURE_FLAG.'.Enabled', true, true, false);
    }

    /**
     * A user should be able to sign in through /authenticate/password
     */
    public function testEndpointPasswordShortcut() {
        $this->assertNoSession();

        $this->api()->post("{$this->baseUrl}/password", [
            'username' => $this->currentUser['email'],
            'password' => $this->currentUser['password']
        ]);

        $this->assertSessionUserID();
    }

    /**
     * /authenticate/password should work even if the PasswordAuthenticator is inactive.
     */
    public function testEndpointPasswordShortcutAlwaysOn() {
        $this->assertNoSession();

        /** @var \Gdn_Configuration $config */
        $config = $this->container()->get(\Gdn_Configuration::class);
        $config->set('Garden.SignIn.DisablePassword', true, true, false);
        try {
            $this->api()->post("{$this->baseUrl}/password", [
                'username' => $this->currentUser['email'],
                'password' => $this->currentUser['password']
            ]);
        } finally {
            $config->set('Garden.SignIn.DisablePassword', false, true, false);
        }

        $this->assertSessionUserID();
    }

    /**
     * An incorrect username should return 404.
     *
     * @expectedException \Exception
     * @expectedExceptionCode 404
     */
    public function testInvalidEmail() {
        $this->api()->post("{$this->baseUrl}", [
            'authenticate' => [
                'authenticatorType' => 'password',
                'authenticatorID' => 'password',
            ],
            'username' => $this->currentUser['email'].'!!!!',
            'password' => $this->currentUser['password']
        ]);
    }

    /**
     * An incorrect password should return 401.
     *
     * @expectedException \Exception
     * @expectedExceptionCode 401
     */
    public function testInvalidPassword() {
        $this->api()->post("{$this->baseUrl}", [
            'authenticate' => [
                'authenticatorType' => 'password',
                'authenticatorID' => 'password',
            ],
            'username' => $this->currentUser['email'],
            'password' => $this->currentUser['password'].'!!!'
        ]);
    }

    /**
     * /authenticate with password/password should not work if the PasswordAuthenticator is inactive.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Cannot authenticate with an inactive authenticator.
     */
    public function testPasswordAuthenticatorInactive() {
        $this->assertNoSession();

        /** @var \Gdn_Configuration $config */
        $config = $this->container()->get(\Gdn_Configuration::class);
        $config->set('Garden.SignIn.DisablePassword', true, true, false);
        try {
            $this->api()->post("{$this->baseUrl}", [
                'authenticate' => [
                    'authenticatorType' => 'password',
                    'authenticatorID' => 'password',
                ],
                'username' => $this->currentUser['email'],
                'password' => $this->currentUser['password']
            ]);
        } finally {
            $config->set('Garden.SignIn.DisablePassword', false, true, false);
        }
    }

    /**
     * A banned user should not be able to log in.
     *
     * @expectedException \Exception
     * @expectedExceptionCode 401
     */
    public function testUserBanned() {
        /** @var \UserModel $userModel */
        $userModel = $this->container()->get(\UserModel::class);
        $userModel->ban($this->currentUser['userID'], ['AddActivity' => false]);

        $this->api()->post("{$this->baseUrl}", [
            'authenticate' => [
                'authenticatorType' => 'password',
                'authenticatorID' => 'password',
            ],
            'username' => $this->currentUser['email'],
            'password' => $this->currentUser['password'],
        ]);
    }

    /**
     * A user should be able to sign in with their email and password.
     */
    public function testValidEmailPassword() {
        $this->assertNoSession();

        $this->api()->post("{$this->baseUrl}", [
            'authenticate' => [
                'authenticatorType' => 'password',
                'authenticatorID' => 'password',
            ],
            'username' => $this->currentUser['email'],
            'password' => $this->currentUser['password']
        ]);

        $this->assertSessionUserID();
    }

    /**
     * A user should be able to sign in with their username and password.
     */
    public function testValidNamePassword() {
        $this->assertNoSession();

        $this->api()->post("{$this->baseUrl}", [
            'authenticate' => [
                'authenticatorType' => 'password',
                'authenticatorID' => 'password',
            ],
            'username' => $this->currentUser['name'],
            'password' => $this->currentUser['password']
        ]);

        $this->assertSessionUserID();
    }
}
