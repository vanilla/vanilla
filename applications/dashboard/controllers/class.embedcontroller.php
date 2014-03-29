<?php if (!defined('APPLICATION')) exit();
 
/**
 * Manages the embedding of a forum on a foreign page.
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0.18
 */

class EmbedController extends DashboardController {
   /**
    * Models to include.
    * 
    * @since 2.0.18
    * @access public
    * @var array
    */
   public $Uses = array('Database', 'Form');
   
   public function Index() {
      Redirect('embed/comments');
   }
   
   public function Initialize() {
      parent::Initialize();
      Gdn_Theme::Section('Dashboard');
   }
   
   /**
    * Display the embedded forum.
    * 
    * @since 2.0.18
    * @access public
    */
   public function Comments($Toggle = '', $TransientKey = '') {
      $this->Permission('Garden.Settings.Manage');
      
      try {
         if ($this->Toggle($Toggle, $TransientKey))
            Redirect('embed/comments');
      } catch (Gdn_UserException $Ex) {
         $this->Form->AddError($Ex);
      }

      $this->AddSideMenu('dashboard/embed/comments');
      $this->Form = new Gdn_Form();
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array('Garden.Embed.CommentsPerPage', 'Garden.Embed.SortComments', 'Garden.Embed.PageToForum'));
      
      $this->Form->SetModel($ConfigurationModel);
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $this->Form->SetData($ConfigurationModel->Data);
      } else {
         if ($this->Form->Save() !== FALSE)
            $this->InformMessage(T("Your settings have been saved."));
      }
      
      $this->Title(T('Blog Comments'));
      $this->Render();
   }
   
   public function Forum($Toggle = '', $TransientKey = '') {
      $this->Permission('Garden.Settings.Manage');
      
      try {
         if ($this->Toggle($Toggle, $TransientKey))
            Redirect('embed/forum');
      } catch (Gdn_UserException $Ex) {
         $this->Form->AddError($Ex);
      }

      $this->AddSideMenu('dashboard/embed/forum');
      $this->Title('Embed Forum');
      $this->Render();
   }
   
   public function Advanced($Toggle = '', $TransientKey = '') {
      $this->Permission('Garden.Settings.Manage');
      
      try {
         if ($this->Toggle($Toggle, $TransientKey))
            Redirect('embed/advanced');
      } catch (Gdn_UserException $Ex) {
         $this->Form->AddError($Ex);
      }
      
      $this->Title('Advanced Embed Settings');

      $this->AddSideMenu('dashboard/embed/advanced');
      $this->Form = new Gdn_Form();

      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array('Garden.TrustedDomains', 'Garden.Embed.RemoteUrl', 'Garden.Embed.ForceDashboard', 'Garden.Embed.ForceForum', 'Garden.SignIn.Popup'));
      
      $this->Form->SetModel($ConfigurationModel);
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Format trusted domains as a string
         $TrustedDomains = GetValue('Garden.TrustedDomains', $ConfigurationModel->Data);
         if (is_array($TrustedDomains))
            $TrustedDomains = implode("\n", $TrustedDomains);
         
         $ConfigurationModel->Data['Garden.TrustedDomains'] = $TrustedDomains;

         // Apply the config settings to the form.
         $this->Form->SetData($ConfigurationModel->Data);
      } else {
         // Format the trusted domains as an array based on newlines & spaces
         $TrustedDomains = $this->Form->GetValue('Garden.TrustedDomains');
         $TrustedDomains = explode(' ', str_replace("\n", ' ', $TrustedDomains));
         $TrustedDomains = array_unique(array_map('trim', $TrustedDomains));
         $this->Form->SetFormValue('Garden.TrustedDomains', $TrustedDomains);
         if ($this->Form->Save() !== FALSE)
            $this->InformMessage(T("Your settings have been saved."));
         
         // Reformat array as string so it displays properly in the form
         $this->Form->SetFormValue('Garden.TrustedDomains', implode("\n", $TrustedDomains));
      }
      
      $this->Permission('Garden.Settings.Manage');
      $this->Render();
   }
   
   /** 
    * Handle toggling this version of embedding on and off. Take care of disabling the other version of embed (the old plugin).
    * @param type $Toggle
    * @param type $TransientKey
    * @return boolean 
    */
   private function Toggle($Toggle = '', $TransientKey = '') {
      if (in_array($Toggle, array('enable', 'disable')) && Gdn::Session()->ValidateTransientKey($TransientKey)) {
         if ($Toggle == 'enable' && array_key_exists('embedvanilla', Gdn::PluginManager()->EnabledPlugins()))
            throw new Gdn_UserException('You must disable the "Embed Vanilla" plugin before continuing.');

         // Do the toggle
         SaveToConfig('Garden.Embed.Allow', $Toggle == 'enable' ? TRUE : FALSE);
         return TRUE;
      }
      return FALSE;      
   }
   
   
   /**
    * Allow for a custom embed theme.
    * 
    * @since 2.0.18
    * @access public
    */
   public function Theme() {
      // Do nothing by default
   }

}
