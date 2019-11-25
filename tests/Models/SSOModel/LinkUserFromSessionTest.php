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
 * Class LinkUserFromSessionTest.
 */
class LinkUserFromSessionTest extends SharedBootstrapTestCase {
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

        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$users['default']['UserID']);
    }

    /**
     * @inheritdoc
     */
    public function tearDown(): void {
        /** @var \Gdn_SQLDriver $driver */
        $driver = self::container()->get('SqlDriver');
        $driver->truncate('UserAuthentication');

        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->end();

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
     * Link a user using the current session.
     */
    public function testLinkUser() {
        $user = self::$users['default'];

        $linkedUser = self::$ssoModel->linkUserFromSession(
            $this->createSSOData($user['Name'], $user['Email'])
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
     * Try to link a user using the session while there's no user signed in.
     *
     * @expectedException \Garden\Web\Exception\ForbiddenException
     * @expectedExceptionMessage Cannot link user from session while not signed in.
     */
    public function testLinkUserWNoSession() {
        $user = self::$users['default'];

        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->end();

        self::$ssoModel->linkUserFromSession(
            $this->createSSOData($user['Name'], $user['Email'])
        );
    }
}
