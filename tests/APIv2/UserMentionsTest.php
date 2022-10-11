<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ForbiddenException;
use Vanilla\Dashboard\Models\UserMentionsModel;
use Vanilla\Formatting\Formats\BBCodeFormat;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the `/apiv/v2/user-mentions` endpoint.
 */
class UserMentionsTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;
    use SchedulerTestTrait;

    /** @var UserMentionsModel */
    private $userMentionModel;

    public $baseUrl = "/user-mentions";

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->resetTable("Discussion");
        $this->resetTable("Comment");
        $this->resetTable("userMention");
        $this->userMentionModel = $this->container()->get(UserMentionsModel::class);
    }

    /**
     * Test for [GET] `/api/v2/user-mentions/{userID}/user`.
     */
    public function testUserMentionsGetUserSuccess()
    {
        $user = $this->createUser(["name" => "user" . __FUNCTION__]);
        $discussion = $this->createDiscussion(["body" => "test @\"{$user["name"]}\""]);
        $expect = [
            "userID" => $user["userID"],
            "recordType" => "discussion",
            "recordID" => $discussion["discussionID"],
            "mentionedName" => $user["name"],
            "parentRecordType" => "category",
            "parentRecordID" => -1,
            "dateInserted" => $discussion["dateInserted"],
            "status" => "active",
        ];

        $result = $this->api()
            ->get($this->baseUrl . "/users/{$user["userID"]}")
            ->getBody();
        $this->assertEquals($expect, $result[0]);
    }

    /**
     * Test that members can't fetch user mentions.
     */
    public function testUserMentionsGetUserPermissionFail()
    {
        $user = $this->createUser(["roleID" => [\RoleModel::MEMBER_ID]]);
        $this->expectException(ForbiddenException::class);

        $this->runWithUser(function () {
            $this->api()->get($this->baseUrl . "/users/1");
        }, $user);
    }

    /**
     * Test that users mentions are properly indexed.
     */
    public function testUserMentionsIndexing()
    {
        $user = $this->createUser(["name" => "user" . __FUNCTION__]);
        $this->createDiscussion(["body" => "test @\"{$user["name"]}\""]);
        $discussion = $this->createDiscussion(["body" => "test @\"{$user["name"]}\""]);
        $this->createDiscussion([
            "body" => "test [QUOTE=\"{$user["name"]};{$discussion["discussionID"]}\"]",
            "format" => BBCodeFormat::FORMAT_KEY,
        ]);
        $this->createDiscussion([
            "body" => "test [QUOTE={$user["name"]};{$discussion["discussionID"]}]",
            "format" => BBCodeFormat::FORMAT_KEY,
        ]);
        $this->createComment(["body" => "test @\"{$user["name"]}\""]);
        $this->createComment(["body" => "test @\"{$user["name"]}\""]);
        $this->resetTable("userMention");

        $response = $this->api()->put("/dba/recalculate-aggregates", ["aggregates" => ["user-mentions"]]);
        $this->assertEquals(200, $response->getStatusCode());
        $result = $this->userMentionModel->getByUser($user["userID"]);
        $this->assertEquals(6, count($result));
    }

    /**
     * Test that the user mentions indexing can be resumed with the long runner.
     */
    public function testLongRunnerContinue()
    {
        $user = $this->createUser(["name" => "user" . __FUNCTION__]);
        $this->resetTable("Discussion");
        $this->createDiscussion(["body" => "test @\"{$user["name"]}\""]);
        $this->createDiscussion(["body" => "test @\"{$user["name"]}\""]);
        $this->createComment(["body" => "test @\"{$user["name"]}\""]);
        $this->createComment(["body" => "test @\"{$user["name"]}\""]);
        $this->resetTable("userMention");

        $this->getLongRunner()->setMaxIterations(1);
        $response = $this->api()->put(
            "/dba/recalculate-aggregates",
            ["aggregates" => ["user-mentions"]],
            [],
            ["throw" => false]
        );
        $this->assertNotNull($response["callbackPayload"]);
        $result = $this->userMentionModel->getByUser($user["userID"]);
        $this->assertEquals(1, count($result));

        // Resume and finish.
        $this->getLongRunner()->setMaxIterations(100);
        $response = $this->resumeLongRunner($response["callbackPayload"]);
        $this->assertEquals(200, $response->getStatusCode());
        $result = $this->userMentionModel->getByUser($user["userID"]);
        $this->assertEquals(4, count($result));
    }

    /**
     * Test that the user mentions indexing can be resumed with the long runner, and picks up on next model index.
     */
    public function testLongRunnerContinueMidSourceChange()
    {
        $user = $this->createUser(["name" => "user" . __FUNCTION__]);
        $this->resetTable("Discussion");
        $this->createDiscussion(["body" => "test @\"{$user["name"]}\""]);
        $this->createDiscussion(["body" => "test @\"{$user["name"]}\""]);
        $this->createComment(["body" => "test @\"{$user["name"]}\""]);
        $this->createComment(["body" => "test @\"{$user["name"]}\""]);
        $this->resetTable("userMention");

        $this->getLongRunner()->setMaxIterations(2);
        $response = $this->api()->put(
            "/dba/recalculate-aggregates",
            ["aggregates" => ["user-mentions"]],
            [],
            ["throw" => false]
        );
        $this->assertNotNull($response["callbackPayload"]);
        $result = $this->userMentionModel->getByUser($user["userID"]);
        $this->assertEquals(2, count($result));

        // Resume and finish.
        $this->getLongRunner()->setMaxIterations(5);
        $response = $this->resumeLongRunner($response["callbackPayload"]);
        $this->assertEquals(200, $response->getStatusCode());
        $result = $this->userMentionModel->getByUser($user["userID"]);
        $this->assertEquals(4, count($result));
    }

    /**
     * Test that the mentions are properly anonymize.
     */
    public function testUserMentionsAnonymize(): void
    {
        $user = $this->createUser(["name" => "user" . __FUNCTION__]);
        $discussion = $this->createDiscussion(["body" => "test @{$user["name"]}"]);
        $comment = $this->createComment(["body" => "test @{$user["name"]}"]);

        $response = $this->api()->post("{$this->baseUrl}/{$user["userID"]}/anonymize");
        $this->assertEquals(201, $response->getStatusCode());

        $userMentions = $this->userMentionModel->getByUser($user["userID"]);
        $this->assertCount(2, $userMentions);
        foreach ($userMentions as $mention) {
            $this->assertEquals("removed", $mention["status"]);
        }

        $updatedDiscussion = $this->api()
            ->get("discussions/{$discussion["discussionID"]}")
            ->getBody();
        $this->assertStringContainsString("[Deleted User]", $updatedDiscussion["body"]);
        $this->assertStringNotContainsString($user["name"], $updatedDiscussion["body"]);

        $updatedComment = $this->api()
            ->get("comments/{$comment["commentID"]}")
            ->getBody();
        $this->assertStringContainsString("[Deleted User]", $updatedComment["body"]);
        $this->assertStringNotContainsString($user["name"], $updatedComment["body"]);
    }

    /**
     * Test that the generator return only non-indexed records.
     */
    public function testNonIndexedGenerator()
    {
        $discussionModel = $this->container()->get(\DiscussionModel::class);
        $user = $this->createUser(["name" => "user" . __FUNCTION__]);
        $this->createDiscussion(["body" => "test @{$user["name"]}"]);
        $discussion1 = $this->createDiscussion();
        $discussion2 = $this->createDiscussion();

        $results = $this->userMentionModel->getNonIndexedIterator($discussionModel, [], "DiscussionID", "asc", 1);
        $this->assertEquals($discussion1["discussionID"], $results->current()["DiscussionID"]);

        $results = $this->userMentionModel->getNonIndexedIterator(
            $discussionModel,
            ["DiscussionID>" => $discussion1["discussionID"]],
            "DiscussionID",
            "asc",
            1
        );
        $this->assertEquals($discussion2["discussionID"], $results->current()["DiscussionID"]);
    }
}
