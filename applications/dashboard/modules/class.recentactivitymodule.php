<?php
/**
 * Recent activity module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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

    public function getData($limit = false) {
        if (!$limit) {
            $limit = $this->Limit;
        }

        $activityModel = new ActivityModel();
        $data = $activityModel->getWhere(['NotifyUserID' => ActivityModel::NOTIFY_PUBLIC], '', '', $limit, 0);
        $this->ActivityData = $data;
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

        $data = $this->ActivityData;
        if (is_object($data) && $data->numRows() > 0) {
            return parent::toString();
        }

        return '';
    }
}
