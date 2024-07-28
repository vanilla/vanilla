<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Exception;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Gdn;
use Gdn_Session;
use InvalidArgumentException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use UserModel;
use Vanilla\AutomationRules\Actions\AutomationAction;
use Vanilla\AutomationRules\Trigger\AutomationTrigger;
use Vanilla\Dashboard\AutomationRules\AutomationRuleService;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Logger;
use Vanilla\Models\LegacyModelUtils;
use Vanilla\Models\PipelineModel;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\SchemaFactory;
use Vanilla\Utility\ModelUtils;

/**
 * AutomationRuleModel
 */
class AutomationRuleModel extends PipelineModel
{
    use LoggerAwareTrait;
    const MAX_LIMIT = 150;
    const STATUS_ACTIVE = "active";
    const STATUS_INACTIVE = "inactive";
    const STATUS_DELETED = "deleted";
    const STATUS_OPTIONS = [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED];

    /**
     * AutomationRuleModel constructor.
     *
     * @param Gdn_Session $session
     * @param UserModel $userModel
     * @param AutomationRuleRevisionModel $automationRuleRevisionModel
     * @param AutomationRuleDispatchesModel $automationRuleDispatchesModel
     * @param LoggerInterface $logger
     * @param AutomationRuleService $automationRuleService
     */
    public function __construct(
        private Gdn_Session $session,
        private UserModel $userModel,
        private AutomationRuleRevisionModel $automationRuleRevisionModel,
        private AutomationRuleDispatchesModel $automationRuleDispatchesModel,
        LoggerInterface $logger
    ) {
        parent::__construct("automationRule");
        $this->setLogger($logger);
        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Structure for the automationRule table.
     *
     * @param \Gdn_Database $database
     * @param bool $explicit
     * @param bool $drop If true, and the table specified with $this->table() already exists,
     *  this method will drop the table before attempting to re-create it.
     * @return void
     * @throws Exception
     */
    public static function structure(\Gdn_Database $database, bool $explicit = false, bool $drop = false): void
    {
        $database
            ->structure()
            ->table("automationRule")
            ->primaryKey("automationRuleID")
            ->column("automationRuleRevisionID", "int")
            ->column("name", "varchar(100)")
            ->column("dateInserted", "datetime")
            ->column("insertUserID", "int")
            ->column("dateUpdated", "datetime", true)
            ->column("updateUserID", "int", true)
            ->column("dateLastRun", "datetime", true)
            ->column("status", ["active", "inactive", "deleted"], "inactive")
            ->set($explicit, $drop);
    }

    /**
     * Get the schema for the recipe model.
     *
     * @return Schema
     */
    public function getAutomationRuleSchema(): Schema
    {
        return Schema::parse([
            "automationRuleID:i",
            "automationRuleRevisionID:i",
            "name:s",
            "dateInserted:dt",
            "dateUpdated:dt",
            "dateLastRun:dt|n",
            "insertUserID:i",
            "updateUserID:i|n",
            "insertUser?" => SchemaFactory::get(UserFragmentSchema::class),
            "updateUser?" => SchemaFactory::get(UserFragmentSchema::class),
            "status:s",
            "recentDispatch:o|n?" => AutomationRuleDispatchesModel::getDispatchRuleSchema(),
            "trigger:o" => self::getTriggerSchema(),
            "action:o" => self::getActionSchema(),
        ]);
    }

    /**
     * @return AutomationRuleService
     */
    protected function automationRuleService(): AutomationRuleService
    {
        return Gdn::getContainer()->get(AutomationRuleService::class);
    }

    /**
     * Get trigger type schema
     *
     * @return Schema
     */
    public static function getTriggerSchema(): Schema
    {
        return Schema::parse(["triggerType:s", "triggerName:s", "triggerValue:o"]);
    }

    /**
     * Get action type schema
     *
     * @return Schema
     */
    public static function getActionSchema(): Schema
    {
        return Schema::parse(["actionType:s", "actionName:s", "actionValue:o|n"]);
    }

    /**
     * Return a list of automation rules based on query filters.
     *
     * @param array $query
     * @return array
     * @throws Exception
     */
    public function getAutomationRules(array $query = []): array
    {
        $where = [];
        $sql = $this->database->sql();
        $sql->select(["ar.*", "arr.triggerType", "arr.triggerValue", "arr.actionType", "arr.actionValue"])
            ->from("automationRule ar")
            ->join("automationRuleRevision arr", "arr.automationRuleRevisionID = ar.automationRuleRevisionID");
        foreach (
            ["automationRuleID" => "automationRuleID", "name" => "name", "status" => "status"]
            as $column => $key
        ) {
            if (!empty($query[$key])) {
                $where["ar.$column"] = $query[$key];
            }
        }
        if (empty($query["status"])) {
            $where["ar.status"] = [self::STATUS_ACTIVE, self::STATUS_INACTIVE];
        }
        if (!empty($query["sort"]) && is_array($query["sort"])) {
            foreach ($query["sort"] as $sort) {
                [$orderField, $orderDirection] = LegacyModelUtils::orderFieldDirection($sort);
                $sql->orderBy("ar." . $orderField, $orderDirection);
            }
        }
        $sql->where($where)->limit($query["limit"] ?? self::MAX_LIMIT);
        $result = $sql->get()->resultArray();
        $userExpands = [];
        if (ModelUtils::isExpandOption("insertUser", $query["expand"] ?? false)) {
            $userExpands[] = "insertUserID";
        }
        if (ModelUtils::isExpandOption("updateUser", $query["expand"] ?? false)) {
            $userExpands[] = "updateUserID";
        }
        if (count($userExpands) > 0) {
            $this->userModel->expandUsers($result, $userExpands);
        }
        return self::normalizeTriggerActionValues(
            $result,
            ModelUtils::isExpandOption("dispatchStatus", $query["expand"] ?? false)
        );
    }

    /**
     * Get an automation recipe by its ID.
     *
     * @param int $automationRuleID
     * @return array
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     */
    public function getAutomationRuleByID(int $automationRuleID): array
    {
        $result = $this->createSql()
            ->select(["ar.*", "arr.triggerType", "arr.triggerValue", "arr.actionType", "arr.actionValue"])
            ->from("automationRule ar")
            ->join("automationRuleRevision arr", "arr.automationRuleRevisionID = ar.automationRuleRevisionID")
            ->where(["ar.automationRuleID" => $automationRuleID])
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);
        if (empty($result)) {
            throw new NoResultsException("Automation rule not found.");
        }
        $automationRule = [$result];
        $automationRule = self::normalizeTriggerActionValues($automationRule);
        if (empty($automationRule)) {
            throw new NoResultsException("Automation rule not found.");
        }
        return array_shift($automationRule);
    }

    /**
     * Process trigger/action recipe to follow the schema
     *
     * @param array $automationRules
     * @param bool $includeRecentDispatch
     * @return array
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     */
    public static function normalizeTriggerActionValues(
        array &$automationRules,
        bool $includeRecentDispatch = false
    ): array {
        if (empty($automationRules)) {
            return $automationRules;
        }
        $automationRulesCount = count($automationRules);
        if ($includeRecentDispatch) {
            $automationRuleDispatchesModel = Gdn::getContainer()->get(AutomationRuleDispatchesModel::class);
            $automationRuleRevisionIDs = array_column($automationRules, "automationRuleRevisionID");
            $mostRecentDispatches = $automationRuleDispatchesModel->getRecentDispatchByAutomationRuleRevisionIDs(
                $automationRuleRevisionIDs
            );
        }
        $automationRuleService = Gdn::getContainer()->get(AutomationRuleService::class);
        $triggerTypes = $automationRuleService->getAutomationTriggers();
        $actionTypes = $automationRuleService->getAutomationActions();

        foreach ($automationRules as $key => &$automationRule) {
            $automationRule["trigger"] = [
                "triggerType" => $automationRule["triggerType"],
                "triggerValue" => is_string($automationRule["triggerValue"])
                    ? json_decode($automationRule["triggerValue"], true)
                    : $automationRule["triggerValue"],
            ];
            $triggerClass = $triggerTypes[$automationRule["triggerType"]] ?? "";
            if (is_a($triggerClass, AutomationTrigger::class, true)) {
                $automationRule["trigger"]["triggerName"] = $triggerClass::getName();
            } else {
                unset($automationRules[$key]); // remove the recipe if the trigger is not valid anymore
                continue;
            }
            $automationRule["action"] = [
                "actionType" => $automationRule["actionType"],
                "actionValue" => is_string($automationRule["actionValue"])
                    ? json_decode($automationRule["actionValue"], true)
                    : $automationRule["actionValue"],
            ];
            $actionClass = $actionTypes[$automationRule["actionType"]] ?? "";
            if (is_a($actionClass, AutomationAction::class, true)) {
                $automationRule["action"]["actionName"] = $actionClass::getName();
            } else {
                unset($automationRules[$key]); // remove the recipe if the action is not valid anymore
            }
            if (!empty($automationRule["attributes"])) {
                $automationRule["attributes"] = json_decode($automationRule["attributes"], true);
            }
            if ($includeRecentDispatch) {
                $automationRule["recentDispatch"] =
                    $mostRecentDispatches[$automationRule["automationRuleRevisionID"]] ?? [];
            }
        }
        return count($automationRules) === $automationRulesCount ? $automationRules : array_values($automationRules);
    }

    /**
     * Get the current max automation rule ID.
     *
     * @return int
     * @throws Exception
     */
    public function getMaxAutomationRuleID(): int
    {
        return (int) $this->createSql()
            ->select("automationRuleID", "max", "maxAutomationRuleID")
            ->get($this->getTable())
            ->column("maxAutomationRuleID")[0];
    }

    /**
     * Validate the given Recipe
     *
     * @param array $inputData
     * @param ValidationField $validationField
     * @return void
     */
    public function validateRecipe(array $inputData, ValidationField $validationField): void
    {
        $triggerType = $inputData["trigger"]["triggerType"] ?? null;
        $triggerValue = $inputData["trigger"]["triggerValue"] ?? null;
        $actionType = $inputData["action"]["actionType"] ?? null;
        $actionValue = $inputData["action"]["actionValue"] ?? null;
        $automationRuleService = $this->automationRuleService();
        $trigger = $automationRuleService->getAutomationTrigger($triggerType);
        // Trigger can be null if the it is not fully enabled, feature flag is turned off.
        if ($trigger === null || !in_array($automationRuleService->getAction($actionType), $trigger::getActions())) {
            $validationField->addError("$actionType is not a valid action type.", ["code" => 403]);
        }

        $automationRules = $this->getAutomationRulesByTriggerActionOrValues(
            $triggerType,
            $actionType,
            $triggerValue,
            $actionValue
        );
        if (!empty($automationRules)) {
            $validationField->addError("Recipe already exists", ["code" => 403]);
        }
        if ($this->getSiteTotalRecipes() >= self::MAX_LIMIT) {
            $validationField->addError(
                "You cannot add more than 150 automation rules. Delete some rules and try again.",
                ["code" => 403]
            );
        }
    }

    /**
     * Get recipes by trigger types, action types or their values
     *
     * @param string|null $triggerType
     * @param string|null $actionType
     * @param array|null $triggerValue
     * @param array|null $actionValue
     * @return array
     */
    public function getAutomationRulesByTriggerActionOrValues(
        ?string $triggerType = null,
        ?string $actionType = null,
        ?array $triggerValue = null,
        ?array $actionValue = null
    ): array {
        $result = $where = [];
        if (!empty($triggerType)) {
            $where["arr.triggerType"] = $triggerType;
        }
        if (!empty($actionType)) {
            $where["arr.actionType"] = $actionType;
        }
        if (!empty($triggerValue)) {
            $where["arr.triggerValue"] = json_encode($triggerValue, JSON_FORCE_OBJECT);
        }
        if (!empty($actionValue)) {
            $where["arr.actionValue"] = json_encode($actionValue, JSON_FORCE_OBJECT);
        }
        if (empty($where)) {
            return $result;
        }
        $where["ar.status <>"] = self::STATUS_DELETED;
        return $this->createSql()
            ->select(["ar.*", "arr.triggerType", "arr.triggerValue", "arr.actionType", "arr.actionValue"])
            ->from("automationRule ar")
            ->join("automationRuleRevision arr", "ar.automationRuleRevisionID = arr.automationRuleRevisionID")
            ->where($where)
            ->get()
            ->resultArray();
    }

    /**
     * Save the recipe
     *
     * @param array $automationRuleData
     * @param int|null $automationRuleID
     * @return int
     * @throws Exception
     */
    public function saveAutomationRule(array $automationRuleData, ?int $automationRuleID = null): int
    {
        $automationRule = [];
        // If we have a valid status then we will use it
        if (isset($automationRuleData["status"]) && in_array($automationRuleData["status"], self::STATUS_OPTIONS)) {
            $automationRule["status"] = $automationRuleData["status"];
        }
        if ($automationRuleID) {
            $automationRuleData["automationRuleID"] = $automationRuleID;
        } else {
            $maxAutomationRuleID = $this->getMaxAutomationRuleID();
            $automationRule["automationRuleID"] = $automationRuleData["automationRuleID"] = ++$maxAutomationRuleID;
            // By default, when creating a new recipe, we will set the status to inactive
            $automationRule["status"] = $automationRule["status"] ?? self::STATUS_INACTIVE;
        }
        $automationRule["name"] =
            $automationRuleData["name"] ?? "Untitled Recipe - {$automationRuleData["automationRuleID"]}";
        try {
            $this->database->beginTransaction();
            $automationRuleRevisionID = $this->automationRuleRevisionModel->insert($automationRuleData);
            $automationRule["automationRuleRevisionID"] = $automationRuleRevisionID;
            if (!empty($automationRuleID)) {
                $this->update($automationRule, ["automationRuleID" => $automationRuleID]);
            } else {
                $automationRuleID = $this->insert($automationRule);
            }
            $this->database->commitTransaction();
            if (($automationRule["status"] ?? "") === self::STATUS_ACTIVE) {
                $this->startAutomationRunByID($automationRuleID);
            }
            // Log the recipe status update, if the status is updated
            if (!empty($automationRuleData["status"]) && !empty($automationRule["status"] ?? "")) {
                $this->logger->info("Updated recipe status", [
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                    Logger::FIELD_TAGS => ["automation rules", "recipe"],
                    "automationRuleID" => $automationRuleID,
                    "status" => $automationRuleData["status"],
                    "updatedUserID" => $this->session->UserID,
                ]);
            }

            return $automationRuleID;
        } catch (Exception $e) {
            $this->database->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Get the total recipes for the site
     *
     * @return int
     */
    public function getSiteTotalRecipes(): int
    {
        $where = [
            "status" => [self::STATUS_ACTIVE, self::STATUS_INACTIVE],
        ];
        return $this->createSql()->getCount($this->getTable(), $where);
    }

    /**
     * Get the total recipes by their status
     *
     * @param string $status
     * @return int
     */
    public function getTotalRecipesByStatus(string $status): int
    {
        if (!in_array($status, self::STATUS_OPTIONS)) {
            throw new InvalidArgumentException("Invalid status");
        }
        $where = [
            "status" => $status,
        ];
        return $this->createSql()->getCount($this->getTable(), $where);
    }

    /**
     * Get total recipes by triggerType, actionType and status
     *
     * @param string $triggerType
     * @param string|null $actionType
     * @param string $status
     * @return int
     */
    public function getTotalAutomationRulesByTriggerActionStatus(
        string $triggerType,
        ?string $actionType = null,
        string $status = self::STATUS_ACTIVE
    ): int {
        if (!in_array($status, self::STATUS_OPTIONS)) {
            $status = self::STATUS_ACTIVE;
        }
        $where = [
            "arr.triggerType" => $triggerType,
            "ar.status" => $status,
        ];
        if (!empty($actionType)) {
            $where["arr.actionType"] = $actionType;
        }
        return $this->createSql()
            ->join("automationRuleRevision arr", "arr.automationRuleRevisionID = ar.automationRuleRevisionID")
            ->getCount($this->getTable() . " ar", $where);
    }

    /**
     * Get all active recipes by trigger and/or action type
     *
     * @param string $triggerType
     * @param string|null $actionType
     * @return array
     */
    public function getActiveAutomationRules(string $triggerType, ?string $actionType = null): array
    {
        $where = [
            "arr.triggerType" => $triggerType,
            "ar.status" => self::STATUS_ACTIVE,
        ];
        if (!empty($actionType)) {
            $where["arr.actionType"] = $actionType;
        }
        return $this->formatAutomationRuleValues(
            $this->createSql()
                ->select(["ar.*", "arr.triggerType", "arr.triggerValue", "arr.actionType", "arr.actionValue"])
                ->from("automationRule ar")
                ->join("automationRuleRevision arr", "arr.automationRuleRevisionID = ar.automationRuleRevisionID")
                ->where($where)
                ->get()
                ->resultArray()
        );
    }

    /**
     * Convert the automation rule values from json to array
     *
     * @param array $automationRules
     * @return array
     */
    private function formatAutomationRuleValues(array $automationRules): array
    {
        $list = true;
        if (!empty($automationRules) && !is_numeric(key($automationRules))) {
            $list = false;
            $automationRules = [$automationRules];
        }
        foreach ($automationRules as $key => $automationRule) {
            $automationRules[$key]["triggerValue"] =
                !empty($automationRule["triggerValue"]) && is_string($automationRule["triggerValue"])
                    ? json_decode($automationRule["triggerValue"], true)
                    : $automationRule["triggerValue"] ?? null;
            $automationRules[$key]["actionValue"] =
                !empty($automationRule["actionValue"]) && is_string($automationRule["actionValue"])
                    ? json_decode($automationRule["actionValue"], true)
                    : $automationRule["actionValue"] ?? null;
        }

        return $list ? $automationRules : array_shift($automationRules);
    }

    /**
     * Delete an automation rule
     *
     * @param int $automationRuleID
     * @return bool
     * @throws Exception
     */
    public function deleteAutomationRule(int $automationRuleID): bool
    {
        $result = $this->update(["status" => self::STATUS_DELETED], ["automationRuleID" => $automationRuleID]);
        $this->logger->info("Deleted recipe.", [
            Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
            Logger::FIELD_TAGS => ["automation rules", "recipe"],
            "automationRuleID" => $automationRuleID,
            "updatedUserID" => $this->session->UserID,
        ]);
        return $result;
    }

    /**
     * Update status by automation rule ID
     *
     * @param int $automationRuleID
     * @param string $status
     * @return bool
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     */
    public function updateAutomationRuleStatus(int $automationRuleID, string $status): bool
    {
        if (!in_array($status, self::STATUS_OPTIONS)) {
            throw new InvalidArgumentException("Invalid status");
        }
        // check if the recipe exists (throws exception if not found
        $automationRecipe = $this->getAutomationRuleByID($automationRuleID);
        // Don't update the status if the recipe is already deleted
        if ($automationRecipe["status"] === self::STATUS_DELETED) {
            throw new NoResultsException("Automation rule not found.");
        }
        //if the status is already the same, then don't update
        if ($automationRecipe["status"] === $status) {
            return true;
        }
        $result = $this->update(["status" => $status], ["automationRuleID" => $automationRuleID]);
        if ($status === self::STATUS_ACTIVE) {
            try {
                $this->startAutomationRunByID($automationRuleID);
            } catch (NotFoundException $e) {
                $this->logger->debug("Failed starting automation run", [
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                    Logger::FIELD_TAGS => ["automation rules"],
                    "automationRuleID" => $automationRuleID,
                    "automationRuleRevisionID" => $automationRecipe["automationRuleRevisionID"],
                    "Exception" => $e,
                ]);
            }
        }
        $this->logger->info("Updated recipe status", [
            Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
            Logger::FIELD_TAGS => ["automation rules", "recipe"],
            "automationRuleID" => $automationRuleID,
            "status" => $status,
            "updatedUserID" => $this->session->UserID,
        ]);
        return $result;
    }

    /**
     * Runs automation rule by Rule ID
     *
     * @param int $automationRuleID
     * @param string $dispatchType
     * @return void
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     */
    public function startAutomationRunByID(
        int $automationRuleID,
        string $dispatchType = AutomationRuleDispatchesModel::TYPE_INITIAL
    ): void {
        if (!in_array($dispatchType, AutomationRuleDispatchesModel::DISPATCH_TYPES)) {
            throw new InvalidArgumentException("Invalid dispatch type");
        }
        $automationRule = $this->getAutomationRuleByID($automationRuleID);
        // Don't run if the recipe is already deleted
        if ($automationRule["status"] === self::STATUS_DELETED) {
            throw new NoResultsException("Recipe not found.");
        }
        if ($automationRule["status"] === self::STATUS_ACTIVE) {
            try {
                if (!$this->automationRuleService()->isTriggerRegistered($automationRule["trigger"]["triggerType"])) {
                    throw new NotFoundException("Trigger not registered.");
                }
                $triggerClass = $this->automationRuleService()->getAutomationTrigger(
                    $automationRule["trigger"]["triggerType"]
                );
                if (!$this->automationRuleService()->isActionRegistered($automationRule["action"]["actionType"])) {
                    throw new NotFoundException("Action not registered.");
                }
                $action = $this->automationRuleService()->getAction($automationRule["action"]["actionType"]);
                if ($action) {
                    $actionClass = new $action($automationRuleID, $dispatchType);
                    $firstRun = $dispatchType !== AutomationRuleDispatchesModel::TYPE_TRIGGERED;
                    $actionClass->triggerLongRunnerRule($triggerClass, $firstRun);
                }
            } catch (NotFoundException $e) {
                // Plugin might be disabled.
                $this->logger->debug("Skipped executing the automation rule as it is not registered", [
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                    Logger::FIELD_TAGS => ["automation rules"],
                    "automationRuleID" => $automationRuleID,
                    "automationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
                    "Exception" => $e,
                ]);
            }
        }
    }

    /**
     * Update date last run by automation rule ID
     *
     * @param int $automationRuleID
     * @param string $dateLastRun
     * @return bool
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     */
    public function updateRuleDateLastRun(int $automationRuleID, string $dateLastRun): bool
    {
        // check if the recipe exists (throws exception if not found
        $automationRecipe = $this->getAutomationRuleByID($automationRuleID);
        // Don't update if the recipe is already deleted
        if ($automationRecipe["status"] === self::STATUS_DELETED) {
            throw new NoResultsException("Rule not found.");
        }
        if (!strtotime($dateLastRun)) {
            throw new InvalidArgumentException("Invalid date format");
        }

        $result = $this->createSql()
            ->update("automationRule")
            ->set("dateLastRun", $dateLastRun)
            ->where("automationRuleID", $automationRuleID)
            ->put()
            ->count();

        $this->logger->info("Updated rule dateLastRun", [
            Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
            Logger::FIELD_TAGS => ["automation rules", "rule"],
            "automationRuleID" => $automationRuleID,
            "dateLastRun" => $dateLastRun,
            "updatedUserID" => $this->session->UserID,
        ]);
        return (bool) $result;
    }

    /**
     * Get active timed automation rule IDs
     *
     * @return array
     */
    public function getActiveTimedAutomationRuleIDs(): array
    {
        $timedAutomationTypes = $this->automationRuleService()->getTimedAutomationTriggerTypes();
        $join = [
            [
                "tableName" => "automationRuleRevision",
                "on" => "automationRuleRevision.automationRuleRevisionID = automationRule.automationRuleRevisionID",
                "joinType" => "inner",
            ],
        ];
        $activeRules = $this->select(
            ["status" => self::STATUS_ACTIVE, "automationRuleRevision.triggerType" => $timedAutomationTypes],
            [self::OPT_SELECT => ["automationRule.automationRuleID"], self::OPT_JOINS => $join]
        );
        return !empty($activeRules) ? array_column($activeRules, "automationRuleID") : [];
    }
}
