<?php

namespace VanillaTests\Models;

use Vanilla\CurrentTimeStamp;
use Vanilla\Formatting\DateTimeFormatter;
use Vanilla\Models\EmailDigestGenerator;
use Vanilla\Models\UserDigestModel;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Web\TwigRenderTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

class EmailDigestGeneratorTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use TwigRenderTrait;
    use SchedulerTestTrait;

    private UserDigestModel $userDigestModel;
    private EmailDigestGenerator $emailDigestGenerator;
    private \CategoryModel $categoryModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->userDigestModel = \Gdn::getContainer()->get(UserDigestModel::class);
        $this->categoryModel = \Gdn::getContainer()->get(\CategoryModel::class);
        $this->emailDigestGenerator = \Gdn::getContainer()->get(EmailDigestGenerator::class);
        $config = [
            "Garden.Email.Disabled" => false,
            "Feature.Digest.Enabled" => true,
            "Garden.Digest.Enabled" => true,
        ];
        \Gdn::config()->saveToConfig($config);
    }

    /**
     * Test that digest is not generated when email is disabled for the site.
     *
     * @return void
     */
    public function testDigestDataNotGeneratedWhenEmailIsDisabledForSite(): void
    {
        $this->runWithConfig(["Garden.Email.Disabled" => true], function () {
            $this->emailDigestGenerator->generateDigestData();
            $this->assertLogMessage("Digest not enabled for the site");
            $this->assertEmpty($this->userDigestModel->select());
        });
    }

    /**
     * Test that digest content doesn't get generated when digest is disabled on config
     *
     * @return void
     */
    public function testDigestDataNotGeneratedWhenDigestIsDisabledForSite(): void
    {
        $this->runWithConfig(["Garden.Digest.Enabled" => false], function () {
            $this->emailDigestGenerator->generateDigestData();
            $this->assertLogMessage("Digest not enabled for the site");
            $this->assertEmpty($this->userDigestModel->select());
        });
    }

    /**
     *
     * @return void
     */
    public function testDigestDataIsNotGeneratedWhenDigestFeatureFlagIsNotEnabled(): void
    {
        $this->runWithConfig(["Feature.Digest.Enabled" => false], function () {
            $this->emailDigestGenerator->generateDigestData();
            $this->assertLogMessage("Digest not enabled for the site");
            $this->assertEmpty($this->userDigestModel->select());
        });
    }

    /**
     * provide default category data for setting default configuration
     *
     * @return array
     */
    public function getDefaultCategoryDataForConfig(): array
    {
        return [
            [
                "categoryID" => 1,
                "preferences" => [
                    "preferences.followed" => true,
                    "preferences.email.comments" => false,
                    "preferences.email.posts" => false,
                    "preferences.popup.comments" => true,
                    "preferences.popup.posts" => true,
                    "preferences.email.digest" => true,
                ],
            ],
        ];
    }

    /**
     * Test for default followed categories
     *
     * @return void
     */
    public function testGetDefaultFollowedCategories(): void
    {
        $this->assertEmpty($this->emailDigestGenerator->getDefaultFollowedCategories());
        $config = $this->getDefaultCategoryDataForConfig();
        $this->runWithConfig(["Preferences.CategoryFollowed.Defaults" => json_encode($config)], function () use (
            $config
        ) {
            $actual = $this->emailDigestGenerator->getDefaultFollowedCategories();
            $this->assertEquals($config, $actual);
        });
        $this->runWithConfig(["Preferences.CategoryFollowed.Defaults" => "hello"], function () {
            $this->emailDigestGenerator->getDefaultFollowedCategories();
            $this->assertLogMessage("Default categories are misconfigured.");
        });
    }

    /**
     * Test to see if default followed is enabled on site
     *
     * @return void
     */
    public function testIsDefaultFollowedEnabled(): void
    {
        $this->assertFalse($this->emailDigestGenerator->isDefaultFollowedEnabled());
        $config = $this->getDefaultCategoryDataForConfig();
        $this->runWithConfig(["Preferences.CategoryFollowed.Defaults" => json_encode($config)], function () {
            $this->assertTrue($this->emailDigestGenerator->isDefaultFollowedEnabled());
        });
    }

    /**
     * Generate some test data for running tests
     *
     * @return array
     */
    public function testGenerateData(): array
    {
        $data = [];
        $roles = ["Guest", self::ROLE_ADMIN, self::ROLE_MEMBER, self::ROLE_MOD, "Applicant"];
        $parentCategoryID = -1;
        for ($i = 1; $i <= 4; $i++) {
            $data["categories"][] = $this->createCategory(["parentCategoryID" => $parentCategoryID]);
            $parentCategoryID = $this->lastInsertedCategoryID;
            $data["permissionCategories"][] = $permissionCategory = $this->createPermissionedCategory(
                ["parentCategoryID" => \CategoryModel::ROOT_ID],
                [\RoleModel::ADMIN_ID, \RoleModel::MEMBER_ID]
            );
            $data["users"][$roles[$i]] = $this->createUserFixture($roles[$i]);
        }
        $this->assertNotEmpty($data);
        return $data;
    }

    /**
     * Test that we get public categories for the site
     *
     * @param array $data
     *
     * @depends  testGenerateData
     */
    public function testPublicVisibleCategories(array $data): void
    {
        $digestGenerator = clone $this->emailDigestGenerator;
        $actual = $this->categoryModel->getPublicVisibleCategories();
        $expected = array_column($data["categories"], "categoryID");
        $this->assertEmpty(array_diff($expected, $actual));

        //create a new permission category with view privilege for guest users
        $permissionCategory = $this->createPermissionedCategory(
            ["name=" => "Guest View Cat"],
            [\RoleModel::GUEST_ID, \RoleModel::MEMBER_ID]
        );
        $actual = $this->categoryModel->getPublicVisibleCategories();
        $this->assertContains($permissionCategory["categoryID"], $actual);
    }

    /**
     * Test to check visibility for the users followed category
     * We need to make sure that the user still have access to the categories he follows.
     *
     * @param array $data
     * @return void
     * @depends testGenerateData
     */
    public function testGetVisibleDigestEnabledCategoriesForUser(array $data): void
    {
        $memberUser = $data["users"]["Member"];
        $preferences = self::getPreferences();
        $expectedCategoryIDs = [];
        for ($i = 0; $i <= 2; $i++) {
            $this->categoryModel->setPreferences($memberUser, $data["categories"][$i]["categoryID"], $preferences);
            $this->categoryModel->setPreferences(
                $memberUser,
                $data["permissionCategories"][$i]["categoryID"],
                $preferences
            );
            $expectedCategoryIDs[] = $data["categories"][$i]["categoryID"];
            $expectedCategoryIDs[] = $data["permissionCategories"][$i]["categoryID"];
            $i++;
        }

        $visibleDigestCategories = $this->emailDigestGenerator->getVisibleDigestEnabledCategoriesForUser($memberUser);
        $this->assertEquals($expectedCategoryIDs, $visibleDigestCategories);

        //change Visibility to the category;
        $this->api()->patch("roles/8", [
            "permissions" => [
                [
                    "id" => $data["permissionCategories"][0]["categoryID"],
                    "type" => "category",
                    "permissions" => [
                        "discussions.view" => false,
                    ],
                ],
            ],
        ]);

        $visibleDigestCategories = $this->emailDigestGenerator->getVisibleDigestEnabledCategoriesForUser($memberUser);
        $this->assertTrue(!in_array($data["permissionCategories"][0]["categoryID"], $visibleDigestCategories));
        $this->assertNotEquals($expectedCategoryIDs, $visibleDigestCategories);
    }

    /**
     * Test that we get proper email settings
     *
     * @return void
     */
    public function testEmailSettings(): void
    {
        $config = [
            "Garden.EmailTemplate.BackgroundColor" => "#eeeeee",
            "Garden.EmailTemplate.ButtonBackgroundColor" => "#38abe3",
            "Garden.EmailTemplate.ButtonTextColor" => "#ffffff",
            "Garden.EmailTemplate.ContainerBackgroundColor" => "#ffffff",
            "Garden.EmailTemplate.TextColor" => "#333333",
            "Garden.EmailTemplate.Image" =>
                "https://www.higherlogic.com/wp-content/uploads/2020/05/higherLogic_stacked.png",
        ];
        $this->runWithConfig($config, function () use ($config) {
            $expected = [
                "siteUrl" => \Gdn::request()->getSimpleUrl(),
                "digestUnsubscribeLink" => "*/digest_unsubscribe/*",
                "notificationPreferenceLink" => url("/profile/preferences", true),
                "title" => "This Week's Trending Posts",
                "imageUrl" => $config["Garden.EmailTemplate.Image"],
                "imageAlt" => \Gdn::config()->get("Garden.Title", "Vanilla Forums Digest"),
                "textColor" => $config["Garden.EmailTemplate.TextColor"],
                "backgroundColor" => $config["Garden.EmailTemplate.BackgroundColor"],
                "buttonTextColor" => $config["Garden.EmailTemplate.ButtonTextColor"],
                "buttonBackgroundColor" => $config["Garden.EmailTemplate.ButtonBackgroundColor"],
                "footer" => "<p>Sample html footer</p>",
            ];
            $actual = $this->emailDigestGenerator->getTemplateSettings();
            $this->assertEquals($expected, $actual);
        });
    }

    /**
     * Test that for sending digest we get only users without any category preference
     *
     * @param array $data
     * @return array
     * @depends testGenerateData
     */
    public function testUsersWithoutCategoryPreference(array $data): array
    {
        //create some member users
        $userIDs = [];
        for ($i = 0; $i < 5; $i++) {
            $this->createUser(["name" => "Test User{$i}"]);
            $userIDs[] = $this->lastUserID;
            if ($i != 4) {
                \Gdn::userMetaModel()->setUserMeta($this->lastUserID, "Preferences.Email.DigestEnabled", 1);
            }
        }
        $preferences = self::getPreferences();

        // make the first user follow some categories
        $visibleCategories = $data["categories"];
        $this->categoryModel->setPreferences($userIDs[0], $visibleCategories[0]["categoryID"], $preferences);

        //Make the user unfollow a category
        $this->categoryModel->setPreferences($userIDs[1], $visibleCategories[0]["categoryID"], [
            "Preferences.Follow" => false,
        ]);

        //Make the third user have a category marked read
        $this->runWithUser(function () use ($visibleCategories) {
            $this->categoryModel->saveUserTree($visibleCategories[2]["categoryID"], [
                "DateMarkedRead" => DateTimeFormatter::timeStampToDateTime(CurrentTimeStamp::get()),
            ]);
        }, $userIDs[2]);

        $this->assertEquals(2, $this->emailDigestGenerator->getTotalUsersWithoutCategoryPreference());

        $usersWithOutPreference = $this->emailDigestGenerator->getUsersWithOutCategoryPreferenceIterator(0);
        $digestUsers = [];
        foreach ($usersWithOutPreference as $userID => $userMeta) {
            $digestUsers[] = $userID;
        }
        $expected = [$userIDs[2], $userIDs[3]];

        $this->assertEquals($expected, $digestUsers);
        return $userIDs;
    }

    /**
     * Test that while generating digest we only get users having category preference and digest enabled
     *
     * @return void
     * @depends testUsersWithoutCategoryPreference
     * @depends testGenerateData
     */
    public function testUsersWithCategoryPreferences(array $userIDs, array $data): void
    {
        $this->assertEquals(1, $this->emailDigestGenerator->getTotalDigestEnabledUsersWithPreference());

        // Make user follow a category with Digest enabled
        $categoryIDs = array_column($data["categories"], "categoryID");
        $this->categoryModel->setPreferences($userIDs[3], $categoryIDs[2], self::getPreferences());

        //Make a user follow a category without Digest enabled

        $this->categoryModel->setPreferences($data["users"]["Moderator"], $categoryIDs[2], self::getPreferences(false));
        \Gdn::userMetaModel()->setUserMeta($data["users"]["Moderator"], "Preferences.Email.DigestEnabled", 1);

        $this->assertEquals(2, $this->emailDigestGenerator->getTotalDigestEnabledUsersWithPreference());
        $usersWithPreference = $this->emailDigestGenerator->getDigestEnabledCategoryFollowingUserIterator(0);
        $digestUsers = [];
        foreach ($usersWithPreference as $userID => $userMeta) {
            $digestUsers[] = $userID;
        }

        $this->assertEquals([$userIDs[0], $userIDs[3]], $digestUsers);
    }

    /**
     * Test that we get default followed categories for a user
     *
     * @return void
     * @depends testGenerateData
     */
    public function testDefaultDigestCategoryIds(array $data)
    {
        $config = $this->getDefaultCategoryDataForConfig();
        $categoryID = $data["categories"][3]["categoryID"];
        $userID = $data["users"]["Applicant"];
        $config[0]["categoryID"] = $categoryID;
        //Default category with public visibility
        $this->runWithConfig(["Preferences.CategoryFollowed.Defaults" => json_encode($config)], function () use (
            $categoryID,
            $userID
        ) {
            $defaultCategories = $this->emailDigestGenerator->getDefaultDigestCategoryIDs($userID);
            $this->assertEquals([$categoryID], $defaultCategories);
        });

        //Category having permission
        $categoryID = $data["permissionCategories"][3]["categoryID"];
        $config[0]["categoryID"] = $categoryID;
        $this->runWithConfig(["Preferences.CategoryFollowed.Defaults" => json_encode($config)], function () use (
            $categoryID,
            $userID
        ) {
            $defaultCategories = $this->emailDigestGenerator->getDefaultDigestCategoryIDs($userID);
            $this->assertEmpty($defaultCategories);
        });

        //Default category with digest not enabled

        $config[0]["preferences"]["preferences.email.digest"] = false;
        $this->runWithConfig(["Preferences.CategoryFollowed.Defaults" => json_encode($config)], function () use (
            $userID
        ) {
            $defaultCategories = $this->emailDigestGenerator->getDefaultDigestCategoryIDs($userID);
            $this->assertEmpty($defaultCategories);
        });

        //Default category with invalid setting
        $this->runWithConfig(["Preferences.CategoryFollowed.Defaults" => "hello"], function () use ($userID) {
            $defaultCategories = $this->emailDigestGenerator->getDefaultDigestCategoryIDs($userID);
            $this->assertEmpty($defaultCategories);
        });

        //Default followed not enabled
        $this->runWithConfig(["Preferences.CategoryFollowed.Defaults" => ""], function () use ($userID) {
            $defaultCategories = $this->emailDigestGenerator->getDefaultDigestCategoryIDs($userID);
            $this->assertNotEmpty($defaultCategories);
        });
    }

    /**
     * Test we get a list of trending discussion from a group of categories
     *
     * @return array
     * @depends testGenerateData
     */
    public function testTrendingDiscussionForCategories(array $data): array
    {
        $this->assertEmpty($this->emailDigestGenerator->getTrendingDiscussionForCategories([999]));
        $categoryIDs = array_column($data["categories"], "categoryID");
        //add some discussions and comments to these categories

        foreach ($categoryIDs as $index => $categoryID) {
            $this->createDiscussion([
                "name" => "Test Discussion for Category {$categoryID}",
                "categoryID" => $categoryID,
            ]);
            $discussionID = $this->lastInsertedDiscussionID;
            $this->createComment([
                "discussionID" => $discussionID,
            ]);

            if ($index % 2 == 0) {
                for ($i = 1; $i <= 2; $i++) {
                    $this->createDiscussion([
                        "name" => "Test Discussion for Category {$categoryID} - {$i}",
                        "categoryID" => $categoryID,
                    ]);

                    $users = $data["users"];
                    unset($users["Applicant"]);

                    foreach ($users as $role => $userID) {
                        $this->runWithUser(function () use ($role, $userID) {
                            $this->createComment([
                                "discussionID" => $this->lastInsertedDiscussionID,
                                "body" => "{$userID} Comment",
                            ]);
                        }, $userID);
                    }
                }
            }
        }
        //
        $trendingDiscussionWithoutUnsubscribeLink = $this->emailDigestGenerator->getTrendingDiscussionForCategories(
            [$categoryIDs[0]],
            false
        );
        $this->assertArrayNotHasKey("unsubscribeLink", $trendingDiscussionWithoutUnsubscribeLink);
        $trendingDiscussion = $this->emailDigestGenerator->getTrendingDiscussionForCategories($categoryIDs);
        $countDiscussions = 0;
        $c = 1;
        $categoryColumns = ["name", "url", "iconUrl", "unsubscribeLink", "discussions"];
        foreach ($trendingDiscussion as $categoryID => $categoryData) {
            if ($c = 1) {
                foreach ($categoryData as $column => $value) {
                    $this->assertContains($column, $categoryColumns);
                }
            }
            $c++;
            $countDiscussions += count($categoryData["discussions"]);
        }
        $this->assertEquals(5, $countDiscussions);
        return $trendingDiscussion;
    }

    /**
     * get some test preferences
     *
     * @param bool $withDigest;
     * @return array
     */
    public static function getPreferences(bool $withDigest = true): array
    {
        $preferences = [
            \CategoryModel::stripCategoryPreferenceKey(\CategoryModel::PREFERENCE_FOLLOW) => true,
            \CategoryModel::stripCategoryPreferenceKey(\CategoryModel::PREFERENCE_DISCUSSION_APP) => true,
        ];
        if ($withDigest) {
            $preferences[\CategoryModel::stripCategoryPreferenceKey(\CategoryModel::PREFERENCE_DIGEST_EMAIL)] = true;
        }
        return $preferences;
    }

    /**
     * Test for system callbale methods
     *
     * @return void
     */
    public function testSystemCallBackMethods(): void
    {
        $this->assertEquals(
            ["collectFollowingCategoryDigestIterator", "collectDefaultDigestIterator"],
            EmailDigestGenerator::getSystemCallableMethods()
        );
    }

    /**
     * Test Long runner
     *
     * @return void
     */
    public function testLongRunnerForDigestIterators()
    {
        $this->resetTable("UserCategory");
        $userIDs = [];
        $categoryIDs = [];
        $metaModel = \Gdn::userMetaModel();
        for ($i = 1; $i <= 5; $i++) {
            $this->createUser();
            $userIDs[] = $this->lastUserID;
            $metaModel->setUserMeta($this->lastUserID, "Preferences.Email.DigestEnabled", 1);
            $this->createCategory(["parentCategoryID" => -1]);
            $categoryIDs[] = $this->lastInsertedCategoryID;
            $this->createDiscussion(["categoryID" => $this->lastInsertedCategoryID]);
        }
        $preferences = $this->getPreferences();
        foreach ($userIDs as $userID) {
            foreach ($categoryIDs as $categoryID) {
                $this->categoryModel->setPreferences($userID, $categoryID, $preferences);
            }
        }
        $longRunner = $this->getLongRunner();
        $longRunner->setMaxIterations(2);
        $options = [
            "lastProcessedUser" => 0,
        ];
        $action = new LongRunnerAction(EmailDigestGenerator::class, "collectFollowingCategoryDigestIterator", [
            $options,
        ]);
        $result = $longRunner->runImmediately($action);
        $this->assertEquals(5, $result->getCountTotalIDs());
        $this->assertNotEmpty($result->getCallbackPayload());
        $this->assertEquals(array_slice($userIDs, 0, 2), $result->getSuccessIDs());

        $longRunner->setMaxIterations(4);
        $finalResult = $this->resumeLongRunner($result);
        $this->assertEquals(
            200,
            $finalResult->getStatusCode(),
            "Long runner should complete. " . $finalResult->getRawBody()
        );
        $body = $finalResult->getBody();
        $this->assertEmpty($body["progress"]["failedIDs"]);
        $this->assertEmpty($body["callbackPayload"]);
        $this->assertEquals(array_slice($userIDs, 2), $body["progress"]["successIDs"]);

        //Test for users without preferences
        $this->resetTable("UserCategory");
        $action = new LongRunnerAction(EmailDigestGenerator::class, "collectDefaultDigestIterator", [$options]);
        $totalUsersWithOutPreference = $this->emailDigestGenerator->getTotalUsersWithoutCategoryPreference();
        $longRunner->reset();
        $longRunner->setMaxIterations(1);
        $result = $longRunner->runImmediately($action);
        $this->assertEquals($totalUsersWithOutPreference, $result->getCountTotalIDs());
        $this->assertNotEmpty($result->getCallbackPayload());

        $longRunner->setMaxIterations($totalUsersWithOutPreference);
        $finalResult = $this->resumeLongRunner($result);
        $this->assertEquals(
            200,
            $finalResult->getStatusCode(),
            "Long runner should complete. " . $finalResult->getRawBody()
        );
        $body = $finalResult->getBody();
        $this->assertEmpty($body["progress"]["failedIDs"]);
        $this->assertEmpty($body["callbackPayload"]);
    }

    /**
     * Test email digest is not processed when the users in the system has no permission to view email
     * @return void
     */
    public function testDigestIsNotGeneratedWhenUsersDontHavePermissionToViewEmails(): void
    {
        \Gdn::permissionModel()
            ->SQL->update("Permission")
            ->set(["`Garden.Email.View`" => 0])
            ->where(["JunctionTable" => null])
            ->put();
        $this->emailDigestGenerator->generateDigestData();
        $this->assertLogMessage("No roles found with Garden.Email.View permission");
    }

    /**
     *
     * @return void
     */
    public function testGetUsersReturnsNullWhenUsersHaveNoEmailViewPermission()
    {
        \Gdn::permissionModel()
            ->SQL->update("Permission")
            ->set(["`Garden.Email.View`" => 0])
            ->where(["JunctionTable" => null])
            ->put();
        $usersWithOutPreference = $this->emailDigestGenerator->getUsersWithOutCategoryPreferenceIterator(0);
        foreach ($usersWithOutPreference as $userID => $userMeta) {
        }
        $this->assertLogMessage(
            "Could not find any user roles to process with 'Garden.Email.View' Permission, for users without category preference"
        );
        $usersWithPreference = $this->emailDigestGenerator->getDigestEnabledCategoryFollowingUserIterator(0);
        foreach ($usersWithPreference as $userID => $userMeta) {
        }
        $this->assertLogMessage(
            "Could not find any user roles to process with 'Garden.Email.View' Permission, for users with category preference"
        );
    }
}
