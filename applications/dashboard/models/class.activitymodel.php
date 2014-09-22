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
   
   public function CalculateData(&$Data) {
      foreach ($Data as &$Row) {
         $this->CalculateRow($Row);
      }
   }
   
   public function CalculateRow(&$Row) {
      $ActivityType = self::GetActivityType($Row['ActivityTypeID']);
      $Row['ActivityType'] = GetValue('Name', $ActivityType);
      if (is_string($Row['Data']))
         $Row['Data'] = @unserialize($Row['Data']);
      
      $Row['PhotoUrl'] = Url($Row['Route'], TRUE);
      if (!$Row['Photo']) {
         if (isset($Row['ActivityPhoto'])) {
            $Row['Photo'] = $Row['ActivityPhoto'];
            $Row['PhotoUrl'] = UserUrl($Row, 'Activity');
         } else {
            $User = Gdn::UserModel()->GetID($Row['ActivityUserID'], DATASET_TYPE_ARRAY);
            if ($User) {
               $Photo = $User['Photo'];
               $Row['PhotoUrl'] = UserUrl($User);
               if (!$Photo || StringBeginsWith($Photo, 'http'))
                  $Row['Photo'] = $Photo;
               else
                  $Row['Photo'] = Gdn_Upload::Url(ChangeBasename($Photo, 'n%s'));
            }
         }
      }
      
      $Data = $Row['Data'];
      if (isset($Data['ActivityUserIDs']))
         $Row['ActivityUserID'] = array_merge(array($Row['ActivityUserID']), $Data['ActivityUserIDs']);
      
      if (isset($Data['RegardingUserIDs']))
         $Row['RegardingUserID'] = array_merge(array($Row['RegardingUserID']), $Data['RegardingUserIDs']);
      
      
      $Row['Url'] = ExternalUrl($Row['Route']);
      
      if ($Row['HeadlineFormat']) {
         $Row['Headline'] = FormatString($Row['HeadlineFormat'], $Row);
      } else {
         $Row['Headline'] = Gdn_Format::ActivityHeadline($Row);
      }
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
   public function Delete($ActivityID, $Options = array()) {
      // Get the activity first.
      $Activity = $this->GetID($ActivityID);
      if ($Activity) {
         // Log the deletion.
         $Log = GetValue('Log', $Options);
         if ($Log) {
            LogModel::Insert($Log, 'Activity', $Activity);
         }
         
         // Delete comments on the activity item
         $this->SQL->Delete('ActivityComment', array('ActivityID' => $ActivityID));
         
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

      // Add the basic activity query.
      $this->SQL
         ->Select('a2.*')
         ->Select('t.FullHeadline, t.ProfileHeadline, t.AllowComments, t.ShowIcon, t.RouteCode')
         ->Select('t.Name', '', 'ActivityType')
         ->From('Activity a')
         ->Join('Activity a2', 'a.ActivityID = a2.ActivityID') // self-join for index speed.
         ->Join('ActivityType t', 'a2.ActivityTypeID = t.ActivityTypeID');
      
      // Add prefixes to the where.
      foreach ($Where as $Key => $Value) {
         if (strpos($Key, '.') === FALSE) {
            $Where['a.'.$Key] = $Value;
            unset($Where[$Key]);
         }
      }
      
      $Result = $this->SQL
         ->Where($Where)
         ->OrderBy('a.DateUpdated', 'desc')
         ->Limit($Limit, $Offset)
         ->Get();
      
      self::GetUsers($Result->ResultArray());
      Gdn::UserModel()->JoinUsers($Result->ResultArray(), array('ActivityUserID', 'RegardingUserID'), array('Join' => array('Name', 'Email', 'Gender', 'Photo')));
      $this->CalculateData($Result->ResultArray());
      
      $this->EventArguments['Data'] =& $Result;
      $this->FireEvent('AfterGet');
      
      return $Result;
   }
   
   /**
    * @param type $Activities 
    * @since 2.1
    */
   public function JoinComments(&$Activities) {
      // Grab all of the activity IDs.
      $ActivityIDs = array();
      foreach ($Activities as $Activity) {
         if ($ID = GetValue('CommentActivityID', $Activity['Data'])) {
            // This activity shares its comments with another activity.
            $ActivityIDs[] = $ID;
         } else {
            $ActivityIDs[] = $Activity['ActivityID'];
         }
      }
      $ActivityIDs = array_unique($ActivityIDs);
      
      $Comments = $this->GetComments($ActivityIDs);
      $Comments = Gdn_DataSet::Index($Comments, array('ActivityID'), array('Unique' => FALSE));
      foreach ($Activities as &$Activity) {
         $ID = GetValue('CommentActivityID', $Activity['Data']);
         if (!$ID)
            $ID = $Activity['ActivityID'];
         
         if (isset($Comments[$ID])) {
            $Activity['Comments'] = $Comments[$ID];
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
   
   public static function GetUsers(&$Data) {
      $UserIDs = array();
      
      foreach ($Data as &$Row) {
         if (is_string($Row['Data']))
            $Row['Data'] = @unserialize($Row['Data']);
         
         $UserIDs[$Row['ActivityUserID']] = 1;
         $UserIDs[$Row['RegardingUserID']] = 1;
         
         if (isset($Row['Data']['ActivityUserIDs'])) {
            foreach ($Row['Data']['ActivityUserIDs'] as $UserID) {
               $UserIDs[$UserID] = 1;
            }
         }
         
         if (isset($Row['Data']['RegardingUserIDs'])) {
            foreach ($Row['Data']['RegardingUserIDs'] as $UserID) {
               $UserIDs[$UserID] = 1;
            }
         }
      }
      
      Gdn::UserModel()->GetIDs(array_keys($UserIDs));
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
         ->Join('ActivityType t', 'a.ActivityTypeID = t.ActivityTypeID');
      
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
    * @return array|object A single SQL result.
    */
   public function GetID($ActivityID, $DataType = FALSE) {
      $Activity = parent::GetID($ActivityID, $DataType);
      if ($Activity) {
         $this->CalculateRow($Activity);
         $Activities = array($Activity);
         self::JoinUsers($Activities);
         $Activity = array_pop($Activities);
      }
      
      return $Activity;
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
      $Result->DatasetType(DATASET_TYPE_ARRAY);
      
      self::GetUsers($Result->ResultArray());
      Gdn::UserModel()->JoinUsers($Result->ResultArray(), array('ActivityUserID', 'RegardingUserID'), array('Join' => array('Name', 'Photo', 'Email', 'Gender')));
      $this->CalculateData($Result->ResultArray());
      
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
   /*public function GetCountNotifications($UserID) {
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
   }*/
   
   public function GetComment($ID) {
      $Activity = $this->SQL->GetWhere('ActivityComment', array('ActivityCommentID' => $ID))->ResultArray();
      if ($Activity) {
         Gdn::UserModel()->JoinUsers($Activity, array('InsertUserID'), array('Join' => array('Name', 'Photo', 'Email')));
         return array_shift($Activity);
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
   public function Add($ActivityUserID, $ActivityType, $Story = NULL, $RegardingUserID = NULL, $CommentActivityID = NULL, $Route = NULL, $SendEmail = '') {
      static $ActivityTypes = array();

      // Get the ActivityTypeID & see if this is a notification.
      $ActivityTypeRow = self::GetActivityType($ActivityType);
         
      if ($ActivityTypeRow !== FALSE) {
         $ActivityTypeID = $ActivityTypeRow['ActivityTypeID'];
         $Notify = (bool)$ActivityTypeRow['Notify'];
      } else {
         trigger_error(ErrorMessage(sprintf('Activity type could not be found: %s', $ActivityType), 'ActivityModel', 'Add'), E_USER_ERROR);
      }
      
      $Activity = array(
          'ActivityUserID' => $ActivityUserID,
          'ActivityType' => $ActivityType,
          'Story' => $Story,
          'RegardingUserID' => $RegardingUserID,
          'Route' => $Route
      );
      

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
      
      // If $SendEmail was FALSE or TRUE, let it override the $Notify setting.
      if ($SendEmail === FALSE || $SendEmail === TRUE)
         $Notify = $SendEmail;
      
      $Preference = FALSE;
      if (($ActivityTypeRow['Notify'] || !$ActivityTypeRow['Public']) && $RegardingUserID) {
         $Activity['NotifyUserID'] = $Activity['RegardingUserID'];
         $Preference = $ActivityType;
      } else {
         $Activity['NotifyUserID'] = self::NOTIFY_PUBLIC;
      }

      // Otherwise let the decision to email lie with the $Notify setting.
      if ($SendEmail == 'Force' || $Notify) {
         $Activity['Emailed'] = self::SENT_PENDING;
      } elseif ($Notify) {
         $Activity['Emailed'] = self::SENT_PENDING;
      } elseif ($SendEmail === FALSE) {
         $Activity['Emailed'] = self::SENT_ARCHIVE;
      }
      
      $Activity = $this->Save($Activity, $Preference);
      
      return GetValue('ActivityID', $Activity);
   }
   
   public static function JoinUsers(&$Activities) {
      Gdn::UserModel()->JoinUsers($Activities, array('ActivityUserID', 'RegardingUserID'), array('Join' => array('Name', 'Email', 'Gender', 'Photo')));
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
      if ((int)$ConfigPreference === 2)
         $Preference = TRUE; // This preference is forced on.
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
      if (!$Activity)
         return;
      
      $Activity = (object)$Activity;
      
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
            $Email->To($User);
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
               // Only send if the user is not banned
               if (!GetValue('Banned', $User))
                  $Email->Send();
               
               $Emailed = self::SENT_OK;
            } catch (phpmailerException $pex) {
               if ($pex->getCode() == PHPMailer::STOP_CRITICAL)
                  $Emailed = self::SENT_FAIL;
               else
                  $Emailed = self::SENT_ERROR;
            } catch (Exception $ex) {
               $Emailed = self::SENT_FAIL; // similar to http 5xx
            }
            try {
               $this->SQL->Put('Activity', array('Emailed' => $Emailed), array('ActivityID' => $ActivityID));
            } catch (Exception $Ex) {
            }
         }
      }
   }
   
   public function Email(&$Activity, $NoDelete = FALSE) {
      if (is_numeric($Activity)) {
         $ActivityID = $Activity;
         $Activity = $this->GetID($ActivityID);
      } else {
         $ActivityID = GetValue('ActivityID', $Activity);
      }
      
      if (!$Activity)
         return FALSE;
      
      $Activity = (array)$Activity;
      
      $User = Gdn::UserModel()->GetID($Activity['NotifyUserID'], DATASET_TYPE_ARRAY);
      if (!$User)
         return FALSE;
      
      // Format the activity headline based on the user being emailed.
      if (GetValue('HeadlineFormat', $Activity)) {
         $SessionUserID = Gdn::Session()->UserID;
         Gdn::Session()->UserID = $User['UserID'];
         $Activity['Headline'] = FormatString($Activity['HeadlineFormat'], $Activity);
         Gdn::Session()->UserID = $SessionUserID;
      } else {
         if (!isset($Activity['ActivityGender'])) {
            $AT = self::GetActivityType($Activity['ActivityType']);
            
            $Data = array($Activity);
            self::JoinUsers($Data);
            $Activity = $Data[0];
            $Activity['RouteCode'] = GetValue('RouteCode', $AT);
            $Activity['FullHeadline'] = GetValue('FullHeadline', $AT);
            $Activity['ProfileHeadline'] = GetValue('ProfileHeadline', $AT);
         }
         
         $Activity['Headline'] = Gdn_Format::ActivityHeadline($Activity, '', $User['UserID']);
      }
      
      // Build the email to send.
      $Email = new Gdn_Email();
      $Email->Subject(sprintf(T('[%1$s] %2$s'), C('Garden.Title'), Gdn_Format::PlainText($Activity['Headline'])));
      $Email->To($User);
      
      $Url = ExternalUrl($Activity['Route'] == '' ? '/' : $Activity['Route']);
      
      if ($Activity['Story']) {
         $Message = sprintf(T('EmailStoryNotification', "%3\$s\n\n%2\$s"), 
               Gdn_Format::PlainText($Activity['Headline']),
               $Url,
               Gdn_Format::PlainText($Activity['Story'])
            );
      } else {
         $Message = sprintf(T('EmailNotification', "%1\$s\n\n%2\$s"), Gdn_Format::PlainText($Activity['Headline']), $Url); 
      }
      $Email->Message($Message);
      
      // Fire an event for the notification.
      $Notification = array('ActivityID' => $ActivityID, 'User' => $User, 'Email' => $Email, 'Route' => $Activity['Route'], 'Story' => $Activity['Story'], 'Headline' => $Activity['Headline'], 'Activity' => $Activity);
      $this->EventArguments = $Notification;
      $this->FireEvent('BeforeSendNotification');
      
      // Send the email.
      try {
         // Only send if the user is not banned
         if (!GetValue('Banned', $User))
            $Email->Send();
         
         $Emailed = self::SENT_OK;
         
         // Delete the activity now that it has been emailed.
         if (!$NoDelete && !$Activity['Notified']) {
            if (GetValue('ActivityID', $Activity)) {
               $this->Delete($Activity['ActivityID']);
            } else {
               $Activity['_Delete'] = TRUE;
            }
         }
      } catch (phpmailerException $pex) {
         if ($pex->getCode() == PHPMailer::STOP_CRITICAL)
            $Emailed = self::SENT_FAIL;
         else
            $Emailed = self::SENT_ERROR;
      } catch (Exception $ex) {
         $Emailed = self::SENT_FAIL; // similar to http 5xx
      }
      $Activity['Emailed'] = $Emailed;
      if ($ActivityID) {
         // Save the emailed flag back to the activity.
         $this->SQL->Put('Activity', array('Emailed' => $Emailed), array('ActivityID' => $ActivityID));
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
    * @param array $Comment
    * @return int|bool 
    * @since 2.1
    */
   public function Comment($Comment) {
      $Comment['InsertUserID'] = Gdn::Session()->UserID;
      $Comment['DateInserted'] = Gdn_Format::ToDateTime();
      $Comment['InsertIPAddress'] = Gdn::Request()->IpAddress();
      
      $this->Validation->ApplyRule('ActivityID', 'Required');
      $this->Validation->ApplyRule('Body', 'Required');
      $this->Validation->ApplyRule('DateInserted', 'Required');
      $this->Validation->ApplyRule('InsertUserID', 'Required');
      
      $this->EventArguments['Comment'] = $Comment;
      $this->FireEvent('BeforeSaveComment');
      
      if ($this->Validate($Comment)) {
         $Activity = $this->GetID($Comment['ActivityID'], DATASET_TYPE_ARRAY);
         Gdn::Controller()->Json('Activity', $CommentActivityID);
         
         $_ActivityID = $Comment['ActivityID'];
         // Check to see if this is a shared activity/notification.
         if ($CommentActivityID = GetValue('CommentActivityID', $Activity['Data'])) {
            Gdn::Controller()->Json('CommentActivityID', $CommentActivityID);
            $Comment['ActivityID'] = $CommentActivityID;
         }
         
         // Check for spam.
         $Spam = SpamModel::IsSpam('ActivityComment', $Comment);
         if ($Spam)
            return SPAM;
         
         // Check for approval
         $ApprovalRequired = CheckRestriction('Vanilla.Approval.Require');
         if ($ApprovalRequired && !GetValue('Verified', Gdn::Session()->User)) {
         	LogModel::Insert('Pending', 'ActivityComment', $Comment);
         	return UNAPPROVED;
         }
         
         $ID = $this->SQL->Insert('ActivityComment', $Comment);
         
         if ($ID) {
            // Check to see if this comment bumps the activity.
            if ($Activity && GetValue('Bump', $Activity['Data'])) {
               $this->SQL->Put('Activity', array('DateUpdated' => $Comment['DateInserted']), array('ActivityID' => $Activity['ActivityID']));
               if ($_ActivityID != $Comment['ActivityID']) {
                  $this->SQL->Put('Activity', array('DateUpdated' => $Comment['DateInserted']), array('ActivityID' => $_ActivityID));
               }
            }
         }
         
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
                  // Only send if the user is not banned
                  $User = Gdn::UserModel()->GetID($UserID);
                  if (!GetValue('Banned', $User))
                     $Email->Send();
                  
                  $Emailed = self::SENT_OK;
               } catch (phpmailerException $pex) {
                  if ($pex->getCode() == PHPMailer::STOP_CRITICAL)
                     $Emailed = self::SENT_FAIL;
                  else
                     $Emailed = self::SENT_ERROR;
               } catch(Exception $Ex) {
                  $Emailed = self::SENT_FAIL;
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
   
   public function SetNotified($ActivityIDs) {
      if (!is_array($ActivityIDs) || count($ActivityIDs) == 0)
         return;
      
      $this->SQL->Update('Activity')
         ->Set('Notified', self::SENT_OK)
         ->WhereIn('ActivityID', $ActivityIDs)
         ->Put();
   }
   
   public function Share(&$Activity) {
      // Massage the event for the user.
      $this->EventArguments['RecordType'] = 'Activity';
      $this->EventArguments['Activity'] =& $Activity;
      
      $this->FireEvent('Share');
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
            $Email->To($User);
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
         throw new Exception('Data missing NotifyUserID and/or ActivityType', 400);
      
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
      Trace('ActivityModel->Save()');
      $Activity = $Data;
      $this->_Touch($Activity);
      
      if ($Activity['ActivityUserID'] == $Activity['NotifyUserID'] && !GetValue('Force', $Options)) {
         Trace('Skipping activity because it would notify the user of something they did.');
         
         return; // don't notify users of something they did.
      }
      
      // Check the user's preference.
      if ($Preference) {
         list($Popup, $Email) = self::NotificationPreference($Preference, $Activity['NotifyUserID'], 'both');
         
         if ($Popup && !$Activity['Notified'])
            $Activity['Notified'] = self::SENT_PENDING;
         if ($Email && !$Activity['Emailed'])
            $Activity['Emailed'] = self::SENT_PENDING;
         
         if (!$Activity['Notified'] && !$Activity['Emailed'] && !GetValue('Force', $Options)) {
            Trace("Skipping activity because the user has no preference set.");
            return;
         }
      }
      
      $ActivityType = self::GetActivityType($Activity['ActivityType']);
      $ActivityTypeID = ArrayValue('ActivityTypeID', $ActivityType);
      if (!$ActivityTypeID) {
         Trace("There is no $ActivityType activity type.", TRACE_WARNING);
         $ActivityType = self::GetActivityType('Default');
         $ActivityTypeID = ArrayValue('ActivityTypeID', $ActivityType);
      }
      
      $Activity['ActivityTypeID'] = $ActivityTypeID;
      
      $NotificationInc = 0;
      if ($Activity['NotifyUserID'] > 0 && $Activity['Notified'])
         $NotificationInc = 1;
      
      // Check to see if we are sharing this activity with another one.
      if ($CommentActivityID = GetValue('CommentActivityID', $Activity['Data'])) {
         $CommentActivity = $this->GetID($CommentActivityID);
         $Activity['Data']['CommentNotifyUserID'] = $CommentActivity['NotifyUserID'];
      }
      
      // Make sure this activity isn't a duplicate.
      if (GetValue('CheckRecord', $Options)) {
         // Check to see if this record already notified so we don't notify multiple times.
         $Where = ArrayTranslate($Activity, array('NotifyUserID', 'RecordType', 'RecordID'));
         $Where['DateUpdated >'] = Gdn_Format::ToDateTime(strtotime('-2 days')); // index hint
         
         $CheckActivity = $this->SQL->GetWhere(
            'Activity',
            $Where)->FirstRow();
         
         if ($CheckActivity)
            return FALSE;
      }
      
      // Check to share the activity.
      if (GetValue('Share', $Options)) {
         $this->Share($Activity);
      }
      
      // Group he activity.
      if ($GroupBy = GetValue('GroupBy', $Options)) {
         $GroupBy = (array)$GroupBy;
         $Where = array();
         foreach ($GroupBy as $ColumnName) {
            $Where[$ColumnName] = $Activity[$ColumnName];
         }
         $Where['NotifyUserID'] = $Activity['NotifyUserID'];
         // Make sure to only group activities by day.
         $Where['DateInserted >'] = Gdn_Format::ToDateTime(strtotime('-1 day'));
         
         // See if there is another activity to group these into.
         $GroupActivity = $this->SQL->GetWhere(
            'Activity', 
            $Where)->FirstRow(DATASET_TYPE_ARRAY);
         
         if ($GroupActivity) {
            $GroupActivity['Data'] = @unserialize($GroupActivity['Data']);
            $Activity = $this->MergeActivities($GroupActivity, $Activity);
            $NotificationInc = 0;
         }
      }
      
      $Delete = FALSE;
      if ($Activity['Emailed'] == self::SENT_PENDING) {
         $this->Email($Activity);
         $Delete = GetValue('_Delete', $Activity);
      }
      
      $ActivityData = $Activity['Data'];
      if (isset($Activity['Data']) && is_array($Activity['Data'])) {
         $Activity['Data'] = serialize($Activity['Data']);
      }
      
      $this->DefineSchema();
      $Activity = $this->FilterSchema($Activity);
      
      $ActivityID = GetValue('ActivityID', $Activity);
      if (!$ActivityID) {
         if (!$Delete) {
            $this->AddInsertFields($Activity);
            TouchValue('DateUpdated', $Activity, $Activity['DateInserted']);
            
            $this->EventArguments['Activity'] =& $Activity;
            $this->EventArguments['ActivityID'] = NULL;
            
            $Handled = FALSE;
            $this->EventArguments['Handled'] =& $Handled;
            
            $this->FireEvent('BeforeSave');
            
            if (count($this->ValidationResults()) > 0)
               return FALSE;
            
            if ($Handled) {
               // A plugin handled this activity so don't save it.
               return $Activity;
            }
            
            if (GetValue('CheckSpam', $Options)) {
               // Check for spam
            	$Spam = SpamModel::IsSpam('Activity', $Activity);
               if ($Spam)
                  return SPAM;
                  
            	// Check for approval
		         $ApprovalRequired = CheckRestriction('Vanilla.Approval.Require');
		         if ($ApprovalRequired && !GetValue('Verified', Gdn::Session()->User)) {
		         	LogModel::Insert('Pending', 'Activity', $Activity);
		         	return UNAPPROVED;
		         }
            }
            
            $ActivityID = $this->SQL->Insert('Activity', $Activity);
            $Activity['ActivityID'] = $ActivityID;
         }
      } else {
         $Activity['DateUpdated'] = Gdn_Format::ToDateTime();
         unset($Activity['ActivityID']);
         
         $this->EventArguments['Activity'] =& $Activity;
         $this->EventArguments['ActivityID'] = $ActivityID;
         $this->FireEvent('BeforeSave');
         
         if (count($this->ValidationResults()) > 0)
               return FALSE;
         
         $this->SQL->Put('Activity', $Activity, array('ActivityID' => $ActivityID));
         $Activity['ActivityID'] = $ActivityID;
      }
      $Activity['Data'] = $ActivityData;
      
      if (isset($CommentActivity)) {
         $CommentActivity['Data']['SharedActivityID'] = $Activity['ActivityID'];
         $CommentActivity['Data']['SharedNotifyUserID'] = $Activity['NotifyUserID'];
         $this->SetField($CommentActivity['ActivityID'], 'Data', $CommentActivity['Data']);
      }
      
      if ($NotificationInc > 0) {
         $CountNotifications =  Gdn::UserModel()->GetID($Activity['NotifyUserID'])->CountNotifications + $NotificationInc;
         Gdn::UserModel()->SetField($Activity['NotifyUserID'], 'CountNotifications', $CountNotifications);
      }
      
      // If this is a wall post then we need to notify on that.
      if (GetValue('Name', $ActivityType) == 'WallPost' && $Activity['NotifyUserID'] == self::NOTIFY_PUBLIC) {
         $this->NotifyWallPost($Activity);
      }
      
      return $Activity;
   }
   
   public function MarkRead($UserID) {
      // Mark all of a user's unread activities read.
      $this->SQL->Put(
         'Activity',
         array('Notified' => self::SENT_OK),
         array('NotifyUserID' => $UserID, 'Notified' => self::SENT_PENDING));
      
      $User = Gdn::UserModel()->GetID($UserID);
      if (GetValue('CountNotifications', $User) != 0)
         Gdn::UserModel()->SetField($UserID, 'CountNotifications', 0);
   }
   
   public function MergeActivities($OldActivity, $NewActivity, $Options = array()) {
      $GroupHeadlineFormat = GetValue('GroupHeadlineFormat', $Options, $NewActivity['HeadlineFormat']);
      $GroupStory = GetValue('GroupStory', $Options, $NewActivity['Story']);
      
//      decho($OldActivity, 'OldAct');

      // Group the two activities together.
      $ActivityUserIDs = GetValue('ActivityUserIDs', $OldActivity['Data'], array());
      array_unshift($ActivityUserIDs, $OldActivity['ActivityUserID']);
      if (($i = array_search($NewActivity['ActivityUserID'], $ActivityUserIDs)) !== FALSE) {
         unset($ActivityUserIDs[$i]);
         $ActivityUserIDs = array_values($ActivityUserIDs);
      }
      $ActivityUserIDs = array_unique($ActivityUserIDs);
//      decho($ActivityUserIDs, 'AIDs');
      
      if (GetValue('RegardingUserID', $NewActivity)) {
         $RegardingUserIDs = GetValue('RegardingUserIDs', $OldActivity['Data'], array());
         array_unshift($RegardingUserIDs, $OldActivity['RegardingUserID']);
         if (($i = array_search($NewActivity['RegardingUserID'], $RegardingUserIDs)) !== FALSE) {
            unset($RegardingUserIDs[$i]);
            $RegardingUserIDs = array_values($RegardingUserIDs);
         }
      }

      $RecordIDs = GetValue('RecordIDs', $GroupData, array());
      if ($OldActivity['RecordID'])
         $RecordIDs[] = $OldActivity['RecordID'];
      $RecordIDs = array_unique($RecordIDs);

      $NewActivity = array_merge($OldActivity, $NewActivity);
      
      if (count($ActivityUserIDs) > 0)
         $NewActivity['Data']['ActivityUserIDs'] = $ActivityUserIDs;
      if (count($RecordIDs) > 0)
         $NewActivity['Data']['RecordIDs'] = $RecordIDs;
      if (isset($RegardingUserIDs) && count($RegardingUserIDs) > 0) {
         $NewActivity['Data']['RegardingUserIDs'] = $RegardingUserIDs;
      }
      
//      decho($NewActivity, 'MergedActivity');
//      die();
      return $NewActivity;
   }
   
   protected function NotifyWallPost($WallPost) {
      $NotifyUser = Gdn::UserModel()->GetID($WallPost['ActivityUserID']);
      
      $Activity = array(
         'ActivityType' => 'WallPost',
         'ActivityUserID' => $WallPost['RegardingUserID'],
         'Format' => $WallPost['Format'],
         'NotifyUserID' => $WallPost['ActivityUserID'],
         'RecordType' => 'Activity',
         'RecordID' => $WallPost['ActivityID'],
         'RegardingUserID' => $WallPost['ActivityUserID'],
         'Route' => UserUrl($NotifyUser, ''),
         'Story' => $WallPost['Story'],
         'HeadlineFormat' => T('HeadlineFormat.NotifyWallPost', '{ActivityUserID,User} posted on your <a href="{Url,url}">wall</a>.')
      );
      
      $this->Save($Activity, 'WallComment');
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
      TouchValue('Headline', $Data, NULL);
      TouchValue('Story', $Data, NULL);
      TouchValue('Notified', $Data, 0);
      TouchValue('Emailed', $Data, 0);
      TouchValue('Photo', $Data, NULL);
      TouchValue('Route', $Data, NULL);
      if (!isset($Data['Data']) || !is_array($Data['Data']))
         $Data['Data'] = array();
   }
}
