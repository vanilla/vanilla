<?php
/**
 * Activity Model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Activity data management.
 */
class ActivityModel extends Gdn_Model {

    /** Activity notification level: Everyone. */
    const NOTIFY_PUBLIC = -1;

    /** Activity notification level: Moderators & admins. */
    const NOTIFY_MODS = -2;

    /** Activity notification level: Admins-only. */
    const NOTIFY_ADMINS = -3;

    /** Activity status: The activity was added before this system was put in place. */
    const SENT_ARCHIVE = 1;

    /** Activity status: The activity sent just fine. */
    const SENT_OK = 2;

    /** Activity status: The activity is waiting to be sent. */
    const SENT_PENDING = 3;

    /** Activity status: The activity could not be sent. */
    const SENT_FAIL = 4;

    /** Activity status: There was an error sending the activity, but it can be retried. */
    const SENT_ERROR = 5;

    /** Activity status: Sending is in progress. */
    const SENT_INPROGRESS = 31;

    /** @var array|null Allowed activity types. */
    public static $ActivityTypes = null;

    /** @var array Activity to be saved. */
    public static $Queue = array();

    /** @var int Limit on number of activity to combine. */
    public static $MaxMergeCount = 10;

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
    public function activityQuery($Join = true) {
        $this->SQL
            ->select('a.*')
            ->select('t.FullHeadline, t.ProfileHeadline, t.AllowComments, t.ShowIcon, t.RouteCode')
            ->select('t.Name', '', 'ActivityType')
            ->from('Activity a')
            ->join('ActivityType t', 'a.ActivityTypeID = t.ActivityTypeID');

        if ($Join) {
            $this->SQL
                ->select('au.Name', '', 'ActivityName')
                ->select('au.Gender', '', 'ActivityGender')
                ->select('au.Photo', '', 'ActivityPhoto')
                ->select('au.Email', '', 'ActivityEmail')
                ->select('ru.Name', '', 'RegardingName')
                ->select('ru.Gender', '', 'RegardingGender')
                ->select('ru.Email', '', 'RegardingEmail')
                ->select('ru.Photo', '', 'RegardingPhoto')
                ->join('User au', 'a.ActivityUserID = au.UserID')
                ->join('User ru', 'a.RegardingUserID = ru.UserID', 'left');
        }

        $this->fireEvent('AfterActivityQuery');
    }

    /**
     *
     *
     * @param $Data
     */
    public function calculateData(&$Data) {
        foreach ($Data as &$Row) {
            $this->CalculateRow($Row);
        }
    }

    /**
     *
     *
     * @param $Row
     */
    public function calculateRow(&$Row) {
        $ActivityType = self::GetActivityType($Row['ActivityTypeID']);
        $Row['ActivityType'] = val('Name', $ActivityType);
        if (is_string($Row['Data'])) {
            $Row['Data'] = @unserialize($Row['Data']);
        }

        $Row['PhotoUrl'] = url($Row['Route'], true);
        if (!$Row['Photo']) {
            if (isset($Row['ActivityPhoto'])) {
                $Row['Photo'] = $Row['ActivityPhoto'];
                $Row['PhotoUrl'] = userUrl($Row, 'Activity');
            } else {
                $User = Gdn::userModel()->getID($Row['ActivityUserID'], DATASET_TYPE_ARRAY);
                if ($User) {
                    $Photo = $User['Photo'];
                    $Row['PhotoUrl'] = userUrl($User);
                    if (!$Photo || stringBeginsWith($Photo, 'http')) {
                        $Row['Photo'] = $Photo;
                    } else {
                        $Row['Photo'] = Gdn_Upload::url(changeBasename($Photo, 'n%s'));
                    }
                }
            }
        }

        $Data = $Row['Data'];
        if (isset($Data['ActivityUserIDs'])) {
            $Row['ActivityUserID'] = array_merge(array($Row['ActivityUserID']), $Data['ActivityUserIDs']);
            $Row['ActivityUserID_Count'] = val('ActivityUserID_Count', $Data);
        }

        if (isset($Data['RegardingUserIDs'])) {
            $Row['RegardingUserID'] = array_merge(array($Row['RegardingUserID']), $Data['RegardingUserIDs']);
            $Row['RegardingUserID_Count'] = val('RegardingUserID_Count', $Data);
        }


        $Row['Url'] = ExternalUrl($Row['Route']);

        if ($Row['HeadlineFormat']) {
            $Row['Headline'] = formatString($Row['HeadlineFormat'], $Row);
        } else {
            $Row['Headline'] = Gdn_Format::activityHeadline($Row);
        }
    }

    /**
     * Define a new activity type.
     * @param string $Name The string code of the activity type.
     * @param array $Activity The data that goes in the ActivityType table.
     * @since 2.1
     */
    public function defineType($Name, $Activity = array()) {
        $this->SQL->replace('ActivityType', $Activity, array('Name' => $Name), true);
    }

    /**
     * Delete a particular activity item.
     *
     * @since 2.0.0
     * @access public
     * @param int $ActivityID Unique ID of acitivity to be deleted.
     */
    public function delete($ActivityID, $Options = array()) {
        // Get the activity first.
        $Activity = $this->getID($ActivityID);
        if ($Activity) {
            // Log the deletion.
            $Log = val('Log', $Options);
            if ($Log) {
                LogModel::insert($Log, 'Activity', $Activity);
            }

            // Delete comments on the activity item
            $this->SQL->delete('ActivityComment', array('ActivityID' => $ActivityID));

            // Delete the activity item
            parent::delete(array('ActivityID' => $ActivityID));
        }
    }

    /**
     * Delete an activity comment.
     *
     * @since 2.1
     *
     * @param int $ID
     * @return Gdn_DataSet
     */
    public function deleteComment($ID) {
        return $this->SQL->delete('ActivityComment', array('ActivityCommentID' => $ID));
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
    public function getWhere($Where, $Offset = 0, $Limit = 30) {
        if (is_string($Where)) {
            $Where = array($Where => $Offset);
            $Offset = 0;
        }

        // Add the basic activity query.
        $this->SQL
            ->select('a2.*')
            ->select('t.FullHeadline, t.ProfileHeadline, t.AllowComments, t.ShowIcon, t.RouteCode')
            ->select('t.Name', '', 'ActivityType')
            ->from('Activity a')
            ->join('Activity a2', 'a.ActivityID = a2.ActivityID')// self-join for index speed.
            ->join('ActivityType t', 'a2.ActivityTypeID = t.ActivityTypeID');

        // Add prefixes to the where.
        foreach ($Where as $Key => $Value) {
            if (strpos($Key, '.') === false) {
                $Where['a.'.$Key] = $Value;
                unset($Where[$Key]);
            }
        }

        $Result = $this->SQL
            ->where($Where)
            ->orderBy('a.DateUpdated', 'desc')
            ->limit($Limit, $Offset)
            ->get();

        self::getUsers($Result->resultArray());
        Gdn::userModel()->joinUsers($Result->resultArray(), array('ActivityUserID', 'RegardingUserID'), array('Join' => array('Name', 'Email', 'Gender', 'Photo')));
        $this->calculateData($Result->resultArray());

        $this->EventArguments['Data'] =& $Result;
        $this->fireEvent('AfterGet');

        return $Result;
    }

    /**
     *
     *
     * @param type $Activities
     * @since 2.1
     */
    public function joinComments(&$Activities) {
        // Grab all of the activity IDs.
        $ActivityIDs = array();
        foreach ($Activities as $Activity) {
            if ($ID = val('CommentActivityID', $Activity['Data'])) {
                // This activity shares its comments with another activity.
                $ActivityIDs[] = $ID;
            } else {
                $ActivityIDs[] = $Activity['ActivityID'];
            }
        }
        $ActivityIDs = array_unique($ActivityIDs);

        $Comments = $this->getComments($ActivityIDs);
        $Comments = Gdn_DataSet::index($Comments, array('ActivityID'), array('Unique' => false));
        foreach ($Activities as &$Activity) {
            $ID = val('CommentActivityID', $Activity['Data']);
            if (!$ID) {
                $ID = $Activity['ActivityID'];
            }

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
    public function get($NotifyUserID = false, $Offset = 0, $Limit = 30) {
        $Offset = is_numeric($Offset) ? $Offset : 0;
        if ($Offset < 0) {
            $Offset = 0;
        }

        $Limit = is_numeric($Limit) ? $Limit : 0;
        if ($Limit < 0) {
            $Limit = 30;
        }

        $this->activityQuery(false);

        if (!$NotifyUserID) {
            $NotifyUserID = self::NOTIFY_PUBLIC;
        }
        $this->SQL->whereIn('NotifyUserID', (array)$NotifyUserID);

        $this->fireEvent('BeforeGet');
        $Result = $this->SQL
            ->orderBy('a.ActivityID', 'desc')
            ->limit($Limit, $Offset)
            ->get();

        Gdn::userModel()->joinUsers($Result, array('ActivityUserID', 'RegardingUserID'), array('Join' => array('Name', 'Photo', 'Email', 'Gender')));

        $this->EventArguments['Data'] =& $Result;
        $this->fireEvent('AfterGet');

        return $Result;
    }

    /**
     *
     *
     * @param $Data
     */
    public static function getUsers(&$Data) {
        $UserIDs = array();

        foreach ($Data as &$Row) {
            if (is_string($Row['Data'])) {
                $Row['Data'] = @unserialize($Row['Data']);
            }

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

        Gdn::userModel()->getIDs(array_keys($UserIDs));
    }

    /**
     *
     *
     * @param $ActivityType
     * @return bool
     */
    public static function getActivityType($ActivityType) {
        if (self::$ActivityTypes === null) {
            $Data = Gdn::sql()->get('ActivityType')->resultArray();
            foreach ($Data as $Row) {
                self::$ActivityTypes[$Row['Name']] = $Row;
                self::$ActivityTypes[$Row['ActivityTypeID']] = $Row;
            }
        }
        if (isset(self::$ActivityTypes[$ActivityType])) {
            return self::$ActivityTypes[$ActivityType];
        }
        return false;
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
    public function getCount($UserID = '') {
        $this->SQL
            ->select('a.ActivityID', 'count', 'ActivityCount')
            ->from('Activity a')
            ->join('ActivityType t', 'a.ActivityTypeID = t.ActivityTypeID');

        if ($UserID != '') {
            $this->SQL
                ->beginWhereGroup()
                ->where('a.ActivityUserID', $UserID)
                ->orWhere('a.RegardingUserID', $UserID)
                ->endWhereGroup();
        }

        $Session = Gdn::session();
        if (!$Session->isValid() || $Session->UserID != $UserID) {
            $this->SQL->where('t.Public', '1');
        }

        $this->fireEvent('BeforeGetCount');
        return $this->SQL
            ->get()
            ->firstRow()
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
    public function getForRole($RoleID = '', $Offset = '0', $Limit = '50') {
        if (!is_array($RoleID)) {
            $RoleID = array($RoleID);
        }

        $Offset = is_numeric($Offset) ? $Offset : 0;
        if ($Offset < 0) {
            $Offset = 0;
        }

        $Limit = is_numeric($Limit) ? $Limit : 0;
        if ($Limit < 0) {
            $Limit = 0;
        }

        $this->activityQuery();
        $Result = $this->SQL
            ->join('UserRole ur', 'a.ActivityUserID = ur.UserID')
            ->whereIn('ur.RoleID', $RoleID)
            ->where('t.Public', '1')
            ->orderBy('a.DateInserted', 'desc')
            ->limit($Limit, $Offset)
            ->get();

        $this->EventArguments['Data'] =& $Result;
        $this->fireEvent('AfterGet');

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
    public function getCountForRole($RoleID = '') {
        if (!is_array($RoleID)) {
            $RoleID = array($RoleID);
        }

        return $this->SQL
            ->select('a.ActivityID', 'count', 'ActivityCount')
            ->from('Activity a')
            ->join('ActivityType t', 'a.ActivityTypeID = t.ActivityTypeID')
            ->join('UserRole ur', 'a.ActivityUserID = ur.UserID')
            ->whereIn('ur.RoleID', $RoleID)
            ->where('t.Public', '1')
            ->get()
            ->firstRow()
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
    public function getID($ActivityID, $DataType = false) {
        $Activity = parent::getID($ActivityID, $DataType);
        if ($Activity) {
            $this->calculateRow($Activity);
            $Activities = array($Activity);
            self::joinUsers($Activities);
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
    public function getNotifications($NotifyUserID, $Offset = '0', $Limit = '30') {
        $this->activityQuery(false);
        $this->fireEvent('BeforeGetNotifications');
        $Result = $this->SQL
            ->where('NotifyUserID', $NotifyUserID)
            ->limit($Limit, $Offset)
            ->orderBy('a.ActivityID', 'desc')
            ->get();
        $Result->DatasetType(DATASET_TYPE_ARRAY);

        self::getUsers($Result->resultArray());
        Gdn::userModel()->joinUsers($Result->resultArray(), array('ActivityUserID', 'RegardingUserID'), array('Join' => array('Name', 'Photo', 'Email', 'Gender')));
        $this->calculateData($Result->resultArray());

        return $Result;
    }


    /**
     * @param $activity
     * @return bool
     */
    public static function canDelete($activity) {
        $session = Gdn::session();

        $profileUserId = val('ActivityUserID', $activity);
        $notifyUserId = val('NotifyUserID', $activity);

        // User can delete any activity
        if ($session->checkPermission('Garden.Activity.Delete')) {
            return true;
        }

        $notifyUserIds = array(ActivityModel::NOTIFY_PUBLIC);
        if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            $notifyUserIds[] = ActivityModel::NOTIFY_MODS;
        }

        // Is this a wall post?
        if (!in_array(val('ActivityType', $activity), array('Status', 'WallPost')) || !in_array($notifyUserId, $notifyUserIds)) {
            return false;
        }
        // Is this on the user's wall?
        if ($profileUserId && $session->UserID == $profileUserId && $session->checkPermission('Garden.Profiles.Edit')) {
            return true;
        }

        // The user inserted the activity --- may be added in later
//      $insertUserId = val('InsertUserID', $activity);
//      if ($insertUserId && $insertUserId == $session->UserID) {
//         return true;
//      }

        return false;
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
    public function getNotificationsSince($UserID, $LastActivityID, $FilterToActivityTypeIDs = '', $Limit = '5') {
        $this->activityQuery();
        $this->fireEvent('BeforeGetNotificationsSince');
        if (is_array($FilterToActivityTypeIDs)) {
            $this->SQL->whereIn('a.ActivityTypeID', $FilterToActivityTypeIDs);
        } else {
            $this->SQL->where('t.Notify', '1');
        }

        $Result = $this->SQL
            ->where('RegardingUserID', $UserID)
            ->where('a.ActivityID >', $LastActivityID)
            ->limit($Limit, 0)
            ->orderBy('a.ActivityID', 'desc')
            ->get();

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
    /*public function getCountNotifications($UserID) {
       $this->SQL
          ->select('a.ActivityID', 'count', 'ActivityCount')
          ->from('Activity a')
          ->join('ActivityType t', 'a.ActivityTypeID = t.ActivityTypeID');

       $this->fireEvent('BeforeGetNotificationsCount');
       return $this->SQL
          ->where('RegardingUserID', $UserID)
          ->where('t.Notify', '1')
          ->get()
          ->firstRow()
          ->ActivityCount;
    }*/

    public function getComment($ID) {
        $Activity = $this->SQL->getWhere('ActivityComment', array('ActivityCommentID' => $ID))->resultArray();
        if ($Activity) {
            Gdn::userModel()->joinUsers($Activity, array('InsertUserID'), array('Join' => array('Name', 'Photo', 'Email')));
            return array_shift($Activity);
        }
        return false;
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
    public function getComments($ActivityIDs) {
        $Result = $this->SQL
            ->select('c.*')
            ->from('ActivityComment c')
            ->whereIn('c.ActivityID', $ActivityIDs)
            ->orderBy('c.ActivityID, c.DateInserted')
            ->get()->resultArray();
        Gdn::userModel()->joinUsers($Result, array('InsertUserID'), array('Join' => array('Name', 'Photo', 'Email')));
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
    public function add($ActivityUserID, $ActivityType, $Story = null, $RegardingUserID = null, $CommentActivityID = null, $Route = null, $SendEmail = '') {
        static $ActivityTypes = array();

        // Get the ActivityTypeID & see if this is a notification.
        $ActivityTypeRow = self::getActivityType($ActivityType);

        if ($ActivityTypeRow !== false) {
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
        $QueueEmail = false;
        if ($SendEmail === 'Only') {
            $SendEmail = '';
            $AddActivity = false;
        } elseif ($SendEmail === 'QueueOnly') {
            $SendEmail = '';
            $QueueEmail = true;
            $AddActivity = true;
            $Notify = true;
        } else {
            $AddActivity = true;
        }

        // If $SendEmail was FALSE or TRUE, let it override the $Notify setting.
        if ($SendEmail === false || $SendEmail === true) {
            $Notify = $SendEmail;
        }

        $Preference = false;
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
        } elseif ($SendEmail === false) {
            $Activity['Emailed'] = self::SENT_ARCHIVE;
        }

        $Activity = $this->save($Activity, $Preference);

        return val('ActivityID', $Activity);
    }

    public static function joinUsers(&$Activities) {
        Gdn::userModel()->joinUsers($Activities, array('ActivityUserID', 'RegardingUserID'), array('Join' => array('Name', 'Email', 'Gender', 'Photo')));
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
    public static function notificationPreference($ActivityType, $Preferences, $Type = null) {
        if (is_numeric($Preferences)) {
            $User = Gdn::userModel()->getID($Preferences);
            if (!$User) {
                return $Type == 'both' ? array(false, false) : false;
            }
            $Preferences = val('Preferences', $User);
        }

        if ($Type === null) {
            $Result = self::notificationPreference($ActivityType, $Preferences, 'Email')
                || self::notificationPreference($ActivityType, $Preferences, 'Popup');

            return $Result;
        } elseif ($Type === 'both') {
            $Result = array(
                self::notificationPreference($ActivityType, $Preferences, 'Popup'),
                self::notificationPreference($ActivityType, $Preferences, 'Email')
            );
            return $Result;
        }

        $ConfigPreference = c("Preferences.$Type.$ActivityType", '0');
        if ((int)$ConfigPreference === 2) {
            $Preference = true; // This preference is forced on.
        }        if ($ConfigPreference !== false) {
            $Preference = arrayValue($Type.'.'.$ActivityType, $Preferences, $ConfigPreference);
        } else {
            $Preference = false;
        }

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
    public function sendNotification($ActivityID, $Story = '', $Force = false) {
        $Activity = $this->getID($ActivityID);
        if (!$Activity) {
            return;
        }

        $Activity = (object)$Activity;

        $Story = Gdn_Format::text($Story == '' ? $Activity->Story : $Story, false);
        // If this is a comment on another activity, fudge the activity a bit so that everything appears properly.
        if (is_null($Activity->RegardingUserID) && $Activity->CommentActivityID > 0) {
            $CommentActivity = $this->getID($Activity->CommentActivityID);
            $Activity->RegardingUserID = $CommentActivity->RegardingUserID;
            $Activity->Route = '/activity/item/'.$Activity->CommentActivityID;
        }

        $User = Gdn::userModel()->getID($Activity->RegardingUserID, DATASET_TYPE_OBJECT);

        if ($User) {
            if ($Force) {
                $Preference = $Force;
            } else {
                $Preferences = $User->Preferences;
                $Preference = arrayValue('Email.'.$Activity->ActivityType, $Preferences, Gdn::config('Preferences.Email.'.$Activity->ActivityType));
            }
            if ($Preference) {
                $ActivityHeadline = Gdn_Format::text(Gdn_Format::activityHeadline($Activity, $Activity->ActivityUserID, $Activity->RegardingUserID), false);
                $Email = new Gdn_Email();
                $Email->subject(sprintf(t('[%1$s] %2$s'), Gdn::config('Garden.Title'), $ActivityHeadline));
                $Email->to($User);
                //$Email->from(Gdn::config('Garden.SupportEmail'), Gdn::config('Garden.SupportName'));

                $Message = sprintf(
                    $Story == '' ? t('EmailNotification', "%1\$s\n\n%2\$s") : t('EmailStoryNotification', "%3\$s\n\n%2\$s"),
                    $ActivityHeadline,
                    ExternalUrl($Activity->Route == '' ? '/' : $Activity->Route),
                    $Story
                );
                $Email->message($Message);

                $Notification = array('ActivityID' => $ActivityID, 'User' => $User, 'Email' => $Email, 'Route' => $Activity->Route, 'Story' => $Story, 'Headline' => $ActivityHeadline, 'Activity' => $Activity);
                $this->EventArguments = $Notification;
                $this->fireEvent('BeforeSendNotification');
                try {
                    // Only send if the user is not banned
                    if (!val('Banned', $User)) {
                        $Email->send();
                    }

                    $Emailed = self::SENT_OK;
                } catch (phpmailerException $pex) {
                    if ($pex->getCode() == PHPMailer::STOP_CRITICAL) {
                        $Emailed = self::SENT_FAIL;
                    } else {
                        $Emailed = self::SENT_ERROR;
                    }
                } catch (Exception $ex) {
                    $Emailed = self::SENT_FAIL; // similar to http 5xx
                }
                try {
                    $this->SQL->put('Activity', array('Emailed' => $Emailed), array('ActivityID' => $ActivityID));
                } catch (Exception $Ex) {
                }
            }
        }
    }

    /**
     *
     *
     * @param $Activity
     * @param bool $NoDelete
     * @return bool
     * @throws Exception
     */
    public function email(&$Activity, $NoDelete = false) {
        if (is_numeric($Activity)) {
            $ActivityID = $Activity;
            $Activity = $this->getID($ActivityID);
        } else {
            $ActivityID = val('ActivityID', $Activity);
        }

        if (!$Activity) {
            return false;
        }

        $Activity = (array)$Activity;

        $User = Gdn::userModel()->getID($Activity['NotifyUserID'], DATASET_TYPE_ARRAY);
        if (!$User) {
            return false;
        }

        // Format the activity headline based on the user being emailed.
        if (val('HeadlineFormat', $Activity)) {
            $SessionUserID = Gdn::session()->UserID;
            Gdn::session()->UserID = $User['UserID'];
            $Activity['Headline'] = formatString($Activity['HeadlineFormat'], $Activity);
            Gdn::session()->UserID = $SessionUserID;
        } else {
            if (!isset($Activity['ActivityGender'])) {
                $AT = self::getActivityType($Activity['ActivityType']);

                $Data = array($Activity);
                self::joinUsers($Data);
                $Activity = $Data[0];
                $Activity['RouteCode'] = val('RouteCode', $AT);
                $Activity['FullHeadline'] = val('FullHeadline', $AT);
                $Activity['ProfileHeadline'] = val('ProfileHeadline', $AT);
            }

            $Activity['Headline'] = Gdn_Format::activityHeadline($Activity, '', $User['UserID']);
        }

        // Build the email to send.
        $Email = new Gdn_Email();
        $Email->subject(sprintf(t('[%1$s] %2$s'), c('Garden.Title'), Gdn_Format::plainText($Activity['Headline'])));
        $Email->to($User);

        $Url = ExternalUrl($Activity['Route'] == '' ? '/' : $Activity['Route']);

        if ($Activity['Story']) {
            $Message = sprintf(
                t('EmailStoryNotification', "%3\$s\n\n%2\$s"),
                Gdn_Format::plainText($Activity['Headline']),
                $Url,
                Gdn_Format::plainText($Activity['Story'])
            );
        } else {
            $Message = sprintf(t('EmailNotification', "%1\$s\n\n%2\$s"), Gdn_Format::plainText($Activity['Headline']), $Url);
        }
        $Email->message($Message);

        // Fire an event for the notification.
        $Notification = array('ActivityID' => $ActivityID, 'User' => $User, 'Email' => $Email, 'Route' => $Activity['Route'], 'Story' => $Activity['Story'], 'Headline' => $Activity['Headline'], 'Activity' => $Activity);
        $this->EventArguments = $Notification;
        $this->fireEvent('BeforeSendNotification');

        // Send the email.
        try {
            // Only send if the user is not banned
            if (!val('Banned', $User)) {
                $Email->send();
            }

            $Emailed = self::SENT_OK;

            // Delete the activity now that it has been emailed.
            if (!$NoDelete && !$Activity['Notified']) {
                if (val('ActivityID', $Activity)) {
                    $this->delete($Activity['ActivityID']);
                } else {
                    $Activity['_Delete'] = true;
                }
            }
        } catch (phpmailerException $pex) {
            if ($pex->getCode() == PHPMailer::STOP_CRITICAL) {
                $Emailed = self::SENT_FAIL;
            } else {
                $Emailed = self::SENT_ERROR;
            }
        } catch (Exception $ex) {
            $Emailed = self::SENT_FAIL; // similar to http 5xx
        }
        $Activity['Emailed'] = $Emailed;
        if ($ActivityID) {
            // Save the emailed flag back to the activity.
            $this->SQL->put('Activity', array('Emailed' => $Emailed), array('ActivityID' => $ActivityID));
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
    public function clearNotificationQueue() {
        unset($this->_NotificationQueue);
        $this->_NotificationQueue = array();
    }

    /**
     * Save a comment on an activity.
     * @param array $Comment
     * @return int|bool
     * @since 2.1
     */
    public function comment($Comment) {
        $Comment['InsertUserID'] = Gdn::session()->UserID;
        $Comment['DateInserted'] = Gdn_Format::toDateTime();
        $Comment['InsertIPAddress'] = Gdn::request()->ipAddress();

        $this->Validation->applyRule('ActivityID', 'Required');
        $this->Validation->applyRule('Body', 'Required');
        $this->Validation->applyRule('DateInserted', 'Required');
        $this->Validation->applyRule('InsertUserID', 'Required');

        $this->EventArguments['Comment'] = $Comment;
        $this->fireEvent('BeforeSaveComment');

        if ($this->validate($Comment)) {
            $Activity = $this->getID($Comment['ActivityID'], DATASET_TYPE_ARRAY);
            Gdn::controller()->json('Activity', $CommentActivityID);

            $_ActivityID = $Comment['ActivityID'];
            // Check to see if this is a shared activity/notification.
            if ($CommentActivityID = val('CommentActivityID', $Activity['Data'])) {
                Gdn::controller()->json('CommentActivityID', $CommentActivityID);
                $Comment['ActivityID'] = $CommentActivityID;
            }

            // Check for spam.
            $Spam = SpamModel::isSpam('ActivityComment', $Comment);
            if ($Spam) {
                return SPAM;
            }

            // Check for approval
            $ApprovalRequired = CheckRestriction('Vanilla.Approval.Require');
            if ($ApprovalRequired && !val('Verified', Gdn::session()->User)) {
                LogModel::insert('Pending', 'ActivityComment', $Comment);
                return UNAPPROVED;
            }

            $ID = $this->SQL->insert('ActivityComment', $Comment);

            if ($ID) {
                // Check to see if this comment bumps the activity.
                if ($Activity && val('Bump', $Activity['Data'])) {
                    $this->SQL->put('Activity', array('DateUpdated' => $Comment['DateInserted']), array('ActivityID' => $Activity['ActivityID']));
                    if ($_ActivityID != $Comment['ActivityID']) {
                        $this->SQL->put('Activity', array('DateUpdated' => $Comment['DateInserted']), array('ActivityID' => $_ActivityID));
                    }
                }

                // Send a notification to the original person.
                if (val('ActivityType', $Activity) === 'WallPost') {
                    $this->notifyWallComment($Comment, $Activity);
                }
            }

            return $ID;
        }
        return false;
    }

    /**
     * Send all notifications in the queue.
     *
     * @since 2.0.17
     * @access public
     */
    public function sendNotificationQueue() {
        foreach ($this->_NotificationQueue as $UserID => $Notifications) {
            if (is_array($Notifications)) {
                // Only send out one notification per user.
                $Notification = $Notifications[0];

                $Email = $Notification['Email'];

                if (is_object($Email)) {
                    $this->EventArguments = $Notification;
                    $this->fireEvent('BeforeSendNotification');

                    try {
                        // Only send if the user is not banned
                        $User = Gdn::userModel()->getID($UserID);
                        if (!val('Banned', $User)) {
                            $Email->send();
                        }

                        $Emailed = self::SENT_OK;
                    } catch (phpmailerException $pex) {
                        if ($pex->getCode() == PHPMailer::STOP_CRITICAL) {
                            $Emailed = self::SENT_FAIL;
                        } else {
                            $Emailed = self::SENT_ERROR;
                        }
                    } catch (Exception $Ex) {
                        $Emailed = self::SENT_FAIL;
                    }

                    try {
                        $this->SQL->put('Activity', array('Emailed' => $Emailed), array('ActivityID' => $Notification['ActivityID']));
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
     *
     *
     * @param $ActivityIDs
     * @throws Exception
     */
    public function setNotified($ActivityIDs) {
        if (!is_array($ActivityIDs) || count($ActivityIDs) == 0) {
            return;
        }

        $this->SQL->update('Activity')
            ->set('Notified', self::SENT_OK)
            ->whereIn('ActivityID', $ActivityIDs)
            ->put();
    }

    /**
     *
     *
     * @param $Activity
     * @throws Exception
     */
    public function share(&$Activity) {
        // Massage the event for the user.
        $this->EventArguments['RecordType'] = 'Activity';
        $this->EventArguments['Activity'] =& $Activity;

        $this->fireEvent('Share');
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
    public function queueNotification($ActivityID, $Story = '', $Position = 'last', $Force = false) {
        $Activity = $this->getID($ActivityID);
        if (!is_object($Activity)) {
            return;
        }

        $Story = Gdn_Format::text($Story == '' ? $Activity->Story : $Story, false);
        // If this is a comment on another activity, fudge the activity a bit so that everything appears properly.
        if (is_null($Activity->RegardingUserID) && $Activity->CommentActivityID > 0) {
            $CommentActivity = $this->getID($Activity->CommentActivityID);
            $Activity->RegardingUserID = $CommentActivity->RegardingUserID;
            $Activity->Route = '/activity/item/'.$Activity->CommentActivityID;
        }
        $User = Gdn::userModel()->getID($Activity->RegardingUserID, DATASET_TYPE_OBJECT); //$this->SQL->select('UserID, Name, Email, Preferences')->from('User')->where('UserID', $Activity->RegardingUserID)->get()->firstRow();

        if ($User) {
            if ($Force) {
                $Preference = $Force;
            } else {
            //            $Preferences = Gdn_Format::Unserialize($User->Preferences);
                $ConfigPreference = c('Preferences.Email.'.$Activity->ActivityType, '0');
                if ($ConfigPreference !== false) {
                    $Preference = val('Email.'.$Activity->ActivityType, $User->Preferences, $ConfigPreference);
                } else {
                    $Preference = false;
                }
            }

            if ($Preference) {
                $ActivityHeadline = Gdn_Format::text(Gdn_Format::activityHeadline($Activity, $Activity->ActivityUserID, $Activity->RegardingUserID), false);
                $Email = new Gdn_Email();
                $Email->subject(sprintf(t('[%1$s] %2$s'), Gdn::config('Garden.Title'), $ActivityHeadline));
                $Email->to($User);
                $Message = sprintf(
                    $Story == '' ? t('EmailNotification', "%1\$s\n\n%2\$s") : t('EmailStoryNotification', "%3\$s\n\n%2\$s"),
                    $ActivityHeadline,
                    ExternalUrl($Activity->Route == '' ? '/' : $Activity->Route),
                    $Story
                );
                $Email->message($Message);
                if (!array_key_exists($User->UserID, $this->_NotificationQueue)) {
                    $this->_NotificationQueue[$User->UserID] = array();
                }

                $Notification = array('ActivityID' => $ActivityID, 'User' => $User, 'Email' => $Email, 'Route' => $Activity->Route, 'Story' => $Story, 'Headline' => $ActivityHeadline, 'Activity' => $Activity);
                if ($Position == 'first') {
                    $this->_NotificationQueue[$User->UserID] = array_merge(array($Notification), $this->_NotificationQueue[$User->UserID]);
                } else {
                    $this->_NotificationQueue[$User->UserID][] = $Notification;
                }
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
    public function queue($Data, $Preference = false, $Options = array()) {
        $this->_touch($Data);
        if (!isset($Data['NotifyUserID']) || !isset($Data['ActivityType'])) {
            throw new Exception('Data missing NotifyUserID and/or ActivityType', 400);
        }

        if ($Data['ActivityUserID'] == $Data['NotifyUserID'] && !val('Force', $Options)) {
            return; // don't notify users of something they did.
        }
        $Notified = $Data['Notified'];
        $Emailed = $Data['Emailed'];

        if (isset(self::$Queue[$Data['NotifyUserID']][$Data['ActivityType']])) {
            list($CurrentData, $CurrentOptions) = self::$Queue[$Data['NotifyUserID']][$Data['ActivityType']];

            $Notified = $Notified ? $Notified : $CurrentData['Notified'];
            $Emailed = $Emailed ? $Emailed : $CurrentData['Emailed'];

            $Reason = null;
            if (isset($CurrentData['Data']['Reason']) && isset($Data['Data']['Reason'])) {
                $Reason = array_merge((array)$CurrentData['Data']['Reason'], (array)$Data['Data']['Reason']);
                $Reason = array_unique($Reason);
            }

            $Data = array_merge($CurrentData, $Data);
            $Options = array_merge($CurrentOptions, $Options);
            if ($Reason) {
                $Data['Data']['Reason'] = $Reason;
            }
        }

        if ($Preference) {
            list($Popup, $Email) = self::notificationPreference($Preference, $Data['NotifyUserID'], 'both');
            if (!$Popup && !$Email) {
                return; // don't queue if user doesn't want to be notified at all.
            }
            if ($Popup) {
                $Notified = self::SENT_PENDING;
            }
            if ($Email) {
                $Emailed = self::SENT_PENDING;
            }
        }
        $Data['Notified'] = $Notified;
        $Data['Emailed'] = $Emailed;

        self::$Queue[$Data['NotifyUserID']][$Data['ActivityType']] = array($Data, $Options);
    }

    /**
     *
     *
     * @param array $Data
     * @param bool $Preference
     * @param array $Options
     * @return array
     * @throws Exception
     */
    public function save($Data, $Preference = false, $Options = array()) {
        trace('ActivityModel->save()');
        $Activity = $Data;
        $this->_touch($Activity);

        if ($Activity['ActivityUserID'] == $Activity['NotifyUserID'] && !val('Force', $Options)) {
            trace('Skipping activity because it would notify the user of something they did.');

            return; // don't notify users of something they did.
        }

        // Check the user's preference.
        if ($Preference) {
            list($Popup, $Email) = self::notificationPreference($Preference, $Activity['NotifyUserID'], 'both');

            if ($Popup && !$Activity['Notified']) {
                $Activity['Notified'] = self::SENT_PENDING;
            }
            if ($Email && !$Activity['Emailed']) {
                $Activity['Emailed'] = self::SENT_PENDING;
            }

            if (!$Activity['Notified'] && !$Activity['Emailed'] && !val('Force', $Options)) {
                trace("Skipping activity because the user has no preference set.");
                return;
            }
        }

        $ActivityType = self::getActivityType($Activity['ActivityType']);
        $ActivityTypeID = arrayValue('ActivityTypeID', $ActivityType);
        if (!$ActivityTypeID) {
            trace("There is no $ActivityType activity type.", TRACE_WARNING);
            $ActivityType = self::getActivityType('Default');
            $ActivityTypeID = arrayValue('ActivityTypeID', $ActivityType);
        }

        $Activity['ActivityTypeID'] = $ActivityTypeID;

        $NotificationInc = 0;
        if ($Activity['NotifyUserID'] > 0 && $Activity['Notified']) {
            $NotificationInc = 1;
        }

        // Check to see if we are sharing this activity with another one.
        if ($CommentActivityID = val('CommentActivityID', $Activity['Data'])) {
            $CommentActivity = $this->getID($CommentActivityID);
            $Activity['Data']['CommentNotifyUserID'] = $CommentActivity['NotifyUserID'];
        }

        // Make sure this activity isn't a duplicate.
        if (val('CheckRecord', $Options)) {
            // Check to see if this record already notified so we don't notify multiple times.
            $Where = arrayTranslate($Activity, array('NotifyUserID', 'RecordType', 'RecordID'));
            $Where['DateUpdated >'] = Gdn_Format::toDateTime(strtotime('-2 days')); // index hint

            $CheckActivity = $this->SQL->getWhere(
                'Activity',
                $Where
            )->firstRow();

            if ($CheckActivity) {
                return false;
            }
        }

        // Check to share the activity.
        if (val('Share', $Options)) {
            $this->Share($Activity);
        }

        // Group he activity.
        if ($GroupBy = val('GroupBy', $Options)) {
            $GroupBy = (array)$GroupBy;
            $Where = array();
            foreach ($GroupBy as $ColumnName) {
                $Where[$ColumnName] = $Activity[$ColumnName];
            }
            $Where['NotifyUserID'] = $Activity['NotifyUserID'];
            // Make sure to only group activities by day.
            $Where['DateInserted >'] = Gdn_Format::toDateTime(strtotime('-1 day'));

            // See if there is another activity to group these into.
            $GroupActivity = $this->SQL->getWhere(
                'Activity',
                $Where
            )->firstRow(DATASET_TYPE_ARRAY);

            if ($GroupActivity) {
                $GroupActivity['Data'] = @unserialize($GroupActivity['Data']);
                $Activity = $this->mergeActivities($GroupActivity, $Activity);
                $NotificationInc = 0;
            }
        }

        $Delete = false;
        if ($Activity['Emailed'] == self::SENT_PENDING) {
            $this->email($Activity);
            $Delete = val('_Delete', $Activity);
        }

        $ActivityData = $Activity['Data'];
        if (isset($Activity['Data']) && is_array($Activity['Data'])) {
            $Activity['Data'] = serialize($Activity['Data']);
        }

        $this->defineSchema();
        $Activity = $this->filterSchema($Activity);

        $ActivityID = val('ActivityID', $Activity);
        if (!$ActivityID) {
            if (!$Delete) {
                $this->addInsertFields($Activity);
                touchValue('DateUpdated', $Activity, $Activity['DateInserted']);

                $this->EventArguments['Activity'] =& $Activity;
                $this->EventArguments['ActivityID'] = null;

                $Handled = false;
                $this->EventArguments['Handled'] =& $Handled;

                $this->fireEvent('BeforeSave');

                if (count($this->validationResults()) > 0) {
                    return false;
                }

                if ($Handled) {
                    // A plugin handled this activity so don't save it.
                    return $Activity;
                }

                if (val('CheckSpam', $Options)) {
                    // Check for spam
                    $Spam = SpamModel::isSpam('Activity', $Activity);
                    if ($Spam) {
                        return SPAM;
                    }

                    // Check for approval
                    $ApprovalRequired = CheckRestriction('Vanilla.Approval.Require');
                    if ($ApprovalRequired && !val('Verified', Gdn::session()->User)) {
                        LogModel::insert('Pending', 'Activity', $Activity);
                        return UNAPPROVED;
                    }
                }

                $ActivityID = $this->SQL->insert('Activity', $Activity);
                $Activity['ActivityID'] = $ActivityID;
            }
        } else {
            $Activity['DateUpdated'] = Gdn_Format::toDateTime();
            unset($Activity['ActivityID']);

            $this->EventArguments['Activity'] =& $Activity;
            $this->EventArguments['ActivityID'] = $ActivityID;
            $this->fireEvent('BeforeSave');

            if (count($this->validationResults()) > 0) {
                return false;
            }

            $this->SQL->put('Activity', $Activity, array('ActivityID' => $ActivityID));
            $Activity['ActivityID'] = $ActivityID;
        }
        $Activity['Data'] = $ActivityData;

        if (isset($CommentActivity)) {
            $CommentActivity['Data']['SharedActivityID'] = $Activity['ActivityID'];
            $CommentActivity['Data']['SharedNotifyUserID'] = $Activity['NotifyUserID'];
            $this->setField($CommentActivity['ActivityID'], 'Data', $CommentActivity['Data']);
        }

        if ($NotificationInc > 0) {
            $CountNotifications = Gdn::userModel()->getID($Activity['NotifyUserID'])->CountNotifications + $NotificationInc;
            Gdn::userModel()->setField($Activity['NotifyUserID'], 'CountNotifications', $CountNotifications);
        }

        // If this is a wall post then we need to notify on that.
        if (val('Name', $ActivityType) == 'WallPost' && $Activity['NotifyUserID'] == self::NOTIFY_PUBLIC) {
            $this->notifyWallPost($Activity);
        }

        return $Activity;
    }

    /**
     *
     *
     * @param $UserID
     */
    public function markRead($UserID) {
        // Mark all of a user's unread activities read.
        $this->SQL->put(
            'Activity',
            array('Notified' => self::SENT_OK),
            array('NotifyUserID' => $UserID, 'Notified' => self::SENT_PENDING)
        );

        $User = Gdn::userModel()->getID($UserID);
        if (val('CountNotifications', $User) != 0) {
            Gdn::userModel()->setField($UserID, 'CountNotifications', 0);
        }
    }

    /**
     *
     *
     * @param $OldActivity
     * @param $NewActivity
     * @param array $Options
     * @return array
     */
    public function mergeActivities($OldActivity, $NewActivity, $Options = array()) {
        $GroupHeadlineFormat = val('GroupHeadlineFormat', $Options, $NewActivity['HeadlineFormat']);
        $GroupStory = val('GroupStory', $Options, $NewActivity['Story']);

//      decho($OldActivity, 'OldAct');

        // Group the two activities together.
        $ActivityUserIDs = val('ActivityUserIDs', $OldActivity['Data'], array());
        $ActivityUserCount = val('ActivityUserID_Count', $OldActivity['Data'], 0);
        array_unshift($ActivityUserIDs, $OldActivity['ActivityUserID']);
        if (($i = array_search($NewActivity['ActivityUserID'], $ActivityUserIDs)) !== false) {
            unset($ActivityUserIDs[$i]);
            $ActivityUserIDs = array_values($ActivityUserIDs);
        }
        $ActivityUserIDs = array_unique($ActivityUserIDs);
        if (count($ActivityUserIDs) > self::$MaxMergeCount) {
            array_pop($ActivityUserIDs);
            $ActivityUserCount++;
        }

//      decho($ActivityUserIDs, 'AIDs');

        if (val('RegardingUserID', $NewActivity)) {
            $RegardingUserIDs = val('RegardingUserIDs', $OldActivity['Data'], array());
            $RegardingUserCount = val('RegardingUserID_Count', $OldActivity['Data'], 0);
            array_unshift($RegardingUserIDs, $OldActivity['RegardingUserID']);
            if (($i = array_search($NewActivity['RegardingUserID'], $RegardingUserIDs)) !== false) {
                unset($RegardingUserIDs[$i]);
                $RegardingUserIDs = array_values($RegardingUserIDs);
            }
            if (count($RegardingUserIDs) > self::$MaxMergeCount) {
                array_pop($RegardingUserIDs);
                $RegardingUserCount++;
            }
        }

        $RecordIDs = val('RecordIDs', $GroupData, array());
        if ($OldActivity['RecordID']) {
            $RecordIDs[] = $OldActivity['RecordID'];
        }
        $RecordIDs = array_unique($RecordIDs);

        $NewActivity = array_merge($OldActivity, $NewActivity);

        if (count($ActivityUserIDs) > 0) {
            $NewActivity['Data']['ActivityUserIDs'] = $ActivityUserIDs;
        }
        if ($ActivityUserCount) {
            $NewActivity['Data']['ActivityUserID_Count'] = $ActivityUserCount;
        }
        if (count($RecordIDs) > 0) {
            $NewActivity['Data']['RecordIDs'] = $RecordIDs;
        }
        if (isset($RegardingUserIDs) && count($RegardingUserIDs) > 0) {
            $NewActivity['Data']['RegardingUserIDs'] = $RegardingUserIDs;

            if ($RegardingUserCount) {
                $NewActivity['Data']['RegardingUserID_Count'] = $RegardingUserCount;
            }
        }

        return $NewActivity;
    }

    /**
     * Notify the user of wall comments.
     *
     * @param $WallPost
     */
    protected function notifyWallComment($Comment, $WallPost) {
        $NotifyUser = Gdn::userModel()->getID($WallPost['ActivityUserID']);

        $Activity = array(
            'ActivityType' => 'WallComment',
            'ActivityUserID' => $Comment['InsertUserID'],
            'Format' => $Comment['Format'],
            'NotifyUserID' => $WallPost['ActivityUserID'],
            'RecordType' => 'ActivityComment',
            'RecordID' => $Comment['ActivityCommentID'],
            'RegardingUserID' => $WallPost['ActivityUserID'],
            'Route' => userUrl($NotifyUser, ''),
            'Story' => $Comment['Body'],
            'HeadlineFormat' => t('HeadlineFormat.NotifyWallComment', '{ActivityUserID,User} commented on your <a href="{Url,url}">wall</a>.')
        );

        $this->save($Activity, 'WallComment');
    }

    /**
     *
     *
     * @param $WallPost
     */
    protected function notifyWallPost($WallPost) {
        $NotifyUser = Gdn::userModel()->getID($WallPost['ActivityUserID']);

        $Activity = array(
            'ActivityType' => 'WallPost',
            'ActivityUserID' => $WallPost['RegardingUserID'],
            'Format' => $WallPost['Format'],
            'NotifyUserID' => $WallPost['ActivityUserID'],
            'RecordType' => 'Activity',
            'RecordID' => $WallPost['ActivityID'],
            'RegardingUserID' => $WallPost['ActivityUserID'],
            'Route' => userUrl($NotifyUser, ''),
            'Story' => $WallPost['Story'],
            'HeadlineFormat' => t('HeadlineFormat.NotifyWallPost', '{ActivityUserID,User} posted on your <a href="{Url,url}">wall</a>.')
        );

        $this->save($Activity, 'WallComment');
    }

    /**
     *
     *
     * @return array
     */
    public function saveQueue() {
        $Result = array();
        foreach (self::$Queue as $UserID => $Activities) {
            foreach ($Activities as $ActivityType => $Row) {
                $Result[] = $this->save($Row[0], false, $Row[1]);
            }
        }
        self::$Queue = array();
        return $Result;
    }

    /**
     *
     *
     * @param $Data
     */
    protected function _touch(&$Data) {
        touchValue('ActivityType', $Data, 'Default');
        touchValue('ActivityUserID', $Data, Gdn::session()->UserID);
        touchValue('NotifyUserID', $Data, self::NOTIFY_PUBLIC);
        touchValue('Headline', $Data, null);
        touchValue('Story', $Data, null);
        touchValue('Notified', $Data, 0);
        touchValue('Emailed', $Data, 0);
        touchValue('Photo', $Data, null);
        touchValue('Route', $Data, null);
        if (!isset($Data['Data']) || !is_array($Data['Data'])) {
            $Data['Data'] = array();
        }
    }
}
