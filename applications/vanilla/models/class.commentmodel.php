<?php
/**
 * Comment model
 *
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0
 */

use Garden\Events\ResourceEvent;
use Garden\Events\EventFromRowInterface;
use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Psr\SimpleCache\CacheInterface;
use Vanilla\Attributes;
use Vanilla\Community\Schemas\PostFragmentSchema;
use Vanilla\Events\LegacyDirtyRecordTrait;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\FormatFieldTrait;
use Vanilla\Formatting\UpdateMediaTrait;
use Vanilla\ImageSrcSet\ImageSrcSet;
use Vanilla\ImageSrcSet\ImageSrcSetService;
use Vanilla\ImageSrcSet\MainImageSchema;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\SchemaFactory;
use Vanilla\Community\Events\CommentEvent;
use Vanilla\Contracts\Formatting\FormatFieldInterface;
use Vanilla\Site\OwnSite;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\InstanceValidatorSchema;
use Vanilla\Utility\ModelUtils;
use Webmozart\Assert\Assert;
use Vanilla\Search\SearchService;
use Vanilla\Search\SearchTypeQueryExtenderInterface;

/**
 * Manages discussion comments data.
 */
class CommentModel extends Gdn_Model implements FormatFieldInterface, EventFromRowInterface, \Vanilla\Contracts\Models\CrawlableInterface {

    use \Vanilla\FloodControlTrait;

    use UpdateMediaTrait;

    use FormatFieldTrait;

    use LegacyDirtyRecordTrait;

    /** Threshold. */
    const COMMENT_THRESHOLD_SMALL = 1000;

    /** Threshold. */
    const COMMENT_THRESHOLD_LARGE = 50000;

    /** Trigger to recalculate counter. */
    const COUNT_RECALC_MOD = 50;

    /** @var array List of fields to order results by. */
    protected $_OrderBy = [['c.DateInserted', '']];

    /** @var array Wheres. */
    protected $_Where = [];

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var bool */
    public $pageCache;

    /** @var array */
    private $options;

    /**
     * @var CacheInterface Object used to store the FloodControl data.
     */
    protected $floodGate;

    /** @var FormatService */
    private $formatterService;

    /**
     * @var CommentModel $instance;
     */
    private static $instance;

    /** @var UserModel */
    private $userModel;

    /** @var CategoryModel */
    private $categoryModel;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var OwnSite */
    private $ownSite;

    /** @var ImageSrcSetService */
    private $imageSrcSetService;

    /**
     * Class constructor. Defines the related database table name.
     *
     * @param Gdn_Validation $validation The validation dependency.
     */
    public function __construct(Gdn_Validation $validation = null) {
        parent::__construct('Comment', $validation);

        $this->imageSrcSetService = Gdn::getContainer()->get(ImageSrcSetService::class);

        $this->floodGate = FloodControlHelper::configure($this, 'Vanilla', 'Comment');
        $this->pageCache = Gdn::cache()->activeEnabled() && c('Properties.CommentModel.pageCache', false);

        $this->discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
        $this->userModel = Gdn::getContainer()->get(UserModel::class);
        $this->categoryModel = Gdn::getContainer()->get(CategoryModel::class);
        $this->siteSectionModel = Gdn::getContainer()->get(SiteSectionModel::class);
        $this->setFormatterService(Gdn::getContainer()->get(FormatService::class));
        $this->setMediaForeignTable($this->Name);
        $this->setMediaModel(Gdn::getContainer()->get(MediaModel::class));
        $this->setSessionInterface(Gdn::getContainer()->get("Session"));
        $this->ownSite = \Gdn::getContainer()->get(OwnSite::class);

        $this->fireEvent('AfterConstruct');
    }

    /**
     * The shared instance of this object.
     *
     * @return CommentModel Returns the instance.
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new CommentModel();
        }
        return self::$instance;
    }

    /**
     *
     *
     * @param $result
     * @param $pageWhere
     * @param $discussionID
     * @param $page
     * @param null $limit
     */
    public function cachePageWhere($result, $pageWhere, $discussionID, $page, $limit = null) {
        if (!$this->pageCache || !empty($this->_Where) || $this->_OrderBy[0][0] != 'c.DateInserted' || $this->_OrderBy[0][1] == 'desc') {
            return;
        }

        if (count($result) == 0) {
            return;
        }

        $configLimit = c('Vanilla.Comments.PerPage', 30);

        if (!$limit) {
            $limit = $configLimit;
        }

        if ($limit != $configLimit) {
            return;
        }

        if (is_array($pageWhere)) {
            $curr = array_values($pageWhere);
        } else {
            $curr = false;
        }

        $new = [getValueR('0.DateInserted', $result)];

        if (count($result) >= $limit) {
            $new[] = valr(($limit - 1).'.DateInserted', $result);
        }

        if ($curr != $new) {
            trace('CommentModel->CachePageWhere()');

            $cacheKey = "Comment.Page.$limit.$discussionID.$page";
            Gdn::cache()->store($cacheKey, $new, [Gdn_Cache::FEATURE_EXPIRY => 86400]);

            trace($new, $cacheKey);
//         Gdn::controller()->setData('_PageCacheStore', array($CacheKey, $New));
        }
    }

    /**
     * Select the data for a single comment.
     *
     * @since 2.0.0
     * @access public
     *
     * @param bool $fireEvent Kludge to fix VanillaCommentReplies plugin.
     * @param bool $join Whether or not to join in insertUser/updateUser information.
     */
    public function commentQuery($fireEvent = true, $join = true) {
        $this->SQL->select('c.*')
            ->select(['d.CategoryID', 'd.Name as DiscussionName', 'd.Type as DiscussionType'])
            ->join('Discussion d', 'c.DiscussionID = d.DiscussionID')
            ->from('Comment c')
            ->where('d.DiscussionID is not NULL')
            ->where('d.CategoryID is not NUll')
        ;

        $extraSelects = \Gdn::eventManager()->fireFilter("commentModel_extraSelects", []);
        if (!empty($extraSelects)) {
            $this->SQL->select($extraSelects);
        }

        if ($join) {
            $this->SQL
                ->select('iu.Name', '', 'InsertName')
                ->select('iu.Photo', '', 'InsertPhoto')
                ->select('iu.Email', '', 'InsertEmail')
                ->join('User iu', 'c.InsertUserID = iu.UserID', 'left')
                ->select('uu.Name', '', 'UpdateName')
                ->select('uu.Photo', '', 'UpdatePhoto')
                ->select('uu.Email', '', 'UpdateEmail')
                ->join('User uu', 'c.UpdateUserID = uu.UserID', 'left');
        }

        if ($fireEvent) {
            $this->fireEvent('AfterCommentQuery');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($orderFields = '', $orderDirection = 'asc', $limit = false, $pageNumber = false) {
        if (is_numeric($orderFields)) {
            deprecated('CommentModel->get($discussionID, ...)', 'CommentModel->getByDiscussion($discussionID, ...)');
            return $this->getByDiscussion($orderFields, $orderDirection, $limit);
        }

        throw new \BadMethodCallException('CommentModel->get() is not supported.', 400);
    }

    /**
     * Select from the comment table, filling in default options where appropriate.
     *
     * @param array $where The where clause.
     * @param string|array $orderFields The columns to order by.
     * @param string $orderDirection The direction to order by.
     * @param int $limit The database limit.
     * @param int $offset The database offset.
     * @param string $alias A named alias for the Comment table.
     * @return Gdn_SQLDriver Returns SQL driver filled in with the select settings.
     */
    private function select($where = [], $orderFields = '', $orderDirection = 'asc', $limit = 0, $offset = 0, $alias = null) {
        // Setup a clean copy of the SQL object.
        $sql = clone $this->SQL;
        $sql->reset();

        // Build up the basic query, accounting for a potential table name alias.
        $from = $this->Name;
        if ($alias) {
            $from .=  " {$alias}";
        }
        $sql->select('CommentID')
            ->from($from)
            ->where($where);

        // Apply a limit.
        $limit = $limit ?: $this->getDefaultLimit();
        $sql->limit($limit, $offset);

        // Determine which sort fields to apply.
        if ($orderFields) {
            $sql->orderBy($orderFields, $orderDirection);
        } else {
            // Fallback to the configured sort fields on the object.
            foreach ($this->_OrderBy as $defaultOrder) {
                [$field, $dir] = $defaultOrder;
                // Reset any potential table prefixes, if we have an alias.
                if ($alias) {
                    $parts = explode('.', $field);
                    $field = $parts[count($parts) === 1 ? 0 : 1];
                    $field = "{$alias}.{$field}";
                }
                $sql->orderBy($field, $dir);
            }
            unset($parts, $field, $dir, $defaultOrder);
        }

        return $sql;
    }

    /**
     * {@inheritdoc}
     */
    public function getWhere($where = false, $orderFields = '', $orderDirection = 'asc', $limit = false, $offset = false) {
        $where = $this->stripWherePrefixes($where);
        [$where, $options] = $this->splitWhere($where, ['joinUsers' => true, 'joinDiscussions' => false]);

        // Build up an inner select of comments to force late-loading.
        $innerSelect = $this->select($where, $orderFields, $orderDirection, $limit, $offset, 'c3');

        // Add the inner select's parameters to the outer select.
        $this->SQL->mergeParameters($innerSelect);

        $innerSelectSql = $innerSelect->getSelect();
        $result = $this->SQL
            ->from($this->Name.' c')
            ->join("($innerSelectSql) c2", "c.CommentID = c2.CommentID")
            ->get();

        if ($options['joinUsers']) {
            $this->userModel->joinUsers($result, ['InsertUserID', 'UpdateUserID']);
        }

        if ($options['joinDiscussions']) {
            $this->discussionModel->joinDiscussionData($result, 'DiscussionID', $options['joinDiscussions']);
        }
        $this->setCalculatedFields($result);

        return $result;
    }

    /**
     * Get comments for a discussion.
     *
     * @param int $discussionID Which discussion to get comment from.
     * @param int $limit Max number to get.
     * @param int $offset Number to skip.
     * @param array $where Additional conditions to pass when querying comments.
     * @return Gdn_DataSet Returns a list of comments.
     */
    public function getByDiscussion($discussionID, $limit, $offset = 0, array $where = []) {
        $this->commentQuery(true, false);
        $this->EventArguments['DiscussionID'] =& $discussionID;
        $this->EventArguments['Limit'] =& $limit;
        $this->EventArguments['Offset'] =& $offset;
        $this->EventArguments['Where'] =& $where;
        $this->fireEvent('BeforeGet');

        $page = pageNumber($offset, $limit);
        $pageWhere = $this->pageWhere($discussionID, $page, $limit);

        if (empty($where) && $pageWhere) {
            $this->SQL
                ->where('c.DiscussionID', $discussionID);

            $this->SQL->where($pageWhere)->limit($limit + 10);
            $this->orderBy($this->SQL);
        } else {
            // Use a subquery to force late-loading of comments. This optimizes pagination.
            $sql2 = clone $this->SQL;
            $sql2->reset();

            // Using a subquery isn't compatible with Vanilla's named parameter implementation. Manually escape conditions.
            $where = array_merge($where, ['c.DiscussionID' => $discussionID]);
            foreach ($where as $field => &$value) {
                if (filter_var($value, FILTER_VALIDATE_INT)) {
                    continue;
                }
                $value = Gdn::database()->connection()->quote($value);
            }

            $sql2->select('CommentID')
                ->from('Comment c')
                ->where($where, null, true, false)
                ->limit($limit, $offset);
            $this->orderBy($sql2);
            $select = $sql2->getSelect();

            $px = $this->SQL->Database->DatabasePrefix;
            $this->SQL->Database->DatabasePrefix = '';

            $this->SQL->join("($select) c2", "c.CommentID = c2.CommentID");
            $this->SQL->Database->DatabasePrefix = $px;
        }

        $this->where($this->SQL);

        $result = $this->SQL->get();

        $this->userModel->joinUsers($result, ['InsertUserID', 'UpdateUserID']);

        $this->setCalculatedFields($result);

        $this->EventArguments['Comments'] =& $result;
        $this->cachePageWhere($result->result(), $pageWhere, $discussionID, $page, $limit);
        $this->fireEvent('AfterGet');

        return $result;
    }

    /**
     * Get comments for a user.
     *
     * @since 2.0.17
     * @access public
     *
     * @param int $userID Which user to get comments for.
     * @param int $limit Max number to get.
     * @param int $offset Number to skip.
     * @return object SQL results.
     */
    public function getByUser($userID, $limit, $offset = 0) {
        // Get category permissions
        $perms = DiscussionModel::categoryPermissions();

        // Build main query
        $this->commentQuery(true, false);
        $this->fireEvent('BeforeGet');
        $this->SQL
            ->select('d.Name', '', 'DiscussionName')
            ->where('c.InsertUserID', $userID)
            ->orderBy('c.CommentID', 'desc')
            ->limit($limit, $offset);

        // Verify permissions (restricting by category if necessary)
        if ($perms !== true) {
            $this->SQL
                ->join('Category ca', 'd.CategoryID = ca.CategoryID', 'left')
                ->whereIn('d.CategoryID', $perms);
        }

        //$this->orderBy($this->SQL);

        $data = $this->SQL->get();
        $this->userModel->joinUsers($data, ['InsertUserID', 'UpdateUserID']);

        return $data;

    }

    /**
     *
     * Get comments for a user. This is an optimized version of CommentModel->getByUser().
     *
     * @since 2.1
     * @access public
     *
     * @param int $userID Which user to get comments for.
     * @param int $limit Max number to get.
     * @param int $offset Number to skip.
     * @param int|bool $lastCommentID A hint for quicker paging.
     * @param string|null $after Only pull comments following this date.
     * @param string $order Order comments ascending (asc) or descending (desc) by ID.
     * @param string $permission Permission to filter categories by.
     * @return Gdn_DataSet SQL results.
     */
    public function getByUser2($userID, $limit, $offset, $lastCommentID = false, $after = null, $order = 'desc', string $permission = '') {
        // This will load all categories. (do not use unless necessary).
        if (!empty($permission)) {
            $categories = CategoryModel::categories();
            $perms = CategoryModel::filterExistingCategoryPermissions($categories, $permission);
            $perms = array_column($perms, 'CategoryID');
        } else {
            $perms = DiscussionModel::categoryPermissions();
        }

        if (is_array($perms) && empty($perms)) {
            return new Gdn_DataSet([]);
        }

        // The point of this query is to select from one comment table, but filter and sort on another.
        // This puts the paging into an index scan rather than a table scan.
        $this->SQL
            ->select('c2.*')
            ->select('d.Name', '', 'DiscussionName')
            ->select('d.CategoryID')
            ->from('Comment c')
            ->join('Comment c2', 'c.CommentID = c2.CommentID')
            ->join('Discussion d', 'c2.DiscussionID = d.DiscussionID')
            ->where('c.InsertUserID', $userID)
            ->orderBy('c.CommentID', $order);

        if ($after) {
            $this->SQL->where('c.DateInserted >', $after);
        }

        if ($lastCommentID) {
            // The last comment id from the last page was given and can be used as a hint to speed up the query.
            $this->SQL
                ->where('c.CommentID <', $lastCommentID)
                ->limit($limit);
        } else {
            $this->SQL->limit($limit, $offset);
        }
        $this->fireEvent('BeforeGetByUser');
        $data = $this->SQL->get();


        $result =& $data->result();
        $this->LastCommentCount = $data->numRows();
        if (count($result) > 0) {
            $this->LastCommentID = $result[count($result) - 1]->CommentID;
        } else {
            $this->LastCommentID = null;
        }

        // Now that we have th comments we can filter out the ones we don't have permission to.
        if ($perms !== true) {
            $remove = [];

            foreach ($data->result() as $index => $row) {
                if (!in_array($row->CategoryID, $perms)) {
                    $remove[] = $index;
                }
            }

            if (count($remove) > 0) {
                foreach ($remove as $index) {
                    unset($result[$index]);
                }

                $result = array_values($result);
            }
        }

        $this->userModel->joinUsers($data, ['InsertUserID', 'UpdateUserID']);

        $this->EventArguments['Comments'] =& $data;
        $this->fireEvent('AfterGet');

        return $data;
    }

    /**
     * Set model option
     *
     * @param string $option
     * @param mixed $value
     */
    public function setOption(string $option, $value) {
        $this->options[$option] = $value;
    }


    /**
     * Get model option
     *
     * @param string $option
     * @param null $default
     * @return mixed|null
     */
    public function getOption(string $option, $default = null) {
        return $this->options[$option] ?? $default;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultLimit() {
        return c('Vanilla.Comments.PerPage', 30);
    }

    /**
     *
     * Get comments based on specific criteria, optionally filtered by user permissions.
     *
     * @param array $where Conditions for filtering comments with a WHERE clause.
     * @param bool $permissionFilter Filter results by the current user's permissions.
     * @param int|null $limit Max number to get.
     * @param int $offset Number to skip.
     * @param string $order Order comments ascending (asc) or descending (desc) by ID.
     * @param string $sort The column to sort by.
     * @return Gdn_DataSet SQL results.
     */
    public function lookup(array $where = [], $permissionFilter = true, $limit = null, $offset = 0, $order = 'desc', string $sort = 'CommentID') {
        if ($limit === null) {
            $limit = $this->getDefaultLimit();
        }
        $joinDirtyRecords = $where[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;
        if (isset($where[DirtyRecordModel::DIRTY_RECORD_OPT])) {
            unset($where[DirtyRecordModel::DIRTY_RECORD_OPT]);
        }
        $perms = DiscussionModel::categoryPermissions();

        if (is_array($perms) && empty($perms)) {
            return new Gdn_DataSet([]);
        }

        // All fields should be associated with a table. If there isn't one, assign it to comments.
        foreach ($where as $field => $value) {
            if (strpos($field, '.') === false) {
                $where["c.{$field}"] = $value;
                unset($where[$field]);
            }
        }

        $query = $this->SQL;
        // Apply the base of the query.
        $this->commentQuery(false, false);

        if ($sort === 'dateUpdated') {
            $this->orderBy('sortDateUpdated');
            $query->select('c.dateUpdated, c.dateInserted', 'COALESCE', 'sortDateUpdated');
        } else {
            $this->orderBy('c.'.$sort);
        }
        $orderBy = $this->orderBy();
        $query->orderBy($orderBy[0][0], $order);

        if (!empty($where)) {
            $query->where($where);
        }
        if ($limit) {
            $query->limit($limit);
        }
        if ($offset) {
            $query->offset($offset);
        }
        if ($joinDirtyRecords) {
            $this->applyDirtyWheres('c');
        }

        $result = $query->get();
        $data =& $result->result();
        $this->LastCommentCount = $result->numRows();

        // Filter out any comments this user does not have access to.
        if ($permissionFilter && $perms !== true) {
            $remove = [];

            foreach ($result->result() as $index => $row) {
                if (!in_array($row->CategoryID, $perms)) {
                    $remove[] = $index;
                }
            }

            if (count($remove) > 0) {
                foreach ($remove as $index) {
                    unset($data[$index]);
                }

                $data = array_values($data);
            }
        }

        $this->EventArguments['Comments'] =& $result;
        $this->fireEvent('AfterGet');

        return $result;
    }

    /**
     * Notify users of a new comment.
     *
     * @param array $comment
     * @param array $discussion
     */
    private function notifyNewComment(?array $comment, ?array $discussion) {
        if ($comment === null || $discussion === null) {
            return;
        }

        $commentID = $comment["CommentID"] ?? null;
        $discussionID = $discussion["DiscussionID"] ?? null;
        $categoryID = $discussion["CategoryID"] ?? null;

        if ($commentID === null || $discussionID === null || $categoryID === null) {
            return;
        }

        $category = CategoryModel::categories($categoryID);
        if ($category === null) {
            return;
        }

        $body = $comment["Body"] ?? null;
        $discussionUserID = $discussion["InsertUserID"] ?? null;
        $format = $comment["Format"] ?? null;

        // Prepare the notification queue.
        $data = [
            "ActivityType" => "Comment",
            "ActivityUserID" => $comment["InsertUserID"] ?? null,
            "HeadlineFormat" => t(
                "HeadlineFormat.Comment",
                '{ActivityUserID,user} commented on <a href="{Url,html}">{Data.Name,text}</a>'
            ),
            "RecordType" => "Comment",
            "RecordID" => $commentID,
            "Route" => "/discussion/comment/{$commentID}#Comment_{$commentID}",
            "Data" => [
                "Name" => $discussion["Name"] ?? null,
                "Category" => $category["Name"] ?? null,
            ],
            "Ext" => [
                "Email" => [
                    "Format" => $format,
                    "Story" => $body,
                ],
            ],
        ];

        // Pass generic activity to events.
        $this->EventArguments["Activity"] = $data;

        /** @var ActivityModel $activityModel */
        $activityModel = Gdn::getContainer()->get(ActivityModel::class);
        $discussionModel = $this->discussionModel;

        if (!Gdn::config("Vanilla.Email.FullPost")) {
            $data["Ext"]["Email"] = $activityModel->setStoryExcerpt($data["Ext"]["Email"]);
        }

        $notificationGroups = [
            "bookmark" => [
                "notifyUserIDs" => array_column(
                    $discussionModel->getBookmarkUsers($discussionID)->resultArray(),
                    "UserID"
                ),
                "options" => ['CheckRecord' => true],
                "preference" => "BookmarkComment",
            ],
            "mine" => [
                "notifyUserIDs" => [$discussionUserID],
                "preference" => "DiscussionComment",
            ],
            "participated" => [
                "notifyUserIDs" => array_column(
                    $discussionModel->getParticipatedUsers($discussionID)->resultArray(),
                    "UserID"
                ),
                "options" => ['CheckRecord' => true],
                "preference" => "ParticipateComment",
            ],
            "mention" => [
                "headlineFormat" => t(
                    "HeadlineFormat.Mention",
                    '{ActivityUserID,user} mentioned you in <a href="{Url,html}">{Data.Name,text}</a>'
                ),
                "notifyUserIDs" => [],
                "preference" => "Mention",
            ],
        ];

        $mentions = [];
        if (is_string($body) && is_string($format)) {
            $mentions = Gdn::formatService()->parseMentions($body, $format);
            /** @var UserModel $userModel */
            $userModel = $this->userModel;

            foreach ($mentions as $mentionName) {
                $mentionUser = $userModel->getByUsername($mentionName);
                if ($mentionUser) {
                    $notificationGroups["mention"]["notifyUserIDs"][] = $mentionUser->UserID ?? null;
                }
            }
        }

        foreach ($notificationGroups as $group => $groupData) {
            $headlineFormat = $groupData["headlineFormat"] ?? $data["HeadlineFormat"];
            $notifyUserIDs = $groupData["notifyUserIDs"] ?? [];
            $preference = $groupData["preference"] ?? false;
            $options = $groupData["options"] ?? [];

            foreach ($notifyUserIDs as $notifyUserID) {
                if ($notifyUserID === null) {
                    continue;
                }

                // Check user can still see the discussion.
                if (!$discussionModel->canView($discussion, $notifyUserID)) {
                    continue;
                }

                $notification = $data;
                $notification["HeadlineFormat"] = $headlineFormat;
                $notification["NotifyUserID"] = $notifyUserID;
                $notification["Data"]["Reason"] = $group;
                $activityModel->queue($notification, $preference, $options);
            }
        }

        // Record advanced notifications.
        $advancedActivity = $data;
        $advancedActivity["Data"]["Reason"] = "advanced";
        $this->recordAdvancedNotications($activityModel, $advancedActivity, $discussion);
        $isValid = true;
        // Throw an event for users to add their own events.
        $this->EventArguments["Comment"] = $comment;
        $this->EventArguments["Discussion"] = $discussion;
        $this->EventArguments["NotifiedUsers"] = array_keys(ActivityModel::$Queue);
        $this->EventArguments["UserModel"] = $this->userModel;
        $this->EventArguments["IsValid"] = &$isValid;
        $this->EventArguments["MentionedUsers"] = $mentions;
        $this->EventArguments["ActivityModel"] = $activityModel;
        $this->fireEvent("BeforeNotification");

        if (!$isValid) {
            return ;
        }
        if (\Vanilla\FeatureFlagHelper::featureEnabled("deferredNotifications")) {
            // Queue sending notifications.
            /** @var Vanilla\Scheduler\SchedulerInterface $scheduler */
            $scheduler = Gdn::getContainer()->get(Vanilla\Scheduler\SchedulerInterface::class);
            $scheduler->addJob(ExecuteActivityQueue::class);
        } else {
            // Send all notifications.
            $activityModel->saveQueue();
        }
    }

    /**
     * Set the order of the comments or return current order.
     *
     * Getter/setter for $this->_OrderBy.
     *
     * @since 2.0.0
     * @access public
     *
     * @param mixed $value Field name(s) to order results by. May be a string or array of strings.
     * @return array $this->_OrderBy (optionally).
     */
    public function orderBy($value = null) {
        if ($value === null) {
            return $this->_OrderBy;
        }

        if (is_string($value)) {
            $value = [$value];
        }

        if (is_array($value)) {
            // Set the order of this object.
            $orderBy = [];

            foreach ($value as $part) {
                if (stringEndsWith($part, ' desc', true)) {
                    $orderBy[] = [substr($part, 0, -5), 'desc'];
                } elseif (stringEndsWith($part, ' asc', true))
                    $orderBy[] = [substr($part, 0, -4), 'asc'];
                else {
                    $orderBy[] = [$part, 'asc'];
                }
            }
            $this->_OrderBy = $orderBy;
        } elseif (is_a($value, 'Gdn_SQLDriver')) {
            // Set the order of the given sql.
            foreach ($this->_OrderBy as $parts) {
                $value->orderBy($parts[0], $parts[1]);
            }
        }
    }

    public function pageWhere($discussionID, $page, $limit) {
        if (!$this->pageCache || !empty($this->_Where) || $this->_OrderBy[0][0] != 'c.DateInserted' || $this->_OrderBy[0][1] == 'desc') {
            return false;
        }

        if ($limit != c('Vanilla.Comments.PerPage', 30)) {
            return false;
        }

        $cacheKey = "Comment.Page.$limit.$discussionID.$page";
        $value = Gdn::cache()->get($cacheKey);
        trace('CommentModel->PageWhere()');
        trace($value, $cacheKey);
//      Gdn::controller()->setData('_PageCache', array($CacheKey, $Value));
        if ($value === false) {
            return false;
        } elseif (is_array($value)) {
            $result = ['DateInserted >=' => $value[0]];
            if (isset($value[1])) {
                $result['DateInserted <='] = $value[1];
            }
            return $result;
        }
        return false;
    }

    /**
     * Sets the UserComment Score value.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $commentID Unique ID of comment we're setting the score for.
     * @param int $userID Unique ID of user scoring the comment.
     * @param int $score Score being assigned to the comment.
     * @return int New total score for the comment.
     */
    public function setUserScore($commentID, $userID, $score) {
        // Insert or update the UserComment row
        $this->SQL->replace(
            'UserComment',
            ['Score' => $score],
            ['CommentID' => $commentID, 'UserID' => $userID]
        );

        // Get the total new score
        $totalScore = $this->SQL->select('Score', 'sum', 'TotalScore')
            ->from('UserComment')
            ->where('CommentID', $commentID)
            ->get()
            ->firstRow()
            ->TotalScore;

        // Update the comment's cached version
        $this->SQL->update('Comment')
            ->set('Score', $totalScore)
            ->where('CommentID', $commentID)
            ->put();

        return $totalScore;
    }

    /**
     * Gets the UserComment Score value for the specified user.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $commentID Unique ID of comment we're getting the score for.
     * @param int $userID Unique ID of user who scored the comment.
     * @return int Current score for the comment.
     */
    public function getUserScore($commentID, $userID) {
        $data = $this->SQL->select('Score')
            ->from('UserComment')
            ->where('CommentID', $commentID)
            ->where('UserID', $userID)
            ->get()
            ->firstRow();

        return $data ? $data->Score : 0;
    }

    /**
     * Record the user's watch data.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object $discussion Discussion being watched.
     * @param int $limit Max number to get.
     * @param int $offset Number to skip.
     * @param int $totalComments Total in entire discussion (hard limit).
     * @param string|null $maxDateInserted The most recent insert date of the viewed comments.
     * @deprecated Use `DiscussionModel::setWatch()` instead.
     */
    public function setWatch($discussion, $limit, $offset, $totalComments, $maxDateInserted = null) {
        deprecated('CommentModel::setWatch()', 'DiscussionModel::setWatch()');

        /* @var DiscussionModel $discussionModel */
        $this->discussionModel->setWatch($discussion, $limit, $offset, $totalComments, $maxDateInserted);
    }

    /**
     * {@inheritdoc}
     */
    public function getCount($wheres = '') {
        if (is_numeric($wheres)) {
            deprecated('CommentModel->getCount(int)', 'CommentModel->getCountByDiscussion()');
            return $this->getCountByDiscussion($wheres);
        }

        return parent::getCount($wheres);
    }

    /**
     * Get a schema instance comprised of standard comment fields.
     *
     * @return Schema
     */
    public function schema(): Schema {
        $result = Schema::parse([
            'commentID:i' => 'The ID of the comment.',
            'discussionID:i' => 'The ID of the discussion.',
            'discussionCollapseID:s?',
            'name:s?' => [
                'description' => 'The name of the comment',
                'x-localize' => true,
            ],
            'categoryID:i?' => 'The ID of the category of the comment',
            'body:s?' => [
                'description' => 'The body of the comment.',
            ],
            'bodyRaw:s?',
            'bodyPlainText:s?' => [
                'description' => 'The body of the comment in plain text.',
                'x-localize' => true,
            ],
            'dateInserted:dt' => 'When the comment was created.',
            'dateUpdated:dt|n' => 'When the comment was last updated.',
            'insertUserID:i' => 'The user that created the comment.',
            'updateUserID:i|n',
            'score:i|n' => 'Total points associated with this post.',
            'insertUser?' => SchemaFactory::get(UserFragmentSchema::class, "UserFragment"),
            'url:s?' => 'The full URL to the comment.',
            'labelCodes:a?' => ['items' => ['type' => 'string']],
            'type:s?' => 'Record type for search drivers.',
            'format:s?' => 'The format of the comment',
            'groupID:i?' => [
                'x-null-value' => -1,
            ],
            'image?' => new MainImageSchema(),
        ]);
        return $result;
    }

    /**
     * Count total comments in a discussion specified by ID.
     *
     * Events: BeforeGetCount
     *
     * @param int $discussionID Unique ID of discussion we're counting comments from.
     * @return object SQL result.
     */
    public function getCountByDiscussion($discussionID) {
        $this->fireEvent('BeforeGetCount');

        if (!empty($this->_Where)) {
            return false;
        }

        return $this->SQL->select('CommentID', 'count', 'CountComments')
            ->from('Comment')
            ->where('DiscussionID', $discussionID)
            ->get()
            ->firstRow()
            ->CountComments;
    }

    /**
     * Count total comments in a discussion specified by $where conditions.
     *
     * @param array|false $where Conditions
     * @return object SQL result.
     */
    public function getCountWhere($where = false) {
        if (is_array($where)) {
            $this->SQL->where($where);
        }

        return $this->SQL->select('CommentID', 'count', 'CountComments')
            ->from('Comment')
            ->get()
            ->firstRow()
            ->CountComments;
    }

    /**
     * Get single comment by ID. Allows you to pick data format of return value.
     *
     * @param int $id Unique ID of the comment.
     * @param string $datasetType Format to return comment in.
     * @param array $options options to pass to the database.
     * @return mixed SQL result in format specified by $resultType.
     */
    public function getID($id, $datasetType = DATASET_TYPE_OBJECT, $options = []) {
        $this->options($options);

        $this->commentQuery(false); // FALSE supresses FireEvent
        $comment = $this->SQL
            ->where('c.CommentID', $id)
            ->get()
            ->firstRow($datasetType);

        if ($comment) {
            $this->calculate($comment);
        }
        return $comment;
    }

    /**
     * Get single comment by ID as SQL result data.
     *
     * @param int $commentID Unique ID of the comment.
     * @param array $options
     * @return Gdn_DataSet SQL result.
     */
    public function getIDData($commentID, $options = []) {
        $this->fireEvent('BeforeGetIDData');
        $this->commentQuery(false); // FALSE supresses FireEvent
        $this->options($options);

        return $this->SQL
            ->where('c.CommentID', $commentID)
            ->get();
    }

    /**
     * Get comments in a discussion since the specified one.
     *
     * Events: BeforeGetNew
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $discussionID Unique ID of the discusion.
     * @param int $lastCommentID Unique ID of the comment.
     * @return object SQL result.
     */
    public function getNew($discussionID, $lastCommentID) {
        $this->commentQuery();
        $this->fireEvent('BeforeGetNew');
        $this->orderBy($this->SQL);
        $comments = $this->SQL
            ->where('c.DiscussionID', $discussionID)
            ->where('c.CommentID >', $lastCommentID)
            ->get();

        $this->setCalculatedFields($comments);
        return $comments;
    }

    /**
     * Gets the offset of the specified comment in its related discussion.
     *
     * Events: BeforeGetOffset
     *
     * @since 2.0.0
     * @access public
     *
     * @param mixed $comment Unique ID or or a comment object for which the offset is being defined.
     * @return object SQL result.
     */
    public function getOffset($comment) {
        $this->fireEvent('BeforeGetOffset');

        if (is_numeric($comment)) {
            $comment = $this->getID($comment);
        }

        $this->SQL
            ->select('c.CommentID', 'count', 'CountComments')
            ->from('Comment c')
            ->where('c.DiscussionID', val('DiscussionID', $comment));

        $this->SQL->beginWhereGroup();

        // Figure out the where clause based on the sort.
        foreach ($this->_OrderBy as $part) {
            //$Op = count($this->_OrderBy) == 1 || isset($PrevWhere) ? '=' : '';
            [$expr, $value] = $this->_WhereFromOrderBy($part, $comment, '');

            if (!isset($prevWhere)) {
                $this->SQL->where($expr, $value);
            } else {
                $this->SQL->orOp();
                $this->SQL->beginWhereGroup();
                $this->SQL->orWhere($prevWhere[0], $prevWhere[1]);
                $this->SQL->where($expr, $value);
                $this->SQL->endWhereGroup();
            }

            $prevWhere = $this->_WhereFromOrderBy($part, $comment, '==');
        }

        $this->SQL->endWhereGroup();

        return $this->SQL
            ->get()
            ->firstRow()
            ->CountComments;
    }

    /**
     * @deprecated since 2.4
     *
     * @param $discussionID
     * @param null $userID
     * @return int
     */
    public function getUnreadOffset($discussionID, $userID = null) {
        deprecated(__METHOD__);

        if ($userID == null) {
            $userID = Gdn::session()->UserID;
        }
        if ($userID == 0) {
            return 0;
        }

        // See of the user has read the discussion.
        $userDiscussion = $this->SQL->getWhere('UserDiscussion', ['DiscussionID' => $discussionID, 'UserID' => $userID])->firstRow(DATASET_TYPE_ARRAY);
        if (empty($userDiscussion)) {
            return 0;
        }

        return $userDiscussion['CountComments'];
    }

    /**
     * Builds Where statements for GetOffset method.
     *
     * @since 2.0.0
     * @access protected
     * @see CommentModel::getOffset()
     *
     * @param array $part Value from $this->_OrderBy.
     * @param object $comment
     * @param string $op Comparison operator.
     * @return array Expression and value.
     */
    protected function _WhereFromOrderBy($part, $comment, $op = '') {
        if (!$op || $op == '=') {
            $op = ($part[1] == 'desc' ? '>' : '<').$op;
        } elseif ($op == '==')
            $op = '=';

        $expr = $part[0].' '.$op;
        if (preg_match('/c\.(\w*\b)/', $part[0], $matches)) {
            $field = $matches[1];
        } else {
            $field = $part[0];
        }
        $value = val($field, $comment);
        if (!$value) {
            $value = 0;
        }

        return [$expr, $value];
    }

    /**
     * Insert or update core data about the comment.
     *
     * Events: BeforeSaveComment, AfterValidateComment, AfterSaveComment.
     *
     * @param array $formPostValues Data from the form model.
     * @param array|false $settings Currently unused.
     * @return int $commentID
     * @since 2.0.0
     */
    public function save($formPostValues, $settings = false) {
        // Define the primary key in this model's table.
        $this->defineSchema();

        // Validate $CommentID and whether this is an insert
        $commentID = val('CommentID', $formPostValues);
        $commentID = is_numeric($commentID) && $commentID > 0 ? $commentID : false;
        $insert = $commentID === false;
        if ($insert) {
            $this->addInsertFields($formPostValues);
        } else {
            $this->addUpdateFields($formPostValues);
        }

        if ($insert || isset($formPostValues['Body'])) {
            // Apply body validation rules.
            $this->Validation->applyRule('Body', 'Required');
            $this->Validation->addRule('MeAction', 'function:ValidateMeAction');
            $this->Validation->applyRule('Body', 'MeAction');
            $maxCommentLength = Gdn::config('Vanilla.Comment.MaxLength');
            if (is_numeric($maxCommentLength) && $maxCommentLength > 0) {
                $this->Validation->setSchemaProperty('Body', 'maxPlainTextLength', $maxCommentLength);
                $this->Validation->applyRule('Body', 'plainTextLength');
            }
            $minCommentLength = c('Vanilla.Comment.MinLength');
            if ($minCommentLength && is_numeric($minCommentLength)) {
                $this->Validation->setSchemaProperty('Body', 'MinTextLength', $minCommentLength);
                $this->Validation->applyRule('Body', 'MinTextLength');
            }
        } else {
            $this->Validation->unapplyRule('Body');
        }

        $isValidUser = true;
        // Prep and fire event
        $this->EventArguments['FormPostValues'] = &$formPostValues;
        $this->EventArguments['CommentID'] = $commentID;
        $this->EventArguments['IsValid'] = &$isValidUser;
        $this->EventArguments['UserModel'] = $this->userModel;
        $this->fireEvent('BeforeSaveComment');

        // Validate the form posted values
        if ($this->validate($formPostValues, $insert)) {
            $prevDiscussionID = false;

            // Backward compatible check for flood control
            if (!val('SpamCheck', $this, true)) {
                deprecated('DiscussionModel->SpamCheck attribute', 'FloodControlTrait->setFloodControlEnabled()');
                $this->setFloodControlEnabled(false);
            }

            // If the post is new and it validates, check for spam
            if (!$insert || !$this->checkUserSpamming(Gdn::session()->UserID, $this->floodGate)) {
                $fields = $this->Validation->schemaValidationFields();
                unset($fields[$this->PrimaryKey]);
                if (!isset($fields['InsertUserID']) || !isset($fields['DateInserted'])) {
                    $comment = $this->getID($commentID, DATASET_TYPE_ARRAY);
                    $insertUserID = $comment['InsertUserID'] ?? null;
                    $dateInserted = $comment['DateInserted'] ?? null;
                } else {
                    $insertUserID = $fields['InsertUserID'];
                    $dateInserted = $fields['DateInserted'];
                }

                $commentData = $commentID ?
                    array_merge($fields, ['CommentID' => $commentID, 'InsertUserID' => $insertUserID, 'DateInserted' => $dateInserted]) :
                    $fields;
                // Check for spam
                $spam = SpamModel::isSpam('Comment', $commentData);
                if ($spam) {
                    return SPAM;
                }

                $isValid = true;
                $invalidReturnType = false;
                $this->EventArguments['CommentData'] = $commentData;
                $this->EventArguments['IsValid'] = &$isValid;
                $this->EventArguments['InvalidReturnType'] = &$invalidReturnType;
                $this->fireEvent('AfterValidateComment');

                if (!$isValid) {
                    return $invalidReturnType;
                }

                // Make sure the discussion actually exists (https://github.com/vanilla/vanilla-patches/issues/716).
                if (isset($formPostValues['DiscussionID'])) {
                    $discussion = $this->discussionModel->getID($formPostValues['DiscussionID']);
                    if (!$discussion) {
                        throw new NotFoundException('Discussion');
                    }
                }

                if ($insert === false) {
                    // Fetch the discussion's data before we save, for comparison's sake.
                    $previousDiscussion = $this->getID($commentID, DATASET_TYPE_ARRAY);
                    $prevDiscussionID = $previousDiscussion['DiscussionID'] ?? false;

                    // Log the save.
                    LogModel::logChange('Edit', 'Comment', array_merge($fields, ['CommentID' => $commentID]));

                    if (c('Garden.ForceInputFormatter')) {
                        $fields['Format'] = Gdn::config('Garden.InputFormatter', '');
                    }

                    // Save the new value.
                    $this->serializeRow($fields);
                    $this->SQL->put($this->Name, $fields, ['CommentID' => $commentID]);
                } else {
                    // Make sure that the comments get formatted in the method defined by Garden.
                    if (!val('Format', $fields) || c('Garden.ForceInputFormatter')) {
                        $fields['Format'] = Gdn::config('Garden.InputFormatter', '');
                    }

                    // Check for approval
                    $approvalRequired = checkRestriction('Vanilla.Approval.Require');
                    if ($approvalRequired && !val('Verified', Gdn::session()->User)) {
                        $discussionModel = $this->discussionModel;
                        $discussion = $discussionModel->getID(val('DiscussionID', $fields));
                        $fields['CategoryID'] = val('CategoryID', $discussion);
                        LogModel::insert('Pending', 'Comment', $fields);
                        return UNAPPROVED;
                    }

                    // Create comment.
                    $this->serializeRow($fields);
                    $commentID = $this->SQL->insert($this->Name, $fields);
                }
                if ($commentID) {
                    $bodyValue = $fields["Body"] ?? null;
                    if ($bodyValue) {
                        $this->calculateMediaAttachments($commentID, !$insert);
                    }

                    $this->EventArguments['CommentID'] = $commentID;
                    $this->EventArguments['Insert'] = $insert;

                    // IsNewDiscussion is passed when the first comment for new discussions are created.
                    $this->EventArguments['IsNewDiscussion'] = val('IsNewDiscussion', $formPostValues);
                    $this->fireEvent('AfterSaveComment');
                }
            }

            // Update discussion's comment count.
            if (isset($formPostValues['DiscussionID']) && $isValidUser) {
                // If we have a previous discussion ID & it's different from the current one, it's been changed.
                $discussionIDChanged = $prevDiscussionID && ($formPostValues['DiscussionID'] !== $prevDiscussionID);
                if ($insert || !$discussionIDChanged) {
                    $this->updateCommentCount($formPostValues['DiscussionID'], ['Slave' => false]);
                } else {
                    $newDiscussion = $this->discussionModel->getID($formPostValues['DiscussionID'], DATASET_TYPE_ARRAY);
                    $this->incrementCountsMovedComment($commentData, $previousDiscussion, $newDiscussion);
                }
            }
        }
        $comment = $commentID ? $this->getID($commentID, DATASET_TYPE_ARRAY) : false;
        if ($comment) {
            $commentEvent = $this->eventFromRow(
                $comment,
                $insert ? CommentEvent::ACTION_INSERT : CommentEvent::ACTION_UPDATE,
                $this->userModel->currentFragment()
            );
            $this->getEventManager()->dispatch($commentEvent);
        }
        return $commentID;
    }

    /**
     * Increments count values for the discussion to which a comment has recently been moved to.
     * Decrement count values  for the discussion from which a comment was removed from.
     *
     * @param array $comment
     * @param array $prevDiscussion
     * @param array $newDiscussion
     */
    private function incrementCountsMovedComment(array $comment, array $prevDiscussion, array $newDiscussion): void {
        $prevDiscussionID = $prevDiscussion['DiscussionID'] ?? null;
        $newDiscussionID = $comment['DiscussionID'] ?? null;
        Assert::notNull($prevDiscussionID, "Expected \$prevDiscussion['DiscussionID']");
        Assert::notNull($newDiscussionID, "Expected \$comment['DiscussionID']");

        $categoryChanged = $prevDiscussion['CategoryID'] != $newDiscussion['CategoryID'];
        $this->discussionModel->adjustLastComment($newDiscussion, $comment, 1, $categoryChanged);
        $this->discussionModel->adjustLastComment($prevDiscussion, $comment, -1, $categoryChanged);

        if ($categoryChanged) {
            // We've already adjusted the aggregate counts, but we didn't update the normal counts.
            $this->categoryModel->setField($prevDiscussion['CategoryID'], 'CountComments-', 1);
            $this->categoryModel->setField($newDiscussion['CategoryID'], 'CountComments+', 1);
        }
    }

    /**
     * Generate a comment event object, based on a database row.
     *
     * @param array $row
     * @param string $action
     * @param array|object|null $sender
     * @return CommentEvent
     */
    public function eventFromRow(array $row, string $action, $sender = null): ResourceEvent {
        $this->userModel->expandUsers($row, ["InsertUserID"]);
        $row = $this->addDiscussionData($row);
        $comment = $this->normalizeRow($row);
        $out = $this->schema()->merge(Schema::parse(['discussion:o' => SchemaFactory::get(PostFragmentSchema::class, "PostFragment")]));
        $comment = $out->validate($comment);

        if ($sender) {
            $senderSchema = new UserFragmentSchema();
            $sender = $senderSchema->validate($sender);
        }

        $result = new CommentEvent(
            $action,
            ["comment" => $comment],
            $sender
        );
        return $result;
    }

    /**
     * Given a database row, massage the data into a more externally-useful format.
     *
     * @param array $row
     * @param array|string|bool $expand Expand fields.
     *
     * @return array
     */
    public function normalizeRow(array $row, $expand = []): array {
        $rawBody = $row['Body'];
        $format = $row['Format'];
        $this->formatField($row, "Body", $row["Format"]);
        $row['Name'] = self::generateCommentName($row["DiscussionName"]);
        $row['Url'] = commentUrl($row);
        $row['Attributes'] = new Attributes($row['Attributes'] ?? null);
        $row['InsertUserID'] = $row['InsertUserID'] ?? 0;
        $row['DateInserted'] = $row['DateInserted'] ?? $row['DateUpdated'] ?? new DateTime();
        $scheme = new CamelCaseScheme();
        $result = $scheme->convertArrayKeys($row);
        if (ModelUtils::isExpandOption(ModelUtils::EXPAND_CRAWL, $expand)) {
            $result['recordCollapseID'] = "site{$this->ownSite->getSiteID()}_discussion{$result['discussionID']}";
            $result['excerpt'] = $this->formatterService->renderExcerpt($rawBody, $format);
            $result['image'] = $this->formatterService->parseImageUrls($rawBody, $format)[0] ?? null;
            $result['bodyPlainText'] = \Gdn::formatService()->renderPlainText($rawBody, $format);
            $result['scope'] = $this->categoryModel->getRecordScope($row['CategoryID']);
            $result['score'] = $row['Score'] ?? 0;
            $siteSection = $this->siteSectionModel
                ->getSiteSectionForAttribute('allCategories', $row['CategoryID']);
            $result['locale'] = $siteSection->getContentLocale();
            $searchService = Gdn::getContainer()->get(SearchService::class);
            /** @var SearchTypeQueryExtenderInterface $extender */
            foreach ($searchService->getExtenders() as $extender) {
                $extender->extendRecord($result, 'comment');
            }
        }

        // Get the comment's parsed content's first image & get the srcset for it.
        $result['image'] = $this->formatterService->parseMainImage($rawBody, $format);

        return $result;
    }

    /**
     * Generate a comment name from a discussion name. This will return 'Untitled' if passed a null value.
     *
     * @param string|null $discussionName
     * @return string
     */
    public static function generateCommentName(?string $discussionName): string {
        return sprintf(t('Re: %s'), $discussionName ?? t('Untitled'));
    }

    /**
     * Update the attachment status of attachemnts in particular comment.
     *
     * @param int $commentID The ID of the comment.
     * @param bool $isUpdate Whether or not we are updating an existing comment.
     */
    private function calculateMediaAttachments(int $commentID, bool $isUpdate) {
        $commentRow = $this->getID($commentID, DATASET_TYPE_ARRAY);
        if ($commentRow) {
            if ($isUpdate) {
                $this->flagInactiveMedia($commentID, $commentRow["Body"], $commentRow["Format"]);
            }
            $this->refreshMediaAttachments($commentID, $commentRow["Body"], $commentRow["Format"]);
        }
    }

    /**
     * Insert or update meta data about the comment.
     *
     * Updates unread comment totals, bookmarks, and activity. Sends notifications.
     *
     * @param int $commentID Unique ID for this comment.
     * @param int $insert Used as a boolean for whether this is a new comment.
     * @param bool $checkExisting Not used.
     * @param bool $incUser Whether or not to just increment the user's comment count rather than recalculate it.
     * @since 2.0.0
     * @access public
     */
    public function save2($commentID, $insert, $checkExisting = true, $incUser = false) {
        $session = Gdn::session();

        // Load comment data
        $fields = $this->getID($commentID, DATASET_TYPE_ARRAY);

        // Clear any session stashes related to this discussion
        $discussionModel = $this->discussionModel;
        $discussionID = $fields['DiscussionID'] ?? null;
        $discussion = $discussionModel->getID($discussionID);
        $session->setPublicStash('CommentForForeignID_'.getValue('ForeignID', $discussion), null);

        // Make a quick check so that only the user making the comment can make the notification.
        // This check may be used in the future so should not be depended on later in the method.
        $validController = Gdn::controller() instanceof Gdn_Controller;
        if ($validController && Gdn::controller()->deliveryType() === DELIVERY_TYPE_ALL && $fields['InsertUserID'] != $session->UserID) {
            return;
        }

        // Update the discussion author's CountUnreadDiscussions (ie.
        // the number of discussions created by the user that s/he has
        // unread messages in) if this comment was not added by the
        // discussion author.
        $this->updateUser($fields['InsertUserID'], $incUser && $insert);

        // Mark the user as participated and update DateLastViewed.
        $this->SQL->replace(
            'UserDiscussion',
            ['Participated' => 1, 'DateLastViewed' => $fields['DateInserted']],
            ['DiscussionID' => $discussionID, 'UserID' => val('InsertUserID', $fields)]
        );

        if ($insert) {
            CategoryModel::instance()->incrementLastComment($fields);
            $this->notifyNewComment(
                $fields ? (array)$fields : null,
                $discussion ? (array)$discussion : null
            );
        }
    }

    /**
     * Record advanced notifications for users.
     *
     * @param ActivityModel $activityModel
     * @param array $activity
     * @param array $discussion
     * @param array $NotifiedUsers
     */
    public function recordAdvancedNotications($activityModel, $activity, $discussion) {
        if (is_numeric($discussion)) {
            $discussion = $this->getID($discussion);
        }

        $categoryID = val('CategoryID', $discussion);

        // Make sure the category actually exists.
        $category = CategoryModel::categories($categoryID);
        if (!$category) {
            return;
        }

        // Grab all of the users that need to be notified.
        $data = $this->SQL
            ->whereIn('Name', ['Preferences.Email.NewComment.'.$category['CategoryID'], 'Preferences.Popup.NewComment.'.$category['CategoryID']])
            ->get('UserMeta')->resultArray();

        $notifyUsers = [];
        foreach ($data as $row) {
            if (!$row['Value']) {
                continue;
            }

            $userID = $row['UserID'];
            // Check user can still see the discussion.
            $discussionModel = $this->discussionModel;

            if (!Gdn::config(CategoryModel::CONF_CATEGORY_FOLLOWING) &&
                !$this->userModel->checkPermission($userID, 'Garden.AdvancedNotifications.Allow')
            ) {
                continue;
            }

            if (!$discussionModel->canView($discussion, $userID)) {
                continue;
            }

            $name = $row['Name'];
            if (str_contains($name, '.Email.')) {
                $notifyUsers[$userID]['Emailed'] = ActivityModel::SENT_PENDING;
            } elseif (str_contains($name, '.Popup.')) {
                $notifyUsers[$userID]['Notified'] = ActivityModel::SENT_PENDING;
            }
        }

        foreach ($notifyUsers as $userID => $prefs) {
            $activity['NotifyUserID'] = $userID;
            $activity['Emailed'] = val('Emailed', $prefs, false);
            $activity['Notified'] = val('Notified', $prefs, false);
            $activityModel->queue($activity);
        }
    }

    public function removePageCache($discussionID, $from = 1) {
        if (!$this->pageCache) {
            return;
        }

        $countComments = $this->SQL->getWhere('Discussion', ['DiscussionID' => $discussionID])->value('CountComments');
        $limit = c('Vanilla.Comments.PerPage', 30);
        $pageCount = pageNumber($countComments, $limit) + 1;

        for ($page = $from; $page <= $pageCount; $page++) {
            $cacheKey = "Comment.Page.$limit.$discussionID.$page";
            Gdn::cache()->remove($cacheKey);
        }
    }

    /**
     * Updates the CountComments value on the discussion based on the CommentID being saved.
     *
     * Events: BeforeUpdateCommentCount.
     *
     * @param array|int $discussion
     * @param array $options
     *
     * @since 2.0.0
     * @access public
     *
     * @since 2.3 Added the $options parameter.
     */
    public function updateCommentCount($discussion, $options = []) {
        // Get the discussion.
        if (is_numeric($discussion)) {
            $this->options($options);
            $discussion = $this->SQL->getWhere('Discussion', ['DiscussionID' => $discussion])->firstRow(DATASET_TYPE_ARRAY);
        }
        $discussionID = $discussion['DiscussionID'] ?? null;

        $this->fireEvent('BeforeUpdateCommentCountQuery');

        $this->options($options);

        $sql = clone $this->SQL;
        $sql->reset();

        $firstComment = $sql
            ->orderBy(['DateInserted', 'CommentID'])
            ->limit(1)
            ->getWhere('Comment', ['DiscussionID' => $discussionID])->firstRow(DATASET_TYPE_ARRAY);

        $lastComment = $sql
            ->orderBy(['-DateInserted', '-CommentID'])
            ->limit(1)
            ->getWhere('Comment', ['DiscussionID' => $discussionID])->firstRow(DATASET_TYPE_ARRAY);

        $data = [
            'FirstCommentID' => $firstComment['CommentID'] ?? false,
            'LastCommentID' => $lastComment['CommentID'] ?? false,
            'LastCommentUserID' => $lastComment['InsertUserID'] ?? false,
            'DateLastComment' => $lastComment['DateInserted'] ?? false,
            'CountComments' => $this->getCount(['DiscussionID' => $discussionID])
        ];

        $this->EventArguments['Discussion'] =& $discussion;
        $this->EventArguments['Counts'] =& $data;
        $this->fireEvent('BeforeUpdateCommentCount');

        if ($discussion) {
            if ($data && $data['CountComments'] !== 0) {
                $this->SQL->update('Discussion');
                if (!$discussion['Sink'] && $data['DateLastComment']) {
                    $this->SQL->set('DateLastComment', $data['DateLastComment']);
                } elseif (!$data['DateLastComment']) {
                    $this->SQL->set('DateLastComment', $discussion['DateInserted']);
                }

                $this->SQL
                    ->set('FirstCommentID', $data['FirstCommentID'])
                    ->set('LastCommentID', $data['LastCommentID'])
                    ->set('CountComments', $data['CountComments'])
                    ->set('hot', ($discussion['Score'] ?? 0) + ($data['CountComments'] ?? 0))
                    ->where('DiscussionID', $discussionID)
                    ->put();

                // Update the last comment's user ID.
                $this->SQL
                    ->update('Discussion d')
                    ->update('Comment c')
                    ->set('d.LastCommentUserID', 'c.InsertUserID', false)
                    ->where('d.DiscussionID', $discussionID)
                    ->where('c.CommentID', 'd.LastCommentID', false, false)
                    ->put();
            } else {
                // Update the discussion with null counts.
                $this->SQL
                    ->update('Discussion')
                    ->set('CountComments', 0)
                    ->set('FirstCommentID', null)
                    ->set('LastCommentID', null)
                    ->set('DateLastComment', 'DateInserted', false, false)
                    ->set('LastCommentUserID', null)
                    ->where('DiscussionID', $discussionID)
                    ->put();
            }
            $this->addDirtyRecord('discussion', $discussionID);
        }
    }

    /**
     * Update UserDiscussion so users don't have incorrect counts.
     *
     * @since 2.0.18
     * @access public
     *
     * @param int $discussionID Unique ID of the discussion we are updating.
     */
    public function updateUserCommentCounts($discussionID) {
        $sql = "update ".$this->Database->DatabasePrefix."UserDiscussion ud
         set CountComments = (
            select count(c.CommentID)+1
            from ".$this->Database->DatabasePrefix."Comment c
            where c.DateInserted < ud.DateLastViewed
         )
         where DiscussionID = $discussionID";
        $this->SQL->query($sql);
    }

    /**
     * Update user's total comment count.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $userID Unique ID of the user to be updated.
     */
    public function updateUser($userID, $inc = false) {
        $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
        if ($inc) {
            $countComments = val('CountComments', $user);
            // Increment if 100 or greater; Recalculate on 120, 140 etc.
            if ($countComments >= 100 && $countComments % 20 !== 0) {
                $this->SQL->update('User')
                    ->set('CountComments', 'CountComments + 1', false)
                    ->where('UserID', $userID)
                    ->put();

                $this->userModel->updateUserCache($userID, 'CountComments', $countComments + 1);
                $this->addDirtyRecord('user', $userID);
                return;
            }
        }

        $countComments = $this->SQL
            ->select('CommentID', 'count', 'CountComments')
            ->from('Comment')
            ->where('InsertUserID', $userID)
            ->get()->value('CountComments', 0);

        // Save the count to the user table
        $this->userModel->setField($userID, 'CountComments', $countComments);
    }

    /**
     * Override of parent::setField
     *
     * @param int $rowID
     * @param array|string $property
     * @param bool $value
     */
    public function setField($rowID, $property, $value = false) {
        parent::setField($rowID, $property, $value);
        $this->addDirtyRecord('comment', $rowID);
    }

    /**
     * Delete a comment.
     *
     * {@inheritdoc}
     */
    public function delete($where = [], $options = []) {
        if (is_numeric($where)) {
            deprecated('CommentModel->delete(int)', 'CommentModel->deleteID(int)');

            $result = $this->deleteID($where, $options);
            return $result;
        }

        throw new \BadMethodCallException("CommentModel->delete() is not supported.", 400);
    }

    /**
     * Delete a comment.
     *
     * This is a hard delete that completely removes it from the database.
     * Events: DeleteComment, BeforeDeleteComment.
     *
     * @param int $id Unique ID of the comment to be deleted.
     * @param array $options Additional options for the delete.
     * @return bool Always returns true.
     */
    public function deleteID($id, $options = []) {
        Assert::integerish($id);
        Assert::isArray($options);

        $this->EventArguments['CommentID'] = $id;

        $comment = $this->getID($id, DATASET_TYPE_ARRAY);
        if (!$comment) {
            return false;
        }
        $discussion = $this->SQL->getWhere('Discussion', ['DiscussionID' => $comment['DiscussionID']])->firstRow(DATASET_TYPE_ARRAY);

        // Decrement the UserDiscussion comment count if the user has seen this comment
        $offset = $this->getOffset($id);
        $this->SQL->update('UserDiscussion')
            ->set('CountComments', 'CountComments - 1', false)
            ->where('DiscussionID', $comment['DiscussionID'])
            ->where('CountComments >', $offset)
            ->put();

        $this->EventArguments['Comment'] = $comment;
        $this->EventArguments['Discussion'] = $discussion;
        $this->fireEvent('DeleteComment');
        $this->fireEvent('BeforeDeleteComment');

        // Log the deletion.
        $log = val('Log', $options, 'Delete');
        LogModel::insert($log, 'Comment', $comment, val('LogOptions', $options, []));

        // Delete the comment.
        $this->SQL->delete('Comment', ['CommentID' => $id]);

        // Update the comment count
        $this->updateCommentCount($discussion, ['Slave' => false]);

        // Update the user's comment count
        $this->updateUser($comment['InsertUserID']);

        // Update the category.
        $categoryID = val('CategoryID', $discussion);
        $category = CategoryModel::categories($categoryID);
        if ($category && $category['LastCommentID'] == $id) {
            $categoryModel = new CategoryModel();
            $categoryModel->setRecentPost($category['CategoryID']);
        }
        // Decrement CountAllComments for category and its parents.
        CategoryModel::decrementAggregateCount($categoryID, CategoryModel::AGGREGATE_COMMENT);

        // Clear the page cache.
        $this->removePageCache($comment['DiscussionID']);

        if ($comment) {
            $dataObject = (object)$comment;
            $this->calculate($dataObject);

            $commentEvent = $this->eventFromRow(
                (array)$dataObject,
                CommentEvent::ACTION_DELETE,
                $this->userModel->currentFragment()
            );
            $this->getEventManager()->dispatch($commentEvent);
        }
        return true;
    }

    /**
     * Check if the user has the correct permissions to delete a comment. Throws an error if not.
     *
     * @param int $commentID
     *
     * @throws NoResultsException If the record wasn't found.
     * @throws PermissionException If the user doesn't have permission to delete.
     */
    public function checkCanDelete(int $commentID) {
        $comment = $this->getID($commentID);
        if ($comment === false) {
            throw new NoResultsException('Comment');
        }

        $discussion = $this->discussionModel->getID($comment->DiscussionID);
        if ($discussion === false) {
            throw new NoResultsException('Discussion');
        }

        $allowsSelfDelete = Gdn::config('Vanilla.Comments.AllowSelfDelete');
        $isOwnPost = $comment->InsertUserID === Gdn::session()->UserID;


        if (!$allowsSelfDelete || !$isOwnPost) {
            $this->discussionModel->categoryPermission('Vanilla.Comments.Delete', $discussion->CategoryID);
        }
    }

    /**
     * Modifies comment data before it is returned.
     *
     * @since 2.1a32
     * @access public
     *
     * @param object $data SQL result.
     */
    public function setCalculatedFields(&$data) {
        $result = &$data->result();
        foreach ($result as &$comment) {
            $this->calculate($comment);
        }
    }

    /**
     * Modifies comment data before it is returned.
     *
     * @since 2.1a32
     * @access public
     *
     * @param object $Data SQL result.
     */
    public function calculate($comment) {

        // Do nothing yet.
        if ($attributes = val('Attributes', $comment)) {
            setValue('Attributes', $comment, dbdecode($attributes));
        }

        $this->EventArguments['Comment'] = $comment;
        $this->fireEvent('SetCalculatedFields');
    }

    public function where($value = null) {
        if ($value === null) {
            return $this->_Where;
        } elseif (!$value)
            $this->_Where = [];
        elseif (is_a($value, 'Gdn_SQLDriver')) {
            if (!empty($this->_Where)) {
                $value->where($this->_Where);
            }
        } else {
            $this->_Where = $value;
        }
    }

    /**
     * Determines whether or not the current user can edit a comment.
     *
     * @param object|array $comment The comment to examine.
     * @param int &$timeLeft Sets the time left to edit or 0 if not applicable.
     * @param array|null $discussion The discussion row associated with this comment.
     * @return bool Returns true if the user can edit or false otherwise.
     */
    public static function canEdit($comment, &$timeLeft = 0, $discussion = null) {
        // Guests can't edit.
        if (Gdn::session()->UserID === 0) {
            return false;
        }

        // Only attempt to fetch the discussion if we weren't provided one.
        if ($discussion === null) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID(val('DiscussionID', $comment));
        }

        // Can the current user edit all comments in this category?
        $category = CategoryModel::categories(val('CategoryID', $discussion));
        if (CategoryModel::checkPermission($category, 'Vanilla.Comments.Edit')) {
            return true;
        }

        // Check if user can view the category contents.
        if (!CategoryModel::checkPermission($category, 'Vanilla.Comments.Add')) {
            return false;
        }

        // Make sure only moderators can edit closed things.
        if (val('Closed', $discussion)) {
            return false;
        }

        // Non-mods can't edit if they aren't the author.
        if (Gdn::session()->UserID != val('InsertUserID', $comment)) {
            return false;
        }

        return parent::editContentTimeout($comment, $timeLeft);
    }

    /**
     * @inheritDoc
     */
    public function getCrawlInfo(): array {
        $r = \Vanilla\Models\LegacyModelUtils::getCrawlInfoFromPrimaryKey(
            $this,
            '/api/v2/comments?sort=-commentID&expand[]=crawl',
            'commentID'
        );
        return $r;
    }

    /**
     * Return a URL for a comment. This function is in here and not functions.general so that plugins can override.
     *
     * @param object|array $comment
     * @param bool $withDomain
     * @return string
     */
    public static function commentUrl($comment, $withDomain = true) {
        if (function_exists('commentUrl')) {
            // Legacy overrides.
            return commentUrl($comment, $withDomain);
        } else {
            return self::createRawCommentUrl($comment, $withDomain);
        }
    }

    /**
     * Return a URL for a comment. This function is in here and not functions.general so that plugins can override.
     *
     * @param object|array $comment
     * @param bool $withDomain
     * @return string
     *
     * @internal Don't use unless you are the global commentUrl function.
     */
    public static function createRawCommentUrl($comment, $withDomain = true) {
        $eventManager = \Gdn::eventManager();
        if ($eventManager->hasHandler('customCommentUrl')) {
            return $eventManager->fireFilter('customCommentUrl', '', $comment, $withDomain);
        }

        $comment = (object)$comment;
        $result = "/discussion/comment/{$comment->CommentID}#Comment_{$comment->CommentID}";
        return url($result, $withDomain);
    }

    /**
     * Add a 'discussion' field to the comment that contains the discussion data.
     *
     * @param array $row The row of comment data.
     * @return array
     */
    private function addDiscussionData(array $row): array {
        $row['discussion'] = $this->discussionModel->getID($row['DiscussionID'], DATASET_TYPE_ARRAY);
        $row['discussion']['Type'] = $row['discussion']['Type'] ?? 'discussion';
        return $row;
    }
}
