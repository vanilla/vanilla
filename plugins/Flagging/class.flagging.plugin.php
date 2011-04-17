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
   'Version' => '1.1.0',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'SettingsUrl' => '/dashboard/plugin/flagging',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'HasLocale' => TRUE,
   'RegisterPermissions' => array('Plugins.Flagging.Notify'),
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class FlaggingPlugin extends Gdn_Plugin {
   /**
    * Add Flagging to Dashboard menu.
    */
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
   
   /**
    * Let users with permission choose to receive Flagging emails.
    */
   public function ProfileController_AfterPreferencesDefined_Handler($Sender) {
      if (Gdn::Session()->CheckPermission('Plugins.Flagging.Notify')) {
         $Sender->Preferences['Notifications']['Email.Flag'] = T('Notify me when a comment is flagged.');
         $Sender->Preferences['Notifications']['Popup.Flag'] = T('Notify me when a comment is flagged.');
      }
   }
   
   /**
    * Save Email.Flag preference list in config for easier access.
    */
   public function UserModel_BeforeSaveSerialized_Handler($Sender) {
      if (Gdn::Session()->CheckPermission('Plugins.Flagging.Notify')) {
         if ($Sender->EventArguments['Column'] == 'Preferences' && is_array($Sender->EventArguments['Name'])) {
            // Shorten our arguments
            $UserID = $Sender->EventArguments['UserID'];
            $Prefs = $Sender->EventArguments['Name'];
            $FlagPref = GetValue('Email.Flag', $Prefs, NULL);
            
            if ($FlagPref !== NULL) {
               // Add or remove user from config array
               $NotifyUsers = C('Plugins.Flagging.NotifyUsers', array());
               $IsNotified = array_search($UserID, $NotifyUsers); // beware '0' key
               if ($IsNotified !== FALSE && !$FlagPref) {
                  // Remove from NotifyUsers
                  unset($NotifyUsers[$IsNotified]);
               }
               elseif ($IsNotified === FALSE && $FlagPref) {
                  // Add to NotifyUsers
                  $NotifyUsers[] = $UserID;
               }
               
               // Save new list of users to notify
               SaveToConfig('Plugins.Flagging.NotifyUsers', array_values($NotifyUsers));
            }
         }
      }
   }
   
   /**
    * Create virtual Flagging controller.
    */
   public function PluginController_Flagging_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('Content Flagging');
      $Sender->AddSideMenu('plugin/flagging');
      $Sender->Form = new Gdn_Form();
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }
   
   /**
    * Get flagged content & show settings.
    *
    * Default method of virtual Flagging controller.
    */
   public function Controller_Index($Sender) {
      $Sender->AddCssFile('admin.css');
      $Sender->AddCssFile($this->GetResource('design/flagging.css', FALSE, FALSE));
      
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array(
         'Plugins.Flagging.UseDiscussions',
         'Plugins.Flagging.CategoryID'
      ));
      
      // Set the model on the form.
      $Sender->Form->SetModel($ConfigurationModel);
      
      $CategoryModel = new CategoryModel();
      $Sender->CategoryData = $CategoryModel->GetFull('', 'Vanilla.Discussions.Add');
      
      // If seeing the form for the first time...
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $Sender->Form->SetData($ConfigurationModel->Data);
      } else {
         $Saved = $Sender->Form->Save();
         if($Saved) {
            $Sender->InformMessage(T("Your changes have been saved."));
         }
      }
      
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
   
   /**
    * Enable/Disable Flagging.
    */
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
   
   /**
    * Dismiss a flag, then view index.
    */
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
   
   /**
    * Add Flagging styling to Discussion.
    */
   public function DiscussionController_BeforeCommentsRender_Handler($Sender) {
      if (!C('Plugins.Flagging.Enabled')) return;
      $Sender->AddCssFile($this->GetResource('design/flagging.css', FALSE, FALSE));
   }
   
   /**
    * Create 'Flag' link for comments in a discussion.
    */
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
      $Sender->Options .= '<span>'.Anchor(T('Flag'), "discussion/flag/{$Context}/{$ElementID}/{$ElementAuthorID}/".Gdn_Format::Url($ElementAuthor)."/{$EncodedURL}", 'FlagContent Popup') . '</span>';
   }
   
   // Note: Mark added this slick code. Tim does not approve.
   public function PostController_CommentOptions_Handler($Sender) {
      $this->DiscussionController_CommentOptions_Handler($Sender);
   }
   
   /**
    * Handle flagging process in a discussion.
    */
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
         'URL'             => $URL,
         'UserID'          => $UserID,
         'UserName'        => $UserName
      ));
      
      if ($Sender->Form->AuthenticatedPostBack()) {
         $SQL = Gdn::SQL();
         $Comment = $Sender->Form->GetValue('Plugin.Flagging.Reason');
         $Sender->SetData('Plugin.Flagging.Reason', $Comment);
         $CreateDiscussion = C('Plugins.Flagging.UseDiscussions');
         
         if ($CreateDiscussion) {
            // Category
            $CategoryID = C('Plugins.Flagging.CategoryID');
                        
            // New discussion name
            if ($Context == 'comment') {
               $Result = $SQL
                  ->Select('d.Name')
                  ->Select('c.Body')
                  ->From('Comment c')
                  ->Join('Discussion d', 'd.DiscussionID = c.DiscussionID', 'left')
                  ->Where('c.CommentID', $ElementID)
                  ->Get()
                  ->FirstRow();
            } elseif ($Context == 'discussion') {
               $DiscussionModel = new DiscussionModel();
               $Result = $DiscussionModel->GetID($ElementID);
            }
            
            $DiscussionName = GetValue('Name', $Result);
            $PrefixedDiscussionName = T('FlagPrefix', 'FLAG: ') . $DiscussionName;
            
            // Prep data for the template
            $Sender->SetData('Plugin.Flagging.Report', array(
               'DiscussionName'  => $DiscussionName,
               'FlaggedContent'  => GetValue('Body', $Result)
            ));
         
            // Assume no discussion exists
            $this->DiscussionID = NULL;
            
            // Get discussion ID if already flagged
            $FlagResult = Gdn::SQL()
               ->Select('DiscussionID')
               ->From('Flag fl')
               ->Where('ForeignType', $Context)
               ->Where('ForeignID', $ElementID)
               ->Get()
               ->FirstRow();
            
            if ($FlagResult) {
               // New comment in existing discussion
               $DiscussionID = $FlagResult->DiscussionID;
               $ReportBody = $Sender->FetchView($this->GetView('reportcomment.php'));
               $SQL->Insert('Comment', array(
                  'DiscussionID'    => $DiscussionID,
                  'InsertUserID'    => $UserID,
                  'Body'            => $ReportBody,
                  'Format'          => 'Html',
                  'DateInserted'    => date('Y-m-d H:i:s')
               ));
               $CommentModel = new CommentModel();
               $CommentModel->UpdateCommentCount($DiscussionID);
            }
            else {
               // New discussion body
               $ReportBody = $Sender->FetchView($this->GetView('report.php'));
               $DiscussionID = $SQL->Insert('Discussion', array(
                  'InsertUserID'    => $UserID,
                  'UpdateUserID'    => $UserID,
                  'CategoryID'      => $CategoryID,
                  'Name'            => $PrefixedDiscussionName,
                  'Body'            => $ReportBody,
                  'Format'          => 'Html',
                  'CountComments'   => 1,
                  'DateInserted'    => date('Y-m-d H:i:s'),
                  'DateUpdated'     => date('Y-m-d H:i:s'),
                  'DateLastComment' => date('Y-m-d H:i:s')
               ));
               
               // Update discussion count
               $DiscussionModel = new DiscussionModel();
               $DiscussionModel->UpdateDiscussionCount($CategoryID);
            }
         }
         
         try {
            // Insert the flag
            $SQL->Insert('Flag', array(
               'DiscussionID'    => $DiscussionID,
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
         
         // Notify users with permission who've chosen to be notified
         if (!$FlagResult) { // Only send if this is first time it's being flagged.
            $Sender->SetData('Plugin.Flagging.DiscussionID', $DiscussionID);
            $Subject = (isset($PrefixedDiscussionName)) ? $PrefixedDiscussionName : T('A discussion was flagged');
            $EmailBody = $Sender->FetchView($this->GetView('reportemail.php'));
            $NotifyUsers = C('Plugins.Flagging.NotifyUsers', array());
            
            // Send emails
            $UserModel = new UserModel();
            foreach ($NotifyUsers as $UserID) {
               $User = $UserModel->GetID($UserID);
               $Email = new Gdn_Email();
               $Email->To($User->Email)
                  ->Subject(sprintf(T('[%1$s] %2$s'), Gdn::Config('Garden.Title'), $Subject))
                  ->Message($EmailBody)
                  ->Send();
            }
         }
                  
         $Sender->InformMessage(T("Your complaint has been registered."));
      }
      $Sender->Render($this->GetView('flag.php'));
   }
   
   /**
    * Database changes needed for this plugin.
    */
   public function Structure() {
      $Structure = Gdn::Structure();
      $Structure
         ->Table('Flag')
         ->Column('DiscussionID', 'int(11)', TRUE)
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