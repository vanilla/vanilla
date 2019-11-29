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
 * Test the /api/v2/authenticate endpoints.
 */
class AutoConnectTest extends AbstractAPIv2Test {

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
     * @inheritdoc
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        self::container()->rule(MockSSOAuthenticator::class);

        /** @var \Gdn_Configuration $config */
        self::$config = static::container()->get(\Gdn_Configuration::class);
        self::$config->set('Feature.'.\AuthenticateApiController::FEATURE_FLAG.'.Enabled', true, true, false);
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
        parent::setUp();

        $uniqueID = self::randomUsername('ac');
        $userData = [
            'name' => $uniqueID,
            'email' => $uniqueID.'@example.com',
            'password' => 'pwd_'.$uniqueID,
        ];

        /** @var \UsersApiController $usersAPIController */
        $usersAPIController = $this->container()->get('UsersAPIController');
        $userFragment = $usersAPIController->post($userData)->getData();
        $this->currentUser = array_merge($userFragment, $userData);

        /** @var \Vanilla\Models\AuthenticatorModel $authenticatorModel */
        $authenticatorModel = $this->container()->get(AuthenticatorModel::class);

        $authType = MockSSOAuthenticator::getType();
        $this->authenticator = $authenticatorModel->createSSOAuthenticatorInstance([
            'authenticatorID' => $authType,
            'type' => $authType,
            'SSOData' => json_decode(json_encode(new SSOData($authType, $authType, $uniqueID, $userData)), true),
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
     * Test POST /authenticate with different configuration combination.
     *
     * @param $configurations
     * @param $authenticatorProperties
     * @param $expectedResults
     *
     * @dataProvider provider
     */
    public function testAuthenticate($configurations, $authenticatorProperties, $expectedResults) {
        foreach($configurations as $key => $value) {
            self::$config->set($key, $value);
        }
        foreach($authenticatorProperties as $property => $value) {
            $this->authenticator->$property($value);
        }

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

        $this->assertIsArray($body);
        $this->assertArrayHasKey('authenticationStep', $body);

        if ($expectedResults['authenticationStep'] === 'authenticated') {
            $this->assertEquals('authenticated', $body['authenticationStep']);
        } else if ($expectedResults['authenticationStep'] === 'linkUser') {
            $this->assertEquals('linkUser', $body['authenticationStep']);
            $this->assertArrayHasKey('authSessionID', $body);
        }

        // Start the session as the current user to do the verification.
        $this->api()->setUserID($this->currentUser['userID']);

        $result = $this->api()->get(
            $this->baseUrl.'/authenticators/'.$this->authenticator->getID()
        );

        $this->assertEquals(200, $result->getStatusCode());

        $body = $result->getBody();

        $this->assertInternalType('array', $body);
        $this->assertArrayHasKey('isUserLinked', $body);
        $this->assertEquals($expectedResults['isUserLinked'], $body['isUserLinked']);
    }

    /**
     * Provide configuration combinations.
     *
     * @return array
     */
    public function provider() {
        $configurationsDefinition = [
            'Garden.Registration.NoEmail' => [false, true],
            'Garden.Registration.EmailUnique' => [false, true],
            'Garden.Registration.AllowConnect' => [false, true],
        ];
        $configurationSets = $this->configurationSetsGenerator($configurationsDefinition);

        $authenticatorProperties = [
            'setAutoLinkUser' => [true, false],
        ];
        $authenticatorPropertiesSets = $this->configurationSetsGenerator($authenticatorProperties);


        foreach($authenticatorPropertiesSets as $authenticatorProperties) {
            foreach($configurationSets as $configurations) {
                $data[] = [
                    'configurations' => $configurations,
                    'authenticatorProperties' => $authenticatorProperties,
                    'expectedResults' => $this->determineExpectedResult($configurations, $authenticatorProperties),
                ];
            }
        }

        return $data;
    }

    /**
     * Determine if the a configuration combination should pass or fail a test.
     *
     * @param array $configurationSet
     * @param array $authenticatorProperties
     * @return array
     */
    private function determineExpectedResult($configurationSet, $authenticatorProperties) {
        $authenticationFailure = [
            'authenticationStep' => 'linkUser',
            'isUserLinked' => false,
        ];
        $authenticationSuccess = [
            'authenticationStep' => 'authenticated',
            'isUserLinked' => true,
        ];

        // Since we are authenticating as a user that already exists in vanilla it will always fail
        if (!$configurationSet['Garden.Registration.AllowConnect']) {
            return $authenticationFailure;
        }

        // This disable Garden.Registration.EmailUnique which disable Garden.Registration.AutoConnect
        if ($configurationSet['Garden.Registration.NoEmail']) {
            return $authenticationFailure;
        }

        // This disable Garden.Registration.AutoConnect
        if (!$configurationSet['Garden.Registration.EmailUnique']) {
            return $authenticationFailure;
        }

        if (!$authenticatorProperties['setAutoLinkUser']) {
            return $authenticationFailure;
        }

        return $authenticationSuccess;
    }

    /**
     * Generate all possible configuration sets from a list of configurations and their value(s).
     *
     * @param $configurations
     * @return array
     */
    private function configurationSetsGenerator($configurations) {
        $configurationsCurrentIndex = [];
        $configurationsMaxIndex = [];
        $possibleConfigurations = 1;
        foreach ($configurations as $configuration => $values) {
            $configurationsCurrentIndex[$configuration] = 0;
            $configurationsMaxIndex[$configuration] = count($values);
            $possibleConfigurations *= $configurationsMaxIndex[$configuration];
        }
        $configurationSets = array_fill(0, $possibleConfigurations, array_fill_keys(array_keys($configurations), null));

        foreach ($configurationSets as &$set) {
            foreach ($set as $configuration => &$value) {
                $value = $configurations[$configuration][$configurationsCurrentIndex[$configuration]];
            }

            foreach ($configurationsCurrentIndex as $configuration => &$index) {
                if ($index === 0) {
                    if ($configurationsMaxIndex[$configuration] > 1) {
                        $index += 1;
                        break;
                    }
                } else {
                    if ($index + 1 < $configurationsMaxIndex[$configuration]) {
                        $index += 1;
                        break;
                    } else {
                        $index = 0;
                    }
                }
            }
        }

        return $configurationSets;
    }
}
