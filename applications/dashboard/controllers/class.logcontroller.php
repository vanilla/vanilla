<?php
/**
 * Non-activity action logging.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /log endpoint.
 */
class LogController extends DashboardController {

    /** @var array Objects to prep. */
    public $Uses = ['Form', 'LogModel'];

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
     * @param string $action Type of action.
     * @param array $logIDs Numeric IDs of items to confirm.
     */
    public function confirm() {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $this->permission(['Garden.Moderation.Manage', 'Moderation.Spam.Manage', 'Moderation.ModerationQueue.Manage'], false);
        $action = Gdn::request()->post('Action', false);
        $logIDs = Gdn::request()->post('IDs', false);

        $this->Form->addHidden('LogIDs', $logIDs);
       $this->Form->IDPrefix = 'Confirm_';

        if (trim($logIDs)) {
            $logIDArray = explode(',', $logIDs);
        } else {
            $logIDArray = [];
        }

        // We also want to collect the users from the log.
        $logs = $this->LogModel->getIDs($logIDArray);
        $users = [];
        foreach ($logs as $log) {
            $userID = $log['RecordUserID'];
            if (!$userID) {
                continue;
            }
            $users[$userID] = ['UserID' => $userID];
        }
        Gdn::userModel()->joinUsers($users, ['UserID']);
        $this->setData('Users', $users);

        $this->setData('Action', $action);
        $this->setData('ActionUrl', url('/log/'.$action));
        $this->setData('ItemCount', count($logIDArray));
        $this->render();
    }

    /**
     * Count log items.
     *
     * @since 2.0.?
     * @access public
     *
     * @param string $operation Comma-separated ist of action types to find.
     */
    public function count($operation) {
        // Don't use Gdn_Controller->permission() here because this isn't a "real page".
        if (!Gdn::session()->checkPermission(['Garden.Moderation.Manage', 'Moderation.Spam.Manage', 'Moderation.ModerationQueue.Manage'], false)) {
            $this->statusCode(403);
            echo '';
            return;
        }

        if ($operation == 'edits') {
            $operation = ['edit', 'delete'];
        } else {
            $operation = explode(',', $operation);
        }
        array_map('ucfirst', $operation);

        $count = $this->LogModel->getCountWhere(['Operation' => $operation]);

        if ($count > 0) {
            echo '<span class="Alert">', $count, '</span>';
        }
    }

    /**
     * Delete logs.
     *
     * @since 2.0.?
     * @access public
     *
     * @param array $logIDs Numeric IDs of logs to delete.
     */
    public function delete() {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $this->permission(['Garden.Moderation.Manage', 'Moderation.ModerationQueue.Manage'], false);
        $logIDs = Gdn::request()->post('LogIDs');
        $this->LogModel->deleteIDs($logIDs);
        $this->render('Blank', 'Utility');
    }

    /**
     * Delete spam and optionally delete the users.
     * @param type $logIDs
     */
    public function deleteSpam() {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $this->permission(['Garden.Moderation.Manage', 'Moderation.Spam.Manage'], false);
        $logIDs = Gdn::request()->post('LogIDs');
        $logIDs = explode(',', $logIDs);

        // Ban the appropriate users.
        $userIDs = $this->Form->getFormValue('UserID', []);
        if (!is_array($userIDs)) {
            $userIDs = [];
        }

        if (!empty($userIDs)) {
            // Grab the rest of the log entries.
            $otherLogIDs = $this->LogModel->getWhere(['Operation' => 'Spam', 'RecordUserID' => $userIDs]);
            $otherLogIDs = array_column($otherLogIDs, 'LogID');
            $logIDs = array_merge($logIDs, $otherLogIDs);

            foreach ($userIDs as $userID) {
                Gdn::userModel()->ban($userID, ['Reason' => 'Spam', 'DeleteContent' => true, 'Log' => true]);
            }
        }

        // Grab the logs.
        $this->LogModel->deleteIDs($logIDs);
        $this->render('Blank', 'Utility');
    }

    /**
     * View list of edits (edit/delete actions).
     *
     * @since 2.0.?
     * @access public
     *
     * @param int $page Page number.
     */
    public function edits($type = '', $page = '', $op = false) {
        $this->permission('Garden.Moderation.Manage');
        list($offset, $limit) = offsetLimit($page, 10);
        $this->setData('Title', t('Change Log'));

        $operations = ['Edit', 'Delete', 'Ban'];
        if ($op && in_array(ucfirst($op), $operations)) {
            $operations = ucfirst($op);
        }

        $where = [
            'Operation' => $operations//,
//          'RecordType' => array('Discussion', 'Comment', 'Activity')
        ];

        $allowedTypes = ['Discussion', 'Comment', 'Activity', 'User'];

        $type = strtolower($type);
        if ($type == 'configuration') {
            $this->permission('Garden.Settings.Manage');
            $where['RecordType'] = ['Configuration'];
        } else {
            if (in_array(ucfirst($type), $allowedTypes)) {
                $where['RecordType'] = ucfirst($type);
            } else {
                $where['RecordType'] = $allowedTypes;
            }
        }

        $recordCount = $this->LogModel->getCountWhere($where);
        $this->setData('RecordCount', $recordCount);
        if ($offset >= $recordCount) {
            $offset = $recordCount - $limit;
        }

        $log = $this->LogModel->getWhere($where, 'LogID', 'Desc', $offset, $limit);
        $this->setData('Log', $log);

        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = 'Table';
        }

        Gdn_Theme::section('Moderation');
        $this->setHighlightRoute('dashboard/log/edits');
        $this->render();
    }

    /**
     * Access the log history of a specific record
     *
     * @param string $recordType
     * @param int $recordID
     */
    public function record($recordType, $recordID, $page = '') {
        $this->permission('Garden.Moderation.Manage');
        list($offset, $limit) = offsetLimit($page, 10);
        $this->setData('Title', t('Change Log'));

        $recordType = ucfirst($recordType);
        $where = [
            'Operation' => ['Edit', 'Delete', 'Ban'],
            'RecordType' => $recordType,
            'RecordID' => $recordID
        ];

        $recordCount = $this->LogModel->getCountWhere($where);
        $this->setData('RecordCount', $recordCount);
        if ($offset >= $recordCount) {
            $offset = $recordCount - $limit;
        }

        $log = $this->LogModel->getWhere($where, 'LogID', 'Desc', $offset, $limit);
        $this->setData('Log', $log);

        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = 'Table';
        }

        Gdn_Theme::section('Moderation');
        $this->setHighlightRoute('dashboard/log/edits');
        $this->render();
    }


    /**
     * Searches the logs for edit, delete or ban operations on posts made by the user with the given user ID.
     *
     * @param $recordUserID The user ID to search the logs for.
     * @param string $page The page number.
     * @throws Exception
     */
    public function user($recordUserID, $page = '') {
        if (!is_numeric($recordUserID)) {
            throw new Exception('Invalid ID');
        }
        $this->permission('Garden.Moderation.Manage');
        list($offset, $limit) = offsetLimit($page, 10);
        $this->setData('Title', t('Change Log by User'));

        $where = [
            'Operation' => ['Edit', 'Delete', 'Ban'],
            'RecordUserID' => $recordUserID
        ];

        $recordCount = $this->LogModel->getCountWhere($where);
        $this->setData('RecordCount', $recordCount);
        if ($offset >= $recordCount) {
            $offset = $recordCount - $limit;
        }

        $log = $this->LogModel->getWhere($where, 'LogID', 'Desc', $offset, $limit);
        $this->setData('Log', $log);

        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = 'Table';
        }

        Gdn_Theme::section('Moderation');
        $this->setHighlightRoute('dashboard/log/edits');
        $this->render('record');
    }

    /**
     * Convenience method to call model's FormatContent.
     *
     * @since 2.0.?
     * @access protected
     *
     * @param object $log .
     */
    protected function formatContent($log) {
        return $this->LogModel->formatContent($log);
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
        $this->addJsFile('jquery-ui.min.js');
        $this->addJsFile('jquery.popup.js');
    }

    /**
     * View moderation logs.
     *
     * @since 2.0.?
     * @access public
     *
     * @param mixed $CategoryUrl Slug.
     * @param int $page Page number.
     */
    public function moderation($page = '') {
        $this->permission(['Garden.Moderation.Manage', 'Moderation.ModerationQueue.Manage'], false);

        $where = ['Operation' => ['Moderate', 'Pending']];

        // Filter by category menu
        if ($categoryID = Gdn::request()->getValue('CategoryID')) {
            $this->setData('ModerationCategoryID', $categoryID);
            $where['CategoryID'] = $categoryID;
        }

        list($offset, $limit) = offsetLimit($page, 10);
        $this->setData('Title', t('Moderation Queue'));

        $recordCount = $this->LogModel->getCountWhere($where);
        $this->setData('RecordCount', $recordCount);
        if ($offset >= $recordCount) {
            $offset = $recordCount - $limit;
        }

        $log = $this->LogModel->getWhere($where, 'LogID', 'Desc', $offset, $limit);
        $this->setData('Log', $log);

        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = 'Table';
        }

        Gdn_Theme::section('Moderation');
        $this->setHighlightRoute('dashboard/log/moderation');
        $this->render();
    }

    /**
     * Restore logs.
     *
     * @since 2.0.?
     * @access public
     *
     * @param array $logIDs List of log IDs.
     */
    public function restore() {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $this->permission(['Garden.Moderation.Manage', 'Moderation.Spam.Manage', 'Moderation.ModerationQueue.Manage'], false);
        $logIDs = Gdn::request()->post('LogIDs');

        // Grab the logs.
        $logs = $this->LogModel->getIDs($logIDs);
        try {
            foreach ($logs as $log) {
                $this->LogModel->restore($log);
            }
        } catch (Exception $ex) {
            $this->Form->addError($ex->getMessage());
        }
        $this->LogModel->recalculate();
        $this->render('Blank', 'Utility');
    }

    public function notSpam() {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $this->permission(['Garden.Moderation.Manage', 'Moderation.Spam.Manage'], false);
        $logIDs = Gdn::request()->post('LogIDs');

        $logs = [];

        // Verify the appropriate users.
        $userIDs = $this->Form->getFormValue('UserID', []);
        if (!is_array($userIDs)) {
            $userIDs = [];
        }

        foreach ($userIDs as $userID) {
            Gdn::userModel()->setField($userID, 'Verified', true);
            $logs = array_merge($logs, $this->LogModel->getWhere(['Operation' => 'Spam', 'RecordUserID' => $userID]));
        }

        // Grab the logs.
        $logs = array_merge($logs, $this->LogModel->getIDs($logIDs));

//      try {
        foreach ($logs as $log) {
            $this->LogModel->restore($log);
        }
//      } catch (Exception $Ex) {
//         $this->Form->addError($Ex->getMessage());
//      }
        $this->LogModel->recalculate();

        $this->setData('Complete');
        $this->setData('Count', count($logs));
        $this->render('Blank', 'Utility');
    }

    /**
     * View spam logs.
     *
     * @since 2.0.?
     * @access public
     *
     * @param int $page Page number.
     */
    public function spam($page = '') {
        $this->permission(['Garden.Moderation.Manage', 'Moderation.Spam.Manage'], false);
        list($offset, $limit) = offsetLimit($page, 10);
        $this->setData('Title', t('Spam Queue'));

        $where = ['Operation' => ['Spam']];

        $recordCount = $this->LogModel->getCountWhere($where);
        $this->setData('RecordCount', $recordCount);
        if ($offset >= $recordCount) {
            $offset = $recordCount - $limit;
        }

        $log = $this->LogModel->getWhere($where, 'LogID', 'Desc', $offset, $limit);
        $this->setData('Log', $log);

        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = 'Table';
        }

        Gdn_Theme::section('Moderation');
        $this->setHighlightRoute('dashboard/log/spam');
        $this->render();
    }
}
