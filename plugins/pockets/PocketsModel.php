<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Addons\Pockets;

/**
 * Model for pockets.
 */
class PocketsModel extends \Gdn_Model {

    /**
     * @inheritdoc
     */
    public function __construct() {
        parent::__construct('Pocket');
    }

    /**
     * @inheritdoc
     */
    public function getID($iD, $options = []) {
        $result =  parent::getID($iD, DATASET_TYPE_ARRAY, $options);
        return $this->expandAttributes($result);
    }

    /**
     * @inheritdoc
     */
    public function save($formPostValues, $settings = false) {
        $row = $this->collapseAttributes($formPostValues);

        return parent::save($row, $settings);
    }

    /**
     * Get all pockets
     *
     * @return array
     */
    public function getAll(): array {
        $rows = $this->SQL->get('Pocket', 'Location, Sort, Name')->resultArray();
        $rows = array_map(function ($row) {
            return $this->expandAttributes($row);
        }, $rows);
        return $rows;
    }
}
