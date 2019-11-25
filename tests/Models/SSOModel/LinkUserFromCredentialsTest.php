<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models\SSOModel;

use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Models\SSOData;
use Vanilla\Models\SSOModel;
use VanillaTests\Fixtures\Authenticator\MockSSOAuthenticator;
use VanillaTests\SiteTestTrait;

/**
 * Class LinkUserFromCredentialsTest.
 */
class LinkUserFromCredentialsTest extends SharedBootstrapTestCase {
    use SiteTestTrait {
        SiteTestTrait::setUpBeforeClass as siteSetUpBeforeClass;
    }

    /** @var SSOModel */
    private static $ssoModel;

    /** @var array  */
    private static $users = [
        'default' => [
            // 'UserID' // Will be filled in setUpBeforeClass()
            'Name' => 'TypicalUser1',
            'Email' => 'typicalUser1@example.com',
            'Password' => 'trustno1',
        ],
        'reset' => [
            //'UserID' // Will be filled in setUpBeforeClass()
            'Name' => 'TypicalUser2',
            'Email' => 'typicalUser2@example.com',
            'Password' => 'trustno1',
            'HashMethod' => 'reset',
        ],
        'random' => [
            //'UserID' // Will be filled in setUpBeforeClass()
            'Name' => 'TypicalUser3',
            'Email' => 'typicalUser3@example.com',
            'Password' => 'trustno1',
            'HashMethod' => 'random',
        ],
    ];

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass(): void {
        self::siteSetUpBeforeClass();

        /** @var \UserModel $userModel */
        $userModel = self::container()->get(\UserModel::class);
        foreach (self::$users as &$user) {
            $userID = $userModel->save($user, ['NoConfirmEmail' => true]);
            if ($userID) {
                $user['UserID'] = $userID;
            } else {
                self::fail('Something went wrong in setUp.'.PHP_EOL.print_r($userModel->validationResults(), true));
                break;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        parent::setUp();

        // Let's get a new SSOModel for this test.
        self::$ssoModel = self::container()->get(SSOModel::class);
    }

    /**
     * @inheritdoc
     */
    public function tearDown(): void {
        /** @var \Gdn_SQLDriver $driver */
        $driver = self::container()->get('SqlDriver');
        $driver->truncate('UserAuthentication');

        parent::tearDown();
    }

    /**
     * Convenience method to create SSOData object.
     *
     * @param $name
     * @param $email
     *
     * @return \Vanilla\Models\SSOData
     * @throws \Exception
     */
    private function createSSOData($name, $email) {
        return SSOData::fromArray([
            'authenticatorType' => MockSSOAuthenticator::getType(),
            'authenticatorID' => MockSSOAuthenticator::getType(),
            'uniqueID' => 'ssouniqueid',
            'user' => [
                'name' => $name,
                'email' => $email,
            ],
        ]);
    }

    /**
     * Link a user using its credentials.
     */
    public function testLinkUser() {
        $user = self::$users['default'];

        $linkedUser = self::$ssoModel->linkUserFromCredentials(
            $this->createSSOData($user['Name'], $user['Email']),
            SSOModel::IDENTIFIER_TYPE_ID,
            $user['UserID'],
            $user['Password']
        );

        $this->assertInternalType('array', $linkedUser);

        foreach($user as $field => $value) {
            if ($field === 'Password') {
                continue;
            }
            $this->assertArrayHasKey($field, $linkedUser);
            $this->assertEquals($linkedUser[$field], $value);
        }
    }

    /**
     * Try to link a user with bad credentials.
     *
     * @expectedException \Garden\Web\Exception\ClientException
     * @expectedExceptionMessage The password validation failed.
     */
    public function testLinkUserBadPassword() {
        $user = self::$users['default'];

        self::$ssoModel->linkUserFromCredentials(
            $this->createSSOData($user['Name'], $user['Email']),
            SSOModel::IDENTIFIER_TYPE_ID,
            $user['UserID'],
            'DefinitelyNotHisPassword'
        );
    }

    /**
     * Test linking a user with Garden.Registration.AllowConnect = false.
     *
     * @expectedException \Garden\Web\Exception\ServerException
     * @expectedExceptionMessage Liking user is not allowed.
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\ClientException
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws \Garden\Web\Exception\ServerException
     */
    public function testAllowConnectDisabled() {
        /** @var \Gdn_Configuration $config */
        $config = self::container()->get(\Gdn_Configuration::class);
        $config->set('Garden.Registration.AllowConnect', false);

        $user = self::$users['default'];

        try {
            self::$ssoModel->linkUserFromCredentials(
                $this->createSSOData($user['Name'], $user['Email']),
                SSOModel::IDENTIFIER_TYPE_ID,
                $user['UserID'],
                $user['Password']
            );
        } finally {
            $config->set('Garden.Registration.AllowConnect', true);
        }
    }

    /**
     * @expectedException \Garden\Schema\ValidationException
     * @expectedExceptionMessageRegExp /^You need to reset your password\./
     */
    public function testUserHashMethodReset() {
        $user = self::$users['reset'];

        self::$ssoModel->linkUserFromCredentials(
            $this->createSSOData($user['Name'], $user['Email']),
            SSOModel::IDENTIFIER_TYPE_ID,
            $user['UserID'],
            $user['Password']
        );
    }

    /**
     * @expectedException \Garden\Schema\ValidationException
     * @expectedExceptionMessageRegExp /^Your account does not have a password assigned to it yet\./
     */
    public function testUserHashMethodRandom() {
        $user = self::$users['random'];

        self::$ssoModel->linkUserFromCredentials(
            $this->createSSOData($user['Name'], $user['Email']),
            SSOModel::IDENTIFIER_TYPE_ID,
            $user['UserID'],
            $user['Password']
        );
    }
}
