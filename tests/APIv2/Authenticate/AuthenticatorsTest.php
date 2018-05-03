<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\APIv2\Authenticate;

use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\MockSSOAuthenticator;

/**
 * Class AuthenticatorsTest
 */
class AuthenticatorsTest extends AbstractAPIv2Test {

    /** @var MockSSOAuthenticator */
    private $authenticator;

    /** @var string */
    private $baseUrl = '/authenticate/authenticators';

    /**
     * @inheritdoc
     */
    public static function setupBeforeClass() {
        parent::setupBeforeClass();
        self::container()
            ->rule(MockSSOAuthenticator::class)
            ->setAliasOf('MockSSOAuthenticator');
    }

    /**
     * @inheritdoc
     */
    public function setUp() {
        parent::setUp();

        $uniqueID = uniqid('inactv_auth_');
        $this->authenticator = new MockSSOAuthenticator($uniqueID);

        $this->container()->setInstance('MockSSOAuthenticator', $this->authenticator);

        $session = $this->container()->get(\Gdn_Session::class);
        $session->end();
    }

    /**
     * @param array $record
     */
    public function assertIsAuthenticator(array $record) {
        $this->assertInternalType('array', $record);

        $this->assertArrayHasKey('authenticatorID', $record);
        $this->assertArrayHasKey('type', $record);

        $this->assertArrayHasKey('ui', $record);
        $this->assertInternalType('array', $record['ui']);
        $this->assertArrayHasKey('url', $record['ui']);
        $this->assertArrayHasKey('buttonName', $record['ui']);
        $this->assertArrayHasKey('backgroundColor', $record['ui']);
        $this->assertArrayHasKey('foregroundColor', $record['ui']);;

        // They also have to enable SignIn.
        if (isset($record['sso'])) {
            $this->assertArrayHasKey('canSignIn', $record['sso']);
            $this->assertInternalType('bool', $record['sso']['canSignIn']);
            // Must be true or something is wrong.
            $this->assertTrue($record['sso']['canSignIn']);

            $this->assertArrayHasKey('canAutoLinkUser', $record['sso']);
            $this->assertInternalType('bool', $record['sso']['canAutoLinkUser']);
        }
    }

    /**
     * Test GET /authenticators
     */
    public function testListAuthenticators() {
        $response = $this->api()->get($this->baseUrl);

        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();

        $this->assertInternalType('array', $body);
        $this->assertTrue(count($body) > 1);

        foreach ($body as $record) {
            $this->assertIsAuthenticator($record);
        }
    }

    /**
     * Test GET /authenticators/:id
     */
    public function testGetAuthenticators() {

        $response = $this->api()->get($this->baseUrl.'/'.$this->authenticator->getID());

        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertIsAuthenticator($body);
    }
}
