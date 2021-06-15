<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers;

use Vanilla\Utility\ArrayUtils;
use VanillaTests\SiteTestCase;
use VanillaTests\VanillaTestCase;

/**
 * Tests for the `ProfileController`
 */
class ProfileControllerTest extends SiteTestCase {
    const REDIRECT_URL = 'https://example.com/{name}?id={userID}';

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();
        $this->createUserFixtures();
    }

    /**
     * The user's profile should redirect if there is a basic URL in the redirection.
     */
    public function testProfileRedirect(): void {
        $this->runWithConfig(['Garden.Profile.RedirectUrl' => self::REDIRECT_URL], function () {
            $user = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY);
            $user = ArrayUtils::camelCase($user);

            /** @var \ProfileController $r */
            $r = $this->bessy()->get("/profile/{$user['name']}");
            $actual = $r->addDefinition('RedirectTo');
            $this->assertNotEmpty($actual);
            $expected = formatString(self::REDIRECT_URL, $user);
            $this->assertSame($expected, $actual);
        });
    }

    /**
     * Provide test cases for private/banned profiles.
     */
    public function provideUsersPrivateProfile(): array {
        // banned, private, testWithAdmin,testRolePersonalView, privateBannedEnabled, exception
        return [
            ['member-private' => false, true, false, false, false, true],
            ['member-private-personalViewPermission' => false, true, false, true, false, false],
            ['private-banned' => true, true, false, false, false, true],
            ['private-banned-personalViewPermission' => true, true, false, true, false, false],
            ['private-banned-privateBannedEnabled' => true, true, false, false, true, true],
            ['private-banned-personalViewPermission-privateBannedEnabled' => true, true, false, true, true, false],
            ['no-changes' => false, false, false, false, false, false],
            ['banned' => true, false, false, false, false, false],
            ['banned-privateBannedEnabled' => true, false, false, false, true, true],
            ['banned-personalViewPermission-privateBannedEnabled' => true, false, false, true, true, false],
            ['private-personalViewPermission-privateBannedEnabled' => false, true, true, true, true, false],
            ['banned-private-personalViewPermission' => true, true, true, true, false, false]
        ];
    }

    /**
     * Test viewing a profile of a banned / private user.
     *
     * @param bool $banned
     * @param bool $private
     * @param bool $testWithAdmin
     * @param bool $permPersonalView
     * @param bool $privateBanned
     * @param bool $controllerException
     * @dataProvider provideUsersPrivateProfile
     */
    public function testProfileViewPrivateUser(
        bool $banned,
        bool $private,
        bool $testWithAdmin,
        bool $permPersonalView,
        bool $privateBanned,
        bool $controllerException
    ): void {
        $userID = $this->createUserFixture(VanillaTestCase::ROLE_MEMBER);
        $this->userModel->saveAttribute($userID, ['Private' => $private]);
        if ($banned) {
            $this->api()->put("/users/{$userID}/ban", ['banned' => $banned]);
        }
        $user = $this->api()->get("/users/$userID")->getBody();
        $this->api()->patch('/roles/' . 8, [
            'permissions' => [
                [
                    'type' => 'global',
                    'permissions' => [
                        'personalInfo.view' => $permPersonalView
                    ]
                ]
            ]
        ]);
        $userTest = $testWithAdmin ? $this->createUserFixture(VanillaTestCase::ROLE_ADMIN) : $this->createUserFixture(VanillaTestCase::ROLE_MEMBER);
        $this->getSession()->start($userTest);

        try {
            $r = $this->runWithConfig(['Vanilla.BannedUsers.PrivateProfiles' => $privateBanned], function () use ($user) {
                /** @var \ProfileController $r */
                return $this->bessy()->get("/profile/{$user['name']}");
            });
        } catch (\Gdn_UserException $e) {
            $this->assertEquals(\ProfileController::PRIVATE_PROFILE, $e->getMessage());
        }

        if (!$controllerException) {
            $this->assertTrue(!empty($r->Data['Profile']));
        }
    }

    /**
     * Test ProfileController::Invitations permissions.
     */
    public function testProfileInvitations(): void {
        $userMemberAData = [
            "Name" => "testuserA",
            "Email" => "testuserA@example.com",
            "Password" => "vanilla"
        ];
        $userMemberBData = [
            "Name" => "testuserB",
            "Email" => "testuserb@example.com",
            "Password" => "vanilla"
        ];
        $userMemberAID = $this->userModel->save($userMemberAData);
        $this->userModel->save($userMemberBData);

        $userAdmin = $this->userModel->getID($this->adminID, DATASET_TYPE_ARRAY);

        /** @var \ProfileController $r */
        // As member user A, access user B's invitation page.
        \Gdn::session()->start($userMemberAID);
        try {
            $this->bessy()->get("/profile/invitations/p1/{$userMemberBData['Name']}");
        } catch (\Gdn_UserException $ex) {
            $this->assertEquals(403, $ex->getCode());
        }
        // Switch to an admin user, user should be able to view invitation page.
        \Gdn::session()->start($userAdmin['UserID']);
        $r = $this->bessy()->get("/profile/invitations/p1/{$userMemberBData['Name']}");
        $this->assertNotEmpty($r->data('Profile'));
    }

    /**
     * The user's profile should redirect if there is a basic URL in the redirection.
     */
    public function testProfileRedirectOwn(): void {
        $this->runWithConfig(['Garden.Profile.RedirectUrl' => self::REDIRECT_URL], function () {
            \Gdn::session()->start($this->memberID);
            $user = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY);
            $user = ArrayUtils::camelCase($user);

            /** @var \ProfileController $r */
            $r = $this->bessy()->get("/profile");
            $actual = $r->addDefinition('RedirectTo');
            $this->assertNotEmpty($actual);
            $expected = formatString(self::REDIRECT_URL, $user);
            $this->assertSame($expected, $actual);
        });
    }

    /**
     * Test the /profile/preference endpoint that it saves only safe keys and values.
     *
     * @param array $preferences Test preference data.
     * @param string $expected Expected outcomes from test data.
     * @dataProvider providePreferences Provide scenarios of input.
     */
    public function testPreference(array $preferences, string $expected): void {
        \Gdn::session()->start($this->memberID);
        $user = $this->getSession()->User;
        // Pass bad data.
        if ($expected === 'malformatted') {
            $this->expectExceptionMessage('Improperly formatted Preference.');
            $this->bessy()->post('/profile/preference', $preferences);
        } else {
            // Pass good data.
            $this->bessy()->post('/profile/preference', $preferences);
            $user = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY);

            // Verify output as a moderator.
            \Gdn::session()->end();
            \Gdn::session()->start($this->moderatorID);
            $xmlOptions = ['deliveryMethod' => DELIVERY_METHOD_XML, 'deliveryType' => DELIVERY_TYPE_DATA];
            $profileUrl = '/profile/'.$user['UserID'].'/'.$user['Name'];
            $view = $this->bessy()->getHtml($profileUrl, [], $xmlOptions);
            $view->assertContainsString($expected);
        }
    }

    /**
     * Provide test preference data.
     *
     * @return array
     */
    public function providePreferences() {
        $r = [
            [['a:b' => 'c'], 'malformatted'],
            [['a' => 'b:c'], 'malformatted'],
            [['a.b' => 'c'], '<a.b>c</a.b>']
        ];
        return $r;
    }
}
