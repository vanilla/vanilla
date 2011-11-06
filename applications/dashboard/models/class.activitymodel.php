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
 * Activity Model
 *
 * @package Dashboard
 */
 
/**
 * Activity data management.
 *
 * @since 2.0.0
 * @package Dashboard
 */
class ActivityModel extends Gdn_Model {
   /**
    * Defines the related database table name.
    */
   public function __construct() {
      parent::__construct('Activity');
   }
   
   /**
    * Build basis of common activity SQL query.
    *
    * @since 2.0.0
    * @access public
    */
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
         
      $this->FireEvent('AfterActivityQuery');
   }
   
   /**
    * Delete a particular activity item.
    *
    * @since 2.0.0
    * @access public
    * @param int $ActivityID Unique ID of acitivity to be deleted.
    */
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

   /**
    * Modifies standard Gdn_Model->GetWhere to use AcitivityQuery.
    *
    * Events: AfterGet.
    *
    * @since 2.0.0
    * @access public
    * @param string $Field Column name for where clause.
    * @param mixed $Value Value for where clause.
    * @return DataSet SQL results.
    */
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
   
   /**
    * Modifies standard Gdn_Model->Get to use AcitivityQuery.
    *
    * Events: BeforeGet, AfterGet.
    *
    * @since 2.0.0
    * @access public
    * @param int $UserID Unique ID of user to gather activity for.
    * @param int $Offset Number to skip.
    * @param int $Limit How many to return.
    * @return DataSet SQL results.
    */
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
   
   /**
    * Get number of activity related to a user.
    *
    * Events: BeforeGetCount.
    *
    * @since 2.0.0
    * @access public
    * @param string $UserID Unique ID of user.
    * @return int Number of activity items found.
    */
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
   
   /**
    * Get activity related to a particular role.
    *
    * Events: AfterGet.
    *
    * @since 2.0.18
    * @access public
    * @param string $RoleID Unique ID of role.
    * @param int $Offset Number to skip.
    * @param int $Limit Max number to return.
    * @return DataSet SQL results.
    */
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
   
   /**
    * Get number of activity related to a particular role.
    *
    * @since 2.0.18
    * @access public
    * @param int $RoleID Unique ID of role.
    * @return int Number of activity items.
    */
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
   
   /**
    * Get a particular activity record.
    *
    * @since 2.0.0
    * @access public
    * @param int $ActivityID Unique ID of activity item.
    * @return DataSet A single SQL result.
    */
   public function GetID($ActivityID) {
      $this->ActivityQuery();
      return $this->SQL
         ->Where('a.ActivityID', $ActivityID)
         ->Get()
         ->FirstRow();
   }
   
   /**
    * Get notifications for a user.
    *
    * Events: BeforeGetNotifications.
    *
    * @since 2.0.0
    * @access public
    * @param int $UserID Unique ID of user.
    * @param int $Offset Number to skip.
    * @param int $Limit Max number to return.
    * @return DataSet SQL results.
    */
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
   
   /**
    * Get notifications for a user since designated ActivityID.
    *
    * Events: BeforeGetNotificationsSince.
    *
    * @since 2.0.18
    * @access public
    * @param int $UserID Unique ID of user.
    * @param int $LastActivityID ID of activity to start at.
    * @param array $FilterToActivityTypeIDs Limits returned activity to particular types.
    * @param int $Limit Max number to return.
    * @return DataSet SQL results.
    */
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
   
   /**
    * Get number of notifications for a user.
    *
    * Events: BeforeGetNotificationsCount.
    *
    * @since 2.0.0
    * @access public
    * @param int $UserID Unique ID of user.
    * @return int Number of notifications.
    */
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
   
   /**
    * Get comments related to designated activity items.
    *
    * Events: BeforeGetComments.
    *
    * @since 2.0.0
    * @access public
    * @param array $ActivityIDs IDs of activity items.
    * @return DataSet SQL results.
    */
   public function GetComments($ActivityIDs) {
      $this->ActivityQuery();
      $this->FireEvent('BeforeGetComments');
      return $this->SQL
         ->WhereIn('a.CommentActivityID', $ActivityIDs)
         ->OrderBy('a.CommentActivityID', 'desc')
         ->OrderBy('a.DateInserted', 'asc')
         ->Get();
   }
   
   /**
    * Add a new activity item.
    *
    * Getting reworked for 2.1 so I'm cheating and skipping params for now. -mlr
    *
    * @since 2.0.0
    * @access public
    * @param int $ActivityUserID
    * @param string $ActivityType
    * @param string $Story
    * @param int $RegardingUserID
    * @param int $CommentActivityID
    * @param string $Route
    * @param mixed $SendEmail
    * @return int ActivityID of item created.
    */
   public function Add($ActivityUserID, $ActivityType, $Story = '', $RegardingUserID = '', $CommentActivityID = '', $Route = '', $SendEmail = '') {
      static $ActivityTypes = array();

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
   
   /**
    * Get default notification preference for an activity type.
    *
    * @since 2.0.0
    * @access public
    * @param string $ActivityType
    * @param array $Preferences
    * @param string $Type
    * @return bool
    */
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
   
   
   /**
    * Send notification.
    *
    * @since 2.0.17
    * @access public
    * @param int $ActivityID
    * @param array $Story
    * @param string $Force
    */
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
            $ActivityHeadline = Gdn_Format::Text(Gdn_Format::ActivityHeadline($Activity, $Activity->ActivityUserID, $Activity->RegardingUserID), FALSE);
            $Email = new Gdn_Email();
            $Email->Subject(sprintf(T('[%1$s] %2$s'), Gdn::Config('Garden.Title'), $ActivityHeadline));
            $Email->To($User->Email, $User->Name);
            //$Email->From(Gdn::Config('Garden.SupportEmail'), Gdn::Config('Garden.SupportName'));
            
            $Message = sprintf(
                  $Story == '' ? T('EmailNotification', "%1\$s\n\n%2\$s") : T('EmailStoryNotification', "%3\$s\n\n%2\$s"),
                  $ActivityHeadline,
                  ExternalUrl($Activity->Route == '' ? '/' : $Activity->Route),
                  $Story
               );
            $Email->Message($Message);
            
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
    * @var array The Notification Queue is used to stack up notifications to users. Ensures
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
   
   /**
    * Clear notification queue.
    *
    * @since 2.0.17
    * @access public
    */
   public function ClearNotificationQueue() {
      unset($this->_NotificationQueue);
      $this->_NotificationQueue = array();
   }
   
   /**
    * Send all notifications in the queue.
    *
    * @since 2.0.17
    * @access public
    */
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
    *
    * @since 2.0.17
    * @access public
    * @param int $ActivityID
    * @param string $Story
    * @param string $Position
    * @param bool $Force
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
            $ActivityHeadline = Gdn_Format::Text(Gdn_Format::ActivityHeadline($Activity, $Activity->ActivityUserID, $Activity->RegardingUserID), FALSE);
            $Email = new Gdn_Email();
            $Email->Subject(sprintf(T('[%1$s] %2$s'), Gdn::Config('Garden.Title'), $ActivityHeadline));
            $Email->To($User->Email, $User->Name);
            $Message = sprintf(
                  $Story == '' ? T('EmailNotification', "%1\$s\n\n%2\$s") : T('EmailStoryNotification', "%3\$s\n\n%2\$s"),
                  $ActivityHeadline,
                  ExternalUrl($Activity->Route == '' ? '/' : $Activity->Route),
                  $Story
               );
            $Email->Message($Message);
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