<?php if (!defined('APPLICATION')) exit();

/**
 * Contains useful functions for cleaning up the database.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.1
 */

class DbaController extends DashboardController {
   /// Properties ///
   
   /**
    * @var Gdn_Form 
    */
   public $Form = NULL;
   
   /**
    * @var DBAModel 
    */
   public $Model = NULL;
   
   
   /// Methods ///
   
   public function __construct() {
      parent::__construct();
   }
   
   public function Initialize() {
      parent::Initialize();
      Gdn_Theme::Section('Dashboard');
      $this->Model = new DBAModel();
      $this->Form = new Gdn_Form();
      $this->Form->InputPrefix = '';
      
      $this->AddJsFile('dba.js');
   }
   
   public function Counts($Table = FALSE, $Column = FALSE, $From = FALSE, $To = FALSE, $Max = FALSE) {
      set_time_limit(300);
      $this->Permission('Garden.Settings.Manage');
      
      if ($Table && $Column && strcasecmp($this->Request->RequestMethod(), Gdn_Request::INPUT_POST) == 0) {
         if (!ValidateRequired($Table))
            throw new Gdn_UserException("Table is required.");
         if (!ValidateRequired($Column))
            throw new Gdn_UserException("Column is required.");
         
         $Result = $this->Model->Counts($Table, $Column, $From, $To);
         $this->SetData('Result', $Result);
      } else {
         $this->SetData('Jobs', array());
         $this->FireEvent('CountJobs');
      }
      
      $this->SetData('Title', T('Recalculate Counts'));
      $this->AddSideMenu();
      $this->Render('Job');
   }
   
   public function FixUrlCodes($Table, $Column) {
      $this->Permission('Garden.Settings.Manage');
      
      if ($this->Request->IsAuthenticatedPostBack()) {
         $Result = $this->Model->FixUrlCodes($Table, $Column);
         $this->SetData('Result', $Result);
      }
      
      $this->SetData('Title', "Fix url codes for $Table.$Column");
      $this->_SetJob($this->Data('Title'));
      $this->AddSideMenu();
      $this->Render('Job');
   }
   
   public function HtmlEntityDecode($Table, $Column) {
      $this->Permission('Garden.Settings.Manage');
      
//      die($this->Request->RequestMethod());
      if (strcasecmp($this->Request->RequestMethod(), Gdn_Request::INPUT_POST) == 0) {
         $Result = $this->Model->HtmlEntityDecode($Table, $Column);
         $this->SetData('Result', $Result);
      }
      
      $this->SetData('Title', "Decode Html Entities for $Table.$Column");
      $this->_SetJob($this->Data('Title'));
      $this->AddSideMenu();
      $this->Render('Job');
   }
   
   protected function _SetJob($Name) {
      $Args = array_change_key_case($this->ReflectArgs);
      $Url = "/dba/{$this->RequestMethod}.json?".http_build_query($Args);
      $this->Data['Jobs'][$Name] = $Url;
   }
}