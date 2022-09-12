<?php
/**
 * @author Ryan Perry <ryan.p@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use CategoryModel;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use UserModel;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Web\SystemTokenUtils;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/categories endpoints.
 */
class CategoryPreferencesTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use EventSpyTestTrait;
    use UsersAndRolesApiTestTrait;

    /** @var CategoryModel */
    private static $categoryModel;

    /**
     * Fix some container setup issues of the breadcrumb model.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setupBeforeClass();
        self::$categoryModel = self::container()->get(CategoryModel::class);
    }

    /**
     * Verify ability to successfully retrieve a user's preferences for a single category.
     *
     * @param bool|null $following
     * @param bool|null $discussionsApp
     * @param bool|null $discussionsEmail
     * @param bool|null $commentsApp
     * @param bool|null $commentsEmail
     * @param string|null $expected
     * @dataProvider provideLegacyNotificationData
     */
    public function testNotificationPreferencesGet(
        ?bool $following,
        ?bool $discussionsApp,
        ?bool $discussionsEmail,
        ?bool $commentsApp,
        ?bool $commentsEmail,
        ?string $expected
    ): void {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $user = $this->createUser();
        $userID = $user["userID"];
        $this->api()->setUserID($userID);

        if (is_bool($following)) {
            self::$categoryModel->follow($userID, $categoryID, $following);
        }

        /** @var \UserMetaModel $userMetaModel */
        $userMetaModel = $this->container()->get(\UserMetaModel::class);
        $userMetaModel->setUserMeta($userID, "Preferences.Popup.NewDiscussion.{$categoryID}", $discussionsApp);
        $userMetaModel->setUserMeta($userID, "Preferences.Email.NewDiscussion.{$categoryID}", $discussionsEmail);
        $userMetaModel->setUserMeta($userID, "Preferences.Popup.NewComment.{$categoryID}", $commentsApp);
        $userMetaModel->setUserMeta($userID, "Preferences.Email.NewComment.{$categoryID}", $commentsEmail);

        $preferences = $this->api()
            ->get("/categories/{$categoryID}/preferences/{$userID}")
            ->getBody();
        $this->assertSame($expected, $preferences[CategoryModel::PREFERENCE_KEY_NOTIFICATION]);
    }

    /**
     * Provide data for verifying a user's legacy notification settings map to the proper notification value.
     *
     * @return array[]
     */
    public function provideLegacyNotificationData(): array
    {
        return [
            "Following, only" => [true, null, null, null, null, CategoryModel::NOTIFICATION_FOLLOW],
            "Email on comments, only" => [null, null, null, null, true, CategoryModel::NOTIFICATION_ALL],
            "Email on comments, following" => [true, null, null, null, true, CategoryModel::NOTIFICATION_ALL],
            "Email on discussions, only" => [null, null, true, null, null, CategoryModel::NOTIFICATION_DISCUSSIONS],
            "Email on discussions, following" => [
                true,
                null,
                true,
                null,
                null,
                CategoryModel::NOTIFICATION_DISCUSSIONS,
            ],
            "In-app on comments, only" => [null, null, null, true, null, CategoryModel::NOTIFICATION_ALL],
            "In-app on comments, following" => [true, null, null, true, null, CategoryModel::NOTIFICATION_ALL],
            "In-app on discussions, only" => [null, true, null, null, null, CategoryModel::NOTIFICATION_DISCUSSIONS],
            "In-app on discussions, following" => [
                true,
                true,
                null,
                null,
                null,
                CategoryModel::NOTIFICATION_DISCUSSIONS,
            ],
            "Email on comments and discussions" => [null, null, true, null, true, CategoryModel::NOTIFICATION_ALL],
            "Email on comments and discussions, following" => [
                true,
                null,
                true,
                null,
                true,
                CategoryModel::NOTIFICATION_ALL,
            ],
            "In-app on comments and discussions" => [null, true, null, true, null, CategoryModel::NOTIFICATION_ALL],
            "In-app on comments and discussions, following" => [
                true,
                true,
                null,
                true,
                null,
                CategoryModel::NOTIFICATION_ALL,
            ],
            "Email and in-app on comments and discussions" => [
                null,
                true,
                true,
                true,
                true,
                CategoryModel::NOTIFICATION_ALL,
            ],
            "Email and in-app on comments and discussions, following" => [
                true,
                true,
                true,
                true,
                true,
                CategoryModel::NOTIFICATION_ALL,
            ],
        ];
    }

    /**
     * Verify ability to update a user's legacy notification settings via the postNotifications preference.
     *
     * @param ?string $postNotifications
     * @param bool|null $useEmailNotifications
     * @param bool|null $expectedFollowing
     * @param bool|null $expectedDiscussionsApp
     * @param bool|null $expectedDiscussionsEmail
     * @param bool|null $expectedCommentsApp
     * @param bool|null $expectedCommentsEmail
     * @dataProvider providePostNotificationsData
     */
    public function testNotificationPreferencesSet(
        ?string $postNotifications,
        ?bool $useEmailNotifications,
        ?bool $expectedFollowing,
        ?bool $expectedDiscussionsApp,
        ?bool $expectedDiscussionsEmail,
        ?bool $expectedCommentsApp,
        ?bool $expectedCommentsEmail
    ): void {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $user = $this->createUser();
        $userID = $user["userID"];
        $this->api()->setUserID($userID);

        $request = [CategoryModel::PREFERENCE_KEY_NOTIFICATION => $postNotifications];
        if (is_bool($useEmailNotifications)) {
            $request[CategoryModel::PREFERENCE_KEY_USE_EMAIL_NOTIFICATIONS] = $useEmailNotifications;
        }
        $this->api()->patch("/categories/{$categoryID}/preferences/{$userID}", $request);

        $actualFollowing = self::$categoryModel->isFollowed($userID, $categoryID);
        $this->assertSame($expectedFollowing, $actualFollowing);

        /** @var \UserMetaModel $userMetaModel */
        $userMetaModel = $this->container()->get(\UserMetaModel::class);
        $preferences = [
            3 => "Preferences.Popup.NewDiscussion.%d",
            4 => "Preferences.Email.NewDiscussion.%d",
            5 => "Preferences.Popup.NewComment.%d",
            6 => "Preferences.Email.NewComment.%d",
        ];
        foreach ($preferences as $arg => $preference) {
            $expected = func_get_arg($arg);

            $key = sprintf($preference, $categoryID);
            $meta = $userMetaModel->getUserMeta($userID, $key);
            $actual = $meta[$key];
            $this->assertSame(
                $expected,
                $actual === null ? $actual : (bool) $actual,
                "{$key} was not set as expected."
            );
        }
    }

    /**
     * Provide data for verifying a notification preference properly maps to legacy notification settings.
     *
     * @return array[]
     */
    public function providePostNotificationsData(): array
    {
        return [
            CategoryModel::NOTIFICATION_ALL => [CategoryModel::NOTIFICATION_ALL, null, true, true, null, true, null],
            CategoryModel::NOTIFICATION_DISCUSSIONS => [
                CategoryModel::NOTIFICATION_DISCUSSIONS,
                null,
                true,
                true,
                null,
                null,
                null,
            ],
            CategoryModel::NOTIFICATION_FOLLOW => [
                CategoryModel::NOTIFICATION_FOLLOW,
                null,
                true,
                null,
                null,
                null,
                null,
            ],
            CategoryModel::NOTIFICATION_ALL . ", opt into emails" => [
                CategoryModel::NOTIFICATION_ALL,
                true,
                true,
                true,
                true,
                true,
                true,
            ],
            CategoryModel::NOTIFICATION_DISCUSSIONS . ", opt into emails" => [
                CategoryModel::NOTIFICATION_DISCUSSIONS,
                true,
                true,
                true,
                true,
                null,
                null,
            ],
            CategoryModel::NOTIFICATION_ALL . ", opt out of emails" => [
                CategoryModel::NOTIFICATION_ALL,
                false,
                true,
                true,
                null,
                true,
                null,
            ],
            CategoryModel::NOTIFICATION_DISCUSSIONS . ", opt out of emails" => [
                CategoryModel::NOTIFICATION_DISCUSSIONS,
                false,
                true,
                true,
                null,
                null,
                null,
            ],
            "null" => [null, null, false, null, null, null, null],
        ];
    }

    /**
     * Make sure calling the legacy preferences endpoint doesn't clear our notifications.
     *
     * @see https://github.com/vanilla/support/issues/4856
     */
    public function testLegacyPreferencesDoesntBreak()
    {
        $this->createCategory();
        $user = $this->createUser();
        $this->api()->setUserID($user["userID"]);
        $url = "/categories/{$this->lastInsertedCategoryID}/preferences/{$this->lastUserID}";
        $initial = $this->api()
            ->patch($url, [
                CategoryModel::PREFERENCE_KEY_NOTIFICATION => CategoryModel::NOTIFICATION_ALL,
                CategoryModel::PREFERENCE_KEY_USE_EMAIL_NOTIFICATIONS => true,
            ])
            ->getBody();

        $this->bessy()->postJsonData("/profile/preferences/{$user["name"]}", [
            "Popup-dot-WallComment" => 1,
        ]);

        $after = $this->api()
            ->get($url)
            ->getBody();
        $this->assertEquals($initial, $after);
    }

    /**
     * Test that notifications get sent for items in categories more than 2 deep.
     */
    public function testNotificationPreferencesDepth4()
    {
        $this->runWithConfig(
            [
                \CategoryModel::CONF_CATEGORY_FOLLOWING => true,
            ],
            function () {
                // Create categories.
                $this->createCategory();
                $this->createCategory();
                $this->createCategory();
                $depth4Category = $this->createCategory();
                $this->assertNotEquals(CategoryModel::ROOT_ID, $depth4Category["parentCategoryID"]);

                // Create Users
                $postUser = $this->createUser();
                $followUser = $this->createUser();

                // Follow the category.
                $this->runWithUser(function () use ($depth4Category, $followUser) {
                    $url = "/categories/{$depth4Category["categoryID"]}/preferences/{$followUser["userID"]}";
                    $this->api()->patch($url, [
                        CategoryModel::PREFERENCE_KEY_NOTIFICATION => CategoryModel::NOTIFICATION_ALL,
                        CategoryModel::PREFERENCE_KEY_USE_EMAIL_NOTIFICATIONS => true,
                    ]);
                }, $followUser);
                // Create the posts
                $this->runWithUser(function () use ($depth4Category) {
                    $discussion = $this->createDiscussion(["name" => "Hello Discussion"]);
                    $this->assertEquals($depth4Category["categoryID"], $discussion["categoryID"]);
                    $this->createComment();
                }, $postUser);
                // Follow user should have 2 app notifications and 2 email notifications.
                $this->runWithUser(function () use ($followUser) {
                    /** @var \ActivityModel $activityModel */
                    $activityModel = self::container()->get(\ActivityModel::class);
                    $this->assertEquals(2, $activityModel->getUserTotalUnread($followUser["userID"]));
                }, $followUser);
            }
        );
    }

    /**
     * Provide data for testing the useEmailNotifications field, specifically.
     *
     * @return array
     */
    public function provideUseEmailNotificationsData(): array
    {
        $data = $this->providePostNotificationsData();
        $result = array_filter($data, function ($data) {
            return is_bool($data[1]);
        });

        return $result;
    }

    /**
     * Verify ability to update a user's legacy notification settings via the postNotifications preference.
     *
     * @param string|null $postNotifications
     * @param bool $useEmailNotifications
     * @param bool|null $expectedFollowing
     * @param bool|null $expectedDiscussionsApp
     * @param bool|null $expectedDiscussionsEmail
     * @param bool|null $expectedCommentsApp
     * @param bool|null $expectedCommentsEmail
     * @dataProvider provideUseEmailNotificationsData
     */
    public function testUseEmailNotificationsSet(
        ?string $postNotifications,
        bool $useEmailNotifications,
        ?bool $expectedFollowing,
        ?bool $expectedDiscussionsApp,
        ?bool $expectedDiscussionsEmail,
        ?bool $expectedCommentsApp,
        ?bool $expectedCommentsEmail
    ): void {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $user = $this->createUser();
        $userID = $user["userID"];
        $this->api()->setUserID($userID);

        // Setup notifications in a separate request, before attempting to modify email notification settings.
        $this->api()->patch("/categories/{$categoryID}/preferences/{$userID}", [
            CategoryModel::PREFERENCE_KEY_NOTIFICATION => $postNotifications,
        ]);
        $this->api()->patch("/categories/{$categoryID}/preferences/{$userID}", [
            CategoryModel::PREFERENCE_KEY_USE_EMAIL_NOTIFICATIONS => $useEmailNotifications,
        ]);

        /** @var \UserMetaModel $userMetaModel */
        $userMetaModel = $this->container()->get(\UserMetaModel::class);
        $preferences = [
            3 => "Preferences.Popup.NewDiscussion.%d",
            4 => "Preferences.Email.NewDiscussion.%d",
            5 => "Preferences.Popup.NewComment.%d",
            6 => "Preferences.Email.NewComment.%d",
        ];
        foreach ($preferences as $arg => $preference) {
            $expected = func_get_arg($arg);

            $key = sprintf($preference, $categoryID);
            $meta = $userMetaModel->getUserMeta($userID, $key);
            $actual = $meta[$key];
            $this->assertSame(
                $expected,
                $actual === null ? $actual : (bool) $actual,
                "{$key} was not set as expected."
            );
        }
    }

    /**
     * Verify listing out all of a user's category preferences.
     */
    public function testNotificationPreferencesIndex(): void
    {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $user = $this->createUser();
        $userID = $user["userID"];
        $this->api()->setUserID($userID);

        $categoryModel = self::$categoryModel;
        $categoryModel->follow($userID, $categoryID, true);

        $response = $this->api()
            ->get("/categories/preferences/{$userID}")
            ->getBody();
        $this->assertCount(1, $response);

        $actual = array_shift($response);
        $this->assertSame(
            [
                "preferences" => [
                    CategoryModel::PREFERENCE_KEY_NOTIFICATION => CategoryModel::NOTIFICATION_FOLLOW,
                    CategoryModel::PREFERENCE_KEY_USE_EMAIL_NOTIFICATIONS => false,
                ],
                "categoryID" => $categoryID,
                "name" => $category["name"],
                "url" => $category["url"],
            ],
            $actual
        );
    }

    /**
     * Test a production bug that occurred on category preferences with categoryIDs >= 10.
     *
     * @see https://higherlogic.atlassian.net/browse/APPENG-11913
     */
    public function testNotificationPreferencesHighCategoryIDs(): void
    {
        $user = $this->createUser();

        // There was a bug with category IDs more than one digit long that doesn't usually get hit in tests.
        \Gdn::sql()->query("alter table " . \Gdn::sql()->prefixTable("Category") . " AUTO_INCREMENT=100", "update");

        $categories = [];
        for ($i = 0; $i < 2; $i++) {
            $cat = $this->createCategory();
            \Gdn::sql()->insert("UserMeta", [
                "UserID" => $user["userID"],
                "Name" => "Preferences.Email.NewDiscussion.{$cat["categoryID"]}",
                "Value" => "1",
            ]);
            $categories[$cat["categoryID"]] = $cat;
        }

        $cat = $this->createCategory();
        \Gdn::sql()->insert("UserCategory", [
            "UserID" => $user["userID"],
            "CategoryID" => $cat["categoryID"],
            "DateMarkedRead" => null,
            "Followed" => 1,
            "Unfollow" => 0,
        ]);
        $categories[$cat["categoryID"]] = $cat;

        $prefs = $this->api()
            ->get("/categories/preferences/{$user["userID"]}")
            ->getBody();
        $this->assertCount(count($categories), $prefs);
    }

    /**
     * Verify disabling a user's notifications for a particular category.
     */
    public function testNotificationPreferencesNone(): void
    {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $user = $this->createUser();
        $userID = $user["userID"];
        $this->api()->setUserID($userID);

        $preferences = $this->api()
            ->get("/categories/{$categoryID}/preferences/{$userID}")
            ->getBody();
        $this->assertSame(null, $preferences[CategoryModel::PREFERENCE_KEY_NOTIFICATION]);
    }

    /**
     * Verify users with inadequate permissions cannot see other user's preferences.
     */
    public function testNotificationPreferencesNoPermission(): void
    {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $user = $this->createUser();
        $userID = $user["userID"];
        $targetUser = $this->createUser();
        $targetUserID = $targetUser["userID"];
        $this->api()->setUserID($userID);

        $this->expectException(ForbiddenException::class);
        $this->api()
            ->get("/categories/{$categoryID}/preferences/{$targetUserID}")
            ->getBody();
    }

    /**
     * Verify users with inadequate permissions cannot see other user's preferences.
     */
    public function testNotificationPreferencesNoPermissionIndex(): void
    {
        $user = $this->createUser();
        $userID = $user["userID"];
        $targetUser = $this->createUser();
        $targetUserID = $targetUser["userID"];
        $this->api()->setUserID($userID);

        $this->expectException(ForbiddenException::class);
        $this->api()
            ->get("/categories/preferences/{$targetUserID}")
            ->getBody();
    }

    /**
     * Verify users with inadequate permissions cannot set other user's preferences.
     */
    public function testNotificationPreferencesNoPermissionPatch(): void
    {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $user = $this->createUser();
        $userID = $user["userID"];
        $targetUser = $this->createUser();
        $targetUserID = $targetUser["userID"];
        $this->api()->setUserID($userID);

        $this->expectException(ForbiddenException::class);
        $this->api()->patch("/categories/{$categoryID}/preferences/{$targetUserID}", [
            CategoryModel::PREFERENCE_KEY_NOTIFICATION => CategoryModel::NOTIFICATION_FOLLOW,
        ]);
    }

    /**
     * Verify that an error is thrown when a user opts for email notifications without having the Garden.Email.View permission.
     */
    public function testNotificationEmailPreferenceWithoutEmailViewPermission(): void
    {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $user = $this->createUser();
        $userID = $user["userID"];
        $session = $this->getSession();
        $session->start($userID);
        $session->setPermission(["Garden.Email.View" => false]);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");
        $this->api()->patch("/categories/{$categoryID}/preferences/{$userID}", [
            CategoryModel::PREFERENCE_KEY_NOTIFICATION => "discussions",
            CategoryModel::PREFERENCE_KEY_USE_EMAIL_NOTIFICATIONS => true,
        ]);
    }

    /**
     * Verify that opting another user into email notifications succeeds when the user
     * in question has the 'Garden.Email.View' permission.
     */
    public function testNotificationEmailPreferenceWithoutEmailViewPermissionSetByAdmin(): void
    {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $roleWithEmail = $this->createRole([], ["email.view" => true]);
        $userSuccess = $this->createUser(["roleID" => [$roleWithEmail["roleID"]]]);

        // Should work when the user has the 'Garden.Email.View' permission.
        $preferencesWithEmail = $this->api()
            ->patch("/categories/{$categoryID}/preferences/{$userSuccess["userID"]}", [
                CategoryModel::PREFERENCE_KEY_NOTIFICATION => "discussions",
                CategoryModel::PREFERENCE_KEY_USE_EMAIL_NOTIFICATIONS => true,
            ])
            ->getBody();
        $this->assertTrue($preferencesWithEmail[CategoryModel::PREFERENCE_KEY_USE_EMAIL_NOTIFICATIONS]);
    }

    /**
     * Verify that opting another user into email notifications fails when the user in question doesn't have
     * the 'Garden.Email.View' permission.
     */
    public function testNotificationEmailPreferencesWithoutEmailViewPermissionSetByAdmin(): void
    {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $roleWithoutEmail = $this->createRole([], ["email.view" => false]);
        $userFailure = $this->createUser(["roleID" => [$roleWithoutEmail["roleID"]]]);

        // Should fail when the user doesn't have the 'Garden.Email.View' permission.
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");
        $this->api()->patch("/categories/{$categoryID}/preferences/{$userFailure["userID"]}", [
            CategoryModel::PREFERENCE_KEY_NOTIFICATION => "discussions",
            CategoryModel::PREFERENCE_KEY_USE_EMAIL_NOTIFICATIONS => true,
        ]);
    }
}
