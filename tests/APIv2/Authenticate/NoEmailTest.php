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
class NoEmailTest extends AbstractAPIv2Test {

    /**
     * @var \Gdn_Configuration
     */
    private static $config;

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

        self::$config = self::container()->get('Config');
    }

    /**
     * {@inheritdoc}
     */
    public function setUp() {
        $this->startSessionOnSetup(false);
        parent::setUp();


        $uniqueID = self::randomUsername('ne');
        $this->currentUser = [
            'name' => $uniqueID,
        ];

        $this->authenticator = new MockSSOAuthenticator($uniqueID, $this->currentUser);

        $this->container()->setInstance('MockSSOAuthenticator', $this->authenticator);

        $this->container()->get('Config')->set('Garden.Registration.NoEmail', true);
    }

    /**
     * Test POST /authenticate with a user that doesn't have an email.
     */
    public function testAuthenticate() {
        $postData = [
            'authenticate' => [
                'authenticatorType' => $this->authenticator::getType(),
                'authenticatorID' => $this->authenticator->getID(),
            ],
        ];

        $result = $this->api()->post(
            $this->baseUrl,
            $postData
        );

        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();

        $this->assertInternalType('array', $body);
        $this->assertArrayHasKey('authenticationStep', $body);
        $this->assertEquals('authenticated', $body['authenticationStep']);

        // The user should have been created and linked
        $result = $this->api()->get(
            $this->baseUrl.'/authenticators/'.$this->authenticator->getID()
        );

        $this->assertEquals(200, $result->getStatusCode());

        $body = $result->getBody();

        $this->assertInternalType('array', $body);
        $this->assertArrayHasKey('isUserLinked', $body);
        $this->assertEquals(true, $body['isUserLinked']);

    }
}
