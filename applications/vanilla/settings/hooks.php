<?php if (!defined('APPLICATION')) exit();

class VanillaHooks implements Gdn_IPlugin {
   public function Setup() {
      return TRUE;
   }
   
   public function Gdn_UserModel_SessionQuery_Handler(&$Sender) {
      // Add some extra fields to the session query
      $Sender->SQL->Select('u.CountDiscussions, u.CountUnreadDiscussions, u.CountDrafts, u.CountBookmarks');
   }
   
   public function Base_Render_Before(&$Sender) {
      // Add menu items.
      $Session = Gdn::Session();
      if ($Sender->Menu) {
         $DiscussionsHome = '/'.Gdn::Config('Vanilla.Discussions.Home', 'discussions');
         $Sender->Menu->AddLink(Gdn::Translate('Discussions'), Gdn::Translate('Discussions'), $DiscussionsHome, FALSE);
         if ($Session->IsValid()) {
            $Bookmarked = Gdn::Translate('My Bookmarks');
            $CountBookmarks = $Session->User->CountBookmarks;
            if (is_numeric($CountBookmarks) && $CountBookmarks > 0)
               $Bookmarked .= '<span>'.$CountBookmarks.'</span>';            
            
            $Sender->Menu->AddLink(Gdn::Translate('Discussions'), '\\'.$Bookmarked, '/discussions/bookmarked', FALSE, array('class' => 'MyBookmarks'));
            $MyDiscussions = Gdn::Translate('My Discussions');
            $CountDiscussions = $Session->User->CountDiscussions;
            if (is_numeric($CountDiscussions) && $CountDiscussions > 0)
               $MyDiscussions .= '<span>'.$CountDiscussions.'</span>';            

            $Sender->Menu->AddLink(Gdn::Translate('Discussions'), '\\'.$MyDiscussions, '/discussions/mine', FALSE, array('class' => 'MyDiscussions'));
            $MyDrafts = Gdn::Translate('My Drafts');
            $CountDrafts = $Session->User->CountDrafts;
            if (is_numeric($CountDrafts) && $CountDrafts > 0)
               $MyDrafts .= '<span>'.$CountDrafts.'</span>';            

            $Sender->Menu->AddLink(Gdn::Translate('Discussions'), '\\'.$MyDrafts, '/drafts', FALSE, array('class' => 'MyDrafts'));
         }
         if ($Session->IsValid())
            $Sender->Menu->AddLink(Gdn::Translate('Discussions'), Gdn::Translate('New Discussion'), '/post/discussion', FALSE);
      }
   }
   
   public function ProfileController_AddProfileTabs_Handler(&$Sender) {
      if (is_object($Sender->User) && $Sender->User->UserID > 0 && $Sender->User->CountDiscussions > 0) {
         // Add the discussion tab
         $Sender->AddProfileTab(Gdn::Translate('Discussions'), 'profile/discussions/'.$Sender->User->UserID.'/'.urlencode($Sender->User->Name));
         // Add the discussion tab's css
         $Sender->AddCssFile('vanillaprofile.css', 'vanilla');
         $Sender->AddJsFile('/js/library/jquery.gardenmorepager.js');
         $Sender->AddJsFile('discussions.js');
      }
   }
   
   public function ProfileController_AfterPreferencesDefined_Handler(&$Sender) {
      $Sender->Preferences['Email Notifications']['Email.DiscussionComment'] = Gdn::Translate('Notify me when people comment on my discussions.');
      $Sender->Preferences['Email Notifications']['Email.DiscussionMention'] = Gdn::Translate('Notify me when people mention me in discussion titles.');
      $Sender->Preferences['Email Notifications']['Email.CommentMention'] = Gdn::Translate('Notify me when people mention me in comments.');
   }
	
	/**
	 * Add the discussion search to the search.
	 * @param SearchController $Sender
	 */
	public function SearchController_Search_Handler($Sender) {
		include_once(dirname(__FILE__).DS.'..'.DS.'models'.DS.'class.vanillasearchmodel.php');
		$SearchModel = new Gdn_VanillaSearchModel();
		$SearchModel->Search($Sender->SearchModel);
	}
   
   // Load some information into the BuzzData collection
   public function SettingsController_DashboardData_Handler(&$Sender) {
      $DiscussionModel = new Gdn_DiscussionModel();
      // Number of Discussions
      $CountDiscussions = $DiscussionModel->GetCount();
      $Sender->AddDefinition('CountDiscussions', $CountDiscussions);
      $Sender->BuzzData[Gdn::Translate('Discussions')] = number_format($CountDiscussions);
      // Number of New Discussions in the last day
      $Sender->BuzzData[Translate('New discussions in the last day')] = number_format($DiscussionModel->GetCount(array('d.DateInserted >=' => Format::ToDateTime(strtotime('-1 day')))));
      // Number of New Discussions in the last week
      $Sender->BuzzData[Translate('New discussions in the last week')] = number_format($DiscussionModel->GetCount(array('d.DateInserted >=' => Format::ToDateTime(strtotime('-1 week')))));

      $CommentModel = new Gdn_CommentModel();
      // Number of Comments
      $CountComments = $CommentModel->GetCountWhere();
      $Sender->AddDefinition('CountComments', $CountComments);
      $Sender->BuzzData[Gdn::Translate('Comments')] = number_format($CountComments);
      // Number of New Comments in the last day
      $Sender->BuzzData[Translate('New comments in the last day')] = number_format($CommentModel->GetCountWhere(array('DateInserted >=' => Format::ToDateTime(strtotime('-1 day')))));
      // Number of New Comments in the last week
      $Sender->BuzzData[Translate('New comments in the last week')] = number_format($CommentModel->GetCountWhere(array('DateInserted >=' => Format::ToDateTime(strtotime('-1 week')))));
   }
   
   public function ProfileController_Discussions_Create(&$Sender) {
      $UserReference = ArrayValue(0, $Sender->RequestArgs, '');
      $Offset = ArrayValue(1, $Sender->RequestArgs, 0);
      // Tell the ProfileController what tab to load
      $Sender->SetTabView($UserReference, 'Discussions', 'Profile', 'Discussions', 'Vanilla');
      
      // Load the data for the requested tab.
      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
      
      $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 30);
      $DiscussionModel = new Gdn_DiscussionModel();
      $Sender->DiscussionData = $DiscussionModel->Get($Offset, $Limit, array('d.InsertUserID' => $Sender->User->UserID));
      $CountDiscussions = $Offset + $Sender->DiscussionData->NumRows();
      if ($Sender->DiscussionData->NumRows() == $Limit)
         $CountDiscussions = $Offset + $Limit + 1;
      
      // Build a pager
      $PagerFactory = new PagerFactory();
      $Sender->Pager = $PagerFactory->GetPager('MorePager', $Sender);
      $Sender->Pager->MoreCode = 'More Discussions';
      $Sender->Pager->LessCode = 'Newer Discussions';
      $Sender->Pager->ClientID = 'Pager';
      $Sender->Pager->Configure(
         $Offset,
         $Limit,
         $CountDiscussions,
         'profile/discussions/'.Format::Url($Sender->User->Name).'/%1$s/'
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
   function SettingsController_DefineAdminPermissions_Handler(&$Sender) {
      if (isset($Sender->RequiredAdminPermissions)) {
         $Sender->RequiredAdminPermissions[] = 'Vanilla.Settings.Manage';
         $Sender->RequiredAdminPermissions[] = 'Vanilla.Categories.Manage';
         $Sender->RequiredAdminPermissions[] = 'Vanilla.Spam.Manage';
      }
   }
   
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Forum', Gdn::Translate('Forum'));
      $Menu->AddLink('Forum', Gdn::Translate('General'), 'vanilla/settings/general', 'Vanilla.Settings.Manage');
      $Menu->AddLink('Forum', Gdn::Translate('Spam'), 'vanilla/settings/spam', 'Vanilla.Spam.Manage');
      $Menu->AddLink('Forum', Gdn::Translate('Categories'), 'vanilla/settings/managecategories', 'Vanilla.Categories.Manage');
   }
   
}