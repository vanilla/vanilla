<?php
/**
 * Recent user module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Renders the recently active users. Built for use in a side panel.
 */
class RecentUserModule extends Gdn_Module {

    public function __construct($Sender = '') {
        parent::__construct($Sender);
    }

    public function getData($Limit = 20) {
        $UserModel = new UserModel();
        $this->_Sender->RecentUserData = $UserModel->GetActiveUsers($Limit);
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        if (!c('Garden.Modules.ShowRecentUserModule')) {
            return '';
        }

        $Data = $this->_Sender->RecentUserData;
        if ($Data !== false && $Data->numRows() > 0) {
            return parent::ToString();
        }

        return '';
    }
}
