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
$PluginInfo['ShareThis'] = array(
   'Name' => 'ShareThis',
   'Description' => 'Adds ShareThis (http://sharethis.com) buttons below discussions.',
   'Version' => '1.2',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
   'SettingsUrl' => '/dashboard/plugin/sharethis',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Brendan Sera-Shriar a.k.a digibomb",
   'AuthorEmail' => 'brendan@vanillaforums.com',
   'AuthorUrl' => 'http://www.dropthedigibomb.com'
);


class ShareThisPlugin extends Gdn_Plugin {
   /**
    * Show buttons after OP message body.
    */
	public function DiscussionController_AfterDiscussionBody_Handler($Sender) {
      $PublisherNumber = C('Plugin.ShareThis.PublisherNumber', 'Publisher Number');
      $ViaHandle = C('Plugin.ShareThis.ViaHandle', '');
      $CopyNShare = C('Plugin.ShareThis.CopyNShare', false);

      $doNotHash = $CopyNShare ? 'false' : 'true';
      $doNotCopy = $CopyNShare ? 'false' : 'true';
      $Domain = Gdn::Request()->Scheme() == 'https' ? 'https://ws.sharethis.com' : 'http://w.sharethis.com';

      echo <<<SHARETHIS
      <script type="text/javascript">var switchTo5x=true;</script>
      <script type="text/javascript" src="{$Domain}/button/buttons.js"></script>
      <script type="text/javascript">stLight.options({
         publisher: "{$PublisherNumber}",
         doNotHash: {$doNotHash},
         doNotCopy: {$doNotCopy},
         hashAddressBar: false
      });</script>
      <div class="ShareThisButtonWrapper Right">
         <span class="st_twitter_hcount ShareThisButton" st_via="{$ViaHandle}" displayText="Tweet"></span>
         <span class="st_facebook_hcount ShareThisButton" displayText="Facebook"></span>
         <span class="st_linkedin_hcount ShareThisButton Hidden" displayText="LinkedIn"></span>
         <span class="st_googleplus_hcount ShareThisButton Hidden" displayText="Google +"></span>
         <span class="st_reddit_hcount ShareThisButton Hidden" displayText="Reddit"></span>
         <span class="st_pinterest_hcount ShareThisButton Hidden" displayText="Pinterest"></span>
         <span class="st_email_hcount ShareThisButton" displayText="Email"></span>
         <span class="st_sharethis_hcountShareThisButton" displayText="ShareThis"></span>
      </div>
SHARETHIS;

   }

   public function Setup() {
      // Nothing to do here!
   }

   /**
    * Add to dashboard side menu.
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Add-ons', T('ShareThis'), 'plugin/sharethis', 'Garden.Settings.Manage');
   }

   /**
    * Settings page.
    */
   public function PluginController_ShareThis_Create($Sender) {
   	$Sender->Permission('Garden.Settings.Manage');
   	$Sender->Title('ShareThis');
      $Sender->AddSideMenu('plugin/sharethis');
      $Sender->Form = new Gdn_Form();

      $PublisherNumber = C('Plugin.ShareThis.PublisherNumber', 'Publisher Number');
      $ViaHandle = C('Plugin.ShareThis.ViaHandle', '');
      $CopyNShare = C('Plugin.ShareThis.CopyNShare', false);

      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigArray = array('Plugin.ShareThis.PublisherNumber','Plugin.ShareThis.ViaHandle', 'Plugin.ShareThis.CopyNShare');
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         $ConfigArray['Plugin.ShareThis.PublisherNumber'] = $PublisherNumber;
         $ConfigArray['Plugin.ShareThis.ViaHandle'] = $ViaHandle;
         $ConfigArray['Plugin.ShareThis.CopyNShare'] = $CopyNShare;
      }

      $ConfigurationModel->SetField($ConfigArray);
      $Sender->Form->SetModel($ConfigurationModel);
      // If seeing the form for the first time...
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $Sender->Form->SetData($ConfigurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Plugin.ShareThis.PublisherNumber', 'Required');
         if ($Sender->Form->Save() !== FALSE)
            $Sender->InformMessage(T("Your changes have been saved."));
      }

      $Sender->Render('sharethis', '', 'plugins/ShareThis');
   }

}
