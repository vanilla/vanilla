<?php
/**
 * Regarding model.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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
     *
     *
     * @param mixed $regardingID
     * @return array|bool|stdClass
     */
    public function getID($regardingID) {
        $regarding = $this->getWhere(['RegardingID' => $regardingID])->firstRow();
        return $regarding;
    }

    /**
     *
     *
     * @param string|unknown_type $foreignType
     * @param string|unknown_type $foreignID
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
