<?php
/**
 * Regarding model.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles regarding data.
 */
class RegardingModel extends Gdn_Model {

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('Regarding');
    }

    /**
     * Get a single record by ID.
     *
     * @param mixed $regardingID
     * @param string|false $datasetType
     * @param array $options
     * @return array|bool|stdClass
     */
    public function getID($regardingID, $datasetType = false, $options = []) {
        $regarding = $this->getWhere(['RegardingID' => $regardingID])->firstRow();
        return $regarding;
    }

    /**
     * Get everything for the foreign ID.
     *
     * @param string $foreignType
     * @param string $foreignID
     * @return array|bool|stdClass
     */
    public function get($foreignType, $foreignID) {
        return $this->getWhere([
            'ForeignType' => $foreignType,
            'ForeignID' => $foreignID
        ])->firstRow(DATASET_TYPE_ARRAY);
    }

    /**
     *
     *
     * @param $type
     * @param $foreignType
     * @param $foreignID
     * @return array|bool|stdClass
     */
    public function getRelated($type, $foreignType, $foreignID) {
        return $this->getWhere([
            'Type' => $type,
            'ForeignType' => $foreignType,
            'ForeignID' => $foreignID
        ])->firstRow(DATASET_TYPE_ARRAY);
    }

    /**
     *
     *
     * @param $foreignType
     * @param array $foreignIDs
     * @return Gdn_DataSet
     */
    public function getAll($foreignType, $foreignIDs = []) {
        if (count($foreignIDs) == 0) {
            return new Gdn_DataSet([]);
        }

        return Gdn::sql()->select('*')
            ->from('Regarding')
            ->where('ForeignType', $foreignType)
            ->whereIn('ForeignID', $foreignIDs)
            ->get();
    }
}
