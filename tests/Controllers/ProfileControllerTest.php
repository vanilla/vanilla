<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers;

use Garden\Password\VbulletinPassword;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ResponseException;
use Gdn_UserException;
use ProfileController;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\Fixtures\TestUploader;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Tests for the `ProfileController`
 */
class ProfileControllerTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    const REDIRECT_URL = "https://example.com/{name}?id={userID}";
    const OPT_SHOULD_REAUTHENTICATE = "shouldReauthenticate";
    const OPT_FORCE_REAUTHENTICATE = "forceReauthenticate";
    const OPT_USER_ID = "userID";

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->createUserFixtures();
    }

    /**
     * The user's profile should redirect if there is a basic URL in the redirection.
     */
    public function testProfileRedirect(): void
    {
        $this->runWithConfig(["Garden.Profile.RedirectUrl" => self::REDIRECT_URL], function () {
            $user = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY);
            $user = ArrayUtils::camelCase($user);

            /** @var ProfileController $r */
            $r = $this->bessy()->get("/profile/{$user["name"]}");
            $actual = $r->addDefinition("RedirectTo");
            $this->assertNotEmpty($actual);
            $expected = formatString(self::REDIRECT_URL, $user);
            $this->assertSame($expected, $actual);
        });
    }

    /**
     * Test that when a guest user navigates to the "/profile" page, an error is triggered.
     */
    public function testProfilePageGuestRedirect(): void
    {
        $this->runWithUser(function () {
            // In production, the failed permission check will then redirect to the login page.
            $this->expectException(\Gdn_UserException::class);
            $this->bessy()->get("/profile");
        }, \UserModel::GUEST_USER_ID);
    }

    /**
     * Provide test cases for private/banned profiles.
     */
    public function provideUsersPrivateProfile(): array
    {
        // banned, private, testWithAdmin,testRolePersonalView, privateBannedEnabled, exception
        return [
            ["member-private" => false, true, false, false, false, true],
            ["member-private-personalViewPermission" => false, true, false, true, false, false],
            ["private-banned" => true, true, false, false, false, true],
            ["private-banned-personalViewPermission" => true, true, false, true, false, false],
            ["private-banned-privateBannedEnabled" => true, true, false, false, true, true],
            ["private-banned-personalViewPermission-privateBannedEnabled" => true, true, false, true, true, false],
            ["no-changes" => false, false, false, false, false, false],
            ["banned" => true, false, false, false, false, false],
            ["banned-privateBannedEnabled" => true, false, false, false, true, true],
            ["banned-personalViewPermission-privateBannedEnabled" => true, false, false, true, true, false],
            ["private-personalViewPermission-privateBannedEnabled" => false, true, true, true, true, false],
            ["banned-private-personalViewPermission" => true, true, true, true, false, false],
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
        $this->userModel->saveAttribute($userID, ["Private" => $private]);
        if ($banned) {
            $this->api()->put("/users/{$userID}/ban", ["banned" => $banned]);
        }
        $user = $this->api()
            ->get("/users/$userID")
            ->getBody();
        $this->api()->patch("/roles/" . 8, [
            "permissions" => [
                [
                    "type" => "global",
                    "permissions" => [
                        "personalInfo.view" => $permPersonalView,
                    ],
                ],
            ],
        ]);
        $userTest = $testWithAdmin
            ? $this->createUserFixture(VanillaTestCase::ROLE_ADMIN)
            : $this->createUserFixture(VanillaTestCase::ROLE_MEMBER);
        $this->getSession()->start($userTest);

        try {
            $r = $this->runWithConfig(["Vanilla.BannedUsers.PrivateProfiles" => $privateBanned], function () use (
                $user
            ) {
                /** @var ProfileController $r */
                return $this->bessy()->get("/profile/{$user["name"]}");
            });
        } catch (\Gdn_UserException $e) {
            $this->assertEquals(ProfileController::PRIVATE_PROFILE, $e->getMessage());
        }

        if (!$controllerException) {
            $this->assertTrue(!empty($r->Data["Profile"]));
        }
    }

    /**
     * Test ProfileController::Invitations permissions.
     */
    public function testProfileInvitations(): void
    {
        $userMemberAData = [
            "Name" => "testuserA",
            "Email" => "testuserA@example.com",
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
        ];
        $userMemberBData = [
            "Name" => "testuserB",
            "Email" => "testuserb@example.com",
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
        ];
        $userMemberAID = $this->userModel->save($userMemberAData);
        $this->userModel->save($userMemberBData);

        $userAdmin = $this->userModel->getID($this->adminID, DATASET_TYPE_ARRAY);

        /** @var ProfileController $r */
        // As member user A, access user B's invitation page.
        \Gdn::session()->start($userMemberAID);
        try {
            $this->bessy()->get("/profile/invitations/p1/{$userMemberBData["Name"]}");
        } catch (\Gdn_UserException $ex) {
            $this->assertEquals(403, $ex->getCode());
        }
        // Switch to an admin user, user should be able to view invitation page.
        \Gdn::session()->start($userAdmin["UserID"]);
        $r = $this->bessy()->get("/profile/invitations/p1/{$userMemberBData["Name"]}");
        $this->assertNotEmpty($r->data("Profile"));
    }

    /**
     * The user's profile should redirect if there is a basic URL in the redirection.
     */
    public function testProfileRedirectOwn(): void
    {
        $this->runWithConfig(["Garden.Profile.RedirectUrl" => self::REDIRECT_URL], function () {
            \Gdn::session()->start($this->memberID);
            $user = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY);
            $user = ArrayUtils::camelCase($user);

            /** @var ProfileController $r */
            $r = $this->bessy()->get("/profile");
            $actual = $r->addDefinition("RedirectTo");
            $this->assertNotEmpty($actual);
            $expected = formatString(self::REDIRECT_URL, $user);
            $this->assertSame($expected, $actual);
        });
    }

    /**
     * Test changing ability to change a profile picture with only the profilePicture.edit permission.
     */
    public function testChangeProfilePicture(): void
    {
        $roleID = $this->roleID("Member");
        // If the user has the profilePicture.edit permission, they shouldn't need the profiles.edit permission.
        $this->api()->patch("/roles/{$roleID}/permissions", [
            ["permissions" => ["profilePicture.edit" => true, "profiles.edit" => false], "type" => "global"],
        ]);
        $session = $this->getSession();
        $session->start($this->memberID);
        $uploadForm = $this->bessy()
            ->getHtml("/profile/picture?userid={$this->memberID}")
            ->getInnerHtml();
        $this->assertStringContainsString("Upload New Picture", $uploadForm);
    }

    /**
     *  Test that you can upload profile picture for your profile.
     */
    public function testUploadProfilePicture(): void
    {
        $currentUserId = $this->api()->getUserID();
        $userData = $this->userModel->getID($currentUserId, DATASET_TYPE_ARRAY);
        $this->assertEmpty($userData["Photo"]);

        //Now upload a svg image for the user profile
        TestUploader::uploadFile("Avatar", PATH_ROOT . "/tests/fixtures/insightful.svg");
        $r = $this->bessy()->post("/profile/picture");
        $lastHtml = $this->bessy()->getLastHtml();
        $lastHtml->assertContainsString($r->Data["avatar"], "profile picture was not updated");
        $this->assertNotEmpty($r->Data["avatar"]);
        $userData = $this->userModel->getID($currentUserId, DATASET_TYPE_ARRAY);
        $this->assertNotEmpty($userData["Photo"]);
        $pictureName = basename($userData["Photo"]);
        $oldProfilePicture = str_replace($pictureName, "p" . $pictureName, $userData["Photo"]);
        $this->assertFileExists(PATH_UPLOADS . "/" . $oldProfilePicture);
        $this->assertEquals("svg", strtolower(pathinfo(PATH_UPLOADS . "/" . $oldProfilePicture, PATHINFO_EXTENSION)));

        // Now upload a jpg image for the user profile
        TestUploader::uploadFile("Avatar", PATH_ROOT . "/tests/fixtures/apple.jpg");
        $r = $this->bessy()->post("/profile/picture");
        $updatedUserData = $this->userModel->getID($currentUserId, DATASET_TYPE_ARRAY);

        // The old profile picture should be deleted
        $this->assertFileDoesNotExist(PATH_UPLOADS . "/" . $oldProfilePicture);
        $pictureName = basename($updatedUserData["Photo"]);
        $newProfilePicture = str_replace($pictureName, "p" . $pictureName, $updatedUserData["Photo"]);
        $this->assertFileExists(PATH_UPLOADS . "/" . $newProfilePicture);
        $this->assertEquals("jpg", strtolower(pathinfo(PATH_UPLOADS . "/" . $newProfilePicture, PATHINFO_EXTENSION)));
    }

    /**
     * Test the private profile config setting.
     */
    public function testPrivateProfile(): void
    {
        $privateUser = $this->createUser([
            "name" => "PrivateUser",
            "email" => "private_profile@test.com",
            "private" => true,
        ]);
        $this->runWithPermissions(
            function () use ($privateUser) {
                $this->expectException(\Gdn_UserException::class);
                $this->expectExceptionMessage(ProfileController::PRIVATE_PROFILE);
                $r = $this->bessy()->get(userUrl($privateUser));
            },
            ["profiles.view" => false, "personalInfo.view" => false]
        );
    }

    /**
     * Test the private profile config setting.
     */

    public function testPrivateProfileConfigSetting(): void
    {
        $this->runWithConfig(["Garden.Profile.Public" => false], function () {
            $this->getSession()->end();
            $this->expectException(\Gdn_UserException::class);
            $this->expectExceptionMessage(ProfileController::PRIVATE_PROFILE);
            $profileController = $this->container()->get(ProfileController::class);
            $profileController->getUserInfo("", "", $this->memberID);
        });
    }

    /**
     * Test that unsigned guest users with no permissions to view profile receive permission error.
     *
     * @return void
     */
    public function testGuestUsersWithOutProfileViewPermissionReceivePermissionError(): void
    {
        $this->getSession()->end();
        $this->expectException(\Gdn_UserException::class);
        $this->expectExceptionMessage("You don't have permission to do that.");
        $r = $this->bessy()->get("/profile/{$this->memberID}");
    }

    /**
     * Test a basic flow of `/profile/edit`.
     */
    public function testProfileEdit(): void
    {
        $this->runWithConfig([ProfileFieldModel::CONFIG_FEATURE_FLAG => false], function () {
            $userID = $this->createUserFixture(self::ROLE_MEMBER);
            $this->getSession()->start($userID);

            $page = $this->bessy()->getHtml("/profile/edit");
            $data = $page->getFormValues();

            // By default, there is not much we are allowed to edit.
            $r = $this->bessy()->postBack(["ShowEmail" => true]);

            $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
            $this->assertTrue((bool) $user["ShowEmail"], "The user was not updated.");
        });
    }

    /**
     * Run through a profile/edit loop with and without re-authentication.
     *
     * @param array $fields
     * @param array $options
     */
    protected function doProfileEditSteps(array $fields = [], $options = [])
    {
        $options += [
            self::OPT_SHOULD_REAUTHENTICATE => true, // should there be a re-authentication page during the steps?
            self::OPT_FORCE_REAUTHENTICATE => true, // force authentication timeout
            self::OPT_USER_ID => null, // edit a specific user ID
        ];

        $userID = $options[self::OPT_USER_ID] ?? $this->getSession()->UserID;
        if (empty($options[self::OPT_USER_ID])) {
            $path = "/profile/edit";
        } else {
            $path = "/profile/edit/$userID/x";
        }

        $page = $this->bessy()->getHtml($path);

        if ($options[self::OPT_FORCE_REAUTHENTICATE]) {
            \Gdn::authenticator()
                ->identity()
                ->setAuthTime(CurrentTimeStamp::get() - \Gdn_Controller::REAUTH_TIMEOUT - 100);
        }
        $page = $this->bessy()->postBackHtml($fields);

        if ($options[self::OPT_SHOULD_REAUTHENTICATE]) {
            $page->assertFormInput("AuthenticatePassword");

            // Make some bad attempts first.
            try {
                $page = $this->bessy()->postBackHtml(["AuthenticatePassword" => ""]);
                $this->fail("There should have been an error.");
            } catch (ValidationException $ex) {
                $this->assertStringContainsString("Password is required", $ex->getMessage());
            }

            try {
                $page = $this->bessy()->postBackHtml(["AuthenticatePassword" => "xyz"]);
                $this->fail("There should have been an error.");
            } catch (ValidationException $ex) {
                $this->assertStringContainsString("The password you entered was incorrect", $ex->getMessage());
            }

            // See password value as set in SiteTestTrait's createUserFixture() function.
            $this->bessy()->postBack(["AuthenticatePassword" => "test15!AVeryS3cUR3pa55W0rd"]);
        } else {
            $page->assertNoFormInput("AuthenticatePassword");
        }
        $pendingFields = ["Email"];
        // Assert all the fields.
        $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
        foreach (array_diff_key($fields, array_flip($pendingFields)) as $name => $value) {
            $this->assertEquals($user[$name], $value, "User.$name was not updated.");
        }
    }

    /**
     * Test a flow where a user must re-authenticate using their password.
     */
    public function testProfileEditReauthenticate(): void
    {
        $this->runWithConfig([ProfileFieldModel::CONFIG_FEATURE_FLAG => false], function () {
            $userID = $this->createUserFixture(self::ROLE_MEMBER);
            $this->getSession()->start($userID);

            $this->doProfileEditSteps(["ShowEmail" => true]);
        });
    }

    /**
     * When changing email there should be a forced re-authentication.
     *
     * @see https://github.com/vanilla/vanilla-patches/issues/634
     */
    public function testProfileEditReauthenticateOnEmailChange(): void
    {
        $this->runWithConfig([ProfileFieldModel::CONFIG_FEATURE_FLAG => false], function () {
            $userID = $this->createUserFixture(self::ROLE_MEMBER);
            $this->getSession()->start($userID);

            $this->doProfileEditSteps(["Email" => "changed@example.com"], [self::OPT_FORCE_REAUTHENTICATE => false]);

            $page = $this->bessy()->getHtml("/profile/edit");
        });
    }

    /**
     * There was a bug being caused by a mismatch in password hashes.
     *
     * @see https://higherlogic.atlassian.net/browse/VNLA-728
     */
    public function testReauthenticatePasswordBug(): void
    {
        $this->runWithConfig(
            [ProfileFieldModel::CONFIG_FEATURE_FLAG => false, "Garden.Registration.Method" => "Connect"],
            function () {
                $userID = $this->createUserFixture(self::ROLE_MEMBER);
                $pw = new VbulletinPassword();
                $hash = $pw->hash("test");
                $this->userModel->setField($userID, ["Password" => $hash, "HashMethod" => "vbulletin"]);

                $this->getSession()->start($userID);

                $this->doProfileEditSteps(["ShowEmail" => true], [self::OPT_SHOULD_REAUTHENTICATE => false]);
            }
        );
    }

    /**
     * Test the above bug, but with a generic config.
     *
     * @see https://higherlogic.atlassian.net/browse/VNLA-728
     */
    public function testReauthenticateDifferentPasswordHash(): void
    {
        $this->runWithConfig([ProfileFieldModel::CONFIG_FEATURE_FLAG => false], function () {
            $userID = $this->createUserFixture(self::ROLE_MEMBER);
            $pw = new VbulletinPassword();
            // See password value as set in SiteTestTrait's createUserFixture() function.
            $hash = $pw->hash("test15!AVeryS3cUR3pa55W0rd");
            $this->userModel->setField($userID, ["Password" => $hash, "HashMethod" => "vbulletin"]);

            $this->getSession()->start($userID);

            $this->doProfileEditSteps(["ShowEmail" => true]);
        });
    }

    /**
     * Make sure the re-authorization steps work when an admin is editing another user's profile.
     *
     * Note: There is some indication in the code that we did not want to re-authenticate administrators at all.
     * When reading the code though it seems as though a check to always re-authenticate was put in here:
     * https://github.com/vanilla/vanilla-cloud/blob/master/applications/dashboard/controllers/class.profilecontroller.php#L454.
     *
     * If at some point we discover this shouldn't be the case then this test can be changed. However, it seems better
     * to force re-authentication for users with such a high privilege.
     */
    public function testReauthenticateWithDifferentUser(): void
    {
        $this->runWithConfig([ProfileFieldModel::CONFIG_FEATURE_FLAG => false], function () {
            $adminID = $this->createUserFixture(self::ROLE_ADMIN);
            $memberID = $this->createUserFixture(self::ROLE_MEMBER);
            // Change the member's password to make sure it's the admin's password we are checking.
            $this->userModel->setField($memberID, ["Password" => "foo"]);

            $this->getSession()->start($adminID);

            $this->doProfileEditSteps(["ShowEmail" => true], [self::OPT_USER_ID => $memberID]);
        });
    }

    /**
     * Test the user's profile url when the user has a `/` in its username.
     */
    public function testUserWithSlashInName()
    {
        $user = $this->createUser([
            "name" => "IHave/Slash",
        ]);

        $r = $this->bessy()->getJsonData($user["url"]);
        $this->assertEquals(200, $r->getStatus());
    }

    /**
     * Test redirections to the Edit Profile page.
     *
     * @dataProvider providerEditProfile
     *
     * @return void
     */
    public function testEditProfileRedirect(
        $urlStructure,
        $expectedRedirectedUrl,
        $expectedStatusCode,
        $expectedMessage
    ): void {
        $this->runWithConfig(
            ["Garden.Registration.NameUnique" => false, "Feature.CustomProfileFields.Enabled" => true],
            function () use ($urlStructure, $expectedRedirectedUrl, $expectedStatusCode, $expectedMessage) {
                $user = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY);
                $user = ArrayUtils::camelCase($user);

                $urlStructure = str_replace(
                    ["##userID##", "##userName##"],
                    [$user["userID"], $user["name"]],
                    $urlStructure
                );

                $expectedRedirectedUrl = str_replace(
                    ["##userID##", "##userName##"],
                    [$user["userID"], $user["name"]],
                    $expectedRedirectedUrl
                );

                try {
                    $this->bessy()->get($urlStructure);
                    // We shouldn't reach what follows.
                    $this->fail();
                } catch (ResponseException $exception) {
                    $responseMetas = $exception->getResponse()->getMetaArray();
                    $this->assertEquals($expectedStatusCode, $responseMetas["status"]);
                    $this->assertEquals($expectedRedirectedUrl, $responseMetas["HTTP_LOCATION"]);

                    try {
                        $x = $this->bessy()->getHtml($responseMetas["HTTP_LOCATION"]);
                        // The user was found.
                        $this->assertEquals($expectedMessage, $exception->getMessage());
                    } catch (Gdn_UserException $exception) {
                        $this->assertEquals($expectedMessage, $exception->getMessage());
                    }
                }
            }
        );
    }

    /**
     * Data provider for testEditProfileRedirect()
     *
     * @return array
     */
    public function providerEditProfile()
    {
        return [
            [
                "/profile/edit/##userID##/##userName##",
                "https://vanilla.test/profilecontrollertest/profile/account-privacy/##userID##",
                302,
                "Found",
            ],
            [
                "/profile/edit/##userID##",
                "https://vanilla.test/profilecontrollertest/profile/account-privacy/##userID##",
                302,
                "Found",
            ],
            [
                "/profile/edit/##userName##",
                "https://vanilla.test/profilecontrollertest/profile/account-privacy/##userName##",
                302,
                "Found",
            ],
            [
                "/profile/edit/##userName##/##userID##",
                "https://vanilla.test/profilecontrollertest/profile/account-privacy/##userName##",
                302,
                "Found",
            ],
        ];
    }
}
