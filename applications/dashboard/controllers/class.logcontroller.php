<?php
/**
 * Non-activity action logging.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /log endpoint.
 */
class LogController extends DashboardController {

    /** @var array Objects to prep. */
    public $Uses = array('Form', 'LogModel');

    /** @var Gdn_Form */
    public $Form;

    /** @var LogModel */
    public $LogModel;

    /**
     * Confirmation page.
     *
     * @since 2.0.?
     * @access public
     *
     * @param string $Action Type of action.
     * @param array $LogIDs Numeric IDs of items to confirm.
     */
    public function confirm() {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $this->permission(array('Garden.Moderation.Manage', 'Moderation.Spam.Manage', 'Moderation.ModerationQueue.Manage'), false);
        $Action = Gdn::request()->post('Action', false);
        $LogIDs = Gdn::request()->post('IDs', false);

        $this->Form->addHidden('LogIDs', $LogIDs);
       $this->Form->IDPrefix = 'Confirm_';

        if (trim($LogIDs)) {
            $LogIDArray = explode(',', $LogIDs);
        } else {
            $LogIDArray = array();
        }

        // We also want to collect the users from the log.
        $Logs = $this->LogModel->getIDs($LogIDArray);
        $Users = array();
        foreach ($Logs as $Log) {
            $UserID = $Log['RecordUserID'];
            if (!$UserID) {
                continue;
            }
            $Users[$UserID] = array('UserID' => $UserID);
        }
        Gdn::userModel()->joinUsers($Users, array('UserID'));
        $this->setData('Users', $Users);

        $this->setData('Action', $Action);
        $this->setData('ActionUrl', url('/log/'.$Action));
        $this->setData('ItemCount', count($LogIDArray));
        $this->render();
    }

    /**
     * Count log items.
     *
     * @since 2.0.?
     * @access public
     *
     * @param string $Operation Comma-separated ist of action types to find.
     */
    public function count($Operation) {
        // Don't use Gdn_Controller->permission() here because this isn't a "real page".
        if (!Gdn::session()->checkPermission(array('Garden.Moderation.Manage', 'Moderation.Spam.Manage', 'Moderation.ModerationQueue.Manage'), false)) {
            $this->statusCode(403);
            echo '';
            return;
        }

        if ($Operation == 'edits') {
            $Operation = array('edit', 'delete');
        } else {
            $Operation = explode(',', $Operation);
        }
        array_map('ucfirst', $Operation);

        $Count = $this->LogModel->getCountWhere(array('Operation' => $Operation));

        if ($Count > 0) {
            echo '<span class="Alert">', $Count, '</span>';
        }
    }

    /**
     * Delete logs.
     *
     * @since 2.0.?
     * @access public
     *
     * @param array $LogIDs Numeric IDs of logs to delete.
     */
    public function delete() {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $this->permission(array('Garden.Moderation.Manage', 'Moderation.ModerationQueue.Manage'), false);
        $LogIDs = Gdn::request()->post('LogIDs');
        $this->LogModel->delete($LogIDs);
        $this->render('Blank', 'Utility');
    }

    /**
     * Delete spam and optionally delete the users.
     * @param type $LogIDs
     */
    public function deleteSpam() {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $this->permission(array('Garden.Moderation.Manage', 'Moderation.Spam.Manage'), false);
        $LogIDs = Gdn::request()->post('LogIDs');
        $LogIDs = explode(',', $LogIDs);

        // Ban the appropriate users.
        $UserIDs = $this->Form->getFormValue('UserID', array());
        if (!is_array($UserIDs)) {
            $UserIDs = array();
        }

        if (!empty($UserIDs)) {
            // Grab the rest of the log entries.
            $OtherLogIDs = $this->LogModel->getWhere(array('Operation' => 'Spam', 'RecordUserID' => $UserIDs));
            $OtherLogIDs = array_column($OtherLogIDs, 'LogID');
            $LogIDs = array_merge($LogIDs, $OtherLogIDs);

            foreach ($UserIDs as $UserID) {
                Gdn::userModel()->ban($UserID, array('Reason' => 'Spam', 'DeleteContent' => true, 'Log' => true));
            }
        }

        // Grab the logs.
        $this->LogModel->delete($LogIDs);
        $this->render('Blank', 'Utility');
    }

    /**
     * View list of edits (edit/delete actions).
     *
     * @since 2.0.?
     * @access public
     *
     * @param int $Page Page number.
     */
    public function edits($Type = '', $Page = '', $Op = false) {
        $this->permission('Garden.Moderation.Manage');
        list($Offset, $Limit) = offsetLimit($Page, 10);
        $this->setData('Title', t('Change Log'));

        $Operations = array('Edit', 'Delete', 'Ban');
        if ($Op && in_array(ucfirst($Op), $Operations)) {
            $Operations = ucfirst($Op);
        }

        $Where = array(
            'Operation' => $Operations//,
//          'RecordType' => array('Discussion', 'Comment', 'Activity')
        );

        $AllowedTypes = array('Discussion', 'Comment', 'Activity', 'User');

        $Type = strtolower($Type);
        if ($Type == 'configuration') {
            $this->permission('Garden.Settings.Manage');
            $Where['RecordType'] = array('Configuration');
        } else {
            if (in_array(ucfirst($Type), $AllowedTypes)) {
                $Where['RecordType'] = ucfirst($Type);
            } else {
                $Where['RecordType'] = $AllowedTypes;
            }
        }

        $RecordCount = $this->LogModel->getCountWhere($Where);
        $this->setData('RecordCount', $RecordCount);
        if ($Offset >= $RecordCount) {
            $Offset = $RecordCount - $Limit;
        }

        $Log = $this->LogModel->getWhere($Where, 'LogID', 'Desc', $Offset, $Limit);
        $this->setData('Log', $Log);

        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = 'Table';
        }

        $this->addSideMenu('dashboard/log/edits');
        $this->render();
    }

    /**
     * Access the log history of a specific record
     *
     * @param string $RecordType
     * @param int $RecordID
     */
    public function record($RecordType, $RecordID, $Page = '') {
        $this->permission('Garden.Moderation.Manage');
        list($Offset, $Limit) = offsetLimit($Page, 10);
        $this->setData('Title', t('Change Log'));

        $RecordType = ucfirst($RecordType);
        $Where = array(
            'Operation' => array('Edit', 'Delete', 'Ban'),
            'RecordType' => $RecordType,
            'RecordID' => $RecordID
        );

        $RecordCount = $this->LogModel->getCountWhere($Where);
        $this->setData('RecordCount', $RecordCount);
        if ($Offset >= $RecordCount) {
            $Offset = $RecordCount - $Limit;
        }

        $Log = $this->LogModel->getWhere($Where, 'LogID', 'Desc', $Offset, $Limit);
        $this->setData('Log', $Log);

        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = 'Table';
        }

        $this->addSideMenu('dashboard/log/edits');
        $this->render();
    }


    /**
     * Searches the logs for edit, delete or ban operations on posts made by the user with the given user ID.
     *
     * @param $recordUserID The user ID to search the logs for.
     * @param string $Page The page number.
     * @throws Exception
     */
    public function user($recordUserID, $Page = '') {
        if (!is_numeric($recordUserID)) {
            throw new Exception('Invalid ID');
        }
        $this->permission('Garden.Moderation.Manage');
        list($Offset, $Limit) = offsetLimit($Page, 10);
        $this->setData('Title', t('Change Log by User'));

        $Where = array(
            'Operation' => array('Edit', 'Delete', 'Ban'),
            'RecordUserID' => $recordUserID
        );

        $RecordCount = $this->LogModel->getCountWhere($Where);
        $this->setData('RecordCount', $RecordCount);
        if ($Offset >= $RecordCount) {
            $Offset = $RecordCount - $Limit;
        }

        $Log = $this->LogModel->getWhere($Where, 'LogID', 'Desc', $Offset, $Limit);
        $this->setData('Log', $Log);

        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = 'Table';
        }

        $this->addSideMenu('dashboard/log/edits');
        $this->render('record');
    }

    /**
     * Convenience method to call model's FormatContent.
     *
     * @since 2.0.?
     * @access protected
     *
     * @param object $Log .
     */
    protected function formatContent($Log) {
        return $this->LogModel->formatContent($Log);
    }

    /**
     * Always triggered first. Add Javascript files.
     *
     * @since 2.0.?
     * @access public
     */
    public function initialize() {
        parent::initialize();
        Gdn_Theme::section('Dashboard');
        $this->addJsFile('log.js');
        $this->addJsFile('jquery.expander.js');
        $this->addJsFile('jquery-ui.js');
    }

    /**
     * View moderation logs.
     *
     * @since 2.0.?
     * @access public
     *
     * @param mixed $CategoryUrl Slug.
     * @param int $Page Page number.
     */
    public function moderation($Page = '') {
        $this->permission(array('Garden.Moderation.Manage', 'Moderation.ModerationQueue.Manage'), false);

        $Where = array('Operation' => array('Moderate', 'Pending'));

        // Filter by category menu
        if ($CategoryID = Gdn::request()->getValue('CategoryID')) {
            $this->setData('ModerationCategoryID', $CategoryID);
            $Where['CategoryID'] = $CategoryID;
        }

        list($Offset, $Limit) = offsetLimit($Page, 10);
        $this->setData('Title', t('Moderation Queue'));

        $RecordCount = $this->LogModel->getCountWhere($Where);
        $this->setData('RecordCount', $RecordCount);
        if ($Offset >= $RecordCount) {
            $Offset = $RecordCount - $Limit;
        }

        $Log = $this->LogModel->getWhere($Where, 'LogID', 'Desc', $Offset, $Limit);
        $this->setData('Log', $Log);

        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = 'Table';
        }

        $this->addSideMenu('dashboard/log/moderation');
        $this->render();
    }

    /**
     * Restore logs.
     *
     * @since 2.0.?
     * @access public
     *
     * @param array $LogIDs List of log IDs.
     */
    public function restore() {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $this->permission(array('Garden.Moderation.Manage', 'Moderation.Spam.Manage', 'Moderation.ModerationQueue.Manage'), false);
        $LogIDs = Gdn::request()->post('LogIDs');

        // Grab the logs.
        $Logs = $this->LogModel->getIDs($LogIDs);
        try {
            foreach ($Logs as $Log) {
                $this->LogModel->restore($Log);
            }
        } catch (Exception $Ex) {
            $this->Form->addError($Ex->getMessage());
        }
        $this->LogModel->recalculate();
        $this->render('Blank', 'Utility');
    }

    public function notSpam() {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $this->permission(array('Garden.Moderation.Manage', 'Moderation.Spam.Manage'), false);
        $LogIDs = Gdn::request()->post('LogIDs');

        $Logs = array();

        // Verify the appropriate users.
        $UserIDs = $this->Form->getFormValue('UserID', array());
        if (!is_array($UserIDs)) {
            $UserIDs = array();
        }

        foreach ($UserIDs as $UserID) {
            Gdn::userModel()->setField($UserID, 'Verified', true);
            $Logs = array_merge($Logs, $this->LogModel->getWhere(array('Operation' => 'Spam', 'RecordUserID' => $UserID)));
        }

        // Grab the logs.
        $Logs = array_merge($Logs, $this->LogModel->getIDs($LogIDs));

//      try {
        foreach ($Logs as $Log) {
            $this->LogModel->restore($Log);
        }
//      } catch (Exception $Ex) {
//         $this->Form->addError($Ex->getMessage());
//      }
        $this->LogModel->recalculate();

        $this->setData('Complete');
        $this->setData('Count', count($Logs));
        $this->render('Blank', 'Utility');
    }

    /**
     * View spam logs.
     *
     * @since 2.0.?
     * @access public
     *
     * @param int $Page Page number.
     */
    public function spam($Page = '') {
        $this->permission(array('Garden.Moderation.Manage', 'Moderation.Spam.Manage'), false);
        list($Offset, $Limit) = offsetLimit($Page, 10);
        $this->setData('Title', t('Spam Queue'));

        $Where = array('Operation' => array('Spam'));

        $RecordCount = $this->LogModel->getCountWhere($Where);
        $this->setData('RecordCount', $RecordCount);
        if ($Offset >= $RecordCount) {
            $Offset = $RecordCount - $Limit;
        }

        $Log = $this->LogModel->getWhere($Where, 'LogID', 'Desc', $Offset, $Limit);
        $this->setData('Log', $Log);

        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = 'Table';
        }

        $this->addSideMenu('dashboard/log/spam');
        $this->render();
    }
}
