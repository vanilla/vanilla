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
         ->Select('au.Email', '', 'ActivityEmail')
         ->Select('ru.Name', '', 'RegardingName')
         ->Select('ru.Gender', '', 'RegardingGender')
         ->Select('ru.Email', '', 'RegardingEmail')
         ->Select('ru.Photo', '', 'RegardingPhoto')
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
      $Result = $this->SQL
         ->Where($Field, $Value)
         ->OrderBy('a.DateInserted', 'desc')
         ->Get();

      $this->EventArguments['Data'] =& $Result;
      $this->FireEvent('AfterGet');
      return $Result;
   }
   
   public function Get($UserID = '', $Offset = '0', $Limit = '50') {
      $Offset = is_numeric($Offset) ? $Offset : 0;
      if ($Offset < 0)
         $Offset = 0;

      $Limit = is_numeric($Limit) ? $Limit : 0;
      if ($Limit < 0)
         $Limit = 0;

      $this->ActivityQuery();
      $this->SQL->Where('a.CommentActivityID is null');
      if ($UserID != '') {
         $this->SQL
            //->BeginWhereGroup()
            ->Where('a.ActivityUserID', $UserID);
            // ->OrWhere('a.RegardingUserID', $UserID)
            //->EndWhereGroup();
            // mosullivan 2011-03-08: "Or" killing query speed
      }
      
      $Session = Gdn::Session();
      if (!$Session->IsValid() || $Session->UserID != $UserID)
         $this->SQL->Where('t.Public', '1');

      $this->FireEvent('BeforeGet');
      $Result = $this->SQL
         ->OrderBy('a.DateInserted', 'desc')
         ->Limit($Limit, $Offset)
         ->Get();

      $this->EventArguments['Data'] =& $Result;
      $this->FireEvent('AfterGet');
      return $Result;
   }
   
   public function GetCount($UserID = '') {
      $this->SQL
         ->Select('a.ActivityID', 'count', 'ActivityCount')
         ->From('Activity a')
         ->Join('ActivityType t', 'a.ActivityTypeID = t.ActivityTypeID')
         ->Where('a.CommentActivityID is null');
      
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

      $this->FireEvent('BeforeGetCount');
      return $this->SQL
         ->Get()
         ->FirstRow()
         ->ActivityCount;
   }

   public function GetForRole($RoleID = '', $Offset = '0', $Limit = '50') {
      if (!is_array($RoleID))
         $RoleID = array($RoleID);
      
      $Offset = is_numeric($Offset) ? $Offset : 0;
      if ($Offset < 0)
         $Offset = 0;

      $Limit = is_numeric($Limit) ? $Limit : 0;
      if ($Limit < 0)
         $Limit = 0;
      
      $this->ActivityQuery();
      $Result = $this->SQL
         ->Join('UserRole ur', 'a.ActivityUserID = ur.UserID')
         ->WhereIn('ur.RoleID', $RoleID)
         ->Where('a.CommentActivityID is null')
         ->Where('t.Public', '1')
         ->OrderBy('a.DateInserted', 'desc')
         ->Limit($Limit, $Offset)
         ->Get();
         
      $this->EventArguments['Data'] =& $Result;
      $this->FireEvent('AfterGet');
      return $Result;
   }
   
   public function GetCountForRole($RoleID = '') {
      if (!is_array($RoleID))
         $RoleID = array($RoleID);
      
      return $this->SQL
         ->Select('a.ActivityID', 'count', 'ActivityCount')
         ->From('Activity a')
         ->Join('ActivityType t', 'a.ActivityTypeID = t.ActivityTypeID')
         ->Join('UserRole ur', 'a.ActivityUserID = ur.UserID')
         ->WhereIn('ur.RoleID', $RoleID)
         ->Where('a.CommentActivityID is null')
         ->Where('t.Public', '1')
         ->Get()
         ->FirstRow()
         ->ActivityCount;
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
   
   public function GetNotificationsSince($UserID, $LastActivityID, $FilterToActivityTypeIDs = '', $Limit = '5') {
      $this->ActivityQuery();
      $this->FireEvent('BeforeGetNotificationsSince');
		if (is_array($FilterToActivityTypeIDs))
			$this->SQL->WhereIn('a.ActivityTypeID', $FilterToActivityTypeIDs);
      else
         $this->SQL->Where('t.Notify', '1');
		
      $Result = $this->SQL
         ->Where('RegardingUserID', $UserID)
         ->Where('a.ActivityID >', $LastActivityID)
         ->Limit($Limit, 0)
         ->OrderBy('a.ActivityID', 'desc')
         ->Get();

      return $Result;
   }
   
   public function GetCountNotifications($UserID) {
      $this->SQL
         ->Select('a.ActivityID', 'count', 'ActivityCount')
         ->From('Activity a')
         ->Join('ActivityType t', 'a.ActivityTypeID = t.ActivityTypeID');
         
      $this->FireEvent('BeforeGetNotificationsCount');
      return $this->SQL
         ->Where('RegardingUserID', $UserID)
         ->Where('t.Notify', '1')
         ->Get()
         ->FirstRow()
         ->ActivityCount;
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
      static $ActivityTypes = array();
   
      // Make sure the user is authenticated.

      // Get the ActivityTypeID & see if this is a notification
      if (isset($ActivityTypes[$ActivityType])) {
         $ActivityTypeRow = $ActivityTypes[$ActivityType];
      } else {
         $ActivityTypeRow = $this->SQL
            ->Select('ActivityTypeID, Name, Notify')
            ->From('ActivityType')
            ->Where('Name', $ActivityType)
            ->Get()
            ->FirstRow();

         $ActivityTypes[$ActivityType] = $ActivityTypeRow;
      }
         
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
      if ($SendEmail === 'Only') {
         $SendEmail = '';
         $AddActivity = FALSE;
      } else if ($SendEmail === 'QueueOnly') {
         $SendEmail = '';
         $QueueEmail = TRUE;
         $AddActivity = FALSE;
      } else {
         $AddActivity = TRUE;
      }
      
      if ($Notify) {
         // Only add the activity if the user wants to be notified in some way.
         $RegardingUser = Gdn::UserModel()->GetID($RegardingUserID);
         if ($SendEmail != 'Force' && !self::NotificationPreference($ActivityType, GetValue('Preferences', $RegardingUser))) {
//            echo "User doesn't want to be notified...";
            return FALSE;
         }
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
            $this->QueueNotification($ActivityID, $Story, 'last', $SendEmail == 'Force');
         else
            $this->SendNotification($ActivityID, $Story, $SendEmail == 'Force');
      }
      
      return $ActivityID;
   }
   
   public static function NotificationPreference($ActivityType, $Preferences, $Type = NULL) {
      if ($Type === NULL) {
         $Result = self::NotificationPreference($ActivityType, $Preferences, 'Email')
                || self::NotificationPreference($ActivityType, $Preferences, 'Popup');
         
         return $Result;
      }
      
      $ConfigPreference = C('Preferences.Email.'.$ActivityType, '0');
      if ($ConfigPreference !== FALSE)
         $Preference = ArrayValue($Type.'.'.$ActivityType, $Preferences, $ConfigPreference);
      else
         $Preference = FALSE;
      
      return $Preference;
   }
   
   public function SendNotification($ActivityID, $Story = '', $Force = FALSE) {
      $Activity = $this->GetID($ActivityID);
      if (!is_object($Activity))
         return;
      
      $Story = Gdn_Format::Text($Story == '' ? $Activity->Story : $Story, FALSE);
      // If this is a comment on another activity, fudge the activity a bit so that everything appears properly.
      if (is_null($Activity->RegardingUserID) && $Activity->CommentActivityID > 0) {
         $CommentActivity = $this->GetID($Activity->CommentActivityID);
         $Activity->RegardingUserID = $CommentActivity->RegardingUserID;
         $Activity->Route = '/activity/item/'.$Activity->CommentActivityID;
      }
      
      $User = Gdn::UserModel()->GetID($Activity->RegardingUserID, DATASET_TYPE_OBJECT);

      if ($User) {
         if ($Force)
            $Preference = $Force;
         else {
            $Preferences = $User->Preferences;
            $Preference = ArrayValue('Email.'.$Activity->ActivityType, $Preferences, Gdn::Config('Preferences.Email.'.$Activity->ActivityType));
         }
         if ($Preference) {
            $ActivityHeadline = Gdn_Format::Text(ActivityHeadline($Activity, $Activity->ActivityUserID, $Activity->RegardingUserID), FALSE);
            $Email = new Gdn_Email();
            $Email->Subject(sprintf(T('[%1$s] %2$s'), Gdn::Config('Garden.Title'), $ActivityHeadline));
            $Email->To($User->Email, $User->Name);
            //$Email->From(Gdn::Config('Garden.SupportEmail'), Gdn::Config('Garden.SupportName'));
            $Email->Message(
               sprintf(
                  T($Story == '' ? 'EmailNotification' : 'EmailStoryNotification'),
                  $ActivityHeadline,
                  ExternalUrl($Activity->Route == '' ? '/' : $Activity->Route),
                  $Story
               )
            );
            
            $Notification = array('ActivityID' => $ActivityID, 'User' => $User, 'Email' => $Email, 'Route' => $Activity->Route, 'Story' => $Story, 'Headline' => $ActivityHeadline, 'Activity' => $Activity);
            $this->EventArguments = $Notification;
            $this->FireEvent('BeforeSendNotification');
            try {
               $Email->Send();
               $Emailed = 2; // similar to http 200 OK
            } catch (phpmailerException $pex) {
               if ($pex->getCode() == PHPMailer::STOP_CRITICAL)
                  $Emailed = 4;
               else
                  $Emailed = 5;
            } catch (Exception $ex) {
               $Emailed = 4; // similar to http 5xx
            }
            try {
               $this->SQL->Put('Activity', array('Emailed' => $Emailed), array('ActivityID' => $ActivityID));
            } catch (Exception $Ex) {
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
            
            if (is_object($Email)) {
               $this->EventArguments = $Notification;
               $this->FireEvent('BeforeSendNotification');
            
               try {
                  $Email->Send();
                  $Emailed = 2;
               } catch (phpmailerException $pex) {
                  if ($pex->getCode() == PHPMailer::STOP_CRITICAL)
                     $Emailed = 4;
                  else
                     $Emailed = 5;
               } catch(Exception $Ex) {
                  $Emailed = 4;
               }
               
               try {
                  $this->SQL->Put('Activity', array('Emailed' => $Emailed), array('ActivityID' => $Notification['ActivityID']));
               } catch (Exception $Ex) {
               }
            }
         }
      }

      // Clear out the queue
      unset($this->_NotificationQueue);
      $this->_NotificationQueue = array();
   }
   
   /**
    * Queue a notification for sending.
    */
   public function QueueNotification($ActivityID, $Story = '', $Position = 'last', $Force = FALSE) {
      $Activity = $this->GetID($ActivityID);
      if (!is_object($Activity))
         return;
      
      $Story = Gdn_Format::Text($Story == '' ? $Activity->Story : $Story, FALSE);
      // If this is a comment on another activity, fudge the activity a bit so that everything appears properly.
      if (is_null($Activity->RegardingUserID) && $Activity->CommentActivityID > 0) {
         $CommentActivity = $this->GetID($Activity->CommentActivityID);
         $Activity->RegardingUserID = $CommentActivity->RegardingUserID;
         $Activity->Route = '/activity/item/'.$Activity->CommentActivityID;
      }
      $User = Gdn::UserModel()->GetID($Activity->RegardingUserID, DATASET_TYPE_OBJECT); //$this->SQL->Select('UserID, Name, Email, Preferences')->From('User')->Where('UserID', $Activity->RegardingUserID)->Get()->FirstRow();

      if ($User) {
         if ($Force)
            $Preference = $Force;
         else {
            $Preferences = Gdn_Format::Unserialize($User->Preferences);
            $ConfigPreference = C('Preferences.Email.'.$Activity->ActivityType, '0');
            if ($ConfigPreference !== FALSE)
               $Preference = ArrayValue('Email.'.$Activity->ActivityType, $Preferences, $ConfigPreference);
            else
               $Preference = FALSE;
         }
         
         if ($Preference) {
            $ActivityHeadline = Gdn_Format::Text(ActivityHeadline($Activity, $Activity->ActivityUserID, $Activity->RegardingUserID), FALSE);
            $Email = new Gdn_Email();
            $Email->Subject(sprintf(T('[%1$s] %2$s'), Gdn::Config('Garden.Title'), $ActivityHeadline));
            $Email->To($User->Email, $User->Name);
            //$Email->From(Gdn::Config('Garden.SupportEmail'), Gdn::Config('Garden.SupportName'));
            $Email->Message(
               sprintf(
                  T($Story == '' ? 'EmailNotification' : 'EmailStoryNotification'),
                  $ActivityHeadline,
                  ExternalUrl($Activity->Route == '' ? '/' : $Activity->Route, TRUE),
                  $Story
               )
            );
            if (!array_key_exists($User->UserID, $this->_NotificationQueue))
               $this->_NotificationQueue[$User->UserID] = array();

            $Notification = array('ActivityID' => $ActivityID, 'User' => $User, 'Email' => $Email, 'Route' => $Activity->Route, 'Story' => $Story, 'Headline' => $ActivityHeadline, 'Activity' => $Activity);
            if ($Position == 'first')
               $this->_NotificationQueue[$User->UserID] = array_merge(array($Notification), $this->_NotificationQueue[$User->UserID]);
            else
               $this->_NotificationQueue[$User->UserID][] = $Notification;
         }
      }
   }
}