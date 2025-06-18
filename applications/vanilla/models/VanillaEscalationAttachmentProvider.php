<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Garden\Schema\Schema;
use Garden\Utils\ContextException;
use Vanilla\Dashboard\Models\AttachmentProviderInterface;
use Vanilla\Forum\Models\CommunityManagement\EscalationModel;
use Vanilla\Permissions;
use Vanilla\Utility\ModelUtils;

/**
 * Attachment provider to show Vanilla escalations as attachments.
 */
class VanillaEscalationAttachmentProvider implements AttachmentProviderInterface
{
    /**
     * DI.
     */
    public function __construct(
        private \Gdn_Session $session,
        private \AttachmentModel $attachmentModel,
        private \UserModel $userModel,
        private EscalationModel $escalationModel
    ) {
    }

    /**
     * Used to create an escalation.
     *
     * @param array $escalation A normalized escalation record.
     *
     * @return void
     */
    public function createAttachmentFromEscalation(array $escalation): void
    {
        $foreignID = match ($escalation["recordType"]) {
            "discussion" => "d-" . $escalation["recordID"],
            "comment" => "c-" . $escalation["recordID"],
            default => throw new ContextException("Invalid record type."),
        };

        $row =
            [
                "Type" => $this->getTypeName(),
                "ForeignID" => $foreignID,
                "ForeignUserID" => $escalation["insertUserID"],
                "Source" => $this->getProviderName(),
                "SourceID" => $escalation["escalationID"],
                "SourceURL" => $escalation["url"],
            ] + $this->extractAttachmentMetadata($escalation);

        $this->attachmentModel->save($row);
        ModelUtils::validationResultToValidationException($this->attachmentModel);
    }

    /**
     * Given a normalized escalation extract metadata from it for the attachment record.
     *
     * @param array $escalation
     *
     * @return array
     */
    private function extractAttachmentMetadata(array $escalation): array
    {
        $dateLastReport = $escalation["dateLastReport"];
        if (is_array($dateLastReport)) {
            $dateLastReport = new \DateTimeImmutable($dateLastReport["date"]);
        }

        if ($dateLastReport instanceof \DateTimeInterface) {
            $dateLastReport = $dateLastReport->format(\DateTime::RFC3339_EXTENDED);
        }
        return [
            "name" => $escalation["name"],
            "assignedUserID" => $escalation["assignedUserID"] ?? null,
            "countReports" => $escalation["countReports"],
            "reportReasons" => $escalation["reportReasons"],
            "dateLastReport" => $dateLastReport,
            "status" => $escalation["status"],
            "categoryID" => $escalation["placeRecordID"],
        ];
    }

    /**
     * @inheritdoc
     */
    public function normalizeAttachment(array $attachment): array
    {
        // No normalization needed right now.
        $metadata = [];

        $metadata[] = [
            "labelCode" => "Name",
            "value" => $attachment["name"],
        ];

        if ($attachment["assignedUserID"] !== null && $attachment["assignedUserID"] > \UserModel::GUEST_USER_ID) {
            $userFragment = $this->userModel->getFragmentByID($attachment["assignedUserID"], true);
            $metadata[] = [
                "labelCode" => "Assignee",
                "value" => $userFragment["userID"],
                "format" => "user",
                "userFragment" => $userFragment,
            ];
        }

        $metadata[] = [
            "labelCode" => "# Reports",
            "value" => $attachment["countReports"],
        ];

        $metadata[] = [
            "labelCode" => "Last Reported",
            "value" => $attachment["dateLastReport"],
            "format" => "date-time",
        ];

        $metadata[] = [
            "labelCode" => "Report Reasons",
            "value" => array_map(function (array $reason) {
                return $reason["name"];
            }, $attachment["reportReasons"]),
        ];

        $attachment["metadata"] = $metadata;

        return $attachment;
    }

    /**
     * @inheritdoc
     */
    public function refreshAttachments(array $attachmentRows): array
    {
        $escalationIDs = array_column($attachmentRows, "SourceID");
        $escalations = $this->escalationModel->queryEscalations(filters: ["escalationID" => $escalationIDs]);

        $escalationsByID = array_column($escalations, null, "escalationID");

        foreach ($attachmentRows as &$attachmentRow) {
            $escalation = $escalationsByID[$attachmentRow["SourceID"]] ?? null;
            if (!isset($escalation)) {
                $attachmentRow["status"] = "deleted";
                continue;
            }

            $attachmentRow = array_merge($attachmentRow, $this->extractAttachmentMetadata($escalation));
            $this->attachmentModel->save($attachmentRow);
        }

        return $attachmentRows;
    }

    /**
     * @inheritdoc
     */
    public function getTypeName(): string
    {
        return "vanilla-escalation";
    }

    /**
     * @inheritdoc
     */
    public function getIsEscalation(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getWriteableContentScope(): string
    {
        // No one can create these through the attachments API.
        return AttachmentProviderInterface::WRITEABLE_CONTENT_SCOPE_NONE;
    }

    /**
     * @return bool
     */
    public function hasReadPermissions(): bool
    {
        return $this->session->getPermissions()->hasAny(["community.moderate", "posts.moderate"]);
    }

    /**
     * @inheritdoc
     */
    public function getRecordTypes(): array
    {
        return ["discussion", "comment"];
    }

    /**
     * @inheritdoc
     */
    public function getTitleLabelCode(): string
    {
        return "Vanilla - Escalation";
    }

    /**
     * @inheritdoc
     */
    public function getExternalIDLabelCode(): string
    {
        return "Escalation #";
    }

    /**
     * @inheritdoc
     */
    public function getLogoIconName(): string
    {
        return "vanilla-logo";
    }

    /**
     * @inheritdoc
     */
    public function getRefreshTimeSeconds(): int
    {
        return 60 * 15; // 1 minutes
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
    public function canViewBasicAttachment(array $attachment): bool
    {
        // There is not basic view for this right now.
        return false;
    }

    /**
     * @inheritdoc
     */
    public function canViewFullAttachment(array $attachment): bool
    {
        if ($this->session->checkPermission("community.moderate")) {
            // Global mods can always view this.
            return true;
        }

        // Otherwise it's category specific permissions.
        $categoryID = $attachment["categoryID"] ?? null;
        return $this->session
            ->getPermissions()
            ->has(
                "posts.moderate",
                $categoryID,
                checkMode: Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
                junctionTable: \CategoryModel::PERM_JUNCTION_TABLE
            );
    }

    /**
     * @inheritdoc
     */
    public function canCreateAttachmentForRecord(string $recordType, int $recordID): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getProviderName(): string
    {
        return "Vanilla";
    }

    ///
    /// We don't write these.
    ///

    /**
     * We don't actually write attachments through this system.
     *
     * Instead they are created through {@link self::createAttachmentFromEscalation()}
     *
     * @param string $recordType
     * @param int $recordID
     * @param array $issueData
     * @return array
     */
    public function createAttachment(string $recordType, int $recordID, array $issueData): array
    {
        throw new ContextException("Not implemented.");
    }

    /**
     * We don't actually write attachments through this system.
     *
     * Instead they are created through {@link self::createAttachmentFromEscalation()}
     *
     * @param string $recordType
     * @param int $recordID
     * @param array $args
     * @return Schema
     */
    public function getHydratedFormSchema(string $recordType, int $recordID, array $args): Schema
    {
        throw new ContextException("Not implemented.");
    }

    public function getCreateLabelCode(): string
    {
        return "You should never see this.";
    }

    public function getSubmitLabelCode(): string
    {
        return "You should never see this.";
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalCatalogInfo(): array
    {
        return [];
    }
}
