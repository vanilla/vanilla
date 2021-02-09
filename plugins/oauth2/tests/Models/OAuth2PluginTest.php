<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\OAuth2\Tests\Models;

use Garden\Web\Exception\ResponseException;
use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\Request;
use VanillaTests\SiteTestCase;

/**
 * Tests for the OAuth 2 plugin.
 */
class OAuth2PluginTest extends SiteTestCase {
    protected const CLIENT_ID1 = 'p1';
    protected const CLIENT_ID2 = 'p2';
    protected const CLIENT_ID_SINGLE = 'single';

    /**
     * @var \OAuth2Plugin
     */
    private $oauth2Plugin;

    /**
     * @var \Gdn_AuthenticationProviderModel
     */
    private $providerModel;

    /**
     * @var array
     */
    private $providers = [];

    /**
     * @var string
     */
    private $testAccessCode;

    /**
     * @var string
     */
    private $testAccessToken;

    /**
     * @var \SsoUtils
     */
    private $ssoUtils;

    /**
     * {@inheritdoc}
     */
    public static function getAddons(): array {
        return ['dashboard', 'vanilla', 'oauth2'];
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        self::container()->call(function (
            \OAuth2Plugin $oauth2Plugin,
            \Gdn_PluginManager $pluginManager
        ) {
            $oauth2Plugin->gdn_pluginManager_afterStart_handler($pluginManager);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();

        $this->container()->call(function (
            \OAuth2Plugin $oauth2Plugin,
            \Gdn_AuthenticationProviderModel $authenticationProviderModel
        ) {
            $this->oauth2Plugin = $oauth2Plugin;
            $this->providerModel = $authenticationProviderModel;
            $this->ssoUtils = $this->callOn($oauth2Plugin, function () {
                /** @var \Gdn_OAuth2 $this */
                return $this->ssoUtils;
            });
        });

        // Re-run the structure because some tests assert fixing of the structure.
        $this->providerModel->delete([\Gdn_AuthenticationProviderModel::COLUMN_ALIAS => $this->oauth2Plugin->getProviderKey()]);
//        \Gdn::sql()->truncate('Gdn_UserAuthentication');
        $this->oauth2Plugin->structure();
        $this->providers = [];
        $this->testAccessCode = 'code'.self::id();
        $this->testAccessToken = 'token'.self::id();
    }

    /**
     * Set up multiple test connections.
     *
     * @param bool $defaultFirst Whether or not to set the first provider as the default.
     */
    protected function setupMultipleProviders(bool $defaultFirst = false): void {
        $this->providerModel->delete([\Gdn_AuthenticationProviderModel::COLUMN_ALIAS => $this->oauth2Plugin->getProviderKey()]);

        foreach ([self::CLIENT_ID1, self::CLIENT_ID2] as $id) {
            $provider = [
                \Gdn_AuthenticationProviderModel::COLUMN_KEY => $id,
                \Gdn_AuthenticationProviderModel::COLUMN_ALIAS => $this->oauth2Plugin->getProviderKey(),
                \Gdn_AuthenticationProviderModel::COLUMN_NAME => $this->oauth2Plugin->getProviderKey()." $id",
                'AssociationSecret' => "secret$id",
                'AuthorizeUrl' => "https://example.com/$id/authorize",
                'TokenUrl' => "https://example.com/$id/token",
                'ProfileUrl' => "https://example.com/$id/profile",
                'RegisterUrl' => "https://example.com/$id/register",
                'AllowAccessTokens' => true,
            ];
            if ($defaultFirst && $id === self::CLIENT_ID1) {
                $provider['IsDefault'] = true;
            }
            $r = $this->providerModel->save($provider);
            $this->assertSame($r, $id);
            $this->providers[$id] = \Gdn_AuthenticationProviderModel::getProviderByKey($id);
        }
    }

    /**
     * Set up the default single provider.
     */
    private function setupSingleProvider() {
        // Give the single provider a key that is different than the default.
        $provider = \Gdn_AuthenticationProviderModel::getProviderByScheme($this->oauth2Plugin->getProviderKey());

        $id = self::CLIENT_ID_SINGLE;
        $provider = [
            \Gdn_AuthenticationProviderModel::COLUMN_KEY => $id,
            \Gdn_AuthenticationProviderModel::COLUMN_NAME => $this->oauth2Plugin->getProviderKey()." $id",
            'AssociationSecret' => "secret$id",
            'AuthorizeUrl' => "https://example.com/$id/authorize",
            'TokenUrl' => "https://example.com/$id/token",
            'ProfileUrl' => "https://example.com/$id/profile",
        ] + $provider;

        $this->providerModel->save($provider);
        $this->providers[$id] = \Gdn_AuthenticationProviderModel::getProviderByKey($id);
    }

    /**
     * The plugin structure should switch the OAuth key from the old format to the new format.
     */
    public function testSwitchKey(): void {
        $provider = \Gdn_AuthenticationProviderModel::getProviderByScheme($this->oauth2Plugin->getProviderKey());
        $this->assertIsArray($provider);

        // Set the old school key and then make sure the structure fixes it.
        $provider['AssociationKey'] = __FUNCTION__;
        $this->providerModel->save($provider);
        $this->oauth2Plugin->structure();

        $providerDb = \Gdn_AuthenticationProviderModel::getProviderByScheme($this->oauth2Plugin->getProviderKey());
        $this->assertSame(__FUNCTION__, $providerDb[\Gdn_AuthenticationProviderModel::COLUMN_KEY]);
        $this->assertArrayNotHasKey('AssociationKey', $providerDb);
    }

    /**
     * Test a basic load/save of the oauth.
     */
    public function testSettingsEndpoint(): void {
        $html = $this->bessy()->getHtml('/settings/'.$this->oauth2Plugin->getProviderKey());
        $html->assertFormInput(\Gdn_OAuth2::COLUMN_ASSOCIATION_KEY, $this->oauth2Plugin->getProviderKey());
        $id = $html->assertFormInput($this->providerModel->PrimaryKey)->getAttribute('value');

        $provider = [
            $this->providerModel->PrimaryKey => $id,
            \Gdn_OAuth2::COLUMN_ASSOCIATION_KEY => __FUNCTION__,
            'AssociationSecret' => 'secret',
            'AuthorizeUrl' => 'https://example.com/authorize',
            'TokenUrl' => 'https://example.com/token',
            'ProfileUrl' => 'https://example.com/profile',
        ];

        $controller = $this->bessy()->post('/settings/'.$this->oauth2Plugin->getProviderKey(), $provider);

        $providerDb = \Gdn_AuthenticationProviderModel::getProviderByKey(__FUNCTION__);
        $provider[$this->providerModel->PrimaryKey] = (int)$provider[$this->providerModel->PrimaryKey];
        $this->assertArraySubsetRecursive($provider, $providerDb);
    }

    /**
     * Assert that the authorize redirects to the right place.
     *
     * @param string|null $clientID
     */
    protected function assertRedirectFlow(?string $clientID): void {

        try {
            $this->bessy()->get("/entry/" . $this->oauth2Plugin->getProviderKey() . "-redirect", array_filter(['client_id' => $clientID]));
            $this->fail('The endpoint did not redirect.');
        } catch (ResponseException $ex) {
            $response = $ex->getResponse();
            $this->assertSame(302, $response->getStatus());

            $location = $response->getHeader('Location');
            if ($clientID) {
                $provider = $this->providers[$clientID];
            } else {
                $provider = reset($this->providers);
                $clientID = $provider[\Gdn_AuthenticationProviderModel::COLUMN_KEY];
            }

            $queryString = parse_url($location, PHP_URL_QUERY);
            $this->assertSame($provider['AuthorizeUrl'] . '?' . $queryString, $location);

            parse_str($queryString, $query);

            $this->assertSame($clientID, $query['client_id']);

            $this->assertArrayHasKey('state', $query);
            $state = $this->callMethodOn($this->oauth2Plugin, 'decodeState', $query['state']);
            $this->assertArrayHasKey('cid', $state, 'There must be a cid in the state.');
            $this->assertSame($clientID, $state['cid'], 'The client ID must be passed in the state.');
        }
    }

    /**
     * You should be able to redirect to the appropriate provider's URL when specifying /entry/oauth-redirect?client_id=xyz.
     */
    public function testRedirectWithClientID(): void {
        $this->setupMultipleProviders();

        $this->assertRedirectFlow(self::CLIENT_ID1);
        $this->assertRedirectFlow(self::CLIENT_ID2);
    }

    /**
     * You should be able to redirect to the
     */
    public function testRedirectWithNoClientID(): void {
        $this->setupSingleProvider();

        $this->assertRedirectFlow(null);
    }

    /**
     * Test the return URL that goes from `/entry/oauth2` -> `/entry/connect/oauth2`.
     *
     * @param string $clientID
     */
    public function assertReturnUrlFlow(string $clientID): void {
        $this->createProviderProxyRequestMock($clientID);

        try {
            // 1. This is the URL that the OAuth server redirects to.
            $this->bessy()->get('/entry/'.$this->oauth2Plugin->getProviderKey(), array_filter([
                'code' => $this->testAccessCode,
                'state' => $this->generateState($clientID),
            ]));
            $this->fail('The endpoint should redirect to /entry/connect');
        } catch (ResponseException $ex) {
            // 2. Our OAuth implementation then redirects to `/entry/connect/oauth2`.
            $response = $ex->getResponse();
            $this->assertSame(302, $response->getStatus());
            $location = new Request($response->getHeader('Location'));
            $this->assertSubpath('/entry/connect/'.$this->oauth2Plugin->getProviderKey(), $location->getPath());

            // 3. Simulate the browser requesting the redirected URL.
            $controller = $this->bessy()->get($this->stripWebRoot($location->getPath()), $location->getQuery());

            // 4. The user should be signed in.
            $this->assertTrue(\Gdn::session()->isValid(), 'The user was not signed into Vanilla.');
            $auth = \Gdn::userModel()->getAuthenticationByUser(\Gdn::session()->UserID, $clientID);
            $this->assertIsArray($auth, 'The GDN_UserAuthentication entry was not found or incorrect.');
        }
    }

    /**
     * Test the OAuth return flow with multiple connections.
     */
    public function testReturnUrlFlowMultipleProviders(): void {
        $this->setupMultipleProviders();
        \Gdn::session()->end();

        $this->assertReturnUrlFlow(self::CLIENT_ID1);
    }

    /**
     * Test the OAuth return flow with a single connection.
     */
    public function testReturnUrlFlowSingleProvider(): void {
        $this->setupSingleProvider();

        \Gdn::session()->end();

        $this->assertReturnUrlFlow(self::CLIENT_ID_SINGLE);
    }

    /**
     * Generate a fake state that will validate.
     *
     * @param string $clientID
     * @param bool $forceNew
     * @return string
     */
    protected function generateState(string $clientID, bool $forceNew = true): string {
        $state = $this->ssoUtils->getStateToken($forceNew);
        $r = $this->callMethodOn($this->oauth2Plugin, 'encodeState', ['cid' => $clientID, 'token' => $state]);
        return $r;
    }

    /**
     * Create a mock `ProxyRequest` class that mimics an OAuth 2 sever.
     *
     * @param ?string $clientID
     * @param bool $register Whether or not to register the mock in the container.
     * @return \ProxyRequest
     */
    protected function createProviderProxyRequestMock(?string $clientID, $register = true): \ProxyRequest {
        if ($clientID === null) {
            $provider = \Gdn_AuthenticationProviderModel::getProviderByScheme($this->oauth2Plugin->getProviderKey());
        } else {
            $provider = \Gdn_AuthenticationProviderModel::getProviderByKey($clientID);
        }
        $mock = $this->createMock(\ProxyRequest::class);
        $mock->ContentType = 'application/json';

        $mock->method('responseClass')
            ->willReturnCallback(function ($class) use ($mock) {
                return fnmatch(str_replace('x', '*', $class), $mock->ResponseStatus);
            });

        $mock->method('request')
            ->willReturnCallback(function ($options = null, $params = null, $files = null, $extraHeaders = null) use ($mock, $provider) {
                $request = new Request($options['URL'], $options['Method'], $params);
                $token = new Request($provider['TokenUrl']);
                $profile = new Request($provider['ProfileUrl']);
                $mock->ResponseStatus = 200;

                switch ($request->getPath()) {
                    case $token->getPath():
                        TestCase::assertSame('POST', $request->getMethod());
                        TestCase::assertSame($provider[\Gdn_AuthenticationProviderModel::COLUMN_KEY], $params['client_id'], 'Invalid client_id.');
                        TestCase::assertSame($this->testAccessCode, $params['code'], 'Invalid access code.');
                        TestCase::assertSame($provider['AssociationSecret'], $params['client_secret'], 'Invalid secret.');
                        TestCase::assertSame('authorization_code', $params['grant_type'], 'Invalid grant_type.');

                        $response = [
                            'access_token' => $this->testAccessToken,
                            'refresh_token' => $this->testAccessToken,
                        ];
                        break;
                    case $profile->getPath():
                        if (empty($provider['Bearer'])) {
                            TestCase::assertSame($this->testAccessToken, $request->getQuery()['access_token'], 'Invalid access token.');
                        } else {
                            TestCase::assertSame('Bearer '.$this->testAccessToken, $extraHeaders['Authorization']);
                        }

                        $response = [
                            'UniqueID' => $this->testAccessToken,
                            'Name' => $this->testAccessToken,
                            'Email' => $this->testAccessToken.'@example.com',
                        ];
                        break;
                    default:
                        $mock->ResponseStatus = 404;
                        throw new \Exception("Unknown URL: ".$options['URL']);
                }
                $mock->ResponseBody = json_encode($response);
                return $response;
            });

        if ($register) {
            $this->container()->setInstance(\ProxyRequest::class, $mock);
        }
        return $mock;
    }

    /**
     * Assert that sign in buttons for OAuth clients show up on the sign in page.
     *
     * @param array $clientIDs The client IDs to look for.
     */
    public function assertSignInButtons(array $clientIDs) {
        $html = $this->bessy()->getHtml('/entry/signin');
        $buttons = $html->queryCssSelector('.Method > a');
        $this->assertSame(count($clientIDs), $buttons->count(), 'Wring sign in button count.');

        $ids = [];
        foreach ($buttons as $button) {
            $href = new Request($button->getAttribute('href'));
            $ids[$href->getQuery()['client_id']] = true;

            $this->oauth2Plugin->setCurrentClientID($href->getQuery()['client_id']);
            $this->assertUrlSubset($this->oauth2Plugin->authorizeUri(), $href);
        }
        $expected = array_fill_keys($clientIDs, true);
        $this->assertArraySubsetRecursive($expected, $ids, 'Could not find buttons for all client IDs.');
    }

    /**
     * Make sure multiple sign in buttons show up on the page.
     */
    public function testMultipleSignInButtons(): void {
        $this->setupMultipleProviders();
        $this->assertSignInButtons(array_keys($this->providers));
    }

    /**
     * Marking a buttons as `Visible = false` should not display on the sign in page.
     */
    public function testSignInButtonVisibility(): void {
        $this->setupMultipleProviders();
        $this->providerModel->update(['Visible' => false], [\Gdn_AuthenticationProviderModel::COLUMN_KEY => self::CLIENT_ID2]);

        $this->assertSignInButtons([self::CLIENT_ID1]);
    }

    /**
     * Marking a buttons as `Active = false` should not display on the sign in page.
     */
    public function testSignInButtonDeactivate(): void {
        $this->setupMultipleProviders();
        $this->providerModel->update(['Active' => false], [\Gdn_AuthenticationProviderModel::COLUMN_KEY => self::CLIENT_ID2]);

        $this->assertSignInButtons([self::CLIENT_ID1]);
    }

    /**
     * You should be able to issue an access token with multiple connections.
     */
    public function testExchangeAccessToken(): void {
        $this->setupMultipleProviders();
        $this->createProviderProxyRequestMock(self::CLIENT_ID1);

        $token = $this->api()->post('/tokens/oauth', [
            'clientID' => self::CLIENT_ID1,
            'oauthAccessToken' => $this->testAccessToken,
        ])->getBody();

        /** @var \AccessTokenModel $tokenModel */
        $tokenModel = $this->container()->get(\AccessTokenModel::class);

        $r = $tokenModel->verify($token['accessToken']);
        $user = \Gdn::userModel()->getID($r['UserID'], DATASET_TYPE_ARRAY);
        $this->assertSame($this->testAccessToken, $user['Name']);
        $row = \Gdn::userModel()->getAuthenticationByUser($r['UserID'], self::CLIENT_ID1);
        $this->assertIsArray($row);
    }

    /**
     * Make sure the default sign in redirects properly.
     */
    public function testDefaultSignIn(): void {
        $this->setupMultipleProviders(true);

        try {
            $this->bessy()->get('/entry/signin');
            $this->fail('Expected a redirect.');
        } catch (ResponseException $ex) {
            $response = $ex->getResponse();
            $this->assertSame(302, $response->getStatus());
            $location = $response->getHeader('Location');

            $this->oauth2Plugin->setCurrentClientID(self::CLIENT_ID1);
            $this->assertUrlSubset($this->oauth2Plugin->authorizeUri([]), $location);
        }
    }

    /**
     * Make sure the default register redirects properly.
     */
    public function testDefaultRegister(): void {
        $this->setupMultipleProviders(true);

        try {
            $this->bessy()->get('/entry/register');
            $this->fail('Expected a redirect.');
        } catch (ResponseException $ex) {
            $response = $ex->getResponse();
            $this->assertSame(302, $response->getStatus());
            $location = $response->getHeader('Location');

            $this->oauth2Plugin->setCurrentClientID(self::CLIENT_ID1);

            $url = new Request($this->callMethodOn($this->oauth2Plugin, 'realRegisterUri'));
            $url->removeQueryItem('state');
            $this->assertUrlSubset($url, $location);
        }
    }
}
