<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentIPAddressProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Models\ModelCache;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;

/**
 * Model for managing the recordStatus table.
 */
class RecordStatusModel extends FullRecordCacheModel
{
    //region Properties
    private const TABLE_NAME = "recordStatus";

    /** @var EventManager */
    private $eventManager;

    public const DISCUSSION_STATUS_NONE = 0;

    /** @var array $systemDefinedIDs */
    public static $systemDefinedIDs = [RecordStatusModel::DISCUSSION_STATUS_NONE];

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

    /** @var array[] DEFAULT_STATUSES */
    protected const DEFAULT_STATUSES = [self::DEFAULT_DISCUSSION_STATUS];
    //endregion

    /** @var \Gdn_Dirtycache */
    private $localCache;

    /** @var \Gdn_Session */
    private $session;

    //region Constructor
    /**
     * Setup the model.
     *
     * @param CurrentUserFieldProcessor $userFields
     * @param CurrentIPAddressProcessor $ipFields
     * @param EventManager $eventManager
     * @param \Gdn_Cache $cache
     * @param \Gdn_Session $session
     */
    public function __construct(
        CurrentUserFieldProcessor $userFields,
        CurrentIPAddressProcessor $ipFields,
        EventManager $eventManager,
        \Gdn_Cache $cache,
        \Gdn_Session $session
    ) {
        parent::__construct(self::TABLE_NAME, $cache, [
            ModelCache::OPT_TTL => 60 * 60,
        ]);
        $this->eventManager = $eventManager;
        $this->session = $session;
        $this->localCache = new \Gdn_Dirtycache();

        $userFields->camelCase();
        $this->addPipelineProcessor($userFields);

        $ipFields->camelCase();
        $this->addPipelineProcessor($ipFields);

        $dateFields = new CurrentDateFieldProcessor();
        $dateFields->camelCase();
        $this->addPipelineProcessor($dateFields);

        $booleanFields = new BooleanFieldProcessor(["isDefault", "isSystem", "isActive"]);
        $this->addPipelineProcessor($booleanFields);
        $this->modelCache->setOnInvalidate(function () {
            $this->localCache = new \Gdn_Dirtycache();
        });
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
            ->column("isActive", "tinyint", 1)
            ->column("insertUserID", "int")
            ->column("dateInserted", "datetime")
            ->column("updateUserID", "int", null)
            ->column("dateUpdated", "datetime", null)
            ->set();

        // Create the default statuses to insert into the recordStatus table.
        foreach (self::DEFAULT_STATUSES as $default) {
            self::processDefaultStatuses($database, $default, true);
        }

        self::adjustAutoIncrement($database);
    }

    /**
     * Set recordStatuses `isActive` value to either 1 or 0, depending on the system's requirements.
     *
     * @return void
     * @throws ClientException
     */
    public function structureActiveStates(): void
    {
        $sql = $this->createSql();

        $structureEvent = new RecordStatusStructureEvent();
        $this->eventManager->dispatch($structureEvent);

        $activeStatusIDs = $structureEvent->getActiveRecordsStatusIDs();

        $activeStatusIDs = array_values(
            array_unique(array_merge($activeStatusIDs, self::$systemDefinedIDs), SORT_REGULAR)
        );

        try {
            // Look for statuses that shouldn't be active, but are.
            $toDisableStatuses = $sql
                ->select("statusID")
                ->from(self::TABLE_NAME)
                ->where("isActive", 1)
                ->whereNotIn("statusID", $activeStatusIDs)
                ->get()
                ->resultArray();
            // Mark those IDs as inactive.
            foreach ($toDisableStatuses as $toDisableStatus) {
                $this->setIsActive($toDisableStatus["statusID"], false);
            }

            // Look for statuses that should be active, but aren't.
            $toEnableStatuses = $sql
                ->select("statusID")
                ->from(self::TABLE_NAME)
                ->where("isActive", 0)
                ->whereIn("statusID", $activeStatusIDs)
                ->get()
                ->resultArray();
            // Mark those IDs as active.
            foreach ($toEnableStatuses as $toEnableStatus) {
                $this->setIsActive($toEnableStatus["statusID"]);
            }
        } finally {
            $this->clearCache();
        }
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
            "isActive" => ["type" => "boolean"],
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
        return Schema::parse([
            "statusID:i",
            "name:s",
            "state:s?",
            "recordType:s",
            "recordSubType:s?",
            "log?" => Schema::parse(["reasonUpdated:s?", "dateUpdated", "updateUser?" => new UserFragmentSchema()]),
        ]);
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
        $defaults = ["recordType" => "discussion", "recordSubtype" => "ideation", "isSystem" => 0, "isActive" => 1];
        $convertedStatus = array_merge($convertedStatus, $defaults);
        unset($convertedStatus["statusID"]);

        return $convertedStatus;
    }

    /**
     * Enable/disable a `GDN_recordStatus` record by setting the `isActive` field to either 1 or 0.
     *
     * @param int $statusID The record ID to update.
     * @param bool $isActive Set isActive to 1 or 0.
     * @return bool
     * @throws ClientException If attempting to update a system defined record status.
     */
    public function setIsActive(int $statusID, bool $isActive = true): bool
    {
        $where = ["statusID" => $statusID];
        $isActiveValue = (int) $isActive;

        if (
            $this->select([
                "statusID" => $statusID,
                "isActive" => (int) !$isActive,
            ])
        ) {
            return parent::update(["isActive" => $isActiveValue], $where);
        }

        return false;
    }

    /**
     * Get a list of statuses that match States 'open'/'closed'.
     *
     * @param string $field
     * @param string $statusState - status state 'open'/'closed'
     *
     * @return array List of statuses.
     */
    public static function statusByState(string $field, string $statusState): array
    {
        $recordState = Gdn::getContainer()->get(RecordStatusModel::class);
        $statuses = $recordState->getActiveStatuses();
        $statusIDs = [];
        foreach ($statuses as $statusID => $status) {
            if ($status["state"] == $statusState) {
                $statusIDs[] = $statusID;
            }
        }
        return [$field => $statusIDs];
    }

    /**
     * Validate status provided are active, and existing.
     *
     * @param array $statusIDs
     */
    public function validateStatusesAreActive(array $statusIDs): array
    {
        // If filtering by default 0 status, add other inactive statuses to the query.
        if (in_array(0, $statusIDs)) {
            $inactiveStatuses = array_keys($this->getStatuses(false));
            $statusIDs = array_merge($statusIDs, $inactiveStatuses);
        } else {
            $statuses = $this->getActiveStatuses();
            $found = 0;
            foreach ($statusIDs as $statusID) {
                if ($statuses[$statusID] ?? false) {
                    $found++;
                } else {
                    break;
                }
            }

            if ($found < count($statusIDs)) {
                throw new NotFoundException("Discussion Status is non-existent or inactive", [
                    "statusID" => $statusIDs,
                ]);
            }
        }

        return $statusIDs;
    }
    //endregion

    //region Non-Public methods

    /**
     * Process a default status as part of database structure logic for this table.
     *
     * @param \Gdn_Database $database Database handle
     * @param array $default Default record status to process
     * @param bool $tableExists True if the table already exists, false if the table does not yet exist
     * @throws \Exception Query error.
     */
    public static function processDefaultStatuses(\Gdn_Database $database, array $default, bool $tableExists): void
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
    public static function adjustAutoIncrement(\Gdn_Database $database): void
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
     * Get all currently active/inactive statuses.
     *
     * @return array{array-key, array{statusID: string, name: string, state: string}}
     */
    public function getActiveStatuses(): array
    {
        return $this->getStatuses(true);
    }
    /**
     * Get all currently active/inactive statuses.
     *
     * @param bool $isActive status active/inactive
     * @return array{array-key, array{statusID: string, name: string, state: string}}
     */
    public function getStatuses(bool $isActive = true): array
    {
        $statuses = $this->select(["isActive" => $isActive]);
        $userID = $this->session->UserID;
        // We use a local cache to reduce the number of events that might be fired.
        // Cache is fragmented by userID primarily for tests where userIDs might change without destroying the model.

        $cacheKey = $isActive . "recordStatusByUser_" . $userID;
        $activeStatusIDs = $this->localCache->get($cacheKey);
        if ($activeStatusIDs === \Gdn_Cache::CACHEOP_FAILURE) {
            $event = new RecordStatusActiveEvent();
            $event->setIsActive($isActive);
            $event->addActiveRecordStatusIDs(array_column($statuses, "statusID"));
            $this->eventManager->dispatch($event);
            $activeStatusIDs = $event->getActiveRecordsStatusIDs();
            $this->localCache->store($cacheKey, $activeStatusIDs);
        }

        $activeStatusesByID = [];
        foreach ($statuses as $status) {
            if (in_array($status["statusID"], $activeStatusIDs)) {
                $activeStatusesByID[$status["statusID"]] = $status;
            }
        }
        return $activeStatusesByID;
    }

    /**
     * Add Status data to an array.
     *
     * @param array $rows Results we need to associate status data with.
     */
    public function expandStatuses(array &$rows)
    {
        ModelUtils::leftJoin($rows, ["statusID"], [$this, "getActiveStatuses"]);
    }
    //endregion
}
