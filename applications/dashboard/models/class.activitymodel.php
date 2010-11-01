<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class ActivityModel extends Gdn_Model {
   /**
    * Class constructor. Defines the related database table name.
    */
   public function __construct() {
      parent::__construct('Activity');
   }
   
   public function ActivityQuery() {
      $this->SQL
         ->Select('a.*')
         ->Select('t.FullHeadline, t.ProfileHeadline, t.AllowComments, t.ShowIcon, t.RouteCode')
         ->Select('t.Name', '', 'ActivityType')
         ->Select('au.Name', '', 'ActivityName')
         ->Select('au.Gender', '', 'ActivityGender')
         ->Select('au.Photo', '', 'ActivityPhoto')
         ->Select('ru.Name', '', 'RegardingName')
         ->Select('ru.Gender', '', 'RegardingGender')
         ->From('Activity a')
         ->Join('ActivityType t', 'a.ActivityTypeID = t.ActivityTypeID')
         ->Join('User au', 'a.ActivityUserID = au.UserID')
         ->Join('User ru', 'a.RegardingUserID = ru.UserID', 'left');
   }
   
   public function Delete($ActivityID) {
      // Get the activity first
      $Activity = $this->GetID($ActivityID);
      if (is_object($Activity)) {
         $Users = array();
         $Users[] = $Activity->ActivityUserID;
         if (is_numeric($Activity->RegardingUserID) && $Activity->RegardingUserID > 0)
            $Users[] = $Activity->RegardingUserID;
            
         // Update the user's dateupdated field so that profile pages will not
         // be cached and will reflect this deletion.
         $this->SQL->Update('User')
            ->Set('DateUpdated', Gdn_Format::ToDateTime())
            ->WhereIn('UserID', $Users)
            ->Put();
         
         // Delete comments on the activity item
         parent::Delete(array('CommentActivityID' => $ActivityID), FALSE, TRUE);
         // Delete the activity item
         parent::Delete(array('ActivityID' => $ActivityID));
      }
   }

   public function GetWhere($Field, $Value = '') {
      $this->ActivityQuery();
      return $this->SQL
         ->Where($Field, $Value)
         ->OrderBy('a.DateInserted', 'desc')
         ->Get();
   }
   
   public function Get($UserID = '', $Offset = '0', $Limit = '50') {
      $this->ActivityQuery();
      $this->SQL->Where('a.CommentActivityID is null');
      if ($UserID != '') {
         $this->SQL
            ->BeginWhereGroup()
            ->Where('a.ActivityUserID', $UserID)
            ->OrWhere('a.RegardingUserID', $UserID)
            ->EndWhereGroup();
      }
      
      $Session = Gdn::Session();
      if (!$Session->IsValid() || $Session->UserID != $UserID)
         $this->SQL->Where('t.Public', '1');

      $this->FireEvent('BeforeGet');
      return $this->SQL
         ->OrderBy('a.DateInserted', 'desc')
         ->Limit($Limit, $Offset)
         ->Get();
   }
   
   public function GetForRole($RoleID = '', $Offset = '0', $Limit = '50') {
      if (!is_array($RoleID))
         $RoleID = array($RoleID);
      
      $this->ActivityQuery();
      return $this->SQL
         ->Join('UserRole ur', 'a.ActivityUserID = ur.UserID')
         ->WhereIn('ur.RoleID', $RoleID)
         ->Where('a.CommentActivityID is null')
         ->Where('t.Public', '1')
         ->OrderBy('a.DateInserted', 'desc')
         ->Limit($Limit, $Offset)
         ->Get();
   }
   
   public function GetID($ActivityID) {
      $this->ActivityQuery();
      return $this->SQL
         ->Where('a.ActivityID', $ActivityID)
         ->Get()
         ->FirstRow();
   }
   
   public function GetNotifications($UserID, $Offset = '0', $Limit = '50') {
      $this->ActivityQuery();
      $this->FireEvent('BeforeGetNotifications');
      return $this->SQL
         ->Where('RegardingUserID', $UserID)
         ->Where('t.Notify', '1')
         ->Limit($Limit, $Offset)
         ->OrderBy('a.ActivityID', 'desc')
         ->Get();
   }
   
   public function GetComments($ActivityIDs) {
      $this->ActivityQuery();
      $this->FireEvent('BeforeGetComments');
      return $this->SQL
         ->WhereIn('a.CommentActivityID', $ActivityIDs)
         ->OrderBy('a.CommentActivityID', 'desc')
         ->OrderBy('a.DateInserted', 'asc')
         ->Get();
   }
   
   public function Add($ActivityUserID, $ActivityType, $Story = '', $RegardingUserID = '', $CommentActivityID = '', $Route = '', $SendEmail = '') {
      // Make sure the user is authenticated
      // Get the ActivityTypeID & see if this is a notification
      $ActivityTypeRow = $this->SQL
         ->Select('ActivityTypeID, Name, Notify')
         ->From('ActivityType')
         ->Where('Name', $ActivityType)
         ->Get()
         ->FirstRow();
         
      if ($ActivityTypeRow !== FALSE) {
         $ActivityTypeID = $ActivityTypeRow->ActivityTypeID;
         $Notify = $ActivityTypeRow->Notify == '1';
      } else {
         trigger_error(ErrorMessage(sprintf('Activity type could not be found: %s', $ActivityType), 'ActivityModel', 'Add'), E_USER_ERROR);
      }
      if ($ActivityTypeRow->Name == 'ActivityComment' && $Story == '') {
         $this->Validation->AddValidationResult('Body', 'You must provide a comment.');
         return FALSE;
      }

      // Massage $SendEmail to allow for only sending an email.
      $QueueEmail = FALSE;
      if ($SendEmail == 'Only') {
         $SendEmail = '';
         $AddActivity = FALSE;
      } else if ($SendEmail == 'QueueOnly') {
         $SendEmail = '';
         $QueueEmail = TRUE;
         $AddActivity = FALSE;
      } else {
         $AddActivity = TRUE;
      }
         
      // If this is a notification, increment the regardinguserid's count
      if ($AddActivity && $Notify) {
         $this->SQL
            ->Update('User')
            ->Set('CountNotifications', 'coalesce(CountNotifications) + 1', FALSE)
            ->Where('UserID', $RegardingUserID)
            ->Put();
      }
      
      $Fields = array('ActivityTypeID' => $ActivityTypeID,
         'ActivityUserID' => $ActivityUserID
      );
      if ($Story != '')
         $Fields['Story'] = $Story;
         
      if ($Route != '')
         $Fields['Route'] = $Route;
         
      if (is_numeric($RegardingUserID))
         $Fields['RegardingUserID'] = $RegardingUserID;
         
      if (is_numeric($CommentActivityID))
         $Fields['CommentActivityID'] = $CommentActivityID;

//      if ($AddActivity) {
         $this->AddInsertFields($Fields);
         $this->DefineSchema();
         $ActivityID = $this->Insert($Fields); // NOTICE! This will silently fail if there are errors. Developers can figure out what's wrong by dumping the results of $this->ValidationResults();
//      }

      // If $SendEmail was FALSE or TRUE, let it override the $Notify setting.
      if ($SendEmail === FALSE || $SendEmail === TRUE)
         $Notify = $SendEmail;

      // Otherwise let the decision to email lie with the $Notify setting.

      // Send a notification to the user.
      if ($Notify) {
         if ($QueueEmail)
            $this->QueueNotification($ActivityID, $Story);
         else
            $this->SendNotification($ActivityID, $Story);
      }
      
      return $ActivityID;
   }
   
   public function SendNotification($ActivityID, $Story = '') {
      $Activity = $this->GetID($ActivityID);
      if (!is_object($Activity))
         return;
      
      $Story = Gdn_Format::Text($Story == '' ? $Activity->Story : $Story, FALSE);
      // If this is a comment on another activity, fudge the activity a bit so that everything appears properly.
      if (is_null($Activity->RegardingUserID) && $Activity->CommentActivityID > 0) {
         $CommentActivity = $this->GetID($Activity->CommentActivityID);
         $Activity->RegardingUserID = $CommentActivity->RegardingUserID;
         $Activity->Route = '/profile/'.$CommentActivity->RegardingUserID.'/'.Gdn_Format::Url($CommentActivity->RegardingName).'/#Activity_'.$Activity->CommentActivityID;
      }
      $User = $this->SQL->Select('Name, Email, Preferences')->From('User')->Where('UserID', $Activity->RegardingUserID)->Get()->FirstRow();

      if ($User) {
         $Preferences = Gdn_Format::Unserialize($User->Preferences);
         $Preference = ArrayValue('Email.'.$Activity->ActivityType, $Preferences, Gdn::Config('Preferences.Email.'.$Activity->ActivityType));
         if ($Preference) {
            $ActivityHeadline = Gdn_Format::Text(Gdn_Format::ActivityHeadline($Activity, $Activity->ActivityUserID, $Activity->RegardingUserID), FALSE);
            $Email = new Gdn_Email();
            $Email->Subject(sprintf(T('[%1$s] %2$s'), Gdn::Config('Garden.Title'), $ActivityHeadline));
            $Email->To($User->Email, $User->Name);
            //$Email->From(Gdn::Config('Garden.SupportEmail'), Gdn::Config('Garden.SupportName'));
            $Email->Message(
               sprintf(
                  T($Story == '' ? 'EmailNotification' : 'EmailStoryNotification'),
                  $ActivityHeadline,
                  Url($Activity->Route == '' ? '/' : $Activity->Route, TRUE),
                  $Story
               )
            );
            
            try {
               $Email->Send();
            } catch (Exception $ex) {
               // Don't do anything with the exception.
            }
         }
      }
   }
   
   /**
    * The Notification Queue is used to stack up notifications to users. Ensures
    * that they only receive one notification about a single topic. For example:
    * if someone comments on a discussion that they started and they have
    * bookmarked, it will only notify them about one or the other, not both.
    *
    * This code makes the assumption that the queue is used for one user action
    * at a time. For example: a comment being added to a discussion. The queue
    * should be cleared before it is used, and sending the queue will clear it
    * again.
    */
   private $_NotificationQueue = array();
   public function ClearNotificationQueue() {
      unset($this->_NotificationQueue);
      $this->_NotificationQueue = array();
   }
   public function SendNotificationQueue() {
      foreach ($this->_NotificationQueue as $UserID => $Notifications) {
         if (is_array($Notifications)) {
            // Only send out one notification per user.
            $Notification = $Notifications[0];
            $Email = $Notification['Email'];
            if (is_object($Email))
               $Email->Send();

         }
      }

      // Clear out the queue
      unset($this->_NotificationQueue);
      $this->_NotificationQueue = array();
   }
   
   /**
    * Queue a notification for sending.
    */
   public function QueueNotification($ActivityID, $Story = '') {
      $Activity = $this->GetID($ActivityID);
      if (!is_object($Activity))
         return;
      
      $Story = Gdn_Format::Text($Story == '' ? $Activity->Story : $Story, FALSE);
      // If this is a comment on another activity, fudge the activity a bit so that everything appears properly.
      if (is_null($Activity->RegardingUserID) && $Activity->CommentActivityID > 0) {
         $CommentActivity = $this->GetID($Activity->CommentActivityID);
         $Activity->RegardingUserID = $CommentActivity->RegardingUserID;
         $Activity->Route = '/profile/'.$CommentActivity->RegardingUserID.'/'.Gdn_Format::Url($CommentActivity->RegardingName).'/#Activity_'.$Activity->CommentActivityID;
      }
      $User = $this->SQL->Select('UserID, Name, Email, Preferences')->From('User')->Where('UserID', $Activity->RegardingUserID)->Get()->FirstRow();

      if ($User) {
         $Preferences = Gdn_Format::Unserialize($User->Preferences);
         $Preference = ArrayValue('Email.'.$Activity->ActivityType, $Preferences, Gdn::Config('Preferences.Email.'.$Activity->ActivityType));
         if ($Preference) {
            $ActivityHeadline = Gdn_Format::Text(Gdn_Format::ActivityHeadline($Activity, $Activity->ActivityUserID, $Activity->RegardingUserID), FALSE);
            $Email = new Gdn_Email();
            $Email->Subject(sprintf(T('[%1$s] %2$s'), Gdn::Config('Garden.Title'), $ActivityHeadline));
            $Email->To($User->Email, $User->Name);
            //$Email->From(Gdn::Config('Garden.SupportEmail'), Gdn::Config('Garden.SupportName'));
            $Email->Message(
               sprintf(
                  T($Story == '' ? 'EmailNotification' : 'EmailStoryNotification'),
                  $ActivityHeadline,
                  Url($Activity->Route == '' ? '/' : $Activity->Route, TRUE),
                  $Story
               )
            );
            if (!array_key_exists($User->UserID, $this->_NotificationQueue))
               $this->_NotificationQueue[$User->UserID] = array();
            
            $this->_NotificationQueue[$User->UserID][] = array(
               'ActivityID' => $ActivityID,
               'User' => $User,
               'Email' => $Email
            );
         }
      }
   }
}