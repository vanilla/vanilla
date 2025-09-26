<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Forum\Digest;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ServerException;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use Vanilla\Forum\Digest\DigestModel;
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
    private UserNotificationPreferencesModel $userNotificationPreferencesModel;
    private DigestModel $digestModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->userDigestModel = \Gdn::getContainer()->get(UserDigestModel::class);
        $this->categoryModel = \Gdn::getContainer()->get(\CategoryModel::class);
        $this->digestModel = $this->container()->get(DigestModel::class);
        $this->emailDigestGenerator = \Gdn::getContainer()->get(EmailDigestGenerator::class);
        $this->userNotificationPreferencesModel = $this->container()->get(UserNotificationPreferencesModel::class);
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
        $digestCategoryData = $this->emailDigestGenerator->getDigestData($digestUserCategory);

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
                "digestSubscribeReason" => "*/digest_subscribe_reason/*",
                "digestUnsubscribeLink" => "*/digest_unsubscribe/*",
                "notificationPreferenceLink" => url("/profile/preferences", true),
                "imageUrl" => $config["Garden.EmailTemplate.Image"],
                "imageAlt" => \Gdn::config()->get("Garden.Title", "Vanilla Forums Digest"),
                "textColor" => $config["Garden.EmailTemplate.TextColor"],
                "backgroundColor" => $config["Garden.EmailTemplate.BackgroundColor"],
                "buttonTextColor" => $config["Garden.EmailTemplate.ButtonTextColor"],
                "buttonBackgroundColor" => $config["Garden.EmailTemplate.ButtonBackgroundColor"],
                "containerBackgroundColor" => $config["Garden.EmailTemplate.ContainerBackgroundColor"],
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
        $this->assertEmpty($this->emailDigestGenerator->getTopDiscussions([999]));
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
        $trendingDiscussionWithoutUnsubscribeLink = $this->emailDigestGenerator->getTopDiscussions(
            [$categoryIDs[0]],
            false
        );
        $this->assertArrayNotHasKey("unsubscribeLink", $trendingDiscussionWithoutUnsubscribeLink);
        $trendingDiscussion = $this->emailDigestGenerator->getTopDiscussions($categoryIDs);
        $countDiscussions = 0;
        $c = 1;
        $categoryColumns = ["name", "url", "iconUrl", "unsubscribeLink", "discussions"];
        foreach ($trendingDiscussion["Category"] as $categoryID => $categoryData) {
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

        $action = $this->emailDigestGenerator->prepareDigestAction(
            CurrentTimeStamp::getDateTime(),
            DigestModel::DIGEST_TYPE_WEEKLY
        );
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
     * Disable all current users Digest preference
     *
     * @return void
     * @throws \Throwable
     */
    private function disableDigestForCurrentUsers(): void
    {
        $metaModel = \Gdn::userMetaModel();
        $UserIDs = $this->userModel
            ->createSql()
            ->select("UserID")
            ->from("User")
            ->get()
            ->column("UserID");
        $metaModel->setUserMeta($UserIDs, "Preferences.Email.DigestEnabled", null);
    }

    /**
     * Autosubscribe a user to the digest.
     *
     * @param array $user
     * @return void
     */
    private function enableAutosubscribeForUser(array $user)
    {
        $metaModel = \Gdn::userMetaModel();
        $metaModel->setUserMeta($user["userID"], "Preferences.Email.DigestEnabled", 3);
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
     * Test that a user who is auto-subscribed to the digest gets the digest, but only the default auto-subscribe preference is set.
     *
     * @return void
     */
    public function testAutosubscribedUserGetsDigest(): void
    {
        $this->resetTable("UserCategory");
        $this->resetTable("UserMeta");

        $cat1 = $this->createCategory(["name" => "Cat 1"]);
        $this->createDiscussion(["name" => "Cat 1 discussion"]);
        $cat2 = $this->createCategory(["name" => "Cat 2"]);
        $this->createDiscussion(["name" => "Cat 2 discussion"]);

        $digestUser = $this->createUser();
        $this->enableAutosubscribeForUser($digestUser);
        $this->setCategoryPreference($digestUser, $cat1, $this->followingPreferences(true));
        $this->setCategoryPreference($digestUser, $cat2, $this->followingPreferences(true));

        // Auto-subscribe is disabled. User should not get the digest even if they have the preference set to auto-subscribe.
        $this->runWithConfig([DigestModel::AUTOSUBSCRIBE_DEFAULT_PREFERENCE => 0], function () use ($digestUser) {
            $action = $this->emailDigestGenerator->prepareDigestAction(
                CurrentTimeStamp::getDateTime(),
                DigestModel::DIGEST_TYPE_WEEKLY
            );
            $result = $this->getLongRunner()->runImmediately($action);
            $this->assertEquals(0, $result->getCountTotalIDs());
            $this->assertEmailNotSentTo($digestUser["email"]);
        });

        // Auto-subscribe is enabled. User should get the digest.
        $this->runWithConfig([DigestModel::AUTOSUBSCRIBE_DEFAULT_PREFERENCE => 1], function () use ($digestUser) {
            $action = $this->emailDigestGenerator->prepareDigestAction(
                CurrentTimeStamp::getDateTime(),
                DigestModel::DIGEST_TYPE_WEEKLY
            );
            $result = $this->getLongRunner()->runImmediately($action);
            $this->assertEquals(1, $result->getCountTotalIDs());
            $this->assertEmailSentTo($digestUser["email"]);
        });
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
     * Test that the digest subscribe reason reflects the user's `Email.DigestEnabled` preference.
     *
     * @return void
     */
    public function testSubscribeReasonMessage(): void
    {
        $autoSubscribedUser = $this->createUser();
        $optedInUser = $this->createUser();
        $this->resetTable("UserCategory");

        $cat1 = $this->createCategory(["name" => "Cat 1"]);
        $this->createDiscussion(["name" => "Cat 1 discussion"]);
        $cat2 = $this->createCategory(["name" => "Cat 2"]);
        $this->createDiscussion(["name" => "Cat 2 discussion"]);

        $this->enableDigestForUser($autoSubscribedUser);
        $this->setCategoryPreference($autoSubscribedUser, $cat1, $this->followingPreferences(true));
        $this->setCategoryPreference($autoSubscribedUser, $cat2, $this->followingPreferences(true));
        $this->userNotificationPreferencesModel->save($autoSubscribedUser["userID"], ["Email.DigestEnabled" => 3]);

        $this->enableDigestForUser($optedInUser);
        $this->setCategoryPreference($optedInUser, $cat1, $this->followingPreferences(true));
        $this->setCategoryPreference($optedInUser, $cat2, $this->followingPreferences(true));

        $this->userNotificationPreferencesModel->save($optedInUser["userID"], ["Email.DigestEnabled" => 1]);

        // Message should reflect that the user is auto-subscribed.
        $autoEmail = $this->emailDigestGenerator->prepareSingleUserDigest($autoSubscribedUser["userID"]);
        $this->assertStringContainsString(
            "You are receiving this email because you were opted in to receive email digests.",
            $autoEmail->getHtmlContent()
        );

        $this->assertStringContainsString(
            "You are receiving this email because you were opted in to receive email digests.",
            $autoEmail->getTextContent()
        );

        // Message should reflect that the user opted in.
        $optedInEmail = $this->emailDigestGenerator->prepareSingleUserDigest($optedInUser["userID"]);
        $this->assertStringContainsString(
            "You are receiving this email because you opted in to receive email digests.",
            $optedInEmail->getHtmlContent()
        );

        $this->assertStringContainsString(
            "You are receiving this email because you opted in to receive email digests.",
            $optedInEmail->getTextContent()
        );
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

    /**
     * Test we get proper digest user count
     *
     * @return void
     */
    public function testDigestEnabledUserCount()
    {
        $this->disableDigestForCurrentUsers();
        $this->runWithConfig(
            [
                DigestModel::AUTOSUBSCRIBE_DEFAULT_PREFERENCE => 1,
                DigestModel::DEFAULT_DIGEST_FREQUENCY_KEY => DigestModel::DIGEST_TYPE_WEEKLY,
            ],
            function () {
                $preferences = [
                    "DigestEnabled" => ["Email" => 1, "Frequency" => DigestModel::DIGEST_TYPE_WEEKLY],
                ];
                $this->createUser(["name" => "UserA"], [], $preferences);
                $preferences["DigestEnabled"]["Frequency"] = DigestModel::DIGEST_TYPE_DAILY;
                $this->createUser(["name" => "UserB"], [], $preferences);
                $preferences["DigestEnabled"]["Frequency"] = DigestModel::DIGEST_TYPE_MONTHLY;
                $this->createUser(["name" => "UserC"], [], $preferences);
                $preferences = [
                    "DigestEnabled" => ["Email" => 3, "Frequency" => DigestModel::DIGEST_TYPE_MONTHLY],
                ];
                $this->createUser(["name" => "UserD"], [], $preferences);

                // Now create some users with no digest frequency preference.
                for ($i = 1; $i <= 5; $i++) {
                    $this->createUser(["name" => "User-{$i}"], [], ["DigestEnabled" => ["Email" => 1]]);
                }

                $this->assertEquals(9, $this->emailDigestGenerator->getDigestEnabledUsersCount());
                $this->assertEquals(
                    6,
                    $this->emailDigestGenerator->getDigestEnabledUsersCount(DigestModel::DIGEST_TYPE_WEEKLY)
                );
                $this->assertEquals(
                    1,
                    $this->emailDigestGenerator->getDigestEnabledUsersCount(DigestModel::DIGEST_TYPE_DAILY)
                );
                $this->assertEquals(
                    2,
                    $this->emailDigestGenerator->getDigestEnabledUsersCount(DigestModel::DIGEST_TYPE_MONTHLY)
                );
            }
        );
    }

    /**
     * Test sending email digests to users with different frequency preferences.
     *
     * @return void
     */
    public function testGenerateEmailFrequency(): void
    {
        $this->runWithConfig(
            [
                DigestModel::DEFAULT_DIGEST_FREQUENCY_KEY => DigestModel::DIGEST_TYPE_MONTHLY,
            ],
            function () {
                // Set up 4 users with different digest frequencies who all follow a certain category.
                $followedCat = $this->createCategory();
                $preferences = [
                    "DigestEnabled" => ["Email" => 1, "Frequency" => DigestModel::DIGEST_TYPE_DAILY],
                ];
                $dailyUser = $this->createUser([], [], $preferences);
                $this->setCategoryPreference($dailyUser, $followedCat, self::followingPreferences());

                $preferences["DigestEnabled"]["Frequency"] = DigestModel::DIGEST_TYPE_WEEKLY;
                $weeklyUser = $this->createUser([], [], $preferences);
                $this->setCategoryPreference($weeklyUser, $followedCat, self::followingPreferences());

                $preferences["DigestEnabled"]["Frequency"] = DigestModel::DIGEST_TYPE_MONTHLY;
                $monthlyUser = $this->createUser([], [], $preferences);
                $this->setCategoryPreference($monthlyUser, $followedCat, self::followingPreferences());

                unset($preferences["DigestEnabled"]["Frequency"]);
                $defaultUser = $this->createUser([], [], $preferences);
                $this->setCategoryPreference($defaultUser, $followedCat, self::followingPreferences());

                $todayDiscussion = $this->createDiscussion(["categoryID" => $followedCat["categoryID"]]);

                CurrentTimeStamp::mockTime(strtotime("-2 days"));
                $twoDaysAgoDiscussion = $this->createDiscussion(["categoryID" => $followedCat["categoryID"]]);

                CurrentTimeStamp::mockTime(strtotime("-2 weeks"));
                $twoWeeksAgoDiscussion = $this->createDiscussion(["categoryID" => $followedCat["categoryID"]]);

                CurrentTimeStamp::clearMockTime();

                // A user with the daily digest preference should only see the most recent discussion.
                $dailyDigest = $this->emailDigestGenerator->prepareSingleUserDigest($dailyUser["userID"]);
                $this->assertStringContainsString($todayDiscussion["url"], $dailyDigest->getTextContent());
                $this->assertStringNotContainsString($twoDaysAgoDiscussion["url"], $dailyDigest->getTextContent());
                $this->assertStringNotContainsString($twoWeeksAgoDiscussion["url"], $dailyDigest->getTextContent());
                // The title should reflect the correct frequency.
                $this->assertStringContainsString("Today's trending content", $dailyDigest->getTextContent());

                // A user with the weekly digest preference should see the most recent two discussions, because
                // they were sent within the last week, but not the third.
                $weeklyDigest = $this->emailDigestGenerator->prepareSingleUserDigest($weeklyUser["userID"]);
                $this->assertStringContainsString($todayDiscussion["url"], $weeklyDigest->getTextContent());
                $this->assertStringContainsString($twoDaysAgoDiscussion["url"], $weeklyDigest->getTextContent());
                $this->assertStringNotContainsString($twoWeeksAgoDiscussion["url"], $weeklyDigest->getTextContent());
                // The title should reflect the correct frequency.
                $this->assertStringContainsString("This week's trending content", $weeklyDigest->getTextContent());

                // A user with the monthly digest preference should see all three discussions.
                $monthlyDigest = $this->emailDigestGenerator->prepareSingleUserDigest($monthlyUser["userID"]);
                $this->assertStringContainsString($todayDiscussion["url"], $monthlyDigest->getTextContent());
                $this->assertStringContainsString($twoDaysAgoDiscussion["url"], $monthlyDigest->getTextContent());
                $this->assertStringContainsString($twoWeeksAgoDiscussion["url"], $monthlyDigest->getTextContent());
                // The title should reflect the correct frequency.
                $this->assertStringContainsString("This month's trending content", $monthlyDigest->getTextContent());

                // A user with the default digest preference should see all three discussions because the default is set to monthly.
                $defaultDigest = $this->emailDigestGenerator->prepareSingleUserDigest($defaultUser["userID"]);
                $this->assertStringContainsString($todayDiscussion["url"], $defaultDigest->getTextContent());
                $this->assertStringContainsString($twoDaysAgoDiscussion["url"], $defaultDigest->getTextContent());
                $this->assertStringContainsString($twoWeeksAgoDiscussion["url"], $defaultDigest->getTextContent());
                // The title should reflect the default frequency.
                $this->assertStringContainsString("This month's trending content", $defaultDigest->getTextContent());
            }
        );
    }

    /*
     * Test that a user with an unconfirmed email won't get the digest.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testUnconfirmedEmailUser(): void
    {
        $this->disableDigestForCurrentUsers();
        $category = $this->createCategory();
        $this->createDiscussion();

        $unconfirmedUser = $this->createUser();
        $this->enableDigestForUser($unconfirmedUser);
        $this->setCategoryPreference($unconfirmedUser, $category, $this->followingPreferences());
        $this->api()->patch("/users/{$unconfirmedUser["userID"]}", ["emailConfirmed" => false]);

        $confirmedUser = $this->createUser();
        $this->enableDigestForUser($confirmedUser);
        $this->setCategoryPreference($confirmedUser, $category, $this->followingPreferences());

        $action = $this->emailDigestGenerator->prepareDigestAction(
            CurrentTimeStamp::getDateTime(),
            DigestModel::DIGEST_TYPE_WEEKLY
        );
        $result = $this->getLongRunner()->runImmediately($action);
        $this->assertEquals(1, $result->getCountTotalIDs());
        $this->assertEmailNotSentTo($unconfirmedUser["email"]);
        $this->assertEmailSentTo($confirmedUser["email"]);
    }

    /**
     * Test daily digest scheduled action
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testPrepareDailyDigestAction(): void
    {
        $this->resetTable("UserCategory");
        $this->runWithConfig(
            [
                DigestModel::DEFAULT_DIGEST_FREQUENCY_KEY => DigestModel::DIGEST_TYPE_WEEKLY,
            ],
            function () {
                // Create some categories and discussions.
                $cat1 = $this->createCategory(["name" => "Digest Category A"]);
                $this->createDiscussion([
                    "name" => "Digest Discussion A",
                    "categoryID" => $cat1["categoryID"],
                    "body" => "This is a discussion happening in Digest Category A",
                ]);
                $cat2 = $this->createCategory(["name" => "Digest Category B"]);
                $this->createDiscussion([
                    "name" => "Digest Discussion B",
                    "categoryID" => $cat2["categoryID"],
                    "body" => "This is a discussion happening in Digest Category B",
                ]);
                $cat3 = $this->createCategory(["name" => "Digest Category C"]);
                $this->createDiscussion([
                    "name" => "Digest Discussion C",
                    "categoryID" => $cat3["categoryID"],
                    "body" => "This is a discussion happening in Digest Category C",
                ]);
                $cat4 = $this->createCategory(["name" => "Digest Category D"]);

                // Create some users with frequency preferences.
                $userPreferences = [
                    "DigestEnabled" => ["Email" => 1, "Frequency" => DigestModel::DIGEST_TYPE_DAILY],
                ];
                $dailyUserA = $this->createUser(["name" => "DigestUserA"], [], $userPreferences);
                $this->setCategoryPreference($dailyUserA, $cat1, self::followingPreferences());
                $this->setCategoryPreference($dailyUserA, $cat2, self::followingPreferences());

                $dailyUserB = $this->createUser(["name" => "DigestUserB"], [], $userPreferences);
                $this->setCategoryPreference($dailyUserB, $cat3, self::followingPreferences());

                $dailyUserC = $this->createUser(["name" => "DigestUserC"], [], $userPreferences);
                $this->setCategoryPreference($dailyUserC, $cat4, self::followingPreferences());

                // Create a user with no frequency preference.
                unset($userPreferences["DigestEnabled"]["Frequency"]);
                $defaultUser = $this->createUser(["name" => "DigestUserD"], [], $userPreferences);

                $currentTimeStamp = CurrentTimeStamp::getDateTime();

                $action = $this->emailDigestGenerator->prepareDigestAction(
                    $currentTimeStamp,
                    DigestModel::DIGEST_TYPE_DAILY
                );

                $this->assertTrue($this->digestModel->checkIfDigestScheduledForDay($currentTimeStamp));
                $digestData = $this->digestModel->selectSingle(
                    [
                        "digestType" => DigestModel::DIGEST_TYPE_DAILY,
                    ],
                    [DigestModel::OPT_ORDER => "dateInserted", DigestModel::OPT_DIRECTION => "desc"]
                );
                $this->assertEquals(3, $digestData["totalSubscribers"]);
                $result = $this->getLongRunner()->runImmediately($action);
                $this->assertEquals(3, $result->getCountTotalIDs());
                // Test email sent to users with daily digest preference.
                $digestEmailA = $this->assertEmailSentTo($dailyUserA["email"]);
                $emailHtml = $digestEmailA->getHtmlDocument();
                $emailHtml->assertContainsString($cat1["name"]);
                $emailHtml->assertContainsString($cat2["name"]);
                $digestEmailB = $this->assertEmailSentTo($dailyUserB["email"]);
                $emailHtml = $digestEmailB->getHtmlDocument();
                $emailHtml->assertContainsString($cat3["name"]);
                $this->assertEmailNotSentTo($dailyUserC["email"]);
                $log = $this->assertLog(["event" => "user_digest_skip"]);
                $this->assertEquals($dailyUserC["userID"], $log["data"]["UserID"]);
                $this->assertEquals(
                    "Skipped generating digest for user because there was no discussions visible to them.",
                    $log["message"]
                );
            }
        );
    }

    /**
     *  Test Monthly digest scheduled action
     */
    public function testPrepareMonthlyDigestAction()
    {
        $this->resetTable("UserCategory");
        $this->runWithConfig(
            [
                DigestModel::DEFAULT_DIGEST_FREQUENCY_KEY => DigestModel::DIGEST_TYPE_WEEKLY,
            ],
            function () {
                $currentTimeStamp = CurrentTimeStamp::getDateTime();

                //Get a time stamp 1 month and 5 days back
                $lastMonthTimeStamp = $currentTimeStamp->modify("-1 month -5 day");

                // Set the time stamp to create discussion so that they don't show up in the monthly digest
                CurrentTimeStamp::mockTime($lastMonthTimeStamp);
                // create some categories and discussions
                $cat1 = $this->createCategory(["name" => "Digest Monthly Category A"]);
                $this->createDiscussion([
                    "name" => "Digest Monthly Discussion A",
                    "categoryID" => $cat1["categoryID"],
                    "body" => "This is a discussion happening in Digest Monthly Category A",
                ]);
                $cat2 = $this->createCategory(["name" => "Digest Monthly Category B"]);
                $this->createDiscussion([
                    "name" => "Digest Monthly Discussion B",
                    "categoryID" => $cat2["categoryID"],
                    "body" => "This is a discussion happening in Digest Monthly Category B",
                ]);
                $userPreferences = [
                    "DigestEnabled" => ["Email" => 1, "Frequency" => DigestModel::DIGEST_TYPE_MONTHLY],
                ];
                $userA = $this->createUser(["name" => "DigestMonthlyUserA"], [], $userPreferences);
                $this->setCategoryPreference($userA, $cat1, self::followingPreferences());
                $this->setCategoryPreference($userA, $cat2, self::followingPreferences());

                $userB = $this->createUser(["name" => "DigestMonthlyUserB"], [], $userPreferences);
                $this->setCategoryPreference($userB, $cat2, self::followingPreferences());

                $userC = $this->createUser(["name" => "DigestMonthlyUserC"], [], $userPreferences);
                $this->setCategoryPreference($userC, $cat1, self::followingPreferences(false));

                // 10 days back
                CurrentTimeStamp::mockTime($currentTimeStamp->modify("-10 days"));
                $this->createDiscussion([
                    "name" => "Latest Digest Monthly Discussion",
                    "categoryID" => $cat1["categoryID"],
                    "body" => "This is the very latest discussion happening in Digest Monthly Category A",
                ]);

                CurrentTimeStamp::clearMockTime();
                $currentTime = CurrentTimeStamp::getDateTime();
                $action = $this->emailDigestGenerator->prepareDigestAction(
                    $currentTime,
                    DigestModel::DIGEST_TYPE_MONTHLY
                );
                $this->assertTrue(
                    $this->digestModel->checkIfDigestScheduledForDay($currentTime, DigestModel::DIGEST_TYPE_MONTHLY)
                );
                $digestData = $this->digestModel->selectSingle(
                    [
                        "digestType" => DigestModel::DIGEST_TYPE_MONTHLY,
                    ],
                    [DigestModel::OPT_ORDER => "dateInserted", DigestModel::OPT_DIRECTION => "desc"]
                );
                $digestID = $digestData["digestID"];
                $this->assertEquals(3, $digestData["totalSubscribers"]);
                $result = $this->getLongRunner()->runImmediately($action);
                $this->assertEquals(3, $result->getCountTotalIDs());

                $digestResult = $this->userDigestModel->select(["digestID" => $digestID]);
                $this->assertEquals(3, count($digestResult));
                $digestResult = array_column($digestResult, "status", "userID");
                $this->assertEquals("sent", $digestResult[$userA["userID"]]);
                $this->assertEquals("skipped", $digestResult[$userB["userID"]]);
                $this->assertEquals("sent", $digestResult[$userC["userID"]]);

                $digestEmailA = $this->assertEmailSentTo($userA["email"]);
                $this->assertEmailNotSentTo($userB["email"]);
                $this->assertEmailSentTo($userC["email"]);

                $emailHtml = $digestEmailA->getHtmlDocument();
                $emailHtml->assertContainsString($cat1["name"]);
                $emailHtml->assertNotContainsString($cat2["name"]);
                $emailHtml->assertNotContainsString("Digest Monthly Discussion A");
                $emailHtml->assertNotContainsString("Digest Monthly Discussion B");
                $emailHtml->assertContainsString("Latest Digest Monthly Discussion");

                $log = $this->assertLog(["event" => "user_digest_skip"]);
                $this->assertEquals($userB["userID"], $log["data"]["UserID"]);
            }
        );
    }
}
