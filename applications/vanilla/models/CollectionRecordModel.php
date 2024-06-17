<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Exception;
use UserModel;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;

/**
 * Model for collection records.
 */
class CollectionRecordModel extends PipelineModel
{
    private const TABLE_NAME = "collectionRecord";

    public const LIMIT_DEFAULT = 100;

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME);

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"]);
        $this->addPipelineProcessor($dateProcessor);
    }

    /**
     * Get count or records based on condition
     */
    public function getCount(array $where = []): int
    {
        return $this->createSql()->getCount($this->getTable(), $where);
    }

    /**
     * Iterator to get records based on condition in batches
     *
     * @param array $where
     * @param array $whereOr
     * @param array $orderFields
     * @param string $orderDirection
     * @param int $batchSize
     * @return \Generator
     * @throws Exception
     */
    public function getWhereIterator(
        array $where = [],
        array $whereOr = [],
        array $orderFields = [],
        string $orderDirection = "asc",
        int $batchSize = 100
    ): \Generator {
        $offset = 0;

        $sql = $this->createSql();
        while (true) {
            $sql->select()->from($this->getTable());
            if (!empty($where)) {
                $sql->where($where);
            }
            if (!empty($whereOr)) {
                $sql->beginWhereGroup();
                foreach ($whereOr as $key => $whereOrItem) {
                    if ($key == 0) {
                        $sql->where($whereOrItem);
                    } else {
                        $sql->orWhere($whereOrItem);
                    }
                }
                $sql->endWhereGroup();
            }
            if ($orderFields) {
                $sql->orderBy($orderFields, $orderDirection);
            }
            $records = $sql
                ->limit($batchSize, $offset)
                ->get()
                ->resultArray();

            foreach ($records as $record) {
                $primaryKey = $record["collectionID"] . "_" . $record["recordID"];
                yield $primaryKey => $record;
            }

            $offset += $batchSize;
            if (count($records) < $batchSize) {
                return;
            }
        }
    }

    /**
     * Get collection records based on filter.
     *
     * @param array $where
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getCollectionRecords(array $where = [], int $limit = 100, int $offset = 0): array
    {
        $sql = $this->createSql();
        $readSchemaArray = $this->getReadSchema()->getSchemaArray();
        $columns = array_keys($readSchemaArray["properties"]);
        foreach ($where as $key => $value) {
            $column = explode(" ", $key)[0];
            if (in_array($column, $columns)) {
                $where["cr." . $key] = $value;
                unset($where[$key]);
            }
        }
        return $sql
            ->select()
            ->select("cr.dateInserted as dateAddedToCollection")
            ->from($this->getTable() . " cr")
            ->join("collection c", "c.collectionID = cr.collectionID")
            ->where($where)
            ->limit($limit, $offset)
            ->get()
            ->resultArray();
    }
}
