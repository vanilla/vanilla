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

    /** Cache time to live. */
    const CACHE_TTL = 3600;

    /** @var ContentGroupRecordProviderInterface[] */
    protected static $provider;

    /** @var ContentGroupRecordProviderInterface[] */
    protected $providersByType;

    /** @var ModelCache */
    private $modelCache;

    /** @var bool $filtered */
    private $filtered = false;

    /** @var \UserModel */
    protected $userModel;

    /**
     * ContentGroupModel constructor.
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
     * @param int $contentGroupID
     * @return array
     */
    public function getContentGroupRecordByID(int $contentGroupID): array
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
     * Delete a specific content group record
     *
     * @param int $contentGroupID
     * @return void
     */
    public function deleteContentGroup(int $contentGroupID): void
    {
        $where = ["contentGroupID" => $contentGroupID];
        $sql = $this->createSql();
        $sql->delete("contentGroupRecord", $where);
        $this->delete($where);
        $this->clearAllCache($contentGroupID);
    }

    /**
     * Add new content Group and its records
     *
     * @param array $contentGroupRecord
     * @return int $contentGroupID
     * @throws ClientException
     */
    public function saveContentGroup(array $contentGroupRecord): int
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
        $this->clearAllCache($contentGroupID);
    }

    /**
     * Get a single content group with its corresponding records expanded
     *
     * @param int $contentGroupID
     * @param string $locale
     * @return array
     */
    public function getContentGroupRecordContentByID(int $contentGroupID, string $locale = "en"): array
    {
        $filtered = false;
        $cacheArgs = array_merge($this->userModel->getRoleIDs(\Gdn::session()->UserID), ["locale" => $locale]);
        $modelCache = $this->getModelCache($contentGroupID);
        $contentRecord = $modelCache->getCachedOrHydrate($cacheArgs, function () use (
            $contentGroupID,
            &$filtered,
            $locale
        ) {
            $filtered = true;
            return $this->generateRecordContent($contentGroupID, $locale);
        });
        if (!$filtered) {
            $contentRecord["records"] = $this->filterContentRecords($contentRecord["records"], null);
        }
        return $contentRecord;
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
    public function validateContentGroupRecords(array $records, ValidationField $validationField): bool
    {
        $valid = true;
        $recordByTypes = $this->filterRecordsByType($records);

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

    /**
     * Create a model cache
     * @param int $contentGroupID
     * @return ModelCache
     */
    private function getModelCache(int $contentGroupID): ModelCache
    {
        $cacheNameSpace = "ContentRecordContent-$contentGroupID";
        return new ModelCache($cacheNameSpace, \Gdn::cache(), [
            \Gdn_Cache::FEATURE_EXPIRY => self::CACHE_TTL,
        ]);
    }

    /**
     * Clear all Model cache
     * @param int $contentGroupID
     * @return void
     */
    private function clearAllCache(int $contentGroupID): void
    {
        $modelCache = $this->getModelCache($contentGroupID);
        $modelCache->invalidateAll();
    }

    /**
     * Get content group record contents
     * @param int $contentGroupID
     * @param string $locale
     * @return array
     * @throws ValidationException
     * @throws \Vanilla\Exception\Database\NoResultsException
     */
    public function generateRecordContent(int $contentGroupID, string $locale): array
    {
        $contentGroupRecord = $this->getContentGroupRecordByID($contentGroupID);

        $filteredRecords = $this->filterContentRecords($contentGroupRecord["records"], $locale, false);

        $contentGroupRecord["records"] = $filteredRecords;

        return $contentGroupRecord;
    }

    /**
     * Filter content group record contents to see if they are good to be displayed
     * @param array $contentRecords
     * @param ?string $locale
     * @param bool $cached
     * @return array
     */
    private function filterContentRecords(array $contentRecords, ?string $locale, bool $cached = true): array
    {
        $recordByTypes = [];

        //find the current valid records
        foreach ($this->filterRecordsByType($contentRecords) as $recordType => $records) {
            $provider = $this->providersByType[$recordType] ?? null;
            if (empty($provider)) {
                continue;
            }
            $validRecordIDs = $provider->filterValidRecordIDs(array_column($records, "recordID")) ?? [];
            if (!$cached) {
                $recordByTypes[$recordType] = $provider->getRecords($validRecordIDs, $locale);
            } else {
                $recordByTypes[$recordType] = array_flip($validRecordIDs);
            }
        }

        // filter out records that are currently not valid
        foreach ($contentRecords as $index => $record) {
            if (
                isset($recordByTypes[$record["recordType"]]) &&
                isset($recordByTypes[$record["recordType"]][$record["recordID"]])
            ) {
                if (!empty($recordByTypes[$record["recordType"]][$record["recordID"]])) {
                    $contentRecords[$index]["record"] = $recordByTypes[$record["recordType"]][$record["recordID"]];
                }
            } else {
                unset($contentRecords[$index]);
            }
        }
        return array_values($contentRecords);
    }
}
