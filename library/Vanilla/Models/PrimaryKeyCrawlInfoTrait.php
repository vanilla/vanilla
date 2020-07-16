<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Vanilla\Models;

/**
 * Apply this trait to a `Model` subclass to add a utility method for getting crawl information.
 */
trait PrimaryKeyCrawlInfoTrait {
    /**
     * Get the approximate total row count of the model's table.
     *
     * @return int
     */
    protected function getTotalRowCount(): int {
        $database = $this->createSql()->Database;

        /** @var \Gdn_DataSet $data */
        $data = $database->query(
            'show table status like '.$database->connection()->quote($database->DatabasePrefix.$this->getTable()),
            [],
            ['ReturnType' => 'DataSet']
        );

        return $data->value('Rows', 0);
    }

    /**
     * Get the crawl information for a model.
     *
     * @param string $url
     * @param string $parameter
     * @return array
     */
    protected function getCrawlInfoFromPrimaryKey(string $url, string $parameter): array {
        $pk = $this->getPrimaryKey();
        if (count($pk) !== 1) {
            throw new \InvalidArgumentException(__METHOD__.' is only valid on models with one primary key column.', 500);
        }
        $pk = array_pop($pk);

        $count = $this->getTotalRowCount();
        $range = $this->createSql()
            ->select($pk, 'min', 'min')
            ->select($pk, 'max', 'max')
            ->get($this->getTable())->firstRow(DATASET_TYPE_ARRAY) ?: ['min' => null, 'max' => null];

        $r = [
                'url' => $url,
                'parameter' => $parameter,
                'count' => $count,
            ] + $range;
        return $r;
    }
}
