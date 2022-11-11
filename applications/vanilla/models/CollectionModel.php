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
 * A model for handling collection
 */
class CollectionModel extends PipelineModel
{
    private const TABLE_NAME = "collection";

    public const LIMIT_DEFAULT = 20;

    /** Cache time to live. */
    const CACHE_TTL = 3600;

    /** @var CollectionRecordProviderInterface[] */
    protected static $provider;

    /** @var CollectionRecordProviderInterface[] */
    protected $providersByType;

    /** @var ModelCache */
    private $modelCache;

    /** @var bool $filtered */
    private $filtered = false;

    /** @var \UserModel */
    protected $userModel;

    /**
     * Collection Model constructor.
     *
     * @param \Gdn_Session $session
     * @param \UserModel $userModel
     */
    public function __construct(\Gdn_Session $session, \UserModel $userModel)
    {
        parent::__construct(self::TABLE_NAME);
        $dateProcessor = new Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new Operation\CurrentUserFieldProcessor($session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);

        $this->userModel = $userModel;
    }

    /**
     * Get all providers
     *
     * @param CollectionRecordProviderInterface $provider
     * @return void
     */
    public function addCollectionRecordProvider(CollectionRecordProviderInterface $provider)
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
     * Get collection records based on filter
     *
     * @param array $where
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function searchCollectionRecords(array $where = [], array $options = []): array
    {
        $results = $this->select($where, $options);
        $where = [];
        $where["c.collectionID"] = array_column($results, "collectionID");
        $sql = $this->createSql();
        $records = [];
        $results = $sql
            ->select()
            ->from($this->getTable() . " c")
            ->join("collectionRecord r", "r.collectionID = c.collectionID")
            ->orderBy(["r.collectionID", "r.sort"], "asc")
            ->where($where)
            ->get()
            ->resultArray();
        $collectionID = null;
        $key = -1;
        $availableRecordTypes = $this->getAllRecordTypes();
        foreach ($results as $value) {
            if (!in_array($value["recordType"], $availableRecordTypes)) {
                continue;
            }
            $collectionRecords = array_intersect_key($value, ["recordID" => "", "recordType" => "", "sort" => ""]);
            unset($value["recordID"], $value["recordType"], $value["sort"]);
            if ($collectionID != $value["collectionID"]) {
                $key++;
                $collectionID = $value["collectionID"];
                $records[$key] = $value;
            }
            $records[$key]["records"][] = $collectionRecords;
        }
        unset($results);

        return $records;
    }

    /**
     * Get a single collection by its ID
     *
     * @param int $collectionID
     * @return array
     */
    public function getCollectionRecordByID(int $collectionID): array
    {
        $collectionRecord = $this->selectSingle(["collectionID" => $collectionID]);
        $sql = $this->createSql();
        $filteredRecords = [];
        $records = $sql
            ->select()
            ->where("collectionID", $collectionID)
            ->orderBy("sort")
            ->get("collectionRecord")
            ->resultArray();
        $activeTypes = $this->getAllRecordTypes();
        foreach ($records as $record) {
            if (!in_array($record["recordType"], $activeTypes)) {
                continue;
            }
            $filteredRecords[] = $record;
        }

        $collectionRecord["records"] = $filteredRecords;

        return $collectionRecord;
    }

    /**
     * Get a group of collections given a set of criteria.
     *
     * @param array $recordData This should specify the recordID and recordType
     * @return array
     */
    public function getCollectionsByRecord(array $recordData): array
    {
        $sql = $this->createSql();
        $collectionIDs = $sql
            ->select("collectionID")
            ->where($recordData)
            ->get("collectionRecord")
            ->column("collectionID");
        $collections = $this->select(["collectionID" => $collectionIDs]);
        return $collections;
    }

    /**
     * Delete a specific collection record
     *
     * @param int $collectionID
     * @return void
     */
    public function deleteCollection(int $collectionID): void
    {
        $where = ["collectionID" => $collectionID];
        $sql = $this->createSql();
        $sql->delete("collectionRecord", $where);
        $this->delete($where);
        $this->clearAllCache($collectionID);
    }

    /**
     * Add new collection and its records
     *
     * @param array $collectionRecord
     * @return int $collectionID
     * @throws ClientException
     */
    public function saveCollection(array $collectionRecord): int
    {
        if (!isset($collectionRecord["records"])) {
            throw new \Gdn_UserException("Records for the collection are not supplied");
        }
        $records = $collectionRecord["records"];
        unset($collectionRecord["records"]);
        try {
            $this->database->beginTransaction();
            $collectionID = $this->insert($collectionRecord);
            if (empty($collectionID)) {
                throw new ClientException("Error inserting the collection Record");
            }
            $this->addCollectionRecords($collectionID, $records);

            $this->database->commitTransaction();
        } catch (\Exception $e) {
            $this->database->rollbackTransaction();
            throw $e;
        }

        return $collectionID;
    }

    /**
     * Add collection records
     *
     * @param int $collectionID
     * @param array $records
     * @return void
     */
    public function addCollectionRecords(int $collectionID, array $records): void
    {
        $sql = $this->createSql();
        $insertedKeys = [];
        foreach ($records as $record) {
            $record["collectionID"] = $collectionID;
            $record["sort"] = empty($record["sort"]) ? 30 : $record["sort"];
            $key = $record["recordID"] . "_" . $record["recordType"];
            if (!in_array($key, $insertedKeys)) {
                $sql->insert("collectionRecord", $record);
                $insertedKeys[] = $key;
            }
        }
        if (count($insertedKeys)) {
            $this->clearAllCache($collectionID);
        }
    }

    /**
     * Remove a record from one or more collections.
     *
     * @param array $record
     * @param array $collectionIDs
     */
    public function removeRecordFromCollections(array $record, array $collectionIDs): void
    {
        if (count($collectionIDs)) {
            $sql = $this->createSql();
            $where = [
                "collectionID" => $collectionIDs,
                "recordID" => $record["recordID"],
                "recordType" => $record["recordType"],
            ];

            $sql->delete("collectionRecord", $where);

            //We need to clear existing cache
            foreach ($collectionIDs as $collectionID) {
                $this->clearAllCache($collectionID);
            }
        }
    }

    /**
     * Update a collection and its records
     *
     * @param int $collectionID
     * @param array $collectionRecord
     * @return void
     * @throws \Garden\Schema\ValidationException
     * @throws \Vanilla\Exception\Database\NoResultsException
     */
    public function updateCollection(int $collectionID, array $collectionRecord): void
    {
        $currentRecords = $this->selectSingle(["collectionID" => $collectionID]);
        $records = $collectionRecord["records"];
        unset($collectionRecord["records"]);
        $collectionRecord = array_merge($currentRecords, $collectionRecord);
        try {
            $this->database->beginTransaction();
            $sql = $this->createSql();
            $sql->delete("collectionRecord", ["collectionID" => $collectionID]);
            $this->update($collectionRecord, ["collectionID" => $collectionID]);
            $this->addCollectionRecords($collectionID, $records);
            $this->database->commitTransaction();
        } catch (\Exception $e) {
            $this->database->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Get a single collection with its corresponding records expanded
     *
     * @param int $collectionID
     * @param string $locale
     * @return array
     */
    public function getCollectionRecordContentByID(int $collectionID, string $locale = "en"): array
    {
        $filtered = false;
        $cacheArgs = array_merge($this->userModel->getRoleIDs(\Gdn::session()->UserID), ["locale" => $locale]);
        $modelCache = $this->getModelCache($collectionID);
        $collectionRecord = $modelCache->getCachedOrHydrate($cacheArgs, function () use (
            $collectionID,
            &$filtered,
            $locale
        ) {
            $filtered = true;
            return $this->generateRecordContent($collectionID, $locale);
        });
        if (!$filtered) {
            $collectionRecord["records"] = $this->filterCollectionRecords($collectionRecord["records"], null);
        }
        return $collectionRecord;
    }

    /**
     * Filter given records based on their record type
     *
     * @param array $records
     * @return array
     */
    private function filterRecordsByType(array $records): array
    {
        $recordByTypes = [];
        if (count($records)) {
            foreach ($records as $i => $record) {
                if (isset($record["recordID"]) && isset($record["recordType"])) {
                    $record["index"] = $i;
                    $recordByTypes[$record["recordType"]][] = $record;
                }
            }
        }

        return $recordByTypes;
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
    public function validateCollectionRecords(array $records, ValidationField $validationField): bool
    {
        $valid = true;
        $recordByTypes = $this->filterRecordsByType($records);

        // Validate the record types.
        foreach ($recordByTypes as $recordType => $records) {
            $provider = $this->providersByType[$recordType] ?? null;
            if ($provider === null) {
                $errMessage = "The recordType {$recordType} is not a valid record type for collection.";
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

    /**
     * Create a model cache
     * @param int $collectionID
     * @return ModelCache
     */
    private function getModelCache(int $collectionID): ModelCache
    {
        $cacheNameSpace = "CollectionRecordContent-$collectionID";
        return new ModelCache($cacheNameSpace, \Gdn::cache(), [
            \Gdn_Cache::FEATURE_EXPIRY => self::CACHE_TTL,
        ]);
    }

    /**
     * Clear all Model cache
     * @param int $collectionID
     * @return void
     */
    private function clearAllCache(int $collectionID): void
    {
        $modelCache = $this->getModelCache($collectionID);
        $modelCache->invalidateAll();
    }

    /**
     * Get collection record contents
     * @param int $collectionID
     * @param string $locale
     * @return array
     * @throws ValidationException
     * @throws \Vanilla\Exception\Database\NoResultsException
     */
    public function generateRecordContent(int $collectionID, string $locale): array
    {
        $collectionRecord = $this->getCollectionRecordByID($collectionID);

        $filteredRecords = $this->filterCollectionRecords($collectionRecord["records"], $locale, false);

        $collectionRecord["records"] = $filteredRecords;

        return $collectionRecord;
    }

    /**
     * Filter collection record contents to see if they are good to be displayed
     * @param array $collectionRecords
     * @param ?string $locale
     * @param bool $cached
     * @return array
     */
    private function filterCollectionRecords(array $collectionRecords, ?string $locale, bool $cached = true): array
    {
        $recordByTypes = [];

        //find the current valid records
        foreach ($this->filterRecordsByType($collectionRecords) as $recordType => $records) {
            $provider = $this->providersByType[$recordType] ?? null;
            if (empty($provider)) {
                continue;
            }
            $validRecordIDs = $provider->filterValidRecordIDs(array_column($records, "recordID")) ?? [];
            if (!$cached) {
                $recordByTypes[$recordType] = $provider->getRecords($validRecordIDs, $locale);
            } else {
                $records = array_column($records, null, "recordID");
                foreach ($validRecordIDs as $recordID) {
                    if (!empty($records[$recordID])) {
                        $recordByTypes[$recordType][$recordID] = $records[$recordID]["record"];
                    }
                }
            }
        }

        // filter out records that are currently not valid
        foreach ($collectionRecords as $index => $record) {
            if (
                isset($recordByTypes[$record["recordType"]]) &&
                isset($recordByTypes[$record["recordType"]][$record["recordID"]])
            ) {
                if (!empty($recordByTypes[$record["recordType"]][$record["recordID"]])) {
                    $collectionRecords[$index]["record"] = $recordByTypes[$record["recordType"]][$record["recordID"]];
                }
            } else {
                unset($collectionRecords[$index]);
            }
        }
        return array_values($collectionRecords);
    }
}
