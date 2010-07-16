<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class VanillaHooks implements Gdn_IPlugin {
   
   public function UserModel_SessionQuery_Handler(&$Sender) {
      // Add some extra fields to the session query
      //$Sender->SQL->Select('u.CountDiscussions, u.CountUnreadDiscussions, u.CountDrafts, u.CountBookmarks');
   }
   
	// Remove data when deleting a user
   public function UserModel_BeforeDeleteUser_Handler($Sender) {
      $UserID = GetValue('UserID', $Sender->EventArguments);
      $Options = GetValue('Options', $Sender->EventArguments, array());
      $Options = is_array($Options) ? $Options : array();

		$Sender->SQL->Delete('UserDiscussion', array('UserID' => $UserID));
		$Sender->SQL->Delete('Draft', array('InsertUserID' => $UserID));
      
      $DeleteMethod = GetValue('DeleteMethod', $Options, 'delete');
      if ($DeleteMethod == 'delete') {
         $Sender->SQL->Delete('Comment', array('InsertUserID' => $UserID));
      } else if ($DeleteMethod == 'wipe') {
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
		$Sender->SQL->Delete('Discussion', array('InsertUserID' => $UserID));

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

   public function Base_Render_Before(&$Sender) {
      // Add menu items.
      $Session = Gdn::Session();
      if ($Sender->Menu) {
         $Sender->Menu->AddLink('Discussions', T('Discussions'), '/discussions', FALSE);
      }
   }
   
   public function ProfileController_AddProfileTabs_Handler(&$Sender) {
      if (is_object($Sender->User) && $Sender->User->UserID > 0 && $Sender->User->CountDiscussions > 0) {
         // Add the discussion tab
         $Sender->AddProfileTab(T('Discussions'), 'profile/discussions/'.$Sender->User->UserID.'/'.urlencode($Sender->User->Name));
         // Add the discussion tab's css
         $Sender->AddCssFile('vanillaprofile.css', 'vanilla');
         $Sender->AddJsFile('jquery.gardenmorepager.js');
         $Sender->AddJsFile('discussions.js');
      }
   }
   
   public function ProfileController_AfterPreferencesDefined_Handler(&$Sender) {
      $Sender->Preferences['Email Notifications']['Email.DiscussionComment'] = T('Notify me when people comment on my discussions.');
      $Sender->Preferences['Email Notifications']['Email.DiscussionMention'] = T('Notify me when people mention me in discussion titles.');
      $Sender->Preferences['Email Notifications']['Email.CommentMention'] = T('Notify me when people mention me in comments.');
      $Sender->Preferences['Email Notifications']['Email.BookmarkComment'] = T('Notify me when people comment on my bookmarked discussions.');
   }
	
	/**
	 * Add the discussion search to the search.
	 * @param SearchController $Sender
	 */
	public function SearchModel_Search_Handler($Sender) {
		include_once(dirname(__FILE__).DS.'..'.DS.'models'.DS.'class.vanillasearchmodel.php');
		$SearchModel = new VanillaSearchModel();
		$SearchModel->Search($Sender);
	}
   
   // Load some information into the BuzzData collection
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
      
      // Deliver json data if necessary
      if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL && $Offset > 0) {
         $Sender->SetJson('LessRow', $Sender->Pager->ToString('less'));
         $Sender->SetJson('MoreRow', $Sender->Pager->ToString('more'));
         $Sender->View = 'discussions';
      }
      
      // Set the handlertype back to normal on the profilecontroller so that it fetches it's own views
      $Sender->HandlerType = HANDLER_TYPE_NORMAL;
      // Do not show discussion options
      $Sender->ShowOptions = FALSE;
      // Render the ProfileController
      $Sender->Render();
   }
   
   /**
    * Make sure that vanilla administrators can see the garden admin pages.
    */
   public function SettingsController_DefineAdminPermissions_Handler(&$Sender) {
      if (isset($Sender->RequiredAdminPermissions)) {
         $Sender->RequiredAdminPermissions[] = 'Vanilla.Settings.Manage';
         $Sender->RequiredAdminPermissions[] = 'Vanilla.Categories.Manage';
         $Sender->RequiredAdminPermissions[] = 'Vanilla.Spam.Manage';
      }
   }
   
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Forum', T('Categories'), 'vanilla/settings/managecategories', 'Vanilla.Categories.Manage');
      $Menu->AddLink('Forum', T('Spam'), 'vanilla/settings/spam', 'Vanilla.Spam.Manage');
      $Menu->AddLink('Forum', T('Advanced'), 'vanilla/settings/advanced', 'Vanilla.Settings.Manage');
   }
   
   public function Setup() {
      $Database = Gdn::Database();
      $Config = Gdn::Factory(Gdn::AliasConfig);
      $Drop = Gdn::Config('Vanilla.Version') === FALSE ? TRUE : FALSE;
      $Explicit = TRUE;
      $Validation = new Gdn_Validation(); // This is going to be needed by structure.php to validate permission names
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