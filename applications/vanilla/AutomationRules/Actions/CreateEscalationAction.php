<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Actions;

use CommentModel;
use DiscussionModel;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\Schema;
use Gdn;
use RoleModel;
use UserModel;
use Vanilla\Dashboard\AutomationRules\Models\EscalationRuleDataType;
use Vanilla\Dashboard\AutomationRules\Models\PostInterface;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Forum\Models\CommunityManagement\CommunityManagementRecordModel;
use Vanilla\Forum\Models\CommunityManagement\EscalationModel;
use Vanilla\Forum\Models\VanillaEscalationAttachmentProvider;
use Vanilla\Logger;
use Vanilla\Models\Model;

class CreateEscalationAction extends AutomationAction implements PostInterface
{
    public string $affectedRecordType = "Discussion";

    private array $postRecord;
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "createEscalationAction";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "Create Escalation";
    }

    /**
     * @inheridoc
     */
    public static function getContentType(): string
    {
        return "posts";
    }

    /**
     * @inheridoc
     */
    public static function canAddAction(): bool
    {
        return FeatureFlagHelper::featureEnabled("CommunityManagement");
    }

    /**
     * @inheridoc
     */
    public function setPostRecord(array $postRecord): void
    {
        $this->postRecord = $postRecord;
    }

    /**
     * @inheridoc
     */
    public function getPostRecord(): array
    {
        return $this->postRecord;
    }

    /**
     * @inheridoc
     */
    public static function getSchema(): Schema
    {
        $roleModel = Gdn::getContainer()->get(RoleModel::class);
        $userModel = Gdn::getContainer()->get(UserModel::class);
        $roles = $roleModel->getByPermission("Garden.Moderation.Manage")->resultArray();
        $users = $userModel->search(["roleIDs" => array_column($roles, "RoleID")], "name", "asc")->resultArray();
        $userEnum = array_column($users, "Name", "UserID");
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
                "enum" => array_keys($userEnum),
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Assign Moderator", "Select what moderator escalations should be assigned to"),
                    new StaticFormChoices($userEnum)
                ),
            ],
        ];

        return Schema::parse($schema);
    }

    /**
     * @inheridoc
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
        $this->setPostRecord($object);
        return $this->execute();
    }

    /**
     * @inheridoc
     */
    public function execute(): bool
    {
        $object = $this->getPostRecord();
        $recordID = $object["recordID"];
        $recordType = $object["recordType"];
        $createEscalationRule = $this->getAutomationRule();
        $escalationsModel = \Gdn::getContainer()->get(EscalationModel::class);
        // Make sure the post is not already escalated
        $escalation =
            $escalationsModel->queryEscalations([
                "recordType" => $recordType,
                "recordID" => $recordID,
            ])[0] ?? null;
        $escalationID = $escalation["escalationID"] ?? null;
        $attributes = [
            "affectedRecordType" => $recordType,
            "estimatedRecordCount" => 1,
            "affectedRecordCount" => 1,
        ];
        // Mark the dispatch as running
        if (!$this->dispatched) {
            $this->logDispatched(AutomationRuleDispatchesModel::STATUS_RUNNING, null, $attributes);
        }
        try {
            if ($escalation === null) {
                $record = [];
                if ($recordType == "discussion") {
                    $discussionModel = \Gdn::getContainer()->get(DiscussionModel::class);
                    $record = $discussionModel->getID($recordID, DATASET_TYPE_ARRAY);
                } elseif ($recordType == "comment") {
                    $commentModel = \Gdn::getContainer()->get(CommentModel::class);
                    $record = $commentModel->getID($recordID, DATASET_TYPE_ARRAY);
                }
                $escalationID = $escalationsModel->insert([
                    "name" => $object["recordName"],
                    "status" => EscalationModel::STATUS_OPEN,
                    "assignedUserID" => $createEscalationRule["action"]["actionValue"]["assignedModeratorID"] ?? null,
                    "countComments" => 0,
                    "recordType" => $recordType,
                    "recordID" => $recordID,
                    "recordUserID" => $record["InsertUserID"] ?? null,
                    "recordDateInserted" => $record["DateInserted"] ?? null,
                    "placeRecordType" => "category",
                    "placeRecordID" => $object["placeRecordID"] ?? 0,
                    "insertUserID" => GDN::userModel()->getSystemUserID(),
                ]);
            }
            $escalationsModel->escalateReportsForEscalation($escalationID);
            $recordIsLive = $createEscalationRule["action"]["actionValue"]["recordIsLive"];
            if (!$recordIsLive) {
                $communityManagementRecordModel = \Gdn::getContainer()->get(CommunityManagementRecordModel::class);
                $communityManagementRecordModel->removeRecord($recordID, $recordType);
            }

            $rows = $escalationsModel->queryEscalations(
                [
                    "escalationID" => $escalationID,
                ],
                [Model::OPT_LIMIT => 1]
            );
            $result = $rows[0] ?? null;
            $attachmentProvider = \Gdn::getContainer()->get(VanillaEscalationAttachmentProvider::class);
            $attachmentProvider->createAttachmentFromEscalation($result);
        } catch (\Exception $e) {
            // Log the error
            $this->logger->error("Error occurred while updating record Status", [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                Logger::FIELD_TAGS => ["automationRules", "changeIdeationStatusAction"],
                "error" => $e->getMessage(),
                "recordID" => $recordID,
                "recordType" => $recordType,
                "automationRuleID" => $createEscalationRule["automationRuleID"],
                "automationRuleRevisionID" => $createEscalationRule["automationRuleRevisionID"],
            ]);
            // Mark the dispatch as failed
            if ($this->dispatchType == AutomationRuleDispatchesModel::TYPE_TRIGGERED) {
                $attributes["affectedRecordCount"] = 0;
                $this->automationRuleDispatchesModel->updateDispatchStatus(
                    $this->getDispatchUUID(),
                    AutomationRuleDispatchesModel::STATUS_FAILED,
                    $attributes,
                    $e->getMessage()
                );
            }
            return false;
        }
        $this->logger->info("Post escalated.", [
            Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
            Logger::FIELD_TAGS => ["automation rules", "createEscalationAction"],
            "recordID" => $recordID,
            "recordType" => $recordType,
            "automationRuleID" => $this->getAutomationRuleID(),
            "dispatchUUID" => $this->getDispatchUUID(),
        ]);

        $logData = [
            "DispatchUUID" => $this->getDispatchUUID(),
        ];
        $log = [
            "RecordType" => $recordType,
            "RecordID" => $recordID,
            "AutomationRuleRevisionID" => $createEscalationRule["automationRuleRevisionID"],
            "Data" => [
                "createEscalationAction" => [
                    "recordType" => $recordType,
                    "recordID" => $recordID,
                    "escalationID" => $escalationID,
                ],
            ],
            "DispatchUUID" => $this->getDispatchUUID(),
        ];
        $this->insertLogEntry($log);

        if ($this->dispatchType === AutomationRuleDispatchesModel::TYPE_TRIGGERED) {
            $this->automationRuleDispatchesModel->updateDispatchStatus(
                $this->getDispatchUUID(),
                AutomationRuleDispatchesModel::STATUS_SUCCESS,
                $attributes
            );
            $this->automationRuleDispatchesModel->updateDateFinished($logData["DispatchUUID"]);
        }
        return true;
    }

    /**
     * @inheridoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $escalateValueSchema = Schema::parse([
            "assignedModeratorID?" => [
                "type" => "integer",
            ],
            "recordIsLive?" => [
                "type" => "boolean",
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
     * @inheritDoc
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
            $result = $rows[0] ?? null;
            foreach ($result as $index => $tag) {
                $isLastOrOnlyItem = count($result) === 1 || (count($result) > 1 && $index === count($result) - 1);
                $result .= $tag["Name"] . ($isLastOrOnlyItem ? " " : ", ");
            }
            $result .= "</div>";
        }

        return $result;
    }
}
