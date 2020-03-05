<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models\SSOModel;

use Garden\Schema\ValidationException;
use Vanilla\Models\AuthenticatorModel;
use Vanilla\Models\SSOData;
use Vanilla\Models\SSOModel;
use VanillaTests\Fixtures\Authenticator\MockSSOAuthenticator;
use VanillaTests\SetsGeneratorTrait;
use VanillaTests\SharedBootstrapTestCase;
use VanillaTests\SiteTestTrait;

/**
 * Class CreateUserTest.
 */
class CreateUserTest extends SharedBootstrapTestCase {
    use SiteTestTrait;
    use SetsGeneratorTrait;

    /** @var SSOModel */
    private static $ssoModel;

    private static $invalidEmails = [null, '' , 'not an email'];
    private static $existingEmail = 'existingEmail@example.com';

    private static $invalidNames = [null, '', 'U'];
    private static $existingName = 'ExistingUsername';

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        parent::setUp();

        self::$ssoModel = self::container()->get(SSOModel::class);

        /** @var \UserModel $userModel */
        $userModel = self::container()->get(\UserModel::class);
        $userModel->insert([
            'Name' => self::$existingName,
            'Email' => self::$existingEmail,
            'Password' => uniqid(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function tearDown(): void {
        /** @var \Gdn_SQLDriver $driver */
        $driver = self::container()->get('SqlDriver');
        $driver->truncate('User');

        parent::tearDown();
    }

    /**
     * Create a user using SSOModel.
     *
     * @dataProvider provider
     *
     * @param $configurations
     * @param $ssoDataArray
     * @param $options
     * @param $expectedResult
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\ClientException
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws \Garden\Web\Exception\ServerException
     */
    public function testCreateUser($configurations, $ssoDataArray, $options, $expectedResult) {
        /** @var \Gdn_Configuration $config */
        $config = self::container()->get(\Gdn_Configuration::class);

        foreach ($configurations as $name => $value) {
            $config->set($name, $value);
        }

        $ssoData = SSOData::fromArray($ssoDataArray);

        /** @var \Vanilla\Models\AuthenticatorModel $authenticatorModel */
        $authenticatorModel = $this->container()->get(AuthenticatorModel::class);
        $authenticator = $authenticatorModel->createSSOAuthenticatorInstance([
            'authenticatorID' => $ssoData->getAuthenticatorID(),
            'type' => $ssoData->getAuthenticatorType(),
            'SSOData' => json_decode(json_encode($ssoData), true),
        ]);

        if (isset($expectedResult['exception'])) {
            $this->expectException($expectedResult['exception']);
        }

        try {
            $result = self::$ssoModel->createUser($ssoData, $options);
            $expectedResult['dataCallback']($result);
        } finally {
            $authenticatorModel->deleteSSOAuthenticatorInstance($authenticator);
        }

    }


    /**
     * Determine, from the parameters,
     *
     * @param $configurations
     * @param $ssoDataArray
     * @param $options
     * @param $expectedResult
     *
     * @return array
     */
    public function determineExpectedResult($configurations, $ssoDataArray, $options, $expectedResult) {
        $name = null;
        $email = null;
        if ($options['useSSOData']) {
            $name = $ssoDataArray['user']['name'];
            $email = $ssoDataArray['user']['email'];
        }

        // This always override.
        if (isset($options['name'])) {
            $name = $options['name'];
        }
        // This always override.
        if (isset($options['email'])) {
            $email = $options['email'];
        }

        $noName = !$name;
        $duplicateUsername = $configurations['Garden.Registration.NameUnique'] && $name === self::$existingName;
        $invalidName = in_array($name, self::$invalidNames, true);
        if ($noName || $duplicateUsername || $invalidName) {
            return [
                'exception' => ValidationException::class,
                'dataCallback' => function() {},
            ];
        }

        $noEmail = !$configurations['Garden.Registration.NoEmail'] && !$email;
        // EmailUnique has priority over NoEmail if an email is set.
        $duplicateEmail = $configurations['Garden.Registration.EmailUnique'] && $email === self::$existingEmail;
        $invalidEmail = in_array($name, self::$invalidEmails, true);

        if ($noEmail || $duplicateEmail || $invalidEmail) {
            return [
                'exception' => ValidationException::class,
                'dataCallback' => function() {},
            ];
        }

        return [
            'exception' => null,
            'dataCallback' => function($data) use ($name, $email) {
                $this->assertArrayHasKey('Name', $data);
                $this->assertEquals($name, $data['Name']);

                $this->assertArrayHasKey('Email', $data);
                $this->assertEquals($email, $data['Email']);
            }
        ];
    }

    /**
     * Provide data sets.
     *
     * @return array
     */
    public function provider() {
        $configsWEmail = [
            'Garden.Registration.NoEmail' => [false],
            'Garden.Registration.EmailUnique' => [false, true],
            'Garden.Registration.NameUnique' => [false, true],
        ];
        $configsNoEmail = [
            'Garden.Registration.NoEmail' => [true],
            'Garden.Registration.EmailUnique' => [false, true],
            'Garden.Registration.NameUnique' => [false, true],
        ];
        $configurationSets = array_merge(
            $this->combinatorialSetsGenerator($configsWEmail),
            $this->combinatorialSetsGenerator($configsNoEmail)
        );


        $ssoDataUserArray = array_merge(
            $this->combinatorialSetsGenerator([
                'name' => ['User'],
                'email' => ['user@example.com']
            ]),
            $this->combinatorialSetsGenerator([
                'name' => ['User'],
                'email' => array_merge(self::$invalidEmails, [self::$existingEmail]),
            ]),
            $this->combinatorialSetsGenerator([
                'name' => array_merge(self::$invalidNames, [self::$existingName]),
                'email' => ['user@example.com'],
            ])
        );

        $ssoDataArray = $this->combinatorialSetsGenerator([
            'authenticatorType' => [MockSSOAuthenticator::getType()],
            'authenticatorID' => [MockSSOAuthenticator::getType()],
            'uniqueID' => ['ssouniqueid'],
            'user' => $ssoDataUserArray,
        ]);

        $options = $this->combinatorialSetsGenerator([
            'useSSOData' => [true, false],
            'name' => [null, 'OverriddenUser'],
            'email' => [null, 'overriddenUser@example.com'],
        ]);

        $result =  $this->combinatorialSetsGenerator([
            'configurations' => $configurationSets,
            'ssoDataArray' => $ssoDataArray,
            'options' => $options,
            'expectedResult' => [[$this, 'determineExpectedResult']],
        ]);

        return $result;
    }
}
