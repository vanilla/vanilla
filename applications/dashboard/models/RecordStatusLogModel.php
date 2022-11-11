<?php
/**
 * @author Sooraj Francis <sooraj.francis@higerlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\Schema\Schema;
use UserModel;
use Vanilla\CurrentTimeStamp;
use Vanilla\Models\PipelineModel;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;

class RecordStatusLogModel extends PipelineModel
{
    private const TABLE_NAME = "recordStatusLog";
    public const RECORD_TYPE_DISCUSSION = "discussion";
    public const RECORD_TYPE_COMMENT = "comment";

    /** @var UserModel */
    private $userModel;

    /** @var RecordStatusModel */
    private $recordStatusModel;

    /**
     * Class constructor.
     *
     * @param RecordStatusModel $recordStatusModel
     * @param UserModel $userModel,
     */
    public function __construct(RecordStatusModel $recordStatusModel, UserModel $userModel)
    {
        parent::__construct(self::TABLE_NAME);
        $this->setPrimaryKey("recordLogID");
        $this->userModel = $userModel;
        $this->recordStatusModel = $recordStatusModel;
    }

    /**
     * Structure the recordStatusLog table schema.
     *
     * @param \Gdn_Database $database Database handle
     * @param bool $explicit Optional, true to remove any columns that are not specified here,
     * false to retain those columns. Default false.
     * @param bool $drop Optional, true to drop table if it already exists,
     * false to retain table if it already exists. Default false.
     */
    public static function structure(\Gdn_Database $database, bool $explicit = false, bool $drop = false): void
    {
        $database
            ->structure()
            ->table("recordStatusLog")
            ->primaryKey("recordLogID")
            ->column("statusID", "int", false, "index")
            ->column("insertUserID", "int")
            ->column("dateInserted", "datetime")
            ->column("recordType", "varchar(100)", false, "index.record")
            ->column("recordID", "int", false, "index.record")
            ->column("reason", "text", null)
            ->set($explicit, $drop);

        // Old index is supplanted by the `IX_recordStatusLog_record` index.
        $database
            ->structure()
            ->table("recordStatusLog")
            ->dropIndexIfExists("IX_recordStatusLog_recordType");
        $database
            ->structure()
            ->table("recordStatusLog")
            ->dropIndexIfExists("FK_recordStatusLog_recordID");
    }

    /**
     * @return Schema
     */
    public function getSchema(): Schema
    {
        $schema = Schema::parse([
            "recordLogID" => ["type" => "integer"],
            "statusID" => ["type" => "integer"],
            "insertUserID" => ["type" => "integer"],
            "dateInserted" => ["format" => "date-time"],
            "recordType" => ["type" => "string"],
            "recordID" => ["type" => "integer"],
            "reason" => [
                "type" => "string",
                "allowNull" => true,
            ],
        ]);

        return $schema;
    }

    /**
     * Get the count of record status log for a record id.
     *
     * @param int $recordID .
     * @param string $recordType
     *
     * @return int count of log data
     */
    public function getRecordStatusLogCount(int $recordID, string $recordType = "discussion")
    {
        $alias = "logCount";
        $where = [
            "recordID" => $recordID,
            "recordType" => $recordType,
        ];

        return $this->createSql()
            ->select($this->getPrimaryKey(), "count", $alias)
            ->getWhere(self::TABLE_NAME, $where)
            ->firstRow()->$alias;
    }

    /**
     * Get most recent record Status log
     *
     * @param string $recordType record status type.
     * @param array $recordIDs record IDs
     * @param bool $isInternal is the status internal or not.
     *
     * @return array
     */
    private function selectMostRecentStatusLogForRecords(string $recordType, array $recordIDs, bool $isInternal): array
    {
        $innerQuery = $this->createSql()
            ->select("recordLogID", "MAX", "recordLogID")
            ->from("recordStatusLog rsl3")
            ->join("recordStatus as rs", "rsl3.statusID = rs.statusID")
            ->where(["rs.isInternal" => $isInternal])
            ->groupBy(["rsl3.recordType", "rsl3.recordID"]);

        $innerQueryString = $innerQuery->getSelect();

        $query = $this->createSql()
            ->from("recordStatusLog as rsl")
            ->select("rsl.*")
            ->join("($innerQueryString) as rsl2", "rsl.recordLogID = rsl2.recordLogID")
            ->where(["rsl.recordID" => $recordIDs, "rsl.recordType" => $recordType]);

        $query->namedParameters(array_merge($query->namedParameters(), $innerQuery->namedParameters()));
        $statusLogs = $query->get()->resultArray();

        $statusesLogsByRecordID = [];
        foreach ($statusLogs as $statusLog) {
            $statusesLogsByRecordID[$statusLog["recordID"]] = [
                "recordLogID" => $statusLog["recordLogID"],
                "dateUpdated" => $statusLog["dateInserted"],
                "reasonUpdated" => $statusLog["reason"],
                "updateUserID" => $statusLog["insertUserID"],
            ];
        }
        return $statusesLogsByRecordID;
    }

    /**
     * Expand status for discussions.
     *
     * @param array $rows Discussions rows.
     */
    public function expandStatusLogs(array &$rows, string $recordType, string $recordIDField): void
    {
        ModelUtils::leftJoin($rows, [$recordIDField => "status.log"], function (array $recordIDs) use ($recordType) {
            return $this->selectMostRecentStatusLogForRecords($recordType, $recordIDs, false);
        });
        $this->userModel->expandUsers($rows, ["status.log.updateUserID" => "status.log.updateUser"]);

        if (\Gdn::session()->checkPermission("staff.allow")) {
            ModelUtils::leftJoin($rows, [$recordIDField => "internalStatus.log"], function (array $recordIDs) use (
                $recordType
            ) {
                return $this->selectMostRecentStatusLogForRecords($recordType, $recordIDs, true);
            });
            $this->userModel->expandUsers($rows, [
                "internalStatus.log.updateUserID" => "internalStatus.log.updateUser",
            ]);
        }
    }

    /**
     * Gets a list of Record Status logs, and validated what the user can be,based on isInternal status type.
     *
     * @param array $where select where clause.
     * @param array $options select options.
     * @return array List of recordStatus logs.
     */
    public function getAllowedStatusLogs(array $where, array $options): array
    {
        $logs = $this->select($where, $options);
        if (!\Gdn::session()->checkPermission("staff.allow")) {
            // Get a list of all external statuses, active and inactive.
            $externalStatuses = $this->recordStatusModel->getStatuses(true, false);
            $returnLog = [];
            foreach ($logs as $log) {
                // External status, keep.
                if (array_key_exists($log["statusID"], $externalStatuses)) {
                    $returnLog[] = $log;
                }
            }
            $logs = $returnLog;
        }
        return $logs;
    }
}
