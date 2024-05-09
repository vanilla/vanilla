<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Controllers;

use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\AttachmentService;
use VanillaTests\Fixtures\Addons\TestMockIssue\MockAttachmentProvider;
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

    private MockAttachmentProvider $mockExternalIssueProvier;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container()
            ->rule(AttachmentService::class)
            ->addCall("addProvider", [new \Garden\Container\Reference(MockAttachmentProvider::class)]);
        $this->mockExternalIssueProvier = $this->container()->get(MockAttachmentProvider::class);
    }

    /**
     * Test posting an attachment for a discussion.
     *
     * @return array
     */
    public function testPostDiscussionAttachment(): array
    {
        CurrentTimeStamp::mockTime("2023-01-01");
        $discussion = $this->createDiscussion();
        $attachmentData = $this->createAttachmentPostData("discussion", $discussion["discussionID"]);

        $attachment = $this->api()
            ->post("/attachments", $attachmentData)
            ->getBody();

        $expected = [
            "attachmentID" => $attachment["attachmentID"],
            "attachmentType" => "mock-issue",
            "recordType" => "discussion",
            "recordID" => $discussion["discussionID"],
            "foreignID" => "d-{$discussion["discussionID"]}",
            "foreignUserID" => 2,
            "source" => "mock",
            "sourceID" => $attachment["sourceID"],
            "sourceUrl" => "www.example.com/mockIssue/{$attachment["sourceID"]}",
            "status" => "active",
            "lastModifiedDate" => "2023-01-01T00:00:00+00:00",
            "metadata" => [
                [
                    "labelCode" => "Special Mock Field 1",
                    "value" => "foo",
                ],
                [
                    "labelCode" => "Special Mock Field 2",
                    "value" => "bar",
                ],
                [
                    "labelCode" => "Last Modified",
                    "value" => "2023-01-01T00:00:00.000+00:00",
                    "format" => "date-time",
                ],
            ],
            "dateInserted" => "2023-01-01T00:00:00+00:00",
            "insertUserID" => 2,
            "dateUpdated" => "2023-01-01T00:00:00+00:00",
        ];

        $this->assertEquals($expected, $attachment);

        return $attachment;
    }

    /**
     * Test posting an attachment for a comment.
     *
     * @return void
     */
    public function testPostCommentAttachment(): void
    {
        CurrentTimeStamp::mockTime("2023-01-01");
        $this->createDiscussion();
        $comment = $this->createComment();
        $attachmentData = $this->createAttachmentPostData("comment", $comment["commentID"]);

        $attachment = $this->api()
            ->post("/attachments", $attachmentData)
            ->getBody();

        $expected = [
            "attachmentID" => $attachment["attachmentID"],
            "attachmentType" => "mock-issue",
            "recordType" => "comment",
            "recordID" => $comment["commentID"],
            "foreignID" => "c-{$comment["commentID"]}",
            "foreignUserID" => 2,
            "source" => "mock",
            "sourceID" => $attachment["sourceID"],
            "sourceUrl" => "www.example.com/mockIssue/{$attachment["sourceID"]}",
            "status" => "active",
            "lastModifiedDate" => "2023-01-01T00:00:00+00:00",
            "metadata" => [
                [
                    "labelCode" => "Special Mock Field 1",
                    "value" => "foo",
                ],
                [
                    "labelCode" => "Special Mock Field 2",
                    "value" => "bar",
                ],
                [
                    "labelCode" => "Last Modified",
                    "value" => "2023-01-01T00:00:00.000+00:00",
                    "format" => "date-time",
                ],
            ],
            "dateInserted" => "2023-01-01T00:00:00+00:00",
            "insertUserID" => 2,
            "dateUpdated" => "2023-01-01T00:00:00+00:00",
        ];

        $this->assertEquals($expected, $attachment);
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

        $posted = $this->api()
            ->post("/attachments", $attachmentData)
            ->getBody();

        $attachments = $this->api()
            ->get("/attachments", ["recordType" => "discussion", "recordID" => $discussion["discussionID"]])
            ->getBody();

        $this->assertCount(1, $attachments);

        $attachment = $attachments[0];
        $this->assertEquals($posted, $attachment);
    }

    /**
     * Test that an attachment record with no saved status (as might be the case with old or corrupted records)
     * doesn't throw, but is returned with a status of "unknown".
     *
     * @return void
     */
    public function testGettingAttachmentWithoutStatus(): void
    {
        $discussion = $this->createDiscussion();
        $attachmentData = [
            "Type" => "mock-issue",
            "ForeignID" => "d-" . $discussion["discussionID"],
            "ForeignUserID" => 2,
            "Source" => "mock",
            "SourceID" => "123",
            "SourceURL" => "www.example.com/mockIssue/123",
            "DateInserted" => "2024-01-01",
            "InsertUserID" => 2,
            "InsertIPAddress" => "::1",
        ];

        $database = $this->container()->get(\Gdn_Database::class);
        $database->sql()->insert("Attachment", $attachmentData);

        $attachments = $this->api()
            ->get("/attachments", ["recordType" => "discussion", "recordID" => $discussion["discussionID"]])
            ->getBody();

        $this->assertCount(1, $attachments);

        $attachment = $attachments[0];
        $this->assertSame("unknown", $attachment["status"]);
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
        $expected = [
            "mock-issue" => [
                "recordTypes" => ["discussion", "comment", "user"],
                "label" => "Mock - Create Case",
                "attachmentType" => "mock-issue",
                "submitButton" => "Create Case",
                "title" => "Mock - Case",
                "externalIDLabel" => "Mock #",
                "logoIcon" => "logo-mock",
                "name" => "Mock Provider",
                "canEscalateOwnPost" => false,
                "escalationDelayUnit" => null,
                "escalationDelayLength" => 0,
            ],
        ];
        $response = $this->api()->get("/attachments/catalog");
        $body = $response->getBody();
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals($expected, $body);
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
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("object", $response["type"]);
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

    /**
     * Test refreshing attachments using the attachments/refresh endpoint.
     *
     * @depends testPostDiscussionAttachment
     * @return void
     */
    public function testAttachmentsRefresh($attachment)
    {
        $attachments = $this->api()
            ->post("attachments/refresh", ["attachmentIDs" => [$attachment["attachmentID"]]])
            ->getBody();
        $this->assertCount(1, $attachments);
        $this->assertArrayHasKey(0, $attachments);

        $metadata = array_column($attachments[0]["metadata"], "value", "labelCode");
        $this->assertArrayHasKey("Special Mock Field 1", $metadata);
        $this->assertEquals("foo_updated", $metadata["Special Mock Field 1"]);
        $this->assertArrayHasKey("Special Mock Field 2", $metadata);
        $this->assertEquals("bar_updated", $metadata["Special Mock Field 2"]);
    }
}
