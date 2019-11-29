<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2\Authenticate;

use Vanilla\Models\AuthenticatorModel;
use Vanilla\Models\SSOData;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\Authenticator\MockSSOAuthenticator;

/**
 * Test the /api/v2/authenticate endpoints.
 */
class LinkUserTest extends AbstractAPIv2Test {

    private $baseUrl = '/authenticate/link-user';

    /**
     * @var MockSSOAuthenticator
     */
    private $authenticator;

    /** @var array */
    private $currentUser;

    /** @var string */
    private $userPassword = 'trustno1';

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        self::container()->rule(MockSSOAuthenticator::class);
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get(\Gdn_Configuration::class);
        $config->set('Feature.'.\AuthenticateApiController::FEATURE_FLAG.'.Enabled', true, true, false);
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
        parent::setUp();

        $uniqueID = self::randomUsername('lu');
        $userData = [
            'name' => $uniqueID,
            'email' => $uniqueID.'@example.com',
            'password' => $this->userPassword,
        ];

        /** @var \UsersApiController $usersAPIController */
        $usersAPIController = $this->container()->get('UsersAPIController');
        $userFragment = $usersAPIController->post($userData)->getData();
        $this->currentUser = array_merge($userFragment, $userData);

        /** @var \Vanilla\Models\AuthenticatorModel $authenticatorModel */
        $authenticatorModel = $this->container()->get(AuthenticatorModel::class);

        $authType = MockSSOAuthenticator::getType();
        $this->authenticator = $authenticatorModel->createSSOAuthenticatorInstance([
            'authenticatorID' => $authType,
            'type' => $authType,
            'SSOData' => json_decode(json_encode(new SSOData($authType, $authType, $uniqueID, $userData)), true),
        ]);
        $this->authenticator->setTrusted(false);

        $session = $this->container()->get(\Gdn_Session::class);
        $session->end();

        $this->assertNoSession();
    }

    /**
     * @inheritdoc
     */
    public function tearDown(): void {
        /** @var \Vanilla\Models\AuthenticatorModel $authenticatorModel */
        $authenticatorModel = $this->container()->get(AuthenticatorModel::class);

        $authenticatorModel->deleteSSOAuthenticatorInstance($this->authenticator);
    }

    /**
     * Test GET /authenticate/link-user/:authSessionID
     */
    public function testGetLinkUser() {
        $authSessionID = $this->createAuthSessionID();

        $result = $this->api()->get($this->baseUrl.'/'.$authSessionID);

        $this->assertEquals(200, $result->getStatusCode());

        $body = $result->getBody();

        $this->assertIsArray($body);
        $this->assertArrayHasKey('ssoUser', $body);
        $this->assertArrayHasKey('authenticator', $body);
        $this->assertArrayHasKey('config', $body);

        return $authSessionID;
    }

    /**
     * Test POST /authenticate/link-user ith method = password, using userID.
     */
    public function testPostLinkUserMethodPasswordWUserID() {
        $authSessionID = $this->createAuthSessionID();

        $result = $this->api()->post($this->baseUrl, [
            'authSessionID' => $authSessionID,
            'method' => 'password',
            'userID' => $this->currentUser['userID'],
            'password' => $this->userPassword,
        ]);

        $this->assertLinkSuccess($result);
    }

    /**
     * Test POST /authenticate/link-user ith method = password, using name.
     */
    public function testPostLinkUserMethodPasswordWName() {
        $authSessionID = $this->createAuthSessionID();

        $result = $this->api()->post($this->baseUrl, [
            'authSessionID' => $authSessionID,
            'method' => 'password',
            'username' => $this->currentUser['name'],
            'password' => $this->userPassword,
        ]);

        $this->assertLinkSuccess($result);
    }

    /**
     * Test POST /authenticate/link-user with method = password, using email.
     */
    public function testPostLinkUserMethodPasswordWEmail() {
        $authSessionID = $this->createAuthSessionID();

        $result = $this->api()->post($this->baseUrl, [
            'authSessionID' => $authSessionID,
            'method' => 'password',
            'username' => $this->currentUser['email'],
            'password' => $this->userPassword,
        ]);

        $this->assertLinkSuccess($result);
    }

    /**
     * Test POST /authenticate/link-user with method = register.
     */
    public function testPostLinkUserMethodRegister() {
        $authSessionID = $this->createAuthSessionID();

        $result = $this->api()->post($this->baseUrl, [
            'authSessionID' => $authSessionID,
            'method' => 'register',
            'name' => 'NewUser',
            'email' => 'NewUser@example.com',
            'agreeToTerms' => true,
        ]);
    }

    /**
     * Test POST /authenticate/link-user with method = session.
     */
    public function testPostLinkUserMethodSession() {
        $authSessionID = $this->createAuthSessionID();

        /* @var \Gdn_Session $session */
        $session = $this->container()->get(\Gdn_Session::class);
        $session->start($this->currentUser['userID']);

        $this->assertSessionUserID($this->currentUser['userID']);

        $result = $this->api()->post($this->baseUrl, [
            'authSessionID' => $authSessionID,
            'method' => 'session',
        ]);

        $this->assertLinkSuccess($result);
    }

    /**
     * Test POST /authenticate/link-user with method = register and agreeToTerm = false.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage You must agree to the terms of service.
     */
    public function testPostLinkUserMethodRegisterWAgreeToTermFalse() {
        $authSessionID = $this->createAuthSessionID();

        $result = $this->api()->post($this->baseUrl, [
            'authSessionID' => $authSessionID,
            'method' => 'register',
            'name' => 'NewUser',
            'email' => 'NewUser@example.com',
            'agreeToTerms' => false,
        ]);
    }

    /**
     * Test DELETE /authenticate/link-user/:authSessionID
     *
     * @depends testGetLinkUser
     * @expectedException \Exception
     * @expectedExceptionMessage AuthenticationSession not found.
     */
    public function testDeleteLinkUser() {
        $authSessionID = $this->createAuthSessionID();

        $result = $this->api()->delete($this->baseUrl.'/'.$authSessionID);
        $this->assertEquals(204, $result->getStatusCode());

        $this->api()->get($this->baseUrl.'/'.$authSessionID);
    }

    /**
     * Create an authSessionID by posting to /authenticate
     *
     * @return string
     */
    private function createAuthSessionID() {
        $postData = [
            'authenticate' => [
                'authenticatorType' => $this->authenticator::getType(),
                'authenticatorID' => $this->authenticator->getID(),
            ],
        ];

        $result = $this->api()->post(
            '/authenticate',
            $postData
        );

        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();

        $this->assertInternalType('array', $body);
        $this->assertArrayHasKey('authenticationStep', $body);

        $this->assertEquals('linkUser', $body['authenticationStep']);
        $this->assertArrayHasKey('authSessionID', $body);

        $this->assertNoSession();

        return $body['authSessionID'];
    }

    /**
     * Assert that linking was successful.
     *
     * @param $result
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    private function assertLinkSuccess($result) {
        $this->assertEquals(201, $result->getStatusCode());
        $body = $result->getBody();

        $this->assertIsArray($body);
        $this->assertEquals(1, count($body));
        $this->assertArrayHasKey('user', $body);
        $this->assertEquals($this->currentUser['userID'], $body['user']['userID']);

        $this->assertSessionUserID($this->currentUser['userID']);
    }

    /**
     * Assert that a given user has a session.
     *
     * @param int|null $expected The expected user or **null** for the current user.
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    private function assertSessionUserID(int $expected = null) {
        if ($expected === null) {
            $expected = $this->currentUser['userID'];
        }

        /* @var \Gdn_Session $session */
        $session = $this->container()->get(\Gdn_Session::class);
        $this->assertEquals($expected, $session->UserID);
    }
}
