<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

class LogController extends DashboardController {
   /// PROPERTIES ///

   /**
    * @var Gdn_Form
    */
   public $Form;

   /**
    * @var LogModel
    */
   public $LogModel;


   /// METHODS ///
   public function  __construct() {
      parent::__construct();
      
      $this->Form = new Gdn_Form();
      $this->LogModel = new LogModel();
   }

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

   public function Delete($LogIDs) {
      $this->Permission('Garden.Moderation.Manage');
      // Grab the logs.
      $this->LogModel->Delete($LogIDs);
   }

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

   protected function FormatContent($Log) {
      return $this->LogModel->FormatContent($Log);
   }

   public function Initialize() {
      parent::Initialize();
      $this->AddJsFile('log.js');
      $this->AddJsFile('jquery.expander.js');
      $this->AddJsFile('jquery.ui.packed.js');
   }

   public function Restore($LogIDs) {
      $this->Permission('Garden.Moderation.Manage');

      // Grab the logs.
      $Logs = $this->LogModel->GetIDs($LogIDs);
      foreach ($Logs as $Log) {
         $this->LogModel->Restore($Log);
      }
      $this->LogModel->Recalculate();
   }



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