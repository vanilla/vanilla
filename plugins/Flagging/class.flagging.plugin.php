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
   'Version' => '1.0.1',
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
   
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $NumFlaggedItems = Gdn::SQL()->Select('fl.ForeignID','DISTINCT', 'NumFlaggedItems')
         ->From('Flag fl')
         ->GroupBy('ForeignURL')
         ->Get()->NumRows();
      
      $LinkText = T('Flagged Content');
      if ($NumFlaggedItems)
         $LinkText .= " ({$NumFlaggedItems})";
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Forum', T('Forum'));
      $Menu->AddLink('Forum', $LinkText, 'plugin/flagging', 'Garden.Settings.Manage');
   }

   public function PluginController_Flagging_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('Content Flagging');
      $Sender->AddSideMenu('plugin/flagging');
      $Sender->Form = new Gdn_Form();
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }
   
   public function Controller_Index($Sender) {
      $Sender->AddCssFile('admin.css');
      $Sender->AddCssFile($this->GetResource('design/flagging.css', FALSE, FALSE));
      
      $FlaggedItems = Gdn::SQL()->Select('*')
         ->From('Flag fl')
         ->OrderBy('DateInserted', 'DESC')
         ->Get();
      
      $Sender->FlaggedItems = array();
      while ($Flagged = $FlaggedItems->NextRow(DATASET_TYPE_ARRAY)) {
         $URL = $Flagged['ForeignURL'];
         $Index = $Flagged['DateInserted'].'-'.$Flagged['InsertUserID'];
         $Flagged['EncodedURL'] = str_replace('=','-',base64_encode($Flagged['ForeignURL']));
         $Sender->FlaggedItems[$URL][$Index] = $Flagged;
      }
      unset($FlaggedItems);
      
      $Sender->Render($this->GetView('flagging.php'));
   }
   
   public function Controller_Toggle($Sender) {
		
		// Enable/Disable Content Flagging
		if (Gdn::Session()->ValidateTransientKey(GetValue(1, $Sender->RequestArgs))) {
			if (C('Plugins.Flagging.Enabled')) {
				$this->_Disable();
			} else {
				$this->_Enable();
			}
			Redirect('plugin/flagging');
		}
   }
   
   public function Controller_Dismiss($Sender) {
      $Arguments = $Sender->RequestArgs;
      if (sizeof($Arguments) != 2) return;
      list($Controller, $EncodedURL) = $Arguments;
      
      $URL = base64_decode(str_replace('-','=',$EncodedURL));
      
      Gdn::SQL()->Delete('Flag',array(
         'ForeignURL'      => $URL
      ));
      
      $this->Controller_Index($Sender);
   }
   
   public function DiscussionController_BeforeCommentsRender_Handler($Sender) {
      if (!C('Plugins.Flagging.Enabled')) return;
      $Sender->AddCssFile($this->GetResource('design/flagging.css', FALSE, FALSE));
   }
   
   public function DiscussionController_CommentOptions_Handler($Sender) {
      if (!C('Plugins.Flagging.Enabled')) return;
      
      // Signed in users only. No guest reporting!
      if (!Gdn::Session()->UserID) return;
      
      $Context = strtolower($Sender->EventArguments['Type']);
      $ElementID = ($Context == 'comment') ? $Sender->EventArguments['Comment']->CommentID : $Sender->EventArguments['Discussion']->DiscussionID;
      
      if (!is_object($Sender->EventArguments['Author'])) {
         $ElementAuthorID = 0;
         $ElementAuthor = 'Unknown';
      } else {
         $ElementAuthorID = $Sender->EventArguments['Author']->UserID;
         $ElementAuthor = $Sender->EventArguments['Author']->Name;
      }
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
      $Sender->Options .= '<span>'.Anchor(T('Flag'), "discussion/flag/{$Context}/{$ElementID}/{$ElementAuthorID}/{$ElementAuthor}/{$EncodedURL}", 'FlagContent Popup') . '</span>';
   }
   // Note: Mark added this slick code. Tim does not approve.
   public function PostController_CommentOptions_Handler($Sender) {
      $this->DiscussionController_CommentOptions_Handler($Sender);
   }
   
   public function DiscussionController_Flag_Create($Sender) {
      if (!C('Plugins.Flagging.Enabled')) return;
      
      // Signed in users only.
      if (!($UserID = Gdn::Session()->UserID)) return;
      $UserName = Gdn::Session()->User->Name;
      
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
         
         try {
            Gdn::SQL()
               ->Insert('Flag', array(
                  'InsertUserID'    => $UserID,
                  'InsertName'      => $UserName,
                  'AuthorID'        => $ElementAuthorID,
                  'AuthorName'      => $ElementAuthor,
                  'ForeignURL'      => $URL,
                  'ForeignID'       => $ElementID,
                  'ForeignType'     => $Context,
                  'Comment'         => $Comment,
                  'DateInserted'    => date('Y-m-d H:i:s')
            ));
         } catch(Exception $e) {}
         $Sender->StatusMessage = T("Your complaint has been registered.");
      }
      $Sender->Render($this->GetView('flag.php'));
   }
   
   public function Structure() {
      $Structure = Gdn::Structure();
      $Structure
         ->Table('Flag')
         ->Column('InsertUserID', 'int(11)', FALSE, 'key')
         ->Column('InsertName', 'varchar(64)')
         ->Column('AuthorID', 'int(11)')
         ->Column('AuthorName', 'varchar(64)')
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
      SaveToConfig('Plugins.Flagging.Enabled', TRUE);
   }
   
   protected function _Disable() {
      RemoveFromConfig('Plugins.Flagging.Enabled');
   }
   
}