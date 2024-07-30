<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\CurrentTimeStamp;
use Vanilla\Database\Operation;
use Vanilla\Formatting\Formats\WysiwygFormat;
use Vanilla\Forum\Models\CommunityManagement\EscalationModel;
use Vanilla\Forum\Models\CommunityManagement\ReportModel;
use Vanilla\Forum\Models\CommunityManagement\ReportReasonModel;
use Vanilla\Logging\AuditLogger;
use Vanilla\Models\Model;
use Vanilla\Permissions;
use Vanilla\Premoderation\PremoderationException;
use Vanilla\Premoderation\PremoderationItem;
use Vanilla\Premoderation\PremoderationResponse;
use Vanilla\Premoderation\PremoderationResult;
use Vanilla\Premoderation\PremoderationService;
use Vanilla\Premoderation\SuperSpamAuditLog;

/**
 * Model to handle premoderation for the new community management system.
 * This is the modern analog to the legacy {@link \SpamModel} and related events.
 */
class PremoderationModel
{
    /**
     * DI.
     */
    public function __construct(
        private \Gdn_Session $session,
        private EscalationModel $escalationModel,
        private ReportModel $reportModel,
        private PremoderationService $premoderationService,
        private \UserModel $userModel,
        private \Gdn_Database $db
    ) {
    }

    /**
     * Handle premoderation for an item.
     *
     * @param PremoderationItem $item
     *
     * @return void
     * @throws PremoderationException
     */
    public function premoderateItem(PremoderationItem $item): void
    {
        // First check if we have any permissions that bail us out of premoderation.
        if ($this->session->checkPermission("community.moderate")) {
            // Global community mod.
            return;
        }

        if ($item->placeRecordType === "category") {
            $categoryID = $item->placeRecordID;
            if (
                $this->session
                    ->getPermissions()
                    ->has(
                        "posts.moderate",
                        $categoryID,
                        Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
                        \CategoryModel::PERM_JUNCTION_TABLE
                    )
            ) {
                // Category mod.
                return;
            }
        }

        $result = $this->premoderationService->premoderateItem($item);

        if ($result->isSuperSpam() && ($auditEvent = SuperSpamAuditLog::tryFromPremoderationResult($item, $result))) {
            // This was so bad we will silently drop it after audit logging it.
            AuditLogger::log($auditEvent);
            throw new PremoderationException($item, $result);
        }

        if (!$result->isSpam() && !$result->isApprovalRequired()) {
            // Nothing to do.
            return;
        }

        $reportID = $this->reportResult($item, $result);
        if ($reportID === null) {
            return;
        }

        // Then finally throw the exception up.
        throw new PremoderationException($item, $result, ["reportID" => $reportID]);
    }

    /**
     * @param PremoderationItem $item
     * @param PremoderationResult $result
     * @return int|null The reportID if we were able to create it..
     */
    private function reportResult(PremoderationItem $item, PremoderationResult $result): ?int
    {
        return $this->db->runWithTransaction(function () use ($item, $result) {
            // Make reports based on the results

            $reportID = $this->createReport($item, $result);
            if ($reportID === null) {
                return null;
            }

            $escalationID = null;
            if ($item->recordID !== null) {
                $existingEscalations = $this->escalationModel->select(
                    where: [
                        "recordType" => $item->recordType,
                        "recordID" => $item->recordID,
                    ],
                    options: [
                        Model::OPT_LIMIT => 1,
                    ]
                );
                $escalationID = $existingEscalations[0]["escalationID"] ?? null;
            }

            if ($escalationID !== null) {
                $this->escalationModel->linkReportsToEscalation($escalationID, [$reportID]);
            }

            return $reportID;
        });
    }

    /**
     * @param PremoderationItem $item
     * @param PremoderationResult $result
     * @return int|null
     */
    private function createReport(PremoderationItem $item, PremoderationResult $result): ?int
    {
        $reasonIDs = [];
        if ($result->isSpam()) {
            $reasonIDs[] = ReportReasonModel::INITIAL_REASON_SPAM_AUTOMATION;
        }
        if ($result->isApprovalRequired()) {
            $reasonIDs[] = ReportReasonModel::INITIAL_REASON_APPROVAL;
        }
        if (empty($reasonIDs)) {
            return null;
        }

        $noteHtml = "";
        $modID = $this->userModel->getSystemUserID();
        foreach ($result->getResponses() as $response) {
            $noteHtml .= $response->getNoteHtml();
            $modID = $response->getModeratorUserID() ?? $modID;
        }

        $report = [
            "recordType" => $item->recordType,
            "recordID" => $item->recordID,
            "recordName" => $item->recordName,
            "recordUserID" => $item->userID,
            "recordDateInserted" => CurrentTimeStamp::getDateTime(),
            "placeRecordType" => $item->placeRecordType,
            "placeRecordID" => $item->placeRecordID,
            "insertUserID" => $modID,
            "recordBody" => $item->recordBody,
            "recordFormat" => $item->recordFormat,
            "status" => ReportModel::STATUS_NEW,
            "premoderatedRecord" => $item->rawRow,
            "isPendingUpdate" => $item->isEdit,
            "isPending" => true,
            "reportReasonIDs" => $reasonIDs,
        ];

        if (!empty($noteHtml)) {
            $report["noteBody"] = $noteHtml;
            $report["noteFormat"] = WysiwygFormat::FORMAT_KEY;
        }

        if ($item->isEdit && $item->recordID !== null) {
            // Clear out existing "New" edits. We only ever care about the latest pending edit.
            $this->reportModel->delete(
                where: [
                    "recordType" => $item->recordType,
                    "recordID" => $item->recordID,
                    "status" => ReportModel::STATUS_NEW,
                    "isPendingUpdate" => true,
                ]
            );
        }

        $reportID = $this->reportModel->insert(
            $report,
            options: [
                Model::OPT_MODE => Operation::MODE_IMPORT,
            ]
        );

        return $reportID;
    }
}
