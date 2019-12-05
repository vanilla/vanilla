<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models\SSOModel;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
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

        $this->assertIsArray($fetchedUser);
        $this->assertArrayHasKey('UserID', $fetchedUser);
        $this->assertEquals($fetchedUser['UserID'], $user['UserID']);
    }

    /**
     * Test getting an item by a non-existent ID.
     */
    public function testGetByIdNotFound() {
        $this->expectException(NotFoundException::class);
        $this->invokeMethod(self::$ssoModel, 'getUserByID', [666]);
    }

    /**
     * Get a user by its email.
     */
    public function testGetByEmail() {
        $user = self::$users['default'];
        $fetchedUser = $this->invokeMethod(self::$ssoModel, 'getUserByEmail', [$user['Email']]);

        $this->assertIsArray($fetchedUser);
        $this->assertArrayHasKey('UserID', $fetchedUser);
        $this->assertEquals($fetchedUser['UserID'], $user['UserID']);
    }

    /**
     * Test get by email with `Garden.Registration.NoEmail === true`.
     */
    public function testGetByEmailWithConfigNoEmailTrue() {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Cannot get user by email due to current configurations.');

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
     * Test get by email with `Garden.Registration.EmailUnique === false`.
     */
    public function testGetByEmailWithConfigEmailUniqueFalse() {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Cannot get user by email due to current configurations.');

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
     * Test getting a user by email with an empty email.
     */
    public function testGetByEmailEmpty() {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Email is required.');

        $this->invokeMethod(self::$ssoModel, 'getUserByEmail', ['']);
    }

    /**
     * Multiple users found with the same email.
     */
    public function testGetByEmailDuplicatedResults() {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Multiple users found with the same email.');

        $this->invokeMethod(self::$ssoModel, 'getUserByEmail', ['duplicated@example.com']);
    }

    /**
     * Test getting a user by email with a non-existent email.
     */
    public function testGetByEmailNotFound() {
        $this->expectException(NotFoundException::class);

        $this->invokeMethod(self::$ssoModel, 'getUserByEmail', ['doesnotexist@example.com']);
    }

    /**
     * Get a user by its name.
     */
    public function testGetByName() {
        $user = self::$users['default'];
        $fetchedUser = $this->invokeMethod(self::$ssoModel, 'getUserByName', [$user['Name']]);

        $this->assertIsArray($fetchedUser);
        $this->assertArrayHasKey('UserID', $fetchedUser);
        $this->assertEquals($fetchedUser['UserID'], $user['UserID']);
    }

    /**
     * Cannot get user by name due to current configurations.
     */
    public function testGetByNameWithConfigNameUniqueFalse() {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Cannot get user by name due to current configurations.');

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
     * Test getting a user by name with an empty name argument.
     */
    public function testGetByNameEmpty() {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Name is required.');

        $this->invokeMethod(self::$ssoModel, 'getUserByName', ['']);
    }

    /**
     * Multiple users found with the same name.
     */
    public function testGetByNameDuplicatedResults() {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Multiple users found with the same name.');

        $this->invokeMethod(self::$ssoModel, 'getUserByName', ['duplicated']);
    }

    /**
     * Getting a user by a non-existent name should throw a not found exception.
     */
    public function testGetByNameNotFound() {
        $this->expectException(NotFoundException::class);
        $this->invokeMethod(self::$ssoModel, 'getUserByName', ['doesnotexist']);
    }


}
