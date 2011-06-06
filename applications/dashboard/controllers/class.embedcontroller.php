<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/
/**
 * Embed Controller
 *
 * @package Dashboard
 */
 
/**
 * Manages the embedding of a forum on a foreign page.
 *
 * @since 2.0.18
 * @package Dashboard
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
   
   /**
    * Display the embedded forum.
    * 
    * @since 2.0.18
    * @access public
    */
   public function Index() {
      $this->AddSideMenu('dashboard/embed');
      $this->Title('Embed Vanilla');
      $this->Form = new Gdn_Form();

      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array('Garden.TrustedDomains'));
      
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
      
      $this->Render();
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