<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace APIv2;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Gdn;
use LogModel;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test Comments delete.
 */
class CommentsDeleteTest extends SiteTestCase
{
    use EventSpyTestTrait;
    use CommunityApiTestTrait;
    use ExpectExceptionTrait;
    use SchedulerTestTrait;
    use UsersAndRolesApiTestTrait;

    private LogModel $logModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->enableFeature("customLayout.post");
        $this->logModel = Gdn::getContainer()->get(LogModel::class);
        parent::setUp();
    }

    /**
     * Test success DELETE /discussions/list
     */
    public function testSuccessFullDeleteRestoreComments(): void
    {
        $this->createUserFixtures();
        // Create our records.
        $this->resetCategoryTable();
        $this->resetTable("Discussion");
        $this->resetTable("Comment");
        $this->enableCaching();

        $comments = $this->createNestedComments($this->memberID);
        $commentIDsToDelete = $comments["commentIDsToDelete"];
        $commentIDsToBeKept = $comments["commentIDsToBeKept"];
        $childCommentIDsOfDeletedParents = $comments["childCommentIDsOfDeletedChildren"];

        // Delete the records.
        $response = $this->api()->deleteWithBody("/comments/list", [
            "commentIDs" => $commentIDsToDelete,
            "deleteMethod" => "full",
        ]);
        // Request should be successful.
        $this->assertEquals(200, $response->getStatusCode());

        // Redirects should have been created.
        $keptComment = $this->api()->get("/comments/" . $commentIDsToBeKept[1]);
        $this->assertEquals(200, $keptComment->getStatusCode());
        $body = $keptComment->getBody();
        $this->assertNotEmpty($body);
        $deletedComment = $this->api()->get("/comments", ["commentID" => $commentIDsToDelete[1]]);
        $this->assertEquals(200, $deletedComment->getStatusCode());
        $body = $deletedComment->getBody();
        $this->assertEmpty($body);
        $deletedChildComment = $this->api()->get("/comments", [
            "commentID" => $childCommentIDsOfDeletedParents[$commentIDsToDelete[0]][1],
        ]);
        $this->assertEquals(200, $deletedChildComment->getStatusCode());
        $body = $deletedChildComment->getBody();
        $this->assertEmpty($body);

        //Test Restore of the parent and child comments
        $logs = $this->logModel->getWhere(["RecordType" => "Comment", "RecordID" => $commentIDsToDelete[0]]);
        $this->assertCount(1, $logs);
        $this->assertNotEmpty($logs[0]["TransactionLogID"]);
        $transactionLogs = $this->logModel->getWhere(
            [
                "RecordType" => "Comment",
                "TransactionLogID" => $logs[0]["TransactionLogID"],
            ],
            "RecordID"
        );
        $this->assertCount(count($childCommentIDsOfDeletedParents[$commentIDsToDelete[0]]) + 1, $transactionLogs);
        $this->assertRowsLike(
            [
                "RecordID" => array_merge(
                    [$commentIDsToDelete[0]],
                    $childCommentIDsOfDeletedParents[$commentIDsToDelete[0]]
                ),
            ],
            $transactionLogs,
            true,
            count($childCommentIDsOfDeletedParents[$commentIDsToDelete[0]]) + 1
        );

        $this->logModel->restore($logs[0]);

        // Test restore of parent comment, and children.
        $keptComment = $this->api()->get("/comments/" . $commentIDsToBeKept[1]);
        $this->assertEquals(200, $keptComment->getStatusCode());
        $body = $keptComment->getBody();
        $this->assertNotEmpty($body);

        $keptComment = $this->api()->get("/comments", ["parentCommentID" => $commentIDsToBeKept[1]]);
        $this->assertEquals(200, $keptComment->getStatusCode());
        $body = $keptComment->getBody();
        $this->assertNotEmpty($body);

        // Check that member without Vanilla.Comments.Delete permission is not able to delete
        $this->runWithUser(function () use ($commentIDsToDelete) {
            $this->expectException(ForbiddenException::class);
            $response = $this->api()->deleteWithBody("/comments/list", [
                "commentIDs" => $commentIDsToDelete,
                "deleteMethod" => "full",
            ]);
            // Request should be successful.
            $this->assertEquals(200, $response->getStatusCode());
            $body = $response->getBody();
        }, $this->memberID);
    }

    /**
     * Test success PATCH /discussions/merge
     */
    public function testSuccessTombstoneDeleteRestoreComments(): void
    {
        $this->createUserFixtures();
        // Create our records.
        $this->resetCategoryTable();
        $this->resetTable("Discussion");
        $this->resetTable("Comment");
        $this->enableCaching();
        $comments = $this->createNestedComments($this->memberID);
        $commentIDsToDelete = $comments["commentIDsToDelete"];
        $commentIDsToBeKept = $comments["commentIDsToBeKept"];
        $commentIDsToNotTouch = $comments["childCommentIDsOfDeletedChildren"];

        // Delete the records.
        $response = $this->api()->deleteWithBody("/comments/list", [
            "commentIDs" => $commentIDsToDelete,
            "deleteMethod" => "tombstone",
        ]);
        // Request should be successful.
        $this->assertEquals(200, $response->getStatusCode());

        // Redirects should have been created.
        $keptComment = $this->api()->get("/comments/" . $commentIDsToBeKept[1]);
        $this->assertEquals(200, $keptComment->getStatusCode());
        $body = $keptComment->getBody();
        $this->assertNotEmpty($body);
        $this->assertEquals($this->memberID, $body["insertUserID"]);
        $deletedComment = $this->api()->get("/comments/" . $commentIDsToDelete[0]);
        $this->assertEquals(200, $deletedComment->getStatusCode());
        $body = $deletedComment->getBody();
        $this->assertNotEmpty($body);
        $this->assertEquals(0, $body["insertUserID"]);
        $notTouchedChildComment = $this->api()->get("/comments/" . $commentIDsToNotTouch[$commentIDsToDelete[0]][1]);
        $this->assertEquals(200, $notTouchedChildComment->getStatusCode());
        $body = $notTouchedChildComment->getBody();
        $this->assertNotEmpty($body);
        $this->assertEquals($this->memberID, $body["insertUserID"]);

        //Test Restore of the comments from being tombstoned
        $logs = $this->logModel->getWhere(["RecordType" => "Comment", "RecordID" => $commentIDsToDelete[0]]);
        $this->assertCount(1, $logs);
        $this->assertEmpty($logs[0]["TransactionLogID"]);

        $this->logModel->restore($logs[0]);

        // Check restore comment and its children
        $keptComment = $this->api()->get("/comments/" . $commentIDsToDelete[0]);
        $this->assertEquals(200, $keptComment->getStatusCode());
        $body = $keptComment->getBody();
        $this->assertNotEmpty($body);
        $this->assertEquals($this->memberID, $body["insertUserID"]);
    }

    /**
     * Create nested structure of comments for delete tests.
     *
     * @param int $userID
     * @return array
     */
    private function createNestedComments(int $userID): array
    {
        $commentIDsToDelete = $commentIDsToBeKept = $childCommentIDsOfDeletedChildren = [];
        $this->createCategory();
        $this->createDiscussion();
        $this->runWithUser(function () use (
            &$commentIDsToDelete,
            &$commentIDsToBeKept,
            &$childCommentIDsOfDeletedChildren
        ) {
            // Depth 1
            $comment1 = $this->createComment();
            $comment2 = $this->createComment();
            $comment3Leaf = $this->createComment();
            $commentIDsToDelete = [$comment1["commentID"], $comment3Leaf["commentID"]];
            $commentIDsToBeKept = [$comment2["commentID"]];
            // Depth 2
            $comment1_1 = $this->createNestedComment($comment1);
            $comment1_2 = $this->createNestedComment($comment1);
            $comment1_3Leaf = $this->createNestedComment($comment1);
            $childCommentIDsOfDeletedChildren[$comment1["commentID"]] = [
                $comment1_1["commentID"],
                $comment1_2["commentID"],
                $comment1_3Leaf["commentID"],
            ];

            $comment2_1 = $this->createNestedComment($comment2);
            $comment2_2 = $this->createNestedComment($comment2);
            $comment2_3Leaf = $this->createNestedComment($comment2);
            $commentIDsToBeKept[] = $comment2_1["commentID"];
            $commentIDsToDelete[] = $comment2_2["commentID"];
            $commentIDsToDelete[] = $comment2_3Leaf["commentID"];
            // Depth 3
            $comment1_1_1 = $this->createNestedComment($comment1_1);
            $comment1_1_2 = $this->createNestedComment($comment1_1);
            $comment1_1_3Leaf = $this->createNestedComment($comment1_1);
            $comment1_2_1 = $this->createNestedComment($comment1_2);
            $comment1_2_2 = $this->createNestedComment($comment1_2);
            $comment1_2_3Leaf = $this->createNestedComment($comment1_2);
            $childCommentIDsOfDeletedChildren[$comment1["commentID"]][] = $comment1_1_1["commentID"];
            $childCommentIDsOfDeletedChildren[$comment1["commentID"]][] = $comment1_1_2["commentID"];
            $childCommentIDsOfDeletedChildren[$comment1["commentID"]][] = $comment1_1_3Leaf["commentID"];
            $childCommentIDsOfDeletedChildren[$comment1["commentID"]][] = $comment1_2_1["commentID"];
            $childCommentIDsOfDeletedChildren[$comment1["commentID"]][] = $comment1_2_2["commentID"];
            $childCommentIDsOfDeletedChildren[$comment1["commentID"]][] = $comment1_2_3Leaf["commentID"];

            $comment2_1_1 = $this->createNestedComment($comment2_1);
            $comment2_1_2 = $this->createNestedComment($comment2_1);
            $comment2_1_3Leaf = $this->createNestedComment($comment2_1);
            $commentIDsToBeKept = array_merge($commentIDsToBeKept, [
                $comment2_1_1["commentID"],
                $comment2_1_3Leaf["commentID"],
            ]);
            $commentIDsToDelete[] = $comment2_1_2["commentID"];
            $comment2_2_1 = $this->createNestedComment($comment2_2);
            $comment2_2_2 = $this->createNestedComment($comment2_2);
            $comment2_2_3Leaf = $this->createNestedComment($comment2_2);
            $childCommentIDsOfDeletedChildren[$comment2_2["commentID"]] = [
                $comment2_2_1["commentID"],
                $comment2_2_2["commentID"],
                $comment2_2_3Leaf["commentID"],
            ];

            // Depth 4
            $comment1_1_1_1Leaf = $this->createNestedComment($comment1_1_1);
            $comment1_1_2_1Leaf = $this->createNestedComment($comment1_1_2);
            $comment1_2_1_1Leaf = $this->createNestedComment($comment1_2_1);
            $comment1_2_2_1Leaf = $this->createNestedComment($comment1_2_2);

            $comment2_1_1_1Leaf = $this->createNestedComment($comment2_1_1);
            $comment2_1_1_2Leaf = $this->createNestedComment($comment2_1_1);
            $comment2_1_2_1Leaf = $this->createNestedComment($comment2_1_2);
            $comment2_2_1_1Leaf = $this->createNestedComment($comment2_2_1);
            $comment2_2_2_1Leaf = $this->createNestedComment($comment2_2_2);
            $commentIDsToDelete[] = $comment2_1_1_2Leaf["commentID"];
            $commentIDsToBeKept[] = $comment2_1_1_1Leaf["commentID"];

            $childCommentIDsOfDeletedChildren[$comment1["commentID"]][] = $comment1_1_1_1Leaf["commentID"];
            $childCommentIDsOfDeletedChildren[$comment1["commentID"]][] = $comment1_1_2_1Leaf["commentID"];

            $childCommentIDsOfDeletedChildren[$comment1["commentID"]][] = $comment1_2_1_1Leaf["commentID"];
            $childCommentIDsOfDeletedChildren[$comment1["commentID"]][] = $comment1_2_2_1Leaf["commentID"];
            $childCommentIDsOfDeletedChildren[$comment2_2["commentID"]][] = $comment2_1_2_1Leaf["commentID"];
            $childCommentIDsOfDeletedChildren[$comment2_2["commentID"]][] = $comment2_2_1_1Leaf["commentID"];
            $childCommentIDsOfDeletedChildren[$comment2_2["commentID"]][] = $comment2_2_2_1Leaf["commentID"];
        },
        $userID);

        return [
            "commentIDsToDelete" => $commentIDsToDelete,
            "commentIDsToBeKept" => $commentIDsToBeKept,
            "childCommentIDsOfDeletedChildren" => $childCommentIDsOfDeletedChildren,
        ];
    }

    /**
     * Test that we can partially complete delete.
     *
     * @return mixed
     */
    public function testDeletePartial()
    {
        $this->createUserFixtures();
        // Create our records.
        $this->resetCategoryTable();
        $this->resetTable("Discussion");
        $this->resetTable("Comment");
        $this->resetTable("Log");
        $this->enableCaching();

        $comments = $this->createNestedComments($this->memberID);
        $commentIDsToDelete = $comments["commentIDsToDelete"];

        $this->getLongRunner()->setMaxIterations(1);

        // Delete the records.
        $response = $this->api()->deleteWithBody(
            "/comments/list",
            [
                "commentIDs" => $commentIDsToDelete,
                "deleteMethod" => "full",
            ],
            [],
            ["throw" => false]
        );

        $this->assertEquals(408, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(6, $body["progress"]["countTotalIDs"]);
        $this->assertCount(1, $body["progress"]["successIDs"]);
        $this->assertNotNull($body["callbackPayload"]);
        return $body["callbackPayload"];
    }

    /**
     * Test that we can resume and finish our delete.
     *
     * @param string $callbackPayload
     *
     * @depends testDeletePartial
     */
    public function testResumePartial(string $callbackPayload)
    {
        $this->getLongRunner()->reset();
        $response = $this->resumeLongRunner($callbackPayload);
        $this->assertEquals(200, $response->getStatusCode(), "Long runner should complete. " . $response->getRawBody());
        $this->assertEquals(6, $response->getBody()["progress"]["countTotalIDs"]);

        $logs = $this->logModel->getWhere(["RecordType" => "Comment"]);
        $this->assertCount(25, $logs);
    }

    /**
     * Test permissions for [DELETE] `/api/v2/comments/list`.
     *
     * @return void
     */
    public function testDeleteCommentListPermission(): void
    {
        $this->createDiscussion();
        $this->createComment();
        $user = $this->createUser();

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");
        $this->expectExceptionCode(403);
        $this->runWithUser(function () {
            $this->api()->deleteWithBody("/comments/list", [
                "commentIDs" => [$this->lastInsertCommentID],
                "deleteMethod" => "tombstone",
            ]);
        }, $user);
    }

    /**
     * Test that we can't delete a comment from multiple parents.
     *
     * @return void
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Schema\ValidationException
     */
    public function testDeleteCommentFromMultipleParents(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Comments must belong to the same parent record.");

        $this->createDiscussion();
        $comment1 = $this->createComment();

        $this->createDiscussion();
        $comment2 = $this->createComment();

        $this->api()->deleteWithBody("/comments/list", [
            "commentIDs" => [$comment1["commentID"], $comment2["commentID"]],
            "deleteMethod" => "tombstone",
        ]);
    }
}
