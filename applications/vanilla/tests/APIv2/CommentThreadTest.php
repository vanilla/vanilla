<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use VanillaTests\Forum\ExpectedThreadStructure;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for threaded comments.
 */
class CommentThreadTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    /**
     * Test a simple thread structure.
     *
     * @return void
     */
    public function testSimpleThread()
    {
        $discussion = $this->createDiscussion();
        $comment1 = $this->createComment();
        $comment1_1 = $this->createNestedComment($comment1);
        $comment1_2 = $this->createNestedComment($comment1);
        $comment1_2_1 = $this->createNestedComment($comment1_2);
        // This one from a different user.
        $member = $this->createUser();
        $comment1_2_1_1 = $this->runWithUser(function () use ($comment1_2_1) {
            return $this->createNestedComment($comment1_2_1);
        }, $member);
        $comment1_3 = $this->createNestedComment($comment1);
        $comment1_4 = $this->createNestedComment($comment1);
        $comment1_5 = $this->createNestedComment($comment1);
        $comment2 = $this->createComment();

        $thread = $this->api()
            ->get("/comments/thread", [
                "parentRecordType" => "discussion",
                "parentRecordID" => $discussion["discussionID"],
            ])
            ->getBody();

        $expected = ExpectedThreadStructure::create()
            ->comment(
                $comment1,
                ExpectedThreadStructure::create()
                    ->comment($comment1_1)
                    ->comment($comment1_2, ExpectedThreadStructure::create()->hole($comment1_2, 2, 2))
                    ->comment($comment1_3)
                    ->hole($comment1, 2, 1)
            )
            ->comment($comment2);

        $expected->assertMatches($thread["threadStructure"]);

        // Assert that we have our preloaded comments.
        $this->assertRowsLike(
            [
                "commentID" => [
                    $comment1["commentID"],
                    $comment1_1["commentID"],
                    $comment1_2["commentID"],
                    $comment1_3["commentID"],
                    $comment2["commentID"],
                ],
            ],
            $thread["commentsByID"],
            strictOrder: false
        );
    }

    /**
     * Test that we create a "virtual" hole for the rest of our pages when querying by parentCommentID.
     *
     * @return void
     */
    public function testNestedQueryHole(): void
    {
        $discussion = $this->createDiscussion();
        $comment1 = $this->createComment();
        $comment1_1 = $this->createNestedComment($comment1);
        $comment1_2 = $this->createNestedComment($comment1);
        $comment1_2_1 = $this->createNestedComment($comment1_2);
        $comment1_3 = $this->createNestedComment($comment1);
        $comment1_4 = $this->createNestedComment($comment1);

        $thread = $this->api()
            ->get("/comments/thread", [
                "parentRecordType" => "discussion",
                "parentRecordID" => $discussion["discussionID"],
                "parentCommentID" => $comment1["commentID"],
                "page" => 2,
                "limit" => 1,
            ])
            ->getBody();

        $expected = ExpectedThreadStructure::create(2) // Initial depth is 2 because we queried a parentCommentID.
            ->comment($comment1_2, ExpectedThreadStructure::create()->comment($comment1_2_1))
            // Notably top level hole here.
            ->hole($comment1, 2, 1, offset: 2);

        $expected->assertMatches($thread["threadStructure"]);

        // Make sure we've made apiUrls for our holes.
        $hole = $thread["threadStructure"][1];
        $expectedUrl = url(
            "/api/v2/comments/thread?parentRecordType=discussion&parentRecordID={$discussion["discussionID"]}&parentCommentID={$comment1["commentID"]}&sort=dateInserted&page=3&limit=1&expand%5B0%5D=body",
            true
        );
        $this->assertEquals($expectedUrl, $hole["apiUrl"]);
    }

    /**
     * Test that comments may only be posted into the correct thread.
     *
     * @return void
     */
    public function testPostIntoWrongThread(): void
    {
        $disc1 = $this->createDiscussion();
        $comment1 = $this->createComment();
        $disc2 = $this->createDiscussion();

        $this->expectExceptionMessage("Parent comment is from a different thread.");
        $this->createNestedComment($comment1, [
            "parentRecordType" => "discussion",
            "parentRecordID" => $disc2["discussionID"],
        ]);
    }

    /**
     * Test that comments may not be posted beyond our configured maximum depth.
     *
     * @return void
     */
    public function testCommentExceedsMaxDepth(): void
    {
        $this->runWithConfig(["Vanilla.Comment.MaxDepth" => 2], function () {
            $discussion = $this->createDiscussion();
            $comment1 = $this->createComment();
            $comment1_1 = $this->createNestedComment($comment1);

            $this->expectExceptionMessage("Comment exceeds maximum depth.");
            $comment1_1_1 = $this->createNestedComment($comment1_1);
        });
    }
}
