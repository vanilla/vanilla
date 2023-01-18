<?php
/**
 * Activity Model.
 *
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Garden\EventManager;
use Garden\Events\ResourceEvent;
use Garden\Schema\Schema;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\FloodControlTrait;
use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Formatting\Formats;
use Vanilla\Formatting\FormatService;
use Vanilla\Dashboard\Events\NotificationEvent;
use Vanilla\Formatting\Html\HtmlSanitizer;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\DebugUtils;
use Vanilla\Web\SystemCallableInterface;

/**
 * Activity data management.
 */
class ActivityModel extends Gdn_Model implements SystemCallableInterface
{
    use FloodControlTrait;

    /** @var int Maximum number of items to hold in the queue before flushing. */
    private const MAX_QUEUE_LENGTH = 10;

    /** Maximum length of activity story excerpts. */
    private const DEFAULT_EXCERPT_LENGTH = 160;

    /** @var string Cache key for user notification count. */
    private const NOTIFICATIONS_COUNT_CACHE_KEY = "notificationCount/users/%s";

    /** @var int Cache time for user notification count. */
    private const NOTIFICATION_COUNT_TTL = 10; // 10 seconds.

    /** Activity notification level: Everyone. */
    const NOTIFY_PUBLIC = -1;

    /** Activity notification level: Moderators & admins. */
    const NOTIFY_MODS = -2;

    /** Activity notification level: Admins-only. */
    const NOTIFY_ADMINS = -3;

    /**
     * Activity status: The activity was saved without a status specified and the status defaulted to 0.
     */
    const SENT_NONE = 0;

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

    const SENT_TOAST = 32;

    /** @var array|null Allowed activity types. */
    public static $ActivityTypes = null;

    /** @var array Activity to be saved. */
    public static $Queue = [];

    /** @var int Limit on number of activity to combine. */
    public static $MaxMergeCount = 10;

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

    /** @var UserModel */
    private $userModel;

    /** @var UserMetaModel */
    private UserMetaModel $userMetaModel;

    /** @var CacheInterface */
    private $cache;

    /**
     * Defines the related database table name.
     *
     * @param Gdn_Validation|null $validation The validation dependency.
     * @param LoggerInterface|null $logger
     * @param FormatService|null $formatService
     * @param EventDispatcherInterface|null $eventDispatcher
     */
    public function __construct(
        Gdn_Validation $validation = null,
        ?LoggerInterface $logger = null,
        ?FormatService $formatService = null,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        parent::__construct("Activity", $validation);

        try {
            $this->setPruneAfter(c("Garden.PruneActivityAfter", "2 months"));
        } catch (Exception $ex) {
            $this->setPruneAfter("2 months");
        }

        $this->formatService =
            $formatService instanceof FormatService ? $formatService : Gdn::getContainer()->get(FormatService::class);
        $this->logger = $logger instanceof LoggerInterface ? $logger : Gdn::getContainer()->get(LoggerInterface::class);
        $this->eventDispatcher =
            $eventDispatcher instanceof EventDispatcherInterface
                ? $eventDispatcher
                : Gdn::getContainer()->get(EventDispatcherInterface::class);
        $this->userModel = Gdn::getContainer()->get(UserModel::class);
        $this->userMetaModel = Gdn::getContainer()->get(UserMetaModel::class);
        $this->cache = Gdn::getContainer()->get(CacheInterface::class);
    }

    /**
     * Build basis of common activity SQL query.
     *
     * @param bool $join
     * @since 2.0.0
     * @access public
     */
    public function activityQuery($join = true)
    {
        $this->SQL
            ->select("a.*")
            ->select("t.FullHeadline, t.ProfileHeadline, t.AllowComments, t.ShowIcon, t.RouteCode")
            ->select("t.Name", "", "ActivityType")
            ->from("Activity a")
            ->join("ActivityType t", "a.ActivityTypeID = t.ActivityTypeID");

        if ($join) {
            $this->SQL
                ->select("au.Name", "", "ActivityName")
                ->select("au.Photo", "", "ActivityPhoto")
                ->select("au.Email", "", "ActivityEmail")
                ->select("ru.Name", "", "RegardingName")
                ->select("ru.Email", "", "RegardingEmail")
                ->select("ru.Photo", "", "RegardingPhoto")
                ->join("User au", "a.ActivityUserID = au.UserID")
                ->join("User ru", "a.RegardingUserID = ru.UserID", "left");
        }

        $this->fireEvent("AfterActivityQuery");
    }

    /**
     * Can the current user view the activity?
     *
     * @param array $activity
     * @return bool
     */
    public function canView(array $activity): bool
    {
        $result = false;

        $userid = val("NotifyUserID", $activity);
        switch ($userid) {
            case ActivityModel::NOTIFY_PUBLIC:
                $result = true;
                break;
            case ActivityModel::NOTIFY_MODS:
                if (checkPermission("Garden.Moderation.Manage")) {
                    $result = true;
                }
                break;
            case ActivityModel::NOTIFY_ADMINS:
                if (checkPermission("Garden.Settings.Manage")) {
                    $result = true;
                }
                break;
            default:
                // Actual userid.
                if (Gdn::session()->UserID === $userid || checkPermission("Garden.Community.Manage")) {
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
    public function calculateData(&$data)
    {
        foreach ($data as &$row) {
            $this->calculateRow($row);
        }
    }

    /**
     *
     *
     * @param $row
     */
    public function calculateRow(&$row)
    {
        $activityType = self::getActivityType($row["ActivityTypeID"]);
        $row["ActivityType"] = val("Name", $activityType);
        if (is_string($row["Data"])) {
            $row["Data"] = dbdecode($row["Data"]);
        }

        $row["PhotoUrl"] = url($row["Route"], true);
        if (!$row["Photo"]) {
            if (isset($row["ActivityPhoto"])) {
                $row["Photo"] = $row["ActivityPhoto"];
                $row["PhotoUrl"] = userUrl($row, "Activity");
            } else {
                $user = $this->userModel->getID($row["ActivityUserID"], DATASET_TYPE_ARRAY);
                if ($user) {
                    $photo = $user["Photo"];
                    $row["PhotoUrl"] = userUrl($user);
                    if (!$photo || isUrl($photo)) {
                        $row["Photo"] = $photo;
                    } else {
                        $row["Photo"] = Gdn_Upload::url(changeBasename($photo, "n%s"));
                    }
                }
            }
        }

        $data = $row["Data"];
        if (isset($data["ActivityUserIDs"])) {
            $row["ActivityUserID"] = array_merge([$row["ActivityUserID"]], $data["ActivityUserIDs"]);
            $row["ActivityUserID_Count"] = val("ActivityUserID_Count", $data);
        }

        if (isset($data["RegardingUserIDs"])) {
            $row["RegardingUserID"] = array_merge([$row["RegardingUserID"]], $data["RegardingUserIDs"]);
            $row["RegardingUserID_Count"] = val("RegardingUserID_Count", $data);
        }

        if (!empty($row["Route"])) {
            $row["Url"] = externalUrl($row["Route"]);
        } else {
            $id = $row["ActivityID"];
            $row["Url"] = Gdn::request()->url("/activity/item/$id", true);
        }

        if (isset($row["MaxDateUpdated"])) {
            $row["DateUpdated"] = $row["MaxDateUpdated"];
            unset($row["MaxDateUpdated"]);
        }

        if ($row["HeadlineFormat"]) {
            if (isset($row["count"]) && $row["count"] > 1) {
                $row["HeadlineFormat"] =
                    $row["PluralHeadlineFormat"] ?? ($row["PluralHeadline"] ?? $row["HeadlineFormat"]);
            }
            $row["Headline"] = formatString($row["HeadlineFormat"], $row);
        } else {
            $row["Headline"] = Gdn_Format::activityHeadline($row);
        }
    }

    /**
     * Define a new activity type.
     * @param string $name The string code of the activity type.
     * @param array $activity The data that goes in the ActivityType table.
     * @since 2.1
     */
    public function defineType($name, $activity = [])
    {
        $this->SQL->replace("ActivityType", $activity, ["Name" => $name], true);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($where = [], $options = [])
    {
        if (is_numeric($where)) {
            deprecated("ActivityModel->delete(int)", "ActivityModel->deleteID(int)");

            $result = $this->deleteID($where, $options);
            return $result;
        } elseif (count($where) === 1 && isset($where["ActivityID"])) {
            return parent::delete($where, $options);
        }

        throw new BadMethodCallException("ActivityModel->delete() is not supported.", 400);
    }

    /**
     * Delete a particular activity item.
     *
     * @param int $id The unique ID of activity to be deleted.
     * @param array $options Not used.
     * @return bool Returns **true** if the activity was deleted or **false** otherwise.
     */
    public function deleteID($id, $options = [])
    {
        // Get the activity first.
        $activity = $this->getID($id);
        if ($activity) {
            // Log the deletion.
            $log = val("Log", $options);
            if ($log) {
                LogModel::insert($log, "Activity", $activity);
            }

            // Delete comments on the activity item
            $this->SQL->delete("ActivityComment", ["ActivityID" => $id]);

            // Delete the activity item
            return parent::deleteID($id);
        } else {
            return false;
        }
    }

    /**
     * Delete an activity comment.
     *
     * @param int $iD
     * @return Gdn_DataSet
     * @since 2.1
     *
     */
    public function deleteComment($iD)
    {
        return $this->SQL->delete("ActivityComment", ["ActivityCommentID" => $iD]);
    }

    /**
     * Get the recent activities.
     *
     * @param array $where
     * @param int $limit
     * @param int $offset
     * @return Gdn_DataSet
     */
    public function getWhereRecent($where, $limit = 0, $offset = 0)
    {
        $result = $this->getWhere($where, "", "", $limit, $offset);
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
     * @param bool $shouldBatch Whether to batch notifications of the same activity type that pertain to a common recordID or parentRecordID.
     *
     * @return Gdn_DataSet SQL results.
     */
    public function getWhere($where = [], $orderFields = "", $orderDirection = "", $limit = false, $offset = false)
    {
        if (is_string($where)) {
            deprecated('ActivityModel->getWhere($key, $value)', 'ActivityModel->getWhere([$key => $value])');
            $where = [$where => $orderFields];
            $orderFields = "";
        }
        if (is_numeric($orderFields)) {
            deprecated('ActivityModel->getWhere($where, $limit)');
            $limit = $orderFields;
            $orderFields = "";
        }
        if (is_numeric($orderDirection)) {
            deprecated('ActivityModel->getWhere($where, $limit, $offset)');
            $offset = $orderDirection;
            $orderDirection = "";
        }
        $limit = $limit ?: 30;
        $offset = $offset ?: 0;

        // Add some conditions here to only grab the latest record when ActivityTypeID, UserID, and ParentRecordID all match.
        $this->SQL
            ->select("a.*")
            ->select("t.FullHeadline, t.ProfileHeadline, t.PluralHeadline, t.AllowComments, t.ShowIcon, t.RouteCode")
            ->select("t.Name", "", "ActivityType")
            ->from("Activity a")
            ->join("ActivityType t", "a.ActivityTypeID = t.ActivityTypeID");

        // Add prefixes to the where.
        foreach ($where as $key => $value) {
            if (strpos($key, ".") === false) {
                $where["a." . $key] = $value;
                unset($where[$key]);
            }
        }

        $orderFields = $orderFields ?: "a.DateUpdated";
        $orderDirection = $orderDirection ?: "desc";

        $hasPrimaryKeyFilter = isset($where["ActivityID"]) || isset($where["a.ActivityID"]);
        $hasNotifiedFilter = false;
        foreach ($where as $key => $val) {
            if (str_contains($key, "Notified")) {
                $hasNotifiedFilter = true;
            }
        }
        if (!$hasPrimaryKeyFilter && !$hasNotifiedFilter) {
            // Exclude email only activities.
            $where["a.Notified <>"] = self::SENT_SKIPPED;
        }

        $result = $this->SQL
            ->where($where)
            ->orderBy($orderFields, $orderDirection)
            ->limit($limit, $offset)
            ->get();

        $this->userModel->joinUsers(
            $result->resultArray(),
            ["ActivityUserID", "RegardingUserID"],
            ["Join" => ["Name", "Email", "Photo"]]
        );
        $this->calculateData($result->resultArray());

        $this->EventArguments["Data"] = &$result;
        $this->fireEvent("AfterGet");

        return $result;
    }

    /**
     * Get the batched activities.
     *
     * @param array $where A filter suitable for passing to Gdn_SQLDriver::where().
     * @param string $orderFields A comma delimited string to order the data.
     * @param string $orderDirection One of **asc** or **desc**.
     * @param int|bool $limit The database limit.
     * @param int|bool $offset The database offset.
     * @param int|bool $innerQueryLimit Optimize the aggregate to limit the scope. Note: This parameter might cause some records to not be returned. It is advised to use a limit of at least 100+.
     * @param array $innerWhere A filter suitable for passing to Gdn_SQLDriver::where(). Will default to $where if no value is provided.
     * @return Gdn_DataSet SQL results.
     */
    public function getWhereBatched(
        $where = [],
        $orderFields = "",
        $orderDirection = "",
        $limit = false,
        $offset = false,
        $innerQueryLimit = false,
        $innerWhere = null
    ) {
        $innerWhere = $innerWhere ?? $where;
        $innerQuery = $this->getBatchedQuery($innerWhere, $innerQueryLimit);
        $limit = $limit ?: 30;
        $offset = $offset ?: 0;
        $orderFields = $orderFields ?: "a.MaxDateUpdated";
        $orderDirection = $orderDirection ?: "desc";

        if (!isset($where["ActivityID"]) && !isset($where["Notified"])) {
            // Exclude email only activities.
            $where["Notified <>"] = self::SENT_SKIPPED;
        }

        if (isset($where["ActivityTypeID"])) {
            $where["a2.ActivityTypeID"] = $where["ActivityTypeID"];
            unset($where["ActivityTypeID"]);
        }

        $sql = $this->Database
            ->createSql()
            ->select("a2.*")
            ->select("t.FullHeadline, t.ProfileHeadline, t.PluralHeadline, t.AllowComments, t.ShowIcon, t.RouteCode")
            ->select("t.Name", "", "ActivityType")
            ->select("a.*")
            ->from("($innerQuery) a")
            ->join("Activity a2", "a.ActivityID = a2.ActivityID")
            ->join("ActivityType t", "a2.ActivityTypeID = t.ActivityTypeID")
            ->where($where)
            ->orderBy($orderFields, $orderDirection)
            ->limit($limit, $offset);
        $result = $sql->get();
        $this->userModel->joinUsers(
            $result->resultArray(),
            ["ActivityUserID", "RegardingUserID"],
            ["Join" => ["Name", "Email", "Photo"]]
        );
        $this->calculateData($result->resultArray());

        $this->EventArguments["Data"] = &$result;
        $this->fireEvent("AfterGet");

        return $result;
    }

    /**
     * return the inner query used by getWhereBatched().
     *
     * @param array $where A filter suitable for passing to Gdn_SQLDriver::where().
     * @param int|bool $innerQueryLimit Optimize the aggregate to limit the scope.
     * @return string
     */
    private function getBatchedQuery(array $where, $innerQueryLimit = false): string
    {
        $sql = $this->Database
            ->createSql()
            ->select("ActivityID", "min", "ActivityID")
            ->select("DateUpdated", "max", "MaxDateUpdated")
            ->select("ActivityID", "count", "count");

        $innerQuery = $this->Database
            ->createSql()
            ->select([
                "ActivityID",
                "ParentRecordID",
                "RecordID",
                "ActivityTypeID",
                "RecordType",
                "Notified",
                "DateUpdated",
                "BatchID",
            ])
            ->from($this->getTableName())
            ->where($where)
            ->limit($innerQueryLimit)
            ->orderBy("DateUpdated", "desc")
            ->getSelect(true);
        $sql->from("($innerQuery) a");

        $query = $sql->getSelect(true);
        // Coalesce is not an accepted value by out sqlDriver.
        $query .= " group by
                coalesce(`a`.`ParentRecordID`,
                `a`.`RecordID`, `a`.`ActivityID`),
                `a`.`ActivityTypeID`,
                `a`.`RecordType`,
                `a`.`Notified`,
                `a`.`BatchID`";

        return $query;
    }

    /**
     * Get a single notification with the count of batched records by using the shouldBatch option in the call to getWhere().
     *
     * @param int $id The notification ID.
     * @return array|bool
     */
    public function getNotificationWithCount(int $id)
    {
        $notification = $this->getID($id, DATASET_TYPE_ARRAY);
        $where = [
            "ActivityTypeID" => $notification["ActivityTypeID"],
            "NotifyUserID" => $notification["NotifyUserID"],
        ];
        if (isset($notification["ParentRecordID"])) {
            $where["ParentRecordID"] = $notification["ParentRecordID"];
        } else {
            $where["RecordID"] = $notification["RecordID"];
        }
        $notification = $this->getWhereBatched($where, "", "", 1000, false)->firstRow();

        return $notification;
    }

    /**
     *
     *
     * @param array &$activities
     * @since 2.1
     */
    public function joinComments(&$activities)
    {
        // Grab all the activity IDs.
        $activityIDs = [];
        foreach ($activities as $activity) {
            if ($iD = val("CommentActivityID", $activity["Data"])) {
                // This activity shares its comments with another activity.
                $activityIDs[] = $iD;
            } else {
                $activityIDs[] = $activity["ActivityID"];
            }
        }
        $activityIDs = array_unique($activityIDs);

        $comments = $this->getComments($activityIDs);
        $comments = Gdn_DataSet::index($comments, ["ActivityID"], ["Unique" => false]);
        foreach ($activities as &$activity) {
            $iD = val("CommentActivityID", $activity["Data"]);
            if (!$iD) {
                $iD = $activity["ActivityID"];
            }

            if (isset($comments[$iD])) {
                $activity["Comments"] = $comments[$iD];
            } else {
                $activity["Comments"] = [];
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
    public function getByUser($notifyUserID = false, $offset = 0, $limit = 30)
    {
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
        $this->SQL->whereIn("NotifyUserID", (array) $notifyUserID);

        $this->fireEvent("BeforeGet");
        $result = $this->SQL
            ->orderBy("a.ActivityID", "desc")
            ->limit($limit, $offset)
            ->get();

        $this->userModel->joinUsers(
            $result,
            ["ActivityUserID", "RegardingUserID"],
            ["Join" => ["Name", "Photo", "Email"]]
        );

        $this->EventArguments["Data"] = &$result;
        $this->fireEvent("AfterGet");

        return $result;
    }

    /**
     *
     *
     * @param array &$data
     */
    public static function getUsers(&$data)
    {
        $userIDs = [];

        foreach ($data as &$row) {
            if (is_string($row["Data"])) {
                $row["Data"] = dbdecode($row["Data"]);
            }

            $userIDs[$row["ActivityUserID"]] = 1;
            $userIDs[$row["RegardingUserID"]] = 1;

            if (isset($row["Data"]["ActivityUserIDs"])) {
                foreach ($row["Data"]["ActivityUserIDs"] as $userID) {
                    $userIDs[$userID] = 1;
                }
            }

            if (isset($row["Data"]["RegardingUserIDs"])) {
                foreach ($row["Data"]["RegardingUserIDs"] as $userID) {
                    $userIDs[$userID] = 1;
                }
            }
        }

        Gdn::userModel()->getIDs(array_keys($userIDs));
    }

    /**
     * Get an activity type by its name or ID.
     *
     * @param int|string $activityTypeIDOrName
     * @return array|false
     */
    public static function getActivityType($activityTypeIDOrName)
    {
        if (self::$ActivityTypes === null || DebugUtils::isTestMode()) {
            $data = Gdn::sql()
                ->get("ActivityType")
                ->resultArray();
            foreach ($data as $row) {
                self::$ActivityTypes[$row["Name"]] = $row;
                self::$ActivityTypes[$row["ActivityTypeID"]] = $row;
            }
        }
        if (isset(self::$ActivityTypes[$activityTypeIDOrName])) {
            return self::$ActivityTypes[$activityTypeIDOrName];
        }
        return false;
    }

    /**
     * Get number of activity related to a user.
     *
     * Events: BeforeGetCount.
     *
     * @param array|string $wheres Where conditions to apply to the query.
     * @param int|null $userID Unique ID of user.
     *
     * @return int Number of activity items found.
     * @since 2.0.0
     * @access public
     */
    public function getCount($wheres = "", ?int $userID = null)
    {
        $this->SQL
            ->select("a.ActivityID", "count", "ActivityCount")
            ->from("Activity a")
            ->join("ActivityType t", "a.ActivityTypeID = t.ActivityTypeID");

        if (is_array($wheres)) {
            $this->SQL->where($wheres);
        }

        if ($userID != "") {
            $this->SQL
                ->beginWhereGroup()
                ->where("a.ActivityUserID", $userID)
                ->orWhere("a.RegardingUserID", $userID)
                ->endWhereGroup();
        }

        $session = Gdn::session();
        if (!$session->isValid() || $session->UserID != $userID) {
            $this->SQL->where("t.Public", "1");
        }

        $this->fireEvent("BeforeGetCount");
        return $this->SQL->get()->firstRow()->ActivityCount;
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
    public function getForRole($roleID = "", $offset = 0, $limit = 50)
    {
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
            ->join("UserRole ur", "a.ActivityUserID = ur.UserID")
            ->whereIn("ur.RoleID", $roleID)
            ->where("t.Public", "1")
            ->orderBy("a.DateInserted", "desc")
            ->limit($limit, $offset)
            ->get();

        $this->EventArguments["Data"] = &$result;
        $this->fireEvent("AfterGet");

        return $result;
    }

    /**
     * Get number of activity related to a particular role.
     *
     * @param int|string $roleID Unique ID of role.
     * @return int Number of activity items.
     * @since 2.0.18
     * @access public
     */
    public function getCountForRole($roleID = "")
    {
        if (!is_array($roleID)) {
            $roleID = [$roleID];
        }

        return $this->SQL
            ->select("a.ActivityID", "count", "ActivityCount")
            ->from("Activity a")
            ->join("ActivityType t", "a.ActivityTypeID = t.ActivityTypeID")
            ->join("UserRole ur", "a.ActivityUserID = ur.UserID")
            ->whereIn("ur.RoleID", $roleID)
            ->where("t.Public", "1")
            ->get()
            ->firstRow()->ActivityCount;
    }

    /**
     * Get a particular activity record.
     *
     * @param int $id Unique ID of activity item.
     * @param bool|string $datasetType The format of the resulting data.
     * @param array $options Not used.
     * @return array|object A single SQL result.
     */
    public function getID($id, $datasetType = false, $options = [])
    {
        $activity = parent::getID($id, $datasetType);
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
    public function getNotifications($notifyUserID, $offset = 0, $limit = 30)
    {
        $this->activityQuery(false);
        $this->fireEvent("BeforeGetNotifications");
        $result = $this->SQL
            ->where("NotifyUserID", $notifyUserID)
            ->limit($limit, $offset)
            ->orderBy("a.ActivityID", "desc")
            ->get();
        $result->datasetType(DATASET_TYPE_ARRAY);

        self::getUsers($result->resultArray());
        $this->userModel->joinUsers(
            $result->resultArray(),
            ["ActivityUserID", "RegardingUserID"],
            ["Join" => ["Name", "Photo", "Email"]]
        );
        $this->calculateData($result->resultArray());

        return $result;
    }

    /**
     * @param $activity
     * @return bool
     */
    public static function canDelete($activity)
    {
        $session = Gdn::session();

        $profileUserId = val("ActivityUserID", $activity);
        $notifyUserId = val("NotifyUserID", $activity);

        // User can delete any activity
        if ($session->checkPermission("Garden.Activity.Delete")) {
            return true;
        }

        $notifyUserIds = [ActivityModel::NOTIFY_PUBLIC];
        if (Gdn::session()->checkPermission("Garden.Moderation.Manage")) {
            $notifyUserIds[] = ActivityModel::NOTIFY_MODS;
        }

        // Is this a wall post?
        if (
            !in_array(val("ActivityType", $activity), ["Status", "WallPost"]) ||
            !in_array($notifyUserId, $notifyUserIds)
        ) {
            return false;
        }
        // Is this on the user's wall?
        if ($profileUserId && $session->UserID == $profileUserId && $session->checkPermission("Garden.Profiles.Edit")) {
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
    public function getNotificationsSince($userID, $lastActivityID, $filterToActivityTypeIDs = "", $limit = 5)
    {
        $this->activityQuery();
        $this->fireEvent("BeforeGetNotificationsSince");
        if (is_array($filterToActivityTypeIDs)) {
            $this->SQL->whereIn("a.ActivityTypeID", $filterToActivityTypeIDs);
        } else {
            $this->SQL->where("t.Notify", "1");
        }

        $result = $this->SQL
            ->where("RegardingUserID", $userID)
            ->where("a.ActivityID >", $lastActivityID)
            ->limit($limit, 0)
            ->orderBy("a.ActivityID", "desc")
            ->get();

        return $result;
    }

    /**
     * @param int $iD
     * @return array|false
     */
    public function getComment($iD)
    {
        $activity = $this->SQL->getWhere("ActivityComment", ["ActivityCommentID" => $iD])->resultArray();
        if ($activity) {
            $this->userModel->joinUsers($activity, ["InsertUserID"], ["Join" => ["Name", "Photo", "Email"]]);
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
    public function getComments($activityIDs)
    {
        $result = $this->SQL
            ->select("c.*")
            ->from("ActivityComment c")
            ->whereIn("c.ActivityID", $activityIDs)
            ->orderBy("c.ActivityID, c.DateInserted")
            ->get()
            ->resultArray();
        $this->userModel->joinUsers($result, ["InsertUserID"], ["Join" => ["Name", "Photo", "Email"]]);
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
    public function add(
        $activityUserID,
        $activityType,
        $story = null,
        $regardingUserID = null,
        $commentActivityID = null,
        $route = null,
        $sendEmail = ""
    ) {
        // Get the ActivityTypeID & see if this is a notification.
        $activityTypeRow = self::getActivityType($activityType);
        $notify = val("Notify", $activityTypeRow, false);

        if ($activityTypeRow === false) {
            trigger_error(
                errorMessage(sprintf("Activity type could not be found: %s", $activityType), "ActivityModel", "Add"),
                E_USER_ERROR
            );
        }

        $activity = [
            "ActivityUserID" => $activityUserID,
            "ActivityType" => $activityType,
            "Story" => $story,
            "RegardingUserID" => $regardingUserID,
            "Route" => $route,
        ];

        // Massage $SendEmail to allow for only sending an email.
        if ($sendEmail === "Only") {
            $sendEmail = "";
        }

        // If $SendEmail was FALSE or TRUE, let it override the $Notify setting.
        if ($sendEmail === false || $sendEmail === true) {
            $notify = $sendEmail;
        }

        $preference = false;
        if (($activityTypeRow["Notify"] || !$activityTypeRow["Public"]) && !empty($regardingUserID)) {
            $activity["NotifyUserID"] = $activity["RegardingUserID"];
            $preference = $activityType;
        } else {
            $activity["NotifyUserID"] = self::NOTIFY_PUBLIC;
        }

        // Otherwise let the decision to email lie with the $Notify setting.
        if ($sendEmail === "Force" || $notify) {
            $activity["Emailed"] = self::SENT_PENDING;
        } elseif ($notify) {
            $activity["Emailed"] = self::SENT_PENDING;
        } elseif ($sendEmail === false) {
            $activity["Emailed"] = self::SENT_ARCHIVE;
        }

        $activity = $this->save($activity, $preference);

        return val("ActivityID", $activity);
    }

    /**
     * Join the users to the activities.
     *
     * @param array|Gdn_DataSet &$activities The activities to join.
     */
    public static function joinUsers(&$activities)
    {
        Gdn::userModel()->joinUsers(
            $activities,
            ["ActivityUserID", "RegardingUserID"],
            ["Join" => ["Name", "Email", "Photo"]]
        );
    }

    /**
     * Get default notification preference for an activity type.
     *
     * @param string $activityType
     * @param array|int $preferencesOrUserID
     * @param null|string $type One of the following:
     *  - Popup: Popup a notification.
     *  - Email: Email the notification.
     *  - NULL: True if either notification is true.
     *  - both: Return an array of (Popup, Email).
     * @return bool|bool[]
     * @since 2.0.0
     * @access public
     */
    public static function notificationPreference($activityType, $preferencesOrUserID, $type = null)
    {
        if (is_numeric($preferencesOrUserID)) {
            $user = Gdn::userModel()->getID($preferencesOrUserID);
            if (!$user) {
                return $type == "both" ? [false, false] : false;
            }
            $preferences = val("Preferences", $user);
            if (!is_array($preferences)) {
                $preferences = [];
            }

            // Grab preferences from usermeta as well.
            $metaPrefs = \Gdn::userMetaModel()->getUserMeta($preferencesOrUserID, "Preferences.%", [], "Preferences.");
            $preferences = array_merge($preferences, $metaPrefs);
        } else {
            $preferences = $preferencesOrUserID;
        }

        if ($type === null) {
            $result =
                self::notificationPreference($activityType, $preferences, "Email") ||
                self::notificationPreference($activityType, $preferences, "Popup");

            return $result;
        } elseif ($type === "both") {
            $result = [
                self::notificationPreference($activityType, $preferences, "Popup"),
                self::notificationPreference($activityType, $preferences, "Email"),
            ];
            return $result;
        }

        $configPreference = c("Preferences.$type.$activityType", "0");
        if ((int) $configPreference === 2) {
            $preference = true; // This preference is forced on.
        } elseif ($configPreference !== false) {
            $preference = val($type . "." . $activityType, $preferences, $configPreference);
        } else {
            $preference = false;
        }

        return $preference;
    }

    /**
     * Takes an array representing an activity and builds the email message based on the activity's story and
     * the contents of the global config Garden.Email.Prefix.
     *
     * @param array|object $activity The activity to build the email for.
     * @return string The email message.
     */
    private function getEmailMessage($activity)
    {
        $message = "";

        if ($prefix = c("Garden.Email.Prefix", "")) {
            $message = $prefix;
        }

        $isArray = is_array($activity);

        $story = $isArray ? $activity["Story"] ?? null : $activity->Story ?? null;
        $format = $isArray ? $activity["Format"] ?? null : $activity->Format ?? null;

        if ($story && $format) {
            $message .= Gdn::formatService()->renderHTML((string) $story, $format);
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
    public function email(&$activity, $options = [])
    {
        // The $options parameter used to be $noDelete bool, this is the backwards compat.
        if (is_bool($options)) {
            $options = ["NoDelete" => $options];
        }
        $options += [
            "NoDelete" => false,
            "EmailSubject" => "",
        ];

        if (is_numeric($activity)) {
            $activityID = $activity;
            $activity = $this->getID($activityID);
        } else {
            $activityID = val("ActivityID", $activity);
        }

        if (!$activity) {
            return false;
        }

        $activity = (array) $activity;

        $user = $this->userModel->getID($activity["NotifyUserID"], DATASET_TYPE_ARRAY);
        if (!$user) {
            return false;
        }

        if (!Gdn::userModel()->checkPermission($user, "Garden.Email.View")) {
            // User doens't have permission to get emails.
            $activity["Emailed"] = self::SENT_SKIPPED;
            return false;
        }

        $activity["Headline"] = $this->getActivityHeadline($activity, $user);

        // Build the email to send.
        $email = new Gdn_Email();
        $email->subject($this->getEmailSubjectFormatted($activity, $options));
        $email->to($user);

        $url = externalUrl(val("Route", $activity) == "" ? "/" : val("Route", $activity));

        $emailTemplate = $email
            ->getEmailTemplate()
            ->setButton($url, val("ActionText", $activity, t("Check it out")))
            ->setTitle($this->getEmailSubject($activity, $options));

        if ($message = $this->getEmailMessage($activity)) {
            $emailTemplate->setMessage($message, true);
        }

        $email->setEmailTemplate($emailTemplate);

        // Fire an event for the notification.
        $notification = [
            "ActivityID" => $activityID,
            "User" => $user,
            "Email" => $email,
            "Route" => $activity["Route"],
            "Story" => $activity["Story"],
            "Headline" => $activity["Headline"],
            "Activity" => $activity,
        ];
        $this->EventArguments = $notification;
        $this->fireEvent("BeforeSendNotification");

        // Only send if the user is not banned
        if (!val("Banned", $user)) {
            $activity["Emailed"] = $this->sendEmail($email);
        } else {
            $activity["Emailed"] = self::SENT_SKIPPED;
        }

        if ($activityID) {
            // Save the emailed flag back to the activity.
            $this->SQL->put("Activity", ["Emailed" => $activity["Emailed"]], ["ActivityID" => $activityID]);
        }
        return true;
    }

    /**
     * Given an activity, generate a fully-formatted headline.
     *
     * @param array $activity
     * @return string
     */
    private function getActivityHeadline(array $activity, array $user): string
    {
        // Format the activity headline based on the user being emailed.
        if (val("HeadlineFormat", $activity)) {
            $sessionUserID = Gdn::session()->UserID;
            Gdn::session()->UserID = $user["UserID"];
            $result = formatString($activity["HeadlineFormat"], $activity);
            Gdn::session()->UserID = $sessionUserID;
        } else {
            if (!isset($activity["ActivityGender"])) {
                $aT = self::getActivityType($activity["ActivityType"]);

                $data = [$activity];
                self::joinUsers($data);
                $activity = $data[0];
                $activity["RouteCode"] = val("RouteCode", $aT);
                $activity["FullHeadline"] = val("FullHeadline", $aT);
                $activity["ProfileHeadline"] = val("ProfileHeadline", $aT);
            }

            $result = Gdn_Format::activityHeadline($activity, "", $user["UserID"]);
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
    private function getEmailSubject(array $activity, array $options): string
    {
        $emailSubject = $options["EmailSubject"] ?? null;
        $headline = $this->formatService->renderPlainText($activity["Headline"], Formats\HtmlFormat::FORMAT_KEY);
        if ($emailSubject) {
            $emailSubject = $headline . " in " . $emailSubject;
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
    private function getEmailSubjectFormatted(array $activity, array $options): string
    {
        $subject = $this->getEmailSubject($activity, $options);
        $result = sprintf(t("[%1\$s] %2\$s"), c("Garden.Title"), $subject);
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
    public function clearNotificationQueue()
    {
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
    public function comment($comment)
    {
        $comment["InsertUserID"] = Gdn::session()->UserID;
        $comment["DateInserted"] = Gdn_Format::toDateTime();
        $comment["InsertIPAddress"] = ipEncode(Gdn::request()->ipAddress());

        $this->Validation->applyRule("ActivityID", "Required");
        $this->Validation->applyRule("Body", "Required");
        $this->Validation->applyRule("DateInserted", "Required");
        $this->Validation->applyRule("InsertUserID", "Required");

        $this->EventArguments["Comment"] = &$comment;
        $this->fireEvent("BeforeSaveComment");

        if ($this->validate($comment)) {
            $activity = $this->getID($comment["ActivityID"], DATASET_TYPE_ARRAY);

            $_ActivityID = $comment["ActivityID"];
            // Check to see if this is a shared activity/notification.
            if ($commentActivityID = val("CommentActivityID", $activity["Data"])) {
                Gdn::controller()->json("CommentActivityID", $commentActivityID);
                $comment["ActivityID"] = $commentActivityID;
            }

            $storageObject = FloodControlHelper::configure($this, "Vanilla", "ActivityComment");
            if ($this->checkUserSpamming(Gdn::session()->User->UserID, $storageObject)) {
                return false;
            }

            // Check for spam.
            $spam = SpamModel::isSpam("ActivityComment", $comment);
            if ($spam) {
                return SPAM;
            }

            // Check for approval
            $approvalRequired = checkRestriction("Vanilla.Approval.Require");
            if ($approvalRequired && !val("Verified", Gdn::session()->User)) {
                LogModel::insert("Pending", "ActivityComment", $comment);
                return UNAPPROVED;
            }

            $iD = $this->SQL->insert("ActivityComment", $comment);

            if ($iD) {
                // Check to see if this comment bumps the activity.
                if ($activity && val("Bump", $activity["Data"])) {
                    $this->SQL->put(
                        "Activity",
                        ["DateUpdated" => $comment["DateInserted"]],
                        ["ActivityID" => $activity["ActivityID"]]
                    );
                    if ($_ActivityID != $comment["ActivityID"]) {
                        $this->SQL->put(
                            "Activity",
                            ["DateUpdated" => $comment["DateInserted"]],
                            ["ActivityID" => $_ActivityID]
                        );
                    }
                }

                // Send a notification to the original person.
                if (val("ActivityType", $activity) === "WallPost") {
                    $this->notifyWallComment($comment, $activity);
                }
            }

            return $iD;
        }
        return false;
    }

    /**
     * Get total unread notifications for a user.
     *
     * @param int $userID
     */
    public function getUserTotalUnread($userID)
    {
        if (!$userID) {
            return 0; // If false or null or 0 (guest) get called, we don't have any notifications.
        }
        $key = sprintf(self::NOTIFICATIONS_COUNT_CACHE_KEY, $userID);
        $notificationCount = $this->cache->get($key, null);
        if ($notificationCount === null) {
            $notifications = $this->SQL
                ->select("ActivityID", "count", "total")
                ->from($this->Name)
                ->where("NotifyUserID", $userID)
                ->where("Notified", self::SENT_PENDING)
                ->get()
                ->resultArray();
            if (!is_array($notifications) || !isset($notifications[0])) {
                $notificationCount = 0;
            } else {
                $notificationCount = $notifications[0]["total"] ?? 0;
            }
            $this->cache->set($key, $notificationCount, self::NOTIFICATION_COUNT_TTL);
        }
        return $notificationCount;
    }

    /**
     * Get total notifications for a user.
     *
     * @param integer $notifyUser Enum: NOTIFY_PUBLIC, NOTIFY_MODS, NOTIFY_ADMINS
     * @param array $where
     * @return integer
     */
    public function getUserTotal(int $notifyUser, array $where = []): int
    {
        $where = $where + [
            "NotifyUserID" => $notifyUser,
        ];
        $notifications = $this->SQL
            ->select("ActivityID", "count", "total")
            ->from($this->Name)
            ->where($where)
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
     * @param array $activities
     */
    public function setNotified($activities)
    {
        $singleActivities = [];
        foreach ($activities as $activity) {
            $activity = (array) $activity;
            if ($activity["count"] > 1) {
                $this->updateNotificationStatusBatch($activity, self::SENT_TOAST);
            } else {
                $singleActivities[] = $activity["ActivityID"];
            }
        }
        if (count($singleActivities) == 0) {
            return;
        }

        $this->SQL
            ->update("Activity")
            ->set("Notified", self::SENT_TOAST)
            ->whereIn("ActivityID", $singleActivities)
            ->put();
    }

    /**
     *
     *
     * @param $activity
     * @throws Exception
     */
    public function share(&$activity)
    {
        // Massage the event for the user.
        $this->EventArguments["RecordType"] = "Activity";
        $this->EventArguments["Activity"] = &$activity;

        $this->fireEvent("Share");
    }

    /**
     * Queue a notification for sending.
     *
     * @param int $activityID
     * @param string $story
     * @param string $position
     * @param bool $force
     * @since 2.0.17
     * @access public
     */
    public function queueNotification($activityID, $story = "", $position = "last", $force = false)
    {
        $activity = $this->getID($activityID);
        if (!is_object($activity)) {
            return;
        }

        $story = Gdn_Format::text($story == "" ? $activity->Story : $story, false);
        // If this is a comment on another activity, fudge the activity a bit so that everything appears properly.
        if (is_null($activity->RegardingUserID) && $activity->CommentActivityID > 0) {
            $commentActivity = $this->getID($activity->CommentActivityID);
            $activity->RegardingUserID = $commentActivity->RegardingUserID;
            $activity->Route = "/activity/item/" . $activity->CommentActivityID;
        }
        $user = $this->userModel->getID($activity->RegardingUserID, DATASET_TYPE_OBJECT);

        if ($user) {
            if ($force) {
                $preference = $force;
            } else {
                $configPreference = c("Preferences.Email." . $activity->ActivityType, "0");
                if ($configPreference !== false) {
                    $preference = val("Email." . $activity->ActivityType, $user->Preferences, $configPreference);
                } else {
                    $preference = false;
                }
            }

            if ($preference) {
                $activityHeadline = Gdn_Format::text(
                    Gdn_Format::activityHeadline($activity, $activity->ActivityUserID, $activity->RegardingUserID),
                    false
                );
                $email = new Gdn_Email();
                $email->subject(sprintf(t('[%1$s] %2$s'), Gdn::config("Garden.Title"), $activityHeadline));
                $email->to($user);
                $url = externalUrl(val("Route", $activity) == "" ? "/" : val("Route", $activity));

                $emailTemplate = $email
                    ->getEmailTemplate()
                    ->setButton($url, val("ActionText", $activity, t("Check it out")))
                    ->setTitle(Gdn_Format::plainText(val("Headline", $activity)));

                if ($message = $this->getEmailMessage($activity)) {
                    $emailTemplate->setMessage($message, true);
                }

                $email->setEmailTemplate($emailTemplate);

                if (!array_key_exists($user->UserID, $this->_NotificationQueue)) {
                    $this->_NotificationQueue[$user->UserID] = [];
                }

                $notification = [
                    "ActivityID" => $activityID,
                    "User" => $user,
                    "Email" => $email,
                    "Route" => $activity->Route,
                    "Story" => $story,
                    "Headline" => $activityHeadline,
                    "Activity" => $activity,
                ];
                if ($position == "first") {
                    $this->_NotificationQueue[$user->UserID] = array_merge(
                        [$notification],
                        $this->_NotificationQueue[$user->UserID]
                    );
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
    public function queue($data, $preference = false, $options = [])
    {
        $this->_touch($data);
        if ($data["ActivityUserID"] == $data["NotifyUserID"] && !val("Force", $options)) {
            return; // don't notify users of something they did.
        }

        $data = $this->calculateActivityPreference($data, $preference ?: null);

        self::$Queue[$data["NotifyUserID"]][$data["ActivityType"]] = [$data, $options];
        if (count(self::$Queue) >= self::MAX_QUEUE_LENGTH) {
            $this->saveQueue();
        }
    }

    /**
     * Calculate email and in app notification preferences for a user on a particular record.
     *
     * Order of checks:
     * - If there is a preference, we check the preference in the order of - User specific preference fallback to global preference.
     * - If there is not a preference then we set both to true.
     *   - Otherwise we default to sending both in-app and email.
     * - Otherwise fallback to the config value for that.
     *
     * @param array $activity
     * @param string|null $preference
     *
     * @return array The updated activity record.
     */
    private function calculateActivityPreference(array $activity, ?string $preference): array
    {
        if ($preference === null) {
            return $activity;
        }
        [$popup, $email] = self::notificationPreference($preference, $activity["NotifyUserID"], "both");

        $activity["Notified"] =
            $popup && !Gdn::config("Garden.Popups.Disabled") ? self::SENT_PENDING : self::SENT_SKIPPED;
        $activity["Emailed"] =
            $email && !Gdn::config("Garden.Email.Disabled") ? self::SENT_PENDING : self::SENT_SKIPPED;

        return $activity;
    }

    /**
     * Use this after calling "calculateActivityPreference()" to determine if an activity should be sent.
     *
     * @param array $activity
     *
     * @return bool
     */
    private function shouldSendActivity(array $activity): bool
    {
        return $activity["Notified"] !== self::SENT_SKIPPED || $activity["Emailed"] !== self::SENT_SKIPPED;
    }

    /**
     * Set the story for an activity to an excerpt of the original.
     *
     * @param array $activity
     * @param int $length
     * @return array
     */
    public function setStoryExcerpt(array $activity, int $length = self::DEFAULT_EXCERPT_LENGTH): array
    {
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
     * @param array $formPostValues
     * @param string|false $settings The name of the activity type to save.
     * @param array $options
     * - 'Force' -> Bypass the check preventing a user from receiving notifications where the activityUser and userID are the same.
     *
     * @return array|bool|string|null
     * @throws Exception
     */
    public function save($formPostValues, $settings = false, $options = [])
    {
        $preference = $settings;

        $activity = $formPostValues;
        $this->_touch($activity);

        $extraFields = $formPostValues["Ext"] ?? [];
        unset($formPostValues["Ext"]);
        $emailFields = $extraFields["Email"] ?? [];

        if ($activity["ActivityUserID"] == $activity["NotifyUserID"] && !val("Force", $options)) {
            trace("Skipping activity because it would notify the user of something they did.");

            return null; // don't notify users of something they did.
        }

        // Check the user's preference.
        $activity = $this->calculateActivityPreference($activity, $preference ?: null);

        $activity = $this->handleDuplicateActivityForEvent($activity);
        if ($activity === null) {
            trace("Skipping activity because it was a duplicate for the current event.");
            return null;
        }

        $activityType = self::getActivityType($activity["ActivityType"]);
        $activityTypeID = val("ActivityTypeID", $activityType);
        if (!$activityTypeID) {
            trace("There is no $activityType activity type.", TRACE_WARNING);
            $activityType = self::getActivityType("Default");
            $activityTypeID = val("ActivityTypeID", $activityType);
        }

        $activity["ActivityTypeID"] = $activityTypeID;

        $notificationInc = 0;
        if ($activity["NotifyUserID"] > 0 && $activity["Notified"]) {
            $notificationInc = 1;
        }

        // Check to see if we are sharing this activity with another one.
        if ($commentActivityID = val("CommentActivityID", $activity["Data"])) {
            $commentActivity = $this->getID($commentActivityID);
            $activity["Data"]["CommentNotifyUserID"] = $commentActivity["NotifyUserID"];
        }

        // Make sure this activity isn't a duplicate.
        if (val("CheckRecord", $options)) {
            // Check to see if this record already notified so we don't notify multiple times.
            $where = arrayTranslate($activity, ["NotifyUserID", "RecordType", "RecordID"]);
            $where["DateUpdated >"] = Gdn_Format::toDateTime(
                CurrentTimeStamp::getDateTime()
                    ->modify("-2 days")
                    ->getTimestamp()
            ); // index hint

            $checkActivity = $this->SQL->getWhere("Activity", $where)->firstRow();

            if ($checkActivity) {
                return false;
            }
        }

        // Check to share the activity.
        if (val("Share", $options)) {
            $this->share($activity);
        }

        // Group the activity.
        if ($groupBy = val("GroupBy", $options)) {
            $groupBy = (array) $groupBy;
            $where = [];
            foreach ($groupBy as $columnName) {
                $where[$columnName] = $activity[$columnName];
            }
            $where["NotifyUserID"] = $activity["NotifyUserID"];
            // Make sure to only group activities by day.
            $where["DateInserted >"] = Gdn_Format::toDateTime(
                CurrentTimeStamp::getDateTime()
                    ->modify("-1 day")
                    ->getTimestamp()
            );

            // See if there is another activity to group these into.
            $groupActivity = $this->SQL->getWhere("Activity", $where)->firstRow(DATASET_TYPE_ARRAY);

            if ($groupActivity) {
                $groupActivity["Data"] = dbdecode($groupActivity["Data"]);
                $activity = $this->mergeActivities($groupActivity, $activity);
                $notificationInc = 0;
            }
        }

        if ($activity["Emailed"] == self::SENT_PENDING) {
            $emailActivity = $emailFields + $activity;
            $this->email($emailActivity, $options);
            $activity["Emailed"] = $emailActivity["Emailed"];
        }

        $activityData = $activity["Data"];
        if (isset($activity["Data"]) && is_array($activity["Data"])) {
            $activity["Data"] = dbencode($activity["Data"]);
        }

        $this->defineSchema();
        $activity = $this->filterSchema($activity);

        $activityID = val("ActivityID", $activity);
        if (!$activityID) {
            if (!val("DisableFloodControl", $options)) {
                $storageObject = FloodControlHelper::configure($this, "Vanilla", "Activity");
                if ($this->checkUserSpamming(Gdn::session()->UserID, $storageObject)) {
                    return false;
                }
            }

            $this->addInsertFields($activity);
            touchValue("DateUpdated", $activity, $activity["DateInserted"]);

            $this->EventArguments["Activity"] = &$activity;
            $this->EventArguments["ActivityID"] = null;

            $handled = false;
            $this->EventArguments["Handled"] = &$handled;

            $this->fireEvent("BeforeSave");

            if (count($this->validationResults()) > 0) {
                return false;
            }

            if ($handled) {
                $skip = $activity["Skip"] ?? false;
                // A plugin handled this activity so don't save it.
                // If the plugin left a flag to skip sending notifications, return null.
                return $skip ? null : $activity;
            }

            if (val("CheckSpam", $options)) {
                // Check for spam
                $spam = SpamModel::isSpam("Activity", $activity);
                if ($spam) {
                    return SPAM;
                }

                // Check for approval
                $approvalRequired = checkRestriction("Vanilla.Approval.Require");
                if ($approvalRequired && !val("Verified", Gdn::session()->User)) {
                    LogModel::insert("Pending", "Activity", $activity);
                    return UNAPPROVED;
                }
            }

            $activityID = $this->SQL->insert("Activity", $activity);
            $activity["ActivityID"] = $activityID;

            if ($activity["Notified"] === self::SENT_PENDING || $activity["Emailed"] == self::SENT_PENDING) {
                $eventActivity = $this->getID($activityID, DATASET_TYPE_ARRAY) ?? [];
                $event = $this->notificationEventFromRow($eventActivity, NotificationEvent::ACTION_INSERT);

                if ($event instanceof ResourceEvent) {
                    $this->eventDispatcher->dispatch($event);
                }
            }

            $this->prune();
        } else {
            $activity["DateUpdated"] = Gdn_Format::toDateTime();
            unset($activity["ActivityID"]);

            $this->EventArguments["Activity"] = &$activity;
            $this->EventArguments["ActivityID"] = $activityID;
            $this->fireEvent("BeforeSave");

            if (count($this->validationResults()) > 0) {
                return false;
            }

            $this->SQL->put("Activity", $activity, ["ActivityID" => $activityID]);
            $activity["ActivityID"] = $activityID;
        }
        $activity["Data"] = $activityData;

        if (isset($commentActivity)) {
            $commentActivity["Data"]["SharedActivityID"] = $activity["ActivityID"];
            $commentActivity["Data"]["SharedNotifyUserID"] = $activity["NotifyUserID"];
            $this->setField($commentActivity["ActivityID"], "Data", $commentActivity["Data"]);
        }

        if ($notificationInc > 0 && ($notifyUser = $this->userModel->getID($activity["NotifyUserID"]))) {
            $countNotifications = $notifyUser->CountNotifications + $notificationInc;
            $this->userModel->setField($activity["NotifyUserID"], "CountNotifications", $countNotifications);
        }

        // If this is a wall post then we need to notify on that.
        if (val("Name", $activityType) == "WallPost" && $activity["NotifyUserID"] == self::NOTIFY_PUBLIC) {
            $this->notifyWallPost($activity);
        }

        return $activity;
    }

    /**
     * Handle an activity being a duplicate for a particular event.
     *
     * @param array $activity The particular activity record.
     *
     * @return array|null
     */
    private function handleDuplicateActivityForEvent(array $activity): ?array
    {
        $activityEventID = $activity["ActivityEventID"] ?? null;
        if ($activityEventID === null) {
            // Nothing to do if there isn't an eventID.
            return $activity;
        }

        // We should check if the user has already received a notification from this particular event.
        $existingRows = $this->createSql()
            ->select(["Notified", "Emailed", "ActivityID", "Data"])
            ->from($this->getTableName())
            ->where([
                "ActivityEventID" => $activityEventID,
                "NotifyUserID" => $activity["NotifyUserID"],
            ])
            ->get()
            ->resultArray();

        foreach ($existingRows as $existingActivity) {
            $existingActivityID = $existingActivity["ActivityID"];
            $existingData = $existingActivity["Data"] ?? "{}";
            $existingData = json_decode($existingData, true) ?: [];
            $existingReasons = (array) ($existingData["Reason"] ?? null);
            $updatedReasons = $existingReasons;
            $existingHadInApp = $existingActivity["Notified"] !== self::SENT_SKIPPED;
            $existingHadEmail = $existingActivity["Emailed"] !== self::SENT_SKIPPED;

            if ($existingHadInApp) {
                $activity["Notified"] = self::SENT_SKIPPED;
                $updatedReasons[] = $activity["Data"]["Reason"] ?? null;
            }
            if ($existingHadEmail) {
                $activity["Emailed"] = self::SENT_SKIPPED;
                $updatedReasons[] = $activity["Data"]["Reason"] ?? null;
            }

            if (count($updatedReasons) > count($existingReasons)) {
                // Update the existing activity row with a new reason.
                $newData = array_merge($existingData, [
                    "Reason" => implode(", ", array_unique(array_filter($updatedReasons))),
                ]);
                $this->setField($existingActivityID, "Data", dbencode($newData));
            }
        }

        if (!$this->shouldSendActivity($activity)) {
            // We just updated existing activities. No need to insert a new one.
            return null;
        } else {
            return $activity;
        }
    }

    /**
     * Update a single activity's notification field. to reflect a read status.
     *
     * @param int $activityID
     * @param null|int $status
     */
    public function updateNotificationStatusSingle(int $activityID, ?int $status = self::SENT_OK): void
    {
        $this->SQL->put("Activity", ["Notified" => $status], ["ActivityID" => $activityID]);
    }

    /**
     * Update the notification status of a batch of notifications derived from a single activity/activityID.
     *
     * @param $activityOrActivityID
     * @param null|int $status
     */
    public function updateNotificationStatusBatch($activityOrActivityID, ?int $status = self::SENT_OK): void
    {
        $notification = is_numeric($activityOrActivityID)
            ? $this->getID($activityOrActivityID, DATASET_TYPE_ARRAY)
            : (array) $activityOrActivityID;
        $batchID = (int) $status === self::SENT_OK ? betterRandomString(20, "AaO") : null;
        // We never want a "read" status to be undone.
        $whereNotIn = array_merge([self::SENT_OK], [$status]);
        $this->SQL->put(
            "Activity",
            ["Notified" => $status, "BatchID" => $batchID],
            [
                "Notified <>" => $whereNotIn,
                "BatchID" => null,
                "NotifyUserID" => $notification["NotifyUserID"],
                "ParentRecordID" => $notification["ParentRecordID"],
                "ActivityTypeID" => $notification["ActivityTypeID"],
                "RecordType" => $notification["RecordType"],
            ]
        );
    }

    /**
     * LongRunner setting all user notifications to read.
     *
     * User with LongRunner::run* methods.
     *
     * @param int $userID
     * @param int $batchSize
     * @return Generator<int, array|LongRunnerNextArgs>
     */
    public function markAllRead(int $userID, $batchSize = 50): Generator
    {
        $unreadStatus = [self::SENT_PENDING, self::SENT_TOAST, self::SENT_NONE];
        yield new LongRunnerQuantityTotal([$this, "getUserTotalUnread"], [$userID]);

        try {
            do {
                $activities = $this->getWhere(
                    [
                        "NotifyUserID" => $userID,
                        "Notified" => $unreadStatus,
                    ],
                    "",
                    "asc",
                    $batchSize
                )->resultArray();

                foreach ($activities as $activity) {
                    $activityID = $activity["ActivityID"];

                    if (isset($activity["count"]) && $activity["count"] > 1) {
                        $this->updateNotificationStatusBatch($activity);
                    } else {
                        $this->SQL->put(
                            "Activity",
                            ["Notified" => self::SENT_OK],
                            ["ActivityID" => $activity["ActivityID"]]
                        );
                    }
                    yield new LongRunnerSuccessID($activityID);
                }
            } while (count($activities) > 0);
        } catch (LongRunnerTimeoutException $timeoutException) {
            return new LongRunnerNextArgs([$userID]);
        } finally {
            // We need to bypass the cache to get an accurate reading.
            $count = $this->getUserTotal($userID, [
                "Notified" => $unreadStatus,
            ]);
            $this->userModel->setField($userID, "CountNotifications", $count);
        }

        return LongRunner::FINISHED;
    }

    /**
     *
     *
     * @param $oldActivity
     * @param $newActivity
     * @param array $options
     * @return array
     */
    public function mergeActivities($oldActivity, $newActivity, $options = [])
    {
        // Group the two activities together.
        $activityUserIDs = val("ActivityUserIDs", $oldActivity["Data"], []);
        $activityUserCount = val("ActivityUserID_Count", $oldActivity["Data"], 0);
        array_unshift($activityUserIDs, $oldActivity["ActivityUserID"]);
        if (($i = array_search($newActivity["ActivityUserID"], $activityUserIDs)) !== false) {
            unset($activityUserIDs[$i]);
            $activityUserIDs = array_values($activityUserIDs);
        }
        $activityUserIDs = array_unique($activityUserIDs);
        if (count($activityUserIDs) > self::$MaxMergeCount) {
            array_pop($activityUserIDs);
            $activityUserCount++;
        }

        $regardingUserCount = 0;
        if (val("RegardingUserID", $newActivity)) {
            $regardingUserIDs = val("RegardingUserIDs", $oldActivity["Data"], []);
            $regardingUserCount = val("RegardingUserID_Count", $oldActivity["Data"], 0);
            array_unshift($regardingUserIDs, $oldActivity["RegardingUserID"]);
            if (($i = array_search($newActivity["RegardingUserID"], $regardingUserIDs)) !== false) {
                unset($regardingUserIDs[$i]);
                $regardingUserIDs = array_values($regardingUserIDs);
            }
            if (count($regardingUserIDs) > self::$MaxMergeCount) {
                array_pop($regardingUserIDs);
                $regardingUserCount++;
            }
        }

        $recordIDs = [];
        if ($oldActivity["RecordID"]) {
            $recordIDs[] = $oldActivity["RecordID"];
        }
        $recordIDs = array_unique($recordIDs);

        $newActivity = array_merge($oldActivity, $newActivity);

        if (count($activityUserIDs) > 0) {
            $newActivity["Data"]["ActivityUserIDs"] = $activityUserIDs;
        }
        if ($activityUserCount) {
            $newActivity["Data"]["ActivityUserID_Count"] = $activityUserCount;
        }
        if (count($recordIDs) > 0) {
            $newActivity["Data"]["RecordIDs"] = $recordIDs;
        }
        if (isset($regardingUserIDs) && count($regardingUserIDs) > 0) {
            $newActivity["Data"]["RegardingUserIDs"] = $regardingUserIDs;

            if ($regardingUserCount) {
                $newActivity["Data"]["RegardingUserID_Count"] = $regardingUserCount;
            }
        }

        return $newActivity;
    }

    /**
     *  Fires beforeWallNotificationSend event.
     *
     * @param array $activity
     * @param bool $notificationValid
     */
    private function verifyNotification(array $activity, bool &$notificationValid)
    {
        $args = [
            "Activity" => $activity,
            "IsValid" => &$notificationValid,
            "UserModel" => $this->userModel,
        ];
        /** @var EventManager $eventManager */
        $eventManager = Gdn::getContainer()->get(EventManager::class);
        $eventManager->fireFilter("activityModel_beforeWallNotificationSend", $this, $args);
    }

    /**
     * Notify the user of wall comments.
     *
     * @param array $comment
     * @param array $wallPost Activity data.
     */
    protected function notifyWallComment($comment, $wallPost)
    {
        $notifyUser = $this->userModel->getID($wallPost["ActivityUserID"]);

        $activity = [
            "ActivityType" => "WallComment",
            "ActivityUserID" => $comment["InsertUserID"],
            "Format" => $comment["Format"],
            "NotifyUserID" => $wallPost["ActivityUserID"],
            "RecordType" => "ActivityComment",
            "RecordID" => $comment["ActivityCommentID"],
            "RegardingUserID" => $wallPost["ActivityUserID"],
            "Route" => userUrl($notifyUser, ""),
            "Story" => $comment["Body"],
            "HeadlineFormat" => t(
                "HeadlineFormat.NotifyWallComment",
                '{ActivityUserID,User} commented on your <a href="{Url,url}">wall</a>.'
            ),
        ];

        $notificationValid = true;
        $this->verifyNotification($activity, $notificationValid);
        if ($notificationValid) {
            $this->save($activity, "WallComment");
        }
    }

    /**
     *
     *
     * @param $wallPost
     */
    protected function notifyWallPost($wallPost)
    {
        $notifyUser = $this->userModel->getID($wallPost["ActivityUserID"]);

        $activity = [
            "ActivityType" => "WallPost",
            "ActivityUserID" => $wallPost["RegardingUserID"],
            "Format" => $wallPost["Format"],
            "NotifyUserID" => $wallPost["ActivityUserID"],
            "RecordType" => "Activity",
            "RecordID" => $wallPost["ActivityID"],
            "RegardingUserID" => $wallPost["ActivityUserID"],
            "Route" => userUrl($notifyUser, ""),
            "Story" => $wallPost["Story"],
            "HeadlineFormat" => t(
                "HeadlineFormat.NotifyWallPost",
                '{ActivityUserID,User} posted on your <a href="{Url,url}">wall</a>.'
            ),
        ];

        $notificationValid = true;
        $this->verifyNotification($activity, $notificationValid);
        if ($notificationValid) {
            $this->save($activity, "WallComment");
        }
    }

    /**
     * Save all queued activity rows.
     *
     * @param bool $batchEmails Should emails be sent to multiple recipients when possible?
     * @return array
     */
    public function saveQueue()
    {
        $result = [];
        $options = [
            "DisableFloodControl" => true,
        ];

        if (Gdn_Cache::activeEnabled()) {
            // Prefetch users, so they're cached when retrieved in later operations.
            $prefetchUserIDs = [];
            foreach (self::$Queue as $activities) {
                foreach ($activities as $row) {
                    $activity = $row[0] ?? [];
                    $notifyUserID = $activity["NotifyUserID"] ?? null;
                    if ($notifyUserID > 0) {
                        $prefetchUserIDs[$notifyUserID] = true;
                    }
                }
            }
            if (!empty($prefetchUserIDs)) {
                // We don't care about the return values. We just want the records cached.
                $prefetchUserIDs = array_keys($prefetchUserIDs);
                $this->userModel->getDefaultSSOIDs($prefetchUserIDs);
                $this->userModel->getIDs($prefetchUserIDs);
            }
        }

        foreach (self::$Queue as $activities) {
            foreach ($activities as $row) {
                $result[] = $this->save($row[0], false, $options + $row[1]);
            }
        }
        self::$Queue = [];
        return $result;
    }

    /**
     *
     *
     * @param $data
     */
    protected function _touch(&$data)
    {
        touchValue("ActivityType", $data, "Default");
        touchValue("ActivityUserID", $data, Gdn::session()->UserID);
        touchValue("NotifyUserID", $data, self::NOTIFY_PUBLIC);
        touchValue("Headline", $data, null);
        touchValue("Story", $data, null);
        touchValue("Notified", $data, self::SENT_NONE);
        touchValue("Emailed", $data, self::SENT_NONE);
        touchValue("Photo", $data, null);
        touchValue("Route", $data, null);
        if (!isset($data["Data"]) || !is_array($data["Data"])) {
            $data["Data"] = [];
        }
    }

    /**
     * Get the delete after time.
     *
     * @return string Returns a string compatible with {@link strtotime()}.
     */
    public function getPruneAfter()
    {
        return $this->pruneAfter;
    }

    /**
     * Get the exact timestamp to prune.
     *
     * @return DateTime|null Returns the date that we should prune after.
     */
    private function getPruneDate()
    {
        if (!$this->pruneAfter) {
            return null;
        } else {
            $tz = new DateTimeZone("UTC");
            $now = new DateTime("@" . CurrentTimeStamp::get(), $tz);
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
    public function setPruneAfter($pruneAfter)
    {
        if ($pruneAfter) {
            // Make sure the string is negative.
            $now = CurrentTimeStamp::get();
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
    private function prune()
    {
        $date = $this->getPruneDate();

        $this->SQL->delete("Activity", ["DateUpdated <" => Gdn_Format::toDateTime($date->getTimestamp())], 10);
    }

    /**
     * Invoke send on an instance of Gdn_Email and return its status.
     *
     * @param Gdn_Email $email
     * @return integer
     */
    public function sendEmail(Gdn_Email $email): int
    {
        // Send the email.
        try {
            $email->send();
            return self::SENT_OK;
        } catch (\PHPMailer\PHPMailer\Exception $pex) {
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
     * Generate a notification event object, based on a database row.
     *
     * @param array $row
     * @param string $action
     * @return NotificationEvent|null
     */
    private function notificationEventFromRow(array $row, string $action): ?NotificationEvent
    {
        $notifyUserID = $row["NotifyUserID"] ?? 0;
        if ($notifyUserID && $notifyUserID < 1) {
            return null;
        }

        $notifyUser = $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY);
        if (!is_array($notifyUser)) {
            return null;
        }

        $this->calculateRow($row);
        $notification = $this->normalizeNotificationRow($row);

        $notifyUser = $this->userModel->normalizeRow($notifyUser);
        $notification["notifyUsers"] = [$notifyUser];
        $notification = $this->notificationSchema()->validate($notification);

        // Pre-fetch user authentication rows for caching.
        $notifyUserIDs = array_column($notification["notifyUsers"], "userID");
        $this->userModel->getDefaultSSOIDs($notifyUserIDs);

        foreach ($notification["notifyUsers"] as &$currentUser) {
            $currentUser = $this->addUserFragmentFields($currentUser);
        }

        $result = new NotificationEvent($action, ["notification" => $notification]);

        return $result;
    }

    /**
     * Given a user fragment, augment it with helpful fields related to user notifications (e.g. email, SSO IDs).
     *
     * @param array $userFragment
     * @return array
     */
    private function addUserFragmentFields(array $userFragment): array
    {
        $userID = $userFragment["userID"] ?? null;

        if ($userID > 0) {
            $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
            $ssoID = $this->userModel->getDefaultSSOIDs([$userID]);

            $userFragment["email"] = $user["Email"] ?? null;
            $userFragment["ssoID"] = $ssoID[$userID] ?? null;
        } else {
            $userFragment["email"] = null;
            $userFragment["ssoID"] = null;
        }

        return $userFragment;
    }

    /**
     * Given a database row, massage the data into a more externally-useful format.
     *
     * @param array $row
     * @return array
     */
    public function normalizeNotificationRow(array $row): array
    {
        $row["notificationID"] = $row["ActivityID"];
        $row["photoUrl"] = $row["Photo"];
        $row["read"] = $row["Notified"] === ActivityModel::SENT_OK;
        if (!$row["read"]) {
            $row["readUrl"] = self::getReadUrl($row["notificationID"]);
        }
        $row["reason"] = $row["Data"]["Reason"] ?? null;
        if (is_array($row["reason"])) {
            // Some historical records have an array here instead of a CSV.
            $row["reason"] = implode(", ", $row["reason"]);
        }
        $row["activityName"] = $row["ActivityName"];

        $body = formatString($row["Headline"], $row);
        // Replace anchors with bold text until notifications can be spun off from activities.
        $row["body"] = preg_replace("#<a [^>]+>(.+)</a>#Ui", "<strong>$1</strong>", $body);

        $htmlSanitizer = Gdn::getContainer()->get(HtmlSanitizer::class);
        $row["body"] = $htmlSanitizer->filter($row["body"]);

        $scheme = new CamelCaseScheme();
        $result = $scheme->convertArrayKeys($row);
        return $result;
    }

    /**
     * Get url to mark notification as read.
     *
     * @param int $notificationID
     * @return string
     */
    public static function getReadUrl(int $notificationID)
    {
        $urlFormat = "/activity/%d/mark-read-and-redirect";
        $url = sprintf($urlFormat, $notificationID);
        return url($url, true);
    }

    /**
     * Get a schema instance comprised of standard activity fields.
     *
     * @return Schema
     */
    public function notificationSchema(): Schema
    {
        $result = Schema::parse([
            "notificationID" => ["type" => "integer"],
            "notifyUsers?" => [
                "items" => new UserFragmentSchema(),
                "type" => "array",
            ],
            "body" => ["type" => "string"],
            "photoUrl" => [
                "allowNull" => true,
                "type" => "string",
            ],
            "activityName?" => ["type" => "string"],
            "activityType:s?",
            "url" => ["type" => "string"],
            "dateInserted" => ["type" => "datetime"],
            "dateUpdated" => ["type" => "datetime"],
            "read" => ["type" => "boolean"],
            "readUrl?" => ["type" => "string"],
            "count:i?",
            "reason:s?",
        ]);

        return $result;
    }

    /**
     * @inheridoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["markAllRead"];
    }
}
