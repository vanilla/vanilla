<?php
/**
 * Recent activity module.
 *
 * @copyright 2008-2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Renders the 5 most recent activities for use in a side panel.
 */
class RecentActivityModule extends Gdn_Module {

    public $ActivityData = FALSE;

    public $ActivityModuleTitle = '';

    public $Limit = 5;

    public function GetData($Limit = FALSE) {
        if (!$Limit)
            $Limit = $this->Limit;

        $ActivityModel = new ActivityModel();
        $Data = $ActivityModel->GetWhere(array('NotifyUserID' => ActivityModel::NOTIFY_PUBLIC), 0, $Limit);
        $this->ActivityData = $Data;
    }

    public function AssetTarget() {
        return 'Panel';
    }

    public function ToString() {
        if (!Gdn::Session()->CheckPermission('Garden.Activity.View'))
            return '';

        if (StringIsNullOrEmpty($this->ActivityModuleTitle))
            $this->ActivityModuleTitle = T('Recent Activity');

        if (!$this->ActivityData)
            $this->GetData();

        $Data = $this->ActivityData;
        if (is_object($Data) && $Data->NumRows() > 0)
            return parent::ToString();

        return '';
    }
}
