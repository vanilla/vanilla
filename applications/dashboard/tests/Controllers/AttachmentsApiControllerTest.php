<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Controllers;

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

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $container = \Gdn::getContainer();
        $container
            ->rule(ExternalIssueService::class)
            ->addCall("addProvider", [new \Garden\Container\Reference(MockExternalIssueProvider::class)]);
    }

    /**
     * Test posting an attachment for a discussion.
     *
     * @return void
     */
    public function testPostDiscussionAttachment(): void
    {
        $discussion = $this->createDiscussion();
        $attachmentData = $this->createAttachmentData("discussion", $discussion["discussionID"]);

        $attachment = $this->api()
            ->post("/attachments", $attachmentData)
            ->getBody();

        foreach ($attachmentData as $field => $value) {
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
        $attachmentData = $this->createAttachmentData("comment", $comment["commentID"]);

        $attachment = $this->api()
            ->post("/attachments", $attachmentData)
            ->getBody();

        foreach ($attachmentData as $field => $value) {
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
        $attachmentData = $this->createAttachmentData("discussion", $discussion["discussionID"]);
        $this->api()->setUserID($member["userID"]);

        $this->expectExceptionMessage("Permission Problem");
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
        $attachmentData = $this->createAttachmentData("discussion", $discussion["discussionID"]);

        $attachmentData["source"] = "invalid";

        $this->expectExceptionMessage("No provider was found for this attachment source.");
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
        $attachmentData = $this->createAttachmentData("discussion", $discussion["discussionID"]);

        $this->api()
            ->post("/attachments", $attachmentData)
            ->getBody();

        $attachments = $this->api()
            ->get("/attachments", ["recordType" => "discussion", "recordID" => $discussion["discussionID"]])
            ->getBody();

        $this->assertCount(1, $attachments);

        $attachment = $attachments[0];

        foreach ($attachmentData as $field => $value) {
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

        $attachmentData = $this->createAttachmentData("discussion", $discussion["discussionID"]);
        $this->api()->post("/attachments", $attachmentData);

        $member = $this->createUser();
        $this->api()->setUserID($member["userID"]);

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
            "type" => "mock-issue",
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
}
