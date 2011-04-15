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
 * Activity Controller
 *
 * @package Dashboard
 */
 
/**
 * Manages the activity stream.
 *
 * @since 2.0.0
 * @package Dashboard
 */
class ActivityController extends Gdn_Controller {
   /**
    * Models to include.
    * 
    * @since 2.0.0
    * @access public
    * @var array
    */
   public $Uses = array('Database', 'Form', 'ActivityModel');
   
   /**
    * Include JS, CSS, and modules used by all methods.
    *
    * Always called by dispatcher before controller's requested method.
    * 
    * @since 2.0.0
    * @access public
    */
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      
      $this->AddCssFile('style.css');
      
      // Add Modules
      $this->AddModule('GuestModule');
      $this->AddModule('SignedInModule');
      
      parent::Initialize();
   }
   
   /**
    * Display a single activity item & comments.
    * 
    * Email notifications regarding activities link to this method.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $ActivityID Unique ID of activity item to display.
    */
   public function Item($ActivityID = 0) {
      $this->AddJsFile('activity.js');
      $this->Title(T('Activity Item'));

      if (!is_numeric($ActivityID) || $ActivityID < 0)
         $ActivityID = 0;
         
      $this->ActivityData = $this->ActivityModel->GetWhere('ActivityID', $ActivityID);
      $this->CommentData = $this->ActivityModel->GetComments(array($ActivityID));
      $this->SetData('ActivityData', $this->ActivityData);
      
      $this->Render();
   }
   
   /**
    * Default activity stream.
    * 
    * @since 2.0.0
    * @access public
    * @todo Validate comment length rather than truncating.
    * 
    * @param int $RoleID Unique ID of role to limit activity to.
    * @param int $Offset Number of activity items to skip.
    */
   public function Index($RoleID = '', $Offset = FALSE) {
      $this->Permission('Garden.Activity.View');
      
      // Limit to specific RoleIDs?
      if ($RoleID == 0)
         $RoleID = '';
         
      if ($RoleID != '')
         $RoleID = explode(',', $RoleID);
         
      // Which page to load
      $Offset = is_numeric($Offset) ? $Offset : 0;
      if ($Offset < 0)
         $Offset = 0;
      
      // Page meta
      $this->AddJsFile('jquery.gardenmorepager.js');
      $this->AddJsFile('activity.js');
      $this->Title(T('Recent Activity'));
      
      // Comment submission 
      $Session = Gdn::Session();
      $Comment = $this->Form->GetFormValue('Comment');
      $this->CommentData = FALSE;
      if ($Session->UserID > 0 && $this->Form->AuthenticatedPostBack() && !StringIsNullOrEmpty($Comment)) {
         $Comment = substr($Comment, 0, 1000); // Limit to 1000 characters...
         
         // Update About if necessary
         $ActivityType = 'WallComment';
         $NewActivityID = $this->ActivityModel->Add(
            $Session->UserID,
            $ActivityType,
            $Comment);
         
         if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            Redirect('activity');
         } else {
            // Load just the single new comment
            $this->HideActivity = TRUE;
            $this->ActivityData = $this->ActivityModel->GetWhere('ActivityID', $NewActivityID);
            $this->View = 'activities';
         }
      } else {
         $Limit = 50;
         $this->ActivityData = is_array($RoleID) ? $this->ActivityModel->GetForRole($RoleID, $Offset, $Limit) : $this->ActivityModel->Get('', $Offset, $Limit);
         $TotalRecords = is_array($RoleID) ? $this->ActivityModel->GetCountForRole($RoleID) : $this->ActivityModel->GetCount();
         if ($this->ActivityData->NumRows() > 0) {
            $ActivityData = $this->ActivityData->ResultArray();
            $ActivityIDs = ConsolidateArrayValuesByKey($ActivityData, 'ActivityID');
            $this->CommentData = $this->ActivityModel->GetComments($ActivityIDs);
         }
         $this->View = 'all';
         
         // Build a pager
         $PagerFactory = new Gdn_PagerFactory();
         $this->Pager = $PagerFactory->GetPager('MorePager', $this);
         $this->Pager->MoreCode = 'More';
         $this->Pager->LessCode = 'Newer Activity';
         $this->Pager->ClientID = 'Pager';
         $this->Pager->Configure(
            $Offset,
            $Limit,
            $TotalRecords,
            'activity/'.(is_array($RoleID) ? implode(',', $RoleID) : '0').'/%1$s/%2$s/'
         );
         
         // Deliver json data if necessary
         if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->SetJson('LessRow', $this->Pager->ToString('less'));
            $this->SetJson('MoreRow', $this->Pager->ToString('more'));
            $this->View = 'activities';
         }
      }
      
      // Add RecentUser module
      $RecentUserModule = new RecentUserModule($this);
      $RecentUserModule->GetData();
      $this->AddModule($RecentUserModule);
      
      $this->SetData('ActivityData', $this->ActivityData);
      
      $this->Render();
   }
   
   /**
    * Delete an activity item.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $ActivityID Unique ID of item to delete.
    * @param string $TransientKey Verify intent.
    */
   public function Delete($ActivityID = '', $TransientKey = '') {
      $Session = Gdn::Session();
      if (
         $Session->ValidateTransientKey($TransientKey)
         && is_numeric($ActivityID)
      ) {
         $HasPermission = $Session->CheckPermission('Garden.Activity.Delete');
         if (!$HasPermission) {
            $Activity = $this->ActivityModel->GetID($ActivityID);
            $HasPermission = $Activity->InsertUserID == $Session->UserID;
         }
         if ($HasPermission)
            $this->ActivityModel->Delete($ActivityID);
      }
      
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL)
         Redirect(GetIncomingValue('Target', $this->SelfUrl));
      
      // Still here? Getting a 404.
      $this->ControllerName = 'Home';
      $this->View = 'FileNotFound';
      $this->Render();
   }
   
   /**
    * Comment on an activity item.
    * 
    * @since 2.0.0
    * @access public
    */
   public function Comment() {
      $Session = Gdn::Session();
      $this->Form->SetModel($this->ActivityModel);
      $NewActivityID = 0;
      
      // Form submitted
      if ($this->Form->AuthenticatedPostBack()) {
         $Body = $this->Form->GetValue('Body', '');
         $ActivityID = $this->Form->GetValue('ActivityID', '');
         if (is_numeric($ActivityID) && $ActivityID > 0) {
            $NewActivityID = $this->ActivityModel->Add(
               $Session->UserID,
               'ActivityComment',
               $Body,
               '',
               $ActivityID,
               '',
               TRUE
            );
            $this->Form->SetValidationResults($this->ActivityModel->ValidationResults());
            if ($this->Form->ErrorCount() > 0)
               $this->ErrorMessage($this->Form->Errors());
         }
      }
      
      // Redirect back to the sending location if this isn't an ajax request
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
         Redirect($this->Form->GetValue('Return', Gdn_Url::WebRoot()));
      } else {
         // Load the newly added comment
         $this->Comment = $this->ActivityModel->GetID($NewActivityID);
         $this->Comment->ActivityType .= ' Hidden'; // Hide it so jquery can reveal it
         
         // Set it in the appropriate view
         $this->View = 'comment';
      }

      // And render
      $this->Render();
   }   
}