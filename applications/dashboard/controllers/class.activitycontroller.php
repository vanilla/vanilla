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
    * @var ActivityModel
    */
   public $ActivityModel;
   
   public function __get($Name) {
      switch ($Name) {
         case 'CommentData':
            Deprecated('ActivityController->CommentData', "ActivityController->Data('Activities')");
            $Result = new Gdn_DataSet(array(), DATASET_TYPE_OBJECT);
            return $Result;
         case 'ActivityData':
            Deprecated('ActivityController->ActivityData', "ActivityController->Data('Activities')");
            $Result = new Gdn_DataSet($this->Data('Activities'), DATASET_TYPE_ARRAY);
            $Result->DatasetType(DATASET_TYPE_OBJECT);
            return $Result;
      }
   }
   
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
      Gdn_Theme::Section('ActivityList');
      $this->SetData('Breadcrumbs', array(array('Name' => T('Activity'), 'Url' => '/activity')));
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
         
      $this->ActivityData = $this->ActivityModel->GetWhere(array('ActivityID' => $ActivityID));
      $this->SetData('Comments', $this->ActivityModel->GetComments(array($ActivityID)));
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
    * @param int $Offset Number of activity items to skip.
    */
   public function Index($Filter = FALSE, $Page = FALSE) {
      switch (strtolower($Filter)) {
         case 'mods':
            $this->Title(T('Recent Moderator Activity'));
            $this->Permission('Garden.Moderation.Manage');
            $NotifyUserID = ActivityModel::NOTIFY_MODS;
            break;
         case 'admins':
            $this->Title(T('Recent Admin Activity'));
            $this->Permission('Garden.Settings.Manage');
            $NotifyUserID = ActivityModel::NOTIFY_ADMINS;
            break;
         default:
            $Filter = 'public';
            $this->Title(T('Recent Activity'));
            $this->Permission('Garden.Activity.View');
            $NotifyUserID = ActivityModel::NOTIFY_PUBLIC;
            break;
      }
         
      // Which page to load
      list($Offset, $Limit) = OffsetLimit($Page, 30);
      $Offset = is_numeric($Offset) ? $Offset : 0;
      if ($Offset < 0)
         $Offset = 0;
      
      // Page meta.
      $this->AddJsFile('activity.js');
      
      // Comment submission 
      $Session = Gdn::Session();
      $Comment = $this->Form->GetFormValue('Comment');
      $Activities = $this->ActivityModel->GetWhere(array('NotifyUserID' => $NotifyUserID), $Offset, $Limit)->ResultArray();
      $this->ActivityModel->JoinComments($Activities);
      
      $this->SetData('Filter', strtolower($Filter));
      $this->SetData('Activities', $Activities);
      
      $this->AddModule('ActivityFilterModule');
      
      $this->View = 'all';
      $this->Render();
   }
   
   public function DeleteComment($ID, $TK, $Target = '') {
      $Session = Gdn::Session();
      
      if (!$Session->ValidateTransientKey($TK))
         throw PermissionException();
      
      $Comment = $this->ActivityModel->GetComment($ID);
      if (!$ID)
         throw NotFoundException();
      
      if ($Session->CheckPermission('Garden.Activity.Delete') || $Comment['InsertUserID'] = $Session->UserID) {
         $this->ActivityModel->DeleteComment($ID);
      } else {
         throw PermissionException();
      }
      
      if ($this->DeliveryType() === DELIVERY_TYPE_ALL)
         Redirect($Target);
      
      $this->Render('Blank', 'Utility', 'Dashboard');
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
      if (!$Session->ValidateTransientKey($TransientKey))
         throw PermissionException();
      
      if (!is_numeric($ActivityID))
         throw Gdn_UserException('Invalid activity ID');
      
      
      $HasPermission = $Session->CheckPermission('Garden.Activity.Delete');
      if (!$HasPermission) {
         $Activity = $this->ActivityModel->GetID($ActivityID);
         if (!$Activity)
            throw NotFoundException('Activity');
         $HasPermission = $Activity['InsertUserID'] == $Session->UserID;
      }
      if (!$HasPermission)
         throw PermissionException();

      $this->ActivityModel->Delete($ActivityID);
      
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
      $this->Permission('Garden.Profiles.Edit');
      
      $Session = Gdn::Session();
      $this->Form->SetModel($this->ActivityModel);
      $NewActivityID = 0;
      
      // Form submitted
      if ($this->Form->AuthenticatedPostBack()) {
         $Body = $this->Form->GetValue('Body', '');
         $ActivityID = $this->Form->GetValue('ActivityID', '');
         if (is_numeric($ActivityID) && $ActivityID > 0) {
            $ActivityComment = array(
                'ActivityID' => $ActivityID,
                'Body' => $Body,
                'Format' => 'Text');
            
            $ID = $this->ActivityModel->Comment($ActivityComment);
            
            if ($ID == SPAM || $ID == UNAPPROVED) {
               $this->StatusMessage = T('ActivityCommentRequiresApproval', 'Your comment will appear after it is approved.');
               $this->Render('Blank', 'Utility');
               return;
            }
            
            $this->Form->SetValidationResults($this->ActivityModel->ValidationResults());
            if ($this->Form->ErrorCount() > 0) {
               throw new Exception($this->ActivityModel->Validation->ResultsText());
               
               $this->ErrorMessage($this->Form->Errors());
            }
         }
      }
      
      // Redirect back to the sending location if this isn't an ajax request
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
         $Target = $this->Form->GetValue('Return');
         if (!$Target)
            $Target = '/activity';
         Redirect($Target);
      } else {
         // Load the newly added comment.
         $this->SetData('Comment', $this->ActivityModel->GetComment($ID));
         
         // Set it in the appropriate view.
         $this->View = 'comment';
      }

      // And render
      $this->Render();
   }
   
   public function Post($Notify = FALSE, $UserID = FALSE) {
      if (is_numeric($Notify)) {
         $UserID = $Notify;
         $Notify = FALSE;
      }
      
      if (!$UserID) {
         $UserID = Gdn::Session()->UserID;
      }
      
      switch ($Notify) {
         case 'mods':
            $this->Permission('Garden.Moderation.Manage');
            $NotifyUserID = ActivityModel::NOTIFY_MODS;
            break;
         case 'admins':
            $this->Permission('Garden.Settings.Manage');
            $NotifyUserID = ActivityModel::NOTIFY_ADMINS;
            break;
         default:
            $this->Permission('Garden.Profiles.Edit');
            $NotifyUserID = ActivityModel::NOTIFY_PUBLIC;
            break;
      }
      
      $Activities = array();
      
      if ($this->Form->AuthenticatedPostBack()) {
         $Data = $this->Form->FormValues();
         $Data = $this->ActivityModel->FilterForm($Data);
         if (!isset($Data['Format']) || strcasecmp($Data['Format'], 'Raw') == 0)
            $Data['Format'] = C('Garden.InputFormatter');
         
         if ($UserID != Gdn::Session()->UserID) {
            // This is a wall post.
            $Activity = array(
                'ActivityType' => 'WallPost',
                'ActivityUserID' => $UserID,
                'RegardingUserID' => Gdn::Session()->UserID,
                'HeadlineFormat' => T('HeadlineFormat.WallPost', '{RegardingUserID,you} &rarr; {ActivityUserID,you}'),
                'Story' => $Data['Comment'],
                'Format' => $Data['Format']
            );
         } else {
            // This is a status update.
            $Activity = array(
                'ActivityType' => 'Status',
                'HeadlineFormat' => T('HeadlineFormat.Status', '{ActivityUserID,user}'),
                'Story' => $Data['Comment'],
                'Format' => $Data['Format'],
                'NotifyUserID' => $NotifyUserID
            );
            $this->SetJson('StatusMessage', Gdn_Format::PlainText($Activity['Story'], $Activity['Format']));
         }
         
         $Activity = $this->ActivityModel->Save($Activity, FALSE, array('CheckSpam' => TRUE));
         if ($Activity == SPAM || $Activity == UNAPPROVED) {
            $this->StatusMessage = T('ActivityRequiresApproval', 'Your post will appear after it is approved.');
            $this->Render('Blank', 'Utility');
            return;
         }
         
         if ($Activity) {
            if ($UserID == Gdn::Session()->UserID && $NotifyUserID == ActivityModel::NOTIFY_PUBLIC)
               Gdn::UserModel()->SetField(Gdn::Session()->UserID, 'About', Gdn_Format::PlainText($Activity['Story'], $Activity['Format']));
            
            $Activities = array($Activity);
            ActivityModel::JoinUsers($Activities);
            $this->ActivityModel->CalculateData($Activities);
         } else {
            $this->Form->SetValidationResults($this->ActivityModel->ValidationResults());
            
            $this->StatusMessage = $this->ActivityModel->Validation->ResultsText();
//            $this->Render('Blank', 'Utility');
         }
      }

      if ($this->DeliveryType() == DELIVERY_TYPE_ALL) {
         Redirect($this->Request->Get('Target', '/activity'));
      }
      
      $this->SetData('Activities', $Activities);
      $this->Render('Activities');
   }
}
