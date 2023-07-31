<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Gdn;
use UserMetaModel;
use Vanilla\Dashboard\Models\ActivityService;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the /notification-preferences api endpoints.
 */
class NotificationPreferencesApiControllerTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait;

    /** @var ActivityService */
    private $activityService;

    /** @var UserNotificationPreferencesModel */
    private $userPrefsModel;

    /** @var UserMetaModel */
    protected $userMetaModel;

    /** @var string */
    private $baseUrl = "/notification-preferences";

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->activityService = Gdn::getContainer()->get(ActivityService::class);
        $this->userPrefsModel = Gdn::getContainer()->get(UserNotificationPreferencesModel::class);
        $this->userMetaModel = Gdn::getContainer()->get(UserMetaModel::class);
    }

    /**
     * Test that the /notification-preferences/schema endpoint delivers a schema.
     *
     * @return void
     */
    public function testGetSchema(): void
    {
        $schema = $this->api()
            ->get("/notification-preferences/schema")
            ->getBody();
        $this->assertNotEmpty($schema["properties"]["notifications"]);
        $this->assertTrue(
            isset(
                $schema["properties"]["notifications"]["properties"]["followedPosts"]["properties"][
                    "DiscussionComment"
                ]["properties"]["email"]
            )
        );
    }

    /**
     * Test that the "email" field is filtered out of the schema if emails are disabled globally.
     *
     * @return void
     */
    public function testGetSchemaEmailDisabled(): void
    {
        $this->runWithConfig(["Garden.Email.Disabled" => true], function () {
            $schema = $this->api()
                ->get("/notification-preferences/schema")
                ->getBody();
            $this->assertNotEmpty($schema["properties"]["notifications"]);

            // There should be no email field.
            $this->assertTrue(
                !isset(
                    $schema["properties"]["notifications"]["properties"]["followedPosts"]["properties"][
                        "DiscussionComment"
                    ]["properties"]["email"]
                )
            );
        });
    }

    /**
     * Test that the "email" field is filtered out of the schema if a session user doesn't have the email.view permission.
     *
     * @return void
     */
    public function testGetSchemaNoEmailViewPermission(): void
    {
        $noEmailRole = $this->createRole([
            "permissions" => [
                [
                    "permissions" => [
                        "email.view" => false,
                        "session.valid" => true,
                    ],
                    "type" => "global",
                ],
            ],
        ]);
        $user = $this->createUser(["roleID" => [$noEmailRole["roleID"]]]);
        $this->api()->setUserID($user["userID"]);

        $schema = $this->api()
            ->get("/notification-preferences/schema", ["userID" => $user["userID"]])
            ->getBody();
        $this->assertNotEmpty($schema["properties"]["notifications"]);

        // There should be no email field.
        $this->assertTrue(
            !isset(
                $schema["properties"]["notifications"]["properties"]["followedPosts"]["properties"][
                    "DiscussionComment"
                ]["properties"]["email"]
            )
        );
    }

    /**
     * Test that the "email" field is filtered out of the schema if a user (who is not the session user)
     * doesn't have the email.view permission.
     *
     * @return void
     */
    public function testGetSchemaNoEmailViewNonSessionUser(): void
    {
        $noEmailRole = $this->createRole(["Garden.Email.View" => 0]);
        $user = $this->createUser(["roleID" => [$noEmailRole["roleID"]]]);

        $schema = $this->api()
            ->get("/notification-preferences/schema", ["userID" => $user["userID"]])
            ->getBody();
        $this->assertNotEmpty($schema["properties"]["notifications"]);

        // There should be no email field.
        $this->assertTrue(
            !isset(
                $schema["properties"]["notifications"]["properties"]["followedPosts"]["properties"][
                    "DiscussionComment"
                ]["properties"]["email"]
            )
        );
    }

    /**
     * Test that a field does not appear in the user schema when it has been explicitly disabled in the config.
     *
     * @return void
     */
    public function testGetSchemaSpecificPreferenceDisabled(): void
    {
        $this->runWithConfig(
            ["Garden.Preferences.Disabled.DiscussionComment" => 1, "Garden.Preferences.Disabled.ActivityComment" => 0],
            function () {
                $schema = $this->api()
                    ->get("/notification-preferences/schema")
                    ->getBody();
                $this->assertNotEmpty($schema["properties"]["notifications"]);

                // The DiscussionComment activity type shouldn't be there.
                $this->assertTrue(
                    !isset(
                        $schema["properties"]["notifications"]["properties"]["followedPosts"]["properties"][
                            "DiscussionComment"
                        ]
                    )
                );

                // But the ActivityComment should.
                $this->assertTrue(
                    isset(
                        $schema["properties"]["notifications"]["properties"]["myAccount"]["properties"][
                            "ActivityComment"
                        ]
                    )
                );
            }
        );
    }

    /**
     * Test that preferences are filtered out of the schema based on site config settings.
     *
     * @return void
     */
    public function testGetPreferenceSchemaConfigFilter(): void
    {
        // The "Applicant" preference should be included when the registration method is "Approval"
        $this->runWithConfig(["Garden.Registration.Method" => "Approval"], function () {
            $schema = $this->api()
                ->get("/notification-preferences/schema")
                ->getBody();
            $this->assertTrue(
                isset($schema["properties"]["notifications"]["properties"]["communityTask"]["properties"]["Applicant"])
            );
        });

        // The "Applicant" preference should NOT be included when the registration method is something other than "Approval"
        $this->runWithConfig(["Garden.Registration.Method" => "Basic"], function () {
            $schema = $this->api()
                ->get("/notification-preferences/schema")
                ->getBody();
            $this->assertTrue(
                !isset($schema["properties"]["notifications"]["properties"]["myAccount"]["properties"]["Applicant"])
            );
        });
    }

    /**
     * Test that preferences are filtered out of the schema based on the user's permissions.
     *
     * @return void
     */
    public function testGetPreferenceSchemaPermissionFilter(): void
    {
        $this->runWithConfig(["Garden.Registration.Method" => "Approval"], function () {
            // The "Applicant" preference should appear if the user has the 'site.manage' permission.
            $schema = $this->api()
                ->get("/notification-preferences/schema")
                ->getBody();
            $this->assertTrue(
                isset($schema["properties"]["notifications"]["properties"]["communityTask"]["properties"]["Applicant"])
            );

            // And it shouldn't appear if you don't have it.
            $memberUser = $this->createUser();
            $this->api()->setUserID($memberUser["userID"]);
            $schema = $this->api()
                ->get("/notification-preferences/schema")
                ->getBody();
            $this->assertTrue(
                !isset($schema["properties"]["notifications"]["properties"]["myAccount"]["properties"]["Applicant"])
            );
        });
    }

    /**
     * Test that the email digest preference's appearance in the schema respects the global email digest setting.
     *
     * @return void
     */
    public function testEmailDigestPreference(): void
    {
        // The "Email Digest" preference should be included when the digest is enabled globally.
        $this->runWithConfig(["Garden.Digest.Enabled" => true, "Feature.Digest.Enabled" => true], function () {
            $schema = $this->api()
                ->get("/notification-preferences/schema")
                ->getBody();
            $this->assertTrue(isset($schema["properties"]["emailDigest"]["properties"]["DigestEnabled"]));
        });

        // Neither the "Email Digest", nor its group should be included when the digest is disabled globally.
        $this->runWithConfig(["Garden.Digest.Enabled" => false], function () {
            $schema = $this->api()
                ->get("/notification-preferences/schema")
                ->getBody();
            $this->assertTrue(!isset($schema["properties"]["emailDigest"]));
        });
    }

    /**
     * Test that the email digest preference does not appear in the default preferences schema or the default preferences.
     *
     * @return void
     */
    public function testEmailDigestDefaultPreferences(): void
    {
        $this->runWithConfig(["Garden.Digest.Enabled" => true, "Feature.Digest.Enabled" => true], function () {
            $schema = $this->api()
                ->get("/notification-preferences/schema", ["schemaType" => "defaults"])
                ->getBody();
            $this->assertTrue(!isset($schema["properties"]["emailDigest"]["properties"]["DigestEnabled"]));
            $this->assertTrue(!isset($schema["properties"]["emailDigest"]));

            $defaultPreferences = $this->api()
                ->get("/notification-preferences/defaults")
                ->getBody();
            $this->assertTrue(!isset($defaultPreferences["DigestEnabled"]));
        });
    }

    /**
     * Test getting the schema for site-wide default notification preference settings.
     *
     * @return void
     */
    public function testGetDefaultsSchema(): void
    {
        $schema = $this->api()->get("/notification-preferences/schema", ["schemaType" => "defaults"]);
        // The default schema should be a lot like the user schema, except we also have this "disabled" field.
        $this->assertTrue(
            isset(
                $schema["properties"]["notifications"]["properties"]["followedPosts"]["properties"][
                    "DiscussionComment"
                ]["properties"]["disabled"]
            )
        );
    }

    /**
     * Test that the GET /notification-preferences/{userID} endpoint returns a user's preferences.
     *
     * @return void
     */
    public function testGetUserPreferences(): void
    {
        $user = $this->createUser();
        $activityPreferences = $this->activityService->getAllPreferences();
        $prefsToSave = [];
        $expected = [];
        foreach ($activityPreferences as $preference) {
            $prefsToSave["Email." . $preference] = 1;
            $prefsToSave["Popup." . $preference] = 1;

            $expected[$preference]["email"] = 1;
            $expected[$preference]["popup"] = 1;
        }
        $this->userPrefsModel->save($user["userID"], $prefsToSave);
        $retrievedPrefs = $this->api()
            ->get("{$this->baseUrl}/{$user["userID"]}")
            ->getBody();
        $this->assertEqualsCanonicalizing($expected, $retrievedPrefs);
    }

    /**
     * Test that a user cannot view another user's preferences without the users.edit permission.
     *
     * @return void
     */
    public function testGetPreferencesNoUserEdit()
    {
        $memberUser = $this->createUser();
        $someOtherMemberUser = $this->createUser();
        $this->api()->setUserID($memberUser["userID"]);

        $this->expectExceptionCode(403);
        $this->api()->get("/notification-preferences/{$someOtherMemberUser["userID"]}");
    }

    /**
     * Test patching a user's notification preference.
     *
     * @return void
     */
    public function testPatchSinglePreference(): void
    {
        $user = $this->createUser();
        $existingPrefs = $this->userPrefsModel->getUserPrefs($user["userID"]);
        $this->assertSame(0, $existingPrefs["Email.DiscussionComment"]);
        $this->api()->patch("{$this->baseUrl}/{$user["userID"]}", ["DiscussionComment" => ["email" => true]]);
        $updatedPrefs = $this->api()
            ->get("{$this->baseUrl}/{$user["userID"]}")
            ->getBody();
        $this->assertSame(true, $updatedPrefs["DiscussionComment"]["email"]);
        // Verify the preferences have been saved to both the userMeta table and the user table.
        $userData = $this->userModel->getID($user["userID"]);
        $prefFromUserTable = $userData->Preferences;
        $this->assertSame(1, $prefFromUserTable["Email.DiscussionComment"]);
        $prefFromMetaTable = $this->userMetaModel->getUserMeta($user["userID"], "Preferences.Email.DiscussionComment");
        $this->assertSame("1", $prefFromMetaTable["Preferences.Email.DiscussionComment"]);
    }

    /**
     * Test that trying to patch an activity type that doesn't exist throws a 404 error.
     *
     * @return void
     */
    public function testNonExistentActivityType(): void
    {
        $user = $this->createUser();
        $this->api()->setUserID($user["userID"]);
        $this->expectExceptionCode(404);
        $this->api()->patch("/notification-preferences/{$user["userID"]}", ["NotAnActivityType" => ["email" => 1]]);
    }

    /**
     * Test that patching a preference with a notification type that isn't "Email" or "Popup" throws a 404 error.
     *
     * @return void
     */
    public function testNonexistentNotificationMethod(): void
    {
        $user = $this->createUser();
        $this->api()->setUserID($user["userID"]);
        $this->expectExceptionCode(404);
        $this->api()->patch("/notification-preferences/{$user["userID"]}", ["DiscussionComment" => ["Eeeeemail" => 1]]);
    }

    /**
     * Test that patching a user's notification preferences without the "users.edit" permission throws a 403 error.
     *
     * @return void
     */
    public function testPatchWithoutUsersEditPermission(): void
    {
        $memberUser = $this->createUser();
        $someOtherMemberUser = $this->createUser();
        $this->api()->setUserID($memberUser["userID"]);

        $this->expectExceptionCode(403);
        $this->api()->get("/notification-preferences/{$someOtherMemberUser["userID"]}", [
            "DiscussionComment" => ["email" => 1],
        ]);
    }

    /**
     * Test that patching the preferences of a user with a higher rank throws a 403 error.
     *
     * @return void
     */
    public function testPatchWithLowerRank(): void
    {
        $modUser = $this->createUser(["roleID" => [\RoleModel::MOD_ID]]);
        $adminUser = $this->createUser(["roleID" => [\RoleModel::ADMIN_ID]]);

        $this->api()->setUserID($modUser["userID"]);
        $this->expectExceptionCode(403);
        $this->api()->get("/notification-preferences/{$adminUser["userID"]}", [
            "DiscussionComment" => ["email" => 1],
        ]);
    }

    /**
     * Test getting a site's default user notification preference settings.
     *
     * @return void
     */
    public function testGetDefaults(): void
    {
        $defaults = $this->api()
            ->get("notification-preferences/defaults")
            ->getBody();
        foreach ($defaults as $key => $val) {
            $this->assertTrue(in_array($key, $this->activityService->getAllPreferences()));
            $this->assertArrayHasKey("disabled", $val);
            $this->assertArrayHasKey("email", $val);
            $this->assertArrayHasKey("popup", $val);
        }
    }

    /**
     * Test updating a site's default user notification preference settings.
     *
     * @return void
     */
    public function testPatchDefaults(): void
    {
        $patch = [
            "ActivityComment" => [
                "disabled" => true,
            ],
            "WallComment" => [
                "popup" => false,
            ],
            "DiscussionComment" => [
                "email" => true,
            ],
        ];

        $this->api()->patch("notification-preferences/defaults", $patch);

        $patchedDefaults = $this->api()
            ->get("notification-preferences/defaults")
            ->getBody();

        $this->assertSame(true, $patchedDefaults["ActivityComment"]["disabled"]);
        $this->assertSame(false, $patchedDefaults["WallComment"]["popup"]);
        $this->assertSame(true, $patchedDefaults["DiscussionComment"]["email"]);
    }

    /**
     * Test updating a default user notification preference setting with an invalid activity type.
     *
     * @return void
     */
    public function testPatchDefaultsInvalidPreference(): void
    {
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage("'Invalid' is not a valid preference.");
        $this->api()->patch("notification-preferences/defaults", ["Invalid" => ["email" => true]]);
    }

    /**
     * Test updating a default user notification preference setting with an invalid notification method.
     *
     * @return void
     */
    public function testPatchDefaultsInvalidMethod(): void
    {
        $this->expectExceptionCode(404);
        $this->api()->patch("notification-preferences/defaults", ["DiscussionComment" => ["snail-mail" => true]]);
    }

    /**
     * Test that trying to patch default notification preference settings without the 'site.manage' permisison throws
     * an error.
     *
     * @return void
     */
    public function testPatchDefaultsAsNonAdmin(): void
    {
        $modUser = $this->createUser(["roleID" => [\RoleModel::MOD_ID]]);
        $this->api()->setUserID($modUser["userID"]);
        $this->expectExceptionCode(403);
        $this->api()->patch("notification-preferences/defaults", ["DiscussionComment" => ["email" => true]]);
    }

    /**
     * Test that trying to set the default preferences for an activity that doesn't allow it will throw the appropriate error.
     *
     * @return void
     */
    public function testPatchForbiddenDefaultPreference(): void
    {
        $this->expectExceptionMessage("You cannot set a default preference for the EmailDigest activity.");
        $this->runWithConfig(["Garden.Digest.Enabled" => true, "Feature.Digest.Enabled" => true], function () {
            $this->api()->patch("notification-preferences/defaults", ["DigestEnabled" => ["email" => true]]);
        });
    }

    /**
     * Test patching a preference that is tied to more than one activitity.
     *
     * @return void
     */
    public function testSettingPreferenceAssociatedWithMultipleActivities(): void
    {
        $user = $this->createUser();
        $this->api()->setUserID($user["userID"]);
        $initialPreferences = $this->api()
            ->get("notification-preferences/{$user["userID"]}")
            ->getBody();
        $newMentionPreferences = [
            "popup" => !$initialPreferences["Mention"]["popup"],
            "email" => !$initialPreferences["Mention"]["email"],
        ];
        $this->api()->patch("notification-preferences/{$user["userID"]}", ["Mention" => $newMentionPreferences]);

        $updatedPreferences = $this->api()
            ->get("notification-preferences/{$user["userID"]}")
            ->getBody();
        $this->assertSame($newMentionPreferences["popup"], $updatedPreferences["Mention"]["popup"]);
        $this->assertSame($newMentionPreferences["email"], $updatedPreferences["Mention"]["email"]);
    }

    /**
     * Test patching a default preference that is tied to more than one activity.
     *
     * @return void
     */
    public function testSettingDefaultPreferenceAssociatedWithMultipleActivities(): void
    {
        $initialDefaults = $this->api()
            ->get("notification-preferences/defaults")
            ->getBody();
        $newMentionDefaults = [
            "popup" => !$initialDefaults["Mention"]["popup"],
            "email" => !$initialDefaults["Mention"]["email"],
        ];
        $this->api()->patch("notification-preferences/defaults", ["Mention" => $newMentionDefaults]);

        $updatedDefaults = $this->api()
            ->get("notification-preferences/defaults")
            ->getBody();
        $this->assertSame($newMentionDefaults["popup"], $updatedDefaults["Mention"]["popup"]);
        $this->assertSame($newMentionDefaults["email"], $updatedDefaults["Mention"]["email"]);
    }

    /**
     * Test that a new user's preferences match the default preferences.
     *
     * @return void
     */
    public function testNewUserPrefsMatchDefaults(): void
    {
        $newUser = $this->createUser();
        $defaultPrefs = $this->api()
            ->get("/notification-preferences/defaults")
            ->getBody();
        $userPrefs = $this->api()
            ->get("/notification-preferences/{$newUser["userID"]}")
            ->getBody();
        foreach ($userPrefs as $prefName => $pref) {
            if (isset($defaultPrefs[$prefName])) {
                // There are some preferences for which a default setting is not allowed.
                $this->assertSame($defaultPrefs[$prefName]["email"], $pref["email"]);
                $this->assertSame($defaultPrefs[$prefName]["popup"], $pref["popup"]);
            }
        }
    }
}
