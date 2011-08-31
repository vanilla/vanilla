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

      if (trim($LogIDs))
         $LogIDs = explode(',', $LogIDs);
      else
         $LogIDs = array();

      $this->SetData('Action', $Action);
      $this->SetData('ItemCount', count($LogIDs));

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
   }
   
   /**
    * View list of edits (edit/delete actions).
    *
    * @since 2.0.?
    * @access public
    *
    * @param int $Page Page number.
    */
   public function Edits($Page = '') {
      $this->Permission('Garden.Moderation.Manage');
      list($Offset, $Limit) = OffsetLimit($Page, 10);
      $this->SetData('Title', T('Edit/Delete Log'));

      $Where = array('Operation' => array('Edit', 'Delete'));
      
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
      $this->AddJsFile('log.js');
      $this->AddJsFile('jquery.expander.js');
      $this->AddJsFile('jquery.ui.packed.js');
   }
   
   /**
    * View moderation logs.
    *
    * @since 2.0.?
    * @access public
    *
    * @param int $Page Page number.
    */
   public function Moderation($Page = '') {
      $this->Permission('Garden.Moderation.Manage');
      list($Offset, $Limit) = OffsetLimit($Page, 10);
      $this->SetData('Title', T('Moderation Queue'));

      $Where = array('Operation' => 'Moderate');
      
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
      foreach ($Logs as $Log) {
         $this->LogModel->Restore($Log);
      }
      $this->LogModel->Recalculate();
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
      $this->SetData('Title', T('Manage Spam'));

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