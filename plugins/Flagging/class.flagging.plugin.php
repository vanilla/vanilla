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
$PluginInfo['Flagging'] = array(
   'Name' => 'Flagging',
   'Description' => 'This plugin allows users to report content that violates forum rules.',
   'Version' => '1.0',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'SettingsUrl' => '/dashboard/plugin/flagging',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class FlaggingPlugin extends Gdn_Plugin {

   public function PluginController_Flagging_Create(&$Sender) {
      $Sender->Title('Content Flagging');
      $Sender->AddSideMenu('plugin/flagging');
      $Sender->Form = new Gdn_Form();
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }
   
   public function Controller_Index(&$Sender) {
      //$Sender->AddCssFile($this->GetWebResource('design/flagging.css'));
      $Sender->AddCssFile('admin.css');
   }
   
   public function DiscussionController_CommentOptions_Handler(&$Sender) {
      $Sender->AddCssFile($this->GetResource('design/flagging.css', FALSE, FALSE));
      
      // Signed in users only. No guest reporting!
      if (!Gdn::Session()->UserID) return;
      
      $Context = strtolower($Sender->EventArguments['Type']);
      $ElementID = ($Context == 'comment') ? $Sender->EventArguments['Comment']->CommentID : $Sender->EventArguments['Discussion']->DiscussionID;
      $ElementAuthorID = $Sender->EventArguments['Author']->UserID;
      $ElementAuthor = $Sender->EventArguments['Author']->Name;
      switch ($Context) {
         case 'comment':
            $URL = "/discussion/comment/{$ElementID}/#Comment_{$ElementID}";
            break;
            
         case 'discussion':
            $URL = "/discussion/{$ElementID}/".Gdn_Format::Url($Sender->EventArguments['Discussion']->Name);
            break;
            
         default:
            return;
      }
      $EncodedURL = str_replace('=','-',base64_encode($URL));
      $Sender->Options .= '<span>'.Anchor(T('Flag'), "vanilla/discussion/flag/{$Context}/{$ElementID}/{$ElementAuthorID}/{$ElementAuthor}/{$EncodedURL}", 'FlagContent Popup') . '</span>';
   }
   
   public function DiscussionController_Flag_Create(&$Sender) {
      // Signed in users only.
      if (!($UserID = Gdn::Session()->UserID)) return;
      
      $Arguments = $Sender->RequestArgs;
      if (sizeof($Arguments) != 5) return;
      list($Context, $ElementID, $ElementAuthorID, $ElementAuthor, $EncodedURL) = $Arguments;
      $URL = base64_decode(str_replace('-','=',$EncodedURL));
      
      $Sender->SetData('Plugin.Flagging.Data', array(
         'Context'         => $Context,
         'ElementID'       => $ElementID,
         'ElementAuthorID' => $ElementAuthorID,
         'ElementAuthor'   => $ElementAuthor,
         'URL'             => $URL
      ));
      
      if ($Sender->Form->AuthenticatedPostBack()) {
         $Comment = $Sender->Form->GetValue('Plugin.Flagging.Reason');
         
         Gdn::SQL()
            ->Insert('Flag', array(
               'InsertUserID'    => $UserID,
               'ForeignURL'      => $URL,
               'ForeignID'       => $ElementID,
               'ForeignType'     => $Context,
               'Comment'         => $Comment,
               'DateInserted'    => date('Y-m-d H:i:s')
         ));
         $Sender->StatusMessage = T("Your complaint has been registered.");
      }
      $Sender->Render($this->GetView('flag.php'));
   }
   
   public function Structure() {
      $Structure = Gdn::Structure();
      $Structure
         ->Table('Flag')
         ->Column('InsertUserID', 'int(11)', FALSE, 'key')
         ->Column('ForeignURL', 'varchar(255)', FALSE, 'key')
         ->Column('ForeignID', 'int(11)')
         ->Column('ForeignType', 'varchar(32)')
         ->Column('Comment', 'text')
         ->Column('DateInserted', 'datetime')
         ->Set(FALSE, FALSE);
   }

   public function Setup() {
      $this->Structure();
   }
   
   protected function _Enable() {
      
   }
   
   protected function _Disable() {
      
   }
   
}