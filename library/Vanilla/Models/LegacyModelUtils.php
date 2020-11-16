<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

/**
 * Utility functions that operate on models.
 */
final class LegacyModelUtils {
    /**
     * Get the crawl information for a model.
     *
     * @param \Gdn_Model $model
     * @param string $url
     * @param string $parameter
     * @return array
     */
    public static function getCrawlInfoFromPrimaryKey(\Gdn_Model $model, string $url, string $parameter): array {
        $count = $model->getTotalRowCount();
        $range = $model->SQL
            ->select($model->PrimaryKey, 'min', 'min')
            ->select($model->PrimaryKey, 'max', 'max')
            ->get($model->Name)->firstRow(DATASET_TYPE_ARRAY) ?: ['min' => null, 'max' => null];

        $r = [
                'url' => $url,
                'uniqueIDField' => $parameter,
                'parameter' => $parameter,
                'count' => $count,
            ] + $range;
        return $r;
    }

    /**
     * Converts a field using the new `field, -field` order syntax to a field and direction.
     *
     * @param string $field
     * @return array Returns an array in the form `[$field, $direction]`.
     */
    public static function orderFieldDirection(string $field): array {
        if (empty($field)) {
            return ['', ''];
        } elseif ($field[0] === '-') {
            return [substr($field, 1), 'desc'];
        } else {
            return [$field, 'asc'];
        }
    }
}
