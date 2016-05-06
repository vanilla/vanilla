<?php
/**
 * Recent activity module.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Renders the 5 most recent activities for use in a side panel.
 */
class RecentActivityModule extends Gdn_Module {

    /**
     * @var Gdn_DataSet|null
     */
    public $ActivityData = null;

    public $ActivityModuleTitle = '';

    public $Limit = 5;

    public function getData($Limit = false) {
        if (!$Limit) {
            $Limit = $this->Limit;
        }

        $ActivityModel = new ActivityModel();
        $Data = $ActivityModel->getWhere(array('NotifyUserID' => ActivityModel::NOTIFY_PUBLIC), '', '', $Limit, 0);
        $this->ActivityData = $Data;
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        if (!Gdn::session()->checkPermission('Garden.Activity.View')) {
            return '';
        }

        if (stringIsNullOrEmpty($this->ActivityModuleTitle)) {
            $this->ActivityModuleTitle = t('Recent Activity');
        }

        if (!$this->ActivityData) {
            $this->getData();
        }

        $Data = $this->ActivityData;
        if (is_object($Data) && $Data->numRows() > 0) {
            return parent::toString();
        }

        return '';
    }
}
