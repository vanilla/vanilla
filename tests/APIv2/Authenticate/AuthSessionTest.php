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
class AuthSessionTest extends AbstractAPIv2Test {

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
     * Test GET /authenticate/session/{authSessionID}
     */
    public function testGet() {
        $authSessionID = $this->createAuthSessionID();

        $result = $this->api()->get(
            $this->baseUrl.'/session/'.$authSessionID
        );

        $this->assertEquals(200, $result->getStatusCode());

        $body = $result->getBody();

        $this->assertInternalType('array', $body);
        $this->assertArrayHasKey('authSessionID', $body);
        $this->assertArrayHasKey('attributes', $body);
        $this->assertInternalType('array', $body['attributes']);
        $this->assertArrayHasKey('ssoData', $body['attributes']);
    }

    /**
     * Test DELETE /authenticate/session/{authSessionID}
     */
    public function testDelete() {
        $authSessionID = $this->createAuthSessionID();

        $result = $this->api()->delete(
            $this->baseUrl.'/session/'.$authSessionID
        );

        $this->assertEquals(204, $result->getStatusCode());

        $exception = null;
        try {
            $this->api()->get(
                $this->baseUrl.'/session/'.$authSessionID
            );
        } catch (\Exception $e) {
            $exception = $e;
        }

        if (!$exception) {
            $this->fail('The session still exists.');
        }
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

    /**
     * A user should be able to sign in with their username and password.
     */
    public function testPostPasswordName() {
        $this->assertNoSession();

        $result = $this->api()->post("{$this->baseUrl}/password", [
            'username' => $this->currentUser['name'],
            'password' => $this->currentUser['password']
        ]);

        $this->assertSessionUserID();
    }

    /**
     * A user should be able to sign in with their email and password.
     */
    public function testPostPasswordEmail() {
        $this->assertNoSession();

        $result = $this->api()->post("{$this->baseUrl}/password", [
            'username' => $this->currentUser['email'],
            'password' => $this->currentUser['password']
        ]);

        $this->assertSessionUserID();
    }

    /**
     * An incorrect username should return 404.
     *
     * @expectedException \Exception
     * @expectedExceptionCode 404
     */
    public function testPostPasswordNotFound() {
        $result = $this->api()->post("{$this->baseUrl}/password", [
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
    public function testPostPasswordIncorrect() {
        $result = $this->api()->post("{$this->baseUrl}/password", [
            'username' => $this->currentUser['email'],
            'password' => $this->currentUser['password'].'!!!'
        ]);
    }

    /**
     * Assert that there is not currently a user in the session.
     */
    public function assertNoSession() {
        /* @var \Gdn_Session $session */
        $session = $this->container()->get(\Gdn_Session::class);
        $this->assertEquals(0, $session->UserID);
    }

    /**
     * Assert that a given user has a session.
     *
     * @param int|null $expected The expected user or **null** for the current user.
     */
    public function assertSessionUserID(int $expected = null) {
        if ($expected === null) {
            $expected = $this->currentUser['userID'];
        }

        /* @var \Gdn_Session $session */
        $session = $this->container()->get(\Gdn_Session::class);
        $this->assertEquals($expected, $session->UserID);
    }
}
