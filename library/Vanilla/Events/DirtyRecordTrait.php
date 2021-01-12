<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Events;

use Gdn_SQLDriver;
use Vanilla\Models\DirtyRecordModel;

/**
 * Trait DirtyRecordTrait
 *
 * @package Vanilla\Events
 */
trait DirtyRecordTrait {

    /**
     * Add dirtyRecord join and where clause.
     *
     * @param Gdn_SQLDriver $sql
     * @param array|string $primaryKey
     * @param string $type
     * @param string $prefix
     */
    public function joinDirtyRecordTable(Gdn_SQLDriver $sql, $primaryKey, string $type, string $prefix = '') {
        $primaryKey = $this->transformPrimaryKey($primaryKey, $prefix);
        $sql->join("dirtyRecord dr", "$primaryKey = dr.recordID", "right");
        $sql->where(["dr.recordType" => $type]);
    }

    /**
     * Add a recordType to the dirtyRecord table to process later.
     *
     * @param string $recordType
     * @param int $recordID
     */
    public function addDirtyRecord(string $recordType, int $recordID) {
        /** @var DirtyRecordModel $dirtyRecordModel */
        $dirtyRecordModel = \Gdn::getContainer()->get(DirtyRecordModel::class);
        $set = [
            'recordType' => $recordType,
            'recordID' => $recordID,
        ];

        try {
            $dirtyRecordModel->insert($set);
        } catch (\Exception $e) {
            trigger_error(
                "Unable to insert new dirtyRecord for recordType: $recordType, recordID: $recordID",
                E_USER_NOTICE
            );
        }
    }

    /**
     * Get join params for SQLDriver->join()
     *
     * @param string $table
     * @param array|string $primaryKey
     * @param string $prefix
     *
     * @return array
     */
    public function getDirtyRecordJoinParams(string $table, $primaryKey, $prefix = ''): array {
        $primaryKey = $this->transformPrimaryKey($primaryKey, $prefix);

        return [
            "tableName" => "dirtyRecord dr",
            "on" => "$primaryKey = dr.recordID",
            "join" => "right"
        ];
    }

    /**
     * Transform primary key for query.
     *
     * @param array|string $primaryKey
     * @param string $prefix
     *
     * @return string
     */
    public function transformPrimaryKey($primaryKey, string $prefix) {
        $primaryKey = is_array($primaryKey) ? reset($primaryKey) : $primaryKey;
        $primaryKey = $prefix ? "$prefix.$primaryKey" : $primaryKey;

        return $primaryKey;
    }
}
