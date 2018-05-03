<?php
///**
// * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
// * @copyright 2009-2018 Vanilla Forums Inc.
// * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
// */
//
//namespace VanillaTests\APIv2\Authenticate;
//
//use VanillaTests\APIv2\AbstractAPIv2Test;
//use VanillaTests\Fixtures\MockSSOAuthenticator;
//
///**
// * Test the /api/v2/authenticate endpoints.
// */
//class LinkUserTest extends AbstractAPIv2Test {
//
//    private $baseUrl = '/authenticate';
//
//    /**
//     * @var MockSSOAuthenticator
//     */
//    private $authenticator;
//
//    /**
//     * @var array
//     */
//    private $currentUser;
//
//    /**
//     * {@inheritdoc}
//     */
//    public static function setupBeforeClass() {
//        parent::setupBeforeClass();
//        self::container()
//            ->rule(MockSSOAuthenticator::class)
//            ->setAliasOf('MockSSOAuthenticator');
//    }
//
//    /**
//     * {@inheritdoc}
//     */
//    public function setUp() {
//        parent::setUp();
//
//        $uniqueID = self::randomUsername('lu');
//        $userData = [
//            'name' => $uniqueID,
//            'email' => $uniqueID.'@example.com',
//            'password' => 'pwd_'.$uniqueID,
//        ];
//
//        /** @var \UsersApiController $usersAPIController */
//        $usersAPIController = $this->container()->get('UsersAPIController');
//        $userFragment = $usersAPIController->post($userData)->getData();
//        $this->currentUser = array_merge($userFragment, $userData);
//
//        $this->authenticator = new MockSSOAuthenticator($uniqueID, $userData);
//
//        $this->container()->setInstance('MockSSOAuthenticator', $this->authenticator);
//
//        $session = $this->container()->get(\Gdn_Session::class);
//        $session->end();
//
//        $this->assertNoSession();
//    }
//
//    public function tearDown() {
//        parent::tearDown();
//    }
//
//    /**
//     * Test POST /authenticate/link-user by sending userid + password.
//     *
//     * @return int userID
//     */
//    public function testLinkUserWithUserID() {
//        $authSessionID = $this->createAuthSessionID();
//
//        $postData = [
//            'authSessionID' => $authSessionID,
//            'userID' => $this->currentUser['userID'],
//            'password' => $this->currentUser['password'],
//        ];
//
//        $result = $this->api()->post(
//            $this->baseUrl.'/link-user',
//            $postData
//        );
//
//        $this->assertEquals(201, $result->getStatusCode());
//
//        $body = $result->getBody();
//
//        $this->assertInternalType('array', $body);
//        $this->assertArrayHasKey('userID', $body);
//        $this->assertEquals($this->currentUser['userID'], $body['userID']);
//
//        return $body['userID'];
//    }
//
//    /**
//     * Test POST /authenticate/link-user by sending name + email + password.
//     */
//    public function testLinkUserWithNameEmail() {
//        $authSessionID = $this->createAuthSessionID();
//
//        $postData = [
//            'authSessionID' => $authSessionID,
//            'name' => $this->currentUser['name'],
//            'email' => $this->currentUser['email'],
//            'password' => $this->currentUser['password'],
//        ];
//
//        $result = $this->api()->post(
//            $this->baseUrl.'/link-user',
//            $postData
//        );
//
//        $this->assertEquals(201, $result->getStatusCode());
//
//        $body = $result->getBody();
//
//        $this->assertInternalType('array', $body);
//        $this->assertArrayHasKey('userID', $body);
//        $this->assertEquals($this->currentUser['userID'], $body['userID']);
//    }
//
//    /**
//     * Test POST /authenticate/link-user with a wrong password.
//     *
//     * @expectedException \Exception
//     * @expectedExceptionMessage The password verification failed.
//     */
//    public function testLinkUserWrongPassword() {
//        $authSessionID = $this->createAuthSessionID();
//
//        $postData = [
//            'authSessionID' => $authSessionID,
//            'userID' => $this->currentUser['userID'],
//            'password' => uniqid(),
//        ];
//
//        $result = $this->api()->post(
//            $this->baseUrl.'/link-user',
//            $postData
//        );
//
//        $this->assertEquals(201, $result->getStatusCode());
//
//        $body = $result->getBody();
//
//        $this->assertInternalType('array', $body);
//        $this->assertArrayHasKey('userID', $body);
//        $this->assertEquals($this->currentUser['userID'], $body['userID']);
//    }
//
//    /**
//     * Test DELETE /authenticate/authenticators/:id
//     */
//    public function testUnlinkUser() {
//        $userID = $this->testLinkUserWithUserID();
//
//        // Authenticate
//        $this->api()->post($this->baseUrl, [
//            'authenticate' => [
//                'authenticatorType' => $this->authenticator::getType(),
//                'authenticatorID' => $this->authenticator->getID(),
//            ],
//        ]);
//
//        $this->assertSessionUserID($userID);
//
//        // Check if the user is linked.
//        $result = $this->api()->get($this->baseUrl.'/authenticators/'.$this->authenticator->getID());
//        $this->assertEquals(200, $result->getStatusCode());
//        $authenticatorData = $result->getBody();
//        $this->assertTrue($authenticatorData['isUserLinked']);
//
//        // Unlink
//        $this->api()->delete($this->baseUrl.'/authenticators/'.$this->authenticator->getID());
//
//        // Check if the user is linked.
//        $result = $this->api()->get($this->baseUrl.'/authenticators/'.$this->authenticator->getID());
//        $this->assertEquals(200, $result->getStatusCode());
//        $authenticatorData = $result->getBody();
//        $this->assertFalse($authenticatorData['isUserLinked']);
//
//    }
//
//    /**
//     * Create an authSessionID by posting to /authenticate
//     *
//     * @return mixed
//     */
//    protected function createAuthSessionID() {
//        $postData = [
//            'authenticate' => [
//                'authenticatorType' => $this->authenticator::getType(),
//                'authenticatorID' => $this->authenticator->getID(),
//            ],
//        ];
//
//        $result = $this->api()->post(
//            $this->baseUrl,
//            $postData
//        );
//
//        $this->assertEquals(201, $result->getStatusCode());
//
//        $body = $result->getBody();
//
//        $this->assertInternalType('array', $body);
//        $this->assertArrayHasKey('authenticationStep', $body);
//
//        $this->assertEquals('linkUser', $body['authenticationStep']);
//        $this->assertArrayHasKey('authSessionID', $body);
//
//        return $body['authSessionID'];
//    }
//
//    /**
//     * Assert that there is not currently a user in the session.
//     */
//    public function assertNoSession() {
//        /* @var \Gdn_Session $session */
//        $session = $this->container()->get(\Gdn_Session::class);
//        $this->assertEquals(0, $session->UserID);
//    }
//
//    /**
//     * Assert that a given user has a session.
//     *
//     * @param int|null $expected The expected user or **null** for the current user.
//     */
//    public function assertSessionUserID(int $expected = null) {
//        if ($expected === null) {
//            $expected = $this->currentUser['userID'];
//        }
//
//        /* @var \Gdn_Session $session */
//        $session = $this->container()->get(\Gdn_Session::class);
//        $this->assertEquals($expected, $session->UserID);
//    }
//}
