<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules\Actions;

use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Exception;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Gdn;
use Vanilla\Dashboard\AutomationRules\Models\UserInterface;
use Vanilla\Dashboard\AutomationRules\Triggers\ProfileFieldSelectionTrigger;
use Vanilla\Dashboard\AutomationRules\Triggers\TimeSinceUserRegistrationTrigger;
use Vanilla\Dashboard\AutomationRules\Triggers\UserEmailDomainTrigger;
use UserModel;
use Vanilla\AutomationRules\Actions\AutomationAction;
use Vanilla\AutomationRules\Actions\AutomationActionInterface;
use Vanilla\AutomationRules\Actions\EventActionInterface;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Logger;

/**
 * Automation rule action that assigns or removes a specific role to the user.
 */
class AddRemoveUserRoleAction extends AutomationAction implements
    AutomationActionInterface,
    UserInterface,
    EventActionInterface
{
    private int $userID;
    public string $affectedRecordType = "User";
    private UserModel $userModel;

    /**
     * @param int $automationRuleId
     * @param string $dispatchType
     * @param string|null $dispatchUUID
     * @throws NoResultsException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function __construct(
        int $automationRuleId,
        string $dispatchType = AutomationRuleDispatchesModel::TYPE_TRIGGERED,
        ?string $dispatchUUID = null
    ) {
        $this->userModel = Gdn::getContainer()->get(UserModel::class);
        parent::__construct($automationRuleId, $dispatchType, $dispatchUUID);
    }

    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "addRemoveRoleAction";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "Assign/remove a specific role to the user";
    }

    /**
     * @inheridoc
     */
    public static function getTriggers(): array
    {
        return [
            UserEmailDomainTrigger::getType(),
            ProfileFieldSelectionTrigger::getType(),
            TimeSinceUserRegistrationTrigger::getType(),
        ];
    }

    /**
     * @inheridoc
     */
    public static function getSchema(): Schema
    {
        $schema = [
            "type" => "object",
            "properties" => [
                "addRoleID" => [
                    "type" => "string",
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions("Assign Role", "Select a role to be assigned"),
                        new ApiFormChoices("/api/v2/roles", "/api/v2/roles/%s", "roleID", "name")
                    ),
                ],
                "removeRoleID" => [
                    "type" => "string",
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions("Remove Role (optional)", "Select a role to be removed"),
                        new ApiFormChoices("/api/v2/roles", "/api/v2/roles/%s", "roleID", "name")
                    ),
                ],
            ],
            "required" => ["addRoleID"],
        ];

        return Schema::parse($schema);
    }

    /**
     * @inheridoc
     */
    public function execute(): bool
    {
        $ruleApplied = false;
        $modifyRoleRule = $this->getAutomationRule();
        if ($modifyRoleRule["actionType"] !== self::getType()) {
            $this->logger->error("Invalid rule received  for " . self::getType(), [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                Logger::FIELD_TAGS => ["automation rules", "categoryFollowAction"],
                "automationRuleID" => $this->getAutomationRuleID(),
            ]);
            throw new Exception("Invalid Recipe received for " . self::getType());
        }
        $userId = $this->getUserID();
        if (empty($userId)) {
            throw new Exception("User ID is not set");
        }
        if ($this->getUserData()) {
            $attributes = ["affectedRecordType" => "User", "estimatedRecordCount" => 1];
            $logData = [
                "RecordType" => "UserRole",
                "RecordUserID" => $userId,
                "AutomationRuleRevisionID" => $modifyRoleRule["automationRuleRevisionID"],
            ];
            $userRoleIDs = [];
            // get current user Roles;
            $userRoles = $this->userModel->getRoles($userId)->resultArray();
            if (!empty($userRoles)) {
                $userRoleIDs = array_column($userRoles, "RoleID");
                $logData["Data"] = ["CurrentRoles" => $userRoleIDs];
            }
            $addRoleID = $modifyRoleRule["action"]["actionValue"]["addRoleID"];
            $removeRoleID = $modifyRoleRule["action"]["actionValue"]["removeRoleID"] ?? null;
            if (!empty($addRoleID) && !in_array($addRoleID, $userRoleIDs)) {
                $roleData = \RoleModel::roles($addRoleID);
                if (empty($roleData)) {
                    throw new Exception("Role provided to add doesn't exist");
                }
                $this->userModel->addRoles($userId, [$addRoleID], true);
                $logData["Data"]["RoleAdded"] = $addRoleID;
                $ruleApplied = true;
            }
            if (!empty($removeRoleID) && in_array($removeRoleID, $userRoleIDs)) {
                $this->userModel->removeRoles($userId, [$removeRoleID], true);
                $logData["Data"]["RoleRemoved"] = $removeRoleID;
                $ruleApplied = true;
            }

            if (
                !$this->dispatched &&
                $this->dispatchType === AutomationRuleDispatchesModel::TYPE_TRIGGERED &&
                $ruleApplied
            ) {
                $attributes["affectedRecordCount"] = 1;
                $this->logDispatched(AutomationRuleDispatchesModel::STATUS_SUCCESS, null, $attributes);
            }
            if ($ruleApplied) {
                $logData["DispatchUUID"] = $this->getDispatchUUID();
                $this->insertLogEntry($logData);
                // Action execution is done, update the date finished.
                if ($this->dispatchType === AutomationRuleDispatchesModel::TYPE_TRIGGERED) {
                    $this->automationRuleDispatchesModel->updateDateFinished($logData["DispatchUUID"]);
                }
            } else {
                $this->logger->info("skipped applying automation rule ", [
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                    Logger::FIELD_TAGS => ["automation rules", "AddRemoveUserRoleAction"],
                    "automationRuleID" => $this->getAutomationRuleID(),
                    "userID" => $userId,
                ]);
            }
        }
        return $ruleApplied;
    }

    /**
     * @inheridoc
     */
    public function executeLongRunner(array $actionValue, array $object): bool
    {
        $userID = $object["recordID"] ?? ($object["userID"] ?? $object["UserID"]);
        $this->setUserID($userID);
        return $this->execute();
    }

    /**
     * @inheridoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $modifyRoleSchema = Schema::parse([
            "action:o" => [
                "actionType:s" => [
                    "enum" => [self::getType()],
                ],
                "actionValue:o" => Schema::parse(["addRoleID:i", "removeRoleID:i?"]),
            ],
        ]);

        $schema
            ->merge($modifyRoleSchema)
            ->addValidator("action.actionValue", function ($roles, ValidationField $field) {
                $addRoleID = $roles["addRoleID"] ?? "";
                $removeRoleID = $roles["removeRoleID"] ?? "";
                if (empty($addRoleID)) {
                    $field->setName("action.actionValue.addRoleID")->addError("No role was selected to be added.", [
                        "code" => 403,
                    ]);
                    return Invalid::value();
                }
                if ($addRoleID == $removeRoleID) {
                    $field
                        ->setName("action.actionValue.removeRoleID")
                        ->addError("Remove Role should not be same as Add Role.", [
                            "code" => 403,
                        ]);
                    return Invalid::value();
                }
                $currentRoles = array_column(\RoleModel::roles(), "Name", "RoleID");
                if (empty($currentRoles[$addRoleID])) {
                    $field->setName("action.actionValue.addRoleID")->addError("Role provided to add doesn't exist.", [
                        "code" => 403,
                    ]);
                    return Invalid::value();
                }
                if (!empty($removeRoleID) && empty($currentRoles[$removeRoleID])) {
                    $field
                        ->setName("action.actionValue.removeRoleID")
                        ->addError("Role provided to remove doesn't exist.", [
                            "code" => 403,
                        ]);
                    return Invalid::value();
                }
            });
    }

    /**
     * Get userID
     *
     * @return int
     */
    public function getUserID(): int
    {
        return $this->userID;
    }

    /**
     * Set userID
     *
     * @param int $userID
     * @return void
     */
    public function setUserID(int $userID): void
    {
        $this->userID = $userID;
    }

    /**
     * Get user data
     *
     * @return array
     * @throws Exception
     */
    public function getUserData(): array
    {
        $userData = $this->userModel->getID($this->getUserID(), DATASET_TYPE_ARRAY);
        if (empty($userData)) {
            throw new Exception("User not found");
        }
        return $userData;
    }

    /**
     * @inheridoc
     */
    public function expandLogData(array $logData): string
    {
        $result = "<p></p><div><b>" . t("Log Data") . ":</b></div><div>";
        if (!empty($logData["CurrentRoles"])) {
            $result .= "<div id='current-roles'><b>" . t("Current Roles") . ": </b>";
            foreach ($logData["CurrentRoles"] as $index => $roleID) {
                $isLastOrOnlyItem =
                    count($logData["CurrentRoles"]) === 1 ||
                    (count($logData["CurrentRoles"]) > 1 && $index === count($logData["CurrentRoles"]) - 1);
                $roleData = \RoleModel::roles($roleID);
                $result .= $roleData["Name"] . ($isLastOrOnlyItem ? " " : ", ");
            }
            $result .= "</div>";
        }
        if (!empty($logData["RoleAdded"])) {
            $roleData = \RoleModel::roles($logData["RoleAdded"]);
            $result .= "<div id='role-added'><b>" . t("Role Added") . ": </b> " . $roleData["Name"] . "</div>";
        }
        if (!empty($logData["RoleRemoved"])) {
            $roleData = \RoleModel::roles($logData["RoleRemoved"]);
            $result .= "<div id='role-removed''><b>" . t("Role Removed") . ": </b> " . $roleData["Name"] . "</div>";
        }
        $result .= "</div>";
        return $result;
    }
}
