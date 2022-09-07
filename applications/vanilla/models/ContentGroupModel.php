<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\ClientException;
use Vanilla\Database\Operation;

/**
 * A model for handling contentGroup
 */
class ContentGroupModel extends PipelineModel
{
    private const TABLE_NAME = "contentGroup";

    public const LIMIT_DEFAULT = 20;

    /** @var ContentGroupRecordProviderInterface[] */
    protected static $provider;

    /** @var ContentGroupRecordProviderInterface[] */
    protected $providersByType;

    /**
     * ContentGroupModel constructor.
     */
    public function __construct(\Gdn_Session $session)
    {
        parent::__construct(self::TABLE_NAME);
        $dateProcessor = new Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new Operation\CurrentUserFieldProcessor($session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Get all providers
     *
     * @param ContentGroupRecordProviderInterface $provider
     * @return void
     */
    public function addContentGroupRecordProvider(ContentGroupRecordProviderInterface $provider)
    {
        $this->providersByType[$provider->getRecordType()] = $provider;
    }

    /**
     * Get all available recordTypes
     *
     * @return array
     */
    public function getAllRecordTypes(): array
    {
        return array_keys($this->providersByType);
    }

    /**
     * Get content group records based on filter
     *
     * @param array $where
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function searchContentGroupRecords(array $where = [], array $options = []): array
    {
        $results = $this->select($where, $options);
        $where = [];
        $where["c.contentGroupID"] = array_column($results, "contentGroupID");
        $sql = $this->createSql();
        $records = [];
        $results = $sql
            ->select()
            ->from($this->getTable() . " c")
            ->join("contentGroupRecord r", "r.contentGroupID = c.contentGroupID")
            ->orderBy(["r.contentGroupID", "r.sort"], "asc")
            ->where($where)
            ->get()
            ->resultArray();
        $contentGroupID = null;
        $key = -1;
        foreach ($results as $value) {
            $contentRecords = array_intersect_key($value, ["recordID" => "", "recordType" => "", "sort" => ""]);
            unset($value["recordID"], $value["recordType"], $value["sort"]);
            if ($contentGroupID != $value["contentGroupID"]) {
                $key++;
                $contentGroupID = $value["contentGroupID"];
                $records[$key] = $value;
            }
            $records[$key]["records"][] = $contentRecords;
        }
        unset($results);

        return $records;
    }

    /**
     * Get a single content group by its ID
     *
     * @param $contentGroupID
     * @return array
     * @throws \Garden\Schema\ValidationException
     * @throws \Vanilla\Exception\Database\NoResultsException
     */
    public function getContentGroupRecordByID($contentGroupID): array
    {
        $contentRecord = $this->selectSingle(["contentGroupID" => $contentGroupID]);
        $sql = $this->createSql();
        $records = $sql
            ->select()
            ->where("contentGroupID", $contentGroupID)
            ->orderBy("sort")
            ->get("contentGroupRecord")
            ->resultArray();
        $contentRecord["records"] = $records;

        return $contentRecord;
    }

    /**
     * @param $contentGroupID
     * @return void
     * @throws \Exception
     */
    public function deleteContentGroup($contentGroupID): void
    {
        $where = ["contentGroupID" => $contentGroupID];
        $sql = $this->createSql();
        $sql->delete("contentGroupRecord", $where);
        $this->delete($where);
    }

    /**
     * Add new content Group and its records
     *
     * @param $contentGroupRecord
     * @return int $contentGroupID
     * @throws ClientException
     */
    public function saveContentGroup($contentGroupRecord): int
    {
        if (!isset($contentGroupRecord["records"])) {
            throw new \Gdn_UserException("Records for the content group are not supplied");
        }
        $records = $contentGroupRecord["records"];
        unset($contentGroupRecord["records"]);
        try {
            $this->database->beginTransaction();
            $contentGroupID = $this->insert($contentGroupRecord);
            if (empty($contentGroupID)) {
                throw new ClientException("Error inserting the contentGroup Record");
            }
            $this->addContentGroupRecords($contentGroupID, $records);

            $this->database->commitTransaction();
        } catch (\Exception $e) {
            $this->database->rollbackTransaction();
            throw $e;
        }

        return $contentGroupID;
    }

    /**
     * Add content Group records
     *
     * @param int $contentGroupID
     * @param array $records
     * @return void
     */
    private function addContentGroupRecords(int $contentGroupID, array $records): void
    {
        $sql = $this->createSql();
        $insertedKeys = [];
        foreach ($records as $record) {
            $record["contentGroupID"] = $contentGroupID;
            $record["sort"] = empty($record["sort"]) ? 30 : $record["sort"];
            $key = $record["recordID"] . "_" . $record["recordType"];
            if (!in_array($key, $insertedKeys)) {
                $sql->insert("contentGroupRecord", $record);
                $insertedKeys[] = $key;
            }
        }
    }

    /**
     * Update a content group and its records
     *
     * @param int $contentGroupID
     * @param array $contentGroupRecord
     * @return void
     * @throws \Garden\Schema\ValidationException
     * @throws \Vanilla\Exception\Database\NoResultsException
     */
    public function updateContentGroup(int $contentGroupID, array $contentGroupRecord): void
    {
        $currentRecords = $this->selectSingle(["contentGroupID" => $contentGroupID]);
        $records = $contentGroupRecord["records"];
        unset($contentGroupRecord["records"]);
        $contentGroupRecord = array_merge($currentRecords, $contentGroupRecord);
        try {
            $this->database->beginTransaction();
            $sql = $this->createSql();
            $sql->delete("contentGroupRecord", ["contentGroupID" => $contentGroupID]);
            $this->update($contentGroupRecord, ["contentGroupID" => $contentGroupID]);
            $this->addContentGroupRecords($contentGroupID, $records);
            $this->database->commitTransaction();
        } catch (\Exception $e) {
            $this->database->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Validate the given records are valid.
     *
     * @param array $records
     * @param ValidationField $validationField
     *
     * @return bool
     * @throws ValidationException
     */
    public function validateContentGroupRecords(array $records, ValidationField $validationField): bool
    {
        $recordByTypes = [];
        $valid = true;
        foreach ($records as $i => $record) {
            $record["index"] = $i;
            $recordByTypes[$record["recordType"]][] = $record;
        }

        // Validate the record types.
        foreach ($recordByTypes as $recordType => $records) {
            $provider = $this->providersByType[$recordType] ?? null;
            if ($provider === null) {
                $errMessage = "The recordType {$recordType} is not a valid record type for content groups.";
                $invalidRecordIndexes = array_column($records, "index");
                foreach ($invalidRecordIndexes as $invalidRecordIndex) {
                    $validationField->addError($errMessage, ["status" => 400, "index" => $invalidRecordIndex]);
                }
                $valid = false;
                continue;
            }

            // Now validate the recordIDs.
            $validRecordIDs = $provider->filterValidRecordIDs(array_column($records, "recordID"));
            foreach ($records as $errorRecord) {
                if (!in_array($errorRecord["recordID"], $validRecordIDs)) {
                    // This is not a valid recordID.
                    $errMessage =
                        "The record " .
                        $errorRecord["recordID"] .
                        " with recordType " .
                        $errorRecord["recordType"] .
                        " does not exist.";
                    $options = ["status" => 404, "index" => $errorRecord["index"]];
                    $validationField->addError($errMessage, $options);
                    $valid = false;
                }
            }
        }

        return $valid;
    }
}
