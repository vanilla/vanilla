<?php
/**
 * @author Sooraj Francis <sooraj.francis@higerlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\Schema\Schema;
use UserModel;
use Vanilla\Models\PipelineModel;

class RecordStatusLogModel extends PipelineModel
{
    private const TABLE_NAME = "recordStatusLog";
    public const RECORD_TYPE_DISCUSSION = "discussion";
    public const RECORD_TYPE_COMMENT = "comment";

    /** @var RecordStatusModel */
    private $recordStatusModel;

    /** @var UserModel */
    private $userModel;

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
        $this->recordStatusModel = $recordStatusModel;
        $this->userModel = $userModel;
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
            ->column("recordType", "varchar(100)", false, "index")
            ->column("recordID", "int", false, "key")
            ->column("reason", "text", null)
            ->set($explicit, $drop);
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
     * Expand status for discussions.
     *
     * @param array $rows Discussions rows.
     */
    public function expandDiscussionsStatuses(array &$rows): void
    {
        if (count($rows) === 0) {
            return;
        }

        reset($rows);
        $single = is_string(key($rows));

        if ($single) {
            $discussionIDs = [$rows["DiscussionID"]] ?? null;
        } else {
            $discussionIDs = array_column($rows, "DiscussionID");
        }
        // Query to the list of status records
        $statuses = $this->createSql()
            ->getWhere(
                self::TABLE_NAME,
                ["recordID" => $discussionIDs, "recordType" => self::RECORD_TYPE_DISCUSSION],
                "recordLogID",
                "desc"
            )
            ->resultArray();

        if (count($statuses) === 0) {
            return;
        }
        // Expand Record status insertUserID field into user record.
        $this->userModel->expandUsers($statuses, ["insertUserID"]);
        // Expand statusID into status name, and state.
        $this->recordStatusModel->expandStatuses($statuses, ["statusID"], ["name", "state"]);

        $populate = function (array &$row, array $statuses) {
            $discussionID = $row["DiscussionID"] ?? null;
            // Iterate over state records and only pull out latest (first record for each discussion when ordered in descending order.)
            foreach ($statuses as $status) {
                $stateDiscussionID = $status["recordID"] ?? null;
                if ($stateDiscussionID === $discussionID) {
                    $expandStatus = [
                        "statusID" => $status["statusID"],
                        "name" => $status["name"],
                        "state" => $status["state"],
                        "dateUpdated" => $status["dateInserted"],
                        "reasonUpdated" => $status["reason"],
                        "updatedUser" => $status["insertUser"],
                    ];
                    $row["status"] = $expandStatus;
                    break;
                }
            }
        };

        if ($single) {
            $populate($rows, $statuses);
        } else {
            foreach ($rows as &$row) {
                $populate($row, $statuses);
            }
        }
    }
}
