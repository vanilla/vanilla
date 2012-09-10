<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @package Dashboard
 */

/**
 * Non-activity action logging.
 *
 * @since 2.0.0
 * @package Dashboard
 */
class LogController extends DashboardController {
   /** @var array Objects to prep. */
   public $Uses = array('Form', 'LogModel');
   
   /**
    *
    * @var Gdn_Form 
    */
   public $Form;
   
   /**
    *
    * @var LogModel
    */
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
   public function Confirm($Action, $LogIDs = '') {
      $this->Permission('Garden.Moderation.Manage');
      
      $this->Form->InputPrefix = '';
      $this->Form->IDPrefix = 'Confirm_';

      if (trim($LogIDs))
         $LogIDArray = explode(',', $LogIDs);
      else
         $LogIDArray = array();
      
      // We also want to collect the users from the log.
      $Logs = $this->LogModel->GetIDs($LogIDArray);
      $Users = array();
      foreach ($Logs as $Log) {
         $UserID = $Log['RecordUserID'];
         if (!$UserID)
            continue;
         $Users[$UserID] = array('UserID' => $UserID);
      }
      Gdn::UserModel()->JoinUsers($Users, array('UserID'));
      $this->SetData('Users', $Users);

      $this->SetData('Action', $Action);
      $this->SetData('ActionUrl', Url("/log/$Action?logids=".urlencode($LogIDs)));
      $this->SetData('ItemCount', count($LogIDArray));

      $this->Render();
   }
   
   /**
    * Count log items.
    *
    * @since 2.0.?
    * @access public
    *
    * @param string $Operation Comma-separated ist of action types to find.
    */
   public function Count($Operation) {
      $this->Permission('Garden.Moderation.Manage');

      if ($Operation == 'edits')
         $Operation = array('edit', 'delete');
      else
         $Operation = explode(',', $Operation);
      array_map('ucfirst', $Operation);

      $Count = $this->LogModel->GetCountWhere(array('Operation' => $Operation));

      if ($Count > 0)
         echo '<span class="Alert">', $Count, '</span>';
   }
   
   /**
    * Delete logs.
    *
    * @since 2.0.?
    * @access public
    *
    * @param array $LogIDs Numeric IDs of logs to delete.
    */
   public function Delete($LogIDs) {
      $this->Permission('Garden.Moderation.Manage');
      // Grab the logs.
      $this->LogModel->Delete($LogIDs);
      $this->Render('Blank', 'Utility');
   }
   
   /**
    * Delete spam and optionally delete the users.
    * @param type $LogIDs 
    */
   public function DeleteSpam($LogIDs) {
      $this->Permission('Garden.Moderation.Manage');
      
      if (!$this->Request->IsPostBack())
         throw PermissionException('Javascript');
      
      $LogIDs = explode(',', $LogIDs);
      
      // Ban the appropriate users.
      $UserIDs = $this->Form->GetFormValue('UserID', array());
      if (!is_array($UserIDs))
         $UserIDs = array();
      
      if (!empty($UserIDs)) {
         // Grab the rest of the log entries.
         $OtherLogIDs = $this->LogModel->GetWhere(array('Operation' => 'Spam', 'RecordUserID' => $UserIDs));
         $OtherLogIDs = ConsolidateArrayValuesByKey($OtherLogIDs, 'LogID');
         $LogIDs = array_merge($LogIDs, $OtherLogIDs);

         foreach ($UserIDs as $UserID) {
            Gdn::UserModel()->Ban($UserID, array('Reason' => 'Spam', 'DeleteContent' => TRUE, 'Log' => TRUE));
         }
      }
      
      // Grab the logs.
      $this->LogModel->Delete($LogIDs);
      $this->Render('Blank', 'Utility');
   }
   
   /**
    * View list of edits (edit/delete actions).
    *
    * @since 2.0.?
    * @access public
    *
    * @param int $Page Page number.
    */
   public function Edits($Type = '', $Page = '', $Op = FALSE) {
      $this->Permission('Garden.Moderation.Manage');
      list($Offset, $Limit) = OffsetLimit($Page, 10);
      $this->SetData('Title', T('Change Log'));

      $Operations = array('Edit', 'Delete', 'Ban');
      if ($Op && in_array(ucfirst($Op), $Operations))
         $Operations = ucfirst($Op);
      
      $Where = array(
          'Operation' => $Operations//,
//          'RecordType' => array('Discussion', 'Comment', 'Activity')
          );
      
      $AllowedTypes = array('Discussion', 'Comment', 'Activity', 'User');
      
      $Type = strtolower($Type);
      if ($Type == 'configuration') {
         $this->Permission('Garden.Settings.Manage');
         $Where['RecordType'] = array('Configuration');
      } else {
         if (in_array(ucfirst($Type), $AllowedTypes))
            $Where['RecordType'] = ucfirst($Type);
         else
            $Where['RecordType'] = $AllowedTypes;
      }
      
      $RecordCount = $this->LogModel->GetCountWhere($Where);
      $this->SetData('RecordCount', $RecordCount);
      if ($Offset >= $RecordCount)
         $Offset = $RecordCount - $Limit;

      $Log = $this->LogModel->GetWhere($Where, 'LogID', 'Desc', $Offset, $Limit);
      $this->SetData('Log', $Log);

      if ($this->DeliveryType() == DELIVERY_TYPE_VIEW)
         $this->View = 'Table';

      $this->AddSideMenu('dashboard/log/edits');
      $this->Render();
   }
   
   /**
    * Access the log history of a specific record
    * 
    * @param string $RecordType
    * @param int $RecordID 
    */
   public function Record($RecordType, $RecordID, $Page = '') {
      $this->Permission('Garden.Moderation.Manage');
      list($Offset, $Limit) = OffsetLimit($Page, 10);
      $this->SetData('Title', T('Change Log'));

      $RecordType = ucfirst($RecordType);
      $Where = array(
         'Operation'    => array('Edit', 'Delete', 'Ban'),
         'RecordType'   => $RecordType,
         'RecordID'     => $RecordID
      );
      
      $RecordCount = $this->LogModel->GetCountWhere($Where);
      $this->SetData('RecordCount', $RecordCount);
      if ($Offset >= $RecordCount)
         $Offset = $RecordCount - $Limit;

      $Log = $this->LogModel->GetWhere($Where, 'LogID', 'Desc', $Offset, $Limit);
      $this->SetData('Log', $Log);

      if ($this->DeliveryType() == DELIVERY_TYPE_VIEW)
         $this->View = 'Table';
      
      $this->AddSideMenu('dashboard/log/edits');
      $this->Render();
   }
   
   /**
    * Convenience method to call model's FormatContent.
    *
    * @since 2.0.?
    * @access protected
    *
    * @param object $Log.
    */
   protected function FormatContent($Log) {
      return $this->LogModel->FormatContent($Log);
   }
   
   /**
    * Always triggered first. Add Javascript files.
    *
    * @since 2.0.?
    * @access public
    */
   public function Initialize() {
      parent::Initialize();
      Gdn_Theme::Section('Dashboard');
      $this->AddJsFile('log.js');
      $this->AddJsFile('jquery.expander.js');
      $this->AddJsFile('jquery-ui-1.8.17.custom.min.js');
      $this->Form->InputPrefix = '';
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
   public function Moderation($Page = '') {
      $this->Permission('Garden.Moderation.Manage');
      
      $Where = array('Operation' => array('Moderate', 'Pending'));
      
      // Filter by category menu
      if ($CategoryID = Gdn::Request()->GetValue('CategoryID')) { 
         $this->SetData('ModerationCategoryID', $CategoryID);
         $Where['CategoryID'] = $CategoryID;
      }
      
      list($Offset, $Limit) = OffsetLimit($Page, 10);
      $this->SetData('Title', T('Moderation Queue'));      
      
      $RecordCount = $this->LogModel->GetCountWhere($Where);
      $this->SetData('RecordCount', $RecordCount);
      if ($Offset >= $RecordCount)
         $Offset = $RecordCount - $Limit;

      $Log = $this->LogModel->GetWhere($Where, 'LogID', 'Desc', $Offset, $Limit);
      $this->SetData('Log', $Log);

      if ($this->DeliveryType() == DELIVERY_TYPE_VIEW)
         $this->View = 'Table';

      $this->AddSideMenu('dashboard/log/moderation');
      $this->Render();
   }

   /**
    * Restore logs.
    *
    * @since 2.0.?
    * @access public
    *
    * @param array $LogIDs List of log IDs.
    */
   public function Restore($LogIDs) {
      $this->Permission('Garden.Moderation.Manage');

      // Grab the logs.
      $Logs = $this->LogModel->GetIDs($LogIDs);
      try {
         foreach ($Logs as $Log) {
            $this->LogModel->Restore($Log);
         }
      } catch (Exception $Ex) {
         $this->Form->AddError($Ex->getMessage());
      }
      $this->LogModel->Recalculate();
      $this->Render('Blank', 'Utility');
   }
   
   public function NotSpam($LogIDs) {
      $this->Permission('Garden.Moderation.Manage');
      
      if (!$this->Request->IsPostBack())
         throw PermissionException('Javascript');
      
      $Logs = array();
      
      // Verify the appropriate users.
      $UserIDs = $this->Form->GetFormValue('UserID', array());
      if (!is_array($UserIDs))
         $UserIDs = array();
      
      foreach ($UserIDs as $UserID) {
         Gdn::UserModel()->SetField($UserID, 'Verified', TRUE);
         $Logs = array_merge($Logs, $this->LogModel->GetWhere(array('Operation' => 'Spam', 'RecordUserID' => $UserID)));
      }

      // Grab the logs.
      $Logs = array_merge($Logs, $this->LogModel->GetIDs($LogIDs));
      
//      try {
         foreach ($Logs as $Log) {
            $this->LogModel->Restore($Log);
         }
//      } catch (Exception $Ex) {
//         $this->Form->AddError($Ex->getMessage());
//      }
      $this->LogModel->Recalculate();
      
      $this->SetData('Complete');
      $this->SetData('Count', count($Logs));
      $this->Render('Blank', 'Utility');
   }

   /**
    * View spam logs.
    *
    * @since 2.0.?
    * @access public
    *
    * @param int $Page Page number.
    */
   public function Spam($Page = '') {
      $this->Permission('Garden.Moderation.Manage');
      list($Offset, $Limit) = OffsetLimit($Page, 10);
      $this->SetData('Title', T('Spam Queue'));

      $Where = array('Operation' => array('Spam'));

      $RecordCount = $this->LogModel->GetCountWhere($Where);
      $this->SetData('RecordCount', $RecordCount);
      if ($Offset >= $RecordCount)
         $Offset = $RecordCount - $Limit;

      $Log = $this->LogModel->GetWhere($Where, 'LogID', 'Desc', $Offset, $Limit);
      $this->SetData('Log', $Log);

      if ($this->DeliveryType() == DELIVERY_TYPE_VIEW)
         $this->View = 'Table';

      $this->AddSideMenu('dashboard/log/spam');
      $this->Render();
   }
}