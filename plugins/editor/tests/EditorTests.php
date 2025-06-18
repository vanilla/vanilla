<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests\editor;

use Gdn_Upload;
use MediaModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Test for the editor plugin.
 */
class EditorTests extends SiteTestCase
{
    use CommunityApiTestTrait;

    protected static $addons = ["editor"];

    /**
     * @var MediaModel|mixed|object
     */
    private MediaModel $mediaModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->mediaModel = $this->container()->get(MediaModel::class);
        parent::setUp();
    }

    /**
     * Test that we don't attach an image to a rich discussion when calling `/api/v2/discussions/{id}`.
     *
     * @return void
     */
    public function testAttachmentsOnRichDiscussion(): void
    {
        $discussion = $this->createDiscussion([
            "body" => "[{\"type\":\"p\",\"children\":[{\"text\":\"Hello Discussion\"}]}]",
            "format" => "Rich2",
        ]);
        $fileUploaded = $this->mediaModel->insert([
            "Name" => __FUNCTION__,
            "Type" => "image/jpeg",
            "Path" => "uploads/" . __FUNCTION__ . ".jpg",
            "InsertUserID" => 1,
            "Size" => 1000,
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateUpdated" => date("Y-m-d H:i:s"),
            "ForeignID" => $discussion["discussionID"],
            "ForeignTable" => "Discussion",
        ]);
        $this->assertTrue($fileUploaded != false, "Failed to upload file");

        $result = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}")
            ->getBody();
        $this->assertFalse(str_contains($result["body"], __FUNCTION__));
    }

    /**
     * Test that we display attachments on non-rich discussions when calling `/api/v2/discussions/{id}`.
     *
     * @return void
     */
    public function testAttachmentsOnNonRichDiscussion(): void
    {
        $discussion = $this->createDiscussion(["format" => "Text"]);
        $fileUploaded = $this->mediaModel->insert([
            "Name" => __FUNCTION__,
            "Type" => "image/jpeg",
            "Path" => "uploads/" . __FUNCTION__ . ".jpg",
            "InsertUserID" => 1,
            "Size" => 1000,
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateUpdated" => date("Y-m-d H:i:s"),
            "ForeignID" => $discussion["discussionID"],
            "ForeignTable" => "Discussion",
        ]);
        $this->assertTrue($fileUploaded != false, "Failed to upload file");

        $result = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}")
            ->getBody();
        $this->assertTrue(str_contains($result["body"], __FUNCTION__));
    }

    /**
     * Make sure we don't attach an image to a rich comment when calling `/api/v2/comments/{id}`.
     *
     * @return void
     */
    public function testAttachmentsOnRichComment(): void
    {
        $this->createDiscussion();
        $this->createComment([
            "body" => "[{\"type\":\"p\",\"children\":[{\"text\":\"Hello Comment\"}]}]",
            "format" => "Rich2",
        ]);
        $fileUploaded = $this->mediaModel->insert([
            "Name" => __FUNCTION__,
            "Type" => "image/jpeg",
            "Path" => "uploads/" . __FUNCTION__ . ".jpg",
            "InsertUserID" => 1,
            "Size" => 1000,
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateUpdated" => date("Y-m-d H:i:s"),
            "ForeignID" => $this->lastInsertCommentID,
            "ForeignTable" => "Comment",
        ]);
        $this->assertTrue($fileUploaded != false, "Failed to upload file");

        $result = $this->api()
            ->get("/comments/{$this->lastInsertCommentID}")
            ->getBody();

        $this->assertFalse(str_contains($result["body"], __FUNCTION__));
    }

    /**
     * Make sure we are displaying attachments on non-rich comments when calling `/api/v2/comments/{id}`.
     *
     * @return void
     */
    public function testAttachmentsOnNonRichComment(): void
    {
        $this->createDiscussion();
        $this->createComment([
            "format" => "Text",
        ]);
        $fileUploaded = $this->mediaModel->insert([
            "Name" => __FUNCTION__,
            "Type" => "image/jpeg",
            "Path" => "uploads/" . __FUNCTION__ . ".jpg",
            "InsertUserID" => 1,
            "Size" => 1000,
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateUpdated" => date("Y-m-d H:i:s"),
            "ForeignID" => $this->lastInsertCommentID,
            "ForeignTable" => "Comment",
        ]);
        $this->assertTrue($fileUploaded != false, "Failed to upload file");

        $result = $this->api()
            ->get("/comments/{$this->lastInsertCommentID}")
            ->getBody();

        $this->assertTrue(str_contains($result["body"], __FUNCTION__));
    }

    /**
     * Make sure we only add attachments to non-rich comments when calling `/api/v2/comments`.
     *
     * @return void
     */
    public function testAttachmentsIndexhComment(): void
    {
        $this->createDiscussion();

        // Non Rich Comment
        $nonRichComment = $this->createComment([
            "format" => "Text",
        ]);
        $fileUploaded = $this->mediaModel->insert([
            "Name" => __FUNCTION__,
            "Type" => "image/jpeg",
            "Path" => "uploads/" . __FUNCTION__ . ".jpg",
            "InsertUserID" => 1,
            "Size" => 1000,
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateUpdated" => date("Y-m-d H:i:s"),
            "ForeignID" => $nonRichComment["commentID"],
            "ForeignTable" => "Comment",
        ]);
        $this->assertTrue($fileUploaded != false, "Failed to upload file");

        // Rich Comment
        $richComment = $this->createComment([
            "body" => "[{\"type\":\"p\",\"children\":[{\"text\":\"Hello Comment\"}]}]",
            "format" => "Rich2",
        ]);
        $fileUploaded = $this->mediaModel->insert([
            "Name" => __FUNCTION__ . "2",
            "Type" => "image/jpeg",
            "Path" => "uploads/" . __FUNCTION__ . "2.jpg",
            "InsertUserID" => 1,
            "Size" => 1000,
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateUpdated" => date("Y-m-d H:i:s"),
            "ForeignID" => $richComment["commentID"],
            "ForeignTable" => "Comment",
        ]);
        $this->assertTrue($fileUploaded != false, "Failed to upload file");

        $results = $this->api()
            ->get("/comments", ["discussionID" => $this->lastInsertedDiscussionID])
            ->getBody();

        foreach ($results as $comment) {
            if ($comment["commentID"] === $nonRichComment["commentID"]) {
                $this->assertTrue(str_contains($comment["body"], __FUNCTION__));
            } elseif ($comment["commentID"] === $richComment["commentID"]) {
                $this->assertFalse(str_contains($comment["body"], __FUNCTION__));
            }
        }
    }

    /**
     * Test that we don't duplicate attachments when file URL is already in content.
     */
    public function testNoDuplicateAttachmentsWithUrl(): void
    {
        $testFileName = "test-duplicate-file.jpg";
        $testFilePath = "uploads/test-duplicate-file.jpg";

        // Generate the full URL that would be created for this file
        $fileUrl = Gdn_Upload::url($testFilePath);

        // Create discussion with Rich2 format including the file URL directly in text
        $bodyWithFileUrl = json_encode([
            [
                "type" => "p",
                "children" => [["text" => "Some text "], ["text" => $fileUrl], ["text" => " more text"]],
            ],
        ]);

        $discussion = $this->createDiscussion([
            "body" => $bodyWithFileUrl,
            "format" => "Rich2",
        ]);

        // Add the same file as an attachment
        $fileUploaded = $this->mediaModel->insert([
            "Name" => $testFileName,
            "Type" => "image/jpeg",
            "Path" => $testFilePath,
            "InsertUserID" => 1,
            "Size" => 1000,
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateUpdated" => date("Y-m-d H:i:s"),
            "ForeignID" => $discussion["discussionID"],
            "ForeignTable" => "Discussion",
        ]);
        $this->assertTrue($fileUploaded != false, "Failed to upload file");

        $result = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}")
            ->getBody();

        // Count occurrences of the filename in the result
        $fileNameCount = substr_count($result["body"], $testFileName);

        // Should only appear once (in the existing content, not as a duplicate attachment)
        $this->assertEquals(1, $fileNameCount, "File should appear exactly once, not duplicated");
    }

    /**
     * Test that we DO add attachments when file is not already in content.
     */
    public function testAddAttachmentWhenNotInContent(): void
    {
        $testFileName = "test-new-attachment.jpg";
        $testFilePath = "uploads/test-new-attachment.jpg";

        // Create discussion without the file using Rich2 format
        $bodyWithoutFile = json_encode([
            [
                "type" => "p",
                "children" => [["text" => "Some text without the file"]],
            ],
        ]);

        $discussion = $this->createDiscussion([
            "body" => $bodyWithoutFile,
            "format" => "Rich2",
        ]);

        // Add file as attachment
        $fileUploaded = $this->mediaModel->insert([
            "Name" => $testFileName,
            "Type" => "image/jpeg",
            "Path" => $testFilePath,
            "InsertUserID" => 1,
            "Size" => 1000,
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateUpdated" => date("Y-m-d H:i:s"),
            "ForeignID" => $discussion["discussionID"],
            "ForeignTable" => "Discussion",
        ]);
        $this->assertTrue($fileUploaded != false, "Failed to upload file");

        $result = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}")
            ->getBody();

        // Should appear once as a new attachment
        $this->assertStringContainsString($testFileName, $result["body"], "File should be added as attachment");
        $this->assertStringContainsString(
            "js-embed embedResponsive",
            $result["body"],
            "Should contain FileEmbed HTML structure"
        );
    }

    /**
     * Test that we handle files and content with HTML special characters using URLs.
     */
    public function testNoDuplicateAttachmentsWithHtmlSpecialCharacters(): void
    {
        $testFileName = "file with spaces & symbols.jpg";
        $testFilePath = "uploads/file with spaces & symbols.jpg";

        // Generate the full URL that would be created for this file
        $fileUrl = Gdn_Upload::url($testFilePath);

        // Create discussion with Rich2 format including the file URL with special characters
        // Also include some HTML entities in the surrounding text
        $bodyWithSpecialChars = json_encode([
            [
                "type" => "p",
                "children" => [
                    ["text" => "Check this file: \""],
                    ["text" => $fileUrl],
                    ["text" => "\" & more content here."],
                ],
            ],
        ]);

        $discussion = $this->createDiscussion([
            "body" => $bodyWithSpecialChars,
            "format" => "Rich2",
        ]);

        // Add the same file as an attachment
        $fileUploaded = $this->mediaModel->insert([
            "Name" => $testFileName,
            "Type" => "image/jpeg",
            "Path" => $testFilePath,
            "InsertUserID" => 1,
            "Size" => 1000,
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateUpdated" => date("Y-m-d H:i:s"),
            "ForeignID" => $discussion["discussionID"],
            "ForeignTable" => "Discussion",
        ]);
        $this->assertTrue($fileUploaded != false, "Failed to upload file");

        $result = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}")
            ->getBody();

        // Count occurrences of filename (accounting for HTML encoding)
        $htmlEncodedFileName = htmlspecialchars($testFileName, ENT_QUOTES);
        $fileNameCount = substr_count($result["body"], $testFileName);
        $htmlFileNameCount = substr_count($result["body"], $htmlEncodedFileName);

        // Count total embeds to verify we don't have duplicates
        $embedCount = substr_count($result["body"], "js-embed");

        // Should have exactly one filename occurrence (either encoded or unencoded)
        $totalFileNameOccurrences = $fileNameCount + $htmlFileNameCount;
        $this->assertEquals(
            1,
            $totalFileNameOccurrences,
            "File with special characters should appear exactly once in the filename"
        );

        // If embedCount is 0, that means our deduplication worked and prevented duplicate attachment
        // If embedCount is 1, that means the file was properly embedded (original content didn't contain it)
        $this->assertTrue($embedCount <= 1, "Should have at most one embed, no duplicate attachments");
        $this->assertTrue(
            $totalFileNameOccurrences >= 1,
            "File should appear at least once in content or as attachment"
        );
    }

    /**
     * Test deduplication works for comments as well.
     */
    public function testNoDuplicateAttachmentsInComments(): void
    {
        $testFileName = "test-comment-duplicate.jpg";
        $testFilePath = "uploads/test-comment-duplicate.jpg";

        // Generate the full URL that would be created for this file
        $fileUrl = Gdn_Upload::url($testFilePath);

        $this->createDiscussion();

        // Create comment with Rich2 format including file URL
        $bodyWithFileUrl = json_encode([
            [
                "type" => "p",
                "children" => [["text" => "Comment text "], ["text" => $fileUrl], ["text" => " embedded"]],
            ],
        ]);

        $comment = $this->createComment([
            "body" => $bodyWithFileUrl,
            "format" => "Rich2",
        ]);

        // Add the same file as an attachment to the comment
        $fileUploaded = $this->mediaModel->insert([
            "Name" => $testFileName,
            "Type" => "image/jpeg",
            "Path" => $testFilePath,
            "InsertUserID" => 1,
            "Size" => 1000,
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateUpdated" => date("Y-m-d H:i:s"),
            "ForeignID" => $comment["commentID"],
            "ForeignTable" => "Comment",
        ]);
        $this->assertTrue($fileUploaded != false, "Failed to upload file");

        $result = $this->api()
            ->get("/comments/{$comment["commentID"]}")
            ->getBody();

        // Should only appear once
        $fileNameCount = substr_count($result["body"], $testFileName);
        $this->assertEquals(1, $fileNameCount, "File should appear exactly once in comment");
    }

    /**
     * Test that only one file is attached when multiple files have the same name but different URLs.
     */
    public function testNoDuplicateAttachmentsWithSameName(): void
    {
        $testFileName = "duplicate-name-file.jpg";
        $testFilePath1 = "uploads/path1/duplicate-name-file.jpg";
        $testFilePath2 = "uploads/path2/duplicate-name-file.jpg";

        // Create discussion without the files using Rich2 format
        $bodyWithoutFiles = json_encode([
            [
                "type" => "p",
                "children" => [["text" => "Some text without any files"]],
            ],
        ]);

        $discussion = $this->createDiscussion([
            "body" => $bodyWithoutFiles,
            "format" => "Rich2",
        ]);

        // Add first file as attachment
        $fileUploaded1 = $this->mediaModel->insert([
            "Name" => $testFileName,
            "Type" => "image/jpeg",
            "Path" => $testFilePath1,
            "InsertUserID" => 1,
            "Size" => 1000,
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateUpdated" => date("Y-m-d H:i:s"),
            "ForeignID" => $discussion["discussionID"],
            "ForeignTable" => "Discussion",
        ]);
        $this->assertTrue($fileUploaded1 != false, "Failed to upload first file");

        // Add second file with same name but different path
        $fileUploaded2 = $this->mediaModel->insert([
            "Name" => $testFileName,
            "Type" => "image/jpeg",
            "Path" => $testFilePath2,
            "InsertUserID" => 1,
            "Size" => 2000,
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateUpdated" => date("Y-m-d H:i:s"),
            "ForeignID" => $discussion["discussionID"],
            "ForeignTable" => "Discussion",
        ]);
        $this->assertTrue($fileUploaded2 != false, "Failed to upload second file");

        $result = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}")
            ->getBody();

        // Count embeds to verify only one file is attached
        $embedCount = substr_count($result["body"], "js-embed");

        // Verify that only one file is attached (by checking embed count)
        $this->assertEquals(1, $embedCount, "Should have exactly one embed despite multiple files with same name");

        // Verify that we only have one file path in the content (only path1, not path2)
        $this->assertStringContainsString("path1", $result["body"], "Should contain the first file path");
        $this->assertStringNotContainsString("path2", $result["body"], "Should not contain the second file path");
    }

    /**
     * Test that inactive attachments (Active = 0) are not displayed.
     */
    public function testInactiveAttachmentsNotDisplayed(): void
    {
        $testFileName = "inactive-file.jpg";
        $testFilePath = "uploads/inactive-file.jpg";

        // Create discussion without the file using Rich2 format
        $bodyWithoutFile = json_encode([
            [
                "type" => "p",
                "children" => [["text" => "Some text without the file"]],
            ],
        ]);

        $discussion = $this->createDiscussion([
            "body" => $bodyWithoutFile,
            "format" => "Rich2",
        ]);

        // Add file as inactive attachment (Active = 0)
        $fileUploaded = $this->mediaModel->insert([
            "Name" => $testFileName,
            "Type" => "image/jpeg",
            "Path" => $testFilePath,
            "InsertUserID" => 1,
            "Size" => 1000,
            "Active" => 0, // Inactive
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateUpdated" => date("Y-m-d H:i:s"),
            "ForeignID" => $discussion["discussionID"],
            "ForeignTable" => "Discussion",
        ]);
        $this->assertTrue($fileUploaded != false, "Failed to upload file");

        $result = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}")
            ->getBody();

        // Should not appear since the file is inactive
        $this->assertStringNotContainsString($testFileName, $result["body"], "Inactive file should not be displayed");
        $this->assertStringNotContainsString(
            "js-embed",
            $result["body"],
            "Should not contain any embeds for inactive files"
        );
    }

    /**
     * Test that active attachments (Active = 1) are displayed.
     */
    public function testActiveAttachmentsDisplayed(): void
    {
        $testFileName = "active-file.jpg";
        $testFilePath = "uploads/active-file.jpg";

        // Create discussion without the file using Rich2 format
        $bodyWithoutFile = json_encode([
            [
                "type" => "p",
                "children" => [["text" => "Some text without the file"]],
            ],
        ]);

        $discussion = $this->createDiscussion([
            "body" => $bodyWithoutFile,
            "format" => "Rich2",
        ]);

        // Add file as active attachment (Active = 1)
        $fileUploaded = $this->mediaModel->insert([
            "Name" => $testFileName,
            "Type" => "image/jpeg",
            "Path" => $testFilePath,
            "InsertUserID" => 1,
            "Size" => 1000,
            "Active" => 1, // Active
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateUpdated" => date("Y-m-d H:i:s"),
            "ForeignID" => $discussion["discussionID"],
            "ForeignTable" => "Discussion",
        ]);
        $this->assertTrue($fileUploaded != false, "Failed to upload file");

        $result = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}")
            ->getBody();

        // Should appear since the file is active
        $this->assertStringContainsString($testFileName, $result["body"], "Active file should be displayed");
        $this->assertStringContainsString("js-embed", $result["body"], "Should contain embed for active file");
    }
}
