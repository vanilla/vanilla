<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use CategoryModel;
use DiscussionModel;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\DiscussionTypeConverter;
use Vanilla\Formatting\DateTimeFormatter;
use Vanilla\Formatting\Formats\WysiwygFormat;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Models\TestDiscussionModelTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/discussions endpoints.
 */
class DiscussionsTest extends AbstractResourceTest
{
    use TestExpandTrait;
    use TestPutFieldTrait;
    use TestPrimaryKeyRangeFilterTrait;
    use TestSortingTrait;
    use TestDiscussionModelTrait;
    use TestFilterDirtyRecordsTrait;
    use AssertLoggingTrait;
    use UsersAndRolesApiTestTrait;
    use SchedulerTestTrait;
    use ExpectExceptionTrait;
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
     * @inheritdoc
     */
    protected function getExpandableUserFields()
    {
        return [
            "insertUser",
            "lastUser",
            // 'lastPost.insertUser' requires a last post and is not always present.
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function record()
    {
        $record = $this->record;
        $record += ["categoryID" => reset(self::$categoryIDs), "name" => __CLASS__];
        return $record;
    }

    /**
     * {@inheritdoc}
     */
    protected function modifyRow(array $row)
    {
        $row = parent::modifyRow($row);

        if (array_key_exists("categoryID", $row) && !in_array($row["categoryID"], self::$categoryIDs)) {
            throw new \Exception(
                "Provided category ID (" . $row["categoryID"] . ") was not associated with a valid test category"
            );
        }

        $row["closed"] = !$row["closed"];
        $row["pinned"] = !$row["pinned"];
        if ($row["pinned"]) {
            $row["pinLocation"] = $row["pinLocation"] == "category" ? "recent" : "category";
        } else {
            $row["pinLocation"] = null;
        }
        $row["sink"] = !$row["sink"];

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function providePutFields()
    {
        $fields = [
            "bookmark" => ["bookmark", true, "bookmarked"],
        ];
        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();

        /** @var CategoryModel $categoryModel */
        $categoryModel = self::container()->get("CategoryModel");
        $categories = ["Test Category A", "Test Category B", "Test Category C"];
        foreach ($categories as $category) {
            $urlCode = preg_replace("/[^A-Z0-9]+/i", "-", strtolower($category));
            self::$categoryIDs[] = $categoryModel->save([
                "Name" => $category,
                "UrlCode" => $urlCode,
                "InsertUserID" => self::$siteInfo["adminUserID"],
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        DiscussionModel::categoryPermissions(false, true);
        $this->setupTestDiscussionModel();
        $this->createUserFixtures();
    }

    /**
     * Verify a bookmarked discussion shows up under /discussions/bookmarked.
     */
    public function testBookmarked()
    {
        $row = $this->testPost();
        $rowID = $row["discussionID"];
        $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/bookmark", ["bookmarked" => 1]);
        $bookmarked = $this->api()
            ->get("{$this->baseUrl}/bookmarked")
            ->getBody();
        $discussionIDs = array_column($bookmarked, "discussionID");
        $this->assertContains($rowID, $discussionIDs);
    }

    /**
     * Verify we can lookup all our own bookmarked posts only
     */
    public function testSelfBookmarked()
    {
        $row = $this->testPost();
        $rowID = $row["discussionID"];
        $row2 = $this->testPost();
        $rowID2 = $row2["discussionID"];
        $row3 = $this->testPost(); // NOT BOOKMARKED & SHOULDN'T RETURN
        $rowID3 = $row3["discussionID"];
        $this->api()->put("{$this->baseUrl}/{$rowID}/bookmark", ["bookmarked" => 1]);
        $this->api()->put("{$this->baseUrl}/{$rowID2}/bookmark", ["bookmarked" => 1]);
        $bookmarked = $this->api()
            ->get("{$this->baseUrl}?bookmarkUserID=" . self::$siteInfo["adminUserID"])
            ->getBody();
        $discussionIDs = array_column($bookmarked, "discussionID");
        $this->assertContains($rowID, $discussionIDs);
        $this->assertNotContains($rowID3, $discussionIDs);
    }

    /**
     * Verify admin can lookup all another user's bookmarked posts
     */
    public function testAdminBookmarkUserLookup()
    {
        //retrieve only those bookmarked by other user
        $user = $this->createUser();
        $record = [
            "categoryID" => 1,
            "name" => "Filter Test",
            "body" => "A filter test discussion",
            "format" => "text",
        ];
        $row = $this->testPost($record);
        $rowID = $row["discussionID"];
        $row2 = $this->testPost($record);
        $rowID2 = $row2["discussionID"];
        $row3 = $this->testPost($record); // NOT BOOKMARKED & SHOULDN'T RETURN
        $rowID3 = $row3["discussionID"];
        $this->api()->setUserID($user["userID"]);
        $this->api()->put("{$this->baseUrl}/{$rowID}/bookmark", ["bookmarked" => 1]);
        $this->api()->put("{$this->baseUrl}/{$rowID2}/bookmark", ["bookmarked" => 1]);
        $this->api()->setUserID(self::$siteInfo["adminUserID"]); // admin performing lookup
        $bookmarked = $this->api()
            ->get("{$this->baseUrl}?bookmarkUserID=" . $user["userID"])
            ->getBody();
        $discussionIDs = array_column($bookmarked, "discussionID");
        $this->assertContains($rowID, $discussionIDs);
        $this->assertNotContains($rowID3, $discussionIDs);
    }

    /**
     * Verify a non-admin user (unprivileged) cannot lookup another users bookmarked posts
     */
    public function testUnprivilegedBookmarkUserLookup()
    {
        $this->expectException(ForbiddenException::class);
        //regular user attempt to retrieve only those bookmarked by other user
        $user = $this->createUser();
        $user2 = $this->createUser();
        $this->api()->setUserID($user["userID"]); //regular user performing lookup
        $this->api()
            ->get("{$this->baseUrl}?bookmarkUserID=" . $user2["userID"])
            ->getBody();
    }

    /**
     * Verify we can lookup all our own Participated posts only
     */
    public function testSelfParticipated()
    {
        $this->api()->setUserID(self::$siteInfo["adminUserID"]); // admin user creating posts
        $user = $this->createUser();
        $user2 = $this->createUser();
        $row = $this->testPost(); //posted by admin user
        $rowID = $row["discussionID"];
        $row2 = $this->testPost(); //posted by admin user
        $rowID2 = $row2["discussionID"];
        $row3 = $this->testPost(); //posted by user
        $rowID3 = $row3["discussionID"];

        $this->api()->setUserID($user["userID"]); //set user for commenting on posts
        $this->api()->post("/comments", [
            "discussionID" => $rowID,
            "body" => "hello",
            "format" => "markdown",
        ]); //comment by other user
        $this->api()->post("/comments", [
            "discussionID" => $rowID2,
            "body" => "hello2",
            "format" => "markdown",
        ]); //comment by user
        $this->api()->setUserID($user2["userID"]); //set different user for commenting on posts
        $this->api()->post("/comments", [
            "discussionID" => $rowID3,
            "body" => "hello",
            "format" => "markdown",
        ]); //comment by a different user
        $this->api()->setUserID(self::$siteInfo["adminUserID"]); // admin performing lookup
        $bookmarked = $this->api()
            ->get("{$this->baseUrl}?participatedUserID=" . $user["userID"])
            ->getBody();
        $discussionIDs = array_column($bookmarked, "discussionID");
        $this->assertContains($rowID, $discussionIDs);
        $this->assertContains($rowID2, $discussionIDs);
        $this->assertNotContains($rowID3, $discussionIDs); //different user not returned
    }

    /**
     * Verify admin can lookup all another user's Participated posts
     */
    public function testAdminParticipatedUserLookup()
    {
        $user = $this->createUser();
        $user2 = $this->createUser();
        $this->api()->setUserID($user["userID"]); //user creating posts
        $row = $this->testPost();
        $rowID = $row["discussionID"];
        $row2 = $this->testPost();
        $rowID2 = $row2["discussionID"];
        $row3 = $this->testPost();
        $rowID3 = $row3["discussionID"];

        $this->api()->setUserID($user2["userID"]); //set user2 for commenting on posts
        $this->api()->post("/comments", [
            "discussionID" => $rowID,
            "body" => "hello",
            "format" => "markdown",
        ]); //comment by other user
        $this->api()->post("/comments", [
            "discussionID" => $rowID2,
            "body" => "hello2",
            "format" => "markdown",
        ]); //comment by user
        $this->api()->setUserID(self::$siteInfo["adminUserID"]); //set admin user for commenting on posts
        $this->api()->post("/comments", [
            "discussionID" => $rowID3,
            "body" => "hello",
            "format" => "markdown",
        ]); //comment by a different user
        $bookmarked = $this->api()
            ->get("{$this->baseUrl}?participatedUserID=" . $user2["userID"])
            ->getBody();
        $discussionIDs = array_column($bookmarked, "discussionID");
        $this->assertContains($rowID, $discussionIDs);
        $this->assertContains($rowID2, $discussionIDs);
        $this->assertNotContains($rowID3, $discussionIDs); //different user not returned
    }

    /**
     * Verify a non-admin user (unprivileged) cannot lookup another users Participated posts
     */
    public function testUnprivilegedParticipatedUserLookup()
    {
        $this->expectException(ForbiddenException::class);
        //regular user attempt to retrieve only those commened on by other user
        $user = $this->createUser();
        $user2 = $this->createUser();
        $this->api()->setUserID($user["userID"]); //regular user performing lookup
        $this->api()
            ->get("{$this->baseUrl}?participatedUserID=" . $user2["userID"])
            ->getBody();
    }

    /**
     * Verify cannot lookup guest user bookmarked posts
     */
    public function testGuestBookmarkUserLookup()
    {
        $this->expectException(ClientException::class);
        $this->api()
            ->get("{$this->baseUrl}?bookmarkUserID=0")
            ->getBody();
    }

    /**
     * Verify cannot lookup guest user participated posts
     */
    public function testGuestParticipatedUserLookup()
    {
        $this->expectException(ClientException::class);
        $this->api()
            ->get("{$this->baseUrl}?participatedUserID=0")
            ->getBody();
    }

    /**
     * Test getting a list of discussions from followed categories.
     */
    public function testIndexFollowed()
    {
        // Make sure we have a post we aren't following
        $this->createDiscussion();

        // Make sure we're starting from scratch.
        $preFollow = $this->api()
            ->get("/discussions", ["followed" => true])
            ->getBody();
        $this->assertEmpty($preFollow);

        // Create a new category to follow.
        $category = $this->createCategory([
            "name" => __FUNCTION__,
        ]);
        $testCategoryID = $category["categoryID"];
        $this->api()->put("categories/{$testCategoryID}/follow", ["followed" => true]);

        // Add an old pinned discussion
        CurrentTimeStamp::mockTime("2020-01-01");
        $this->createDiscussion(["name" => "pinned globally", "pinned" => true, "pinLocation" => "recent"]);

        // Add a slightly new pinned in category
        CurrentTimeStamp::mockTime("2020-01-02");
        $this->createDiscussion(["name" => "pinned category", "pinned" => true, "pinLocation" => "category"]);

        // Now add some a newer not pinned posts
        CurrentTimeStamp::mockTime("2020-01-03");
        $this->createDiscussion(["name" => "new 1"]);

        // No pinOrder
        $result = $this->api()
            ->get($this->baseUrl, ["followed" => true])
            ->getBody();
        $this->assertRowsLike(
            [
                "name" => ["new 1", "pinned category", "pinned globally"],
            ],
            $result,
            true,
            3
        );

        // With pinLocation
        $result = $this->api()
            ->get("/discussions", ["followed" => true, "pinOrder" => "first"])
            ->getBody();
        $this->assertRowsLike(
            [
                "name" => ["pinned globally", "new 1", "pinned category"],
            ],
            $result,
            true,
            3
        );
    }

    /**
     * Test GET `/discussions` API endpoint filtering by `hasComments`.
     */
    public function testHasCommentsFiltering()
    {
        $this->resetTable("Discussion");
        $this->resetTable("Comment");
        // Create a discussion with a comment.
        $this->createDiscussion();
        $this->createComment();
        // Create another discussion with a comment.
        $this->createDiscussion();
        $this->createComment();
        // Create a discussion without a comment.
        $this->createDiscussion();

        // Get a list of discussions with comments.
        $discussionsWithComments = $this->api()
            ->get($this->baseUrl, ["hasComments" => true])
            ->getBody();
        $this->assertCount(2, $discussionsWithComments);

        // Get a list of discussions without comments.
        $discussionsWithoutComments = $this->api()
            ->get($this->baseUrl, ["hasComments" => false])
            ->getBody();
        $this->assertCount(1, $discussionsWithoutComments);

        $unfilteredDiscussions = $this->api()
            ->get($this->baseUrl)
            ->getBody();
        $this->assertCount(3, $unfilteredDiscussions);
    }

    /**
     * Test the `excludeHiddenCategories` parameter from the GET `/discussions` API endpoint.
     */
    public function testExcludeHiddenCategories()
    {
        $categoryModel = \Gdn::getContainer()->get(CategoryModel::class);

        // $categoryA has `HideAllDiscussions` set to `1` & contains 2 discussions.
        $categoryA = $this->createCategory();
        $categoryModel->setProperty($categoryA["categoryID"], "HideAllDiscussions", 1);
        $this->createDiscussion();
        $this->createDiscussion();

        // $categoryB has `HideAllDiscussions` set to `0` & contains 3 discussions.
        $categoryB = $this->createCategory();
        $this->createDiscussion();
        $this->createDiscussion();
        $this->createDiscussion();

        // Get a list of unfiltered discussions.
        $unfilteredDiscussions = $this->api()
            ->get($this->baseUrl, ["excludeHiddenCategories" => false])
            ->getBody();

        // Get a list of filtered discussions.
        $filteredDiscussions = $this->api()
            ->get($this->baseUrl, ["excludeHiddenCategories" => true])
            ->getBody();

        // Assert that the filtered count is less than the unfiltered count.
        $this->assertTrue(count($filteredDiscussions) < count($unfilteredDiscussions));
    }

    /**
     * Test GET `/discussions` API endpoint filtering by score.
     */
    public function testHasScoreFiltering()
    {
        $this->resetTable("Discussion");
        $this->resetTable("Comment");
        $this->createCategory([
            "name" => "sortTestCategory",
        ]);
        $categoryId = $this->lastInsertedCategoryID;
        $discussions = [];
        // Create a discussion with a score value 1.
        $discussions["score1"] = $this->createDiscussion([
            "name" => "Discussion with score 1",
            "categoryID" => $categoryId,
            "score" => 1,
        ]);
        // Create 2 discussion with a score value 5.
        $discussions["score5"][] = $this->createDiscussion([
            "name" => "Discussion with score 5-1",
            "categoryID" => $categoryId,
            "score" => 5,
        ]);
        $discussions["score5"][] = $this->createDiscussion([
            "name" => "Discussion with score 5-2",
            "categoryID" => $categoryId,
            "score" => 5,
        ]);
        // Create a discussion with a score value -3.
        $discussions["score-3"][] = $this->createDiscussion([
            "name" => "Discussion with score -3",
            "categoryID" => $categoryId,
            "score" => -3,
        ]);

        // Get a list of all discussion
        $discussionList = $this->api()
            ->get($this->baseUrl)
            ->getBody();
        $this->assertCount(4, $discussionList);

        // Get a list of discussions with score 1.
        $discussionWithScore1 = $this->api()
            ->get($this->baseUrl, ["score" => 1])
            ->getBody();

        $this->assertCount(1, $discussionWithScore1);
        $this->assertEquals($discussions["score1"]["discussionID"], $discussionWithScore1[0]["discussionID"]);
        $this->assertEquals(1, $discussionWithScore1[0]["score"]);

        // Get a list of discussions with score 5.
        $discussionWithScore5 = $this->api()
            ->get($this->baseUrl, ["score" => 5])
            ->getBody();
        $this->assertCount(2, $discussionWithScore5);
        $this->assertContainsEquals($discussions["score5"][0]["discussionID"], [
            $discussionWithScore5[0]["discussionID"],
            $discussionWithScore5[1]["discussionID"],
        ]);
        $this->assertContainsEquals($discussions["score5"][1]["discussionID"], [
            $discussionWithScore5[0]["discussionID"],
            $discussionWithScore5[1]["discussionID"],
        ]);

        // Get a list of discussions with score -3.
        $discussionWithScoreMinus3 = $this->api()
            ->get($this->baseUrl, ["score" => -3])
            ->getBody();
        $this->assertCount(1, $discussionWithScoreMinus3);
        $this->assertEquals($discussions["score-3"][0]["discussionID"], $discussionWithScoreMinus3[0]["discussionID"]);
    }
    /**
     * Test PATCH /discussions/<id> with a single field update.
     *
     * @param string $field The name of the field to patch.
     * @dataProvider providePatchFields
     */
    public function testPatchSparse($field)
    {
        // pinLocation doesn't do anything on its own, it requires pinned. It's not a good candidate for a single-field sparse PATCH.
        if ($field == "pinLocation") {
            $this->assertTrue(true);
            return;
        }

        parent::testPatchSparse($field);
    }

    /**
     * Test PUT /discussions/{id}/canonical-url when not set
     */
    public function testPutCanonicalUrl()
    {
        $row = $this->testPost();
        $url = "/canonical/url/test";
        $discussion = $this->api()
            ->put($this->baseUrl . "/" . $row["discussionID"] . "/canonical-url", ["canonicalUrl" => $url])
            ->getBody();
        $this->assertArrayHasKey("canonicalUrl", $discussion);
        $this->assertEquals($url, $discussion["canonicalUrl"]);
    }

    /**
     * Test PUT /discussions/{id}/canonical-url when already set up
     */
    public function testOverwriteCanonicalUrl()
    {
        $row = $this->testPost();
        $url = "/canonical/url/test";
        $discussion = $this->api()
            ->put($this->baseUrl . "/" . $row["discussionID"] . "/canonical-url", ["canonicalUrl" => $url])
            ->getBody();
        $this->assertArrayHasKey("canonicalUrl", $discussion);
        $this->assertEquals($url, $discussion["canonicalUrl"]);

        $r = $this->api()->put($this->baseUrl . "/" . $row["discussionID"] . "/canonical-url", [
            "canonicalUrl" => $url . "overwrite",
        ]);
        $this->assertSame($url . "overwrite", $r["canonicalUrl"]);
    }

    /**
     * Test DELETE /discussions/{id}/canonical-url
     */
    public function testDeleteCanonicalUrl()
    {
        $row = $this->testPost();
        $url = "/canonical/url/test";
        $discussion = $this->api()
            ->put($this->baseUrl . "/" . $row["discussionID"] . "/canonical-url", ["canonicalUrl" => $url])
            ->getBody();
        $response = $this->api()->delete($this->baseUrl . "/" . $row["discussionID"] . "/canonical-url");

        $this->assertEquals("204 No Content", $response->getStatus());

        $discussion = $response->getBody();
        $this->assertTrue(empty($discussion));

        $discussion = $this->api()
            ->get($this->baseUrl . "/" . $row["discussionID"])
            ->getBody();
        $this->assertNotEquals($url, $discussion["canonicalUrl"]);
        $this->assertEquals($discussion["url"], $discussion["canonicalUrl"]);
    }

    /**
     * The discussion index should fail on a private community with a guest.
     */
    public function testIndexPrivateCommunity()
    {
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You must sign in to the private community.");

        $this->runWithPrivateCommunity([$this, "testIndex"]);
    }

    /**
     * Test the new dateLastComment filter.
     */
    public function testDateLastCommentFilter()
    {
        $currentTime = CurrentTimeStamp::getDateTime("Dec 21 2015");
        CurrentTimeStamp::mockTime($currentTime);
        $this->generateIndexRows();
        CurrentTimeStamp::mockTime($currentTime->modify("+1 second"));
        $rows = $this->generateIndexRows();
        $row0 = $rows[0];
        $this->assertNotEmpty($row0["dateLastComment"]);

        $filteredRows = $this->api()
            ->get("/discussions", ["dateLastComment" => "<" . $row0["dateLastComment"]])
            ->getBody();
        $filteredRow0 = $filteredRows[0];
        $this->assertNotSame($row0["discussionID"], $filteredRow0["discussionID"]);
    }

    /**
     * Test comment body expansion.
     */
    public function testExpandLastPostBody()
    {
        $this->resetTable("Discussion");
        $this->resetTable("Comment");
        $this->testPost();

        // Test that the field is there.
        $query = ["expand" => "lastPost,lastPost.body"];
        $rows = $this->api()->get($this->baseUrl, $query);
        $this->assertArrayHasKey("body", $rows[0]["lastPost"]);

        // Comment on a discussions to see if it becomes the last post.
        $comment = $this->api()->post("/comments", [
            "discussionID" => $rows[0]["discussionID"],
            "body" => "hello",
            "format" => "markdown",
        ]);

        $rows = $this->api()->get($this->baseUrl, $query);
        $this->assertSame($comment["commentID"], $rows[0]["lastPost"]["commentID"]);

        // Individual discussions should expand too.
        $discussion = $this->api()->get($this->baseUrl . "/" . $rows[0]["discussionID"], $query);
        $this->assertArrayHasKey("body", $discussion["lastPost"]);
        $this->assertSame($comment["commentID"], $discussion["lastPost"]["commentID"]);
    }

    /**
     * @depends testExpandLastPostBody
     */
    public function testExpandLastUser()
    {
        $rows = $this->api()->get($this->baseUrl, ["expand" => "lastPost,lastPost.insertUser,-lastUser"]);
        $this->assertArrayHasKey("insertUser", $rows[0]["lastPost"]);
        $this->assertArrayNotHasKey("lastUser", $rows[0]);

        // Deprecated but should work for BC.
        $rows = $this->api()->get($this->baseUrl, ["expand" => "lastPost,lastUser"]);
        $this->assertArrayHasKey("insertUser", $rows[0]["lastPost"]);
        $this->assertArrayHasKey("lastUser", $rows[0]);

        $url = $this->baseUrl . "/" . $rows[0]["discussionID"];
        $row = $this->api()->get($url, ["expand" => "lastPost,lastPost.insertUser,-lastUser"]);
        $this->assertArrayHasKey("insertUser", $row["lastPost"]);
        $this->assertArrayNotHasKey("lastUser", $row);
    }

    /**
     * Get Discussions by Status
     *
     * @param int $statusID status ID.
     *
     * @depends testPrepareMoveDiscussionsData
     * @dataProvider providerTestGetDiscussionsByStatus
     */
    public function testGetDiscussionsByStatus(int $statusID)
    {
        $discussionID = self::$data["validDiscussionIDs"][0];
        // set status
        $this->api()
            ->put("/discussions/{$discussionID}/status", [
                "statusID" => $statusID,
            ])
            ->getBody();
        $recordState = $this->container()->get(RecordStatusModel::class);
        $status = $recordState->selectSingle(["statusID" => $statusID]);
        $filter = [$status["isInternal"] ? "internalStatusID" : "statusID" => [$statusID]];
        $rows = $this->api()->get($this->baseUrl, $filter);
        $this->assertArrayHasKey("discussionID", $rows[0]);
        $discussionIDs = [];
        foreach ($rows->getBody() as $discussion) {
            $discussionIDs[] = $discussion["discussionID"];
        }
        $this->assertContains($discussionID, $discussionIDs);
    }

    /**
     * Get Discussions by Status and statusState, expect Exception.
     *
     * @depends testPrepareMoveDiscussionsData
     */
    public function testGetDiscussionsByStatusStatusState()
    {
        $this->expectException(ClientException::class);
        $this->api()->get($this->baseUrl, ["statusID" => [1], "internalStatusID" => [2]]);
    }

    /**
     * Get Discussions by statusState open/closed
     *
     * @param int $statusID
     * @param string $statusState
     * @param int $expectedCount
     *
     * @depends testPrepareMoveDiscussionsData
     * @dataProvider providerTestGetDiscussionsByStatusState
     */
    public function testGetDiscussionsByState(int $statusID, string $statusState = "", int $expectedCount = 0)
    {
        $discussionID = self::$data["validDiscussionIDs"][0];
        // set status
        $this->api()
            ->put("/discussions/{$discussionID}/status", [
                "statusID" => $statusID,
            ])
            ->getBody();
        $rows = $this->api()->get($this->baseUrl, ["statusState" => $statusState]);
        $this->assertArrayHasKey("discussionID", $rows[0]);
        $this->assertGreaterThanOrEqual($expectedCount, count($rows->getBody()));
    }

    /**
     * Data Provider for status filter tests.
     */
    public function providerTestGetDiscussionsByStatus(): array
    {
        return ["status 1" => [1], "status 3" => [3]];
    }

    /**
     * Data Provider for status state filter tests.
     */
    public function providerTestGetDiscussionsByStatusState(): array
    {
        return ["status 1" => [1, "open", 1], "status 3" => [3, "closed", 1]];
    }

    /**
     * The API should not fail when the discussion title/body is empty.
     */
    public function testEmptyDiscussionTitle()
    {
        $row = $this->testPost();

        /* @var \Gdn_SQLDriver $sql */
        $sql = self::container()->get(\Gdn_SQLDriver::class);
        $sql->put("Discussion", ["Name" => "", "Body" => ""], ["DiscussionID" => $row["discussionID"]]);

        $discussion = $this->api()
            ->get("$this->baseUrl/{$row["discussionID"]}")
            ->getBody();
        $this->assertNotEmpty($discussion["name"]);
    }

    /**
     * Announcements should obey the sort.
     */
    public function testAnnouncementSort(): void
    {
        $this->insertDiscussions(3, ["Announce" => 1]);

        $fields = ["discussionID", "-discussionID"];

        foreach ($fields as $field) {
            $rows = $this->api()
                ->get($this->baseUrl, ["pinned" => true, "sort" => $field])
                ->getBody();
            $this->assertNotEmpty($rows);
            $this->assertSorted($rows, $field);
        }
    }

    /**
     * A mix of announcements and discussions should sort properly.
     */
    public function testAnnouncementMixed(): void
    {
        $rows = $this->insertDiscussions(2, ["Announce" => 1]);
        $rows = array_merge($rows, $this->insertDiscussions(2));
        $ids = array_column($rows, "DiscussionID");

        $fields = ["discussionID", "-discussionID"];

        foreach ($fields as $field) {
            $rows = $this->api()
                ->get($this->baseUrl, ["discussionID" => $ids, "pinOrder" => "first", "sort" => $field])
                ->getBody();
            $this->assertNotEmpty($rows);
            $this->assertSorted($rows, "-pinned", $field);
        }
    }

    /**
     * Make sure you can pin a discussion while posting via API.
     */
    public function testPostAnnouncement(): void
    {
        $r = $this->api()
            ->post($this->baseUrl, ["pinned" => true] + $this->record())
            ->getBody();
        $this->assertTrue($r["pinned"]);
        $this->assertSame("category", $r["pinLocation"]);

        $r = $this->api()
            ->post($this->baseUrl, ["pinned" => true, "pinLocation" => "recent"] + $this->record())
            ->getBody();
        $this->assertTrue($r["pinned"]);
        $this->assertSame("recent", $r["pinLocation"]);
    }

    /**
     * Test the "dismissed" field on discussions.
     *
     * @return void
     */
    public function testDismissedField(): void
    {
        // Any discussion will have a dismissed field that defaults to `false`.
        $discussion = $this->createDiscussion();
        $this->api()
            ->get("$this->baseUrl/{$discussion["discussionID"]}")
            ->getBody();
        $this->assertArrayHasKey("dismissed", $discussion);
        $this->assertSame(false, $discussion["dismissed"]);

        // An announcement will have a dismissed field that defaults to `false`.
        $announcedDiscussion = $this->api()
            ->post($this->baseUrl, ["pinned" => true] + $this->record())
            ->getBody();

        $this->api()
            ->get("$this->baseUrl/{$announcedDiscussion["discussionID"]}")
            ->getBody();
        $this->assertArrayHasKey("dismissed", $announcedDiscussion);
        $this->assertSame(false, $announcedDiscussion["dismissed"]);

        // When a discussion is dismissed, the dismissed field should be `true`.
        $dismissedDiscussion = $this->api()
            ->put("$this->baseUrl/{$announcedDiscussion["discussionID"]}/dismiss", ["dismissed" => true])
            ->getBody();
        $this->assertArrayHasKey("dismissed", $dismissedDiscussion);
        $this->assertSame(true, $dismissedDiscussion["dismissed"]);
    }

    /**
     * Make sure specifying discussion type returns records from the db where the type is null.
     */
    public function testGettingTypeDiscussion(): void
    {
        $addedDiscussions = $this->insertDiscussions(4);
        foreach ($addedDiscussions as $discussion) {
            $this->assertTrue(is_null($discussion["Type"]));
        }
        $retrievedDiscussions = $this->api()
            ->get($this->baseUrl, ["type" => "discussion"])
            ->getBody();
        $retrievedDiscussionsIDs = array_column($retrievedDiscussions, "discussionID");
        foreach ($addedDiscussions as $discussion) {
            $this->assertTrue(in_array($discussion["DiscussionID"], $retrievedDiscussionsIDs));
        }
    }

    /**
     * A member should not be able to delete their own discussion.
     */
    public function testNoDeleteOwnDiscussion(): void
    {
        $this->api()->setUserID($this->memberID);
        $discussion = $this->insertDiscussions(1)[0];
        $this->assertFalse(
            $this->getSession()
                ->getPermissions()
                ->has("Vanilla.Discussions.Delete", $discussion["CategoryID"]),
            "The member should not have permission to delete discussions."
        );

        $this->expectException(ForbiddenException::class);
        $this->api()->delete("/discussions/{$discussion["DiscussionID"]}");
    }

    /**
     * Test expanding tags.
     */
    public function testExpandTags(): void
    {
        self::resetTable("Discussion");
        $discussionA = $this->testPost();
        $tagA = $this->api()
            ->post("tags", ["name" => "testa" . __FUNCTION__, "urlCode" => "testa" . __FUNCTION__])
            ->getBody();
        $this->api()->post("discussions/{$discussionA["discussionID"]}/tags", [
            "urlcodes" => [$tagA["urlcode"]],
            "tagIDs" => [$tagA["tagID"]],
        ]);
        $discussions = $this->api()
            ->get("discussions", ["expand" => "tags"])
            ->getBody();
        foreach ($discussions as $discussion) {
            $tags = $discussion["tags"];
            $this->assertEquals($tagA["tagID"], $tags[0]["tagID"]);
        }
        $discussion = $this->api()
            ->get("discussions/" . $discussionA["discussionID"], ["expand" => "tags"])
            ->getBody();
        $tags = $discussion["tags"];
        $this->assertEquals($tagA["tagID"], $tags[0]["tagID"]);
    }

    /**
     * Verify we get the discussion record for a specific tagId
     */
    public function testDiscussionByTag(): void
    {
        self::resetTable("Discussion");
        self::resetTable("Tag");
        self::resetTable("TagDiscussion");
        $addedDiscussions = $this->insertDiscussions(4);
        $tagA = $this->api()
            ->post("tags", ["name" => "testa" . __FUNCTION__, "urlCode" => "testa" . __FUNCTION__])
            ->getBody();
        $taggedDiscussions = [];
        for ($i = 0; $i < 2; $i++) {
            $this->api()->post("discussions/{$addedDiscussions[$i]["DiscussionID"]}/tags", [
                "urlcodes" => [$tagA["urlcode"]],
                "tagIDs" => [$tagA["tagID"]],
            ]);
            $taggedDiscussions[] = $addedDiscussions[$i]["DiscussionID"];
        }
        $retrievedDiscussions = $this->api()
            ->get("discussions", ["tagID" => $tagA["tagID"], "expand" => "tags"])
            ->getBody();
        $tags = $retrievedDiscussions[0]["tags"];
        $retrievedDiscussionsIDs = array_column($retrievedDiscussions, "discussionID");

        $this->assertEqualsCanonicalizing($taggedDiscussions, $retrievedDiscussionsIDs);
        $this->assertEquals($tagA["tagID"], $tags[0]["tagID"]);
    }

    /**
     * Ensure that there are dirtyRecords for a specific resource.
     */
    protected function triggerDirtyRecords()
    {
        $this->resetTable("dirtyRecord");
        // This one is not dirty.
        $this->createDiscussion();
        $discussion = $this->insertDiscussions(2);
        $ids = array_column($discussion, "DiscussionID");
        /** @var DiscussionModel $discussionModel */
        $discussionModel = \Gdn::getContainer()->get(DiscussionModel::class);
        foreach ($ids as $id) {
            $discussionModel->setField($id, "Announce", 1);
        }
    }

    /**
     * Test PUT /discussions/:id/type
     */
    public function testPutDiscussionsType()
    {
        $discussion = $this->insertDiscussions(1)[0];
        /** @var DiscussionModel $discussionModel */
        $discussionModel = \Gdn::getContainer()->get(DiscussionModel::class);
        $id = $discussion["DiscussionID"];
        $discussionModel->setField($id, "Type", "Question");

        $convertedDiscussion = $this->api()
            ->put("/discussions/{$id}/type", ["type" => "discussion"])
            ->getBody();
        $this->assertEquals("discussion", $convertedDiscussion["type"]);
    }

    /**
     * Test PUT /discussions/:id/type with invalid type.
     */
    public function testPutDiscussionsTypeInvalidType()
    {
        $this->expectException(ClientException::class);
        $discussion = $this->insertDiscussions(1)[0];
        $id = $discussion["DiscussionID"];

        $convertedDiscussion = $this->api()
            ->put("/discussions/{$id}/type", ["type" => "poll"])
            ->getBody();
        $this->assertEquals("discussion", $convertedDiscussion["type"]);
    }

    /**
     * Test PUT /discussions/:id/type with restricted type.
     */
    public function testPutDiscussionsTypeRestrictedType()
    {
        $this->expectException(ClientException::class);
        $discussion = $this->insertDiscussions(1)[0];
        $id = $discussion["DiscussionID"];

        $convertedDiscussion = $this->api()
            ->put("/discussions/{$id}/type", ["type" => DiscussionTypeConverter::RESTRICTED_TYPES[0]])
            ->getBody();
        $this->assertEquals("discussion", $convertedDiscussion["type"]);
    }

    /**
     * Test DELETE /discussions/list
     */
    public function testDeleteDiscussionsList(): void
    {
        $discussionData = [
            "name" => "Test Discussion",
            "format" => "text",
            "body" => "Hello Discussion",
            "categoryID" => 1,
        ];
        $countBefore = count(
            $this->api()
                ->get("/discussions")
                ->getBody()
        );
        $discussion1 = $this->api()
            ->post("/discussions", $discussionData)
            ->getBody();
        $discussion2 = $this->api()
            ->post("/discussions", $discussionData)
            ->getBody();
        // Delete 2 valid discussions.
        $this->api()->deleteWithBody("/discussions/list", [
            "discussionIDs" => [$discussion1["discussionID"], $discussion2["discussionID"]],
        ]);
        $countAfter = count(
            $this->api()
                ->get("/discussions")
                ->getBody()
        );
        $this->assertEquals($countBefore, $countAfter);
        $discussion3 = $this->api()
            ->post("/discussions", $discussionData)
            ->getBody();
        $rd = rand(5000, 60000);
        // Delete an invalid discussion.
        try {
            $this->api()
                ->deleteWithBody("/discussions/list", ["discussionIDs" => [$discussion3["discussionID"], $rd]])
                ->getBody();
        } catch (\Exception $e) {
            $this->assertEquals($countBefore, $countAfter);
            $this->assertEquals(408, $e->getCode());
        }
        $this->api()->setUserID(\UserModel::GUEST_USER_ID);
        try {
            $this->api()->deleteWithBody("/discussions/list", [
                "discussionIDs" => [$discussion1["discussionID"], $discussion2["discussionID"]],
            ]);
        } catch (\Exception $e) {
            $this->assertEquals(403, $e->getCode());
        }
    }

    /**
     * Test Success PATCH /discussions/move
     *
     * @depends testPrepareMoveDiscussionsData
     */
    public function testSuccessMoveDiscussionsList(): void
    {
        $this->api()->patch("/discussions/move", [
            "discussionIDs" => self::$data["validDiscussionIDs"],
            "categoryID" => self::$data["validCategory2"]["categoryID"],
        ]);
        $discussions = $this->discussionModel->getIn(self::$data["validDiscussionIDs"])->resultArray();
        foreach ($discussions as $discussion) {
            $this->assertEquals(self::$data["validCategory2"]["categoryID"], $discussion["CategoryID"]);
        }
    }

    /**
     * Test closing discussions using PATCH /discussions/close API endpoint
     *
     * @depends testPrepareMoveDiscussionsData
     */
    public function testCloseOpenedDiscussions(): void
    {
        $discussionIDs = self::$data["openedDiscussionIDs"];

        // We attempt to close every provided discussion
        $response = $this->api()
            ->patch("/discussions/close", [
                "discussionIDs" => $discussionIDs,
                "closed" => true,
            ])
            ->getBody();
        // Verify that the returned successful discussion's IDs are the same as originally provided discussion's IDs.
        $this->assertRowsEqual($discussionIDs, $response["progress"]["successIDs"]);
        $this->assertEquals(count($discussionIDs), $response["progress"]["countTotalIDs"]);

        // Verify each row to make sure every discussion was closed.
        foreach ($discussionIDs as $discussionID) {
            $discussionData = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
            $this->assertTrue((bool) $discussionData["Closed"]);
        }
    }

    /**
     * Test opening discussions using PATCH /discussions/close API endpoint
     *
     * @depends testPrepareMoveDiscussionsData
     */
    public function testOpenClosedDiscussions(): void
    {
        $discussionIDs = self::$data["closedDiscussionIDs"];

        // We attempt to open every provided discussion
        $response = $this->api()
            ->patch("/discussions/close", [
                "discussionIDs" => $discussionIDs,
                "closed" => false,
            ])
            ->getBody();
        // Verify that the returned successful discussion's IDs are the same as originally provided discussion's IDs.
        $this->assertRowsEqual($discussionIDs, $response["progress"]["successIDs"]);
        $this->assertEquals(count($discussionIDs), $response["progress"]["countTotalIDs"]);

        // Verify each row to make sure every discussion was opened.
        foreach ($discussionIDs as $discussionID) {
            $discussionData = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
            $this->assertFalse((bool) $discussionData["Closed"]);
        }
    }

    /**
     * Prepare move discussions test data.
     */
    public function testPrepareMoveDiscussionsData(): void
    {
        $rd1 = rand(6000, 7000);
        $rd2 = rand(4000, 5000);
        $rd3 = rand(2000, 3000);
        $rd4 = rand(1000, 2000);
        $categoryInvalid = [
            "categoryID" => 123456,
            "name" => "invalid category",
            "urlCode" => "invalid category" . $rd1 . $rd2,
        ];
        $category_1Name = "category_1" . $rd1;
        $category_2Name = "category_2" . $rd2;
        $category_3Name = "category_3" . $rd3;
        $category_PermissionName = "category_Permission" . $rd4;
        $categoryData_1 = [
            "customPermissions" => true,
            "displayAs" => "discussions",
            "parentCategoryID" => 1,
            "name" => $category_1Name,
            "urlCode" => slugify($category_1Name),
        ];

        $categoryData_2 = [
            "customPermissions" => true,
            "displayAs" => "discussions",
            "parentCategoryID" => 1,
            "name" => $category_2Name,
            "urlCode" => slugify($category_2Name),
        ];
        $categoryData_3 = [
            "customPermissions" => true,
            "displayAs" => "discussions",
            "parentCategoryID" => 1,
            "name" => $category_3Name,
            "urlCode" => slugify($category_3Name),
        ];
        $categoryData_Permission = [
            "customPermissions" => true,
            "displayAs" => "discussions",
            "parentCategoryID" => 1,
            "name" => $category_PermissionName,
            "urlCode" => slugify($category_PermissionName),
        ];
        $categoryData_Heading = [
            "displayAs" => "heading",
            "parentCategoryID" => 1,
            "name" => "headingCategory" . $rd1,
            "urlCode" => slugify("headingCategory" . $rd1),
        ];
        $category_1 = $this->api()
            ->post("/categories", $categoryData_1)
            ->getBody();
        $category_2 = $this->api()
            ->post("/categories", $categoryData_2)
            ->getBody();
        $category_3 = $this->api()
            ->post("/categories", $categoryData_3)
            ->getBody();
        $category_permission = $this->api()
            ->post("/categories", $categoryData_Permission)
            ->getBody();
        $category_heading = $this->api()
            ->post("/categories", $categoryData_Heading)
            ->getBody();

        $this->api()->patch("/roles/" . \RoleModel::ADMIN_ID, [
            "permissions" => [
                [
                    "id" => $category_permission["categoryID"],
                    "type" => "category",
                    "permissions" => [
                        "discussions.view" => true,
                    ],
                ],
            ],
        ]);
        $discussionData_1 = [
            "name" => "Test Discussion_1",
            "format" => "text",
            "body" => "Hello Discussion_1",
            "categoryID" => $category_1["categoryID"],
            "statusID" => 1,
        ];
        $discussionData_2 = [
            "name" => "Test Discussion_2",
            "format" => "text",
            "body" => "Hello Discussion_2",
            "categoryID" => $category_1["categoryID"],
            "statusID" => 2,
        ];
        $discussionData_3 = [
            "name" => "Test Discussion_3",
            "format" => "text",
            "body" => "Hello Discussion_3",
            "categoryID" => $category_1["categoryID"],
            "statusID" => 0,
        ];
        $discussionData_4 = [
            "name" => "Test Discussion_4",
            "format" => "text",
            "body" => "Hello Discussion_4",
            "categoryID" => $category_1["categoryID"],
            "statusID" => 0,
        ];
        $openedDiscussionData_1 = [
            "name" => "Opened Test Discussion_1",
            "format" => "text",
            "body" => "Hello Discussion_1",
            "categoryID" => $category_1["categoryID"],
            "closed" => 0,
        ];
        $openedDiscussionData_2 = [
            "name" => "Opened Test Discussion_2",
            "format" => "text",
            "body" => "Hello Discussion_2",
            "categoryID" => $category_1["categoryID"],
            "closed" => 0,
        ];
        $openedDiscussionData_3 = [
            "name" => "Opened Test Discussion_3",
            "format" => "text",
            "body" => "Hello Discussion_3",
            "categoryID" => $category_1["categoryID"],
            "closed" => 0,
        ];
        $openedDiscussionData_4 = [
            "name" => "Opened Test Discussion_4",
            "format" => "text",
            "body" => "Hello Discussion_4",
            "categoryID" => $category_1["categoryID"],
            "closed" => 0,
        ];
        $closedDiscussionData_1 = [
            "name" => "Closed Test Discussion_1",
            "format" => "text",
            "body" => "Hello Discussion_1",
            "categoryID" => $category_1["categoryID"],
            "closed" => 1,
        ];
        $closedDiscussionData_2 = [
            "name" => "Closed Test Discussion_2",
            "format" => "text",
            "body" => "Hello Discussion_2",
            "categoryID" => $category_1["categoryID"],
            "closed" => 1,
        ];
        $closedDiscussionData_3 = [
            "name" => "Closed Test Discussion_3",
            "format" => "text",
            "body" => "Hello Discussion_3",
            "categoryID" => $category_1["categoryID"],
            "closed" => 1,
        ];
        $closedDiscussionData_4 = [
            "name" => "Closed Test Discussion_4",
            "format" => "text",
            "body" => "Hello Discussion_4",
            "categoryID" => $category_1["categoryID"],
            "closed" => 1,
        ];

        $discussion_1 = $this->api()
            ->post("/discussions", $discussionData_1)
            ->getBody();
        $discussion_2 = $this->api()
            ->post("/discussions", $discussionData_2)
            ->getBody();
        $discussion_3 = $this->api()
            ->post("/discussions", $discussionData_3)
            ->getBody();
        $discussion_4 = $this->api()
            ->post("/discussions", $discussionData_4)
            ->getBody();
        $openedDiscus_1 = $this->api()
            ->post("/discussions", $openedDiscussionData_1)
            ->getBody();
        $openedDiscus_2 = $this->api()
            ->post("/discussions", $openedDiscussionData_2)
            ->getBody();
        $openedDiscus_3 = $this->api()
            ->post("/discussions", $openedDiscussionData_3)
            ->getBody();
        $openedDiscus_4 = $this->api()
            ->post("/discussions", $openedDiscussionData_4)
            ->getBody();
        $closedDiscus_1 = $this->api()
            ->post("/discussions", $closedDiscussionData_1)
            ->getBody();
        $closedDiscus_2 = $this->api()
            ->post("/discussions", $closedDiscussionData_2)
            ->getBody();
        $closedDiscus_3 = $this->api()
            ->post("/discussions", $closedDiscussionData_3)
            ->getBody();
        $closedDiscus_4 = $this->api()
            ->post("/discussions", $closedDiscussionData_4)
            ->getBody();

        $discussionIDs = [
            $discussion_1["discussionID"],
            $discussion_2["discussionID"],
            $discussion_3["discussionID"],
            $discussion_4["discussionID"],
        ];
        $openedDiscussionIDs = [
            $openedDiscus_1["discussionID"],
            $openedDiscus_2["discussionID"],
            $openedDiscus_3["discussionID"],
            $openedDiscus_4["discussionID"],
        ];
        $closedDiscussionIDs = [
            $closedDiscus_1["discussionID"],
            $closedDiscus_2["discussionID"],
            $closedDiscus_3["discussionID"],
            $closedDiscus_4["discussionID"],
        ];
        self::$data["invalidDiscussionIDs"] = [$rd1, $rd2];
        self::$data["invalidCategory"] = $categoryInvalid;
        self::$data["validCategory1"] = $category_1;
        self::$data["validCategory2"] = $category_2;
        self::$data["validCategory3"] = $category_3;
        self::$data["category_permission"] = $category_permission;
        self::$data["category_heading"] = $category_heading;
        self::$data["discussion_1"] = $discussionData_1;
        self::$data["discussion_2"] = $discussionData_2;
        self::$data["validDiscussionIDs"] = $discussionIDs;
        self::$data["openedDiscussionIDs"] = $openedDiscussionIDs;
        self::$data["closedDiscussionIDs"] = $closedDiscussionIDs;
        self::$data["mixedIDs"] = array_merge(self::$data["validDiscussionIDs"], [1234]);
        $this->assertNotEmpty(self::$data);
    }

    /**
     * Test Failed PATCH /discussions/move
     *
     * @param string $discussionIDs
     * @param string $category
     * @param int $expectedCode
     * @param int|null $maxIterations
     * @dataProvider provideDiscussionsMoveData
     * @depends testPrepareMoveDiscussionsData
     */
    public function testFailMoveDiscussionsList(
        string $discussionIDs,
        string $category,
        int $expectedCode,
        ?int $maxIterations
    ) {
        if ($maxIterations !== null) {
            $this->getLongRunner()->setMaxIterations($maxIterations);
        }
        $this->runWithExpectedExceptionCode($expectedCode, function () use ($discussionIDs, $category) {
            $user = $category === "category_permission" ? $this->createUser() : self::$siteInfo["adminUserID"];
            $this->runWithUser(function () use ($discussionIDs, $category) {
                $this->api()->patch("/discussions/move", [
                    "discussionIDs" => self::$data[$discussionIDs],
                    "categoryID" => self::$data[$category]["categoryID"],
                    "addRedirects" => true,
                ]);
            }, $user);
        });
    }

    /**
     * Provide discussions move data.
     *
     * @return array
     */
    public function provideDiscussionsMoveData(): array
    {
        return [
            "invalid-discussion" => ["invalidDiscussionIDs", "validCategory1", 403, null],
            "invalid-category" => ["validDiscussionIDs", "invalidCategory", 404, null],
            "valid-invalidIDs" => ["mixedIDs", "validCategory2", 403, null],
            "timeout" => ["validDiscussionIDs", "validCategory3", 408, 2],
            "permission-invalid" => ["validDiscussionIDs", "category_permission", 403, null],
            "non-discussion-category-invalid" => ["validDiscussionIDs", "category_heading", 400, null],
        ];
    }

    /**
     * Test posting a discussion with a non-existing categoryID.
     */
    public function testPostInvalidCategory(): void
    {
        $this->expectException(NotFoundException::class);
        $discussionData = [
            "name" => __FUNCTION__,
            "categoryID" => rand(5000, 6000),
            "format" => "text",
            "body" => __FUNCTION__,
        ];
        $this->api()->post("/discussions", $discussionData);
    }

    /**
     * Test editing a discussion.
     */
    public function testDiscussionCanEdit(): void
    {
        $user = $this->createUser();
        $discussion = $this->runWithUser(function () {
            $data = [
                "name" => "test discussion",
                "body" => "Test discussion body",
                "format" => "text",
                "categoryID" => -1,
            ];
            $discussion = $this->api()
                ->post("/discussions", $data)
                ->getBody();
            $this->api()->post("/discussions/{$discussion["discussionID"]}", ["body" => "edited discussion"]);
            $result = $this->api()
                ->get("/discussions/{$discussion["discussionID"]}")
                ->getBody();
            $this->assertEquals("edited discussion", $result["body"]);
            return $discussion;
        }, $user);
        $this->runWithConfig(
            [
                "Garden.EditContentTimeout" => "0",
            ],
            function () use ($user, $discussion) {
                $this->api()->setUserID($user["userID"]);
                $this->expectExceptionMessage("Editing discussions is not allowed.");
                $this->expectExceptionCode(400);
                $this->api()->post("/discussions/{$discussion["discussionID"]}", ["body" => "edited discussion2"]);
            }
        );
    }

    /**
     * Test that an exception is thrown when posting to a non-discussion-type category.
     */
    public function testPostToNonDiscussionCategory()
    {
        $nestedCategory = $this->api()
            ->post("/categories", [
                "name" => "no discussions nested",
                "urlcode" => "no-discussions-nested",
                "displayAs" => "heading",
            ])
            ->getBody();

        $data = [
            "name" => "Can't post to a non-discussion category.",
            "body" => "So don't even try it.",
            "format" => "markdown",
            "categoryID" => $nestedCategory["categoryID"],
        ];

        $this->expectExceptionMessage("You are not allowed to post in categories with a display type of Heading.");
        $this->api()->post("/discussions", $data);
    }

    /**
     * Test that an exception is thrown when moving a discussion to a non-discussion-type category.
     */
    public function testPatchToNonDiscussionCategory()
    {
        $discussion = $this->insertDiscussions(1)[0];
        $headingCategory = $this->api()
            ->post("/categories", [
                "name" => "no discussions heading",
                "urlcode" => "no-discussions-heading",
                "displayAs" => "heading",
            ])
            ->getBody();

        $this->expectExceptionMessage("You are not allowed to post in categories with a display type of Heading.");
        $this->api()->patch("/discussions/{$discussion["DiscussionID"]}", [
            "categoryID" => $headingCategory["categoryID"],
        ]);
    }

    /**
     * Test PUT /discussions/:id/type with no record.
     */
    public function testPutDiscussionsTypeInvalidRecord()
    {
        $this->expectException(ClientException::class);
        $id = null;
        $convertedDiscussion = $this->api()
            ->put("/discussions/{$id}/type", ["type" => "discussion"])
            ->getBody();
        $this->assertEquals("discussion", $convertedDiscussion["type"]);
    }

    /**
     * Test that a permission error is thrown when a member tries to post a discussion with a pin location.
     */
    public function testPostWithPinLocation(): void
    {
        $this->expectExceptionMessage("Permission Problem");
        $this->runWithUser(function () {
            $this->createDiscussion(["pinLocation" => "category"]);
        }, $this->memberID);
    }

    /**
     * Test that a permission error is thrown when a member tries to patch a discussion with a pin location.
     */
    public function testPatchWithPinLocation(): void
    {
        $this->expectExceptionMessage("Permission Problem");
        $this->runWithUser(function () {
            $discussion = $this->createDiscussion();
            $this->api()->patch("/discussions/{$discussion["discussionID"]}", ["pinLocation" => "category"]);
        }, $this->memberID);
    }

    /**
     * Get the resource type.
     *
     * @return array
     */
    protected function getResourceInformation(): array
    {
        return [
            "resourceType" => "discussion",
            "primaryKey" => "discussionID",
        ];
    }

    /**
     * Test that crawl expands use a prev/next pager with no count.
     */
    public function testIndexCrawlPager()
    {
        $this->createDiscussion();
        $this->createDiscussion();
        $r = $this->api()->get("/discussions", ["expand" => ["crawl"], "limit" => 1]);
        $paging = ApiUtils::parsePageHeader($r->getHeader("Link"));
        self::assertArrayHasKey("next", $paging);
        self::assertArrayNotHasKey("last", $paging);
    }

    /**
     * Test fetching only the announcements of a specific category.
     *
     * @return void
     */
    public function testGetCategoryAnnouncement()
    {
        $this->createCategory();
        $this->createDiscussion(["pinned" => true, "categoryID" => -1, "pinLocation" => "recent"]);
        $this->createDiscussion(["pinned" => true, "pinLocation" => "category"]);
        $this->createDiscussion();

        $discussions = $this->api()
            ->get($this->baseUrl, ["pinned" => true, "categoryID" => $this->lastInsertedCategoryID])
            ->getBody();

        // The table might already contain some discussions
        foreach ($discussions as $discussion) {
            $this->assertTrue($discussion["pinned"], "Unexpected non-pinned discussion.");
            $this->assertEquals($this->lastInsertedCategoryID, $discussion["categoryID"]);
        }
    }

    /**
     * Test that fetching the discussions with pinOrder = first returns the global announcements first
     * and that the discussions are sorted by descending DateLastComment.
     *
     * @return void
     */
    public function testIndexDefaultSortPinOrderFirst(): void
    {
        $announcement = $this->createDiscussion(["pinned" => true]);
        $newDiscussion = $this->createDiscussion();

        $discussions = $this->api()
            ->get($this->baseUrl, ["pinOrder" => "first"])
            ->getBody();

        $lastAnnouncementDate = $announcement["dateLastComment"];
        $lastDiscussionDate = $newDiscussion["dateLastComment"];
        $isProcessingAnnouncements = true;

        // These assertions are expected a bunch of random other discussions to have been in the test suite.
        // That is why it is not asserting a specific order.
        foreach ($discussions as $discussion) {
            if ($discussion["pinned"] && $discussion["pinLocation"] == "recent") {
                $this->assertLessThanOrEqual(
                    $lastAnnouncementDate,
                    $discussion["dateLastComment"],
                    "Unexpected announcement dates out of order."
                );
                $this->assertTrue(
                    $isProcessingAnnouncements,
                    "Unexpected pinned discussion after a normal discussion."
                );
                $lastAnnouncementDate = $discussion["dateLastComment"];
            } else {
                $this->assertLessThanOrEqual(
                    $lastDiscussionDate,
                    $discussion["dateLastComment"],
                    "Unexpected announcement dates out of order."
                );
                $isProcessingAnnouncements = false;
                $lastDiscussionDate = $discussion["dateLastComment"];
            }
        }
    }

    /**
     * Test that fetching the discussions with pinOrder = first and a categoryID returns the category announcements first.
     *
     * @return void
     */
    public function testIndexSortPinOrderFirstWithCategory(): void
    {
        $this->createCategory();
        $this->createDiscussion();
        $announcement = $this->createDiscussion(["pinned" => true]);
        $lastAnnouncementDate = $announcement["dateLastComment"];
        $newDiscussion = $this->createDiscussion();
        $lastDiscussionDate = $newDiscussion["dateLastComment"];
        $isProcessingAnnouncements = true;

        $discussions = $this->api()
            ->get($this->baseUrl, ["pinOrder" => "first", "categoryID" => $this->lastInsertedCategoryID])
            ->getBody();

        foreach ($discussions as $discussion) {
            if ($discussion["pinned"]) {
                $this->assertLessThanOrEqual(
                    $lastAnnouncementDate,
                    $discussion["dateLastComment"],
                    "Unexpected announcement dates out of order."
                );
                $this->assertTrue(
                    $isProcessingAnnouncements,
                    "Unexpected pinned discussion after a normal discussion."
                );
                $lastAnnouncementDate = $discussion["dateLastComment"];
            } else {
                $this->assertLessThanOrEqual(
                    $lastDiscussionDate,
                    $discussion["dateLastComment"],
                    "Unexpected announcement dates out of order."
                );
                $isProcessingAnnouncements = false;
                $lastDiscussionDate = $discussion["dateLastComment"];
            }
        }
    }

    /**
     * Test that fetching the discussions with pinOrder = mixed returns the discussions sorted by descending DateLastComment.
     *
     * @return void
     */
    public function testIndexDefaultSortPinOrderMixed(): void
    {
        $discussion = $this->createDiscussion();
        $lastDiscussionDate = $discussion["dateLastComment"];

        $discussions = $this->api()
            ->get($this->baseUrl, ["pinOrder" => "mixed"])
            ->getBody();

        foreach ($discussions as $discussion) {
            $this->assertLessThanOrEqual(
                $lastDiscussionDate,
                $discussion["dateLastComment"],
                "Unexpected announcement dates out of order."
            );
            $lastDiscussionDate = $discussion["dateLastComment"];
        }
    }

    /**
     * Test fetching non announcements discussions.
     *
     * @return void
     */
    public function testNonAnnouncement()
    {
        $this->createCategory();
        $this->createDiscussion(["pinned" => true, "categoryID" => -1, "pinLocation" => "recent"]);
        $this->createDiscussion(["pinned" => true, "pinLocation" => "category"]);
        $this->createDiscussion();

        $discussions = $this->api()
            ->get($this->baseUrl, ["pinned" => false])
            ->getBody();

        // The table might already contain some discussions
        foreach ($discussions as $discussion) {
            $this->assertFalse(
                $discussion["pinned"] && $discussion["pinLocation"] != "category",
                "Unexpected pinned discussion."
            );
        }
    }

    /**
     * Test the wyswig content get properly formatted
     *
     * @return void
     */
    public function testFormatting()
    {
        $config = \Gdn::config();
        $config->set("Garden.InputFormatter", "Wysiwyg");
        $content = "<p><b>Et si on lançait un fil de discussion sur la gratitude?</b><br></p>\r\r
<p>C’est l’idée qui vient de me passer par la tête, et a
laquelle j’ai envie de donner vie.</p>";

        $discussion = $this->createDiscussion([
            "name" => "Format test",
            "format" => WysiwygFormat::FORMAT_KEY,
            "body" => $content,
        ]);
        $query = ["expand" => "excerpt"];
        $expected =
            "Et si on lançait un fil de discussion sur la gratitude? C’est l’idée qui vient de me passer par la tête, et alaquelle j’ai envie de donner vie.";
        $result = $this->api()
            ->get("$this->baseUrl/{$discussion["discussionID"]}", $query)
            ->getBody();
        $this->assertArrayHasKey("excerpt", $result);
        $this->assertEquals($expected, $result["excerpt"]);
    }

    /**
     * Category filtering for discussion index endpoint
     *
     * @return void
     */
    public function testDiscussionIndexFilterByCategories(): void
    {
        $topCategory = $this->createCategory([
            "name" => "Top Category",
            "parentCategoryID" => -1,
        ]);
        $secondCategory = $this->createCategory([
            "name" => "Secondary Category",
            "parentCategoryID" => -1,
        ]);
        $subCategory = $this->createCategory([
            "name" => "SubCategory",
            "parentCategoryID" => $topCategory["categoryID"],
        ]);

        //Create discussions

        $discussionTopCategory = $this->createDiscussion([
            "name" => "Top Category Discussion",
            "categoryID" => $topCategory["categoryID"],
        ]);
        $discussionSubCategory = $this->createDiscussion([
            "name" => "Sub Category Discussion",
            "categoryID" => $subCategory["categoryID"],
        ]);
        $discussionSecondCategory = $this->createDiscussion([
            "name" => "Second Category Discussion",
            "categoryID" => $secondCategory["categoryID"],
        ]);

        // passing single category
        $result = $this->api()
            ->get($this->baseUrl, ["categoryID" => $topCategory["categoryID"]])
            ->getBody();
        $this->assertCount(1, $result);
        $this->assertEquals($discussionTopCategory["discussionID"], $result[0]["discussionID"]);

        //passing multiple category

        $result = $this->api()
            ->get($this->baseUrl, ["categoryID" => [$topCategory["categoryID"], $secondCategory["categoryID"]]])
            ->getBody();
        $this->assertCount(2, $result);
        $discussionIDs = array_column($result, "discussionID");
        $this->assertEqualsCanonicalizing(
            [$discussionTopCategory["discussionID"], $discussionSecondCategory["discussionID"]],
            $discussionIDs
        );

        //Test for child categories

        $result = $this->api()
            ->get($this->baseUrl, [
                "categoryID" => [$topCategory["categoryID"], $secondCategory["categoryID"]],
                "includeChildCategories" => true,
            ])
            ->getBody();
        $this->assertCount(3, $result);

        $discussionIDs = array_column($result, "discussionID");

        $this->assertEqualsCanonicalizing(
            [
                $discussionTopCategory["discussionID"],
                $discussionSubCategory["discussionID"],
                $discussionSecondCategory["discussionID"],
            ],
            $discussionIDs
        );
    }

    /**
     * Test that category index endpoint throws error if we provide non comma seperated category ids
     *
     * @return void
     */
    public function testDiscussionWithCategoryRangeThrowsException()
    {
        $this->expectExceptionMessage("Invalid category argument received. You must provide comma seperated values.");
        $this->expectException(ServerException::class);
        $this->api()->get($this->baseUrl, [
            "categoryID" => "[10, 15]",
        ]);
    }

    /**
     * Test filter categories with permission on discussion endpoint
     *
     * @return void
     */
    public function testForDiscussionIndexFilterByPermissionCategory(): void
    {
        // Category without any visibility restrictions
        $publicCategory = $this->createCategory([
            "name" => "Public Category",
            "parentCategoryID" => -1,
        ]);

        //Category with visibility restrictions
        $permissionCategory = $this->createPermissionedCategory(
            ["name" => "Admin Members Only", "parentCategoryID" => -1],
            [\RoleModel::ADMIN_ID]
        );

        $permissionSubCategory = $this->createPermissionedCategory(
            [
                "name" => "Admin SubCategory",
                "parentCategoryID" => $permissionCategory["categoryID"],
            ],
            [\RoleModel::ADMIN_ID]
        );

        $publicDiscussion = $this->createDiscussion([
            "name" => "Public Discussion",
            "categoryID" => $publicCategory["categoryID"],
        ]);

        $privateDiscussion = $this->createDiscussion([
            "name" => "Private Discussion",
            "categoryID" => $permissionCategory["categoryID"],
        ]);

        $privateSubDiscussion = $this->createDiscussion([
            "name" => "Private Sub Discussion",
            "categoryID" => $permissionSubCategory["categoryID"],
        ]);

        // Now run as a Admin user you should get all the 3 discussions
        $this->runWithUser(function () use ($publicDiscussion, $privateDiscussion, $privateSubDiscussion) {
            $result = $this->api()
                ->get($this->baseUrl, [
                    "categoryID" => [$publicDiscussion["categoryID"], $privateDiscussion["categoryID"]],
                    "includeChildCategories" => true,
                ])
                ->getBody();
            $this->assertCount(3, $result);
            $discussionIDs = array_column($result, "discussionID");
            $this->assertEqualsCanonicalizing(
                [
                    $publicDiscussion["discussionID"],
                    $privateDiscussion["discussionID"],
                    $privateSubDiscussion["discussionID"],
                ],
                $discussionIDs
            );
        }, $this->adminID);

        // Query as a guest

        DiscussionModel::cleanForTests();
        $this->runWithUser(function () use ($publicDiscussion, $privateDiscussion) {
            $result = $this->api()
                ->get($this->baseUrl, [
                    "categoryID" => [$publicDiscussion["categoryID"], $privateDiscussion["categoryID"]],
                    "includeChildCategories" => true,
                ])
                ->getBody();
            //Should get only public discussion as private discussions are not available to guest
            $this->assertCount(1, $result);
            $this->assertEquals($publicDiscussion["discussionID"], $result[0]["discussionID"]);
        }, 0);
    }

    /**
     * Test that we are able to expand by
     *
     * @return void
     */
    public function testDiscussionApiExpandsOnSnippet(): void
    {
        $content = "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae,
ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque
sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi. Aenean fermentum, elit eget
tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus,
neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor,
facilisis luctus, metus</p>";
        $discussion = $this->createDiscussion([
            "name" => "Snippet test test",
            "body" => $content,
        ]);

        $query = ["expand" => ["snippet", "excerpt"]];
        $result = $this->api()
            ->get("$this->baseUrl/{$discussion["discussionID"]}", $query)
            ->getBody();

        $this->assertArrayHasKey("snippet", $result);
        $this->assertTrue(strlen($result["snippet"]) <= 180);
    }

    /**
     * Test that calling the `/discussions/{id}/bump` endpoint updates the discussion's DateLastComment field.
     *
     * @return void
     */
    public function testDiscussionBump(): void
    {
        // Post a discussion and a comment yesterday.
        $oneDayAgo = CurrentTimeStamp::mockTime("-1 day");
        $discussion = $this->createDiscussion();
        $this->createComment();

        $discussion = $this->api()
            ->get("{$this->baseUrl}/{$discussion["discussionID"]}")
            ->getBody();
        $this->assertSame(
            $oneDayAgo->getTimestamp(),
            DateTimeFormatter::dateTimeToTimeStamp($discussion["dateLastComment"])
        );

        $now = CurrentTimeStamp::mockTime("now");

        // Bump the discussion.
        $this->api()->patch("{$this->baseUrl}/{$discussion["discussionID"]}/bump");

        // Make sure it's been bumped.
        $bumpedDiscussion = $this->api()
            ->get("{$this->baseUrl}/{$discussion["discussionID"]}")
            ->getBody();
        $this->assertSame(
            $now->getTimestamp(),
            DateTimeFormatter::dateTimeToTimeStamp($bumpedDiscussion["dateLastComment"])
        );
    }

    /**
     * Test that you cannot bump a permission without the "Curation.Manage" permission.
     *
     * @return void
     */
    public function testBumpPermission(): void
    {
        $this->expectExceptionMessage("Permission Problem");
        $this->expectExceptionCode(403);
        $this->runWithUser(function () {
            $discussion = $this->createDiscussion();
            $this->api()->patch("{$this->baseUrl}/{$discussion["discussionID"]}/bump");
        }, $this->memberID);
    }

    /**
     * Test changing the author of a discussion with the correct permissions.
     *
     * @return void
     */
    public function testChangeAuthorWithPermission(): void
    {
        // Set up two authors and a discussion.
        $originalAuthor = $this->createUser();
        $newAuthor = $this->createUser();
        $this->api()->setUserID($originalAuthor["userID"]);
        $discussion = $this->createDiscussion();

        $this->api()->setUserID(self::$siteInfo["adminUserID"]);

        $originalAuthorInfo = $this->api()
            ->get("/users/{$originalAuthor["userID"]}")
            ->getBody();

        // Change the author.
        $this->api()->patch("{$this->baseUrl}/{$discussion["discussionID"]}", [
            "insertUserID" => $newAuthor["userID"],
        ]);

        // The original author's discussion count should be lower by 1.
        $updatedOriginalAuthorInfo = $this->api()
            ->get("/users/{$originalAuthor["userID"]}")
            ->getBody();
        $this->assertTrue(
            $updatedOriginalAuthorInfo["countDiscussions"] == $originalAuthorInfo["countDiscussions"] - 1
        );

        // The new author should have the discussion reflected in their countDiscussion value.
        $newAuthorInfo = $this->api()
            ->get("/users/{$newAuthor["userID"]}")
            ->getBody();
        $this->assertTrue($newAuthorInfo["countDiscussions"] == 1);
    }

    /**
     * Test changing the author of a discussion without the correct permissions.
     *
     * @return void
     */
    public function testAuthorChangeWithoutPermission(): void
    {
        // Set up two authors and a discussion.
        $originalAuthor = $this->createUser();
        $newIntendedAuthor = $this->createUser();
        $this->api()->setUserID($originalAuthor["userID"]);
        $discussion = $this->createDiscussion();

        // Try changing the author.
        $this->expectExceptionMessage("Permission Problem");
        $this->expectExceptionCode(403);
        $this->api()->patch("{$this->baseUrl}/{$discussion["discussionID"]}", [
            "insertUserID" => $newIntendedAuthor["userID"],
        ]);
    }

    /**
     * Test the [PUT] `/api/v2/discussions/{id}/dismiss endpoint.
     *
     * @return void
     */
    public function testDiscussionDismissal(): void
    {
        $this->createDiscussion();
        $this->createDiscussion(["pinned" => true, "pinLocation" => "category"]);

        $announcement = $this->api()
            ->get("discussions", ["pinOrder" => "first"])
            ->getBody()[0];
        $this->assertTrue($announcement["pinned"]);

        // Dismiss the first announcement and make sure it no longer shows up on top.
        $this->api()->put("discussions/{$announcement["discussionID"]}/dismiss");
        $result = $this->api()
            ->get("discussions", ["pinOrder" => "first"])
            ->getBody();
        $this->assertNotEquals($result[0]["discussionID"], $announcement["discussionID"]);

        // Un-dismiss the first announcement and make sure it shows up on top again.
        $this->api()->put("discussions/{$announcement["discussionID"]}/dismiss", ["dismissed" => false]);
        $result = $this->api()
            ->get("discussions", ["pinOrder" => "first"])
            ->getBody();
        $this->assertEquals($result[0]["discussionID"], $announcement["discussionID"]);
    }

    /**
     * Test expanding attachments via the "/discussions" endpoint.
     *
     * @return void
     */
    public function testExpandDiscussionsAttachments(): void
    {
        $discussion = $this->createDiscussion();
        $attachment = $this->createAttachment("discussion", $discussion["discussionID"]);
        $result = $this->api()
            ->get($this->baseUrl, ["expand" => "attachments", "discussionID" => $discussion["discussionID"]])
            ->getBody();
        $retrievedDiscussion = $result[0];
        $this->assertArrayHasKey("attachments", $retrievedDiscussion);
        $this->assertEquals($attachment["AttachmentID"], $retrievedDiscussion["attachments"][0]["attachmentID"]);
    }

    /**
     * @return void
     */
    public function testUserFilters()
    {
        $this->resetTable("Discussion");
        $user1 = $this->createUser([
            "roleID" => [\RoleModel::MOD_ID, \RoleModel::MEMBER_ID],
        ]);
        $user2 = $this->createUser([
            "roleID" => [\RoleModel::MEMBER_ID],
        ]);

        $disc1 = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $user1);
        $disc2 = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $user2);

        $this->assertApiResults(
            "/discussions",
            ["insertUserID" => $user1["userID"]],
            ["discussionID" => [$disc1["discussionID"]]]
        );

        $this->assertApiResults(
            "/discussions",
            ["insertUserID" => $user2["userID"]],
            ["discussionID" => [$disc2["discussionID"]]]
        );

        $this->assertApiResults(
            "/discussions",
            ["insertUserRoleID" => \RoleModel::MEMBER_ID],
            ["discussionID" => [$disc1["discussionID"], $disc2["discussionID"]]]
        );

        $this->assertApiResults(
            "/discussions",
            ["insertUserRoleID" => \RoleModel::MOD_ID],
            ["discussionID" => [$disc1["discussionID"]]]
        );
    }

    /**
     * Test to ensure joining the UserRole table doesn't duplicate discussion records.
     *
     * @return void
     */
    public function testRoleFilterNoDuplication()
    {
        $this->resetTable("Discussion");
        $user1 = $this->createUser([
            "roleID" => [\RoleModel::MOD_ID, \RoleModel::MEMBER_ID],
        ]);

        $disc1 = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $user1);

        $this->assertApiResults(
            "/discussions",
            ["insertUserRoleID" => [\RoleModel::MOD_ID, \RoleModel::MEMBER_ID]],
            ["discussionID" => [$disc1["discussionID"]]],
            true,
            1
        );
    }
}
