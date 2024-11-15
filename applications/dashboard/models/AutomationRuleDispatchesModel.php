<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\Schema;
use Vanilla\CurrentTimeStamp;
use Garden\Schema\ValidationException;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Models\LegacyModelUtils;
use Vanilla\Models\PipelineModel;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\SchemaFactory;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;
use Gdn_Session;
use UserModel;
use LogModel;
use Gdn;
use Exception;
use InvalidArgumentException;
use Gdn_SQLDriver;

/**
 * AutomationRuleDispatchesModel
 */
class AutomationRuleDispatchesModel extends PipelineModel
{
    // Automatic scheduled automation rules that are triggered by a cron job or by fired events.
    const TYPE_TRIGGERED = "triggered";

    // User manually initiates an automation rule
    const TYPE_MANUAL = "manual";

    // Triggered each time an automation rule is enabled.
    const TYPE_INITIAL = "initial";
    const DISPATCH_TYPES = [self::TYPE_TRIGGERED, self::TYPE_MANUAL, self::TYPE_INITIAL];

    // The job has been queued to run the automation rule.
    const STATUS_QUEUED = "queued";

    //The job is currently running the automation rule.
    const STATUS_RUNNING = "running";

    // The job has successfully completed processing the automation rule.
    const STATUS_SUCCESS = "success";

    // The automation rule has resulted in an error and only partially completed.
    const STATUS_WARNING = "warning";

    // The automation rule has resulted in an error and failed totally.
    const STATUS_FAILED = "failed";

    const STATUS_OPTIONS = [
        self::STATUS_QUEUED,
        self::STATUS_RUNNING,
        self::STATUS_SUCCESS,
        self::STATUS_WARNING,
        self::STATUS_FAILED,
    ];

    private Gdn_Session $session;

    private UserModel $userModel;

    /**
     * AutomationRuleDispatchesModel constructor.
     *
     * @param Gdn_Session $session
     * @param UserModel $userModel
     */
    public function __construct(Gdn_Session $session, UserModel $userModel)
    {
        parent::__construct("automationRuleDispatches");

        $this->session = $session;
        $this->userModel = $userModel;
        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateDispatched"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["dispatchUserID"]);
        $this->addPipelineProcessor($userProcessor);
        $this->addPipelineProcessor(new JsonFieldProcessor(["attributes"]));
    }

    /**
     * Structure for the automationRuleDispatches table.
     *
     * @param \Gdn_Database $database
     * @param bool $explicit
     * @param bool $drop If true, and the table specified with $this->table() already exists,
     * this method will drop the table before attempting to re-create it.
     * @throws Exception
     */
    public static function structure(\Gdn_Database $database, bool $explicit = false, bool $drop = false): void
    {
        // A job with a manual dispatch and a jobID -> join to queuedJob
        //  jobStatus (pending|queued|progress) === locked
        $database
            ->structure()
            ->table("automationRuleDispatches")
            ->column("automationRuleDispatchUUID", "varchar(255)", false, "primary")
            ->column("automationRuleID", "int", false, "index.automationRule")
            ->column("automationRuleRevisionID", "int", false, "index.automationRule")
            ->column("dispatchType", ["triggered", "manual", "initial"])
            ->column("dispatchedJobID", "varchar(255)", true) // Whatever varchar size GDN_queuedJob.JobID uses
            ->column("attributes", "mediumtext", true) // JSON
            ->column("dateDispatched", "datetime")
            ->column("dateFinished", "datetime", true)
            ->column("dispatchUserID", "int")
            ->column("errorMessage", "mediumtext", true)
            ->column("status", "varchar(100)")
            ->set($explicit, $drop);
    }

    /**
     * Get the schema for the recipe model.
     *
     * @return Schema
     */
    public function getDispatchSchema(): Schema
    {
        return Schema::parse([
            "automationRuleDispatchUUID:s",
            "dateDispatched:dt",
            "dateFinished:dt?",
            "dispatchType:s",
            "dispatchStatus:s?",
            "queuedJobStatus:s?",
            "automationRule:o" => [
                "automationRuleID:i",
                "automationRuleRevisionID:i",
                "name:s",
                "dateInserted:dt?",
                "insertUserID:i",
                "dateUpdated:dt?",
                "updateUserID:i",
                "dateLastRun:dt?",
                "status:s",
            ],
            "affectedRows:o?",
            "insertUser:o?" => SchemaFactory::get(UserFragmentSchema::class, "UserFragment"),
            "updateUser:o?" => SchemaFactory::get(UserFragmentSchema::class, "UserFragment"),
            "dispatchUser:o?" => SchemaFactory::get(UserFragmentSchema::class, "UserFragment"),
            "trigger:o" => AutomationRuleModel::getTriggerSchema(),
            "action:o" => AutomationRuleModel::getActionSchema(),
        ]);
    }

    /**
     * Provide a short dispatch schema for the recipe endpoint.
     *
     * @return Schema
     */
    public static function getDispatchRuleSchema(): Schema
    {
        return Schema::parse([
            "automationRuleDispatchUUID:s?",
            "dispatchType:s?" => ["type" => "string", "enum" => self::DISPATCH_TYPES],
            "dispatchedJobID:s?",
            "dateDispatched:dt?",
            "dateFinished:dt?",
            "dispatchStatus:s?" => ["type" => "string", "enum" => self::STATUS_OPTIONS],
        ]);
    }

    /**
     * Get an automation rule dispatch by its UUID.
     *
     * @param string $automationRuleDispatchUUID
     * @return array
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     */
    public function getAutomationRuleDispatchByUUID(string $automationRuleDispatchUUID): array
    {
        $result = $this->createSql()
            ->select(["ard.*", "arr.triggerType", "arr.triggerValue", "arr.actionType", "arr.actionValue"])
            ->from("automationRuleDispatches ard")
            ->join("automationRule ar", "ard.automationRuleID = ar.automationRuleID")
            ->join("automationRuleRevision arr", "arr.automationRuleRevisionID = ard.automationRuleRevisionID")
            ->where(["ard.automationRuleDispatchUUID" => $automationRuleDispatchUUID])
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);
        if (empty($result)) {
            throw new NoResultsException("Dispatch not found.");
        }
        $dispatcher = [$result];
        $dispatcher = AutomationRuleModel::normalizeTriggerActionValues($dispatcher);
        return array_shift($dispatcher);
    }

    /**
     * Return a list of automation recipes based on query filters.
     *
     * @param array $query
     * @param array $extraWhere
     * @return array
     * @throws ContainerException
     * @throws NotFoundException|Exception
     */
    public function getAutomationRuleDispatches(array $query = [], array $extraWhere = []): array
    {
        // Default `ORDER BY dateDispatched DESC` if none is provided.
        if (empty($query["sort"])) {
            $query["sort"] = ["-dateDispatched"];
        }

        $where = [];
        $sql = $this->database->sql();
        $sql->select([
            "ard.*",
            "ard.status as dispatchStatus",
            "ard.dispatchUserID as dispatchUserID",
            "ar.* ",
            "qj.status as queuedJobStatus",
            "arr.triggerType",
            "arr.triggerValue",
            "arr.actionType",
            "arr.actionValue",
        ])
            ->from("automationRuleDispatches ard")
            ->join("automationRule ar", "ard.automationRuleID = ar.automationRuleID")
            ->join("automationRuleRevision arr", "ard.automationRuleRevisionID = arr.automationRuleRevisionID")
            ->join("queuedJob qj", "ard.dispatchedJobID = qj.queuedJobID", "left");

        // Add query filters
        if (!empty($query["automationRuleID"])) {
            $where["ard.automationRuleID"] = $query["automationRuleID"];
        }
        if (!empty($query["automationRuleRevisionID"])) {
            $where["ard.automationRuleRevisionID"] = $query["automationRuleRevisionID"];
        }
        if (!empty($query["automationRuleDispatchUUID"])) {
            $where["ard.automationRuleDispatchUUID"] = $query["automationRuleDispatchUUID"];
        }
        if (!empty($query["actionType"])) {
            $where["arr.actionType"] = $query["actionType"];
        }
        if (empty($query["dispatchStatus"]) && empty($query["lastRun"])) {
            // if no status is provided, provide only records that have estimatedRecordCount or failed status
            $sql->beginWhereGroup()
                ->where("=JSON_EXTRACT(ard.attributes, '$.estimatedRecordCount')>", 0, 0, false)
                ->orWhere("ard.status", self::STATUS_FAILED)
                ->endWhereGroup();
        } else {
            if ($query["lastRun"] ?? false) {
                // if its last run we need a different calculation
                $sql->beginWhereGroup()
                    ->where("ard.status", self::STATUS_SUCCESS)
                    ->orBeginWhereGroup()
                    ->where([
                        "ard.status" => self::STATUS_WARNING,
                        "ard.dispatchType" => self::TYPE_INITIAL,
                    ])
                    ->endWhereGroup()
                    ->orBeginWhereGroup()
                    ->where("ard.status", self::STATUS_WARNING)
                    ->where("=JSON_EXTRACT(ard.attributes, '$.affectedRecordCount')>", 0, 0, false)
                    ->endWhereGroup()
                    ->endWhereGroup();
            } else {
                // if status is provided, provide only records that have estimatedRecordCount
                $where["ard.status"] = $query["dispatchStatus"];
                $sql->where("=JSON_EXTRACT(ard.attributes, '$.estimatedRecordCount')>", 0, 0, false);
            }
        }

        if (!empty($query["sort"]) && is_array($query["sort"])) {
            foreach ($query["sort"] as $sort) {
                [$orderField, $orderDirection] = LegacyModelUtils::orderFieldDirection($sort);
                $sql->orderBy("ard." . $orderField, $orderDirection);
            }
        }

        $where = array_merge($where, $extraWhere);
        if (count($where) > 0) {
            $sql->where($where);
        }

        if (isset($query["page"]) && isset($query["limit"])) {
            [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);
            $sql->limit($limit, $offset);
        }

        $results = $sql->get()->resultArray();
        $userExpands = ["dispatchUserID"];
        if (ModelUtils::isExpandOption("insertUser", $query["expand"] ?? false)) {
            $userExpands[] = "insertUserID";
        }
        if (ModelUtils::isExpandOption("updateUser", $query["expand"] ?? false)) {
            $userExpands[] = "updateUserID";
        }
        if (count($userExpands) > 0) {
            $this->userModel->expandUsers($results, $userExpands);
        }

        $results = AutomationRuleModel::normalizeTriggerActionValues($results);
        $results = $this->assembleAffectedRecordCounts($results);
        return $this->nestAutomationRuleValues($results);
    }

    /**
     * Return the count of automation recipes based on query filters.
     *
     * @param array $query
     * @param array $extraWhere
     * @return int
     * @throws ContainerException
     * @throws NotFoundException|Exception
     */
    public function getCountAutomationRuleDispatches(array $query = [], array $extraWhere = []): int
    {
        $where = [];
        $sql = $this->database->sql();
        $sql->select(["COUNT(ard.automationRuleDispatchUUID) as count"])
            ->from("automationRuleDispatches ard")
            ->join("automationRule ar", "ard.automationRuleID = ar.automationRuleID");
        $joinArr = false;
        // Add query filters
        if (!empty($query["automationRuleID"])) {
            $where["arr.automationRuleID"] = $query["automationRuleID"];
            $joinArr = true;
        }
        if (!empty($query["automationRuleDispatchUUID"])) {
            $where["ard.automationRuleDispatchUUID"] = $query["automationRuleDispatchUUID"];
        }
        if (!empty($query["actionType"])) {
            $where["arr.actionType"] = $query["actionType"];
            $joinArr = true;
        }
        if (empty($query["dispatchStatus"])) {
            // if no status is provided, provide only records that have estimatedRecordCount or failed status
            $sql->beginWhereGroup()
                ->where("=JSON_EXTRACT(ard.attributes, '$.estimatedRecordCount')>", 0, 0, false)
                ->orWhere("ard.status", self::STATUS_FAILED)
                ->endWhereGroup();
        } else {
            // if status is provided, provide only records that have estimatedRecordCount
            $where["ard.status"] = $query["dispatchStatus"];
            $sql->where("=JSON_EXTRACT(ard.attributes, '$.estimatedRecordCount')>", 0, 0, false);
        }

        // If we need the `automationRuleRevision` table, join it.
        if ($joinArr) {
            $sql->join("automationRuleRevision arr", "ard.automationRuleRevisionID = arr.automationRuleRevisionID");
        }

        $where = array_merge($where, $extraWhere);
        if (count($where) > 0) {
            $sql->where($where);
        }
        $results = $sql->get()->firstRow(DATASET_TYPE_ARRAY);
        return $results["count"];
    }

    /**
     * Assemble affected record counts from the attributes data.
     *
     * @param array $results
     * @return array
     */
    private function assembleAffectedRecordCounts(array $results): array
    {
        $logModel = Gdn::getContainer()->get(LogModel::class);
        foreach ($results as &$result) {
            $result["affectedRows"] = [];
            if (isset($result["attributes"])) {
                $affectedRecordType = $result["attributes"]["affectedRecordType"] === "User" ? "user" : "post";

                $result["affectedRows"] = [
                    $affectedRecordType => $result["attributes"]["affectedRecordCount"] ?? 0,
                ];
            }
        }

        return $results;
    }

    /**
     * Generate dispatch UUID based on the data.
     *
     * @param array $data
     * @param bool $isUnique
     * @return string
     */
    public static function generateDispatchUUID(array $data, bool $isUnique = true): string
    {
        $data = json_encode($data);
        if ($isUnique) {
            $data .= microtime(true);
        }
        return sha1($data);
    }

    /**
     * @return Gdn_SQLDriver
     */
    public function getSql(): Gdn_SQLDriver
    {
        return $this->createSql();
    }
    /**
     * Update status or attribute by automation rule dispatch UUID
     *
     * @param string $automationRuleDispatchUUID
     * @param string $status
     * @param array $attribute
     * @param string|null $errorMessage
     * @return bool
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     */
    public function updateDispatchStatus(
        string $automationRuleDispatchUUID,
        string $status = "",
        array $attribute = [],
        ?string $errorMessage = null
    ): bool {
        if (!in_array($status, array_merge(self::STATUS_OPTIONS, [""]))) {
            throw new InvalidArgumentException("Invalid status");
        }
        // check if the recipe exists (throws exception if not found
        $dispatch = $this->getAutomationRuleDispatchByUUID($automationRuleDispatchUUID);

        //if the status is already the same, then don't update
        $update = [];
        if ($attribute != []) {
            if ($dispatch["attributes"] != null) {
                foreach ($dispatch["attributes"] as $key => $value) {
                    //in case of failed records, merge the failed records
                    if ($key === "failedRecords" && !empty($attribute[$key])) {
                        $attribute[$key] = array_merge($dispatch["attributes"][$key], $attribute[$key]);
                    }
                    if (empty($attribute[$key])) {
                        $attribute[$key] = $dispatch["attributes"][$key];
                    }
                }
            }
            $update["attributes"] = $attribute;
        }
        // If there are error Messages and the job finished, mark it with a warning.
        if (!empty($errorMessage) && $status === self::STATUS_SUCCESS) {
            $status = self::STATUS_WARNING;
        }
        if ($dispatch["status"] != $status) {
            $update["status"] = $status;
        }
        if (!empty($errorMessage)) {
            $update["errorMessage"] = !empty($dispatch["errorMessage"])
                ? $dispatch["errorMessage"] . ", " . $errorMessage
                : $errorMessage;
        }
        if (in_array($status, [self::STATUS_SUCCESS, self::STATUS_WARNING])) {
            $update["dateFinished"] = CurrentTimeStamp::getMySQL();
            $rule = Gdn::getContainer()->get(AutomationRuleModel::class);
            $rule->updateRuleDateLastRun($dispatch["automationRuleID"], $update["dateFinished"]);
        }

        return $this->update($update, ["automationRuleDispatchUUID" => $automationRuleDispatchUUID]);
    }

    /**
     * Update the date finished for a dispatch
     *
     * @param string $automationRuleDispatchUUID
     * @return bool
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function updateDateFinished(string $automationRuleDispatchUUID): bool
    {
        $automationRuleModel = Gdn::getContainer()->get(AutomationRuleModel::class);
        $currentTime = CurrentTimeStamp::getMySQL();
        $result = $this->update(
            ["dateFinished" => $currentTime],
            ["automationRuleDispatchUUID" => $automationRuleDispatchUUID]
        );
        if ($result) {
            $dispatch = $this->selectSingle(
                ["automationRuleDispatchUUID" => $automationRuleDispatchUUID],
                [self::OPT_SELECT => ["automationRuleID"]]
            );
            $automationRuleModel->updateRuleDateLastRun($dispatch["automationRuleID"], $currentTime);
        }
        return $result;
    }

    /**
     * Return the results with a nested automation rule values.
     *
     * @param array $results
     * @return array
     */
    private function nestAutomationRuleValues(array $results): array
    {
        $nestedFields = [
            "automationRuleID",
            "automationRuleRevisionID",
            "name",
            "dateInserted",
            "insertUserID",
            "dateUpdated",
            "updateUserID",
            "dateLastRun",
            "status",
        ];
        foreach ($results as &$result) {
            $nestedValues = ArrayUtils::pluck($result, $nestedFields);
            $result = array_diff_key($result, array_flip($nestedFields));
            $result["automationRule"] = $nestedValues;
        }

        return $results;
    }

    /**
     * Check if a particular automation rule has been applied to a user
     *
     * @param int $automationRuleRevisionID
     * @param int $userID
     * @return bool
     * @throws Exception
     */
    public function checkIfRuleExecutedForUser(int $automationRuleRevisionID, int $userID): bool
    {
        $sql = $this->database->sql();
        $result = $sql->getCount("Log", [
            "AutomationRuleRevisionID" => $automationRuleRevisionID,
            "RecordUserID" => $userID,
            "Operation" => "Automation",
        ]);
        return (bool) $result;
    }

    /**
     * Get the most recent dispatch record for a particular automation rule.
     *
     * @param array $automationRuleRevisionIDs
     * @return array
     * @throws Exception
     */
    public function getRecentDispatchByAutomationRuleRevisionIDs(array $automationRuleRevisionIDs): array
    {
        if (empty($automationRuleRevisionIDs)) {
            return [];
        }
        $innerQuery = $this->createSql()
            ->select("dateDispatched", "MAX", "dateDispatched")
            ->select("automationRuleRevisionID")
            ->from("automationRuleDispatches")
            ->where(["automationRuleRevisionID" => $automationRuleRevisionIDs])
            ->groupBy("automationRuleRevisionID")
            ->orderBy("automationRuleRevisionID");

        $result = $this->createSql()
            ->select("ard.*, ard.status as dispatchStatus")
            ->from("({$innerQuery->getSelect(true)}) as rd")
            ->leftJoin(
                "automationRuleDispatches ard",
                "ard.automationRuleRevisionID=rd.automationRuleRevisionID and ard.dateDispatched=rd.dateDispatched"
            )
            ->orderBy("ard.automationRuleRevisionID")
            ->get()
            ->resultArray();

        if (!empty($result)) {
            return array_column($result, null, "automationRuleRevisionID");
        }
        return $result;
    }
}
