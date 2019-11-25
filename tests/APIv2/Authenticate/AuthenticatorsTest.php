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
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        self::container()->rule(MockSSOAuthenticator::class);
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get(\Gdn_Configuration::class);
        $config->set('Feature.'.\AuthenticateApiController::FEATURE_FLAG.'.Enabled', true, true, false);
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        parent::setUp();

        /** @var \Vanilla\Models\AuthenticatorModel $authenticatorModel */
        $authenticatorModel = $this->container()->get(AuthenticatorModel::class);

        $uniqueID = uniqid('inactv_auth_');
        $authType = MockSSOAuthenticator::getType();
        $this->authenticator = $authenticatorModel->createSSOAuthenticatorInstance([
            'authenticatorID' => $authType,
            'type' => $authType,
            'SSOData' => json_decode(json_encode(new SSOData($authType, $authType, $uniqueID)), true),
        ]);

        $session = $this->container()->get(\Gdn_Session::class);
        $session->end();
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
