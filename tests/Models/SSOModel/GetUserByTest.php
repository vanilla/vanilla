<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models\SSOModel;

use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Models\SSOModel;
use VanillaTests\SiteTestTrait;
use VanillaTests\InvokeMethodTrait;

/**
 * Class SSOModelLinkUserFromCredentials.
 */
class GetUserByTest extends SharedBootstrapTestCase {
    use SiteTestTrait {
        SiteTestTrait::setUpBeforeClass as siteSetUpBeforeClass;
    }
    use InvokeMethodTrait;

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
        'duplicate' => [
            // 'UserID' // Will be filled in setUpBeforeClass()
            'Name' => 'duplicated',
            'Email' => 'duplicated@example.com',
            'Password' => 'trustno1',
        ],
        'duplicatedName' => [
            // 'UserID' // Will be filled in setUpBeforeClass()
            'Name' => 'duplicated',
            'Email' => 'duplicateName@example.com',
            'Password' => 'trustno1',
        ],
        'duplicatedEmail' => [
            // 'UserID' // Will be filled in setUpBeforeClass()
            'Name' => 'duplicatedEmail',
            'Email' => 'duplicated@example.com',
            'Password' => 'trustno1',
        ],
    ];

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass(): void {
        self::siteSetUpBeforeClass();

        /** @var \Gdn_Configuration $config */
        $config = self::container()->get(\Gdn_Configuration::class);

        $config->set('Garden.Registration.NameUnique', false);
        $config->set('Garden.Registration.EmailUnique', false);

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

        $config->set('Garden.Registration.NameUnique', true);
        $config->set('Garden.Registration.EmailUnique', true);
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
     * Get a user by its ID.
     */
    public function testGetByID() {
        $user = self::$users['default'];
        $fetchedUser = $this->invokeMethod(self::$ssoModel, 'getUserByID', [$user['UserID']]);

        $this->assertInternalType('array', $fetchedUser);
        $this->assertArrayHasKey('UserID', $fetchedUser);
        $this->assertEquals($fetchedUser['UserID'], $user['UserID']);
    }

    /**
     * @expectedException \Garden\Web\Exception\NotFoundException
     */
    public function testGetByIdNotFound() {
        $this->invokeMethod(self::$ssoModel, 'getUserByID', [666]);
    }

    /**
     * Get a user by its email.
     */
    public function testGetByEmail() {
        $user = self::$users['default'];
        $fetchedUser = $this->invokeMethod(self::$ssoModel, 'getUserByEmail', [$user['Email']]);

        $this->assertInternalType('array', $fetchedUser);
        $this->assertArrayHasKey('UserID', $fetchedUser);
        $this->assertEquals($fetchedUser['UserID'], $user['UserID']);
    }

    /**
     * @expectedException \Garden\Web\Exception\ClientException
     * @expectedExceptionMessage Cannot get user by email due to current configurations.
     */
    public function testGetByEmailWithConfigNoEmailTrue() {
        /** @var \Gdn_Configuration $config */
        $config = self::container()->get(\Gdn_Configuration::class);
        $config->set('Garden.Registration.NoEmail', true);

        try {
            $user = self::$users['default'];
            $fetchedUser = $this->invokeMethod(self::$ssoModel, 'getUserByEmail', [$user['Email']]);
        } finally {
            $config->set('Garden.Registration.NoEmail', false);
        }
    }

    /**
     * @expectedException \Garden\Web\Exception\ClientException
     * @expectedExceptionMessage Cannot get user by email due to current configurations.
     */
    public function testGetByEmailWithConfigEmailUniqueFalse() {
        /** @var \Gdn_Configuration $config */
        $config = self::container()->get(\Gdn_Configuration::class);
        $config->set('Garden.Registration.EmailUnique', false);

        try {
            $user = self::$users['default'];
            $this->invokeMethod(self::$ssoModel, 'getUserByEmail', [$user['Email']]);
        } finally {
            $config->set('Garden.Registration.EmailUnique', true);
        }
    }

    /**
     * @expectedException \Garden\Web\Exception\ClientException
     * @expectedExceptionMessage Email is required.
     */
    public function testGetByEmailEmpty() {
        $this->invokeMethod(self::$ssoModel, 'getUserByEmail', ['']);
    }

    /**
     * @expectedException \Garden\Web\Exception\ServerException
     * @expectedExceptionMessage Multiple users found with the same email.
     */
    public function testGetByEmailDuplicatedResults() {
        $this->invokeMethod(self::$ssoModel, 'getUserByEmail', ['duplicated@example.com']);
    }

    /**
     * @expectedException \Garden\Web\Exception\NotFoundException
     */
    public function testGetByEmailNotFound() {
        $this->invokeMethod(self::$ssoModel, 'getUserByEmail', ['doesnotexist@example.com']);
    }

    /**
     * Get a user by its name.
     */
    public function testGetByName() {
        $user = self::$users['default'];
        $fetchedUser = $this->invokeMethod(self::$ssoModel, 'getUserByName', [$user['Name']]);

        $this->assertInternalType('array', $fetchedUser);
        $this->assertArrayHasKey('UserID', $fetchedUser);
        $this->assertEquals($fetchedUser['UserID'], $user['UserID']);
    }

    /**
     * @expectedException \Garden\Web\Exception\ClientException
     * @expectedExceptionMessage Cannot get user by name due to current configurations.
     */
    public function testGetByNameWithConfigNameUniqueFalse() {
        /** @var \Gdn_Configuration $config */
        $config = self::container()->get(\Gdn_Configuration::class);
        $config->set('Garden.Registration.NameUnique', false);

        try {
            $user = self::$users['default'];
            $this->invokeMethod(self::$ssoModel, 'getUserByName', [$user['Name']]);
        } finally {
            $config->set('Garden.Registration.NameUnique', true);
        }
    }

    /**
     * @expectedException \Garden\Web\Exception\ClientException
     * @expectedExceptionMessage Name is required.
     */
    public function testGetByNameEmpty() {
        $this->invokeMethod(self::$ssoModel, 'getUserByName', ['']);
    }

    /**
     * @expectedException \Garden\Web\Exception\ServerException
     * @expectedExceptionMessage Multiple users found with the same name.
     */
    public function testGetByNameDuplicatedResults() {
        $this->invokeMethod(self::$ssoModel, 'getUserByName', ['duplicated']);
    }

    /**
     * @expectedException \Garden\Web\Exception\NotFoundException
     */
    public function testGetByNameNotFound() {
        $this->invokeMethod(self::$ssoModel, 'getUserByName', ['doesnotexist']);
    }


}
