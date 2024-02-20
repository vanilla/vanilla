<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Controllers;

use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Dashboard\Models\ExternalIssueService;
use VanillaTests\Fixtures\addons\addons\TestMockIssue\MockExternalIssueProvider;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the AttachmentsApiController.
 */
class AttachmentsApiControllerTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    protected static $addons = ["test-mock-issue"];

    private MockExternalIssueProvider $mockExternalIssueProvier;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container()
            ->rule(ExternalIssueService::class)
            ->addCall("addProvider", [new \Garden\Container\Reference(MockExternalIssueProvider::class)]);
        $this->mockExternalIssueProvier = $this->container()->get(MockExternalIssueProvider::class);
    }

    /**
     * Test posting an attachment for a discussion.
     *
     * @return void
     */
    public function testPostDiscussionAttachment(): void
    {
        $discussion = $this->createDiscussion();
        $attachmentData = $this->createAttachmentPostData("discussion", $discussion["discussionID"]);

        $attachment = $this->api()
            ->post("/attachments", $attachmentData)
            ->getBody();

        $expectedAttachmentData = $this->createAttachmentData("discussion", $discussion["discussionID"]);

        foreach ($expectedAttachmentData as $field => $value) {
            $this->assertSame($attachment[$field], $value);
        }
    }

    /**
     * Test posting an attachment for a comment.
     *
     * @return void
     */
    public function testPostCommentAttachment(): void
    {
        $this->createDiscussion();
        $comment = $this->createComment();
        $attachmentData = $this->createAttachmentPostData("comment", $comment["commentID"]);

        $attachment = $this->api()
            ->post("/attachments", $attachmentData)
            ->getBody();

        $expectedAttachmentData = $this->createAttachmentData("comment", $comment["commentID"]);

        foreach ($expectedAttachmentData as $field => $value) {
            $this->assertSame($attachment[$field], $value);
        }
    }

    /**
     * Test that a user without permission cannot post an attachment.
     *
     * @return void
     */
    public function testPostPermissions(): void
    {
        $discussion = $this->createDiscussion();
        $member = $this->createUser();
        $attachmentData = $this->createAttachmentPostData("discussion", $discussion["discussionID"]);
        $this->api()->setUserID($member["userID"]);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You do not have permission to use this provider.");
        $this->api()
            ->post("/attachments", $attachmentData)
            ->getBody();
    }

    /**
     * Test posting an attachment with an invalid source.
     *
     * @return void
     */
    public function testPostWithInvalidSourceType(): void
    {
        $discussion = $this->createDiscussion();
        $attachmentData = $this->createAttachmentPostData("discussion", $discussion["discussionID"]);

        $attachmentData["attachmentType"] = "invalid";

        $this->expectExceptionMessage("No provider was found for this attachment type.");
        $this->api()
            ->post("/attachments", $attachmentData)
            ->getBody();
    }

    /**
     * Test getting attachments for a discussion.
     *
     * @return void
     */
    public function testGet(): void
    {
        $discussion = $this->createDiscussion();
        $attachmentData = $this->createAttachmentPostData("discussion", $discussion["discussionID"]);

        $this->api()
            ->post("/attachments", $attachmentData)
            ->getBody();

        $attachments = $this->api()
            ->get("/attachments", ["recordType" => "discussion", "recordID" => $discussion["discussionID"]])
            ->getBody();

        $this->assertCount(1, $attachments);

        $attachment = $attachments[0];

        $expectedAttachmentData = $this->createAttachmentData("discussion", $discussion["discussionID"]);

        foreach ($expectedAttachmentData as $field => $value) {
            $this->assertSame($attachment[$field], $value);
        }
    }

    /**
     * Test that getting attachments without the required permission throws an error.
     *
     * @return void
     */
    public function testGetPermissions(): void
    {
        $discussion = $this->createDiscussion();

        $attachmentData = $this->createAttachmentPostData("discussion", $discussion["discussionID"]);
        $this->api()->post("/attachments", $attachmentData);

        $member = $this->createUser();
        $this->api()->setUserID($member["userID"]);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");
        $this->api()->get("/attachments", ["recordType" => "discussion", "recordID" => $discussion["discussionID"]]);
    }

    /**
     * Create attachment data for testing.
     *
     * @param string $recordType
     * @param int $recordID
     * @return array
     */
    public function createAttachmentData(string $recordType, int $recordID): array
    {
        $attachmentData = [
            "attachmentType" => "mock-issue",
            "recordType" => $recordType,
            "recordID" => $recordID,
            "source" => "mock",
            "metadata" => [
                [
                    "labelCode" => "specialMockField1",
                    "value" => "foo",
                ],
                [
                    "labelCode" => "specialMockField2",
                    "value" => "bar",
                ],
            ],
        ];

        return $attachmentData;
    }

    /**
     * Create attachment data for posting an attachment.
     *
     * @param string $recordType
     * @param int $recordID
     * @return array
     */
    public function createAttachmentPostData(string $recordType, int $recordID): array
    {
        $attachmentData = [
            "attachmentType" => "mock-issue",
            "recordType" => $recordType,
            "recordID" => $recordID,
            "source" => "mock",
            "specialMockField1" => "foo",
            "specialMockField2" => "bar",
        ];

        return $attachmentData;
    }

    /**
     * Test that we can properly fetch the catalogs by using [GET] `/api/v2/attachments/catalog`.
     *
     * @return void
     */
    public function testGetCatalog(): void
    {
        $expected = ["mock-issue" => ["label" => "mock", "recordTypes" => ["discussion", "comment", "user"]]];
        $response = $this->api()->get("/attachments/catalog");
        $body = $response->getBody();
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals($expected, $body);
    }

    /**
     * Test that no catalog is return when a user is not allowed to see any using [GET] `/api/v2/attachments/catalog`.
     *
     * @return void
     */
    public function testGetCatalogPermission(): void
    {
        $user = $this->createUser();
        $this->runWithUser(function () {
            $response = $this->api()->get("/attachments/catalog");
            $this->assertEmpty($response->getBody());
        }, $user);
    }

    /**
     * Test that we can properly fetch the schema by using [GET] `/api/v2/attachments/schema`.
     *
     * @return void
     */
    public function testGetSchema(): void
    {
        $discussion = $this->createDiscussion();
        $response = $this->api()->get("/attachments/schema", [
            "attachmentType" => "mock-issue",
            "recordType" => "discussion",
            "recordID" => $discussion["discussionID"],
        ]);
        $body = $response->getBody();

        $issuePostSchema = $this->mockExternalIssueProvier->issuePostSchema();
        $attachmentModel = $this->container()->get(\AttachmentModel::class);
        $baseSchema = $attachmentModel->getHydratedAttachmentPostSchema(
            "mock-issue",
            "discussion",
            $discussion["discussionID"]
        );
        $expected = $baseSchema->merge($issuePostSchema)->getSchemaArray();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals($expected, $body);
    }

    /**
     * Test that an error is thrown when a user is not to fetch a schema using [GET] `/api/v2/attachments/schema`.
     *
     * @return void
     */
    public function testGetSchemaPermission(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You do not have permission to use this provider.");
        $discussion = $this->createDiscussion();

        $user = $this->createUser();
        $this->runWithUser(function () use ($discussion) {
            $this->api()->get("/attachments/schema", [
                "attachmentType" => "mock-issue",
                "recordType" => "discussion",
                "recordID" => $discussion["discussionID"],
            ]);
        }, $user);
    }

    /**
     * Test that an error is thrown when calling [GET] `/api/v2/attachments/schema` with an invalid schema.
     *
     * @return void
     */
    public function testGetSchemaInvalidSchema(): void
    {
        $discussion = $this->createDiscussion();
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("No provider was found for this attachment source.");
        $this->api()->get("/attachments/schema", [
            "attachmentType" => __FUNCTION__,
            "recordType" => "discussion",
            "recordID" => $discussion["discussionID"],
        ]);
    }
}
