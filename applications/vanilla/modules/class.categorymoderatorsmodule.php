<?php
/**
 * Category Moderators module
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Renders the moderators in the specified category. Built for use in a side panel.
 */
class CategoryModeratorsModule extends Gdn_Module {

    public function __construct($Sender = '') {
        parent::__construct($Sender);
        $this->ModeratorData = false;
    }

    public function getData($Category) {
        $this->ModeratorData = array($Category);
        CategoryModel::JoinModerators($this->ModeratorData);
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        if (is_array($this->ModeratorData)
            && count($this->ModeratorData) > 0
            && is_array($this->ModeratorData[0]->Moderators)
            && count($this->ModeratorData[0]->Moderators) > 0
        ) {
            return parent::ToString();
        }

        return '';
    }
}
