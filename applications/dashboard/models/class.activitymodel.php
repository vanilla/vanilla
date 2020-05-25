<?php
/**
 * Activity Model.
 *
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Garden\Schema\Schema;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Vanilla\Contracts\Formatting\FormatFieldInterface;
use Vanilla\Dashboard\Models\ActivityEmail;
use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Formatting\Formats;
use Vanilla\Formatting\FormatFieldTrait;
use Vanilla\Formatting\FormatService;
use Vanilla\Dashboard\Events\NotificationEvent;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\SchemaFactory;
use Vanilla\Utility\CamelCaseScheme;

/**
 * Activity data management.
 */
class ActivityModel extends Gdn_Model implements FormatFieldInterface {

    use \Vanilla\FloodControlTrait;

    use FormatFieldTrait;

    /** Maximum length of activity story excerpts. */
    private const DEFAULT_EXCERPT_LENGTH = 160;

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

    /** Activity status: The recipient was not eligible for an email notification. */
    const SENT_SKIPPED = 6;

    /** Activity status: Sending is in progress. */
    const SENT_INPROGRESS = 31;

    /** @var array|null Allowed activity types. */
    public static $ActivityTypes = null;

    /** @var array Activity to be saved. */
    public static $Queue = [];

    /** @var int Limit on number of activity to combine. */
    public static $MaxMergeCount = 10;

    /** @var ActivityEmail[] Emails pending sending. */
    private static $emailQueue = [];

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var Psr\Log\LoggerInterface */
    private $logger;

    /**
     * @var string The amount of time to delete logs after.
     */
    private $pruneAfter;

    /** @var FormatService */
    private $formatService;

    /**
     * Defines the related database table name.
     *
     * @param Gdn_Validation $validation The validation dependency.
     * @param LoggerInterface $logger
     * @param FormatService $formatService
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        Gdn_Validation $validation = null,
        ?LoggerInterface $logger = null,
        ?FormatService $formatService = null,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        parent::__construct('Activity', $validation);
        try {
            $this->setPruneAfter(c('Garden.PruneActivityAfter', '2 months'));
        } catch (Exception $ex) {
            $this->setPruneAfter('2 months');
        }
        $this->formatService = $formatService instanceof FormatService ?
            $formatService : Gdn::getContainer()->get(FormatService::class);
        $this->logger = $logger instanceof LoggerInterface ?
            $logger : Gdn::getContainer()->get(LoggerInterface::class);
        $this->eventDispatcher = $eventDispatcher instanceof EventDispatcherInterface ?
            $eventDispatcher : Gdn::getContainer()->get(EventDispatcherInterface::class);
    }

    /**
     * Build basis of common activity SQL query.
     *
     * @param bool $join
     * @since 2.0.0
     * @access public
     */
    public function activityQuery($join = true) {
        $this->SQL
            ->select('a.*')
            ->select('t.FullHeadline, t.ProfileHeadline, t.AllowComments, t.ShowIcon, t.RouteCode')
            ->select('t.Name', '', 'ActivityType')
            ->from('Activity a')
            ->join('ActivityType t', 'a.ActivityTypeID = t.ActivityTypeID');

        if ($join) {
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
     * Can the current user view the activity?
     *
     * @param array $activity
     * @return bool
     */
    public function canView(array $activity): bool {
        $result = false;

        $userid = val('NotifyUserID', $activity);
        switch ($userid) {
            case ActivityModel::NOTIFY_PUBLIC:
                $result = true;
                break;
            case ActivityModel::NOTIFY_MODS:
                if (checkPermission('Garden.Moderation.Manage')) {
                    $result = true;
                }
                break;
            case ActivityModel::NOTIFY_ADMINS:
                if (checkPermission('Garden.Settings.Manage')) {
                    $result = true;
                }
                break;
            default:
                // Actual userid.
                if (Gdn::session()->UserID === $userid || checkPermission('Garden.Community.Manage')) {
                    $result = true;
                }
                break;
        }

        return $result;
    }

    /**
     *
     *
     * @param $data
     */
    public function calculateData(&$data) {
        foreach ($data as &$row) {
            $this->calculateRow($row);
        }
    }

    /**
     *
     *
     * @param $row
     */
    public function calculateRow(&$row) {
        $activityType = self::getActivityType($row['ActivityTypeID']);
        $row['ActivityType'] = val('Name', $activityType);
        if (is_string($row['Data'])) {
            $row['Data'] = dbdecode($row['Data']);
        }

        $row['PhotoUrl'] = url($row['Route'], true);
        if (!$row['Photo']) {
            if (isset($row['ActivityPhoto'])) {
                $row['Photo'] = $row['ActivityPhoto'];
                $row['PhotoUrl'] = userUrl($row, 'Activity');
            } else {
                $user = Gdn::userModel()->getID($row['ActivityUserID'], DATASET_TYPE_ARRAY);
                if ($user) {
                    $photo = $user['Photo'];
                    $row['PhotoUrl'] = userUrl($user);
                    if (!$photo || stringBeginsWith($photo, 'http')) {
                        $row['Photo'] = $photo;
                    } else {
                        $row['Photo'] = Gdn_Upload::url(changeBasename($photo, 'n%s'));
                    }
                }
            }
        }

        $data = $row['Data'];
        if (isset($data['ActivityUserIDs'])) {
            $row['ActivityUserID'] = array_merge([$row['ActivityUserID']], $data['ActivityUserIDs']);
            $row['ActivityUserID_Count'] = val('ActivityUserID_Count', $data);
        }

        if (isset($data['RegardingUserIDs'])) {
            $row['RegardingUserID'] = array_merge([$row['RegardingUserID']], $data['RegardingUserIDs']);
            $row['RegardingUserID_Count'] = val('RegardingUserID_Count', $data);
        }


        if (!empty($row['Route'])) {
            $row['Url'] = externalUrl($row['Route']);
        } else {
            $id = $row['ActivityID'];
            $row['Url'] = Gdn::request()->url("/activity/item/$id", true);
        }

        if ($row['HeadlineFormat']) {
            $row['Headline'] = formatString($row['HeadlineFormat'], $row);
        } else {
            $row['Headline'] = Gdn_Format::activityHeadline($row);
        }
    }

    /**
     * Define a new activity type.
     * @param string $name The string code of the activity type.
     * @param array $activity The data that goes in the ActivityType table.
     * @since 2.1
     */
    public function defineType($name, $activity = []) {
        $this->SQL->replace('ActivityType', $activity, ['Name' => $name], true);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($where = [], $options = []) {
        if (is_numeric($where)) {
            deprecated('ActivityModel->delete(int)', 'ActivityModel->deleteID(int)');

            $result = $this->deleteID($where, $options);
            return $result;
        } elseif (count($where) === 1 && isset($where['ActivityID'])) {
            return parent::delete($where, $options);
        }

        throw new \BadMethodCallException("ActivityModel->delete() is not supported.", 400);
    }

    /**
     * Delete a particular activity item.
     *
     * @param int $activityID The unique ID of activity to be deleted.
     * @param array $options Not used.
     * @return bool Returns **true** if the activity was deleted or **false** otherwise.
     */
    public function deleteID($activityID, $options = []) {
        // Get the activity first.
        $activity = $this->getID($activityID);
        if ($activity) {
            // Log the deletion.
            $log = val('Log', $options);
            if ($log) {
                LogModel::insert($log, 'Activity', $activity);
            }

            // Delete comments on the activity item
            $this->SQL->delete('ActivityComment', ['ActivityID' => $activityID]);

            // Delete the activity item
            return parent::deleteID($activityID);
        } else {
            return false;
        }
    }

    /**
     * Delete an activity comment.
     *
     * @since 2.1
     *
     * @param int $iD
     * @return Gdn_DataSet
     */
    public function deleteComment($iD) {
        return $this->SQL->delete('ActivityComment', ['ActivityCommentID' => $iD]);
    }

    /**
     * Get the recent activities.
     *
     * @param array $where
     * @param int $limit
     * @param int $offset
     * @return Gdn_DataSet
     */
    public function getWhereRecent($where, $limit = 0, $offset = 0) {
        $result = $this->getWhere($where, '', '', $limit, $offset);
        return $result;
    }

    /**
     * Modifies standard Gdn_Model->GetWhere to use AcitivityQuery.
     *
     * Events: AfterGet.
     *
     * @param array $where A filter suitable for passing to Gdn_SQLDriver::where().
     * @param string $orderFields A comma delimited string to order the data.
     * @param string $orderDirection One of **asc** or **desc**.
     * @param int|bool $limit The database limit.
     * @param int|bool $offset The database offset.
     * @return Gdn_DataSet SQL results.
     */
    public function getWhere($where = [], $orderFields = '', $orderDirection = '', $limit = false, $offset = false) {
        if (is_string($where)) {
            deprecated('ActivityModel->getWhere($key, $value)', 'ActivityModel->getWhere([$key => $value])');
            $where = [$where => $orderFields];
            $orderFields = '';
        }
        if (is_numeric($orderFields)) {
            deprecated('ActivityModel->getWhere($where, $limit)');
            $limit = $orderFields;
            $orderFields = '';
        }
        if (is_numeric($orderDirection)) {
            deprecated('ActivityModel->getWhere($where, $limit, $offset)');
            $offset = $orderDirection;
            $orderDirection = '';
        }
        $limit = $limit ?: 30;
        $offset = $offset ?: 0;

        $orderFields = $orderFields ?: 'a.DateUpdated';
        $orderDirection = $orderDirection ?: 'desc';

        // Add the basic activity query.
        $this->SQL
            ->select('a2.*')
            ->select('t.FullHeadline, t.ProfileHeadline, t.AllowComments, t.ShowIcon, t.RouteCode')
            ->select('t.Name', '', 'ActivityType')
            ->from('Activity a')
            ->join('Activity a2', 'a.ActivityID = a2.ActivityID')// self-join for index speed.
            ->join('ActivityType t', 'a2.ActivityTypeID = t.ActivityTypeID');

        // Add prefixes to the where.
        foreach ($where as $key => $value) {
            if (strpos($key, '.') === false) {
                $where['a.'.$key] = $value;
                unset($where[$key]);
            }
        }

        $result = $this->SQL
            ->where($where)
            ->orderBy($orderFields, $orderDirection)
            ->limit($limit, $offset)
            ->get();

        self::getUsers($result->resultArray());
        Gdn::userModel()->joinUsers(
            $result->resultArray(),
            ['ActivityUserID', 'RegardingUserID'],
            ['Join' => ['Name', 'Email', 'Gender', 'Photo']]
        );
        $this->calculateData($result->resultArray());

        $this->EventArguments['Data'] =& $result;
        $this->fireEvent('AfterGet');

        return $result;
    }

    /**
     *
     *
     * @param array &$activities
     * @since 2.1
     */
    public function joinComments(&$activities) {
        // Grab all of the activity IDs.
        $activityIDs = [];
        foreach ($activities as $activity) {
            if ($iD = val('CommentActivityID', $activity['Data'])) {
                // This activity shares its comments with another activity.
                $activityIDs[] = $iD;
            } else {
                $activityIDs[] = $activity['ActivityID'];
            }
        }
        $activityIDs = array_unique($activityIDs);

        $comments = $this->getComments($activityIDs);
        $comments = Gdn_DataSet::index($comments, ['ActivityID'], ['Unique' => false]);
        foreach ($activities as &$activity) {
            $iD = val('CommentActivityID', $activity['Data']);
            if (!$iD) {
                $iD = $activity['ActivityID'];
            }

            if (isset($comments[$iD])) {
                $activity['Comments'] = $comments[$iD];
            } else {
                $activity['Comments'] = [];
            }
        }
    }

    /**
     * Modifies standard Gdn_Model->Get to use AcitivityQuery.
     *
     * Events: BeforeGet, AfterGet.
     *
     * @param int|bool $notifyUserID Unique ID of user to gather activity for or one of the NOTIFY_* constants in this class.
     * @param int $offset Number to skip.
     * @param int $limit How many to return.
     * @return Gdn_DataSet SQL results.
     */
    public function getByUser($notifyUserID = false, $offset = 0, $limit = 30) {
        $offset = is_numeric($offset) ? $offset : 0;
        if ($offset < 0) {
            $offset = 0;
        }

        $limit = is_numeric($limit) ? $limit : 0;
        if ($limit < 0) {
            $limit = 30;
        }

        $this->activityQuery(false);

        if ($notifyUserID === false || $notifyUserID === 0) {
            $notifyUserID = self::NOTIFY_PUBLIC;
        }
        $this->SQL->whereIn('NotifyUserID', (array)$notifyUserID);

        $this->fireEvent('BeforeGet');
        $result = $this->SQL
            ->orderBy('a.ActivityID', 'desc')
            ->limit($limit, $offset)
            ->get();

        Gdn::userModel()->joinUsers($result, ['ActivityUserID', 'RegardingUserID'], ['Join' => ['Name', 'Photo', 'Email', 'Gender']]);

        $this->EventArguments['Data'] =& $result;
        $this->fireEvent('AfterGet');

        return $result;
    }

    /**
     *
     *
     * @param array &$data
     */
    public static function getUsers(&$data) {
        $userIDs = [];

        foreach ($data as &$row) {
            if (is_string($row['Data'])) {
                $row['Data'] = dbdecode($row['Data']);
            }

            $userIDs[$row['ActivityUserID']] = 1;
            $userIDs[$row['RegardingUserID']] = 1;

            if (isset($row['Data']['ActivityUserIDs'])) {
                foreach ($row['Data']['ActivityUserIDs'] as $userID) {
                    $userIDs[$userID] = 1;
                }
            }

            if (isset($row['Data']['RegardingUserIDs'])) {
                foreach ($row['Data']['RegardingUserIDs'] as $userID) {
                    $userIDs[$userID] = 1;
                }
            }
        }

        Gdn::userModel()->getIDs(array_keys($userIDs));
    }

    /**
     *
     *
     * @param $activityType
     * @return bool
     */
    public static function getActivityType($activityType) {
        if (self::$ActivityTypes === null) {
            $data = Gdn::sql()->get('ActivityType')->resultArray();
            foreach ($data as $row) {
                self::$ActivityTypes[$row['Name']] = $row;
                self::$ActivityTypes[$row['ActivityTypeID']] = $row;
            }
        }
        if (isset(self::$ActivityTypes[$activityType])) {
            return self::$ActivityTypes[$activityType];
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
     * @param string $userID Unique ID of user.
     * @return int Number of activity items found.
     */
    public function getCount($userID = '') {
        $this->SQL
            ->select('a.ActivityID', 'count', 'ActivityCount')
            ->from('Activity a')
            ->join('ActivityType t', 'a.ActivityTypeID = t.ActivityTypeID');

        if ($userID != '') {
            $this->SQL
                ->beginWhereGroup()
                ->where('a.ActivityUserID', $userID)
                ->orWhere('a.RegardingUserID', $userID)
                ->endWhereGroup();
        }

        $session = Gdn::session();
        if (!$session->isValid() || $session->UserID != $userID) {
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
     * @param string $roleID Unique ID of role.
     * @param int $offset Number to skip.
     * @param int $limit Max number to return.
     * @return Gdn_DataSet SQL results.
     * @since 2.0.18
     */
    public function getForRole($roleID = '', $offset = 0, $limit = 50) {
        if (!is_array($roleID)) {
            $roleID = [$roleID];
        }

        $offset = is_numeric($offset) ? $offset : 0;
        if ($offset < 0) {
            $offset = 0;
        }

        $limit = is_numeric($limit) ? $limit : 0;
        if ($limit < 0) {
            $limit = 0;
        }

        $this->activityQuery();
        $result = $this->SQL
            ->join('UserRole ur', 'a.ActivityUserID = ur.UserID')
            ->whereIn('ur.RoleID', $roleID)
            ->where('t.Public', '1')
            ->orderBy('a.DateInserted', 'desc')
            ->limit($limit, $offset)
            ->get();

        $this->EventArguments['Data'] =& $result;
        $this->fireEvent('AfterGet');

        return $result;
    }

    /**
     * Get number of activity related to a particular role.
     *
     * @since 2.0.18
     * @access public
     * @param int|string $roleID Unique ID of role.
     * @return int Number of activity items.
     */
    public function getCountForRole($roleID = '') {
        if (!is_array($roleID)) {
            $roleID = [$roleID];
        }

        return $this->SQL
            ->select('a.ActivityID', 'count', 'ActivityCount')
            ->from('Activity a')
            ->join('ActivityType t', 'a.ActivityTypeID = t.ActivityTypeID')
            ->join('UserRole ur', 'a.ActivityUserID = ur.UserID')
            ->whereIn('ur.RoleID', $roleID)
            ->where('t.Public', '1')
            ->get()
            ->firstRow()
            ->ActivityCount;
    }

    /**
     * Get a particular activity record.
     *
     * @param int $activityID Unique ID of activity item.
     * @param bool|string $dataSetType The format of the resulting data.
     * @param array $options Not used.
     * @return array|object A single SQL result.
     */
    public function getID($activityID, $dataSetType = false, $options = []) {
        $activity = parent::getID($activityID, $dataSetType);
        if ($activity) {
            $this->calculateRow($activity);
            $activities = [$activity];
            self::joinUsers($activities);
            $activity = array_pop($activities);
        }

        return $activity;
    }

    /**
     * Get notifications for a user.
     *
     * Events: BeforeGetNotifications.
     *
     * @param int $notifyUserID Unique ID of user.
     * @param int $offset Number to skip.
     * @param int $limit Max number to return.
     * @return Gdn_DataSet SQL results.
     * @since 2.0.0
     */
    public function getNotifications($notifyUserID, $offset = 0, $limit = 30) {
        $this->activityQuery(false);
        $this->fireEvent('BeforeGetNotifications');
        $result = $this->SQL
            ->where('NotifyUserID', $notifyUserID)
            ->limit($limit, $offset)
            ->orderBy('a.ActivityID', 'desc')
            ->get();
        $result->datasetType(DATASET_TYPE_ARRAY);

        self::getUsers($result->resultArray());
        Gdn::userModel()->joinUsers(
            $result->resultArray(),
            ['ActivityUserID', 'RegardingUserID'],
            ['Join' => ['Name', 'Photo', 'Email', 'Gender']]
        );
        $this->calculateData($result->resultArray());

        return $result;
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

        $notifyUserIds = [ActivityModel::NOTIFY_PUBLIC];
        if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            $notifyUserIds[] = ActivityModel::NOTIFY_MODS;
        }

        // Is this a wall post?
        if (!in_array(val('ActivityType', $activity), ['Status', 'WallPost']) || !in_array($notifyUserId, $notifyUserIds)) {
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
     * @param int $userID Unique ID of user.
     * @param int $lastActivityID ID of activity to start at.
     * @param array|string $filterToActivityTypeIDs Limits returned activity to particular types.
     * @param int $limit Max number to return.
     * @return Gdn_DataSet SQL results.
     * @since 2.0.18
     */
    public function getNotificationsSince($userID, $lastActivityID, $filterToActivityTypeIDs = '', $limit = 5) {
        $this->activityQuery();
        $this->fireEvent('BeforeGetNotificationsSince');
        if (is_array($filterToActivityTypeIDs)) {
            $this->SQL->whereIn('a.ActivityTypeID', $filterToActivityTypeIDs);
        } else {
            $this->SQL->where('t.Notify', '1');
        }

        $result = $this->SQL
            ->where('RegardingUserID', $userID)
            ->where('a.ActivityID >', $lastActivityID)
            ->limit($limit, 0)
            ->orderBy('a.ActivityID', 'desc')
            ->get();

        return $result;
    }

    /**
     * @param int $iD
     * @return array|false
     */
    public function getComment($iD) {
        $activity = $this->SQL->getWhere('ActivityComment', ['ActivityCommentID' => $iD])->resultArray();
        if ($activity) {
            Gdn::userModel()->joinUsers($activity, ['InsertUserID'], ['Join' => ['Name', 'Photo', 'Email']]);
            return array_shift($activity);
        }
        return false;
    }

    /**
     * Get comments related to designated activity items.
     *
     * Events: BeforeGetComments.
     *
     * @param array $activityIDs IDs of activity items.
     * @return Gdn_DataSet SQL results.
     */
    public function getComments($activityIDs) {
        $result = $this->SQL
            ->select('c.*')
            ->from('ActivityComment c')
            ->whereIn('c.ActivityID', $activityIDs)
            ->orderBy('c.ActivityID, c.DateInserted')
            ->get()->resultArray();
        Gdn::userModel()->joinUsers($result, ['InsertUserID'], ['Join' => ['Name', 'Photo', 'Email']]);
        return $result;
    }

    /**
     * Add a new activity item.
     *
     * Getting reworked for 2.1 so I'm cheating and skipping params for now. -mlr
     *
     * @param int $activityUserID
     * @param string $activityType
     * @param string $story
     * @param int|null $regardingUserID
     * @param int $commentActivityID
     * @param string $route
     * @param string|bool $sendEmail
     * @return int ActivityID of item created.
     */
    public function add($activityUserID, $activityType, $story = null, $regardingUserID = null, $commentActivityID = null, $route = null, $sendEmail = '') {
        // Get the ActivityTypeID & see if this is a notification.
        $activityTypeRow = self::getActivityType($activityType);
        $notify = val('Notify', $activityTypeRow, false);

        if ($activityTypeRow === false) {
            trigger_error(
                errorMessage(sprintf('Activity type could not be found: %s', $activityType), 'ActivityModel', 'Add'),
                E_USER_ERROR
            );
        }

        $activity = [
            'ActivityUserID' => $activityUserID,
            'ActivityType' => $activityType,
            'Story' => $story,
            'RegardingUserID' => $regardingUserID,
            'Route' => $route
        ];


        // Massage $SendEmail to allow for only sending an email.
        if ($sendEmail === 'Only') {
            $sendEmail = '';
        } elseif ($sendEmail === 'QueueOnly') {
            $sendEmail = '';
            $notify = true;
        }

        // If $SendEmail was FALSE or TRUE, let it override the $Notify setting.
        if ($sendEmail === false || $sendEmail === true) {
            $notify = $sendEmail;
        }

        $preference = false;
        if (($activityTypeRow['Notify'] || !$activityTypeRow['Public']) && !empty($regardingUserID)) {
            $activity['NotifyUserID'] = $activity['RegardingUserID'];
            $preference = $activityType;
        } else {
            $activity['NotifyUserID'] = self::NOTIFY_PUBLIC;
        }

        // Otherwise let the decision to email lie with the $Notify setting.
        if ($sendEmail === 'Force' || $notify) {
            $activity['Emailed'] = self::SENT_PENDING;
        } elseif ($notify) {
            $activity['Emailed'] = self::SENT_PENDING;
        } elseif ($sendEmail === false) {
            $activity['Emailed'] = self::SENT_ARCHIVE;
        }

        $activity = $this->save($activity, $preference);

        return val('ActivityID', $activity);
    }

    /**
     * Join the users to the activities.
     *
     * @param array|Gdn_DataSet &$activities The activities to join.
     */
    public static function joinUsers(&$activities) {
        Gdn::userModel()->joinUsers(
            $activities,
            ['ActivityUserID', 'RegardingUserID'],
            ['Join' => ['Name', 'Email', 'Gender', 'Photo']]
        );
    }

    /**
     * Get default notification preference for an activity type.
     *
     * @since 2.0.0
     * @access public
     * @param string $activityType
     * @param array $preferences
     * @param string $type One of the following:
     *  - Popup: Popup a notification.
     *  - Email: Email the notification.
     *  - NULL: True if either notification is true.
     *  - both: Return an array of (Popup, Email).
     * @return bool|bool[]
     */
    public static function notificationPreference($activityType, $preferences, $type = null) {
        if (is_numeric($preferences)) {
            $user = Gdn::userModel()->getID($preferences);
            if (!$user) {
                return $type == 'both' ? [false, false] : false;
            }
            $preferences = val('Preferences', $user);
        }

        if ($type === null) {
            $result = self::notificationPreference($activityType, $preferences, 'Email')
                || self::notificationPreference($activityType, $preferences, 'Popup');

            return $result;
        } elseif ($type === 'both') {
            $result = [
                self::notificationPreference($activityType, $preferences, 'Popup'),
                self::notificationPreference($activityType, $preferences, 'Email')
            ];
            return $result;
        }

        $configPreference = c("Preferences.$type.$activityType", '0');
        if ((int)$configPreference === 2) {
            $preference = true; // This preference is forced on.
        } elseif ($configPreference !== false) {
            $preference = val($type.'.'.$activityType, $preferences, $configPreference);
        } else {
            $preference = false;
        }

        return $preference;
    }

    /**
     * Send notification.
     *
     * @since 2.0.17
     * @access public
     * @param int $ActivityID
     * @param array|string $Story
     * @param bool $Force
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
                $Preference = val('Email.'.$Activity->ActivityType, $Preferences, Gdn::config('Preferences.Email.'.$Activity->ActivityType));
            }
            if ($Preference) {
                $ActivityHeadline = Gdn_Format::text(Gdn_Format::activityHeadline($Activity, $Activity->ActivityUserID, $Activity->RegardingUserID), false);
                $Email = new Gdn_Email();
                $Email->subject(sprintf(t('[%1$s] %2$s'), Gdn::config('Garden.Title'), $ActivityHeadline));
                $Email->to($User);

                $url = externalUrl(val('Route', $Activity) == '' ? '/' : val('Route', $Activity));
                $emailTemplate = $Email->getEmailTemplate()
                    ->setButton($url, val('ActionText', $Activity, t('Check it out')))
                    ->setTitle($ActivityHeadline);

                if ($message = $this->getEmailMessage($Activity)) {
                    $emailTemplate->setMessage($message, true);
                }

                $Email->setEmailTemplate($emailTemplate);

                $Notification = ['ActivityID' => $ActivityID, 'User' => $User, 'Email' => $Email, 'Route' => $Activity->Route, 'Story' => $Story, 'Headline' => $ActivityHeadline, 'Activity' => $Activity];
                $this->EventArguments = $Notification;
                $this->fireEvent('BeforeSendNotification');
                try {
                    // Only send if the user is not banned
                    if (!val('Banned', $User)) {
                        $Email->send();
                        $Emailed = self::SENT_OK;
                    } else {
                        $Emailed = self::SENT_SKIPPED;
                    }
                } catch (phpmailerException $pex) {
                    if ($pex->getCode() == PHPMailer::STOP_CRITICAL && !$Email->PhpMailer->isServerError($pex)) {
                        $Emailed = self::SENT_FAIL;
                    } else {
                        $Emailed = self::SENT_ERROR;
                    }
                } catch (Exception $ex) {
                    switch ($ex->getCode()) {
                        case Gdn_Email::ERR_SKIPPED:
                            $Emailed = self::SENT_SKIPPED;
                            break;
                        default:
                            $Emailed = self::SENT_FAIL; // similar to http 5xx
                    }
                }
                try {
                    $this->SQL->put('Activity', ['Emailed' => $Emailed], ['ActivityID' => $ActivityID]);
                } catch (Exception $Ex) {
                    // We don't want a noisy error in a behind-the-scenes notification.
                }
            }
        }
    }


    /**
     * Takes an array representing an activity and builds the email message based on the activity's story and
     * the contents of the global config Garden.Email.Prefix.
     *
     * @param array|object $activity The activity to build the email for.
     * @return string The email message.
     */
    private function getEmailMessage($activity) {
        $message = '';

        if ($prefix = c('Garden.Email.Prefix', '')) {
            $message = $prefix;
        }

        $isArray = is_array($activity);

        $story = $isArray ? $activity['Story'] ?? null : $activity->Story ?? null;
        $format = $isArray ? $activity['Format'] ?? null : $activity->Format ?? null;

        if ($story && $format) {
            $message .= Gdn_Format::to($story, $format);
        }

        return $message;
    }

    /**
     *
     *
     * @param $activity
     * @param array $options Options to modify the behavior of the emailing.
     *
     * - **NoDelete**: Don't delete an email-only activity once the email is sent.
     * - **EmailSubject**: A custom subject for the email.
     * @return bool
     * @throws Exception
     */
    public function email(&$activity, $options = []) {
        // The $options parameter used to be $noDelete bool, this is the backwards compat.
        if (is_bool($options)) {
            $options = ['NoDelete' => $options];
        }
        $options += [
            'NoDelete' => false,
            'EmailSubject' => '',
        ];

        if (is_numeric($activity)) {
            $activityID = $activity;
            $activity = $this->getID($activityID);
        } else {
            $activityID = val('ActivityID', $activity);
        }

        if (!$activity) {
            return false;
        }

        $activity = (array)$activity;

        $user = Gdn::userModel()->getID($activity['NotifyUserID'], DATASET_TYPE_ARRAY);
        if (!$user) {
            return false;
        }

        $activity["Headline"] = $this->getActivityHeadline($activity, $user);

        // Build the email to send.
        $email = new Gdn_Email();
        $email->subject($this->getEmailSubjectFormatted($activity, $options));
        $email->to($user);

        $url = externalUrl(val('Route', $activity) == '' ? '/' : val('Route', $activity));

        $emailTemplate = $email->getEmailTemplate()
            ->setButton($url, val('ActionText', $activity, t('Check it out')))
            ->setTitle($this->getEmailSubject($activity, $options));

        if ($message = $this->getEmailMessage($activity)) {
            $emailTemplate->setMessage($message, true);
        }

        $email->setEmailTemplate($emailTemplate);

        // Fire an event for the notification.
        $notification = ['ActivityID' => $activityID, 'User' => $user, 'Email' => $email, 'Route' => $activity['Route'], 'Story' => $activity['Story'], 'Headline' => $activity['Headline'], 'Activity' => $activity];
        $this->EventArguments = $notification;
        $this->fireEvent('BeforeSendNotification');

        // Only send if the user is not banned
        if (!val('Banned', $user)) {
            $activity['Emailed'] = $this->sendEmail($email, $activity, $options);
            if ($activity['Emailed'] === self::SENT_OK) {
                // Delete the activity now that it has been emailed.
                if (!$options['NoDelete'] && !$activity['Notified']) {
                    if (val('ActivityID', $activity)) {
                        $this->delete($activity['ActivityID']);
                    } else {
                        $activity['_Delete'] = true;
                    }
                }
            }
        } else {
            $activity['Emailed'] = self::SENT_SKIPPED;
        }

        if ($activityID) {
            // Save the emailed flag back to the activity.
            $this->SQL->put('Activity', ['Emailed' => $emailed], ['ActivityID' => $activityID]);
        }
        return true;
    }

    /**
     * Given an activity, generate a fully-formatted headline.
     *
     * @param array $activity
     * @return string
     */
    private function getActivityHeadline(array $activity, array $user): string {
        // Format the activity headline based on the user being emailed.
        if (val('HeadlineFormat', $activity)) {
            $sessionUserID = Gdn::session()->UserID;
            Gdn::session()->UserID = $user['UserID'];
            $result = formatString($activity['HeadlineFormat'], $activity);
            Gdn::session()->UserID = $sessionUserID;
        } else {
            if (!isset($activity['ActivityGender'])) {
                $aT = self::getActivityType($activity['ActivityType']);

                $data = [$activity];
                self::joinUsers($data);
                $activity = $data[0];
                $activity['RouteCode'] = val('RouteCode', $aT);
                $activity['FullHeadline'] = val('FullHeadline', $aT);
                $activity['ProfileHeadline'] = val('ProfileHeadline', $aT);
            }

            $result = Gdn_Format::activityHeadline($activity, '', $user['UserID']);
        }

        return $result;
    }

    /**
     * Get the unformatted subject line for an activity email.
     *
     * @param array $activity
     * @param array $options
     * @return string
     */
    private function getEmailSubject(array $activity, array $options): string {
        $emailSubject = $options["EmailSubject"] ?? null;
        $headline = $this->formatService->renderPlainText($activity["Headline"], Formats\HtmlFormat::FORMAT_KEY);
        if ($emailSubject) {
            $emailSubject = $headline.' in '.$emailSubject;
        }
        $result = $emailSubject ?: $headline;
        return $result;
    }

    /**
     * Get the subject line for an activity email, formatted per the activity data.
     *
     * @param array $activity
     * @param array $options
     * @return string
     */
    private function getEmailSubjectFormatted(array $activity, array $options): string {
        $subject = $this->getEmailSubject($activity, $options);
        $result = sprintf(
            t("[%1\$s] %2\$s"),
            c("Garden.Title"),
            $subject
        );
        return $result;
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
    private $_NotificationQueue = [];

    /**
     * Clear notification queue.
     *
     * @since 2.0.17
     * @access public
     */
    public function clearNotificationQueue() {
        unset($this->_NotificationQueue);
        $this->_NotificationQueue = [];
    }

    /**
     * Save a comment on an activity.
     *
     * @param array $comment
     * @return int|bool|string
     * @since 2.1
     */
    public function comment($comment) {
        $comment['InsertUserID'] = Gdn::session()->UserID;
        $comment['DateInserted'] = Gdn_Format::toDateTime();
        $comment['InsertIPAddress'] = ipEncode(Gdn::request()->ipAddress());

        $this->Validation->applyRule('ActivityID', 'Required');
        $this->Validation->applyRule('Body', 'Required');
        $this->Validation->applyRule('DateInserted', 'Required');
        $this->Validation->applyRule('InsertUserID', 'Required');

        $this->EventArguments['Comment'] = &$comment;
        $this->fireEvent('BeforeSaveComment');

        if ($this->validate($comment)) {
            $activity = $this->getID($comment['ActivityID'], DATASET_TYPE_ARRAY);

            $_ActivityID = $comment['ActivityID'];
            // Check to see if this is a shared activity/notification.
            if ($commentActivityID = val('CommentActivityID', $activity['Data'])) {
                Gdn::controller()->json('CommentActivityID', $commentActivityID);
                $comment['ActivityID'] = $commentActivityID;
            }

            $storageObject = FloodControlHelper::configure($this, 'Vanilla', 'ActivityComment');
            if ($this->checkUserSpamming(Gdn::session()->User->UserID, $storageObject)) {
                return false;
            }

            // Check for spam.
            $spam = SpamModel::isSpam('ActivityComment', $comment);
            if ($spam) {
                return SPAM;
            }

            // Check for approval
            $approvalRequired = checkRestriction('Vanilla.Approval.Require');
            if ($approvalRequired && !val('Verified', Gdn::session()->User)) {
                LogModel::insert('Pending', 'ActivityComment', $comment);
                return UNAPPROVED;
            }

            $iD = $this->SQL->insert('ActivityComment', $comment);

            if ($iD) {
                // Check to see if this comment bumps the activity.
                if ($activity && val('Bump', $activity['Data'])) {
                    $this->SQL->put('Activity', ['DateUpdated' => $comment['DateInserted']], ['ActivityID' => $activity['ActivityID']]);
                    if ($_ActivityID != $comment['ActivityID']) {
                        $this->SQL->put('Activity', ['DateUpdated' => $comment['DateInserted']], ['ActivityID' => $_ActivityID]);
                    }
                }

                // Send a notification to the original person.
                if (val('ActivityType', $activity) === 'WallPost') {
                    $this->notifyWallComment($comment, $activity);
                }
            }

            return $iD;
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
        foreach ($this->_NotificationQueue as $userID => $notifications) {
            if (is_array($notifications)) {
                // Only send out one notification per user.
                $notification = $notifications[0];

                /* @var Gdn_Email $Email */
                $email = $notification['Email'];

                if (is_object($email) && method_exists($email, 'send')) {
                    $this->EventArguments = $notification;
                    $this->fireEvent('BeforeSendNotification');

                    try {
                        // Only send if the user is not banned
                        $user = Gdn::userModel()->getID($userID);
                        if (!val('Banned', $user)) {
                            $email->send();
                            $emailed = self::SENT_OK;
                        } else {
                            $emailed = self::SENT_SKIPPED;
                        }
                    } catch (phpmailerException $pex) {
                        if ($pex->getCode() == PHPMailer::STOP_CRITICAL && !$email->PhpMailer->isServerError($pex)) {
                            $emailed = self::SENT_FAIL;
                        } else {
                            $emailed = self::SENT_ERROR;
                        }
                    } catch (Exception $ex) {
                        switch ($ex->getCode()) {
                            case Gdn_Email::ERR_SKIPPED:
                                $emailed = self::SENT_SKIPPED;
                                break;
                            default:
                                $emailed = self::SENT_FAIL;
                        }
                    }

                    try {
                        $this->SQL->put('Activity', ['Emailed' => $emailed], ['ActivityID' => $notification['ActivityID']]);
                    } catch (Exception $ex) {
                        // Ignore an exception in a behind-the-scenes notification.
                    }
                }
            }
        }

        // Clear out the queue
        unset($this->_NotificationQueue);
        $this->_NotificationQueue = [];
    }

    /**
     * Get total unread notifications for a user.
     *
     * @param integer $userID
     */
    public function getUserTotalUnread($userID) {
        $notifications = $this->SQL
            ->select("ActivityID", "count", "total")
            ->from($this->Name)
            ->where("NotifyUserID", $userID)
            ->where("Notified", self::SENT_PENDING)
            ->get()
            ->resultArray();
        if (!is_array($notifications) || !isset($notifications[0])) {
            return 0;
        }
        return $notifications[0]["total"] ?? 0;
    }

    /**
     * Get total notifications for a user.
     *
     * @param integer $notifyUser Enum: NOTIFY_PUBLIC, NOTIFY_MODS, NOTIFY_ADMINS
     * @return integer
     */
    public function getUserTotal(int $notifyUser): int {
        $notifications = $this->SQL
            ->select("ActivityID", "count", "total")
            ->from($this->Name)
            ->where("NotifyUserID", $notifyUser)
            ->get()
            ->resultArray();
        if (!is_array($notifications) || !isset($notifications[0])) {
            return 0;
        }
        return $notifications[0]["total"] ?? 0;
    }

    /**
     * Mark activities as notified
     *
     * @param array $activityIDs
     */
    public function setNotified($activityIDs) {
        if (!is_array($activityIDs) || count($activityIDs) == 0) {
            return;
        }

        $this->SQL->update('Activity')
            ->set('Notified', self::SENT_OK)
            ->whereIn('ActivityID', $activityIDs)
            ->put();
    }

    /**
     *
     *
     * @param $activity
     * @throws Exception
     */
    public function share(&$activity) {
        // Massage the event for the user.
        $this->EventArguments['RecordType'] = 'Activity';
        $this->EventArguments['Activity'] =& $activity;

        $this->fireEvent('Share');
    }

    /**
     * Queue a notification for sending.
     *
     * @since 2.0.17
     * @access public
     * @param int $activityID
     * @param string $story
     * @param string $position
     * @param bool $force
     */
    public function queueNotification($activityID, $story = '', $position = 'last', $force = false) {
        $activity = $this->getID($activityID);
        if (!is_object($activity)) {
            return;
        }

        $story = Gdn_Format::text($story == '' ? $activity->Story : $story, false);
        // If this is a comment on another activity, fudge the activity a bit so that everything appears properly.
        if (is_null($activity->RegardingUserID) && $activity->CommentActivityID > 0) {
            $commentActivity = $this->getID($activity->CommentActivityID);
            $activity->RegardingUserID = $commentActivity->RegardingUserID;
            $activity->Route = '/activity/item/'.$activity->CommentActivityID;
        }
        $user = Gdn::userModel()->getID($activity->RegardingUserID, DATASET_TYPE_OBJECT);

        if ($user) {
            if ($force) {
                $preference = $force;
            } else {
                $configPreference = c('Preferences.Email.'.$activity->ActivityType, '0');
                if ($configPreference !== false) {
                    $preference = val('Email.'.$activity->ActivityType, $user->Preferences, $configPreference);
                } else {
                    $preference = false;
                }
            }

            if ($preference) {
                $activityHeadline = Gdn_Format::text(Gdn_Format::activityHeadline($activity, $activity->ActivityUserID, $activity->RegardingUserID), false);
                $email = new Gdn_Email();
                $email->subject(sprintf(t('[%1$s] %2$s'), Gdn::config('Garden.Title'), $activityHeadline));
                $email->to($user);
                $url = externalUrl(val('Route', $activity) == '' ? '/' : val('Route', $activity));

                $emailTemplate = $email->getEmailTemplate()
                    ->setButton($url, val('ActionText', $activity, t('Check it out')))
                    ->setTitle(Gdn_Format::plainText(val('Headline', $activity)));

                if ($message = $this->getEmailMessage($activity)) {
                    $emailTemplate->setMessage($message, true);
                }

                $email->setEmailTemplate($emailTemplate);

                if (!array_key_exists($user->UserID, $this->_NotificationQueue)) {
                    $this->_NotificationQueue[$user->UserID] = [];
                }

                $notification = ['ActivityID' => $activityID, 'User' => $user, 'Email' => $email, 'Route' => $activity->Route, 'Story' => $story, 'Headline' => $activityHeadline, 'Activity' => $activity];
                if ($position == 'first') {
                    $this->_NotificationQueue[$user->UserID] = array_merge([$notification], $this->_NotificationQueue[$user->UserID]);
                } else {
                    $this->_NotificationQueue[$user->UserID][] = $notification;
                }
            }
        }
    }

    /**
     * Queue an activity for saving later.
     *
     * @param array $data The data in the activity.
     * @param string|bool $preference The name of the preference governing the activity.
     * @param array $options Additional options for saving.
     * @throws Exception
     */
    public function queue($data, $preference = false, $options = []) {
        $this->_touch($data);
        if (!isset($data['NotifyUserID']) || !isset($data['ActivityType'])) {
            throw new Exception('Data missing NotifyUserID and/or ActivityType', 400);
        }

        if ($data['ActivityUserID'] == $data['NotifyUserID'] && !val('Force', $options)) {
            return; // don't notify users of something they did.
        }
        $notified = $data['Notified'];
        $emailed = $data['Emailed'];

        if (isset(self::$Queue[$data['NotifyUserID']][$data['ActivityType']])) {
            list($currentData, $currentOptions) = self::$Queue[$data['NotifyUserID']][$data['ActivityType']];

            $notified = $notified ? $notified : $currentData['Notified'];
            $emailed = $emailed ? $emailed : $currentData['Emailed'];

            $reason = null;
            if (isset($currentData['Data']['Reason']) && isset($data['Data']['Reason'])) {
                $reason = array_merge((array)$currentData['Data']['Reason'], (array)$data['Data']['Reason']);
                $reason = array_unique($reason);
            }

            $data = array_merge($currentData, $data);
            $options = array_merge($currentOptions, $options);
            if ($reason) {
                $data['Data']['Reason'] = $reason;
            }
        }

        $this->EventArguments['Preference'] = $preference;
        $this->EventArguments['Options'] = $options;
        $this->EventArguments['Data'] = &$data;
        $this->fireEvent('BeforeCheckPreference');
        if (!empty($preference)) {
            list($popup, $email) = self::notificationPreference($preference, $data['NotifyUserID'], 'both');
            if (!$popup && !$email) {
                return; // don't queue if user doesn't want to be notified at all.
            }
            if ($popup) {
                $notified = self::SENT_PENDING;
            }
            if ($email) {
                $emailed = self::SENT_PENDING;
            }
        }
        $data['Notified'] = $notified;
        $data['Emailed'] = $emailed;

        self::$Queue[$data['NotifyUserID']][$data['ActivityType']] = [$data, $options];
    }

    /**
     * Set the story for an activity to an excerpt of the original.
     *
     * @param array $activity
     * @param int $length
     * @return array
     */
    public function setStoryExcerpt(array $activity, int $length = self::DEFAULT_EXCERPT_LENGTH): array {
        if (!isset($activity["Story"]) || !isset($activity["Format"])) {
            return $activity;
        }

        $excerpt = Gdn::formatService()->renderExcerpt($activity["Story"], $activity["Format"]);
        $excerpt = sliceString(rtrim($excerpt, "…"), $length, "…");

        $activity["Story"] = $excerpt;
        $activity["Format"] = TextFormat::FORMAT_KEY;
        return $activity;
    }

    /**
     * Save an activity.
     *
     * @param array $data
     * @param bool $preference
     * @param array $options
     * @return array|bool|string|null
     * @throws Exception
     */
    public function save($data, $preference = false, $options = []) {
        trace('ActivityModel->save()');
        $activity = $data;
        $this->_touch($activity);
        $queueEmail = $options["QueueEmail"] ?? false;

        $extraFields = $data["Ext"] ?? [];
        unset($data["Ext"]);
        $emailFields = $extraFields["Email"] ?? [];

        if ($activity['ActivityUserID'] == $activity['NotifyUserID'] && !val('Force', $options)) {
            trace('Skipping activity because it would notify the user of something they did.');

            return null; // don't notify users of something they did.
        }

        // Check the user's preference.
        if ($preference) {
            list($popup, $email) = self::notificationPreference($preference, $activity['NotifyUserID'], 'both');

            if ($popup && !$activity['Notified']) {
                $activity['Notified'] = self::SENT_PENDING;
            }
            if ($email && !$activity['Emailed']) {
                $activity['Emailed'] = self::SENT_PENDING;
            }

            if (!$activity['Notified'] && !$activity['Emailed'] && !val('Force', $options)) {
                trace("Skipping activity because the user has no preference set.");
                return null;
            }
        }

        $activityType = self::getActivityType($activity['ActivityType']);
        $activityTypeID = val('ActivityTypeID', $activityType);
        if (!$activityTypeID) {
            trace("There is no $activityType activity type.", TRACE_WARNING);
            $activityType = self::getActivityType('Default');
            $activityTypeID = val('ActivityTypeID', $activityType);
        }

        $activity['ActivityTypeID'] = $activityTypeID;

        $notificationInc = 0;
        if ($activity['NotifyUserID'] > 0 && $activity['Notified']) {
            $notificationInc = 1;
        }

        // Check to see if we are sharing this activity with another one.
        if ($commentActivityID = val('CommentActivityID', $activity['Data'])) {
            $commentActivity = $this->getID($commentActivityID);
            $activity['Data']['CommentNotifyUserID'] = $commentActivity['NotifyUserID'];
        }

        // Make sure this activity isn't a duplicate.
        if (val('CheckRecord', $options)) {
            // Check to see if this record already notified so we don't notify multiple times.
            $where = arrayTranslate($activity, ['NotifyUserID', 'RecordType', 'RecordID']);
            $where['DateUpdated >'] = Gdn_Format::toDateTime(strtotime('-2 days')); // index hint

            $checkActivity = $this->SQL->getWhere(
                'Activity',
                $where
            )->firstRow();

            if ($checkActivity) {
                return false;
            }
        }

        // Check to share the activity.
        if (val('Share', $options)) {
            $this->share($activity);
        }

        // Group he activity.
        if ($groupBy = val('GroupBy', $options)) {
            $groupBy = (array)$groupBy;
            $where = [];
            foreach ($groupBy as $columnName) {
                $where[$columnName] = $activity[$columnName];
            }
            $where['NotifyUserID'] = $activity['NotifyUserID'];
            // Make sure to only group activities by day.
            $where['DateInserted >'] = Gdn_Format::toDateTime(strtotime('-1 day'));

            // See if there is another activity to group these into.
            $groupActivity = $this->SQL->getWhere(
                'Activity',
                $where
            )->firstRow(DATASET_TYPE_ARRAY);

            if ($groupActivity) {
                $groupActivity['Data'] = dbdecode($groupActivity['Data']);
                $activity = $this->mergeActivities($groupActivity, $activity);
                $notificationInc = 0;
            }
        }

        $delete = false;
        if ($activity['Emailed'] == self::SENT_PENDING && !$queueEmail) {
            $emailActivity = $emailFields + $activity;
            $this->email($emailActivity, $options);
            $delete = val('_Delete', $emailActivity);
            $activity["Emailed"] = $emailActivity["Emailed"];
        }

        $activityData = $activity['Data'];
        if (isset($activity['Data']) && is_array($activity['Data'])) {
            $activity['Data'] = dbencode($activity['Data']);
        }

        $this->defineSchema();
        $activity = $this->filterSchema($activity);

        $activityID = val('ActivityID', $activity);
        if (!$activityID) {
            if (!$delete) {
                if (!val('DisableFloodControl', $options)) {
                    $storageObject = FloodControlHelper::configure($this, 'Vanilla', 'Activity');
                    if ($this->checkUserSpamming(Gdn::session()->UserID, $storageObject)) {
                        return false;
                    }
                }

                $this->addInsertFields($activity);
                touchValue('DateUpdated', $activity, $activity['DateInserted']);

                $this->EventArguments['Activity'] =& $activity;
                $this->EventArguments['ActivityID'] = null;

                $handled = false;
                $this->EventArguments['Handled'] =& $handled;

                $this->fireEvent('BeforeSave');

                if (count($this->validationResults()) > 0) {
                    return false;
                }

                if ($handled) {
                    // A plugin handled this activity so don't save it.
                    return $activity;
                }

                if (val('CheckSpam', $options)) {
                    // Check for spam
                    $spam = SpamModel::isSpam('Activity', $activity);
                    if ($spam) {
                        return SPAM;
                    }

                    // Check for approval
                    $approvalRequired = checkRestriction('Vanilla.Approval.Require');
                    if ($approvalRequired && !val('Verified', Gdn::session()->User)) {
                        LogModel::insert('Pending', 'Activity', $activity);
                        return UNAPPROVED;
                    }
                }

                $activityID = $this->SQL->insert('Activity', $activity);
                $activity['ActivityID'] = $activityID;

                if ($activity["Notified"] === self::SENT_PENDING) {
                    $event = $this->eventFromRow($activity);
                    $this->eventDispatcher->dispatch($event);
                }
                if ($activity['Emailed'] == self::SENT_PENDING) {
                    $this->queueEmail($emailFields + $activity, $options);
                }

                $this->prune();
            }
        } else {
            $activity['DateUpdated'] = Gdn_Format::toDateTime();
            unset($activity['ActivityID']);

            $this->EventArguments['Activity'] =& $activity;
            $this->EventArguments['ActivityID'] = $activityID;
            $this->fireEvent('BeforeSave');

            if (count($this->validationResults()) > 0) {
                return false;
            }

            $this->SQL->put('Activity', $activity, ['ActivityID' => $activityID]);
            $activity['ActivityID'] = $activityID;
        }
        $activity['Data'] = $activityData;

        if (isset($commentActivity)) {
            $commentActivity['Data']['SharedActivityID'] = $activity['ActivityID'];
            $commentActivity['Data']['SharedNotifyUserID'] = $activity['NotifyUserID'];
            $this->setField($commentActivity['ActivityID'], 'Data', $commentActivity['Data']);
        }

        if ($notificationInc > 0) {
            $countNotifications = Gdn::userModel()->getID($activity['NotifyUserID'])->CountNotifications + $notificationInc;
            Gdn::userModel()->setField($activity['NotifyUserID'], 'CountNotifications', $countNotifications);
        }

        // If this is a wall post then we need to notify on that.
        if (val('Name', $activityType) == 'WallPost' && $activity['NotifyUserID'] == self::NOTIFY_PUBLIC) {
            $this->notifyWallPost($activity);
        }

        return $activity;
    }

    /**
     * Update a single activity's notification fields to reflect a read status.
     *
     * @param int $activityID
     */
    public function markSingleRead(int $activityID) {
        $this->SQL->put(
            "Activity",
            ["Notified" => self::SENT_OK, "Emailed" => self::SENT_OK],
            ["ActivityID" => $activityID]
        );
    }

    /**
     *
     *
     * @param $userID
     */
    public function markRead($userID) {
        // Mark all of a user's unread activities read.
        $this->SQL->put(
            'Activity',
            ['Notified' => self::SENT_OK],
            ['NotifyUserID' => $userID, 'Notified' => self::SENT_PENDING]
        );

        $user = Gdn::userModel()->getID($userID);
        if (val('CountNotifications', $user) != 0) {
            Gdn::userModel()->setField($userID, 'CountNotifications', 0);
        }
    }

    /**
     *
     *
     * @param $oldActivity
     * @param $newActivity
     * @param array $options
     * @return array
     */
    public function mergeActivities($oldActivity, $newActivity, $options = []) {
        // Group the two activities together.
        $activityUserIDs = val('ActivityUserIDs', $oldActivity['Data'], []);
        $activityUserCount = val('ActivityUserID_Count', $oldActivity['Data'], 0);
        array_unshift($activityUserIDs, $oldActivity['ActivityUserID']);
        if (($i = array_search($newActivity['ActivityUserID'], $activityUserIDs)) !== false) {
            unset($activityUserIDs[$i]);
            $activityUserIDs = array_values($activityUserIDs);
        }
        $activityUserIDs = array_unique($activityUserIDs);
        if (count($activityUserIDs) > self::$MaxMergeCount) {
            array_pop($activityUserIDs);
            $activityUserCount++;
        }

        $regardingUserCount = 0;
        if (val('RegardingUserID', $newActivity)) {
            $regardingUserIDs = val('RegardingUserIDs', $oldActivity['Data'], []);
            $regardingUserCount = val('RegardingUserID_Count', $oldActivity['Data'], 0);
            array_unshift($regardingUserIDs, $oldActivity['RegardingUserID']);
            if (($i = array_search($newActivity['RegardingUserID'], $regardingUserIDs)) !== false) {
                unset($regardingUserIDs[$i]);
                $regardingUserIDs = array_values($regardingUserIDs);
            }
            if (count($regardingUserIDs) > self::$MaxMergeCount) {
                array_pop($regardingUserIDs);
                $regardingUserCount++;
            }
        }

        $recordIDs = [];
        if ($oldActivity['RecordID']) {
            $recordIDs[] = $oldActivity['RecordID'];
        }
        $recordIDs = array_unique($recordIDs);

        $newActivity = array_merge($oldActivity, $newActivity);

        if (count($activityUserIDs) > 0) {
            $newActivity['Data']['ActivityUserIDs'] = $activityUserIDs;
        }
        if ($activityUserCount) {
            $newActivity['Data']['ActivityUserID_Count'] = $activityUserCount;
        }
        if (count($recordIDs) > 0) {
            $newActivity['Data']['RecordIDs'] = $recordIDs;
        }
        if (isset($regardingUserIDs) && count($regardingUserIDs) > 0) {
            $newActivity['Data']['RegardingUserIDs'] = $regardingUserIDs;

            if ($regardingUserCount) {
                $newActivity['Data']['RegardingUserID_Count'] = $regardingUserCount;
            }
        }

        return $newActivity;
    }

    /**
     * Notify the user of wall comments.
     *
     * @param array $comment
     * @param $wallPost
     */
    protected function notifyWallComment($comment, $wallPost) {
        $notifyUser = Gdn::userModel()->getID($wallPost['ActivityUserID']);

        $activity = [
            'ActivityType' => 'WallComment',
            'ActivityUserID' => $comment['InsertUserID'],
            'Format' => $comment['Format'],
            'NotifyUserID' => $wallPost['ActivityUserID'],
            'RecordType' => 'ActivityComment',
            'RecordID' => $comment['ActivityCommentID'],
            'RegardingUserID' => $wallPost['ActivityUserID'],
            'Route' => userUrl($notifyUser, ''),
            'Story' => $comment['Body'],
            'HeadlineFormat' => t('HeadlineFormat.NotifyWallComment', '{ActivityUserID,User} commented on your <a href="{Url,url}">wall</a>.')
        ];

        $this->save($activity, 'WallComment');
    }

    /**
     *
     *
     * @param $wallPost
     */
    protected function notifyWallPost($wallPost) {
        $notifyUser = Gdn::userModel()->getID($wallPost['ActivityUserID']);

        $activity = [
            'ActivityType' => 'WallPost',
            'ActivityUserID' => $wallPost['RegardingUserID'],
            'Format' => $wallPost['Format'],
            'NotifyUserID' => $wallPost['ActivityUserID'],
            'RecordType' => 'Activity',
            'RecordID' => $wallPost['ActivityID'],
            'RegardingUserID' => $wallPost['ActivityUserID'],
            'Route' => userUrl($notifyUser, ''),
            'Story' => $wallPost['Story'],
            'HeadlineFormat' => t('HeadlineFormat.NotifyWallPost', '{ActivityUserID,User} posted on your <a href="{Url,url}">wall</a>.')
        ];

        $this->save($activity, 'WallComment');
    }

    /**
     * Save all queued activity rows.
     *
     * @param bool $batchEmails Should emails be sent to multiple recipients when possible?
     * @return array
     */
    public function saveQueue(bool $batchEmails = false) {
        $result = [];
        $options = [
            "DisableFloodControl" => true,
            "QueueEmail" => $batchEmails,
        ];
        foreach (self::$Queue as $activities) {
            foreach ($activities as $row) {
                $result[] = $this->save($row[0], false, $options + $row[1]);
            }
        }
        self::$Queue = [];
        $this->sendEmailQueue();
        return $result;
    }

    /**
     *
     *
     * @param $data
     */
    protected function _touch(&$data) {
        touchValue('ActivityType', $data, 'Default');
        touchValue('ActivityUserID', $data, Gdn::session()->UserID);
        touchValue('NotifyUserID', $data, self::NOTIFY_PUBLIC);
        touchValue('Headline', $data, null);
        touchValue('Story', $data, null);
        touchValue('Notified', $data, 0);
        touchValue('Emailed', $data, 0);
        touchValue('Photo', $data, null);
        touchValue('Route', $data, null);
        if (!isset($data['Data']) || !is_array($data['Data'])) {
            $data['Data'] = [];
        }
    }

    /**
     * Get the delete after time.
     *
     * @return string Returns a string compatible with {@link strtotime()}.
     */
    public function getPruneAfter() {
        return $this->pruneAfter;
    }

    /**
     * Get the exact timestamp to prune.
     *
     * @return \DateTime|null Returns the date that we should prune after.
     */
    private function getPruneDate() {
        if (!$this->pruneAfter) {
            return null;
        } else {
            $tz = new \DateTimeZone('UTC');
            $now = new DateTime('now', $tz);
            $test = new DateTime($this->pruneAfter, $tz);

            $interval = $test->diff($now);

            if ($interval->invert === 1) {
                return $now->add($interval);
            } else {
                return $test;
            }
        }
    }

    /**
     * Set the prune after date.
     *
     * @param string $pruneAfter A string compatible with {@link strtotime()}. Be sure to specify a negative string.
     * @return ActivityModel Returns `$this` for fluent calls.
     */
    public function setPruneAfter($pruneAfter) {
        if ($pruneAfter) {
            // Make sure the string is negative.
            $now = time();
            $testTime = strtotime($pruneAfter, $now);
            if ($testTime === false) {
                throw new InvalidArgumentException('Invalid timespan value for "prune after".', 400);
            }
        }

        $this->pruneAfter = $pruneAfter;
        return $this;
    }

    /**
     * Prune old activities.
     */
    private function prune() {
        $date = $this->getPruneDate();

        $this->SQL->delete(
            'Activity',
            ['DateUpdated <' => Gdn_Format::toDateTime($date->getTimestamp())],
            10
        );
    }

    /**
     * Queue up an activity email notification.
     *
     * @param array $activity
     * @param array $options
     */
    private function queueEmail(array $activity, array $options = []) {
        $activityID = $activity["ActivityID"] ?? null;
        $notifyUserID = $activity["NotifyUserID"] ?? null;
        if (filter_var($activityID, FILTER_VALIDATE_INT) === false || filter_var($notifyUserID, FILTER_VALIDATE_INT) === false) {
            return;
        }

        $user = Gdn::userModel()->getID($notifyUserID, DATASET_TYPE_ARRAY);
        if (!is_array($user) || !array_key_exists("Email", $user)) {
            return;
        }

        if (!array_key_exists("Banned", $user) || $user["Banned"]) {
            return;
        }

        $address = $user["Email"] ?? null;
        if (!is_string($address) || empty($address)) {
            return;
        }

        $decodedData = false;
        if (array_key_exists("Data", $activity) && is_string($activity["Data"])) {
            $activity["Data"] = dbdecode($activity["Data"]);
            $decodedData = true;
        }
        $activity["Headline"] = $this->getActivityHeadline($activity, $user);
        $subject = $this->getEmailSubjectFormatted($activity, $options);
        $body = $this->getEmailMessage($activity);
        $key = implode(".", [
            $activity["ActivityTypeID"] ?? "",
            $activity["RecordID"] ?? "",
            $activity["RecordType"] ?? "",
            md5($subject),
            md5($body),
        ]);
        if ($decodedData === true && array_key_exists("Data", $activity) && is_array($activity["Data"])) {
            $activity["Data"] = dbencode($activity["Data"]);
        }

        if (!array_key_exists($key, static::$emailQueue) || !(static::$emailQueue[$key] instanceof ActivityEmail)) {
            $activityEmail = new ActivityEmail();
            $activityEmail->setBody($body);
            $activityEmail->setSubject($subject);
            $activityEmail->setActionText($activity["ActionText"] ?? t("Check it out"));
            $activityEmail->setActivityTypeID($activity["ActivityTypeID"] ?? null);
            $activityEmail->setInternalRoute($activity["Route"] ?? "");
            $activityEmail->setRecordID($activity["RecordID"] ?? null);
            $activityEmail->setRecordType($activity["RecordType"] ?? null);
            static::$emailQueue[$key] = $activityEmail;
        } else {
            $activityEmail = &static::$emailQueue[$key];
        }

        $activityEmail->addActivityID($activityID);
        $activityEmail->addRecipient($address, $user["Name"] ?? null);
    }

    /**
     * Send any queued emails.
     */
    private function sendEmailQueue() {
        foreach (static::$emailQueue as $activityEmail) {
            try {
                $this->sendActivityEmail($activityEmail);
            } catch (Exception $e) {
                $this->logger->error("An exception occurred while processing the notification email queue.", [
                    "event" => "activity_email_failed",
                    "exception" => $e,
                ]);
            }
        }
        static::$emailQueue = [];
    }

    /**
     * Invoke send on an instance of Gdn_Email and return its status.
     *
     * @param Gdn_Email $email
     * @return integer
     */
    private function sendEmail(Gdn_Email $email): int {
        // Send the email.
        try {
            $email->send();
            return self::SENT_OK;
        } catch (phpmailerException $pex) {
            $this->logger->error("A PHPMailer exception occurred while sending a notification.", [
                "event" => "activity_email_failed",
                "exception" => $pex,
            ]);
            if ($pex->getCode() == PHPMailer::STOP_CRITICAL && !$email->PhpMailer->isServerError($pex)) {
                return self::SENT_FAIL;
            } else {
                return self::SENT_ERROR;
            }
        } catch (Exception $ex) {
            $this->logger->error("An exception occurred while sending a notification.", [
                "event" => "activity_email_failed",
                "exception" => $ex,
            ]);
            switch ($ex->getCode()) {
                case Gdn_Email::ERR_SKIPPED:
                    return self::SENT_SKIPPED;
                default:
                    return self::SENT_FAIL; // similar to http 5xx
            }
        }
    }

    /**
     * Given an ActivityEmail instance, create and send the email it represents.
     *
     * @param ActivityEmail $activityEmail
     */
    private function sendActivityEmail(ActivityEmail $activityEmail) {
        $batchSize = c("Garden.Email.BatchSize", 50);
        $email = new Gdn_Email();
        $recipients = $activityEmail->getRecipients();

        $activityType = static::getActivityType($activityEmail->getActivityTypeID());
        $route = $activityEmail->getInternalRoute() ?? "/";
        $notification = [
            "Activity" => [
                "ActivityType" => $activityType["Name"] ?? null,
                "RecordID" => $activityEmail->getRecordID(),
                "RecordType" => $activityEmail->getRecordType(),
                "Route" => $route,
            ],
            "Email" => $email,
            "Route" => $route,
            "UserAuthorized" => true, // Let anything hooking in know we've already authorized user access to the resources.
        ];

        $batchOffset = 0;
        while ($batch = array_slice($recipients, $batchOffset, $batchSize)) {
            $email->subject($activityEmail->getSubject());
            $email->to($email->getNoReplyAddress(), t("Notifications Postmaster"));

            foreach ($batch as $recipient) {
                [$address, $name] = $recipient;
                $email->bcc($address, $name ?? "");
            }

            $actionUrl = externalUrl($route);
            $emailTemplate = $email->getEmailTemplate()
                ->setButton($actionUrl, $activityEmail->getActionText())
                ->setTitle($activityEmail->getSubject());
            if ($message = $activityEmail->getBody()) {
                $emailTemplate->setMessage($message, true);
            }
            $email->setEmailTemplate($emailTemplate);

            $this->EventArguments = $notification;
            $this->fireEvent("BeforeSendNotification");
            $this->sendEmail($email);

            $email->clear();
            $batchOffset += $batchSize;
        }
    }

    /**
     * Generate a notification event object, based on a database row.
     *
     * @param array $row
     * @return NotificationEvent
     */
    private function eventFromRow(array $row): NotificationEvent {
        /** @var UserModel */
        $userModel = Gdn::getContainer()->get(UserModel::class);
        $userModel->expandUsers($row, ["ActivityUserID", "NotifyUserID", "RegardingUserID"]);
        $notification = $this->normalizeRow($row, true);
        $notification = $this->schema()->validate($notification);
        $result = new NotificationEvent(
            NotificationEvent::ACTION_INSERT,
            ["notification" => $notification]
        );
        return $result;
    }

    /**
     * Given a database row, massage the data into a more externally-useful format.
     *
     * @param array $row
     * @return array
     */
    public function normalizeRow(array $row): array {
        $row["Url"] = !empty($row["Route"]) ? url($row["Route"], true) : null;

        $notifyUser = Gdn::userModel()->getID($row["NotifyUserID"], DATASET_TYPE_ARRAY);
        $row["headline"] = $this->getActivityHeadline($row, $notifyUser);

        $story = $row["Story"] ?? null;
        $format = $row["Format"] ?? null;
        if ($story && $format) {
            $this->formatField($row, "Story", $format);
        }

        $scheme = new CamelCaseScheme();
        $result = $scheme->convertArrayKeys($row);
        return $result;
    }

    /**
     * Get a schema instance comprised of standard activity fields.
     *
     * @return Schema
     */
    public function schema(): Schema {
        $result = Schema::parse([
            "activityID:i",
            "story:s",
            "dateInserted:dt",
            "insertUserID:i",
            "insertUser?" => SchemaFactory::get(UserFragmentSchema::class, "UserFragment"),
            "activityUserID:i",
            "activityUser?" => SchemaFactory::get(UserFragmentSchema::class, "UserFragment"),
            "regardingUserID:i",
            "regardingUser?" => SchemaFactory::get(UserFragmentSchema::class, "UserFragment"),
            "headline:s?",
            "story:s",
            "url:s?",
            "recordType:s?",
            "recordID:i?",
        ]);
        return $result;
    }
}
