<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\HttpException;
use PHPUnit\Framework\AssertionFailedError;
use VanillaTests\TestOAuth\TestOAuthPlugin;

/**
 * Tests for `POST /api/v2/tokens/oauth`.
 */
final class OAuth2TokenTest extends AbstractAPIv2Test {
    protected const CLIENT_ID = 'test123';

    /**
     * The addons to run tests with.
     *
     * @return array
     */
    protected static function getAddons(): array {
        return ['vanilla', 'test-oauth'];
    }

    /**
     * Setup tests.
     */
    public function setUp(): void {
        parent::setUp();
        $this->configureProvider([
            'AssociationKey' => self::CLIENT_ID,
            'AssociationSecret' => 'shh...',
            'Active' => 1,
            'AllowAccessTokens' => true,
        ]);

        // Clean out the provider.
        $oauth = $this->container()->get(TestOAuthPlugin::class);
        \Closure::bind(function () {
            $this->accessToken = null;
            $this->provider = false;
        }, $oauth, TestOAuthPlugin::class)();

        // Clean up the test user.
        /* @var TestOAuthPlugin $plugin */
        $plugin = $this->container()->get(TestOAuthPlugin::class);
        $plugin->cleanUp();

        $this->api()->setUserID(0);
    }

    /**
     * An unconfigured client should not be allowed.
     */
    public function testNotConfigured() {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(500);

        $this->configureProvider(['AssociationSecret' => '']);

        try {
            $r = $this->postAccessToken();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * An inactive client should not be allowed.
     */
    public function testNotActive() {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(500);

        $this->configureProvider(['Active' => 0]);

        $r = $this->postAccessToken();
    }

    /**
     * An inactive client should not be allowed.
     */
    public function testNotAllowed() {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('The OAuth client is not allowed to issue access tokens.');

        $this->configureProvider(['AllowAccessTokens' => false]);

        $r = $this->postAccessToken();
    }

    /**
     * A client ID mismatch should be an error.
     */
    public function testBadClientID() {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('An OAuth client with ID "test123" could not be found.');

        $this->configureProvider(['AssociationKey' => 'different']);
        $r = $this->postAccessToken();
    }

    /**
     * A bad access token should be forbidden.
     */
    public function testBadToken() {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(403);

        $r = $this->postAccessToken('foo');
    }

    /**
     * A good access token should work.
     */
    public function testGoodToken() {
        $r = $this->postAccessToken();

        $this->assertNotEmpty($r['accessToken']);
    }

    /**
     * A profile that doesn't return enough user information should be an error.
     */
    public function testBadProfileNoUniqueID() {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(400);

        try {
            $r = $this->postAccessToken(TestOAuthPlugin::NO_ID_ACCESS_TOKEN);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * A profile that doesn't return enough user information should be an error.
     */
    public function testBadProfileNoUser() {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(400);

        $r = $this->postAccessToken(TestOAuthPlugin::NO_USER_ACCESS_TOKEN);
    }

    /**
     * Make a request to the token endpoint with default valid values.
     *
     * @param string $oauthAccessToken
     * @return array
     */
    private function postAccessToken(string $oauthAccessToken = TestOAuthPlugin::GOOD_ACCESS_TOKEN): array {
        $r = $this->api()->post('/tokens/oauth', [
            'clientID' => self::CLIENT_ID,
            'oauthAccessToken' => $oauthAccessToken,
        ]);

        return $r->getBody();
    }

    /**
     * Configure the provider for different test scenarios.
     *
     * @param array $set The values to set.
     */
    private function configureProvider(array $set): void {
        /* @var \Gdn_AuthenticationProviderModel $model */
        $model = static::container()->get(\Gdn_AuthenticationProviderModel::class);
        $provider = \Gdn_AuthenticationProviderModel::getProviderByKey(TestOAuthPlugin::PROVIDER_KEY);

        $provider = array_replace($provider, $set);
        $r = $model->save($provider);
        if (!$r) {
            throw new AssertionFailedError("Could not save the provider!!!");
        }
    }
}
