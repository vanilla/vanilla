<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\addons\addons\TestMockIssue;

use AttachmentModel;
use CommentModel;
use DiscussionModel;
use Vanilla\Dashboard\Models\ExternalIssueProviderInterface;
use Vanilla\Formatting\DateTimeFormatter;

/**
 * Mock issue provider for tests.
 */
class MockExternalIssueProvider implements ExternalIssueProviderInterface
{
    const SOURCE_NAME = "mock";

    private AttachmentModel $attachmentModel;

    private CommentModel $commentModel;

    private DiscussionModel $discussionModel;

    /**
     * D.I.
     *
     * @param AttachmentModel $attachmentModel
     * @param CommentModel $commentModel
     * @param DiscussionModel $discussionModel
     */
    public function __construct(
        AttachmentModel $attachmentModel,
        CommentModel $commentModel,
        DiscussionModel $discussionModel
    ) {
        $this->attachmentModel = $attachmentModel;
        $this->commentModel = $commentModel;
        $this->discussionModel = $discussionModel;
    }

    /**
     * @inheritDoc
     */
    public function makeNewIssue(string $recordType, int $recordID, array $issueData): array
    {
        $issueData = $this->attachmentModel->fillMetadata($issueData);
        $issueData = \Vanilla\Utility\ArrayUtils::pascalCase($issueData);

        $record =
            $recordType == "discussion"
                ? $this->discussionModel->getID($recordID, DATASET_TYPE_ARRAY)
                : $this->commentModel->getID($recordID, DATASET_TYPE_ARRAY);

        $sourceID = rand(1, 10000);

        $attachmentID = $this->attachmentModel->save([
            "Type" => "mock-issue",
            "ForeignID" => AttachmentModel::rowID($record),
            "ForeignUserID" => $record["InsertUserID"],
            "Source" => "mock",
            "SourceID" => $sourceID,
            "SourceURL" => "www.example.com/mockIssue/{$sourceID}",
            "LastModifiedDate" => DateTimeFormatter::timeStampToDateTime(now()),
            "Status" => "active",
            "SpecialMockField1" => $issueData["SpecialMockField1"],
            "SpecialMockField2" => $issueData["SpecialMockField2"],
        ]);

        $attachment = $this->attachmentModel->getID($attachmentID);
        return (array) $attachment;
    }

    /**
     * @inheritDoc
     */
    public function issuePostSchema(): \Garden\Schema\Schema
    {
        $schema = \Garden\Schema\Schema::parse(["specialMockField1:s", "specialMockField2:s"]);

        return $schema;
    }

    /**
     * @inheritDoc
     */
    public function fullIssueSchema(): \Garden\Schema\Schema
    {
        $schema = $this->attachmentModel->getAttachmentSchema();
        $schema->merge($this->issuePostSchema());
        return $schema;
    }

    /**
     * @inheritDoc
     */
    public function getSourceName(): string
    {
        return self::SOURCE_NAME;
    }
}
