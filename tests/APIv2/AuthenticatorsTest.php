<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;


use Vanilla\Authenticator\Authenticator;
use Vanilla\Models\AuthenticatorModel;

class AuthenticatorsTest extends AbstractAPIv2Test {

    /** @var Authenticator[] */
    private static $authenticators;

    /** @var string */
    protected $baseUrl = '/authenticators';

    /**
     * @inheritdoc
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();

        /** @var AuthenticatorModel $authenticatorModel */
        $authenticatorModel = self::container()->get(AuthenticatorModel::class);
        self::$authenticators = $authenticatorModel->getAuthenticators();

        /** @var \Gdn_Configuration $config */
        $config = static::container()->get(\Gdn_Configuration::class);
        $config->set('Feature.'.\AuthenticateApiController::FEATURE_FLAG.'.Enabled', true, true, false);
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        parent::setUp();
        if (!self::$authenticators) {
            $this->markTestSkipped('No Authenticator found.');
        }
    }

    /**
     * @param array $record
     */
    public function assertIsAuthenticator(array $record) {
        $this->assertIsArray($record);

        $this->assertArrayHasKey('authenticatorID', $record);
        $this->assertArrayHasKey('type', $record);
        $this->assertArrayHasKey('resourceUrl', $record);

        $this->assertArrayHasKey('ui', $record);
        $this->assertIsArray($record['ui']);
        $this->assertArrayHasKey('url', $record['ui']);
        $this->assertArrayHasKey('buttonName', $record['ui']);
        $this->assertArrayHasKey('backgroundColor', $record['ui']);
        $this->assertArrayHasKey('foregroundColor', $record['ui']);

        $this->assertArrayHasKey('isActive', $record);
        $this->assertIsBool($record['isActive']);

        // They also have to enable SignIn.
        if (isset($record['sso'])) {
            $this->assertArrayHasKey('canSignIn', $record['sso']);
            $this->assertIsBool($record['sso']['canSignIn']);
            $this->assertTrue($record['sso']['canSignIn']);
        }
    }

    /**
     * Test GET /authenticators/:type/:id
     */
    public function testGetAuthenticators() {
        $type = self::$authenticators[0]::getType();
        $id = self::$authenticators[0]->getID();

        $response = $this->api()->get($this->baseUrl.'/'.$type.'/'.$id);

        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertIsAuthenticator($body);
    }

    /**
     * Test GET /authenticators/ucfirst(:type)/ucfirst(:id)
     */
    public function testGetAuthenticatorsUCFirst() {
        $type = ucfirst(self::$authenticators[0]::getType());
        $id = ucfirst(self::$authenticators[0]->getID());

        $response = $this->api()->get($this->baseUrl.'/'.$type.'/'.$id);

        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertIsAuthenticator($body);
    }

    /**
     * Test GET /authenticators/strtolower(:type)/strtolower(:id)
     *
     * This should be what is returned by the api in the URL fields.
     */
    public function testGetAuthenticatorsLowerCase() {
        $type = strtolower(self::$authenticators[0]::getType());
        $id = strtolower(self::$authenticators[0]->getID());

        $response = $this->api()->get($this->baseUrl.'/'.$type.'/'.$id);

        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertIsAuthenticator($body);
    }

    /**
     * Test GET /authenticators
     */
    public function testListAuthenticators() {
        $response = $this->api()->get($this->baseUrl);

        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();

        $this->assertIsArray($body);
        $this->assertCount(count(self::$authenticators), $body);

        foreach ($body as $record) {
            $this->assertIsAuthenticator($record);
        }
    }

    /**
     * Test PATCH /authenticators/:id
     */
    public function testPatchAuthenticator() {
        $id = self::$authenticators[0]->getID();

        $response = $this->api()->patch($this->baseUrl.'/'.$id, [
            'isActive' => !self::$authenticators[0]->isActive(),
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertIsAuthenticator($body);
    }
}
