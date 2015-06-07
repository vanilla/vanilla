<?php
/**
 * Regarding model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
     * @param mixed $RegardingID
     * @return array|bool|stdClass
     */
    public function getID($RegardingID) {
        $Regarding = $this->getWhere(array('RegardingID' => $RegardingID))->firstRow();
        return $Regarding;
    }

    /**
     *
     *
     * @param string|unknown_type $ForeignType
     * @param string|unknown_type $ForeignID
     * @return array|bool|stdClass
     */
    public function get($ForeignType, $ForeignID) {
        return $this->getWhere(array(
            'ForeignType' => $ForeignType,
            'ForeignID' => $ForeignID
        ))->firstRow(DATASET_TYPE_ARRAY);
    }

    /**
     *
     *
     * @param $Type
     * @param $ForeignType
     * @param $ForeignID
     * @return array|bool|stdClass
     */
    public function getRelated($Type, $ForeignType, $ForeignID) {
        return $this->getWhere(array(
            'Type' => $Type,
            'ForeignType' => $ForeignType,
            'ForeignID' => $ForeignID
        ))->firstRow(DATASET_TYPE_ARRAY);
    }

    /**
     *
     *
     * @param $ForeignType
     * @param array $ForeignIDs
     * @return Gdn_DataSet
     */
    public function getAll($ForeignType, $ForeignIDs = array()) {
        if (count($ForeignIDs) == 0) {
            return new Gdn_DataSet(array());
        }

        return Gdn::sql()->select('*')
            ->from('Regarding')
            ->where('ForeignType', $ForeignType)
            ->whereIn('ForeignID', $ForeignIDs)
            ->get();
    }
}
