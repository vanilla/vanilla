<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Models;

use DateTimeImmutable;
use Gdn_Cache;
use Vanilla\CurrentTimeStamp;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;

/**
 * A model for handling record
 */
class DirtyRecordModel extends PipelineModel {

    const DIRTY_RECORD_COUNT = 'dirtyRecordCount';

    const DIRTY_RECORD_OPT = 'dirtyRecords';

    /** @var Gdn_Cache */
    private $cache;

    /**
     * DirtyRecordModel constructor.
     *
     * @param Gdn_Cache $cache
     */
    public function __construct(Gdn_Cache $cache) {
        parent::__construct("dirtyRecord");

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"]);
        $this->addPipelineProcessor($dateProcessor);

        $this->cache = $cache;
    }

    /**
     * Overrides the parent insert.
     *
     * @param array $set
     * @param array $options
     */
    public function insert($set, $options = []) {
        $recordType = $set['recordType'] ?? '';
        $recordID =  $set['recordID'] ?? '';

        parent::insert(["recordType" => $recordType, "recordID" => $recordID], [Model::OPT_REPLACE => true]);

        $key = self::DIRTY_RECORD_COUNT."_$recordType";
        $cacheItem = $this->cache->get($key);

        if (!$cacheItem) {
            $this->cache->store($key, 1);
        } else {
            $this->cache->increment($key);
        }
    }

    /**
     * Overrides parent::update
     *
     * @param array $set Field values to set.
     * @param array $where Conditions to restrict the update.
     * @param array $options Update options.
     * @throws \Exception Only use insert method to update records.
     */
    public function update(array $set, array $where, array $options = []): bool {
        throw new \Exception('use insert method to update records');
    }

    /**
     * Get all record ids.
     *
     * @return array
     */
    public function getAllRecordIDs(): array {
        $allRecords = $this->createSql()
            ->from($this->getTable())
            ->select('recordID')
            ->get()->result(DATASET_TYPE_ARRAY);

        return $allRecords;
    }

    /**
     * Clear all records for a given recordType.
     *
     * @param string $recordType
     * @param DateTimeImmutable $time
     */
    public function clearRecordTypes(string $recordType, DateTimeImmutable $time) {
        $this->delete([
            "recordType" => $recordType,
            "dateInserted <" => $time->format(CurrentTimeStamp::MYSQL_DATE_FORMAT)
        ]);
        $key = self::DIRTY_RECORD_COUNT."_$recordType";
        $cacheItem = $this->cache->get($key);
        if ($cacheItem) {
            $this->cache->replace($key, 0);
        }
    }
}
