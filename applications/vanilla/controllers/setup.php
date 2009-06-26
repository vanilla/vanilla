<?php if (!defined('APPLICATION')) exit();

/// <summary>
/// Vanilla Setup Controller
/// </summary>
class SetupController extends Gdn_Controller {
   
   public $Uses = array('Form');
   
   /// <summary>
   /// The methods in setup controllers should not call "Render". Rendering will
   /// be handled by the controller that initiated the setup. This method should
   /// return a boolean value indicating success.
   /// </summary>
   public function Index() {
      $Database = Gdn::Database();
      $Config = Gdn::Factory(Gdn::AliasConfig);
      $Drop = Gdn::Config('Vanilla.Version') === FALSE ? TRUE : FALSE;
      $Explicit = TRUE;
      $Construct = $Database->Structure();
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
         $Config->Load(PATH_CONF . DS . 'config.php', 'Save');
         $Config->Set('Vanilla.Version', $Version);
         $Config->Set('Routes.DefaultController', 'discussions');
         $Config->Save();
      }
      
      return $this->Form->ErrorCount() > 0 ? FALSE : TRUE;
   }  
}