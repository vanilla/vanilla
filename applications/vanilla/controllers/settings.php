<?php if (!defined('APPLICATION')) exit();

/**
 * Settings Controller
 */
class SettingsController extends VanillaController {
   
   public $Uses = array('Database', 'Form');
   
   public function Index() {
      $this->Permission('Vanilla.Settings.Manage');
      $this->AddSideMenu('vanilla/settings');
      if ($this->Head) {
         $this->Head->AddScript('/applications/vanilla/js/settings.js');
         $this->Head->Title(Translate('Forum Settings'));
      }

      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel('Configuration', PATH_CONF . DS . 'config.php', $Validation);
      $ConfigurationModel->SetField(array(
         'Vanilla.Discussions.PerPage',
         'Vanilla.Comments.AutoRefresh',
         'Vanilla.Comments.PerPage',
         'Vanilla.Categories.Use',
         'Vanilla.Discussions.Home'
      ));
      
      // Set the model on the form.
      $this->Form->SetModel($ConfigurationModel);
      
      // If seeing the form for the first time...
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $this->Form->SetData($ConfigurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussions.PerPage', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussions.PerPage', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comments.AutoRefresh', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comments.PerPage', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comments.PerPage', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Categories.Use', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussions.Home', 'Required');
         
         if ($this->Form->Save() !== FALSE)
            $this->StatusMessage = Translate("Your changes have been saved.");

      }
      
      $this->Render();
   }
   
   public function Initialize() {
      parent::Initialize();
      $this->AddCssFile('garden.css');
      if ($this->Menu)
         $this->Menu->HighlightRoute('/garden/settings');
   }   
   
   public function Spam() {
      if ($this->Head)
         $this->Head->Title(Translate('Spam'));
         
      $this->Permission('Vanilla.Spam.Manage');
      $this->AddSideMenu('vanilla/settings/spam');
      
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel('Configuration', PATH_CONF . DS . 'config.php', $Validation);
      $ConfigurationModel->SetField(array(
         'Vanilla.Discussion.SpamCount',
         'Vanilla.Discussion.SpamTime',
         'Vanilla.Discussion.SpamLock',
         'Vanilla.Comment.SpamCount',
         'Vanilla.Comment.SpamTime',
         'Vanilla.Comment.SpamLock',
         'Vanilla.Comment.MaxLength'
      ));
      
      // Set the model on the form.
      $this->Form->SetModel($ConfigurationModel);
      
      // If seeing the form for the first time...
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $this->Form->SetData($ConfigurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussion.SpamCount', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussion.SpamCount', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussion.SpamTime', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussion.SpamTime', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussion.SpamLock', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Discussion.SpamLock', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.SpamCount', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.SpamCount', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.SpamTime', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.SpamTime', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.SpamLock', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.SpamLock', 'Integer');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.MaxLength', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Vanilla.Comment.MaxLength', 'Integer');
         
         if ($this->Form->Save() !== FALSE) {
            $this->StatusMessage = Translate("Your changes have been saved.");
         }
      }
      
      $this->Render();
   }
}