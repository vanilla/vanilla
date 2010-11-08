<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['embedvanilla'] = array(
   'Name' => '&lt;Embed&gt; Vanilla',
   'Description' => "Embed Vanilla allows you to embed your Vanilla forum within another application like WordPress, Drupal, or some custom website you've created.",
   'Version' => '1.0.1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca',
	'SettingsUrl' => '/plugin/embed',
);

class EmbedVanillaPlugin extends Gdn_Plugin {
   
	public function Base_Render_Before($Sender) {
		$InDashboard = !($Sender->MasterView == 'default' || $Sender->MasterView == '');
		$Sender->AddJsFile('plugins/embedvanilla/local.js');

		// Record the remote source using the embed feature.
		$RemoteUrl = C('Plugins.EmbedVanilla.RemoteUrl');
		if (!$RemoteUrl) {
			$RemoteUrl = GetIncomingValue('remote');
			if ($RemoteUrl)
				SaveToConfig('Plugins.EmbedVanilla.RemoteUrl', $RemoteUrl);
		}

		// Report the remote url to redirect to if not currently embedded.
		$Sender->AddDefinition('RemoteUrl', $RemoteUrl);
		if (!IsSearchEngine() && !$InDashboard && C('Plugins.EmbedVanilla.ForceRemoteUrl'))
			$Sender->AddDefinition('ForceRemoteUrl', TRUE);
			
		if ($InDashboard)
			$Sender->AddDefinition('InDashboard', C('Plugins.EmbedVanilla.EmbedDashboard'));
	}
	
	public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Add-ons', T('&lt;Embed&gt; Vanilla'), 'plugin/embed', 'Garden.Settings.Manage');
   }
	
	public function PluginController_Embed_Create($Sender) {
      $Sender->Title('Embed Vanilla');
		$Sender->AddCssFile($this->GetResource('design/settings.css', FALSE, FALSE));
      $Sender->AddSideMenu('plugin/embed');
      $Sender->Form = new Gdn_Form();
		
		$ThemeManager = new Gdn_ThemeManager();
		$Sender->SetData('AvailableThemes', $ThemeManager->AvailableThemes());
      $Sender->SetData('EnabledThemeFolder', $ThemeManager->EnabledTheme());
      $Sender->SetData('EnabledTheme', $ThemeManager->EnabledThemeInfo());
		$Sender->SetData('EnabledThemeName', $Sender->Data('EnabledTheme.Name', $Sender->Data('EnabledTheme.Folder')));

      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array('Plugins.EmbedVanilla.RemoteUrl', 'Plugins.EmbedVanilla.ForceRemoteUrl', 'Plugins.EmbedVanilla.EmbedDashboard'));
      
      $Sender->Form->SetModel($ConfigurationModel);
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $Sender->Form->SetData($ConfigurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Plugins.EmbedVanilla.RemoteUrl', 'WebAddress', 'The remote url you specified could not be validated as a functional url to redirect to.');
         if ($Sender->Form->Save() !== FALSE)
            $Sender->StatusMessage = T("Your settings have been saved.");
      }
		
		// Handle changing the theme to the recommended one
		$ThemeFolder = GetValue(0, $Sender->RequestArgs);
		$TransientKey = GetValue(1, $Sender->RequestArgs);
		$Session = Gdn::Session();
      if ($Session->ValidateTransientKey($TransientKey) && $ThemeFolder != '') {
         try {
            foreach ($Sender->Data('AvailableThemes') as $ThemeName => $ThemeInfo) {
		         if ($ThemeInfo['Folder'] == $ThemeFolder)
                  $ThemeManager->EnableTheme($ThemeName);
            }
         } catch (Exception $Ex) {
            $Sender->Form->AddError($Ex);
         }
         if ($Sender->Form->ErrorCount() == 0)
            Redirect('/plugin/embed');

      }

      $Sender->Render(PATH_PLUGINS.'/embedvanilla/views/settings.php');
   }
	
	public function PluginController_GadgetInfo_Create($Sender) {
		$Sender->Render('plugins/embedvanilla/views/gadget.php');
	}
	
	public function PluginController_Gadget_Create($Sender) {
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<Module>
  <ModulePrefs title=\"Discussions\" 
    title_url=\"http://vanillaforums.org/\" 
    scrolling=\"true\"
    author=\"Mark O'Sullivan\" 
    author_email=\"mark@vanillaforums.com\"
    height=\"500\">
    <Require feature=\"dynamic-height\"/>
  </ModulePrefs>
  <Content type=\"html\">
  <![CDATA[
  <script type=\"text/javascript\" src=\"".Asset('plugins/embedvanilla/remote.js', TRUE)."\"></script>
  ]]>
  </Content>
</Module>";
		$Sender->Finalize();
		die();
	}
	
   public function Setup() {
      // Nothing to do here!
   }

}

if (!function_exists('IsSearchEngine')) {
   function IsSearchEngine() {
      $Engines = array(
         'googlebot', 
         'slurp', 
         'search.msn.com', 
         'nutch', 
         'simpy', 
         'bot', 
         'aspseek', 
         'crawler', 
         'msnbot', 
         'libwww-perl', 
         'fast', 
         'baidu', 
      );
      $HttpUserAgent = strtolower(GetValue('HTTP_USER_AGENT', $_SERVER, ''));
      if ($HttpUserAgent != '') {
         foreach ($Engines as $Engine) {
            if (strpos($HttpUserAgent, $Engine) !== FALSE)
               return TRUE;
         }
      }
      return FALSE;
   }
}
