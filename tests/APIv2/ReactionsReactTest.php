<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\NotFoundException;
use ReactionModel;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test Reaction API capabilities.
 */
class ReactionsReactTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait {
        createDiscussion as traitCreateDiscussion;
    }
    use ExpectExceptionTrait;

    public static $addons = ["stubcontent"];

    /** @var \LogModel */
    private $logModel;

    /**
     * Setup routine, run before each test case.
     */
    public function setUp(): void
    {
        parent::setUp();
        ReactionModel::$ReactionTypes = null;
        $this->logModel = self::container()->get(\LogModel::class);
    }

    /**
     * Test changing a user reaction from one type to another.
     */
    public function testChangeReaction()
    {
        $this->api()->post("/discussions/1/reactions", [
            "reactionType" => "Like",
        ]);
        $reactions = $this->api()->get("/discussions/1/reactions");
        $this->assertTrue($this->hasUserReaction($this->api()->getUserID(), "Like", $reactions->getBody()));
        $this->assertFalse($this->hasUserReaction($this->api()->getUserID(), "LOL", $reactions->getBody()));

        $this->api()->post("/discussions/1/reactions", [
            "reactionType" => "LOL",
        ]);
        $reactions = $this->api()->get("/discussions/1/reactions");
        $this->assertTrue($this->hasUserReaction($this->api()->getUserID(), "LOL", $reactions->getBody()));
        $this->assertFalse($this->hasUserReaction($this->api()->getUserID(), "Like", $reactions->getBody()));
    }

    /**
     * Test a user adding the same reaction to the same post, twice.
     */
    public function testDuplicateReaction()
    {
        $this->api()->post("/discussions/1/reactions", [
            "reactionType" => "Like",
        ]);
        $summary = $this->api()->post("/discussions/1/reactions", [
            "reactionType" => "Like",
        ]);

        $this->assertEquals(1, $this->getSummaryCount("Like", $summary->getBody()));

        $reactions = $this->api()
            ->get("/discussions/1/reactions")
            ->getBody();
        $currentUserReactions = 0;
        foreach ($reactions as $row) {
            if ($row["user"]["userID"] == $this->api()->getUserID()) {
                $currentUserReactions++;
            }
        }
        $this->assertEquals(1, $currentUserReactions);
    }

    /**
     * Test reacting to a comment.
     */
    public function testPostCommentReaction()
    {
        $type = "Like";
        $response = $this->api()->post("/comments/1/reactions", [
            "reactionType" => $type,
        ]);
        $this->assertEquals(201, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertIsArray($body);
        $this->assertSummaryHasReactionType($type, $body);
    }

    /**
     * Test getting reactions to a comment.
     *
     * @depends testPostCommentReaction
     */
    public function testGetCommentReactions()
    {
        $type = "Like";
        $this->api()->post("/comments/1/reactions", [
            "reactionType" => $type,
        ]);

        $response = $this->api()->get("/comments/1/reactions");
        $body = $response->getBody();
        $this->assertIsArray($body);
        $this->assertNotEmpty($body);
        $this->assertTrue($this->hasUserReaction($this->api()->getUserID(), $type, $body));
    }

    /**
     * Test undoing a reaction to a comment.
     *
     * @depends testGetCommentReactions
     */
    public function testDeleteCommentReaction()
    {
        $type = "Like";

        $user = $this->createUser();
        $userID = (int) $user["userID"];
        $this->runWithUser(function () use ($type) {
            $this->api()->post("/comments/1/reactions", [
                "reactionType" => $type,
            ]);
        }, $user);

        // Run as admin, to remove comment reactions for the user selected.
        $getResponse = $this->api()->get("/comments/1/reactions");
        $this->assertTrue($this->hasUserReaction($userID, $type, $getResponse->getBody()));
        $this->api()->delete("/comments/1/reactions/{$userID}");

        $response = $this->api()->get("/comments/1/reactions");
        $this->assertFalse($this->hasUserReaction($userID, $type, $response->getBody()));
    }

    /**
     * Test ability to expand reactions on a comment.
     */
    public function testExpandCommentReactions()
    {
        $getResponse = $this->api()->get("/comments/1", ["expand" => "reactions"]);
        $getBody = $getResponse->getBody();
        $this->assertIsReactionSummary($getBody["reactions"]);

        $indexResponse = $this->api()->get("/comments", [
            "discussionID" => $getBody["discussionID"],
            "expand" => "reactions",
        ]);
        $indexBody = $indexResponse->getBody();
        $this->assertNotEmpty($indexBody);
        foreach ($indexBody as $row) {
            $this->assertIsReactionSummary($row["reactions"]);
        }
    }

    /**
     * Test expand reactions.
     */
    public function testExpandCommentReactionsSelf()
    {
        $this->api()->patch("/reactions/dislike", ["active" => true]);
        $userMember = $this->createUser();
        $this->createDiscussion(1, "Test Discussion Reaction");
        $comment = $this->createComment();

        $this->runWithUser(function () use ($comment) {
            $this->api()->post("/comments/{$comment["commentID"]}/reactions", [
                "reactionType" => "dislike",
            ]);
            $reactionScore = ReactionModel::getReactionTypes()["dislike"]["IncrementValue"];
            $responseBody = $this->api()
                ->get("/comments/{$comment["commentID"]}", ["expand" => "reactions"])
                ->getBody();
            $this->assertIsReactionSummary($responseBody["reactions"]);

            $reactionsByName = array_column($responseBody["reactions"], null, "name");
            $this->assertArrayHasKey("Dislike", $reactionsByName);
            $this->assertTrue($reactionsByName["Dislike"]["hasReacted"]);
            $this->assertEquals($reactionScore, $reactionsByName["Dislike"]["reactionValue"]);
            $this->assertTrue($reactionsByName["Dislike"]["reactionValue"] < 0);

            $this->assertArrayHasKey("Like", $reactionsByName);
            $this->assertFalse($reactionsByName["Like"]["hasReacted"]);
        }, $userMember);
    }

    /**
     * Test reacting to a discussion.
     */
    public function testPostDiscussionReaction()
    {
        $type = "Like";
        $response = $this->api()->post("/discussions/1/reactions", [
            "reactionType" => $type,
        ]);
        $this->assertEquals(201, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertIsArray($body);
        $this->assertSummaryHasReactionType($type, $body);
    }

    /**
     * Test marking a discussion as spam.
     */
    public function testPostDiscussionSpamReaction(): void
    {
        $type = "Spam";
        // Create member user.
        $memberUser = $this->createUser();

        // Create discussions with a member role.
        [$discussionIDA, $discussionIDB] = $this->runWithUser(function () {
            // Create test discussions as a member.
            $discussionA = $this->createDiscussion(1, "Test DiscussionA");
            $discussionB = $this->createDiscussion(1, "Test DiscussionB");

            return [$discussionA["discussionID"], $discussionB["discussionID"]];
        }, $memberUser);

        // Admin marks post as spam, post should be deleted, and moved to the log
        $this->runWithExpectedExceptionCode(404, function () use ($discussionIDA, $type) {
            $this->api()->post("/discussions/${discussionIDA}/reactions", [
                "reactionType" => $type,
            ]);
        });

        $logCountA = $this->logModel->getCountWhere(["Operation" => "Spam", "RecordUserID" => $memberUser["userID"]]);
        $this->assertEquals(1, $logCountA);

        // Member creates 10 comments.
        $this->runWithUser(function () use ($discussionIDB) {
            $this->generateComments(10, $discussionIDB);
        }, $memberUser);

        // Moderator marks post as Spam, Post doesn't get deleted (>= 10 comments).
        $response = $this->api()->post("/discussions/${discussionIDB}/reactions", [
            "reactionType" => $type,
        ]);
        $statusSuccess = $response->getStatusCode();
        $this->assertEquals("201", $statusSuccess);
        $logCountB = $this->logModel->getCountWhere(["Operation" => "Spam", "RecordUserID" => $memberUser["userID"]]);
        $this->assertEquals(2, $logCountB);
    }

    /**
     * Post some records and return data to test the endpoint for getting a user's posts that have been reacted to.
     *
     * @return array
     */
    private function prepareReactedRecordsData()
    {
        $user = $this->createUser();

        // As a member, create a discussion and some comments that will be reacted to.
        $discussion = $this->runWithUser(function () {
            // Post a discussion and a comment.
            $discussion = $this->createDiscussion(1, "testReactedRecordsDiscussion");
            $discussionID = $discussion["discussionID"];
            $this->generateComments(3, $discussionID);
            return $discussion;
        }, $user);

        // Post some reactions.
        $type1 = "Like";
        $type2 = "Dislike";
        $this->api()->post("/discussions/{$discussion["discussionID"]}/reactions", ["reactionType" => $type1]);
        $comments = $this->api()
            ->get("/comments", ["discussionID" => $discussion["discussionID"]])
            ->getBody();
        $this->api()->post("/comments/{$comments[0]["commentID"]}/reactions", ["reactionType" => $type1]);
        $this->api()->post("/comments/{$comments[1]["commentID"]}/reactions", ["reactionType" => $type2]);

        // Edit the title of the disliked record (so we can test expanding updateUser).
        $this->api()->patch("/comments/{$comments[1]["commentID"]}", ["body" => "edited!"]);

        $returnData = [
            "userID" => $user["userID"],
            "type1" => $type1,
            "type2" => $type2,
            "discussion" => $discussion,
            "comments" => $comments,
        ];
        return $returnData;
    }

    /**
     * Test getting a user's records that have a specific reactions.
     */
    public function testGetReactedRecords()
    {
        // Get the records by reaction and make sure the correct records come back.
        $prepData = $this->prepareReactedRecordsData();
        [
            "userID" => $userID,
            "type1" => $type1,
            "type2" => $type2,
            "discussion" => $discussion,
            "comments" => $comments,
        ] = $prepData;

        $likedRecords = $this->api()
            ->get("/users/{$userID}/reacted", ["reactionUrlcode" => $type1])
            ->getBody();
        $this->assertCount(2, $likedRecords);
        $recordIDs = array_column($likedRecords, "recordID");
        $this->assertContains($discussion["discussionID"], $recordIDs);
        $this->assertContains($comments[0]["commentID"], $recordIDs);

        $dislikedRecords = $this->api()
            ->get("/users/{$userID}/reacted", ["reactionUrlCode" => $type2])
            ->getBody();
        $this->assertCount(1, $dislikedRecords);
        $this->assertSame($comments[1]["commentID"], $dislikedRecords[0]["recordID"]);
    }

    /**
     * Test expanding the reacted-to records by insertUser.
     */
    public function testGetReactedExpandInsertUser()
    {
        $prepData = $this->prepareReactedRecordsData();
        ["userID" => $userID, "type2" => $type2] = $prepData;

        // Expand insertUser
        $dislikedExpandedUser = $this->api()
            ->get("users/{$userID}/reacted", ["reactionUrlcode" => $type2, "expand" => "insertUser"])
            ->getBody();
        $expandedUserInfo = $dislikedExpandedUser[0]["insertUser"];
        $expandUserFields = ["userID", "name", "url", "photoUrl", "dateLastActive"];
        foreach ($expandUserFields as $field) {
            $this->assertArrayHasKey($field, $expandedUserInfo);
        }
    }

    /**
     * Test expanding the reacted-to records by updateUser.
     */
    public function testGetReactedExpandUpdateUser()
    {
        $prepData = $this->prepareReactedRecordsData();
        ["userID" => $userID, "type2" => $type2] = $prepData;

        $dislikedExpandedUpdateUser = $this->api()
            ->get("users/{$userID}/reacted", ["reactionUrlcode" => $type2, "expand" => "updateUser"])
            ->getBody();
        $expandedUpdateUserInfo = $dislikedExpandedUpdateUser[0]["updateUser"];
        $expandUpdateUserFields = ["userID", "name", "url", "photoUrl", "dateLastActive"];
        foreach ($expandUpdateUserFields as $field) {
            $this->assertArrayHasKey($field, $expandedUpdateUserInfo);
        }
    }

    /**
     * Test expanding the reacted-to records by reactions.
     */
    public function testGetReactedExpandReactions()
    {
        $prepData = $this->prepareReactedRecordsData();
        ["userID" => $userID, "type2" => $type2] = $prepData;

        // Expand reactions
        $dislikedExpandReactions = $this->api()
            ->get("users/{$userID}/reacted", ["reactionUrlcode" => $type2, "expand" => "reactions"])
            ->getBody();
        $expandReactionsInfo = $dislikedExpandReactions[0]["reactions"];
        $this->assertIsReactionSummary($expandReactionsInfo);
    }

    /**
     * Test expanding everything on the reacted-to records
     */
    public function testGetReactedExpandAll()
    {
        $prepData = $this->prepareReactedRecordsData();
        ["userID" => $userID, "type2" => $type2] = $prepData;

        $expandUserFields = ["userID", "name", "url", "photoUrl", "dateLastActive"];
        $expandReactionFields = ["tagID", "urlcode", "name", "class", "count"];

        // Expand all
        $dislikeExpandAll = $this->api()
            ->get("users/{$userID}/reacted", ["reactionUrlcode" => $type2, "expand" => "all"])
            ->getBody();

        $expandAllUserInfo = $dislikeExpandAll[0]["insertUser"];
        foreach ($expandUserFields as $field) {
            $this->assertArrayHasKey($field, $expandAllUserInfo);
        }

        $expandAllUpdateUserInfo = $dislikeExpandAll[0]["updateUser"];
        foreach ($expandUserFields as $field) {
            $this->assertArrayHasKey($field, $expandAllUpdateUserInfo);
        }

        $expandAllReactionInfo = $dislikeExpandAll[0]["reactions"];
        $this->assertIsReactionSummary($expandAllReactionInfo);
    }

    /**
     * Test getting records by a reaction the user's posts have never received.
     */
    public function testGetReactedNoRecords()
    {
        $prepData = $this->prepareReactedRecordsData();
        ["userID" => $userID] = $prepData;

        // If no discussions match, we should get an empty array back.
        $awesomePosts = $this->api()
            ->get("users/{$userID}/reacted", ["reactionUrlcode" => "Awesome"])
            ->getBody();
        $this->assertEmpty($awesomePosts);
    }

    /**
     * Test getting records by a reaction that doesn't exist.
     */
    public function testGetReactedPhantomReaction()
    {
        $prepData = $this->prepareReactedRecordsData();
        ["userID" => $userID] = $prepData;

        // If the reactionUrlcode doesn't correspond to any reaction, we should get an error.
        $this->expectException(\Garden\Web\Exception\NotFoundException::class);
        $this->expectExceptionMessage("Reaction not found.");
        $this->api()->get("users/{$userID}/reacted", ["reactionUrlcode" => "Phantom"]);
    }

    /**
     * Create discussion.
     *
     * @param int $categoryID Number of Comments to generate.
     * @param string $name Discussion name.
     *
     * @return array $discussion
     */
    private function createDiscussion(int $categoryID, string $name): array
    {
        // Member creates a discussion.
        return $this->traitCreateDiscussion([
            "categoryID" => $categoryID,
            "name" => $name,
        ]);
    }

    /**
     * Generate comments
     *
     * @param int $counter Number of Comments to generate.
     * @param int $discussionID DiscussionID.
     */
    private function generateComments(int $counter, int $discussionID): void
    {
        for ($i = 0; $i < $counter; $i++) {
            $this->api()->post("/comments", [
                "body" => "test comment" . $i,
                "format" => "Markdown",
                "discussionID" => $discussionID,
            ]);
        }
    }

    /**
     * Test getting reactions to a discussion.
     *
     * @depends testPostDiscussionReaction
     */
    public function testGetDiscussionReactions()
    {
        $type = "Like";
        $this->api()->post("/discussions/1/reactions", [
            "reactionType" => $type,
        ]);

        $response = $this->api()->get("/discussions/1/reactions");
        $body = $response->getBody();
        $this->assertIsArray($body);
        $this->assertNotEmpty($body);
        $this->assertTrue($this->hasUserReaction($this->api()->getUserID(), $type, $body));
    }

    /**
     * Test the discussions index filtering by user and reaction type.
     */
    public function testGetDiscussionsByUserReaction()
    {
        $type = "Like";

        $discussion1 = $this->createDiscussion(1, "testGetDiscussionsByUserReaction");
        $discussion2 = $this->createDiscussion(1, "testGetDiscussionsByUserReactionPart2");

        $this->runWithUser(function () use ($type, $discussion1, $discussion2) {
            $this->api()->post("/discussions/${discussion1["discussionID"]}/reactions", [
                "reactionType" => $type,
            ]);
            $this->api()->post("/discussions/${discussion2["discussionID"]}/reactions", [
                "reactionType" => $type,
            ]);

            $newLikedDiscussions = $this->api()
                ->get("/discussions?reactionType=${type}&expand=reactions")
                ->getBody();

            $this->assertCount(2, $newLikedDiscussions);
        }, $this->createUser());
    }

    /**
     * Test undoing a reaction to a discussion.
     *
     * @depends testGetCommentReactions
     */
    public function testDeleteDiscussionReaction()
    {
        $type = "Like";
        $user = $this->createUser();
        $userID = (int) $user["userID"];
        $this->runWithUser(function () use ($type, $userID) {
            $this->api()->post("/discussions/1/reactions", [
                "reactionType" => $type,
            ]);
            $postResponse = $this->api()->get("/discussions/1/reactions");
            $this->assertTrue($this->hasUserReaction($this->api()->getUserID(), $type, $postResponse->getBody()));
            $this->api()->delete("/discussions/1/reactions/{$userID}");
            $response = $this->api()->get("/discussions/1/reactions");
            $this->assertFalse($this->hasUserReaction($this->api()->getUserID(), $type, $response->getBody()));
        }, $userID);
    }

    /**
     * Test ability to expand reactions on a discussion.
     */
    public function testExpandDiscussionReactions()
    {
        $getResponse = $this->api()->get("/discussions/1", ["expand" => "reactions"]);
        $getBody = $getResponse->getBody();
        $this->assertIsReactionSummary($getBody["reactions"]);

        $indexResponse = $this->api()->get("/discussions", ["expand" => "reactions"]);
        $indexBody = $indexResponse->getBody();
        $this->assertNotEmpty($indexBody);
        foreach ($indexBody as $row) {
            $this->assertIsReactionSummary($row["reactions"]);
        }
    }

    /**
     * Test expand reactions.
     */
    public function testExpandDiscussionReactionsSelf()
    {
        $this->api()->patch("/reactions/dislike", ["active" => true]);
        $userMember = $this->createUser();
        $discussion = $this->createDiscussion(1, "Test Discussion Reaction");

        $this->runWithUser(function () use ($discussion) {
            $this->api()->post("/discussions/{$discussion["discussionID"]}/reactions", [
                "reactionType" => "dislike",
            ]);
            $reactionScore = ReactionModel::getReactionTypes()["dislike"]["IncrementValue"];
            $responseBody = $this->api()
                ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "reactions"])
                ->getBody();
            $this->assertIsReactionSummary($responseBody["reactions"]);

            $reactionsByName = array_column($responseBody["reactions"], null, "name");
            $this->assertArrayHasKey("Dislike", $reactionsByName);
            $this->assertTrue($reactionsByName["Dislike"]["hasReacted"]);
            $this->assertEquals($reactionScore, $reactionsByName["Dislike"]["reactionValue"]);
            $this->assertTrue($reactionsByName["Dislike"]["reactionValue"] < 0);

            $this->assertArrayHasKey("Like", $reactionsByName);
            $this->assertFalse($reactionsByName["Like"]["hasReacted"]);
        }, $userMember);
    }

    /**
     * Test promoting a discussion to make sure an unknown user and blank reaction type doesn't show up in the logs.
     * see https://github.com/vanilla/support/issues/3410
     */
    public function testPromotedLogs()
    {
        $discussion = $this->createDiscussion(1, "Test Promoted");
        $this->bessy()->post("/react/discussion/promote?id={$discussion["discussionID"]}");
        $loggedReactions = $this->bessy()->get("/reactions/logged/discussion/{$discussion["discussionID"]}")->Data;
        $userTags = $loggedReactions["UserTags"];
        // Make sure the "Promoted" reaction tag doesn't come through.
        $this->assertCount(1, $userTags);
        // Make sure the userID is a real user.
        $this->assertSame($this->getSession()->UserID, $userTags[0]["UserID"]);
    }

    /**
     * Get the count for a type from a summary array.
     *
     * @param string $type The URL code of a type.
     * @param array $summary A summary of reactions on a record.
     * @return int
     */
    public function getSummaryCount($type, array $summary)
    {
        $result = 0;

        foreach ($summary as $row) {
            if ($row["urlcode"] === $type) {
                $result = $row["count"];
                break;
            }
        }

        return $result;
    }

    /**
     * Given a user ID and a reaction type, verify the combination is in a log of reactions.
     *
     * @param int $userID
     * @param string $type
     * @param array $data
     * @return bool
     */
    public function hasUserReaction($userID, $type, array $data)
    {
        $result = false;

        foreach ($data as $row) {
            if (!array_key_exists("userID", $row) || $row["userID"] !== $userID) {
                continue;
            } elseif (!array_key_exists("reactionType", $row) || !is_array($row["reactionType"])) {
                continue;
            } elseif (!array_key_exists("urlcode", $row["reactionType"]) || $row["reactionType"]["urlcode"] !== $type) {
                continue;
            } else {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * @param array $data
     * @return void
     */
    public function assertIsReactionSummary(array $data): void
    {
        $this->assertNotEmpty($data);

        foreach ($data as $row) {
            $this->assertArrayHasKey("tagID", $row);
            $this->assertIsInt($row["tagID"]);

            $this->assertArrayHasKey("urlcode", $row);
            $this->assertIsString($row["urlcode"]);

            $this->assertArrayHasKey("name", $row);
            $this->assertIsString($row["name"]);

            $this->assertArrayHasKey("class", $row);
            $this->assertIsString($row["class"]);

            $this->assertArrayHasKey("count", $row);
            $this->assertIsInt($row["count"]);
        }
    }

    /**
     * Assert a reaction summary contains a greater-than-zero number of a particular reaction type.
     *
     * @param string $type A valid URL code for a reaction type.
     * @param array $data Data collection (e.g. a response body).
     */
    public function assertSummaryHasReactionType($type, array $data)
    {
        $result = false;

        foreach ($data as $row) {
            if (!array_key_exists("urlcode", $row) || !array_key_exists("count", $row)) {
                continue;
            } elseif ($row["urlcode"] !== $type) {
                continue;
            }

            if ($row["count"] > 0) {
                $result = true;
            }
            break;
        }

        $this->assertTrue($result, "Unable to find a greater-than-zero count for reaction type: {$type}");
    }

    /**
     * Test APIV1 reaction validation: Invalid Record ID.
     */
    public function testApiV1InvalidRecordId()
    {
        $discussion = $this->createDiscussion(1, "Test invalid record ID");
        $reactionEndpoint = "/react/discussion/like?id={$discussion["discussionID"]}";

        $this->bessy()->post($reactionEndpoint);

        // APIV1 Call. Different PHP versions are giving different error message, thus, we aren't expecting any
        // specific one, just that this will error out.
        $this->expectError();
        $this->bessy()->post($reactionEndpoint . "aa");
    }

    /**
     * Test APIV1 reaction validation: Record is not found.
     */
    public function testApiV1RecordIsNotFound()
    {
        $discussion = $this->createDiscussion(1, "Test record is not found");
        $reactionEndpoint = "/react/discussion/like?id={$discussion["discussionID"]}";

        $this->bessy()->post($reactionEndpoint);

        // APIV1 Call.
        $this->expectExceptionMessage("Record is not found");
        $this->bessy()->post($reactionEndpoint . "00000");
    }
}
