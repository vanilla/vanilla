<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2\Authenticate;

use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\TestSSOAuthenticator;

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

        self::$config = self::container()->get('Config');
    }

    /**
     * {@inheritdoc}
     */
    public function setUp() {
        $this->startSessionOnSetup(false);
        parent::setUp();


        $uniqueID = uniqid('ne_');
        $this->currentUser = [
            'name' => 'Authenticate_'.$uniqueID,
        ];

        $this->authenticator = new TestSSOAuthenticator();

        $this->authenticator->setUniqueID($uniqueID);
        $this->authenticator->setUserData($this->currentUser);

        $this->container()->setInstance('TestSSOAuthenticator', $this->authenticator);

        $this->container()->get('Config')->set('Garden.Registration.NoEmail', true);
    }

    /**
     * Test POST /authenticate with a user that doesn't have an email.
     */
    public function testAuthenticate() {
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
        $this->assertEquals('authenticated', $body['authenticationStep']);

        // The user should have been created and linked
        $result = $this->api()->get(
            $this->baseUrl.'/'.$this->authenticator->getName().'/'.$this->authenticator->getID()
        );

        $this->assertEquals(200, $result->getStatusCode());

        $body = $result->getBody();

        $this->assertInternalType('array', $body);
        $this->assertArrayHasKey('linked', $body);
        $this->assertEquals(true, $body['linked']);

    }
}
