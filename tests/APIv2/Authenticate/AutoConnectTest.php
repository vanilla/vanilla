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
class AutoConnectTest extends AbstractAPIv2Test {

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
        parent::setUp();

        $this->authenticator = new TestSSOAuthenticator();

        $uniqueID = uniqid('ac_');
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
     * Test POST /authenticate with different configuration combination.
     *
     * @param $configurations
     * @param $expectedResults
     *
     * @dataProvider provider
     */
    public function testAuthenticate($configurations, $expectedResults) {
        foreach($configurations as $key => $value) {
            self::$config->set($key, $value);
        }

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

        if ($expectedResults['authenticationStep'] === 'authenticated') {
            $this->assertEquals('authenticated', $body['authenticationStep']);
        } else if ($expectedResults['authenticationStep'] === 'linkUser') {
            $this->assertEquals('linkUser', $body['authenticationStep']);
            $this->assertArrayHasKey('authSessionID', $body);
        }

        // Start the session as the current user to do the verification.
        $this->api()->setUserID($this->currentUser['userID']);

        $result = $this->api()->get(
            $this->baseUrl.'/'.$this->authenticator->getName().'/'.$this->authenticator->getID()
        );

        $this->assertEquals(200, $result->getStatusCode());

        $body = $result->getBody();

        $this->assertInternalType('array', $body);
        $this->assertArrayHasKey('linked', $body);
        $this->assertEquals($expectedResults['isUserLinked'], $body['linked']);
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
            'Garden.Registration.AutoConnect' => [false, true],
        ];
        $configurationSets = $this->configurationSetsGenerator($configurationsDefinition);

        foreach($configurationSets as $configurationSet) {
            $data[] = [
                'configurations' => $configurationSet,
                'expectedResults' => $this->determineExpectedResult($configurationSet),
            ];
        }

        return $data;
    }

    /**
     * Determine if the a configuration combination should pass or fail a test.
     *
     * @param $configurationSet
     * @return array
     */
    private function determineExpectedResult($configurationSet) {
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

        if (!$configurationSet['Garden.Registration.AutoConnect']) {
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
