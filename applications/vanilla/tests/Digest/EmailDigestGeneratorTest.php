<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Forum\Digest;

use Garden\Web\Exception\ServerException;
use Vanilla\CurrentTimeStamp;
use Vanilla\Forum\Digest\EmailDigestGenerator;
use Vanilla\Forum\Digest\UserDigestModel;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\TwigRenderTrait;
use VanillaTests\DatabaseTestTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for generation of email digests.
 */
class EmailDigestGeneratorTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use TwigRenderTrait;
    use SchedulerTestTrait;
    use DatabaseTestTrait;
    use ExpectExceptionTrait;

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
            "Garden.Digest.Enabled" => true,
        ];
        \Gdn::config()->saveToConfig($config);
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

    public function testDigestCategoryIDGeneration(): void
    {
        $this->resetCategoryTable();
        $user = $this->createUser();
        $userID = $user["userID"];
        $this->enableDigestForUser($user);

        // There are 4 categories.
        // Our user will not have permission to one of them.
        $cat1 = $this->createCategory();
        $cat2 = $this->createCategory();
        $cat3 = $this->createCategory();
        $hiddenFromRecents = $this->createCategory(
            [],
            [
                "HideAllDiscussions" => true,
            ]
        );
        $permissionCategory = $this->createPermissionedCategory();

        // no hidden from recents or permission category.
        $this->assertDigestCategoryIDs($userID, [$cat1, $cat2, $cat3]);

        // Now if we set 1 and 2 to be default followed those will be used.
        $this->runWithConfig(
            [
                \CategoryModel::DEFAULT_FOLLOWED_CATEGORIES_KEY => json_encode([
                    ["categoryID" => $cat1["categoryID"], "preferences" => self::followingPreferences()],
                    ["categoryID" => $cat2["categoryID"], "preferences" => self::followingPreferences()],
                    [
                        // This one should be completely ignored because we don't have permission to access it.
                        "categoryID" => $permissionCategory["categoryID"],
                        "preferences" => self::followingPreferences(),
                    ],
                ]),
            ],
            function () use ($userID, $cat1, $cat2) {
                $this->assertDigestCategoryIDs($userID, [$cat1, $cat2]);
            }
        );

        // 1 is followed, but 2 has default digest enabled.
        $this->runWithConfig(
            [
                \CategoryModel::DEFAULT_FOLLOWED_CATEGORIES_KEY => json_encode([
                    ["categoryID" => $cat1["categoryID"], "preferences" => self::followingPreferences(false)],
                    ["categoryID" => $cat2["categoryID"], "preferences" => self::followingPreferences(true)],
                ]),
            ],
            function () use ($userID, $cat1, $cat2, $cat3, $hiddenFromRecents) {
                $this->assertDigestCategoryIDs($userID, [$cat2]);

                // A user following a category without digest won't have any effect.
                $this->setCategoryPreference($userID, $cat3, self::followingPreferences(false));
                $this->assertDigestCategoryIDs($userID, [$cat2]);

                // Now if the user subscribes to a specific category that will take precedence.
                $this->setCategoryPreference($userID, $cat1, self::followingPreferences(true));
                $this->assertDigestCategoryIDs($userID, [$cat1]);

                // If the user subscribes to a hidden category they can receive it.
                $this->setCategoryPreference($userID, $hiddenFromRecents, self::followingPreferences());
                $this->assertDigestCategoryIDs($userID, [$cat1, $hiddenFromRecents]);
            }
        );
    }

    /**
     * Assert that a user's digest will contain content from the following categories.
     *
     * @param int $userID
     * @param array|null $expectedCategoryIDs
     */
    private function assertDigestCategoryIDs(int $userID, ?array $expectedCategoryIDs): void
    {
        $digestUserCategory = iterator_to_array(
            $this->emailDigestGenerator->getDigestUserCategoriesIterator([
                "um.userID" => $userID,
            ])
        )[$userID];
        $digestCategoryData = $this->emailDigestGenerator->getDigestCategoryData($digestUserCategory);

        if (isset($expectedCategoryIDs[0]["categoryID"])) {
            $expectedCategoryIDs = array_column($expectedCategoryIDs, "categoryID");
        }

        $this->assertEquals($expectedCategoryIDs, $digestCategoryData["categoryIDs"] ?? null);
    }

    /**
     * Generate some test data for running tests
     *
     * @return array
     */
    public function testGenerateData(): array
    {
        // Clearout the non-root categories that exist.
        \Gdn::database()
            ->createSql()
            ->delete("Category", ["CategoryID >" => \CategoryModel::ROOT_ID]);
        \CategoryModel::clearCache();

        $data = [];
        $roles = ["Guest", self::ROLE_ADMIN, self::ROLE_MEMBER, self::ROLE_MOD, "Applicant"];
        $parentCategoryID = -1;
        for ($i = 1; $i <= 4; $i++) {
            $data["categories"][] = $this->createCategory(["parentCategoryID" => $parentCategoryID]);
            $parentCategoryID = $this->lastInsertedCategoryID;
            $data["permissionCategories"][] = $this->createPermissionedCategory(
                ["parentCategoryID" => \CategoryModel::ROOT_ID],
                [\RoleModel::ADMIN_ID, \RoleModel::MEMBER_ID]
            );
            $data["users"][$roles[$i]] = $this->createUserFixture($roles[$i]);
        }
        $this->assertNotEmpty($data);
        return $data;
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
                "imageUrl" => $config["Garden.EmailTemplate.Image"],
                "imageAlt" => \Gdn::config()->get("Garden.Title", "Vanilla Forums Digest"),
                "textColor" => $config["Garden.EmailTemplate.TextColor"],
                "backgroundColor" => $config["Garden.EmailTemplate.BackgroundColor"],
                "buttonTextColor" => $config["Garden.EmailTemplate.ButtonTextColor"],
                "buttonBackgroundColor" => $config["Garden.EmailTemplate.ButtonBackgroundColor"],
            ];
            $actual = $this->emailDigestGenerator->getTemplateSettings();
            $this->assertEquals($expected, $actual);
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
        $this->assertEmpty($this->emailDigestGenerator->getTopWeeklyDiscussions([999]));
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
        $trendingDiscussionWithoutUnsubscribeLink = $this->emailDigestGenerator->getTopWeeklyDiscussions(
            [$categoryIDs[0]],
            false
        );
        $this->assertArrayNotHasKey("unsubscribeLink", $trendingDiscussionWithoutUnsubscribeLink);
        $trendingDiscussion = $this->emailDigestGenerator->getTopWeeklyDiscussions($categoryIDs);
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
     * @param bool $withDigest ;
     * @return array
     */
    public static function followingPreferences(bool $withDigest = true): array
    {
        $preferences = [
            \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
            \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => true,
        ];
        if ($withDigest) {
            $preferences[\CategoriesApiController::OUTPUT_PREFERENCE_DIGEST] = true;
        }
        return $preferences;
    }

    /**
     * Test Long runner
     *
     * @return void
     */
    public function testPrepareWeeklyDigest()
    {
        $this->resetTable("UserCategory");
        $this->clearUsers();

        $cat1 = $this->createCategory(["name" => "Cat 1"]);
        $this->createDiscussion(["name" => "Cat 1 discussion"]);
        $cat2 = $this->createCategory(["name" => "Cat 2"]);
        $this->createDiscussion(["name" => "Cat 2 discussion"]);
        $cat3 = $this->createCategory(["name" => "Cat 3"]);
        $this->createDiscussion(["name" => "Cat 3 discussion"]);

        $digestEnabledUserWithCatPrefs = $this->createUser();
        $this->enableDigestForUser($digestEnabledUserWithCatPrefs);
        $this->setCategoryPreference($digestEnabledUserWithCatPrefs, $cat1, $this->followingPreferences());
        $this->setCategoryPreference($digestEnabledUserWithCatPrefs, $cat2, $this->followingPreferences());

        $digestEnabledUserWithFollowedCats = $this->createUser();
        $this->enableDigestForUser($digestEnabledUserWithFollowedCats);
        $this->setCategoryPreference($digestEnabledUserWithFollowedCats, $cat1, $this->followingPreferences(false));
        $this->setCategoryPreference($digestEnabledUserWithFollowedCats, $cat2, $this->followingPreferences(false));

        $digestEnabledUserWithNoFollowed = $this->createUser();
        $this->enableDigestForUser($digestEnabledUserWithNoFollowed);

        $digestEnabledUserWithNoFollowedDupe = $this->createUser();
        $this->enableDigestForUser($digestEnabledUserWithNoFollowedDupe);

        $digestDisabledUser = $this->createUser();

        $action = $this->emailDigestGenerator->prepareWeeklyDigestAction(CurrentTimeStamp::getDateTime());
        $this->getLongRunner()->setMaxIterations(2);
        $result = $this->getLongRunner()->runImmediately($action);

        // There were 4 total users that should be receiving digest.
        // The 5th one with digest enabled will not receive the digest.
        $this->assertEquals(4, $result->getCountTotalIDs());

        $firstIterationUserIDs = [
            // Users with explicit category preferences first.
            $digestEnabledUserWithCatPrefs["userID"],
            // Users with without category specific preferences (getting the default categories).
            $digestEnabledUserWithFollowedCats["userID"],
        ];
        $this->assertEquals($firstIterationUserIDs, $result->getSuccessIDs());

        // Make sure we have generated proper digests for these users.
        $this->assertRecordsFound("userDigest", ["userID" => $firstIterationUserIDs], 2);

        // Still more work to do.
        $this->assertNotEmpty($result->getCallbackPayload());

        // Let's do one more.
        $this->getLongRunner()->setMaxIterations(1);
        $result = $this->resumeLongRunner($result);
        $this->assertEquals(4, $result["progress"]["countTotalIDs"]);
        // We've started our users with no followed categories.
        // They get all the content.
        $this->assertEquals([$digestEnabledUserWithNoFollowed["userID"]], $result["progress"]["successIDs"]);

        $result = $this->resumeLongRunner($result);
        $this->assertEquals([$digestEnabledUserWithNoFollowedDupe["userID"]], $result["progress"]["successIDs"]);

        // Now we are on to sending the emails.
        // Let's send 2 of the 4.
        $this->getLongRunner()->setMaxIterations(2);
        $result = $this->resumeLongRunner($result);
        $this->assertEmailSentTo($digestEnabledUserWithCatPrefs["email"]);
        $this->assertEmailSentTo($digestEnabledUserWithFollowedCats["email"]);

        // If user were to be deleted they won't receive the email.
        $this->api()->deleteWithBody("/users/{$digestEnabledUserWithNoFollowed["userID"]}", []);

        $this->getLongRunner()->setMaxIterations(null);
        $result = $this->resumeLongRunner($result);
        $this->assertEmailNotSentTo($digestEnabledUserWithNoFollowed["email"]);
        $this->assertEmailSentTo($digestEnabledUserWithNoFollowedDupe["email"]);

        $this->assertEquals(null, $result["callbackPayload"]);
    }

    /**
     * Enable a digest for the user.
     *
     * @param array $user
     * @return void
     */
    private function enableDigestForUser(array $user)
    {
        $metaModel = \Gdn::userMetaModel();
        $metaModel->setUserMeta($user["userID"], "Preferences.Email.DigestEnabled", 1);
    }

    /**
     * Set language preference for a user
     *
     * @param int $userID
     * @param string $language
     * @return void
     */
    private function enableUserLanguagePreference(int $userID, string $language)
    {
        $metaModel = \Gdn::userMetaModel();
        $metaModel->setUserMeta($userID, "Preferences.NotificationLanguage", $language);
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
        $usersWithOutPreference = $this->emailDigestGenerator->getDigestUserCategoriesIterator([]);
        ModelUtils::consumeGenerator($usersWithOutPreference);
        $this->assertLogMessage("Could not find any user roles to process with 'Garden.Email.View' Permission.");
    }

    /**
     * Test user language preference for user
     *
     * @return void
     */
    public function testGetUserLanguagePreference(): void
    {
        $user = $this->createUser();
        $this->assertEquals("en", $this->emailDigestGenerator->getUserLanguagePreference($user["userID"]));
        $this->enableUserLanguagePreference($user["userID"], "fr");
        $this->assertEquals("en", $this->emailDigestGenerator->getUserLanguagePreference($user["userID"]));
        $this->enableLocales();
        $this->assertEquals("fr", $this->emailDigestGenerator->getUserLanguagePreference($user["userID"]));
        $this->enableUserLanguagePreference($user["userID"], "es");
        $this->assertEquals("en", $this->emailDigestGenerator->getUserLanguagePreference($user["userID"]));
        $this->assertLogMessage("The preferred language chose by user is not currently active.");
    }

    /**
     * Enables locales for the site
     *
     * @return void
     */
    private function enableLocales()
    {
        self::$enabledLocales = ["vf_fr" => "fr", "vf_he" => "he", "vf_ru" => "ru"];
        self::preparelocales();
    }

    /**
     * Test that digestCategory for user contains user language preference
     *
     * @return void
     */
    public function testCategoryIteratorHasUserLanguagePreference(): void
    {
        $this->resetTable("UserCategory");

        $cat1 = $this->createCategory(["name" => "Cat 1"]);
        $this->createDiscussion(["name" => "Cat 1 discussion"]);
        $cat2 = $this->createCategory(["name" => "Cat 2"]);
        $this->createDiscussion(["name" => "Cat 2 discussion"]);

        $digestUser = $this->createUser();
        $this->enableDigestForUser($digestUser);
        $this->setCategoryPreference($digestUser, $cat1, $this->followingPreferences(true));
        $this->setCategoryPreference($digestUser, $cat2, $this->followingPreferences(true));

        $digestUserCategory = iterator_to_array(
            $this->emailDigestGenerator->getDigestUserCategoriesIterator([
                "um.userID" => $digestUser["userID"],
            ])
        )[$digestUser["userID"]];

        $this->assertArrayHasKey("digestLanguage", $digestUserCategory);
        //should get the default language for site as the user doesn't have a language preference set
        $this->assertEquals("en", $digestUserCategory["digestLanguage"]);
        $this->enableLocales();

        //Set user language preference to french
        $this->enableUserLanguagePreference($digestUser["userID"], "fr");

        $digestUserCategory = iterator_to_array(
            $this->emailDigestGenerator->getDigestUserCategoriesIterator([
                "um.userID" => $digestUser["userID"],
            ])
        )[$digestUser["userID"]];
        $this->assertEquals("fr", $digestUserCategory["digestLanguage"]);
    }

    /**
     * Test that a user with no digest content creates an entry in user digest table with skipped status
     *
     * @return void
     */
    public function testCreateUserDigestInsertsSkippedUserRecord()
    {
        $user = $this->createUser();
        $category = $this->createCategory();
        $this->setCategoryPreference($user, $category, $this->followingPreferences(true));
        $this->runWithExpectedException(ServerException::class, function () use ($user) {
            $this->emailDigestGenerator->prepareSingleUserDigest($user["userID"]);
        });
        $this->assertLogMessage("Skipped generating digest for user because there was no discussions visible to them.");
        $userDigestData = $this->userDigestModel->selectSingle(["userID" => $user["userID"]]);
        $this->assertEquals(-1, $userDigestData["digestContentID"]);
        $this->assertEquals("skipped", $userDigestData["status"]);
    }

    /**
     * Test for discussion meta settings
     *
     * @return void
     */
    public function testGetDiscussionMetaSettings(): void
    {
        $discussionMetaSettings = $this->emailDigestGenerator->getDiscussionMetaSettings();
        $expected = [
            "imageEnabled" => false,
            "authorEnabled" => true,
            "viewCountEnabled" => true,
            "commentCountEnabled" => true,
            "scoreCountEnabled" => true,
        ];
        $this->assertEquals($expected, $discussionMetaSettings);
        $config = [
            "Garden.Digest.ImageEnabled" => false,
            "Garden.Digest.AuthorEnabled" => false,
            "Garden.Digest.ViewCountEnabled" => true,
            "Garden.Digest.CommentCountEnabled" => true,
            "Garden.Digest.ScoreCountEnabled" => false,
        ];

        $this->runWithConfig($config, function () {
            $expected = [
                "imageEnabled" => false,
                "authorEnabled" => false,
                "viewCountEnabled" => true,
                "commentCountEnabled" => true,
                "scoreCountEnabled" => false,
            ];
            $discussionMetaSettings = $this->emailDigestGenerator->getDiscussionMetaSettings();
            $this->assertEquals($expected, $discussionMetaSettings);
        });
    }
}
