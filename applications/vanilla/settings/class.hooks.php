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
 * VanillaHooks Plugin
 *
 * The class.hooks.php file is essentially a giant plugin container for an app
 * that is automatically enabled when the app is.
 *
 * @package Vanilla
 */
 
/**
 * Defines all plugins Vanilla uses. 
 * 
 * These fire automatically when their respective events are called with 
 * $this->FireEvent('EventName') and are passed the object that calls the event by reference.
 * Plugin methods are usually defined by names in the pattern: {Class}_{Event}_Handler.
 * The Render method may be hooked into using {Class}_Render_Before. 
 * You may use "Base" as the class when hooking into Dashboard events.
 *
 * @link http://vanillaforums.org/docs/PluginQuickStart
 * @since 2.0.0
 * @package Vanilla
 */
class VanillaHooks implements Gdn_IPlugin {
   /**
    * Add some extra fields to the session query.
    * 
    * @since 2.0.0
    * @package Vanilla
    * @todo Uncomment or delete this method.
    * 
    * @param object $Sender UserModel.
    */
   public function UserModel_SessionQuery_Handler(&$Sender) {
      //$Sender->SQL->Select('u.CountDiscussions, u.CountUnreadDiscussions, u.CountDrafts, u.CountBookmarks');
   }
   
	/**
	 * Remove Vanilla data when deleting a user.
    *
    * @since 2.0.0
    * @package Vanilla
    * 
    * @param object $Sender UserModel.
    */
   public function UserModel_BeforeDeleteUser_Handler($Sender) {
      $UserID = GetValue('UserID', $Sender->EventArguments);
      $Options = GetValue('Options', $Sender->EventArguments, array());
      $Options = is_array($Options) ? $Options : array();
      
      // Remove discussion watch records and drafts
		$Sender->SQL->Delete('UserDiscussion', array('UserID' => $UserID));
		$Sender->SQL->Delete('Draft', array('InsertUserID' => $UserID));
      
      // Comment deletion depends on method selected
      $DeleteMethod = GetValue('DeleteMethod', $Options, 'delete');
      if ($DeleteMethod == 'delete') {
         // Grab all of the discussions that the user has engaged in.
         $DiscussionIDs = $Sender->SQL
            ->Select('DiscussionID')
            ->Select('CommentID', 'count', 'CountComments')
            ->From('Comment')
            ->Where('InsertUserID', $UserID)
            ->GroupBy('DiscussionID')
            ->Get()->ResultArray();

         // Update the comment counts.
         foreach ($DiscussionIDs as $Row) {
            $Sender->SQL
               ->Update('Discussion')
               ->Set('CountComments', "CountComments - {$Row['CountComments']}", FALSE)
               ->Where('DiscussionID', $Row['DiscussionID'])
               ->Put();
         }
         
         $Sender->SQL->Delete('Comment', array('InsertUserID' => $UserID));
         
         // Delete the user's dicussions 
         $Sender->SQL->Delete('Discussion', array('InsertUserID' => $UserID));
      } else if ($DeleteMethod == 'wipe') {
         // Erase the user's dicussions
         $Sender->SQL->Update('Discussion')
            ->Set('Body', T('The user and all related content has been deleted.'))
            ->Set('Format', 'Deleted')
            ->Where('InsertUserID', $UserID)
            ->Put();
         
         // Erase the user's comments
			$Sender->SQL->From('Comment')
				->Join('Discussion d', 'c.DiscussionID = d.DiscussionID')
				->Delete('Comment c', array('d.InsertUserID' => $UserID));

         $Sender->SQL->Update('Comment')
            ->Set('Body', T('The user and all related content has been deleted.'))
            ->Set('Format', 'Deleted')
            ->Where('InsertUserID', $UserID)
            ->Put();
      } else {
         // Leave comments
      }

      // Remove the user's profile information related to this application
      $Sender->SQL->Update('User')
         ->Set(array(
				'CountDiscussions' => 0,
				'CountUnreadDiscussions' => 0,
				'CountComments' => 0,
				'CountDrafts' => 0,
				'CountBookmarks' => 0
			))
         ->Where('UserID', $UserID)
         ->Put();
   }
   
   /**
    * Check whether a user has access to view discussions in a particular category.
    *
    * @since 2.0.18
    * @example $UserModel->GetCategoryViewPermission($UserID, $CategoryID).
    *
    * @param $Sender UserModel.
    * @return bool Whether user has permission.
    */
   public function UserModel_GetCategoryViewPermission_Create($Sender) {
      static $PermissionModel = NULL;


      $UserID = ArrayValue(0, $Sender->EventArguments, '');
		$CategoryID = ArrayValue(1, $Sender->EventArguments, '');
		if ($UserID && $CategoryID) {
         if ($PermissionModel === NULL)
            $PermissionModel = new PermissionModel();
         
         $Category = CategoryModel::Categories($CategoryID);
         if ($Category)
            $PermissionCategoryID = $Category['PermissionCategoryID'];
         else
            $PermissionCategoryID = -1;
         
         $Result = $PermissionModel->GetUserPermissions($UserID, 'Vanilla.Discussions.View', 'Category', 'PermissionCategoryID', 'CategoryID', $PermissionCategoryID);
         return (GetValue('Vanilla.Discussions.View', GetValue(0, $Result), FALSE)) ? TRUE : FALSE;
      }
      return FALSE;
   }
   
   /**
    * Adds 'Discussion' item to menu.
    * 
    * 'Base_Render_Before' will trigger before every pageload across apps.
    * If you abuse this hook, Tim with throw a Coke can at your head.
    * 
    * @since 2.0.0
    * @package Vanilla
    * 
    * @param object $Sender DashboardController.
    */ 
   public function Base_Render_Before(&$Sender) {
      $Session = Gdn::Session();
      if ($Sender->Menu)
         $Sender->Menu->AddLink('Discussions', T('Discussions'), '/discussions', FALSE, array('Standard' => TRUE, 'class' => 'Discussions'));
   }
   
   /**
    * Adds 'Discussions' tab to profiles and adds CSS & JS files to their head.
    * 
    * @since 2.0.0
    * @package Vanilla
    * 
    * @param object $Sender ProfileController.
    */ 
   public function ProfileController_AddProfileTabs_Handler(&$Sender) {
      if (is_object($Sender->User) && $Sender->User->UserID > 0) {
         $UserID = $Sender->User->UserID;
         // Add the discussion tab
         $Sender->AddProfileTab('Discussions', 'profile/discussions/'.$Sender->User->UserID.'/'.rawurlencode($Sender->User->Name), 'Discussions', T('Discussions').CountString(GetValueR('User.CountDiscussions', $Sender, NULL), "/profile/count/discussions?userid=$UserID"));
         $Sender->AddProfileTab('Comments', 'profile/comments/'.$Sender->User->UserID.'/'.rawurlencode($Sender->User->Name), 'Comments', T('Comments').CountString(GetValueR('User.CountComments', $Sender, NULL), "/profile/count/comments?userid=$UserID"));
         // Add the discussion tab's CSS and Javascript.
         $Sender->AddJsFile('jquery.gardenmorepager.js');
         $Sender->AddJsFile('discussions.js');
      }
   }
   
   /**
    * Adds email notification options to profiles.
    * 
    * @since 2.0.0
    * @package Vanilla
    * 
    * @param object $Sender ProfileController.
    */ 
   public function ProfileController_AfterPreferencesDefined_Handler($Sender) {
      $Sender->Preferences['Notifications']['Email.DiscussionComment'] = T('Notify me when people comment on my discussions.');
      $Sender->Preferences['Notifications']['Email.DiscussionMention'] = T('Notify me when people mention me in discussion titles.');
      $Sender->Preferences['Notifications']['Email.CommentMention'] = T('Notify me when people mention me in comments.');
      $Sender->Preferences['Notifications']['Email.BookmarkComment'] = T('Notify me when people comment on my bookmarked discussions.');
      

      $Sender->Preferences['Notifications']['Popup.DiscussionComment'] = T('Notify me when people comment on my discussions.');
      $Sender->Preferences['Notifications']['Popup.DiscussionMention'] = T('Notify me when people mention me in discussion titles.');
      $Sender->Preferences['Notifications']['Popup.CommentMention'] = T('Notify me when people mention me in comments.');
      $Sender->Preferences['Notifications']['Popup.BookmarkComment'] = T('Notify me when people comment on my bookmarked discussions.');

      if (Gdn::Session()->CheckPermission('Garden.AdvancedNotifications.Allow')) {
         $Sender->Preferences['Notifications']['Email.NewDiscussion'] = array(T('Notify me when people start new discussions.'), 'Meta');
         $Sender->Preferences['Notifications']['Email.NewComment'] = array(T('Notify me when people comment on a discussion.'), 'Meta');
//      $Sender->Preferences['Notifications']['Popup.NewDiscussion'] = T('Notify me when people start new discussions.');
      }
   }
	
	/**
	 * Add the discussion search to the search.
	 * 
    * @since 2.0.0
    * @package Vanilla
	 *
	 * @param object $Sender SearchModel
	 */
	public function SearchModel_Search_Handler($Sender) {
		$SearchModel = new VanillaSearchModel();
		$SearchModel->Search($Sender);
	}
   
   /**
	 * Load forum information into the BuzzData collection.
	 * 
    * @since 2.0.0
    * @package Vanilla
	 *
	 * @param object $Sender SettingsController.
	 */
   public function SettingsController_DashboardData_Handler(&$Sender) {
      $DiscussionModel = new DiscussionModel();
      // Number of Discussions
      $CountDiscussions = $DiscussionModel->GetCount();
      $Sender->AddDefinition('CountDiscussions', $CountDiscussions);
      $Sender->BuzzData[T('Discussions')] = number_format($CountDiscussions);
      // Number of New Discussions in the last day
      $Sender->BuzzData[T('New discussions in the last day')] = number_format($DiscussionModel->GetCount(array('d.DateInserted >=' => Gdn_Format::ToDateTime(strtotime('-1 day')))));
      // Number of New Discussions in the last week
      $Sender->BuzzData[T('New discussions in the last week')] = number_format($DiscussionModel->GetCount(array('d.DateInserted >=' => Gdn_Format::ToDateTime(strtotime('-1 week')))));

      $CommentModel = new CommentModel();
      // Number of Comments
      $CountComments = $CommentModel->GetCountWhere();
      $Sender->AddDefinition('CountComments', $CountComments);
      $Sender->BuzzData[T('Comments')] = number_format($CountComments);
      // Number of New Comments in the last day
      $Sender->BuzzData[T('New comments in the last day')] = number_format($CommentModel->GetCountWhere(array('DateInserted >=' => Gdn_Format::ToDateTime(strtotime('-1 day')))));
      // Number of New Comments in the last week
      $Sender->BuzzData[T('New comments in the last week')] = number_format($CommentModel->GetCountWhere(array('DateInserted >=' => Gdn_Format::ToDateTime(strtotime('-1 week')))));
   }
   
   /**
	 * Creates virtual 'Comments' method in ProfileController.
	 * 
    * @since 2.0.0
    * @package Vanilla
	 *
	 * @param object $Sender ProfileController.
	 */
   public function ProfileController_Comments_Create(&$Sender) {
		$View = $Sender->View;
      $UserReference = ArrayValue(0, $Sender->RequestArgs, '');
		$Username = ArrayValue(1, $Sender->RequestArgs, '');
      $Offset = ArrayValue(2, $Sender->RequestArgs, 0);
      // Tell the ProfileController what tab to load
		$Sender->GetUserInfo($UserReference, $Username);
      $Sender->SetTabView('Comments', 'profile', 'Discussion', 'Vanilla');
      
      // Load the data for the requested tab.
      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
      
      $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 30);
      $CommentModel = new CommentModel();
      $Sender->CommentData = $CommentModel->GetByUser($Sender->User->UserID, $Limit, $Offset);
      $CountComments = $Offset + $Sender->CommentData->NumRows();
      if ($Sender->CommentData->NumRows() == $Limit)
         $CountComments = $Offset + $Limit + 1;
      
      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
      $Sender->Pager = $PagerFactory->GetPager('MorePager', $Sender);
      $Sender->Pager->MoreCode = 'More Comments';
      $Sender->Pager->LessCode = 'Newer Comments';
      $Sender->Pager->ClientID = 'Pager';
      $Sender->Pager->Configure(
         $Offset,
         $Limit,
         $CountComments,
         'profile/comments/'.$Sender->User->UserID.'/'.Gdn_Format::Url($Sender->User->Name).'/%1$s/'
      );
      
      // Deliver JSON data if necessary
      if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL && $Offset > 0) {
         $Sender->SetJson('LessRow', $Sender->Pager->ToString('less'));
         $Sender->SetJson('MoreRow', $Sender->Pager->ToString('more'));
         $Sender->View = 'profilecomments';
      }
		$Sender->Offset = $Offset;
      
      // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
      $Sender->HandlerType = HANDLER_TYPE_NORMAL;
      
      // Do not show discussion options
      $Sender->ShowOptions = FALSE;
      
      // Render the ProfileController
      $Sender->Render();
   }
   
   /**
	 * Creates virtual 'Discussions' method in ProfileController.
	 * 
    * @since 2.0.0
    * @package Vanilla
	 *
	 * @param object $Sender ProfileController.
	 */
   public function ProfileController_Discussions_Create(&$Sender) {
      $UserReference = ArrayValue(0, $Sender->RequestArgs, '');
		$Username = ArrayValue(1, $Sender->RequestArgs, '');
      $Offset = ArrayValue(2, $Sender->RequestArgs, 0);
      // Tell the ProfileController what tab to load
		$Sender->GetUserInfo($UserReference, $Username);
      $Sender->SetTabView('Discussions', 'Profile', 'Discussions', 'Vanilla');
      
      // Load the data for the requested tab.
      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
      
      $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 30);
      $DiscussionModel = new DiscussionModel();
      $Sender->DiscussionData = $DiscussionModel->Get($Offset, $Limit, array('d.InsertUserID' => $Sender->User->UserID));
      $CountDiscussions = $Offset + $Sender->DiscussionData->NumRows();
      if ($Sender->DiscussionData->NumRows() == $Limit)
         $CountDiscussions = $Offset + $Limit + 1;
      
      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
      $Sender->Pager = $PagerFactory->GetPager('MorePager', $Sender);
      $Sender->Pager->MoreCode = 'More Discussions';
      $Sender->Pager->LessCode = 'Newer Discussions';
      $Sender->Pager->ClientID = 'Pager';
      $Sender->Pager->Configure(
         $Offset,
         $Limit,
         $CountDiscussions,
         'profile/discussions/'.$Sender->User->UserID.'/'.Gdn_Format::Url($Sender->User->Name).'/%1$s/'
      );
      
      // Deliver JSON data if necessary
      if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL && $Offset > 0) {
         $Sender->SetJson('LessRow', $Sender->Pager->ToString('less'));
         $Sender->SetJson('MoreRow', $Sender->Pager->ToString('more'));
         $Sender->View = 'discussions';
      }
      
      // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
      $Sender->HandlerType = HANDLER_TYPE_NORMAL;
      
      // Do not show discussion options
      $Sender->ShowOptions = FALSE;
      
      // Render the ProfileController
      $Sender->Render();
   }
   
   /**
	 * Makes sure forum administrators can see the dashboard admin pages.
	 * 
    * @since 2.0.0
    * @package Vanilla
	 *
	 * @param object $Sender SettingsController.
	 */
   public function SettingsController_DefineAdminPermissions_Handler(&$Sender) {
      if (isset($Sender->RequiredAdminPermissions)) {
         $Sender->RequiredAdminPermissions[] = 'Vanilla.Settings.Manage';
         $Sender->RequiredAdminPermissions[] = 'Vanilla.Categories.Manage';
         $Sender->RequiredAdminPermissions[] = 'Vanilla.Spam.Manage';
      }
   }
   
   public function Gdn_Statistics_Tick_Handler($Sender, $Args) {
      $Path = GetValue('Path', $Args);
      if (preg_match('`discussion\/(\d+)`i', $Path, $Matches)) {
         $DiscussionID = $Matches[1];
      } elseif(preg_match('`discussion\/comment\/(\d+)`i', $Path, $Matches)) {
         $CommentID = $Matches[1];
         $CommentModel = new CommentModel();
         $Comment = $CommentModel->GetID($CommentID);
         $DiscussionID = GetValue('DiscussionID', $Comment);
      }
      
      if (isset($DiscussionID)) {
         $DiscussionModel = new DiscussionModel();
         $Discussion = $DiscussionModel->GetID($DiscussionID);
         $DiscussionModel->AddView($DiscussionID, GetValue('CountViews', $Discussion));
      }
   }
   
   /**
	 * Adds items to dashboard menu.
	 * 
    * @since 2.0.0
    * @package Vanilla
	 *
	 * @param object $Sender DashboardController.
	 */
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Forum', T('Categories'), 'vanilla/settings/managecategories', 'Vanilla.Categories.Manage');
      $Menu->AddLink('Forum', T('Flood Control'), 'vanilla/settings/floodcontrol', 'Vanilla.Spam.Manage');
      $Menu->AddLink('Forum', T('Advanced'), 'vanilla/settings/advanced', 'Vanilla.Settings.Manage');
   }
   
   /**
	 * Automatically executed when application is enabled.
	 * 
    * @since 2.0.0
    * @package Vanilla
	 */
   public function Setup() {
      $Database = Gdn::Database();
      $Config = Gdn::Factory(Gdn::AliasConfig);
      $Drop = Gdn::Config('Vanilla.Version') === FALSE ? TRUE : FALSE;
      $Explicit = TRUE;
      
      // Call structure.php to update database
      $Validation = new Gdn_Validation(); // Needed by structure.php to validate permission names
      include(PATH_APPLICATIONS . DS . 'vanilla' . DS . 'settings' . DS . 'structure.php');
      
      $ApplicationInfo = array();
      include(CombinePaths(array(PATH_APPLICATIONS . DS . 'vanilla' . DS . 'settings' . DS . 'about.php')));
      $Version = ArrayValue('Version', ArrayValue('Vanilla', $ApplicationInfo, array()), 'Undefined');
      $Save = array(
	      'Vanilla.Version' => $Version,
	      'Routes.DefaultController' => 'discussions'
      );
      SaveToConfig($Save);
   }
}