<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Addons\TestMockIssue;

use AttachmentModel;
use CommentModel;
use DiscussionModel;
use OpenStack\Identity\v3\Models\User;
use UserModel;
use Garden\Schema\Schema;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\AttachmentProviderInterface;

/**
 * Mock issue provider for tests.
 */
class MockAttachmentProvider implements AttachmentProviderInterface
{
    const TYPE_NAME = "mock-issue";

    private AttachmentModel $attachmentModel;

    private CommentModel $commentModel;

    private DiscussionModel $discussionModel;

    private UserModel $userModel;

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
        DiscussionModel $discussionModel,
        UserModel $userModel
    ) {
        $this->attachmentModel = $attachmentModel;
        $this->commentModel = $commentModel;
        $this->discussionModel = $discussionModel;
        $this->userModel = $userModel;
    }

    /**
     * @inheritdoc
     */
    public function createAttachment(string $recordType, int $recordID, array $issueData): array
    {
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
            "LastModifiedDate" => CurrentTimeStamp::getMySQL(),
            "Status" => "active",
            "SpecialMockField1" => $issueData["SpecialMockField1"],
            "SpecialMockField2" => $issueData["SpecialMockField2"],
        ]);

        $attachment = $this->attachmentModel->getID($attachmentID);
        return (array) $attachment;
    }

    /**
     * @inheritdoc
     */
    public function getHydratedFormSchema(string $recordType, int $recordID, array $args): Schema
    {
        $schema = \Garden\Schema\Schema::parse(["specialMockField1:s", "specialMockField2:s"]);

        return $schema;
    }

    /**
     * @inheritdoc
     */
    public function getProviderName(): string
    {
        return "Mock Provider";
    }

    /**
     * @inheritdoc
     */
    public function getTypeName(): string
    {
        return self::TYPE_NAME;
    }

    /**
     * @inheritdoc
     */
    public function getIsEscalation(): bool
    {
        return true;
    }

    /**
     * Verify that the user has the `staff.allow` permission.
     *
     * @return bool
     */
    public function hasReadPermissions(): bool
    {
        return $this->userModel->checkPermission(\Gdn::session()->User, "staff.allow");
    }

    /**
     * @inheritdoc
     */
    public function getRecordTypes(): array
    {
        return ["discussion", "comment", "user"];
    }

    /**
     * @inheritdoc
     */
    public function getCreateLabelCode(): string
    {
        return "Mock - Create Case";
    }

    /**
     * @inheritdoc
     */
    public function getSubmitLabelCode(): string
    {
        return "Create Case";
    }

    /**
     * @inheritdoc
     */
    public function getTitleLabelCode(): string
    {
        return "Mock - Case";
    }

    /**
     * @inheritdoc
     */
    public function getExternalIDLabelCode(): string
    {
        return "Mock #";
    }

    /**
     * @inheritdoc
     */
    public function getLogoIconName(): string
    {
        return "logo-mock";
    }

    /**
     * @inheritdoc
     */
    public function refreshAttachments(array $attachmentRows): array
    {
        // Simulate update from external issue tracker
        foreach ($attachmentRows as &$attachmentRow) {
            $attachmentRow = array_merge($attachmentRow, [
                "SpecialMockField1" => ($attachmentRow["SpecialMockField1"] ?? "fallback") . "_updated",
                "SpecialMockField2" => ($attachmentRow["SpecialMockField2"] ?? "fallback") . "_updated",
            ]);
            $this->attachmentModel->save($attachmentRow);
        }

        return $attachmentRows;
    }

    /**
     * @param array $attachment
     * @return array
     */
    public function normalizeAttachment(array $attachment): array
    {
        $attachment["metadata"] = [
            [
                "labelCode" => "Special Mock Field 1",
                "value" => $attachment["SpecialMockField1"] ?? "fallback",
            ],
            [
                "labelCode" => "Special Mock Field 2",
                "value" => $attachment["SpecialMockField2"] ?? "fallback",
            ],
        ];
        return $attachment;
    }

    /**
     * @inheritdoc
     */
    public function getRefreshTimeSeconds(): int
    {
        return 5 * 60;
    }

    /**
     * @inheritdoc
     */
    public function getWriteableContentScope(): string
    {
        if ($this->userModel->checkPermission(\Gdn::session()->User, "staff.allow")) {
            return AttachmentProviderInterface::WRITEABLE_CONTENT_SCOPE_ALL;
        }
        return AttachmentProviderInterface::WRITEABLE_CONTENT_SCOPE_NONE;
    }

    /**
     * @inheritdoc
     */
    public function canViewBasicAttachment(array $attachment): bool
    {
        return $this->hasReadPermissions();
    }

    /**
     * @inheritdoc
     */
    public function canViewFullAttachment(array $attachment): bool
    {
        return $this->userModel->checkPermission(\Gdn::session()->User, "staff.allow");
    }

    /**
     * @inheritdoc
     */
    public function canCreateAttachmentForRecord(string $recordType, int $recordID): bool
    {
        return $this->userModel->checkPermission(\Gdn::session()->User, "staff.allow");
    }

    /**
     * @inheritdoc
     */
    public function getEscalationDelayUnit(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getEscalationDelayLength(): int
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalCatalogInfo(): array
    {
        return [];
    }
}
