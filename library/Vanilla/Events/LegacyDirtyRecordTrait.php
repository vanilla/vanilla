<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Events;

/**
 * Trait for fetching dirty records on old models.
 */
trait LegacyDirtyRecordTrait
{
    use DirtyRecordTrait;

    /**
     * Get the model instance.
     *
     * @return \Gdn_Model
     */
    abstract protected function getLegacyModel(): \Gdn_Model;

    /**
     * Get recordSet joined with the dirtyRecords table.
     *
     * @param string $prefix
     */
    public function applyDirtyWheres(string $prefix = "", \Gdn_SQLDriver $sql = null)
    {
        $model = $this->getLegacyModel();
        $type = strtolower($model->Name);
        $sql = $sql ?? $model->SQL;
        $this->joinDirtyRecordTable($sql, $model->PrimaryKey, $type, $prefix);
        $sql->where(["dr.recordType" => $type]);
    }
}
