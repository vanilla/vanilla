<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2\Authenticate;

use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\MockSSOAuthenticator;

/**
 * Test the /api/v2/authenticate endpoints.
 */
class LinkUserTest extends AbstractAPIv2Test {

    private $baseUrl = '/authenticate';

    /**
     * @var MockSSOAuthenticator
     */
    private $authenticator;

    /**
     * @var array
     */
    private $currentUser;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass() {
        parent::setupBeforeClass();
        self::container()
            ->rule(MockSSOAuthenticator::class)
            ->setAliasOf('MockSSOAuthenticator');
    }

    /**
     * {@inheritdoc}
     */
    public function setUp() {
        parent::setUp();

        $uniqueID = uniqid('lu_');
        $userData = [
            'name' => 'Authenticate_'.$uniqueID,
            'email' => 'authenticate_'.$uniqueID.'@example.com',
            'password' => 'pwd_'.$uniqueID,
        ];

        /** @var \UsersApiController $usersAPIController */
        $usersAPIController = $this->container()->get('UsersAPIController');
        $userFragment = $usersAPIController->post($userData)->getData();
        $this->currentUser = array_merge($userFragment, $userData);

        $this->authenticator = new MockSSOAuthenticator($uniqueID, $userData);

        $this->container()->setInstance('MockSSOAuthenticator', $this->authenticator);

        $session = $this->container()->get(\Gdn_Session::class);
        $session->end();
    }

    /**
     * Test POST /authenticate/link-user by sending userid + password.
     */
    public function testLinkUserWithUserID() {
        $authSessionID = $this->createAuthSessionID();

        $postData = [
            'authSessionID' => $authSessionID,
            'userID' => $this->currentUser['userID'],
            'password' => $this->currentUser['password'],
        ];

        $result = $this->api()->post(
            $this->baseUrl.'/link-user',
            $postData
        );

        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();

        $this->assertInternalType('array', $body);
        $this->assertArrayHasKey('userID', $body);
        $this->assertEquals($this->currentUser['userID'], $body['userID']);
    }

    /**
     * Test POST /authenticate/link-user by sending name + email + password.
     */
    public function testLinkUserWithNameEmail() {
        $authSessionID = $this->createAuthSessionID();

        $postData = [
            'authSessionID' => $authSessionID,
            'name' => $this->currentUser['name'],
            'email' => $this->currentUser['email'],
            'password' => $this->currentUser['password'],
        ];

        $result = $this->api()->post(
            $this->baseUrl.'/link-user',
            $postData
        );

        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();

        $this->assertInternalType('array', $body);
        $this->assertArrayHasKey('userID', $body);
        $this->assertEquals($this->currentUser['userID'], $body['userID']);
    }

    /**
     * Test POST /authenticate/link-user with a wrong password.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage The password verification failed.
     */
    public function testLinkUserWrongPassword() {
        $authSessionID = $this->createAuthSessionID();

        $postData = [
            'authSessionID' => $authSessionID,
            'userID' => $this->currentUser['userID'],
            'password' => uniqid(),
        ];

        $result = $this->api()->post(
            $this->baseUrl.'/link-user',
            $postData
        );

        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();

        $this->assertInternalType('array', $body);
        $this->assertArrayHasKey('userID', $body);
        $this->assertEquals($this->currentUser['userID'], $body['userID']);
    }

    /**
     * Create an authSessionID by posting to /authenticate
     *
     * @return mixed
     */
    protected function createAuthSessionID() {
        $postData = [
            'authenticatorType' => $this->authenticator::getType(),
            'authenticatorID' => $this->authenticator->getID(),
        ];

        $result = $this->api()->post(
            $this->baseUrl,
            $postData
        );

        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();

        $this->assertInternalType('array', $body);
        $this->assertArrayHasKey('authenticationStep', $body);

        $this->assertEquals('linkUser', $body['authenticationStep']);
        $this->assertArrayHasKey('authSessionID', $body);

        return $body['authSessionID'];
    }
}
