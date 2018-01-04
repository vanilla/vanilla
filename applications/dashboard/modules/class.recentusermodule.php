<?php
/**
 * Recent user module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Renders the recently active users. Built for use in a side panel.
 */
class RecentUserModule extends Gdn_Module {

    public function __construct($sender = '') {
        parent::__construct($sender);
    }

    public function getData($limit = 20) {
        $userModel = new UserModel();
        $this->_Sender->RecentUserData = $userModel->getActiveUsers($limit);
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        if (!c('Garden.Modules.ShowRecentUserModule')) {
            return '';
        }

        $data = $this->_Sender->RecentUserData;
        if ($data !== false && $data->numRows() > 0) {
            return parent::toString();
        }

        return '';
    }
}
