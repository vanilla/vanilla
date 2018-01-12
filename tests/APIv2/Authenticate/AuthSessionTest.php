<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2\Authenticate;

use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\TestSSOAuthenticator;

/**
 * Test the /api/v2/authenticate endpoints.
 */
class AuthSessionTest extends AbstractAPIv2Test {

    private $baseUrl = '/authenticate';

    /**
     * @var TestSSOAuthenticator
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
            ->rule(TestSSOAuthenticator::class)
            ->setAliasOf('TestSSOAuthenticator');
    }

    /**
     * {@inheritdoc}
     */
    public function setUp() {
        parent::setUp();

        $this->authenticator = new TestSSOAuthenticator();

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

        $this->authenticator->setUniqueID($uniqueID);
        $this->authenticator->setUserData($userData);

        $this->container()->setInstance('TestSSOAuthenticator', $this->authenticator);

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
            'authenticator' => $this->authenticator->getName(),
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
