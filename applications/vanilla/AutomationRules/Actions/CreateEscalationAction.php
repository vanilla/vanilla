<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Actions;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\Schema;
use Gdn;
use RoleModel;
use Vanilla\Dashboard\AutomationRules\Models\EscalationRuleDataType;
use Vanilla\Dashboard\AutomationRules\Models\PostInterface;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\FeatureFlagHelper;
use Vanilla\Formatting\Formats\Rich2Format;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forum\Models\CommunityManagement\EscalationModel;
use Vanilla\Forum\Models\CommunityManagement\ReportModel;
use Vanilla\Forum\Models\CommunityManagement\ReportReasonModel;
use Vanilla\Logger;
use Vanilla\Models\Model;
use Vanilla\Forum\Models\CommunityManagement\CommunityManagementRecordModel;

class CreateEscalationAction extends ExternalAction implements PostInterface
{
    public string $affectedRecordType = "Discussion";

    private const AUTOMATION_REPORT_BODY = '[{"children":[{"text":"Automation generated report"}],"type":"p"}]';
    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return "createEscalationAction";
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return "Create Escalation";
    }

    /**
     * @inheritdoc
     */
    public static function getContentType(): string
    {
        return "posts";
    }

    /**
     * @inheritdoc
     */
    public static function canAddAction(): bool
    {
        return FeatureFlagHelper::featureEnabled("CommunityManagementBeta") &&
            FeatureFlagHelper::featureEnabled("escalations");
    }

    /**
     * @inheritdoc
     */
    public function setPostRecord(array $postRecord): void
    {
        $this->postRecord = $postRecord;
    }

    /**
     * @inheritdoc
     */
    public function getPostRecord(): array
    {
        return $this->postRecord;
    }

    /**
     * @inheritdoc
     */
    public static function getSchema(): Schema
    {
        $roleModel = Gdn::getContainer()->get(RoleModel::class);
        $roles = $roleModel->getByPermission("Garden.Moderation.Manage")->resultArray();
        $roleIDs = array_column($roles, "RoleID");
        $qs = !empty($roleIDs) ? "?" . http_build_query(["roleIDs" => $roleIDs]) : "";
        $schema = [
            "recordIsLive?" => [
                "type" => "boolean",
                "default" => false,
                "x-control" => SchemaForm::checkBox(
                    new FormOptions("Keep record live", "Keep post visible in community")
                ),
            ],
            "assignedModeratorID?" => [
                "type" => "integer",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Assign Moderator", "Select what moderator escalations should be assigned to"),
                    new ApiFormChoices("/api/v2/users$qs", "/api/v2/users/%s", "userID", "name")
                ),
            ],
        ];

        return Schema::parse($schema);
    }

    /**
     * @inheritdoc
     */
    public static function getTriggers(): array
    {
        return EscalationRuleDataType::getTriggers();
    }

    /**
     * Execute the long runner action
     *
     * @param array $actionValue Action value.
     * @param array $object Discussion DB object to perform action on.
     * @return bool
     */
    public function executeLongRunner(array $actionValue, array $object): bool
    {
        //check the type of object
        if (!isset($object["recordID"]) && isset($object["CommentID"])) {
            $object["recordID"] = $object["CommentID"];
            $object["recordType"] = "comment";
            $object["recordName"] = "Comment: " . substr($object["Body"] ?? "", 0, 100);
        }
        if (!isset($object["recordID"]) && isset($object["DiscussionID"])) {
            $object["recordID"] = $object["DiscussionID"];
            $object["recordType"] = "discussion";
            $object["recordName"] = $object["Name"] ?? "";
        }
        $this->setPostRecord($object);
        return $this->execute();
    }

    /**
     * @inheritdoc
     */
    public function execute(): bool
    {
        $object = $this->getPostRecord();
        $longRunner = false;
        // Mark the dispatch as running
        if (!$this->dispatched) {
            $attributes = [
                "affectedRecordType" => $object["recordType"],
                "recordID" => $object["recordID"],
                "estimatedRecordCount" => 1,
                "affectedRecordCount" => 0,
            ];
            $this->logDispatched(AutomationRuleDispatchesModel::STATUS_RUNNING, null, $attributes);
        } else {
            $longRunner = true;
            $attributes = [
                "affectedRecordType" => $object["recordType"],
            ];
            $this->automationRuleDispatchesModel->updateDispatchStatus(
                $this->getDispatchUUID(),
                AutomationRuleDispatchesModel::STATUS_RUNNING,
                $attributes
            );
        }
        $reportModel = Gdn::getContainer()->get(ReportModel::class);
        $automationRule = $this->getAutomationRule();

        if (empty($object["reportID"])) {
            if ($this->dispatchType === AutomationRuleDispatchesModel::TYPE_MANUAL) {
                // For manual dispatches, we will create a report no matter what
                $object["reportID"] = $this->createReport($object["recordID"], $object["recordType"]);
            } else {
                // This is not report post escalation we need to make sure if this was reported previously
                $where = [
                    "r.recordType" => $object["recordType"],
                    "r.recordID" => $object["recordID"],
                    "r.placeRecordType" => "category",
                    "r.placeRecordID" => $object["CategoryID"],
                ];
                $report = $reportModel->selectVisibleReports($where, [Model::OPT_LIMIT => 1]);
                if (!empty($report)) {
                    $report = $report[0];
                    // If report was already escalated there is no need to escalate it again, so we can log and skip this action
                    if ($report["status"] != ReportModel::STATUS_NEW) {
                        $this->addLog("info", "The report has been escalated previously.", [
                            "recordID" => $object["recordID"],
                            "recordType" => $object["recordType"],
                            "reportID" => $report["reportID"],
                            "status" => $report["status"],
                        ]);
                        $this->automationRuleDispatchesModel->updateDispatchStatus(
                            $this->getDispatchUUID(),
                            AutomationRuleDispatchesModel::STATUS_WARNING,
                            [],
                            "Skipping escalation as it was already escalated."
                        );
                        return true;
                    }
                    $object["reportID"] = $report["reportID"];
                } else {
                    // If the post was not reported, we need to create a new report and escalate it
                    $object["reportID"] = $this->createReport($object["recordID"], $object["recordType"]);
                }
            }
        }

        if (!($object["reportID"] ?? false) || !$this->checkIfReportNeedsEscalation($object["reportID"])) {
            if (!$longRunner) {
                $this->automationRuleDispatchesModel->updateDispatchStatus(
                    $this->getDispatchUUID(),
                    AutomationRuleDispatchesModel::STATUS_WARNING,
                    [],
                    "Skipped generating escalation."
                );
            }
            return true;
        }
        //Escalate the record
        $escalationID = $this->escalate($object, $longRunner);
        //Log the data
        $log = [
            "RecordType" => $object["recordType"],
            "RecordID" => $object["recordID"],
            "AutomationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
            "Data" => [
                "createEscalationAction" => [
                    "recordType" => $object["recordType"],
                    "recordID" => $object["recordID"],
                    "escalationID" => $escalationID,
                ],
            ],
            "DispatchUUID" => $this->getDispatchUUID(),
        ];
        $this->insertLogEntry($log);
        if (!$longRunner) {
            //This is a single record dispatch mark this as success
            $this->automationRuleDispatchesModel->updateDispatchStatus(
                $this->getDispatchUUID(),
                AutomationRuleDispatchesModel::STATUS_SUCCESS,
                ["affectedRecordCount" => 1],
                ""
            );
        }
        return true;
    }

    /**
     * Check if the report needs to be escalated
     *
     * @param int $reportID
     * @return bool
     * @throws ContainerException
     * @throws NotFoundException
     * @throws \Throwable
     */
    private function checkIfReportNeedsEscalation(int $reportID): bool
    {
        $sql = Gdn::sql();
        $result = $sql
            ->select([
                "r.reportID",
                "r.status as reportStatus",
                "r.recordID",
                "r.recordType",
                "e.escalationID",
                "e.status as escalationStatus",
            ])
            ->from("report r")
            ->leftJoin("escalation e", "e.recordID = r.recordID AND e.recordType = r.recordType ")
            ->where(["r.reportID" => $reportID, "e.status <>" => EscalationModel::STATUS_DONE])
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);
        if (empty($result["escalationID"])) {
            return true;
        }
        // There is an existing escalation in progress. Add the report to the escalation
        $escalationsModel = \Gdn::getContainer()->get(EscalationModel::class);
        $escalationsModel->escalateReportsForEscalation($result["escalationID"]);
        return false;
    }

    /**
     * Escalate a post or comment
     *
     * @param array $record
     * @param bool $longRunningJob
     */
    private function escalate(array $record, bool $longRunningJob = false): int
    {
        $escalationModel = Gdn::getContainer()->get(EscalationModel::class);
        $communityManagementRecordModel = Gdn::getContainer()->get(CommunityManagementRecordModel::class);
        $automationRule = $this->getAutomationRule();
        $actionValue = $automationRule["action"]["actionValue"];

        // Get the full record details from the community management record model
        $fullRecord = $communityManagementRecordModel->getRecord($record["recordType"], $record["recordID"]);
        if (!$fullRecord) {
            throw new \Garden\Web\Exception\NotFoundException($record["recordType"], [
                "recordType" => $record["recordType"],
                "recordID" => $record["recordID"],
            ]);
        }

        $escalationRecord = [
            "recordIsLive" => $actionValue["recordIsLive"] ?? false,
            "recordID" => $record["recordID"],
            "recordType" => $record["recordType"],
            "name" => $record["recordName"] ?? ($record["Name"] ?? ""),
            "placeRecordType" => "category",
            "placeRecordID" => $fullRecord["CategoryID"],
            "status" => EscalationModel::STATUS_OPEN,
            "recordUserID" => $fullRecord["InsertUserID"],
            "recordDateInserted" => $fullRecord["DateInserted"],
            "countComments" => 0,
        ];

        if (isset($actionValue["assignedModeratorID"])) {
            $escalationRecord["assignedUserID"] = $actionValue["assignedModeratorID"];
        }

        $exceptionData = [
            "recordID" => $record["recordID"],
            "recordType" => $record["recordType"],
        ];

        try {
            $escalationID = $escalationModel->insert($escalationRecord);

            // AIDEV-NOTE: Link the report to the escalation if we have a reportID
            if (isset($record["reportID"])) {
                $escalationModel->linkReportsToEscalation($escalationID, [$record["reportID"]]);
            }

            // AIDEV-NOTE: Create attachment for the escalation (same as API controller)
            // Use queryEscalations to get the escalation with all computed fields
            $escalations = $escalationModel->queryEscalations(["escalationID" => $escalationID]);
            $escalation = $escalations[0] ?? null;
            if ($escalation) {
                $attachmentProvider = Gdn::getContainer()->get(
                    \Vanilla\Forum\Models\VanillaEscalationAttachmentProvider::class
                );
                $attachmentProvider->createAttachmentFromEscalation($escalation);
            }
        } catch (\Throwable $e) {
            $exceptionData["error"] = $e->getMessage();
            $this->addLog("error", "Error occurred while escalating record", $exceptionData);
            if (!$longRunningJob) {
                $this->automationRuleDispatchesModel->updateDispatchStatus(
                    $this->getDispatchUUID(),
                    AutomationRuleDispatchesModel::STATUS_FAILED,
                    [],
                    $e->getMessage()
                );
            }
            throw $e;
        } catch (\Exception $e) {
            $exceptionData["error"] = $e->getMessage();
            $this->addLog("error", "Error occurred while escalating record", $exceptionData);
            if (!$longRunningJob) {
                $this->automationRuleDispatchesModel->updateDispatchStatus(
                    $this->getDispatchUUID(),
                    AutomationRuleDispatchesModel::STATUS_FAILED,
                    [],
                    $e->getMessage()
                );
            }
            throw $e;
        }

        $this->addLog("info", "Post escalated.", [
            "recordID" => $record["recordID"],
            "recordType" => $record["recordType"],
            "automationRuleID" => $this->getAutomationRuleID(),
        ]);

        return $escalationID;
    }

    /**
     * create a report for particular record
     *
     * @param int $recordID
     * @param string $recordType
     * @return int
     * @throws ContainerException
     * @throws NotFoundException
     * @throws \Garden\Web\Exception\ClientException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    private function createReport(int $recordID, string $recordType): int
    {
        if (!in_array($recordType, ["discussion", "comment"])) {
            throw new \InvalidArgumentException("Invalid record type");
        }

        $reportModel = Gdn::getContainer()->get(ReportModel::class);
        $communityManagementRecordModel = Gdn::getContainer()->get(CommunityManagementRecordModel::class);

        // Get the full record details from the community management record model
        $fullRecord = $communityManagementRecordModel->getRecord($recordType, $recordID);
        if (!$fullRecord) {
            throw new \Garden\Web\Exception\NotFoundException($recordType, [
                "recordType" => $recordType,
                "recordID" => $recordID,
            ]);
        }

        $report = [
            "recordID" => $recordID,
            "recordType" => $recordType,
            "reportReasonIDs" => [ReportReasonModel::INITIAL_REASON_AUTOMATION_RULE],
            "noteBody" => self::AUTOMATION_REPORT_BODY,
            "noteFormat" => Rich2Format::FORMAT_KEY,
            "placeRecordType" => "category",
            "placeRecordID" => $fullRecord["CategoryID"],
            "status" => ReportModel::STATUS_NEW,
            "recordUserID" => $fullRecord["InsertUserID"],
            "recordName" => $fullRecord["Name"],
            "recordBody" => $fullRecord["Body"],
            "recordFormat" => $fullRecord["Format"],
            "recordDateInserted" => $fullRecord["DateInserted"],
            "recordInsertIPAddress" => $fullRecord["InsertIPAddress"],
        ];

        $reportID = $reportModel->insert($report);
        return $reportID;
    }

    /**
     * Add log entry
     *
     * @param $type
     * @param $message
     * @param $data
     * @return void
     */
    private function addLog($type, $message, $data): void
    {
        $this->logger->$type(
            $message,
            array_merge(
                [
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                    Logger::FIELD_TAGS => ["automationRules", "createEscalationAction"],
                    "dispatchUUID" => $this->getDispatchUUID(),
                ],
                $data
            )
        );
    }

    /**
     * @inheritdoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $escalateValueSchema = Schema::parse([
            "assignedModeratorID?" => [
                "type" => "integer",
            ],
            "recordIsLive?" => [
                "type" => "boolean",
                "default" => false,
            ],
        ]);

        $escalationSchema = Schema::parse([
            "action:o" => [
                "actionType:s" => [
                    "enum" => [self::getType()],
                ],
                "actionValue:o" => $escalateValueSchema,
            ],
        ]);
        $schema->merge($escalationSchema);
    }

    /**
     * @inheritdoc
     */
    public function expandLogData(array $logData): string
    {
        $escalationModel = \Gdn::getContainer()->get(EscalationModel::class);
        $result = "<p></p><div><b>" . t("Log Data") . ":</b></div>";
        if (!empty($logData["createEscalationAction"])) {
            $escalationID = $logData["createEscalationAction"]["escalationID"];
            $rows = $escalationModel->queryEscalations(
                [
                    "escalationID" => $escalationID,
                ],
                [Model::OPT_LIMIT => 1]
            );
            $rowResult = $rows[0] ?? null;
            if ($rowResult !== null) {
                $result .= "<div id='escalation'><b>" . t("Escalated {$rowResult["recordType"]}") . ":</b>";
                $result .= $rowResult["name"] . "</div>";
            }
            $result .= "</div>";
        }

        return $result;
    }
}
