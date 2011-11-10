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
   const NOTIFY_PUBLIC = -1;
   const NOTIFY_MODS = -2;
   const NOTIFY_ADMINS = -3;
   
   const SENT_ARCHIVE = 1; // The activity was added before this system was put in place.
   const SENT_OK = 2; // The activity sent just fine.
   const SENT_PENDING = 3; // The activity is waiting to be sent.
   const SENT_FAIL = 4; // The activity could not be sent.
   const SENT_ERROR = 5; // There was an error sending the activity, but it can be retried.
   
   public static $ActivityTypes = NULL;
   public static $Queue = array();
   
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
   public function ActivityQuery($Join = TRUE) {
      $this->SQL
         ->Select('a.*')
         ->Select('t.FullHeadline, t.ProfileHeadline, t.AllowComments, t.ShowIcon, t.RouteCode')
         ->Select('t.Name', '', 'ActivityType')
         ->From('Activity a')
         ->Join('ActivityType t', 'a.ActivityTypeID = t.ActivityTypeID');
      
      if ($Join) {
         $this->SQL
            ->Select('au.Name', '', 'ActivityName')
            ->Select('au.Gender', '', 'ActivityGender')
            ->Select('au.Photo', '', 'ActivityPhoto')
            ->Select('au.Email', '', 'ActivityEmail')
            ->Select('ru.Name', '', 'RegardingName')
            ->Select('ru.Gender', '', 'RegardingGender')
            ->Select('ru.Email', '', 'RegardingEmail')
            ->Select('ru.Photo', '', 'RegardingPhoto')
            ->Join('User au', 'a.ActivityUserID = au.UserID')
            ->Join('User ru', 'a.RegardingUserID = ru.UserID', 'left');
      }
         
      $this->FireEvent('AfterActivityQuery');
   }
   
   /**
    * Define a new activity type.
    * @param string $Name The string code of the activity type.
    * @param array $Activity The data that goes in the ActivityType table.
    * @since 2.1
    */
   public function DefineType($Name, $Activity = array()) {
      $this->SQL->Replace('ActivityType', $Activity, array('Name' => $Name), TRUE);
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
    *
    * @param int $ID 
    * @since 2.1
    */
   public function DeleteComment($ID) {
      return $this->SQL->Delete('ActivityComment', array('ActivityCommentID' => $ID));
   }

   /**
    * Modifies standard Gdn_Model->GetWhere to use AcitivityQuery.
    *
    * Events: AfterGet.
    *
    * @since 2.0.0
    * @access public
    * @param array $Where The where condition.
    * @param int $Offset The offset of the query.
    * @param int $Limit the limit of the query.
    * @return DataSet SQL results.
    */
   public function GetWhere($Where, $Offset = 0, $Limit = 30) {
      if (is_string($Where)) {
         $Where = array($Where => $Offset);
         $Offset = 0;
      }
      
      $this->ActivityQuery(FALSE);
      $Result = $this->SQL
         ->Where($Where)
         ->OrderBy('a.DateInserted', 'desc')
         ->Limit($Limit, $Offset)
         ->Get();
      Gdn::UserModel()->JoinUsers($Result->ResultArray(), array('ActivityUserID', 'RegardingUserID'), array('Join' => array('Name', 'Email', 'Gender')));

      $this->EventArguments['Data'] =& $Result;
      $this->FireEvent('AfterGet');
      
      return $Result;
   }
   
   /**
    * @param type $Activities 
    * @since 2.1
    */
   public function JoinComments(&$Activities) {
      $ActivityIDs = ConsolidateArrayValuesByKey($Activities, 'ActivityID');
      $Comments = $this->GetComments($ActivityIDs);
      $Comments = Gdn_DataSet::Index($Comments, array('ActivityID'), array('Unique' => FALSE));
      foreach ($Activities as &$Activity) {
         if (isset($Comments[$Activity['ActivityID']])) {
            $Activity['Comments'] = $Comments[$Activity['ActivityID']];
         } else {
            $Activity['Comments'] = array();
         }
      }
   }
   
   /**
    * Modifies standard Gdn_Model->Get to use AcitivityQuery.
    *
    * Events: BeforeGet, AfterGet.
    *
    * @since 2.0.0
    * @access public
    * @param int $NotifyUserID Unique ID of user to gather activity for or one of the NOTIFY_* constants in this class.
    * @param int $Offset Number to skip.
    * @param int $Limit How many to return.
    * @return DataSet SQL results.
    */
   public function Get($NotifyUserID = FALSE, $Offset = 0, $Limit = 30) {
      $Offset = is_numeric($Offset) ? $Offset : 0;
      if ($Offset < 0)
         $Offset = 0;

      $Limit = is_numeric($Limit) ? $Limit : 0;
      if ($Limit < 0)
         $Limit = 30;

      $this->ActivityQuery(FALSE);
      
      if (!$NotifyUserID) {
         $NotifyUserID = self::NOTIFY_PUBLIC;
      }
      $this->SQL->WhereIn('NotifyUserID', (array)$NotifyUserID);

      $this->FireEvent('BeforeGet');
      $Result = $this->SQL
         ->OrderBy('a.ActivityID', 'desc')
         ->Limit($Limit, $Offset)
         ->Get();
      
      Gdn::UserModel()->JoinUsers($Result, array('ActivityUserID', 'RegardingUserID'), array('Join' => array('Name', 'Photo', 'Email', 'Gender')));

      $this->EventArguments['Data'] =& $Result;
      $this->FireEvent('AfterGet');
      
      return $Result;
   }
   
   public static function GetActivityType($ActivityType) {
      if (self::$ActivityTypes === NULL) {
         $Data = Gdn::SQL()->Get('ActivityType')->ResultArray();
         foreach ($Data as $Row) {
            self::$ActivityTypes[$Row['Name']] = $Row;
            self::$ActivityTypes[$Row['ActivityTypeID']] = $Row;
         }
      }
      if (isset(self::$ActivityTypes[$ActivityType]))
         return self::$ActivityTypes[$ActivityType];
      return FALSE;
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
    * @param int $NotifyUserID Unique ID of user.
    * @param int $Offset Number to skip.
    * @param int $Limit Max number to return.
    * @return DataSet SQL results.
    */
   public function GetNotifications($NotifyUserID, $Offset = '0', $Limit = '30') {
      $this->ActivityQuery(FALSE);
      $this->FireEvent('BeforeGetNotifications');
      $Result = $this->SQL
         ->Where('NotifyUserID', $NotifyUserID)
         ->Limit($Limit, $Offset)
         ->OrderBy('a.ActivityID', 'desc')
         ->Get();
      
      Gdn::UserModel()->JoinUsers($Result, array('ActivityUserID', 'RegardingUserID'), array('Join' => array('Name', 'Photo', 'Email', 'Gender')));
      
      return $Result;
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
   
   public function GetComment($ID) {
      $Data = $this->SQL->GetWhere('ActivityComment', array('ActivityCommentID' => $ID))->ResultArray();
      if ($Data) {
         Gdn::UserModel()->JoinUsers($Data, array('InsertUserID'), array('Join' => array('Name', 'Photo', 'Email')));
         return array_shift($Data);
      }
      return FALSE;
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
      $Result = $this->SQL
         ->Select('c.*')
         ->From('ActivityComment c')
         ->WhereIn('c.ActivityID', $ActivityIDs)
         ->OrderBy('c.ActivityID, c.DateInserted')
         ->Get()->ResultArray();
      Gdn::UserModel()->JoinUsers($Result, array('InsertUserID'), array('Join' => array('Name', 'Photo', 'Email')));
      return $Result;
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
         $AddActivity = TRUE;
         $Notify = TRUE;
      } else {
         $AddActivity = TRUE;
      }
      
      if ($AddActivity && $Notify) {
         // Only add the activity if the user wants to be notified in some way.
         $RegardingUser = Gdn::UserModel()->GetID($RegardingUserID);
         if ($SendEmail != 'Force' && !self::NotificationPreference($ActivityType, GetValue('Preferences', $RegardingUser))) {
//            echo "User doesn't want to be notified...";
            return FALSE;
         }
      }
         
      // If this is a notification, increment the regardinguserid's count.
      if ($Notify) {
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
    * @param string $Type One of the following:
    *  - Popup: Popup a notification.
    *  - Email: Email the notification.
    *  - NULL: True if either notification is true.
    *  - both: Return an array of (Popup, Email).
    * @return bool
    */
   public static function NotificationPreference($ActivityType, $Preferences, $Type = NULL) {
      if (is_numeric($Preferences)) {
         $User = Gdn::UserModel()->GetID($Preferences);
         if (!$User)
            return $Type == 'both' ? array(FALSE, FALSE) : FALSE;
         $Preferences = GetValue('Preferences', $User);
      }
      
      if ($Type === NULL) {
         $Result = self::NotificationPreference($ActivityType, $Preferences, 'Email')
                || self::NotificationPreference($ActivityType, $Preferences, 'Popup');
         
         return $Result;
      } elseif ($Type === 'both') {
         $Result = array(
            self::NotificationPreference($ActivityType, $Preferences, 'Popup'),
            self::NotificationPreference($ActivityType, $Preferences, 'Email')
            );
         return $Result;
      }
      
      $ConfigPreference = C("Preferences.$Type.$ActivityType", '0');
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
    * Save a comment on an activity.
    * @param array $Data
    * @return int|bool 
    * @since 2.1
    */
   public function Comment($Data) {
      $Data['InsertUserID'] = Gdn::Session()->UserID;
      $Data['DateInserted'] = Gdn_Format::ToDateTime();
      $Data['InsertIPAddress'] = Gdn::Request()->IpAddress();
      
      $this->Validation->ApplyRule('ActivityID', 'Required');
      $this->Validation->ApplyRule('Body', 'Required');
      $this->Validation->ApplyRule('DateInserted', 'Required');
      $this->Validation->ApplyRule('InsertUserID', 'Required');
      
      if ($this->Validate($Data)) {
         $ID = $this->SQL->Insert('ActivityComment', $Data);
         return $ID;
      }
      return FALSE;
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
//            $Preferences = Gdn_Format::Unserialize($User->Preferences);
            $ConfigPreference = C('Preferences.Email.'.$Activity->ActivityType, '0');
            if ($ConfigPreference !== FALSE)
               $Preference = GetValue('Email.'.$Activity->ActivityType, $User->Preferences, $ConfigPreference);
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
   
   /**
    * Queue an activity for saving later.
    * @param array $Data The data in the activity.
    * @param string|FALSE $Preference The name of the preference governing the activity.
    * @param array $Options Additional options for saving.
    * @return type 
    */
   public function Queue($Data, $Preference = FALSE, $Options = array()) {
      $this->_Touch($Data);
      if (!isset($Data['NotifyUserID']) || !isset($Data['ActivityType']))
         throw Exception('Data missing NotifyUserID and/or ActivityType', 400);
      
      if ($Data['ActivityUserID'] == $Data['NotifyUserID'] && !GetValue('Force', $Options))
         return; // don't notify users of something they did.
      
      $Notified = $Data['Notified'];
      $Emailed = $Data['Emailed'];
      
      if (isset(self::$Queue[$Data['NotifyUserID']][$Data['ActivityType']])) {
         list($CurrentData, $CurrentOptions) = self::$Queue[$Data['NotifyUserID']][$Data['ActivityType']];
         
         $Notified = $Notified ? $Notified : $CurrentData['Notified'];
         $Emailed = $Emailed ? $Emailed : $CurrentData['Emailed'];
         $Data = array_merge($CurrentData, $Data);
         $Options = array_merge($CurrentOptions, $Options);
      }
      
      if ($Preference) {
         list($Popup, $Email) = self::NotificationPreference($Preference, $Data['NotifyUserID'], 'both');
         if (!$Popup && !$Email)
            return; // don't queue if user doesn't want to be notified at all.
         
         if ($Popup)
            $Notified = self::SENT_PENDING;
         if ($Email)
            $Emailed = self::SENT_PENDING;
      }
      $Data['Notified'] = $Notified;
      $Data['Emailed'] = $Emailed;
      
      self::$Queue[$Data['NotifyUserID']][$Data['ActivityType']] = array($Data, $Options);
   }
   
   public function Save($Data, $Preference = FALSE, $Options = array()) {
      $Activity = $Data;
      $this->_Touch($Activity);
      
      if ($Activity['ActivityUserID'] == $Activity['NotifyUserID'] && !GetValue('Force', $Options))
         return; // don't notify users of something they did.
      
      // Check the user's preference.
      if ($Preference) {
         list($Popup, $Email) = self::NotificationPreference($Preference, $Data['NotifyUserID'], 'both');
         if (!$Popop && !$Email && !GetValue('Force', $Options))
            return;
         
         if ($Popup)
            $Activity['Notified'] = self::SENT_PENDING;
         if ($Email)
            $Activity['Emailed'] = self::SENT_PENDING;
      }
      
      if (isset($Activity['Data']) && is_array($Activity['Data'])) {
         $Activity['Data'] = serialize($Activity['Data']);
      }
      $Activity['ActivityTypeID'] = ArrayValue('ActivityTypeID', self::GetActivityType($Activity['ActivityType']));
      
      $this->DefineSchema();
      $Activity = $this->FilterSchema($Activity);
      
      $ActivityID = GetValue('ActivityID', $Activity);
      if (!$ActivityID) {
         $this->AddInsertFields($Activity);
         $ActivityID = $this->SQL->Insert('Activity', $Activity);
         $Activity['ActivityID'] = $ActivityID;
      } else {
         $this->AddUpdateFields($Activity);
         unset($Activity['ActivityID']);
         $this->SQL->Put('Activity', $Activity, array('ActivityID' => $ActivityID));
         $Activity['ActivityID'] = $ActivityID;
      }
      return $Activity;
   }
   
   public function SaveQueue() {
      $Result = array();
      foreach (self::$Queue as $UserID => $Activities) {
         foreach ($Activities as $ActivityType => $Row) {
            $Result[] = $this->Save($Row[0], FALSE, $Row[1]);
         }
      }
      self::$Queue = array();
      return $Result;
   }
   
   protected function _Touch(&$Data) {
      TouchValue('ActivityType', $Data, 'Default');
      TouchValue('ActivityUserID', $Data, Gdn::Session()->UserID);
      TouchValue('NotifyUserID', $Data, self::NOTIFY_PUBLIC);
      TouchValue('Notified', $Data, 0);
      TouchValue('Emailed', $Data, 0);
   }
}