<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace APIv2;

use Vanilla\Dashboard\Models\InterestModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Models\TestDiscussionModelTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/discussions endpoints.
 */
class DiscussionsSuggestionsTest extends SiteTestCase
{
    use TestDiscussionModelTrait;
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;

    /** @var array */
    private static $categoryIDs = [];

    /**
     * @var array
     */
    private static $data = [];

    protected static $addons = ["QnA", "stubcontent", "test-mock-issue"];

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = "")
    {
        $this->baseUrl = "/discussions";
        $this->resourceName = "discussion";

        $this->patchFields = ["body", "categoryID", "closed", "format", "name", "pinLocation", "pinned", "sink"];
        $this->sortFields = ["dateLastComment", "dateInserted", "discussionID"];

        parent::__construct($name, $data, $dataName);
    }

    /**
     * Test getting a list of discussions from interest suggestions.
     */
    public function testIndexSuggestions()
    {
        self::resetTable("Discussion");
        self::resetTable("Tag");
        self::resetTable("TagDiscussion");
        self::resetTable("interest");
        $this->enableFeature(InterestModel::SUGGESTED_CONTENT_FEATURE_FLAG);

        // Make sure we have a post we aren't following
        $this->createDiscussion();
        $this->runWithConfig([InterestModel::CONF_SUGGESTED_CONTENT_ENABLED => true], function () {
            // Make sure we're starting from scratch.
            $preSuggestions = $this->api()
                ->get("/discussions", ["suggestions" => true])
                ->getBody();
            $this->assertEmpty($preSuggestions);

            $user = $this->createUser();

            $testProfileField = $this->createProfileField(["dataType" => "boolean", "formType" => "checkbox"]);

            $discussions = $this->insertDiscussions(11);

            $category1 = $this->createCategory();
            $category1Discussions = array_column(
                $this->insertDiscussions(5, ["CategoryID" => $category1["categoryID"]]),
                "DiscussionID"
            );
            $category2 = $this->createCategory();
            $category2Discussions = array_column(
                $this->insertDiscussions(5, ["CategoryID" => $category2["categoryID"]]),
                "DiscussionID"
            );

            $tagID1 = $this->createTag();
            $tagID2 = $this->createTag();
            for ($i = 0; $i < 5; $i++) {
                $this->api()->post("discussions/{$discussions[$i]["DiscussionID"]}/tags", [
                    "urlcodes" => [$tagID1["urlcode"]],
                    "tagIDs" => [$tagID1["tagID"]],
                ]);
                $category1Discussions[] = $discussions[$i]["DiscussionID"];
            }
            for ($i = 5; $i < 10; $i++) {
                $this->api()->post("discussions/{$discussions[$i]["DiscussionID"]}/tags", [
                    "urlcodes" => [$tagID2["urlcode"]],
                    "tagIDs" => [$tagID2["tagID"]],
                ]);
                $category2Discussions[] = $discussions[$i]["DiscussionID"];
            }

            // Create interest associated with profile fields.
            $this->createInterest([
                "name" => "test",
                "apiName" => "test",
                "categoryIDs" => [$category1["categoryID"]],
                "tagIDs" => [$tagID1["tagID"]],
                "profileFieldMapping" => [
                    $testProfileField["apiName"] => true,
                ],
            ]);

            // Create default interest.
            $this->createInterest([
                "name" => "test-2",
                "apiName" => "test-2",
                "categoryIDs" => [$category2["categoryID"]],
                "tagIDs" => [$tagID2["tagID"]],
                "isDefault" => true,
            ]);

            $this->runWithUser(function () use (
                $user,
                $testProfileField,
                $category1Discussions,
                $category2Discussions
            ) {
                $suggested = $this->api()
                    ->get("/discussions", ["suggestions" => true])
                    ->getBody();

                // Should have one category which is from the default interest.
                $this->assertCount(10, $suggested);
                $this->assertSame($category2Discussions, array_column($suggested, "discussionID"));
                $this->api()->patch("/users/{$user["userID"]}/profile-fields", [
                    $testProfileField["apiName"] => true,
                ]);

                $suggested = $this->api()
                    ->get("/discussions", ["suggestions" => true])
                    ->getBody();

                // Should have the category for the default interest and the one based on filters.
                $this->assertCount(20, $suggested);
                $discussions = array_merge($category1Discussions, $category2Discussions);
                rsort($discussions, SORT_NUMERIC);
                $this->assertSame($discussions, array_column($suggested, "discussionID"));
            },
            $user);
        });
    }

    /**
     * Test getting a list of discussions from interest suggestion, only category set for interests.
     */
    public function testIndexSuggestionsCategoryOnly()
    {
        self::resetTable("Discussion");
        self::resetTable("Tag");
        self::resetTable("TagDiscussion");
        self::resetTable("interest");
        $this->enableFeature(InterestModel::SUGGESTED_CONTENT_FEATURE_FLAG);

        // Make sure we have a post we aren't following
        $this->createDiscussion();
        $this->runWithConfig([InterestModel::CONF_SUGGESTED_CONTENT_ENABLED => true], function () {
            // Make sure we're starting from scratch.
            $preSuggestions = $this->api()
                ->get("/discussions", ["suggestions" => true])
                ->getBody();
            $this->assertEmpty($preSuggestions);

            $user = $this->createUser();

            $testProfileField = $this->createProfileField(["dataType" => "boolean", "formType" => "checkbox"]);

            $discussions = $this->insertDiscussions(11);

            $category1 = $this->createCategory();
            $category1Discussions = array_column(
                $this->insertDiscussions(5, ["CategoryID" => $category1["categoryID"]]),
                "DiscussionID"
            );
            $category2 = $this->createCategory();
            $category2Discussions = array_column(
                $this->insertDiscussions(5, ["CategoryID" => $category2["categoryID"]]),
                "DiscussionID"
            );

            $tagID1 = $this->createTag();
            $tagID2 = $this->createTag();
            for ($i = 0; $i < 5; $i++) {
                $this->api()->post("discussions/{$discussions[$i]["DiscussionID"]}/tags", [
                    "urlcodes" => [$tagID1["urlcode"]],
                    "tagIDs" => [$tagID1["tagID"]],
                ]);
            }
            for ($i = 5; $i < 10; $i++) {
                $this->api()->post("discussions/{$discussions[$i]["DiscussionID"]}/tags", [
                    "urlcodes" => [$tagID2["urlcode"]],
                    "tagIDs" => [$tagID2["tagID"]],
                ]);
            }

            // Create interest associated with profile fields.
            $this->createInterest([
                "name" => "test",
                "apiName" => "test",
                "categoryIDs" => [$category1["categoryID"]],
                "profileFieldMapping" => [
                    $testProfileField["apiName"] => true,
                ],
            ]);

            // Create default interest.
            $this->createInterest([
                "name" => "test-2",
                "apiName" => "test-2",
                "categoryIDs" => [$category2["categoryID"]],
                "isDefault" => true,
            ]);

            $this->runWithUser(function () use (
                $user,
                $testProfileField,
                $category1Discussions,
                $category2Discussions
            ) {
                $suggested = $this->api()
                    ->get("/discussions", ["suggestions" => true])
                    ->getBody();

                // Should have one category which is from the default interest.
                $this->assertCount(5, $suggested);
                $this->assertSame($category2Discussions, array_column($suggested, "discussionID"));
                $this->api()->patch("/users/{$user["userID"]}/profile-fields", [
                    $testProfileField["apiName"] => true,
                ]);

                $suggested = $this->api()
                    ->get("/discussions", ["suggestions" => true])
                    ->getBody();

                // Should have the category for the default interest and the one based on filters.
                $this->assertCount(10, $suggested);
                $discussions = array_merge($category1Discussions, $category2Discussions);
                rsort($discussions, SORT_NUMERIC);
                $this->assertSame($discussions, array_column($suggested, "discussionID"));
            },
            $user);
        });
    }

    /**
     * Test getting a list of discussions from interest suggestions, only tags set for interests
     */
    public function testIndexSuggestionsTagsOnly()
    {
        self::resetTable("Discussion");
        self::resetTable("Tag");
        self::resetTable("TagDiscussion");
        self::resetTable("interest");
        $this->enableFeature(InterestModel::SUGGESTED_CONTENT_FEATURE_FLAG);

        // Make sure we have a post we aren't following
        $this->createDiscussion();
        $this->runWithConfig([InterestModel::CONF_SUGGESTED_CONTENT_ENABLED => true], function () {
            // Make sure we're starting from scratch.
            $preSuggestions = $this->api()
                ->get("/discussions", ["suggestions" => true])
                ->getBody();
            $this->assertEmpty($preSuggestions);

            $user = $this->createUser();

            $testProfileField = $this->createProfileField(["dataType" => "boolean", "formType" => "checkbox"]);

            $discussions = $this->insertDiscussions(11);

            $category1 = $this->createCategory();
            $category1Discussions = [];
            $this->insertDiscussions(5, ["CategoryID" => $category1["categoryID"]]);
            $category2 = $this->createCategory();
            $category2Discussions = [];
            $this->insertDiscussions(5, ["CategoryID" => $category2["categoryID"]]);

            $tagID1 = $this->createTag();
            $tagID2 = $this->createTag();
            for ($i = 0; $i < 5; $i++) {
                $this->api()->post("discussions/{$discussions[$i]["DiscussionID"]}/tags", [
                    "urlcodes" => [$tagID1["urlcode"]],
                    "tagIDs" => [$tagID1["tagID"]],
                ]);
                $category1Discussions[] = $discussions[$i]["DiscussionID"];
            }
            for ($i = 5; $i < 10; $i++) {
                $this->api()->post("discussions/{$discussions[$i]["DiscussionID"]}/tags", [
                    "urlcodes" => [$tagID2["urlcode"]],
                    "tagIDs" => [$tagID2["tagID"]],
                ]);
                $category2Discussions[] = $discussions[$i]["DiscussionID"];
            }

            // Create interest associated with profile fields.
            $this->createInterest([
                "name" => "test",
                "apiName" => "test",
                "categoryIDs" => [],
                "tagIDs" => [$tagID1["tagID"]],
                "profileFieldMapping" => [
                    $testProfileField["apiName"] => true,
                ],
            ]);

            // Create default interest.
            $this->createInterest([
                "name" => "test-2",
                "apiName" => "test-2",
                "categoryIDs" => [],
                "tagIDs" => [$tagID2["tagID"]],
                "isDefault" => true,
            ]);

            $this->runWithUser(function () use (
                $user,
                $testProfileField,
                $category1Discussions,
                $category2Discussions
            ) {
                $suggested = $this->api()
                    ->get("/discussions", ["suggestions" => true])
                    ->getBody();

                // Should have one category which is from the default interest.
                $this->assertCount(5, $suggested);
                $this->assertSame($category2Discussions, array_column($suggested, "discussionID"));
                $this->api()->patch("/users/{$user["userID"]}/profile-fields", [
                    $testProfileField["apiName"] => true,
                ]);

                $suggested = $this->api()
                    ->get("/discussions", ["suggestions" => true])
                    ->getBody();

                // Should have the category for the default interest and the one based on filters.
                $this->assertCount(10, $suggested);
                $discussions = array_merge($category1Discussions, $category2Discussions);
                rsort($discussions, SORT_NUMERIC);
                $this->assertSame($discussions, array_column($suggested, "discussionID"));
            },
            $user);
        });
    }

    /**
     * Test getting a list of discussions from interest suggestions, with private category.
     */
    public function testIndexSuggestionsPrivateCategory()
    {
        self::resetTable("Discussion");
        self::resetTable("Tag");
        self::resetTable("TagDiscussion");
        self::resetTable("interest");
        $this->enableFeature(InterestModel::SUGGESTED_CONTENT_FEATURE_FLAG);

        // Make sure we have a post we aren't following
        $this->createDiscussion();
        $this->runWithConfig([InterestModel::CONF_SUGGESTED_CONTENT_ENABLED => true], function () {
            // Make sure we're starting from scratch.
            $preSuggestions = $this->api()
                ->get("/discussions", ["suggestions" => true])
                ->getBody();
            $this->assertEmpty($preSuggestions);

            $user = $this->createUser();

            $testProfileField = $this->createProfileField(["dataType" => "boolean", "formType" => "checkbox"]);

            $discussions = $this->insertDiscussions(5);

            $category1 = $this->createCategory(["customPermissions" => true]);

            $this->api()->patch("/roles/" . \RoleModel::MEMBER_ID, [
                "permissions" => [
                    [
                        "id" => $category1["categoryID"],
                        "type" => "category",
                        "permissions" => [
                            "discussions.view" => false,
                        ],
                    ],
                ],
            ]);
            $this->api()->patch("/roles/" . \RoleModel::ADMIN_ID, [
                "permissions" => [
                    [
                        "id" => $category1["categoryID"],
                        "type" => "category",
                        "permissions" => [
                            "discussions.view" => true,
                        ],
                    ],
                ],
            ]);
            $category1Discussions = $this->insertDiscussions(11, ["CategoryID" => $category1["categoryID"]]);
            $discussions = array_merge($discussions, $category1Discussions);
            $category1Discussions = []; //array_column($category1Discussions, "DiscussionID");
            $category2 = $this->createCategory();
            $this->api()->patch("/roles/" . \RoleModel::MEMBER_ID, [
                "permissions" => [
                    [
                        "id" => $category2["categoryID"],
                        "type" => "category",
                        "permissions" => [
                            "discussions.view" => true,
                        ],
                    ],
                ],
            ]);
            $category2Discussions = array_column(
                $this->insertDiscussions(5, ["CategoryID" => $category2["categoryID"]]),
                "DiscussionID"
            );

            $tagID1 = $this->createTag();
            $tagID2 = $this->createTag();
            for ($i = 0; $i < 5; $i++) {
                $this->api()->post("discussions/{$discussions[$i]["DiscussionID"]}/tags", [
                    "urlcodes" => [$tagID1["urlcode"]],
                    "tagIDs" => [$tagID1["tagID"]],
                ]);
                $category1Discussions[] = $discussions[$i]["DiscussionID"];
            }
            // Adding tags to discussion in category1 (this is not visible to members)
            for ($i = 5; $i < 10; $i++) {
                $this->api()->post("discussions/{$discussions[$i]["DiscussionID"]}/tags", [
                    "urlcodes" => [$tagID2["urlcode"]],
                    "tagIDs" => [$tagID2["tagID"]],
                ]);
            }

            // Create interest associated with profile fields.
            $this->createInterest([
                "name" => "test",
                "apiName" => "test",
                "categoryIDs" => [$category1["categoryID"]],
                "tagIDs" => [$tagID1["tagID"]],
                "profileFieldMapping" => [
                    $testProfileField["apiName"] => true,
                ],
            ]);

            // Create default interest.
            $this->createInterest([
                "name" => "test-2",
                "apiName" => "test-2",
                "categoryIDs" => [$category2["categoryID"]],
                "tagIDs" => [$tagID2["tagID"]],
                "isDefault" => true,
            ]);

            $this->runWithUser(function () use (
                $user,
                $testProfileField,
                $category1Discussions,
                $category2Discussions
            ) {
                \DiscussionModel::clearCategoryPermissions();
                $suggested = $this->api()
                    ->get("/discussions", ["suggestions" => true])
                    ->getBody();

                // Should have one category which is from the default interest.
                $this->assertCount(5, $suggested);
                rsort($category2Discussions, SORT_NUMERIC);
                $this->assertSame($category2Discussions, array_column($suggested, "discussionID"));
                $this->api()->patch("/users/{$user["userID"]}/profile-fields", [
                    $testProfileField["apiName"] => true,
                ]);

                $suggested = $this->api()
                    ->get("/discussions", ["suggestions" => true])
                    ->getBody();

                // Should have the category for the default interest and the one based on filters.
                $this->assertCount(10, $suggested);
                $discussions = array_merge($category1Discussions, $category2Discussions);
                rsort($discussions, SORT_NUMERIC);
                $this->assertSame($discussions, array_column($suggested, "discussionID"));
            },
            $user);
        });
    }
}
