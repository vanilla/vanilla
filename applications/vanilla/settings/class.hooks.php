<?php if (!defined('APPLICATION')) exit();
 
/**
 * VanillaHooks Plugin
 *
 * The class.hooks.php file is essentially a giant plugin container for an app
 * that is automatically enabled when the app is.
 *
 * @link http://vanillaforums.org/docs/PluginQuickStart
 * @since 2.0.0
 * @package Vanilla
 */
class VanillaHooks implements Gdn_IPlugin {
   
   /**
    *
    * @param DbaController $Sender 
    */
   public function DbaController_CountJobs_Handler($Sender) {
      $Counts = array(
          'Discussion' => array('CountComments', 'FirstCommentID', 'LastCommentID', 'DateLastComment', 'LastCommentUserID'),
          'Category' => array('CountDiscussions', 'CountComments', 'LastDiscussionID', 'LastCommentID', 'LastDateInserted')
      );
      
      foreach ($Counts as $Table => $Columns) {
         foreach ($Columns as $Column) {
            $Name = "Recalculate $Table.$Column";
            $Url = "/dba/counts.json?".http_build_query(array('table' => $Table, 'column' => $Column));
            
            $Sender->Data['Jobs'][$Name] = $Url;
         }
      }
   }
   
   /**
    * Delete all of the Vanilla related information for a specific user.
    * @param int $UserID The ID of the user to delete.
    * @param array $Options An array of options:
    *  - DeleteMethod: One of delete, wipe, or NULL
    * @since 2.1
    */
   public function DeleteUserData($UserID, $Options = array(), &$Data = NULL) {
      $SQL = Gdn::SQL();
      
      // Remove discussion watch records and drafts.
		$SQL->Delete('UserDiscussion', array('UserID' => $UserID));
      
		Gdn::UserModel()->GetDelete('Draft', array('InsertUserID' => $UserID), $Data);
      
      // Comment deletion depends on method selected
      $DeleteMethod = GetValue('DeleteMethod', $Options, 'delete');
      if ($DeleteMethod == 'delete') {
         // Clear out the last posts to the categories.
         $SQL
            ->Update('Category c')
            ->Join('Discussion d', 'd.DiscussionID = c.LastDiscussionID')
            ->Where('d.InsertUserID', $UserID)
            ->Set('c.LastDiscussionID', NULL)
            ->Set('c.LastCommentID', NULL)
            ->Put();
         
         $SQL
            ->Update('Category c')
            ->Join('Comment d', 'd.CommentID = c.LastCommentID')
            ->Where('d.InsertUserID', $UserID)
            ->Set('c.LastDiscussionID', NULL)
            ->Set('c.LastCommentID', NULL)
            ->Put();
         
         // Grab all of the discussions that the user has engaged in.
         $DiscussionIDs = $SQL
            ->Select('DiscussionID')
            ->From('Comment')
            ->Where('InsertUserID', $UserID)
            ->GroupBy('DiscussionID')
            ->Get()->ResultArray();
         $DiscussionIDs = ConsolidateArrayValuesByKey($DiscussionIDs, 'DiscussionID');

         
         Gdn::UserModel()->GetDelete('Comment', array('InsertUserID' => $UserID), $Data);
         
         // Update the comment counts.
         $CommentCounts = $SQL
            ->Select('DiscussionID')
            ->Select('CommentID', 'count', 'CountComments')
            ->Select('CommentID', 'max', 'LastCommentID')
            ->WhereIn('DiscussionID', $DiscussionIDs)
            ->GroupBy('DiscussionID')
            ->Get('Comment')->ResultArray();
         
         foreach ($CommentCounts as $Row) {
            $SQL->Put('Discussion',
               array('CountComments' => $Row['CountComments'] + 1, 'LastCommentID' => $Row['LastCommentID']),
               array('DiscussionID' => $Row['DiscussionID']));
         }
         
         // Update the last user IDs.
         $SQL->Update('Discussion d')
            ->Join('Comment c', 'd.LastCommentID = c.CommentID', 'left')
            ->Set('d.LastCommentUserID', 'c.InsertUserID', FALSE, FALSE)
            ->Set('d.DateLastComment', 'c.DateInserted', FALSE, FALSE)
            ->WhereIn('d.DiscussionID', $DiscussionIDs)
            ->Put();
         
         // Update the last posts.
         $Discussions = $SQL
            ->WhereIn('DiscussionID', $DiscussionIDs)
            ->Where('LastCommentUserID', $UserID)
            ->Get('Discussion');
         
         // Delete the user's dicussions 
         Gdn::UserModel()->GetDelete('Discussion', array('InsertUserID' => $UserID), $Data);
         
         // Update the appropriat recent posts in the categories.
         $CategoryModel = new CategoryModel();
         $Categories = $CategoryModel->GetWhere(array('LastDiscussionID' => NULL))->ResultArray();
         foreach ($Categories as $Category) {
            $CategoryModel->SetRecentPost($Category['CategoryID']);
         }
      } else if ($DeleteMethod == 'wipe') {
         // Erase the user's dicussions
         $SQL->Update('Discussion')
            ->Set('Body', T('The user and all related content has been deleted.'))
            ->Set('Format', 'Deleted')
            ->Where('InsertUserID', $UserID)
            ->Put();

         $SQL->Update('Comment')
            ->Set('Body', T('The user and all related content has been deleted.'))
            ->Set('Format', 'Deleted')
            ->Where('InsertUserID', $UserID)
            ->Put();
      } else {
         // Leave comments
      }

      // Remove the user's profile information related to this application
      $SQL->Update('User')
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
	 * Remove Vanilla data when deleting a user.
    *
    * @since 2.0.0
    * @package Vanilla
    * 
    * @param UserModel $Sender UserModel.
    */
   public function UserModel_BeforeDeleteUser_Handler($Sender) {
      $UserID = GetValue('UserID', $Sender->EventArguments);
      $Options = GetValue('Options', $Sender->EventArguments, array());
      $Options = is_array($Options) ? $Options : array();
      $Content =& $Sender->EventArguments['Content'];
      
      $this->DeleteUserData($UserID, $Options, $Content);
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
      $Permission = GetValue(2, $Sender->EventArguments, 'Vanilla.Discussions.View');
		if ($UserID && $CategoryID) {
         if ($PermissionModel === NULL)
            $PermissionModel = new PermissionModel();
         
         $Category = CategoryModel::Categories($CategoryID);
         if ($Category)
            $PermissionCategoryID = $Category['PermissionCategoryID'];
         else
            $PermissionCategoryID = -1;
         
         $Result = $PermissionModel->GetUserPermissions($UserID, $Permission, 'Category', 'PermissionCategoryID', 'CategoryID', $PermissionCategoryID);
         return (GetValue($Permission, GetValue(0, $Result), FALSE)) ? TRUE : FALSE;
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
   public function Base_Render_Before($Sender) {
      $Session = Gdn::Session();
      if ($Sender->Menu)
         $Sender->Menu->AddLink('Discussions', T('Discussions'), '/discussions', FALSE, array('Standard' => TRUE));
   }
   
   /**
    * Adds 'Discussions' tab to profiles and adds CSS & JS files to their head.
    * 
    * @since 2.0.0
    * @package Vanilla
    * 
    * @param object $Sender ProfileController.
    */ 
   public function ProfileController_AddProfileTabs_Handler($Sender) {
      if (is_object($Sender->User) && $Sender->User->UserID > 0) {
         $UserID = $Sender->User->UserID;
         // Add the discussion tab
         $DiscussionsLabel = Sprite('SpDiscussions').' '.T('Discussions');
         $CommentsLabel = Sprite('SpComments').' '.T('Comments');
         if (C('Vanilla.Profile.ShowCounts', TRUE)) {
            $DiscussionsLabel .= '<span class="Aside">'.CountString(GetValueR('User.CountDiscussions', $Sender, NULL), "/profile/count/discussions?userid=$UserID").'</span>';
            $CommentsLabel .= '<span class="Aside">'.CountString(GetValueR('User.CountComments', $Sender, NULL), "/profile/count/comments?userid=$UserID").'</span>';
         }
         $Sender->AddProfileTab(T('Discussions'), 'profile/discussions/'.$Sender->User->UserID.'/'.rawurlencode($Sender->User->Name), 'Discussions', $DiscussionsLabel);
         $Sender->AddProfileTab(T('Comments'), 'profile/comments/'.$Sender->User->UserID.'/'.rawurlencode($Sender->User->Name), 'Comments', $CommentsLabel);
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
    * @param ProfileController $Sender
    */ 
   public function ProfileController_AfterPreferencesDefined_Handler($Sender) {
      $Sender->Preferences['Notifications']['Email.DiscussionComment'] = T('Notify me when people comment on my discussions.');
      $Sender->Preferences['Notifications']['Email.BookmarkComment'] = T('Notify me when people comment on my bookmarked discussions.');
      $Sender->Preferences['Notifications']['Email.Mention'] = T('Notify me when people mention me.');
      

      $Sender->Preferences['Notifications']['Popup.DiscussionComment'] = T('Notify me when people comment on my discussions.');
      $Sender->Preferences['Notifications']['Popup.BookmarkComment'] = T('Notify me when people comment on my bookmarked discussions.');
      $Sender->Preferences['Notifications']['Popup.Mention'] = T('Notify me when people mention me.');

//      if (Gdn::Session()->CheckPermission('Garden.AdvancedNotifications.Allow')) {
//         $Sender->Preferences['Notifications']['Email.NewDiscussion'] = array(T('Notify me when people start new discussions.'), 'Meta');
//         $Sender->Preferences['Notifications']['Email.NewComment'] = array(T('Notify me when people comment on a discussion.'), 'Meta');
////      $Sender->Preferences['Notifications']['Popup.NewDiscussion'] = T('Notify me when people start new discussions.');
//      }
      
      if (Gdn::Session()->CheckPermission('Garden.AdvancedNotifications.Allow')) {
         $PostBack = $Sender->Form->IsPostBack();
         $Set = array();

         // Add the category definitions to for the view to pick up.
         $DoHeadings = C('Vanilla.Categories.DoHeadings');
         // Grab all of the categories.
         $Categories = array();
         $Prefixes = array('Email.NewDiscussion', 'Popup.NewDiscussion', 'Email.NewComment', 'Popup.NewComment');
         foreach (CategoryModel::Categories() as $Category) {
            if (!$Category['PermsDiscussionsView'] || $Category['Depth'] <= 0 || $Category['Depth'] > 2 || $Category['Archived'])
               continue;

            $Category['Heading'] = ($DoHeadings && $Category['Depth'] <= 1);
            $Categories[] = $Category;

            if ($PostBack) {
               foreach ($Prefixes as $Prefix) {
                  $FieldName = "$Prefix.{$Category['CategoryID']}";
                  $Value = $Sender->Form->GetFormValue($FieldName, NULL);
                  if (!$Value)
                     $Value = NULL;
                  $Set[$FieldName] = $Value;
               }
            }
         }
         $Sender->SetData('CategoryNotifications', $Categories);
         if ($PostBack) {
            UserModel::SetMeta($Sender->User->UserID, $Set, 'Preferences.');
         }
      }
   }
   
   /**
    *
    * @param ProfileController $Sender 
    */
   public function ProfileController_CustomNotificationPreferneces_Handler($Sender) {
      if (!$Sender->Data('NoEmail') && Gdn::Session()->CheckPermission('Garden.AdvancedNotifications.Allow')) {
         include $Sender->FetchViewLocation('NotificationPreferences', 'Settings', 'Vanilla');
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
   public function SettingsController_DashboardData_Handler($Sender) {
      /*
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
      */
   }
   
   /**
	 * Creates virtual 'Comments' method in ProfileController.
	 * 
    * @since 2.0.0
    * @package Vanilla
	 *
	 * @param ProfileController $Sender ProfileController.
	 */
   public function ProfileController_Comments_Create($Sender, $UserReference = '', $Username = '', $Page = '', $UserID = '') {
		$Sender->EditMode(FALSE);
		$View = $Sender->View;
      // Tell the ProfileController what tab to load
		$Sender->GetUserInfo($UserReference, $Username, $UserID);
      $Sender->_SetBreadcrumbs(T('Comments'), '/profile/comments');
      $Sender->SetTabView('Comments', 'profile', 'Discussion', 'Vanilla');
      
      $PageSize = Gdn::Config('Vanilla.Discussions.PerPage', 30);
      list($Offset, $Limit) = OffsetLimit($Page, $PageSize);
      
      $CommentModel = new CommentModel();
      $Comments = $CommentModel->GetByUser2($Sender->User->UserID, $Limit, $Offset, $Sender->Request->Get('lid'));
      $TotalRecords = $Offset + $CommentModel->LastCommentCount + 1;
      
      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
      $Sender->Pager = $PagerFactory->GetPager('MorePager', $Sender);
      $Sender->Pager->MoreCode = 'More Comments';
      $Sender->Pager->LessCode = 'Newer Comments';
      $Sender->Pager->ClientID = 'Pager';
      $Sender->Pager->Configure(
         $Offset,
         $Limit,
         $TotalRecords,
         UserUrl($Sender->User, '', 'comments').'?page={Page}' //?lid='.$CommentModel->LastCommentID
      );
      
      // Deliver JSON data if necessary
      if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL && $Offset > 0) {
         $Sender->SetJson('LessRow', $Sender->Pager->ToString('less'));
         $Sender->SetJson('MoreRow', $Sender->Pager->ToString('more'));
         $Sender->View = 'profilecomments';
      }
		$Sender->SetData('Comments', $Comments);
      
      // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
      $Sender->HandlerType = HANDLER_TYPE_NORMAL;
      
      // Do not show discussion options
      $Sender->ShowOptions = FALSE;
      
      if ($Sender->Head) {
         $Sender->Head->AddTag('meta', array('name' => 'robots', 'content' => 'noindex,noarchive'));
      }
      
      // Render the ProfileController
      $Sender->Render();
   }
   
   /**
	 * Creates virtual 'Discussions' method in ProfileController.
	 * 
    * @since 2.0.0
    * @package Vanilla
	 *
	 * @param ProfileController $Sender ProfileController.
	 */
   public function ProfileController_Discussions_Create($Sender, $UserReference = '', $Username = '', $Page = '', $UserID = '') {
		$Sender->EditMode(FALSE);
		
      // Tell the ProfileController what tab to load
		$Sender->GetUserInfo($UserReference, $Username, $UserID);
      $Sender->_SetBreadcrumbs(T('Discussions'), '/profile/discussions');
      $Sender->SetTabView('Discussions', 'Profile', 'Discussions', 'Vanilla');
		$Sender->CountCommentsPerPage = C('Vanilla.Comments.PerPage', 30);
      
      list($Offset, $Limit) = OffsetLimit($Page, Gdn::Config('Vanilla.Discussions.PerPage', 30));
      
      $DiscussionModel = new DiscussionModel();
      $Discussions = $DiscussionModel->GetByUser($Sender->User->UserID, $Limit, $Offset, FALSE, Gdn::Session()->UserID);
      $CountDiscussions = $Offset + $DiscussionModel->LastDiscussionCount + 1;
      $Sender->DiscussionData = $Sender->SetData('Discussions', $Discussions);
      
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
         UserUrl($Sender->User, '', 'discussions').'/{Page}'
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
      
      if ($Sender->Head) {
         // These pages offer only duplicate content to search engines and are a bit slow.
         $Sender->Head->AddTag('meta', array('name' => 'robots', 'content' => 'noindex,noarchive'));
      }
      
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
   public function SettingsController_DefineAdminPermissions_Handler($Sender) {
      if (isset($Sender->RequiredAdminPermissions)) {
         $Sender->RequiredAdminPermissions[] = 'Garden.Settings.Manage';
      }
   }
   
   public function Gdn_Statistics_Tick_Handler($Sender, $Args) {
      $Path = Gdn::Request()->Post('Path');
      $Args = Gdn::Request()->Post('Args');
      parse_str($Args, $Args);
      $ResolvedPath = trim(Gdn::Request()->Post('ResolvedPath'), '/');
      $ResolvedArgs = @json_decode(Gdn::Request()->Post('ResolvedArgs'));
      $DiscussionID = NULL;
      $DiscussionModel = new DiscussionModel();
      
//      Gdn::Controller()->SetData('Path', $Path);
//      Gdn::Controller()->SetData('Args', $Args);
//      Gdn::Controller()->SetData('ResolvedPath', $ResolvedPath);
//      Gdn::Controller()->SetData('ResolvedArgs', $ResolvedArgs);
      
      // Comment permalink
      if ($ResolvedPath == 'vanilla/discussion/comment') {
         $CommentID = GetValue('CommentID', $ResolvedArgs);
         $CommentModel = new CommentModel();
         $Comment = $CommentModel->GetID($CommentID);
         $DiscussionID = GetValue('DiscussionID', $Comment);
      } 
      
      // Discussion link
      elseif ($ResolvedPath == 'vanilla/discussion/index') {
         $DiscussionID = GetValue('DiscussionID', $ResolvedArgs, NULL);
      }
      
      // Embedded discussion
      elseif ($ResolvedPath == 'vanilla/discussion/embed') {
         $ForeignID = GetValue('vanilla_identifier', $Args);
         if ($ForeignID) {
            // This will be hit a lot so let's try caching it...
            $Key = "DiscussionID.ForeignID.page.$ForeignID";
            $DiscussionID = Gdn::Cache()->Get($Key);
            if (!$DiscussionID) {
               $Discussion = $DiscussionModel->GetForeignID($ForeignID, 'page');
               $DiscussionID = GetValue('DiscussionID', $Discussion);
               Gdn::Cache()->Store($Key, $DiscussionID, array(Gdn_Cache::FEATURE_EXPIRY, 1800));
            }
         }
      }
      
      if ($DiscussionID)
         $DiscussionModel->AddView($DiscussionID);
   }
   
   /**
	 * Adds items to dashboard menu.
	 * 
    * @since 2.0.0
    * @package Vanilla
	 *
	 * @param object $Sender DashboardController.
	 */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Moderation', T('Flood Control'), 'vanilla/settings/floodcontrol', 'Garden.Settings.Manage');
      $Menu->AddLink('Forum', T('Categories'), 'vanilla/settings/managecategories', 'Garden.Settings.Manage');
      $Menu->AddLink('Forum', T('Advanced'), 'vanilla/settings/advanced', 'Garden.Settings.Manage');
      $Menu->AddLink('Forum', T('Blog Comments'), 'dashboard/embed/comments', 'Garden.Settings.Manage');
      $Menu->AddLink('Forum', T('Embed Forum'), 'dashboard/embed/forum', 'Garden.Settings.Manage');
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
      include(PATH_APPLICATIONS . DS . 'vanilla' . DS . 'settings' . DS . 'stub.php');
      
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