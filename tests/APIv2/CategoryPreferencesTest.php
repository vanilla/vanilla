<?php
/**
 * @author Ryan Perry <ryan.p@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use CategoryModel;
use Garden\Web\Exception\ForbiddenException;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
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
     * Test following and unfollowing a category via the patch endpoint.
     */
    public function testFollowUnfollow(): void
    {
        $this->createCategory();

        $url = "/categories/{$this->lastInsertedCategoryID}/preferences/" . self::$siteInfo["adminUserID"];
        $this->api()
            ->patch($url, [
                \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
            ])
            ->getBody();

        $followedCats = $this->api()
            ->get("/categories?followed=true")
            ->getBody();

        $this->assertSame(1, count($followedCats));

        $this->assertSame($this->lastInsertedCategoryID, $followedCats[0]["categoryID"]);

        $this->api()
            ->patch($url, [
                \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => false,
            ])
            ->getBody();

        $followedCats = $this->api()
            ->get("/categories?followed=true")
            ->getBody();

        $this->assertEmpty($followedCats);
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
                \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_APP => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_EMAIL => true,
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
                        \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
                        \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => true,
                        \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL => true,
                        \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_APP => true,
                        \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_EMAIL => true,
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
        $categoryModel->follow($userID, $categoryID, true, true);

        $response = $this->api()
            ->get("/categories/preferences/{$userID}")
            ->getBody();
        $this->assertCount(1, $response);

        $actual = array_shift($response);
        $this->assertSame(
            [
                "categoryID" => $categoryID,
                "name" => $category["name"],
                "url" => $category["url"],
                "preferences" => [
                    \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
                    \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => false,
                    \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL => false,
                    \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_APP => false,
                    \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_EMAIL => false,
                ],
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
            \Gdn::sql()->insert("UserCategory", [
                "UserID" => $user["userID"],
                "CategoryID" => $cat["categoryID"],
                "DateMarkedRead" => null,
                "Followed" => 1,
                "Unfollow" => 0,
            ]);
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
     * Test impact on `GDN_UserCategory`'s records upon Category deletion.
     */
    public function testDeleteCategory(): void
    {
        // Create a user for our testing purpose.
        $user = $this->createUser();

        // There was a bug with category IDs more than one digit long that doesn't usually get hit in tests.
        \Gdn::sql()->query("alter table " . \Gdn::sql()->prefixTable("Category") . " AUTO_INCREMENT=100", "update");

        // Create a bunch of categories
        for ($i = 0; $i < 3; $i++) {
            $cat = $this->createCategory();
        }

        // Have the user follow the last one we created.
        \Gdn::sql()->insert("UserCategory", [
            "UserID" => $user["userID"],
            "CategoryID" => $cat["categoryID"],
            "DateMarkedRead" => null,
            "Followed" => 1,
            "Unfollow" => 0,
        ]);

        // Count the `UserCategory` records for the UserID.`
        $userCategoryCount = $this->getUserCategoryCount(["UserID" => $user["userID"]]);
        $this->assertEquals(1, $userCategoryCount);

        // Delete the category.
        self::$categoryModel->deleteandReplace($cat["categoryID"], 0);

        // Ensure we do not have `UserCategory` records for the UserID.
        $userCategoryCount = $this->getUserCategoryCount(["UserID" => $user["userID"]]);
        $this->assertEquals(0, $userCategoryCount);
    }

    /**
     * Test impact on `GDN_UserCategory`'s records upon User deletion.
     */
    public function testDeleteUser(): void
    {
        // Create a user for our testing purpose.
        $user = $this->createUser();

        // There was a bug with category IDs more than one digit long that doesn't usually get hit in tests.
        \Gdn::sql()->query("alter table " . \Gdn::sql()->prefixTable("Category") . " AUTO_INCREMENT=100", "update");

        // Create a bunch of categories
        for ($i = 0; $i < 3; $i++) {
            $cat = $this->createCategory();
        }

        // Have the user follow the last one we created.
        \Gdn::sql()->insert("UserCategory", [
            "UserID" => $user["userID"],
            "CategoryID" => $cat["categoryID"],
            "DateMarkedRead" => null,
            "Followed" => 1,
            "Unfollow" => 0,
        ]);

        // Count the `UserCategory` records for the UserID.`
        $userCategoryCount = $this->getUserCategoryCount(["UserID" => $user["userID"]]);
        $this->assertEquals(1, $userCategoryCount);

        // Delete the User.
        $this->userModel->deleteID($user["userID"]);

        // Ensure we do not have `UserCategory` records for the UserID.
        $userCategoryCount = $this->getUserCategoryCount(["UserID" => $user["userID"]]);
        $this->assertEquals(0, $userCategoryCount);
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
        $this->assertSame(false, $preferences[\CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW]);
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
            \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
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
            \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
            \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => true,
            \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL => true,
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
                \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL => true,
            ])
            ->getBody();
        $this->assertTrue($preferencesWithEmail[\CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL]);
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
            \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
            \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => true,
            \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL => true,
        ]);
    }

    /**
     * Test that you get an error when you try to update preferences without following a category.
     *
     * @return void
     */
    public function testPatchWithoutFollowing(): void
    {
        $category = $this->createCategory();
        $this->expectExceptionMessage("You must follow a category to set its notification preferences.");
        $this->api()->patch("/categories/{$category["categoryID"]}/preferences/" . self::$siteInfo["adminUserID"]);
    }

    /**
     * Test that you can enable and disable email digest via patch end point
     *
     * @return void
     */
    public function testEmailDigestPreferences(): void
    {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $user = $this->createUser();
        $userID = $user["userID"];
        //Enable category Digest
        $this->runWithUser(function () use ($categoryID, $userID) {
            \Gdn::config()->set("Garden.Digest.Enabled", true);
            $url = "/categories/{$categoryID}/preferences/{$userID}";
            $result = $this->api()
                ->patch($url, [
                    \CategoriesApiController::OUTPUT_PREFERENCE_DIGEST => true,
                    \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
                    \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => true,
                    \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL => true,
                    \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_APP => true,
                    \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_EMAIL => true,
                ])
                ->getBody();
            $this->assertArrayHasKey(\CategoriesApiController::OUTPUT_PREFERENCE_DIGEST, $result);
            $this->assertTrue($result[\CategoriesApiController::OUTPUT_PREFERENCE_DIGEST]);

            //Disable email Digest
            $result = $this->api()
                ->patch($url, [
                    \CategoriesApiController::OUTPUT_PREFERENCE_DIGEST => false,
                    \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
                ])
                ->getBody();
            $this->assertFalse($result[\CategoriesApiController::OUTPUT_PREFERENCE_DIGEST]);

            // test that unfollowing a category will also disable digest
            $result = $this->api()
                ->patch($url, [
                    \CategoriesApiController::OUTPUT_PREFERENCE_DIGEST => true,
                    \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => false,
                ])
                ->getBody();
            $this->assertFalse($result[\CategoriesApiController::OUTPUT_PREFERENCE_DIGEST]);
        }, $user);
    }

    /**
     * Get a count on the `GDN_UserCategory` table for provided `where` conditions.
     *
     * @param array $where
     * @return int
     * @throws \Exception
     */
    private function getUserCategoryCount(array $where): int
    {
        return \Gdn::sql()
            ->getWhere("UserCategory", $where)
            ->count();
    }
}
