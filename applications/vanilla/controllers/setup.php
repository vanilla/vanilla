<?php if (!defined('APPLICATION')) exit();

/**
 * Vanilla Setup Controller
 */
class SetupController extends Gdn_Controller {
   
   public $Uses = array('Form');
   
   /**
    * The methods in setup controllers should not call "Render". Rendering will
    * be handled by the controller that initiated the setup. This method should
    * return a boolean value indicating success.
    */
   public function Index() {
      $Database = Gdn::Database();
      $Config = Gdn::Factory(Gdn::AliasConfig);
      $Drop = Gdn::Config('Vanilla.Version') === FALSE ? TRUE : FALSE;
      $Explicit = TRUE;
      $Validation = new Gdn_Validation(); // This is going to be needed by structure.php to validate permission names
      try {
         include(PATH_APPLICATIONS . DS . 'vanilla' . DS . 'settings' . DS . 'structure.php');
      } catch (Exception $ex) {
         $this->Form->AddError(strip_tags($ex->getMessage()));
      }
      
      if ($this->Form->ErrorCount() == 0) {
         $ApplicationInfo = array();
         include(CombinePaths(array(PATH_APPLICATIONS . DS . 'vanilla' . DS . 'settings' . DS . 'about.php')));
         $Version = ArrayValue('Version', ArrayValue('Vanilla', $ApplicationInfo, array()), 'Undefined');
         $Save = array(
            'Vanilla.Version' => $Version,
            'Routes.DefaultController' => 'discussions'
         );
         SaveToConfig($Save);
      }
      
      return $this->Form->ErrorCount() > 0 ? FALSE : TRUE;
   }  
}