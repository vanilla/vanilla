<?php if (!defined('APPLICATION')) exit();

class VanillaHooks implements IPlugin {
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
         $Sender->Menu->AddLink(Gdn::Translate('Discussions'), 'Discussions', $DiscussionsHome, FALSE);
         if ($Session->IsValid()) {
            $Bookmarked = Gdn::Translate('My Bookmarks');
            $CountBookmarks = $Session->User->CountBookmarks;
            if (is_numeric($CountBookmarks) && $CountBookmarks > 0)
               $Bookmarked .= '<span>'.$CountBookmarks.'</span>';            
            
            $Sender->Menu->AddLink(Gdn::Translate('Discussions'), $Bookmarked, '/discussions/bookmarked', FALSE, array('class' => 'MyBookmarks'));
            $MyDiscussions = Gdn::Translate('My Discussions');
            $CountDiscussions = $Session->User->CountDiscussions;
            if (is_numeric($CountDiscussions) && $CountDiscussions > 0)
               $MyDiscussions .= '<span>'.$CountDiscussions.'</span>';            

            $Sender->Menu->AddLink(Gdn::Translate('Discussions'), $MyDiscussions, '/discussions/mine', FALSE, array('class' => 'MyDiscussions'));
            $MyDrafts = Gdn::Translate('My Drafts');
            $CountDrafts = $Session->User->CountDrafts;
            if (is_numeric($CountDrafts) && $CountDrafts > 0)
               $MyDrafts .= '<span>'.$CountDrafts.'</span>';            

            $Sender->Menu->AddLink(Gdn::Translate('Discussions'), $MyDrafts, '/drafts', FALSE, array('class' => 'MyDrafts'));
         }
         if ($Session->IsValid())
            $Sender->Menu->AddLink(Gdn::Translate('Discussions'), 'New Discussion', '/post/discussion', FALSE);
      }
   }
   
   public function ProfileController_AddProfileTabs_Handler(&$Sender) {
      // Add the discussion tab
      $Sender->AddProfileTab(Gdn::Translate('Discussions'));
      // Add the discussion tab's css
      $Sender->AddCssFile('profile.screen.css', 'vanilla');
      if ($Sender->Head) {
         $Sender->Head->AddScript('/js/library/jquery.gardenmorepager.js');
         $Sender->Head->AddScript('/applications/vanilla/js/discussions.js');
      }
   }
   
   public function ProfileController_Discussions_Create(&$Sender) {
      $UserReference = ArrayValue(0, $Sender->EventArguments, '');
      $Offset = ArrayValue(1, $Sender->EventArguments, 0);
      // Tell the ProfileController what tab to load
      $Sender->SetTabView($UserReference, 'Discussions', 'Profile', 'Discussions', 'Vanilla');
      
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
      $PagerFactory = new PagerFactory();
      $Sender->Pager = $PagerFactory->GetPager('MorePager', $Sender);
      $Sender->Pager->MoreCode = 'More Discussions';
      $Sender->Pager->LessCode = 'Newer Discussions';
      $Sender->Pager->ClientID = 'Pager';
      $Sender->Pager->Configure(
         $Offset,
         $Limit,
         $CountDiscussions,
         'profile/discussions/'.urlencode($Sender->User->Name).'/%1$s/'
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
      $Menu->AddItem('Forum', 'Forum');
      $Menu->AddLink('Forum', 'General', 'vanilla/settings', 'Vanilla.Settings.Manage');
      $Menu->AddLink('Forum', 'Spam', 'vanilla/settings/spam', 'Vanilla.Spam.Manage');
      $Menu->AddLink('Forum', 'Categories', 'vanilla/categories/manage', 'Vanilla.Categories.Manage');
   }
   
}