<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Gdn;
use Gdn_Cache;
use Gdn_DataSet;
use Traversable;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentIPAddressProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Models\PipelineModel;
use Vanilla\Utility\ArrayUtils;

/**
 * Model for managing the recordStatus table.
 */
class RecordStatusModel extends PipelineModel
{
    const RECORD_STATUS_ID_CACHE_KEY = "recordStatusIDs";

    //region Properties
    private const TABLE_NAME = "recordStatus";
    /**
     * @var array An array representation of all the record statuses in the database, indexed by StatusID.
     */
    protected $recordStatuses;

    public const DISCUSSION_STATUS_NONE = 0;
    public const DISCUSSION_STATUS_UNANSWERED = 1;
    public const DISCUSSION_STATUS_ANSWERED = 2;
    public const DISCUSSION_STATUS_ACCEPTED = 3;
    public const DISCUSSION_STATUS_REJECTED = 4;
    public const COMMENT_STATUS_ACCEPTED = 5;
    public const COMMENT_STATUS_REJECTED = 6;
    public const DISCUSSION_STATUS_UNRESOLVED = 7;
    public const DISCUSSION_STATUS_RESOLVED = 8;

    /** @var array $systemDefinedIDs */
    public static $systemDefinedIDs = [
        RecordStatusModel::DISCUSSION_STATUS_NONE,
        RecordStatusModel::DISCUSSION_STATUS_UNANSWERED,
        RecordStatusModel::DISCUSSION_STATUS_ANSWERED,
        RecordStatusModel::DISCUSSION_STATUS_ACCEPTED,
        RecordStatusModel::DISCUSSION_STATUS_REJECTED,
        RecordStatusModel::COMMENT_STATUS_ACCEPTED,
        RecordStatusModel::COMMENT_STATUS_REJECTED,
        RecordStatusModel::DISCUSSION_STATUS_UNRESOLVED,
        RecordStatusModel::DISCUSSION_STATUS_RESOLVED,
    ];

    /** @var array */
    private const DEFAULT_DISCUSSION_STATUS = [
        "statusID" => RecordStatusModel::DISCUSSION_STATUS_NONE,
        "name" => "None",
        "state" => "open",
        "recordType" => "discussion",
        "recordSubtype" => "discussion",
        "isDefault" => 1,
        "isSystem" => 1,
    ];

    /** @var array */
    private const DEFAULT_QUESTION_UNANSWERED_STATUS = [
        "statusID" => RecordStatusModel::DISCUSSION_STATUS_UNANSWERED,
        "name" => "Unanswered",
        "state" => "open",
        "recordType" => "discussion",
        "recordSubtype" => "question",
        "isDefault" => 1,
        "isSystem" => 1,
    ];
    /** @var array */
    private const DEFAULT_QUESTION_ANSWERED_STATUS = [
        "statusID" => RecordStatusModel::DISCUSSION_STATUS_ANSWERED,
        "name" => "Answered",
        "state" => "open",
        "recordType" => "discussion",
        "recordSubtype" => "question",
        "isDefault" => 0,
        "isSystem" => 1,
    ];
    /** @var array */
    private const DEFAULT_QUESTION_ACCEPTED_STATUS = [
        "statusID" => RecordStatusModel::DISCUSSION_STATUS_ACCEPTED,
        "name" => "Accepted",
        "state" => "closed",
        "recordType" => "discussion",
        "recordSubtype" => "question",
        "isDefault" => 0,
        "isSystem" => 1,
    ];
    /** @var array */
    protected const DEFAULT_QUESTION_REJECTED_STATUS = [
        "statusID" => RecordStatusModel::DISCUSSION_STATUS_REJECTED,
        "name" => "Rejected",
        "state" => "open",
        "recordType" => "discussion",
        "recordSubtype" => "question",
        "isDefault" => 0,
        "isSystem" => 1,
    ];
    /** @var array */
    protected const DEFAULT_COMMENT_ACCEPTED_STATUS = [
        "statusID" => RecordStatusModel::COMMENT_STATUS_ACCEPTED,
        "name" => "Accepted",
        "state" => "closed",
        "recordType" => "comment",
        "recordSubtype" => "answer",
        "isDefault" => 0,
        "isSystem" => 1,
    ];
    /** @var array */
    protected const DEFAULT_COMMENT_REJECTED_STATUS = [
        "statusID" => RecordStatusModel::COMMENT_STATUS_REJECTED,
        "name" => "Rejected",
        "state" => "closed",
        "recordType" => "comment",
        "recordSubtype" => "answer",
        "isDefault" => 0,
        "isSystem" => 1,
    ];
    /** @var array */
    protected const DEFAULT_DISCUSSION_UNRESOLVED_STATUS = [
        "statusID" => RecordStatusModel::DISCUSSION_STATUS_UNRESOLVED,
        "name" => "Unresolved",
        "state" => "open",
        "recordType" => "discussion",
        "recordSubtype" => "discussion",
        "isDefault" => 1,
        "isSystem" => 1,
    ];
    /** @var array */
    protected const DEFAULT_DISCUSSION_RESOLVED_STATUS = [
        "statusID" => self::DISCUSSION_STATUS_RESOLVED,
        "name" => "Resolved",
        "state" => "closed",
        "recordType" => "discussion",
        "recordSubtype" => "discussion",
        "isDefault" => 0,
        "isSystem" => 1,
    ];

    /** @var array[] DEFAULT_STATUSES */
    protected const DEFAULT_STATUSES = [
        self::DEFAULT_DISCUSSION_STATUS,
        self::DEFAULT_QUESTION_UNANSWERED_STATUS,
        self::DEFAULT_QUESTION_ANSWERED_STATUS,
        self::DEFAULT_QUESTION_ACCEPTED_STATUS,
        self::DEFAULT_QUESTION_REJECTED_STATUS,
        self::DEFAULT_COMMENT_ACCEPTED_STATUS,
        self::DEFAULT_COMMENT_REJECTED_STATUS,
        self::DEFAULT_DISCUSSION_UNRESOLVED_STATUS,
        self::DEFAULT_DISCUSSION_RESOLVED_STATUS,
    ];
    //endregion

    //region Constructor
    /**
     * Setup the model.
     *
     * @param CurrentUserFieldProcessor $userFields
     * @param CurrentIPAddressProcessor $ipFields
     */
    public function __construct(CurrentUserFieldProcessor $userFields, CurrentIPAddressProcessor $ipFields)
    {
        parent::__construct(self::TABLE_NAME);

        $userFields->camelCase();
        $this->addPipelineProcessor($userFields);

        $ipFields->camelCase();
        $this->addPipelineProcessor($ipFields);

        $dateFields = new CurrentDateFieldProcessor();
        $dateFields->camelCase();
        $this->addPipelineProcessor($dateFields);

        $booleanFields = new BooleanFieldProcessor(["isDefault", "isSystem"]);
        $this->addPipelineProcessor($booleanFields);
    }
    //endregion

    //region Public Methods
    /**
     * Structure the table schema.
     *
     * @param \Gdn_Database $database Database handle
     * @throws \Exception Query error.
     */
    public static function structure(\Gdn_Database $database): void
    {
        $tableExists = $database->structure()->tableExists("recordStatus");
        // TODO: remove block and method below once unique index has been propagated to all sites, post sprint 2022.01
        if ($tableExists) {
            self::dropObsoleteUniqueIndex($database);
        }

        $database
            ->structure()
            ->table("recordStatus")
            ->primaryKey("statusID")
            ->column("name", "varchar(100)", false, ["unique.recordTypeName"])
            ->column("state", ["open", "closed"], "open")
            ->column("recordType", "varchar(100)", false, [
                "index.recordType",
                "index.recordTypeSubType",
                "unique.recordTypeName",
            ])
            ->column("recordSubtype", "varchar(100)", null, ["index.recordTypeSubType", "unique.recordTypeName"])
            ->column("isDefault", "tinyint", 0)
            ->column("isSystem", "tinyint", 0)
            ->column("insertUserID", "int")
            ->column("dateInserted", "datetime")
            ->column("updateUserID", "int", null)
            ->column("dateUpdated", "datetime", null)
            ->set();

        // Create the default statuses to insert into the recordStatus table.
        foreach (self::DEFAULT_STATUSES as $default) {
            self::processDefaultStatuses($database, $default, $tableExists);
        }

        self::adjustAutoIncrement($database);
    }

    /**
     * @return Schema
     */
    public function getSchema(): Schema
    {
        $schema = Schema::parse([
            "statusID" => ["type" => "integer"],
            "name" => ["type" => "string"],
            "state" => [
                "type" => "string",
                "enum" => ["closed", "open"],
            ],
            "recordType" => ["type" => "string"],
            "recordSubtype" => [
                "type" => "string",
                "allowNull" => true,
            ],
            "isDefault" => ["type" => "boolean"],
            "isSystem" => ["type" => "boolean"],
        ]);

        return $schema;
    }

    /**
     * Get the subset of the status schema to include as a fragment when retrieving data
     * for a resource against which a status is applied.
     *
     * @return Schema
     */
    public static function getSchemaFragment(): Schema
    {
        $schema = Schema::parse([
            "statusID" => ["type" => "integer"],
            "name" => ["type" => "string"],
            "recordType" => ["type" => "string"],
            "recordSubtype" => [
                "type" => "string",
                "allowNull" => true,
            ],
        ]);

        return $schema;
    }

    /**
     * Add a record status.
     *
     * @param array $set Field values to set.
     * @param array $options See Vanilla\Models\Model::OPT_*
     * @return mixed ID of the inserted row.
     * @throws ClientException Attempting to insert a system-defined record status.
     * @throws \Exception If an error is encountered while performing the query.
     */
    public function insert(array $set, array $options = [])
    {
        if (!empty($set["isSystem"])) {
            throw new ClientException("Cannot insert a system defined record status");
        }
        $result = parent::insert($set, $options);
        $recordType = $set["recordType"] ?? null;
        $this->updateRecordTypeStatus($result, $recordType, $set);

        return $result;
    }

    /**
     * Update existing record statuses.
     *
     * @param array $set Field values to set.
     * @param array $where Conditions to restrict the update.
     * @param array $options See Vanilla\Models\Model::OPT_*
     * @return bool
     * @throws ClientException If attempting to update a system defined record status.
     * @throws \Exception If an error is encountered while performing the query.
     */
    public function update(array $set, array $where, array $options = []): bool
    {
        if (!empty($set["isSystem"]) || !empty($where["isSystem"])) {
            throw new ClientException("Cannot update system defined statuses");
        }
        $matchingSystemRecords = array_filter(parent::select($where), function ($candidate) {
            return !empty($candidate["isSystem"]);
        });
        if (!empty($matchingSystemRecords)) {
            throw new ClientException("Cannot update system defined statuses");
        }
        $result = parent::update($set, $where, $options);
        $statusID = $where["statusID"] ?? null;
        $this->updateRecordTypeStatus($statusID, null, $set);

        return $result;
    }

    /**
     * Delete resource rows.
     *
     * @param array $where Conditions to restrict the deletion.
     * @param array $options Options for the delete query.
     *    - limit (int): Limit on the results to be deleted.
     * @throws \Exception If an error is encountered while performing the query.
     * @return bool True.
     * @throws ClientException Attempting to delete system defined status.
     * @throws ClientException Attempting to delete default status.
     */
    public function delete(array $where, array $options = []): bool
    {
        $candidates = parent::select($where, $options);
        if (empty($candidates)) {
            return true;
        }
        $matchingSystemRecords = array_filter($candidates, function (array $candidate) {
            return !empty($candidate["isSystem"]);
        });
        if (!empty($matchingSystemRecords)) {
            throw new ClientException("Cannot delete system defined statuses");
        }
        $matchingDefaults = array_filter($candidates, function (array $candidate) {
            return !empty($candidate["isDefault"]);
        });
        if (!empty($matchingDefaults)) {
            throw new ClientException("Default status cannot be deleted");
        }
        return parent::delete($where, $options);
    }

    /**
     * Convert the provided ideation-specific status to its corresponding record status.
     * If corresponding record status does not exist, the array returned will not have a
     * primary key value set. It is then the caller's responsibility to insert the record status
     * using the returned value.
     *
     * @param array $ideationStatus Status record from GDN_Status specific to ideation
     * @return array Corresponding record status record, which, if missing its primary key value,
     * indicates that the corresponding record status record has not yet been persisted.
     * @throws \Garden\Schema\ValidationException Row fails to validate against schema.
     * @throws \Vanilla\Exception\Database\NoResultsException If ideation status references
     * a recordStatus record value that cannot be found.
     */
    public function convertFromIdeationStatus(array $ideationStatus): array
    {
        //StatusID excluded from required properties check to allow for converting pending inserts
        if (
            empty($ideationStatus["Name"]) ||
            empty($ideationStatus["State"]) ||
            !array_key_exists("IsDefault", $ideationStatus)
        ) {
            throw new \InvalidArgumentException("Status provided does not include one or more required properties");
        }
        if (!empty($ideationStatus["recordStatusID"])) {
            $row = $this->selectSingle(["statusID" => $ideationStatus["recordStatusID"]]);
            return $row;
        }

        // Most of the column names are the same between GDN_Status and GDN_recordStatus
        // but the older GDN_Status column names contain an initial capital letter that
        // we're normalizing to an initial lowercase letter.
        $ideationStatus = ArrayUtils::camelCase($ideationStatus);
        $schemaProps = $this->getSchema()->getSchemaArray()["properties"];
        $convertedStatus = array_intersect_key($ideationStatus, $schemaProps);
        $convertedStatus["state"] = isset($convertedStatus["state"]) ? lcfirst($convertedStatus["state"]) : "open";
        $defaults = ["recordType" => "discussion", "recordSubtype" => "ideation", "isSystem" => 0];
        $convertedStatus = array_merge($convertedStatus, $defaults);
        unset($convertedStatus["statusID"]);

        return $convertedStatus;
    }
    //endregion

    //region Non-Public methods
    /**
     * Drop any unique index defined for the table that doesn't include recordSubtype.
     * Initial unique index was over only name and recordType, need to establish unique index
     * over all three of name, recordType and recordSubType.
     *
     * Remove this method once it is no longer necessary.
     *
     * @param \Gdn_Database $database Database handle
     * @return void
     * @throws \Exception Query error.
     */
    private static function dropObsoleteUniqueIndex(\Gdn_Database $database): void
    {
        $indicies = $database
            ->structure()
            ->table("recordStatus")
            ->indexSqlDb();
        $candidates = array_filter(
            $indicies,
            function ($value, $key) {
                return str_starts_with($key, "UX") && !str_contains($value, "recordSubtype");
            },
            ARRAY_FILTER_USE_BOTH
        );
        foreach ($candidates as $indexName => $_) {
            $dropIndexStatement = "drop index {$indexName} on {$database->DatabasePrefix}recordStatus";
            $database->sql()->query($dropIndexStatement);
        }
    }

    /**
     * Process a default status as part of database structure logic for this table.
     *
     * @param \Gdn_Database $database Database handle
     * @param array $default Default record status to process
     * @param bool $tableExists True if the table already exists, false if the table does not yet exist
     * @throws \Exception Query error.
     */
    private static function processDefaultStatuses(\Gdn_Database $database, array $default, bool $tableExists): void
    {
        $defaultInsertProps = ["insertUserID" => 1, "dateInserted" => date("Y-m-d H:i:s")];
        /** @var \Gdn_DataSet $dataSet */
        $dataSet = $tableExists
            ? $database->sql()->getWhere("recordStatus", ["statusID" => $default["statusID"]])
            : new \Gdn_DataSet([], DATASET_TYPE_ARRAY);
        if ($dataSet->numRows() == 0) {
            // Add the default statuses if they're not already there.
            $default = array_merge($default, $defaultInsertProps);
            // Some default statuses have an ID of 0 and need this mode set.
            $database->runWithSqlMode([\Gdn_Database::SQL_MODE_NO_AUTO_VALUE_ZERO], function () use (
                $default,
                $database
            ) {
                $database->sql()->insert("recordStatus", $default);
            });
        } else {
            // Ensure the row matches this definition
            $row = array_intersect_key($dataSet->firstRow(DATASET_TYPE_ARRAY), $default);
            $defaultDiff = array_diff_assoc($default, $row);
            if (!empty($defaultDiff)) {
                $database
                    ->sql()
                    ->update("recordStatus", $defaultDiff, ["statusID" => $default["statusID"]])
                    ->put();
            }
        }
    }

    /**
     * Add a protected space for core/addon status IDs and ensure user-created statuses will have IDs outside it.
     *
     * @param \Gdn_Database $database Database handle.
     * @return void
     * @throws \Exception Query error.
     */
    private static function adjustAutoIncrement(\Gdn_Database $database): void
    {
        $databaseName = $database->sql()->databaseName();
        $tableName = $database->sql()->prefixTable("recordStatus");
        $recordStatusAutoIncrementQuery =
            "SELECT `AUTO_INCREMENT` FROM INFORMATION_SCHEMA.TABLES " .
            "WHERE TABLE_SCHEMA = '{$databaseName}' AND TABLE_NAME = '{$tableName}'";
        $dataSet = $database
            ->sql()
            ->query($recordStatusAutoIncrementQuery, "select")
            ->firstRow("array");
        $autoIncVal = $dataSet === false ? 0 : intval(array_values($dataSet)[0]);
        if ($autoIncVal < 10000) {
            $recordStatusIDQuery = "alter table {$tableName} AUTO_INCREMENT=10000";
            $database->sql()->query($recordStatusIDQuery, "update");
        }
    }

    /**
     * Update the isDefault flag of a record type's statuses, based on updates to that type's statuses.
     *
     * @param mixed $statusID
     * @param string|null $recordType
     * @param array $set
     */
    private function updateRecordTypeStatus($statusID, ?string $recordType = null, array $set = []): void
    {
        if (is_int($statusID) === false) {
            return;
        }

        $isDefault = $set["isDefault"] ?? null;
        if ($isDefault != true) {
            return;
        }

        // Make an effort to obtain the recordType, if not provided.
        if ($recordType === null) {
            try {
                $row = $this->selectSingle(["statusID" => $statusID]);
            } catch (\Exception $e) {
                return;
            }
            $recordType = $row["recordType"] ?? null;
            if (!is_string($recordType)) {
                return;
            }
        }

        // The setting of isDefault for the base record should've already been performed. We just need to reset the others.
        $this->update(
            ["isDefault" => 0],
            [
                "recordType" => $recordType,
                "isSystem" => false,
                "statusID <>" => $statusID,
            ]
        );
    }

    /**
     * Add Status data to an array.
     *
     * @param array|iterable $rows Results we need to associate user data with.
     * @param array $columns Database columns containing StatusID to get data for.
     * @param array $properties List of properties to add to the array.
     */
    public function expandStatuses(&$rows, array $columns, array $properties)
    {
        // How are we supposed to lookup status by column if we don't have any columns?
        if (count($rows) === 0 || count($columns) === 0) {
            return;
        }

        reset($rows);
        $single = !($rows instanceof Traversable) && is_string(key($rows));

        // Retrieve all record statuses, and put them int an index array by StatusID, for easy retrieval.
        $statuses = $this->select();
        $statuses = Gdn_DataSet::index($statuses, ["statusID"]);

        $populate = function (&$row) use ($statuses, $columns, $properties) {
            foreach ($columns as $key) {
                $statusID = $row[$key] ?? null;
                // if status is not found default to ... DEFAULT_DISCUSSION_STATUS=0.
                if ($statusID !== null) {
                    $status = $statuses[$statusID] ?? RecordStatusModel::DEFAULT_DISCUSSION_STATUS;
                } else {
                    $status = RecordStatusModel::DEFAULT_DISCUSSION_STATUS;
                }
                // Populate properties from status record.
                foreach ($properties as $property) {
                    $row[$property] = $status[$property];
                }
            }
        };

        // Inject those user records.
        if ($single) {
            $populate($rows);
        } else {
            foreach ($rows as &$row) {
                $populate($row);
            }
        }
    }
    //endregion
}
