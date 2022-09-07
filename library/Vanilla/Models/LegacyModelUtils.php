<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Gdn_Model;

/**
 * Utility functions that operate on models.
 */
final class LegacyModelUtils
{
    public const COUNT_LIMIT_DEFAULT = 100;

    /**
     * Get the crawl information for a model.
     *
     * @param \Gdn_Model $model
     * @param string $url
     * @param string $parameter
     * @return array
     */
    public static function getCrawlInfoFromPrimaryKey(\Gdn_Model $model, string $url, string $parameter): array
    {
        $count = $model->getTotalRowCount();
        $range = $model->SQL
            ->select($model->PrimaryKey, "min", "min")
            ->select($model->PrimaryKey, "max", "max")
            ->get($model->Name)
            ->firstRow(DATASET_TYPE_ARRAY) ?: ["min" => null, "max" => null];

        $r =
            [
                "url" => $url,
                "uniqueIDField" => $parameter,
                "parameter" => $parameter,
                "count" => $count,
            ] + $range;
        return $r;
    }

    /**
     * Converts a field using the new `field, -field` order syntax to a field and direction.
     *
     * @param string $field
     * @return array Returns an array in the form `[$field, $direction]`.
     */
    public static function orderFieldDirection(string $field): array
    {
        if (empty($field)) {
            return ["", ""];
        } elseif ($field[0] === "-") {
            return [substr($field, 1), "desc"];
        } else {
            return [$field, "asc"];
        }
    }

    /**
     * Continuously get the first page of a model until there are no more rows.
     *
     * This method is a little odd, so it's important to understand how it's used. Its main purpose is to delete the
     * rows from a table one at a time. You iterate through the model using this function calling the deletes until they
     * are complete.
     *
     * @param Gdn_Model $model The model to iterate.
     * @param array $where The where clause for the iteration.
     * @param int $limit The number of records per page.
     * @return iterable
     */
    public static function reduceTable(Gdn_Model $model, array $where, int $limit = 50): iterable
    {
        do {
            $rows = $model->getWhere($where, $model->PrimaryKey, "asc", $limit)->resultArray();
            yield from $rows;
        } while (count($rows) >= $limit);
    }

    /**
     * Alters the keys of an array to make it suitable to save to the DB.
     *
     * @param array $row The row to save.
     * @param array $mappings The path mappings to change the keys to the correct column names.
     * @return array
     */
    public static function normalizeApiInput(array $row, array $mappings = []): array
    {
        $result = [];

        foreach ($row as $key => $value) {
            if (isset($mappings[$key])) {
                $result[$mappings[$key]] = $value;
            } else {
                $result[ucfirst($key)] = $value;
            }
        }
        return $result;
    }

    /**
     * Alters the keys of an array to make it suitable for output.
     *
     * @param array $row
     * @param array $mappings
     * @return array
     */
    public static function normalizeApiOutput(array $row, array $mappings = []): array
    {
        $result = [];
        $flippedMap = array_flip($mappings);

        foreach ($row as $key => $value) {
            if (isset($flippedMap[$key])) {
                $result[lcfirst($flippedMap[$key])] = $value;
            } else {
                $result[lcfirst($key)] = $value;
            }
        }
        return $result;
    }

    /**
     * Get the count of matching rows in a table with a hard limit.
     *
     * A simple query is executed to get rows matching the criteria, with an arbitrary limit. This query is used as a
     * subquery to another "count" query that is intended to solely return the limited count from the database server.
     * This method can be useful when a true count is only necessary to a point, before a relative count is acceptable
     * (i.e. "8", "21", "100+").
     *
     * @param Gdn_Model $model
     * @param array $where
     * @param int $limit
     * @return int
     */
    public static function getLimitedCount(Gdn_Model $model, array $where, int $limit = self::COUNT_LIMIT_DEFAULT): int
    {
        $sql = clone $model->SQL;
        $subquery = $sql
            ->reset()
            ->select($model->PrimaryKey)
            ->from($model->Name)
            ->where($where)
            ->limit($limit)
            ->getSelect();

        $query = /** @lang MySQL */ <<<SQL
select count(*) as rowCount
from ($subquery) as subquery
SQL;
        $result = $sql->Database->query($query, $sql->namedParameters())->value("rowCount");
        return $result;
    }
}
