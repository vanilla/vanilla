<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class EmbedController extends DashboardController {

   public $Uses = array('Database', 'Form');

   public function Index() {
      $this->AddSideMenu('dashboard/embed');
      $this->Title('Embed Vanilla');
      $this->Form = new Gdn_Form();
		
		$ThemeManager = new Gdn_ThemeManager();
		$this->SetData('AvailableThemes', $ThemeManager->AvailableThemes());
      $this->SetData('EnabledThemeFolder', $ThemeManager->EnabledTheme());
      $this->SetData('EnabledTheme', $ThemeManager->EnabledThemeInfo());
		$this->SetData('EnabledThemeName', $this->Data('EnabledTheme.Name', $this->Data('EnabledTheme.Folder')));

      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array('Plugins.EmbedVanilla.RemoteUrl', 'Plugins.EmbedVanilla.ForceRemoteUrl', 'Plugins.EmbedVanilla.EmbedDashboard'));
      
      $this->Form->SetModel($ConfigurationModel);
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $this->Form->SetData($ConfigurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Plugins.EmbedVanilla.RemoteUrl', 'WebAddress', 'The remote url you specified could not be validated as a functional url to redirect to.');
         if ($this->Form->Save() !== FALSE)
            $this->InformMessage(T("Your settings have been saved."));
      }
		
		// Handle changing the theme to the recommended one
		$ThemeFolder = GetValue(0, $this->RequestArgs);
		$TransientKey = GetValue(1, $this->RequestArgs);
		$Session = Gdn::Session();
      if ($Session->ValidateTransientKey($TransientKey) && $ThemeFolder != '') {
         try {
            foreach ($this->Data('AvailableThemes') as $ThemeName => $ThemeInfo) {
		         if ($ThemeInfo['Folder'] == $ThemeFolder)
                  $ThemeManager->EnableTheme($ThemeName);
            }
         } catch (Exception $Ex) {
            $this->Form->AddError($Ex);
         }
         if ($this->Form->ErrorCount() == 0)
            Redirect('/plugin/embed');

      }

      $this->Render();
   }

   public function Theme() {
      // Allow for a custom embed theme
   }

}