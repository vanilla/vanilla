<?php
/**
 * Discussion model
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

use Vanilla\Exception\PermissionException;

/**
 * Manages discussions data.
 */
class DiscussionModel extends Gdn_Model {

    use StaticInitializer;
    use \Vanilla\FloodControlTrait;

    /** Cache key. */
    const CACHE_DISCUSSIONVIEWS = 'discussion.%s.countviews';

    /** @var string Default column to order by. */
    const DEFAULT_ORDER_BY_FIELD = 'DateLastComment';

    /** @var string The filter key for clearing-type filters. */
    const EMPTY_FILTER_KEY = 'none';

    /** Max comments on a discussion before it cannot be auto-deleted by SPAM or moderation actions. */
    const DELETE_COMMENT_THRESHOLD = 10;

    /** @var array|bool */
    private static $categoryPermissions = null;

    /** @var array */
    private static $discussionTypes = null;

    /**
     * @deprecated 2.6
     * @var bool
     */
    public $Watching = false;

    /** @var array Discussion Permissions */
    private $permissionTypes = ['Add', 'Announce', 'Close', 'Delete', 'Edit', 'Sink', 'View'];

    /**
     * @var array The sorts that are accessible via GET. Each sort corresponds with an order by clause.
     *
     * Each sort in the array has the following properties:
     * - **key**: string - The key name of the sort. Appears in the query string, should be url-friendly.
     * - **name**: string - The display name of the sort.
     * - **orderBy**: string - An array indicating order by fields and their directions in the format:
     *   `['field1' => 'direction', 'field2' => 'direction']`
     */
    protected static $allowedSorts = [
        'hot' => ['key' => 'hot', 'name' => 'Hot', 'orderBy' => ['DateLastComment' => 'desc']],
        'top' => ['key' => 'top', 'name' => 'Top', 'orderBy' => ['Score' => 'desc', 'DateInserted' => 'desc']],
        'new' => ['key' => 'new', 'name' => 'New', 'orderBy' => ['DateInserted' => 'desc']]
    ];

    /**
     * @var array The filters that are accessible via GET. Each filter corresponds with a where clause. You can have multiple
     * filter sets. Every filter must be added to a filter set.
     *
     * Each filter set has the following properties:
     * - **key**: string - The key name of the filter set. Appears in the query string, should be url-friendly.
     * - **name**: string - The display name of the filter set. Usually appears in the UI.
     * - **filters**: array - The filters in the set.
     *
     * Each filter in the array has the following properties:
     * - **key**: string - The key name of the filter. Appears in the query string, should be url-friendly.
     * - **setKey**: string - The key name of the filter set.
     * - **name**: string - The display name of the filter. Usually appears as an option in the UI.
     * - **where**: string - The where array query to execute for the filter. Uses
     * - **group**: string - (optional) The dropdown module can group together any items with the same group name.
     */
    protected static $allowedFilters = [];

    /**
     * @var DiscussionModel $instance;
     */
    private static $instance;

    /**
     * @var string The sort key of the order by we apply in the query.
     */
    protected $sort = '';

    /**
     * @var array The filter keys of the wheres we apply in the query.
     */
    protected $filters = [];

    /**
     * @var \Vanilla\CacheInterface Object used to store the FloodControl data.
     */
    protected $floodGate;

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct() {
        parent::__construct('Discussion');
        $this->floodGate = FloodControlHelper::configure($this, 'Vanilla', 'Discussion');
    }

    /**
     * The shared instance of this object.
     *
     * @return DiscussionModel Returns the instance.
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new DiscussionModel();
        }
        return self::$instance;
    }

    /**
     * Verify the current user has a permission in a category.
     *
     * @param string|array $permission The permission slug(s) to check (e.g. Vanilla.Discussions.View).
     * @param int $categoryID The category's numeric ID.
     * @throws PermissionException if the current user does not have the permission in the category.
     */
    public function categoryPermission($permission, $categoryID) {
        $category = CategoryModel::categories($categoryID);
        if ($category) {
            $id = $category['PermissionCategoryID'];
        } else {
            $id = -1;
        }
        $permissions = (array)$permission;

        if (!Gdn::session()->getPermissions()->hasAny($permissions, $id)) {
            throw new PermissionException($permissions);
        }
    }

    /**
     * @return array The current sort array.
     */
    public static function getAllowedSorts() {
        self::initStatic();
        return self::$allowedSorts;
    }

    /**
     * Get the registered filters.
     *
     * This method must never be called before plugins initialisation.
     *
     * @return array The current filter array.
     */
    public static function getAllowedFilters() {
        self::initStatic();
        return self::$allowedFilters;
    }

    /**
     * @return string
     */
    public function getSort() {
        return $this->sort;
    }

    /**
     * @return array
     */
    public function getFilters() {
        return $this->filters;
    }

    /**
     * Set the discussion sort.
     *
     * This setter also accepts an array and checks if the sort key exists on the array. Will only set the sort property
     * if it exists in the allowed sorts array.
     *
     * @param string|array $sort The prospective sort to set.
     */
    public function setSort($sort) {
        if (is_array($sort)) {
            $safeSort = $this->getSortFromArray($sort);
            $this->sort = $safeSort;
        } elseif (is_string($sort)) {
            $safeSort = $this->getSortFromString($sort);
            $this->sort = $safeSort;
        }
    }

    /**
     * Will only set the filters property if the passed filters exist in the allowed filters array.
     *
     * @param array $filters The prospective filters to set.
     */
    public function setFilters($filters) {
        if (is_array($filters)) {
            $safeFilters = $this->getFiltersFromArray($filters);
            $this->filters = $safeFilters;
        }
    }

    /**
     * @return string
     */
    public static function getDefaultSortKey() {
        $orderBy = self::getDefaultOrderBy(); // check config

        // Try to find a matching sort.
        foreach (self::getAllowedSorts() as $sort) {
            if (val('orderBy', $sort, []) == $orderBy) {
                return val('key', $sort, '');
            }
        }
        return '';
    }

    /**
     * Determines whether or not the current user can edit a discussion.
     *
     * @param object|array $discussion The discussion to examine.
     * @param int &$timeLeft Sets the time left to edit or 0 if not applicable.
     * @return bool Returns true if the user can edit or false otherwise.
     */
    public static function canEdit($discussion, &$timeLeft = 0) {
        $category = CategoryModel::categories(val('CategoryID', $discussion));

        // Users with global edit permission can edit.
        if (CategoryModel::checkPermission($category, 'Vanilla.Discussions.Edit')) {
            return true;
        }

        // Make sure only moderators can edit closed things.
        if (val('Closed', $discussion)) {
            return false;
        }

        // Non-mods can't edit if they aren't the author.
        if (Gdn::session()->UserID != val('InsertUserID', $discussion)) {
            return false;
        }

        return parent::editContentTimeout($discussion, $timeLeft);
    }

    public function counts($column, $from = false, $to = false, $max = false) {
        $result = ['Complete' => true];
        switch ($column) {
            case 'CountComments':
                $this->Database->query(DBAModel::getCountSQL('count', 'Discussion', 'Comment'));
                break;
            case 'FirstCommentID':
                $this->Database->query(DBAModel::getCountSQL('min', 'Discussion', 'Comment', $column));
                break;
            case 'LastCommentID':
                $this->Database->query(DBAModel::getCountSQL('max', 'Discussion', 'Comment', $column));
                break;
            case 'DateLastComment':
                $defaultDate = '0000-00-00 00:00:00';
                $countSql = DBAModel::getCountSQL(
                    'max',
                    'Discussion',
                    'Comment',
                    $column,
                    'DateInserted',
                    '',
                    '',
                    [],
                    $defaultDate
                );
                $this->Database->query($countSql);
                $this->SQL
                    ->update('Discussion')
                    ->set('DateLastComment', 'DateInserted', false, false)
                    ->where('DateLastComment', null)
                    ->orWhere('DateLastComment',$defaultDate)
                    ->put();
                break;
            case 'LastCommentUserID':
                if (!$max) {
                    // Get the range for this update.
                    $dBAModel = new DBAModel();
                    list($min, $max) = $dBAModel->primaryKeyRange('Discussion');

                    if (!$from) {
                        $from = $min;
                        $to = $min + DBAModel::$ChunkSize - 1;
                    }
                }
                $this->SQL
                    ->update('Discussion d')
                    ->join('Comment c', 'c.CommentID = d.LastCommentID')
                    ->set('d.LastCommentUserID', 'c.InsertUserID', false, false)
                    ->where('d.DiscussionID >=', $from)
                    ->where('d.DiscussionID <=', $to)
                    ->put();
                $result['Complete'] = $to >= $max;

                $percent = round($to * 100 / $max);
                if ($percent > 100 || $result['Complete']) {
                    $result['Percent'] = '100%';
                } else {
                    $result['Percent'] = $percent.'%';
                }


                $from = $to + 1;
                $to = $from + DBAModel::$ChunkSize - 1;
                $result['Args']['From'] = $from;
                $result['Args']['To'] = $to;
                $result['Args']['Max'] = $max;
                break;
            default:
                throw new Gdn_UserException("Unknown column $column");
        }
        return $result;
    }

    /**
     * Builds base SQL query for discussion data.
     *
     * Events: AfterDiscussionSummaryQuery.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $additionalFields Allows selection of additional fields as Alias=>Table.Fieldname.
     */
    public function discussionSummaryQuery($additionalFields = [], $join = true) {
        // Verify permissions (restricting by category if necessary)
        $perms = CategoryModel::instance()->getVisibleCategoryIDs();

        if ($perms !== true) {
            $this->SQL->whereIn('d.CategoryID', $perms);
        }

        // Buid main query
        $this->SQL
            ->select('d.*')
            ->select('d.InsertUserID', '', 'FirstUserID')
            ->select('d.DateInserted', '', 'FirstDate')
            ->select('d.DateLastComment', '', 'LastDate')
            ->select('d.LastCommentUserID', '', 'LastUserID')
            ->from('Discussion d');

        if ($join) {
            $this->SQL
                ->select('iu.Name', '', 'FirstName')// <-- Need these for rss!
                ->select('iu.Photo', '', 'FirstPhoto')
                ->select('iu.Email', '', 'FirstEmail')
                ->join('User iu', 'd.InsertUserID = iu.UserID', 'left')// First comment author is also the discussion insertuserid

                ->select('lcu.Name', '', 'LastName')
                ->select('lcu.Photo', '', 'LastPhoto')
                ->select('lcu.Email', '', 'LastEmail')
                ->join('User lcu', 'd.LastCommentUserID = lcu.UserID', 'left')// Last comment user

                ->select('ca.Name', '', 'Category')
                ->select('ca.UrlCode', '', 'CategoryUrlCode')
                ->select('ca.PermissionCategoryID')
                ->join('Category ca', 'd.CategoryID = ca.CategoryID', 'left'); // Category

        }

        // Add any additional fields that were requested.
        if (is_array($additionalFields)) {
            foreach ($additionalFields as $alias => $field) {
                // Select the field.
                $this->SQL->select($field, '', is_numeric($alias) ? '' : $alias);
            }
        }

        $this->fireEvent('AfterDiscussionSummaryQuery');
    }

    /**
     * Get the allowed discussion types.
     *
     * @return array Returns an array of discussion type definitions.
     */
    public static function discussionTypes() {
        if (self::$discussionTypes === null) {
            $discussionTypes = ['Discussion' => [
                'Singular' => 'Discussion',
                'Plural' => 'Discussions',
                'AddUrl' => '/post/discussion',
                'AddText' => 'New Discussion'
            ]];


            Gdn::pluginManager()->EventArguments['Types'] = &$discussionTypes;
            Gdn::pluginManager()->fireAs('DiscussionModel')->fireEvent('DiscussionTypes');
            self::$discussionTypes = $discussionTypes;
            unset(Gdn::pluginManager()->EventArguments['Types']);
        }
        return self::$discussionTypes;
    }

    /**
     * Gets the data for multiple discussions based on the given criteria.
     *
     * Sorts results based on config options Vanilla.Discussions.SortField
     * and Vanilla.Discussions.SortDirection.
     * Events: BeforeGet, AfterAddColumns.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $offset Number of discussions to skip.
     * @param int $limit Max number of discussions to return.
     * @param array $wheres SQL conditions.
     * @param array $additionalFields Allows selection of additional fields as Alias=>Table.Fieldname.
     * @return Gdn_DataSet SQL result.
     */
    public function get($offset = '0', $limit = '', $wheres = '', $additionalFields = null) {
        if ($limit == '') {
            $limit = Gdn::config('Vanilla.Discussions.PerPage', 50);
        }

        $offset = !is_numeric($offset) || $offset < 0 ? 0 : $offset;

        $session = Gdn::session();
        $userID = $session->UserID > 0 ? $session->UserID : 0;
        $this->discussionSummaryQuery($additionalFields, false);

        if ($userID > 0) {
            $this->SQL
                ->select('w.UserID', '', 'WatchUserID')
                ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
                ->select('w.CountComments', '', 'CountCommentWatch')
                ->select('w.Participated')
                ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$userID, 'left');
        } else {
            $this->SQL
                ->select('0', '', 'WatchUserID')
                ->select('now()', '', 'DateLastViewed')
                ->select('0', '', 'Dismissed')
                ->select('0', '', 'Bookmarked')
                ->select('0', '', 'CountCommentWatch')
                ->select('0', '', 'Participated')
                ->select('0', '', 'Read')
                ->select('d.Announce', '', 'IsAnnounce');
        }

        $this->addArchiveWhere($this->SQL);

        if ($offset !== false && $limit !== false) {
            $this->SQL->limit($limit, $offset);
        }

        // Get preferred sort order
        $orderBy = $this->getOrderBy();

        $this->EventArguments['OrderFields'] = &$orderBy;
        $this->EventArguments['Wheres'] = &$wheres;
        $this->fireEvent('BeforeGet'); // @see 'BeforeGetCount' for consistency in results vs. counts

        $includeAnnouncements = false;
        if (strtolower(val('Announce', $wheres)) == 'all') {
            $includeAnnouncements = true;
            unset($wheres['Announce']);
        }

        if (is_array($wheres)) {
            $this->SQL->where($wheres);
        }

        foreach ($orderBy as $orderField => $direction) {
            $this->SQL->orderBy($this->addFieldPrefix($orderField), $direction);
        }

        // Set range and fetch
        $data = $this->SQL->get();

        // If not looking at discussions filtered by bookmarks or user, filter announcements out.
        if (!$includeAnnouncements) {
            if (!isset($wheres['w.Bookmarked']) && !isset($wheres['d.InsertUserID'])) {
                $this->removeAnnouncements($data);
            }
        }

        // Change discussions returned based on additional criteria
        $this->addDiscussionColumns($data);

        // Join in the users.
        Gdn::userModel()->joinUsers($data, ['FirstUserID', 'LastUserID']);
        CategoryModel::joinCategories($data);

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->addDenormalizedViews($data);
        }

        // Prep and fire event
        $this->EventArguments['Data'] = $data;
        $this->fireEvent('AfterAddColumns');

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultLimit() {
        return (int)Gdn::config('Vanilla.Discussions.PerPage', 50);
    }

    /**
     * Get a list of the most recent discussions.
     *
     * @param array|false $where The where condition of the get.
     * @param bool|false|int $limit The number of discussion to return.
     * @param int|false $offset The offset within the total set.
     * @param bool $expand Expand relevant related records (e.g. category, users).
     * @return Gdn_DataSet Returns a <a href='psi_element://Gdn_DataSet'>Gdn_DataSet</a> of discussions.
     * of discussions.
     */
    public function getWhereRecent($where = [], $limit = false, $offset = false, $expand = true) {
        $result = $this->getWhere($where, '', '', $limit, $offset, $expand);
        return $result;
    }

    /**
     * Returns an array in the format [field => direction]. You can safely use return values from this function
     * in the orderBy() SQL function.
     *
     * @since 2.3
     * @return array An array of field => direction values.
     */
    protected function getOrderBy() {
        if ($key = self::getSort()) {
            $orderBy = val('orderBy', $this->getSortFromKey($key));
        } else {
            $orderBy = self::getDefaultOrderBy();
        }
        return $orderBy;
    }

    /**
     * Returns an array of field => direction for the order by clause on the Discussion table.
     * Attempts to get ordering fields from the config before settling on DEFAULT_ORDER_BY_FIELD.
     *
     * @return array The default order by fields
     */
    public static function getDefaultOrderBy() {
        $orderField = c('Vanilla.Discussions.SortField', self::DEFAULT_ORDER_BY_FIELD);
        $orderDirection = c('Vanilla.Discussions.SortDirection', 'desc');

        // Normalize any prefixed fields
        if (strpos($orderField, 'd.') === 0) {
            $orderField = substr($orderField, 2);
        }

        return [$orderField => $orderDirection];
    }

    /**
     * Checks for any set filters and if they exist, returns the where clauses from the filters.
     *
     * @param array $categoryIDs The category IDs from the where clause.
     * @return array The where clauses from the filters.
     * @throws Exception
     */
    protected function getWheres($categoryIDs = []) {
        $wheres = [];
        $filters = $this->getFiltersFromKeys($this->getFilters());

        foreach ($filters as $filter) {
            if (!empty($categoryIDs)) {
                $setKey = val('setKey', $filter);
                $filterSetCategories = val('categories', val($setKey, self::getAllowedFilters()));

                if (!empty($filterSetCategories) and array_diff($categoryIDs, $filterSetCategories)) {
                    $filter['wheres'] = [];
                }
            }
            $wheres = $this->combineWheres(val('wheres', $filter, []), $wheres);
        }
        return $wheres;
    }

    /**
     * Combines two arrays of where clauses.
     *
     * @param array $newWheres The clauses we're adding.
     * @param array $wheres The where clauses to add new clauses to.
     * @return array A set of where clauses, array form.
     */
    protected function combineWheres($newWheres, $wheres) {
        foreach ($newWheres as $field => $value) {
            // Combine all our where clauses.
            if (!array_key_exists($field, $wheres)) {
                // Add a new where field to the list.
                $wheres[$field] = $value;
            } elseif (is_array($wheres[$field])) {
                if (!in_array($value, $wheres[$field])) {
                    // Add a new where value.
                    $wheres[$field][] = $value;
                }
            }
        }
        return $wheres;
    }

    /**
     * Get the maximum number of discussion pages.
     *
     * @return int
     */
    public function getMaxPages() {
        return (int)c('Vanilla.Discussions.MaxPages');
    }

    /**
     * Get a list of discussions.
     *
     * This method call will remove announcements and may not return exactly {@link $limit} records for optimization.
     * You can set `$where['d.Announce'] = 'all'` to return announcements.
     *
     * @param array|false $where The where condition of the get.
     * @param string $orderFields The field to order the discussions by.
     * @param string $orderDirection The order, either **asc** or **desc**.
     * @param int|false $limit The number of discussion to return.
     * @param int|false $offset The offset within the total set.
     * @param bool $expand Expand relevant related records (e.g. category, users).
     * @return Gdn_DataSet Returns a {@link Gdn_DataSet} of discussions.
     */
    public function getWhere($where = false, $orderFields = '', $orderDirection = '', $limit = false, $offset = false, $expand = true) {
        // Add backwards compatibility for the old way getWhere() was called.
        if (is_numeric($orderFields)) {
            deprecated('DiscussionModel->getWhere($where, $limit, ...)', 'DiscussionModel->getWhereRecent()');
            $limit = $orderFields;
            $orderFields = '';
        }
        if (is_numeric($orderDirection)) {
            deprecated('DiscussionModel->getWhere($where, $limit, $offset)', 'DiscussionModel->getWhereRecent()');
            $offset = $orderDirection;
            $orderDirection = '';
        }

        if ($limit === 0) {
            trigger_error("You should not supply 0 to for $limit in DiscussionModel->getWhere()", E_USER_NOTICE);
        }
        if (empty($limit)) {
            $limit = c('Vanilla.Discussions.PerPage', 30);
        }
        if (empty($offset)) {
            $offset = 0;
        }

        if (!is_array($where)) {
            $where = [];
        }

        if (isset($where['CategoryID'])) {
            $where['d.CategoryID'] = $where['CategoryID'];
            unset($where['CategoryID']);
        }

        $where = $this->combineWheres($this->getWheres(), $where);

        $orderBy = [];
        if (empty($orderFields)) {
            $orderBy = $this->getOrderBy();
        } elseif (is_string($orderFields)) {
            if ($orderDirection != 'asc') {
                $orderDirection = 'desc';
            }
            $orderBy = [$orderFields => $orderDirection];
        }

        $this->EventArguments['OrderBy'] = &$orderBy;
        $this->EventArguments['Wheres'] = &$where;
        $this->fireEvent('BeforeGet');

        // Verify permissions (restricting by category if necessary)
        $perms = self::categoryPermissions();

        $sql = $this->SQL;

        // Build up the base query. Self-join for optimization.
        $sql->select('d2.*')
            ->from('Discussion d')
            ->join('Discussion d2', 'd.DiscussionID = d2.DiscussionID')
            ->limit($limit, $offset);

        foreach ($orderBy as $field => $direction) {
            $sql->orderBy($this->addFieldPrefix($field), $direction);
        }

        if (array_key_exists('Followed', $where)) {
            if ($where['Followed']) {
                $categoryModel = new CategoryModel();
                $followed = $categoryModel->getFollowed(Gdn::session()->UserID);
                $categoryIDs = array_column($followed, 'CategoryID');

                if (isset($where['d.CategoryID'])) {
                    $where['d.CategoryID'] = array_values(array_intersect((array)$where['d.CategoryID'], $categoryIDs));
                } else {
                    $where['d.CategoryID'] = $categoryIDs;
                }
            }
            unset($where['Followed']);
        }

        if ($perms !== true) {
            if (isset($where['d.CategoryID'])) {
                $where['d.CategoryID'] = array_values(array_intersect((array)$where['d.CategoryID'], $perms));
            } else {
                $where['d.CategoryID'] = $perms;
            }
        }

        // Check to see whether or not we are removing announcements.
        if (strtolower(val('Announce', $where)) == 'all') {
            $removeAnnouncements = false;
            unset($where['Announce']);
        } elseif (strtolower(val('d.Announce', $where)) == 'all') {
            $removeAnnouncements = false;
            unset($where['d.Announce']);
        } else {
            $removeAnnouncements = true;
        }

        // Make sure there aren't any ambiguous discussion references.
        $safeWheres = [];
        foreach ($where as $key => $value) {
            $safeWheres[$this->addFieldPrefix($key)] = $value;
        }
        $sql->where($safeWheres);

        // Add the UserDiscussion query.
        if (($userID = Gdn::session()->UserID) > 0) {
            $sql
                ->join('UserDiscussion w', "w.DiscussionID = d2.DiscussionID and w.UserID = $userID", 'left')
                ->select('w.UserID', '', 'WatchUserID')
                ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
                ->select('w.CountComments', '', 'CountCommentWatch')
                ->select('w.Participated');
        }

        $data = $sql->get();

        // Change discussions returned based on additional criteria
        $this->addDiscussionColumns($data);

        // If not looking at discussions filtered by bookmarks or user, filter announcements out.
        if ($removeAnnouncements && !isset($where['w.Bookmarked']) && !isset($where['d.InsertUserID'])) {
            $this->removeAnnouncements($data);
        }

        // Join in users and categories.
        if ($expand) {
            Gdn::userModel()->joinUsers($data, ['FirstUserID', 'LastUserID']);
            CategoryModel::joinCategories($data);
        }

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->addDenormalizedViews($data);
        }

        // Prep and fire event
        $this->EventArguments['Data'] = $data;
        $this->fireEvent('AfterAddColumns');

        return $data;
    }

    /**
     * Adds a prefix to the field name if the field doesn't already have one.
     *
     * @param string $fieldName The name of the field.
     * @param string $prefix
     * @return string The fieldname with the prefix if one does not exist.
     */
    public function addFieldPrefix($fieldName, $prefix = 'd') {
        // Make sure there aren't any ambiguous discussion references.
        if (strpos($fieldName, '.') === false) {
            $fieldName = $prefix.'.'.$fieldName;
        }
        return $fieldName;
    }

    /**
     * Gets the data for multiple unread discussions based on the given criteria.
     *
     * Sorts results based on config options Vanilla.Discussions.SortField
     * and Vanilla.Discussions.SortDirection.
     * Events: BeforeGet, AfterAddColumns.
     *
     * @deprecated since 2.4, reason: doesn't scale
     *
     * @param int $offset Number of discussions to skip.
     * @param int $limit Max number of discussions to return.
     * @param array $wheres SQL conditions.
     * @param array $additionalFields Allows selection of additional fields as Alias=>Table.Fieldname.
     * @return Gdn_DataSet SQL result.
     */
    public function getUnread($offset = '0', $limit = '', $wheres = '', $additionalFields = null) {
        deprecated(__METHOD__);

        if ($limit == '') {
            $limit = Gdn::config('Vanilla.Discussions.PerPage', 50);
        }

        $offset = !is_numeric($offset) || $offset < 0 ? 0 : $offset;

        $session = Gdn::session();
        $userID = $session->UserID > 0 ? $session->UserID : 0;
        $this->discussionSummaryQuery($additionalFields, false);

        if ($userID > 0) {
            $this->SQL
                ->select('w.UserID', '', 'WatchUserID')
                ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
                ->select('w.CountComments', '', 'CountCommentWatch')
                ->select('w.Participated')
                ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$userID, 'left')
                ->beginWhereGroup()
                ->where('d.CountComments >', 'COALESCE(w.CountComments, 0)', true, false)
                ->orWhere('w.DateLastViewed', null)
                ->endWhereGroup();
        } else {
            $this->SQL
                ->select('0', '', 'WatchUserID')
                ->select('now()', '', 'DateLastViewed')
                ->select('0', '', 'Dismissed')
                ->select('0', '', 'Bookmarked')
                ->select('0', '', 'CountCommentWatch')
                ->select('0', '', 'Participated')
                ->select('d.Announce', '', 'IsAnnounce');
        }

        $this->addArchiveWhere($this->SQL);


        $this->SQL->limit($limit, $offset);

        $this->EventArguments['SortField'] = c('Vanilla.Discussions.SortField', 'd.DateLastComment');
        $this->EventArguments['SortDirection'] = c('Vanilla.Discussions.SortDirection', 'desc');
        $this->EventArguments['Wheres'] = &$wheres;
        $this->fireEvent('BeforeGetUnread'); // @see 'BeforeGetCount' for consistency in results vs. counts

        $includeAnnouncements = false;
        if (strtolower(val('Announce', $wheres)) == 'all') {
            $includeAnnouncements = true;
            unset($wheres['Announce']);
        }

        if (is_array($wheres)) {
            $this->SQL->where($wheres);
        }

        // Get sorting options from config
        $sortField = $this->EventArguments['SortField'];
        if (!in_array($sortField, ['d.DiscussionID', 'd.DateLastComment', 'd.DateInserted'])) {
            trigger_error("You are sorting discussions by a possibly sub-optimal column.", E_USER_NOTICE);
        }

        $sortDirection = $this->EventArguments['SortDirection'];
        if ($sortDirection != 'asc') {
            $sortDirection = 'desc';
        }

        $this->SQL->orderBy($this->addFieldPrefix($sortField), $sortDirection);

        // Set range and fetch
        $data = $this->SQL->get();

        // If not looking at discussions filtered by bookmarks or user, filter announcements out.
        if (!$includeAnnouncements) {
            if (!isset($wheres['w.Bookmarked']) && !isset($wheres['d.InsertUserID'])) {
                $this->removeAnnouncements($data);
            }
        }

        // Change discussions returned based on additional criteria
        $this->addDiscussionColumns($data);

        // Join in the users.
        Gdn::userModel()->joinUsers($data, ['FirstUserID', 'LastUserID']);
        CategoryModel::joinCategories($data);

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->addDenormalizedViews($data);
        }

        // Prep and fire event
        $this->EventArguments['Data'] = $data;
        $this->fireEvent('AfterAddColumns');

        return $data;
    }

    /**
     * Removes undismissed announcements from the data.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object $data SQL result.
     */
    public function removeAnnouncements($data) {
        $result = &$data->result();
        $unset = false;

        foreach ($result as $key => &$discussion) {
            if (isset($this->_AnnouncementIDs)) {
                if (in_array($discussion->DiscussionID, $this->_AnnouncementIDs)) {
                    unset($result[$key]);
                    $unset = true;
                }
            } elseif ($discussion->Announce && $discussion->Dismissed == 0) {
                // Unset discussions that are announced and not dismissed
                unset($result[$key]);
                $unset = true;
            }
        }
        if ($unset) {
            // Make sure the discussions are still in order for json encoding.
            $result = array_values($result);
        }
    }

    /**
     * Add denormalized views to discussions.
     *
     * @param Gdn_DataSet|stdClass $discussions
     */
    public function addDenormalizedViews(&$discussions) {

        if ($discussions instanceof Gdn_DataSet) {
            $result = $discussions->result();
            foreach ($result as &$discussion) {
                $cacheKey = sprintf(DiscussionModel::CACHE_DISCUSSIONVIEWS, $discussion->DiscussionID);
                $cacheViews = Gdn::cache()->get($cacheKey);
                if ($cacheViews !== Gdn_Cache::CACHEOP_FAILURE) {
                    $discussion->CountViews += $cacheViews;
                }
            }
        } else {
            if (isset($discussions->DiscussionID)) {
                $discussion = $discussions;
                $cacheKey = sprintf(DiscussionModel::CACHE_DISCUSSIONVIEWS, $discussion->DiscussionID);
                $cacheViews = Gdn::cache()->get($cacheKey);
                if ($cacheViews !== Gdn_Cache::CACHEOP_FAILURE) {
                    $discussion->CountViews += $cacheViews;
                }
            }
        }
    }

    /**
     * Modifies discussion data before it is returned.
     *
     * Takes archiving into account and fixes inaccurate comment counts.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object $data SQL result.
     */
    public function addDiscussionColumns($data) {
        // Change discussions based on archiving.
        $result = &$data->result();
        foreach ($result as &$discussion) {
            $this->calculate($discussion);
        }
    }

    public function calculate(&$discussion) {
        $archiveTimestamp = Gdn_Format::toTimestamp(Gdn::config('Vanilla.Archive.Date', 0));

        // Fix up output
        $discussion->Name = Gdn_Format::text($discussion->Name);
        $discussion->Attributes = dbdecode($discussion->Attributes);
        $discussion->Url = discussionUrl($discussion);
        $discussion->Tags = $this->formatTags($discussion->Tags);

        // Join in the category.
        $category = CategoryModel::categories($discussion->CategoryID);
        if (empty($category)) {
            $category = false;
        }
        $discussion->Category = $category['Name'];
        $discussion->CategoryUrlCode = $category['UrlCode'];
        $discussion->PermissionCategoryID = $category['PermissionCategoryID'];

        // Add some legacy calculated columns.
        if (!property_exists($discussion, 'FirstUserID')) {
            $discussion->FirstUserID = $discussion->InsertUserID;
            $discussion->FirstDate = $discussion->DateInserted;
            $discussion->LastUserID = $discussion->LastCommentUserID;
            $discussion->LastDate = $discussion->DateLastComment;
        }

        // Add the columns from UserDiscussion if they don't exist.
        if (!property_exists($discussion, 'CountCommentWatch')) {
            $discussion->WatchUserID = null;
            $discussion->DateLastViewed = null;
            $discussion->Dismissed = 0;
            $discussion->Bookmarked = 0;
            $discussion->CountCommentWatch = null;
        }

        // Allow for discussions to be archived
        $dateLastCommentTimestamp = Gdn_Format::toTimestamp($discussion->DateLastComment);
        if ($dateLastCommentTimestamp && $dateLastCommentTimestamp <= $archiveTimestamp) {
            $discussion->Closed = '1';
            if ($discussion->CountCommentWatch) {
                $discussion->CountUnreadComments = $discussion->CountComments - $discussion->CountCommentWatch;
            } else {
                $discussion->CountUnreadComments = 0;
            }
            // Allow for discussions to just be new.
        } elseif ($discussion->CountCommentWatch === null) {
            $discussion->CountUnreadComments = true;

        } else {
            $discussion->CountUnreadComments = $discussion->CountComments - $discussion->CountCommentWatch;
        }

        if (!property_exists($discussion, 'Read')) {
            $discussion->Read = !(bool)$discussion->CountUnreadComments;
            if ($category && !is_null($category['DateMarkedRead'])) {
                // If the category was marked explicitly read at some point, see if that applies here
                if ($category['DateMarkedRead'] > $discussion->DateLastComment) {
                    $discussion->Read = true;
                }

                if ($discussion->Read) {
                    $discussion->CountUnreadComments = 0;
                }
            }
        }

        // Logic for incomplete comment count.
        if ($discussion->CountCommentWatch == 0 && $dateLastViewed = val('DateLastViewed', $discussion)) {
            $discussion->CountUnreadComments = true;
            if (Gdn_Format::toTimestamp($dateLastViewed) >= Gdn_Format::toTimestamp($discussion->LastDate)) {
                $discussion->CountCommentWatch = $discussion->CountComments;
                $discussion->CountUnreadComments = 0;
            }
        }
        if ($discussion->CountUnreadComments === null) {
            $discussion->CountUnreadComments = 0;
        } elseif ($discussion->CountUnreadComments < 0) {
            $discussion->CountUnreadComments = 0;
        }

        $discussion->CountCommentWatch = is_numeric($discussion->CountCommentWatch) ? $discussion->CountCommentWatch : null;

        if ($discussion->LastUserID == null) {
            $discussion->LastUserID = $discussion->InsertUserID;
            $discussion->LastDate = $discussion->DateInserted;
        }

        // Translate Announce to Pinned.
        $pinned = false;
        $pinLocation = null;
        if (property_exists($discussion, 'Announce') && $discussion->Announce > 0) {
            $pinned = true;
            switch (intval($discussion->Announce)) {
                case 1:
                    $pinLocation = 'recent';
                    break;
                case 2:
                    $pinLocation = 'category';
            }
        }
        $discussion->pinned = $pinned;
        $discussion->pinLocation = $pinLocation;

        $this->EventArguments['Discussion'] = &$discussion;
        $this->fireEvent('SetCalculatedFields');
    }

    /**
     * Add SQL Where to account for archive date.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object $sql Gdn_SQLDriver
     */
    public function addArchiveWhere($sql = null) {
        if (is_null($sql)) {
            $sql = $this->SQL;
        }

        $exclude = Gdn::config('Vanilla.Archive.Exclude');
        if ($exclude) {
            $archiveDate = Gdn::config('Vanilla.Archive.Date');
            if ($archiveDate) {
                $sql->where('d.DateLastComment >', $archiveDate);
            }
        }
    }


    /**
     * Gets announced discussions.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $wheres SQL conditions.
     * @param int $offset The number of records to skip.
     * @param int $limit The number of records to limit the query to.
     * @return object SQL result.
     */
    public function getAnnouncements($wheres = '', $offset = 0, $limit = false) {
        $wheres = $this->combineWheres($this->getWheres(), $wheres);
        $session = Gdn::session();
        if ($limit === false) {
            c('Vanilla.Discussions.PerPage', 30);
        }
        $userID = $session->UserID > 0 ? $session->UserID : 0;
        $categoryID = val('d.CategoryID', $wheres, 0);
        $groupID = val('d.GroupID', $wheres, 0);
        // Get the discussion IDs of the announcements.
        $cacheKey = $this->getAnnouncementCacheKey($categoryID);
        if ($groupID == 0) {
            $this->SQL->cache($cacheKey);
        }
        $this->SQL->select('d.DiscussionID')
            ->from('Discussion d');

        $announceOverride = false;
        $whereFields = array_keys($wheres);
        foreach ($whereFields as $field) {
            if (stringBeginsWith($field, 'd.Announce')) {
                $announceOverride = true;
                break;
            }
        }
        if (!$announceOverride) {
            if (!is_array($categoryID) && ($categoryID > 0 || $groupID > 0)) {
                $this->SQL->where('d.Announce >', '0');
            } else {
                $this->SQL->where('d.Announce', 1);
            }
        }

        if ($groupID > 0) {
            $this->SQL->where('d.GroupID', $groupID);
        } elseif (is_array($categoryID)) {
            $this->SQL->whereIn('d.CategoryID', $categoryID);
        } elseif ($categoryID > 0) {
            $this->SQL->where('d.CategoryID', $categoryID);
        }

        $announcementIDs = $this->SQL->get()->resultArray();
        $announcementIDs = array_column($announcementIDs, 'DiscussionID');

        // Short circuit querying when there are no announcements.
        if (count($announcementIDs) == 0) {
            $this->_AnnouncementIDs = $announcementIDs;
            return new Gdn_DataSet();
        }

        $this->discussionSummaryQuery([], false);

        if (!empty($wheres)) {
            $this->SQL->where($wheres);
        }

        if ($userID) {
            $this->SQL->select('w.UserID', '', 'WatchUserID')
                ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
                ->select('w.CountComments', '', 'CountCommentWatch')
                ->select('w.Participated')
                ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$userID, 'left');
        } else {
            // Don't join in the user table when we are a guest.
            $this->SQL->select('null as WatchUserID, null as DateLastViewed, null as Dismissed, null as Bookmarked, null as CountCommentWatch');
        }

        // Add conditions passed.
        $this->SQL->whereIn('d.DiscussionID', $announcementIDs);

        // If we aren't viewing announcements in a category then only show global announcements.
        if (empty($wheres) || is_array($categoryID)) {
            $this->SQL->where('d.Announce', 1);
        } else {
            $this->SQL->where('d.Announce >', 0);
        }

        // If we allow users to dismiss discussions, skip ones this user dismissed
        if (c('Vanilla.Discussions.Dismiss', 1) && $userID) {
            $this->SQL
                ->where('coalesce(w.Dismissed, \'0\')', '0', false);
        }

        $this->SQL->limit($limit, $offset);

        $orderBy = $this->getOrderBy();
        foreach ($orderBy as $field => $direction) {
            $this->SQL->orderBy($this->addFieldPrefix($field), $direction);
        }

        $data = $this->SQL->get();

        // Save the announcements that were fetched for later removal.
        $announcementIDs = [];
        foreach ($data as $row) {
            $announcementIDs[] = val('DiscussionID', $row);
        }
        $this->_AnnouncementIDs = $announcementIDs;

        $this->addDiscussionColumns($data);

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->addDenormalizedViews($data);
        }

        Gdn::userModel()->joinUsers($data, ['FirstUserID', 'LastUserID']);
        CategoryModel::joinCategories($data);

        // Prep and fire event
        $this->EventArguments['Data'] = $data;
        $this->fireEvent('AfterAddColumns');

        return $data;
    }

    /**
     * @param int $categoryID Category ID,
     * @return string $key CacheKey name to be used for cache.
     */
    public function getAnnouncementCacheKey($categoryID = 0) {
        $key = 'Announcements';
        if (!is_array($categoryID) && $categoryID > 0) {
            $key .= ':'.$categoryID;
        }
        return $key;
    }

    /**
     * Gets all users who have bookmarked the specified discussion.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $discussionID Unique ID to find bookmarks for.
     * @return object SQL result.
     */
    public function getBookmarkUsers($discussionID) {
        return $this->SQL
            ->select('UserID')
            ->from('UserDiscussion')
            ->where('DiscussionID', $discussionID)
            ->where('Bookmarked', '1')
            ->get();
    }

    /**
     *
     * Get discussions for a user.
     *
     * Events: BeforeGetByUser
     *
     * @since 2.1
     * @access public
     *
     * @param int $userID Which user to get discussions for.
     * @param int $limit Max number to get.
     * @param int $offset Number to skip.
     * @param int $lastDiscussionID A hint for quicker paging.
     * @param int $watchUserID User to use for read/unread data.
     * @return Gdn_DataSet SQL results.
     */
    public function getByUser($userID, $limit, $offset, $lastDiscussionID = false, $watchUserID = false) {
        $perms = DiscussionModel::categoryPermissions();

        if (is_array($perms) && empty($perms)) {
            return new Gdn_DataSet([]);
        }

        // Allow us to set perspective of a different user.
        if (empty($watchUserID)) {
            $watchUserID = $userID;
        }

        // The point of this query is to select from one comment table, but filter and sort on another.
        // This puts the paging into an index scan rather than a table scan.
        $this->SQL
            ->select('d2.*')
            ->select('d2.InsertUserID', '', 'FirstUserID')
            ->select('d2.DateInserted', '', 'FirstDate')
            ->select('d2.DateLastComment', '', 'LastDate')
            ->select('d2.LastCommentUserID', '', 'LastUserID')
            ->from('Discussion d')
            ->join('Discussion d2', 'd.DiscussionID = d2.DiscussionID')
            ->where('d.InsertUserID', $userID)
            ->orderBy('d.DiscussionID', 'desc');

        // Join in the watch data.
        if ($watchUserID > 0) {
            $this->SQL
                ->select('w.UserID', '', 'WatchUserID')
                ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
                ->select('w.CountComments', '', 'CountCommentWatch')
                ->select('w.Participated')
                ->join('UserDiscussion w', 'd2.DiscussionID = w.DiscussionID and w.UserID = '.$watchUserID, 'left');
        } else {
            $this->SQL
                ->select('0', '', 'WatchUserID')
                ->select('now()', '', 'DateLastViewed')
                ->select('0', '', 'Dismissed')
                ->select('0', '', 'Bookmarked')
                ->select('0', '', 'CountCommentWatch')
                ->select('d.Announce', '', 'IsAnnounce');
        }

        if (!empty($lastDiscussionID)) {
            // The last comment id from the last page was given and can be used as a hint to speed up the query.
            $this->SQL
                ->where('d.DiscussionID <', $lastDiscussionID)
                ->limit($limit);
        } else {
            $this->SQL->limit($limit, $offset);
        }

        $this->fireEvent('BeforeGetByUser');

        $data = $this->SQL->get();


        $result = &$data->result();
        $this->LastDiscussionCount = $data->numRows();

        if (count($result) > 0) {
            $this->LastDiscussionID = $result[count($result) - 1]->DiscussionID;
        } else {
            $this->LastDiscussionID = null;
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

        // Change discussions returned based on additional criteria
        $this->addDiscussionColumns($data);

        // Join in the users.
        Gdn::userModel()->joinUsers($data, ['FirstUserID', 'LastUserID']);
        CategoryModel::joinCategories($data);

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->addDenormalizedViews($data);
        }

        $this->EventArguments['Data'] = &$data;
        $this->fireEvent('AfterAddColumns');

        return $data;
    }

    /**
     * Get all the users that have participated in the discussion.
     * @param int $discussionID
     * @return Gdn_DataSet
     */
    public function getParticipatedUsers($discussionID) {
        return $this->SQL
            ->select('UserID')
            ->from('UserDiscussion')
            ->where('DiscussionID', $discussionID)
            ->where('Participated', '1')
            ->get();
    }

    /**
     * Identify current user's category permissions and set as local array.
     *
     * @since 2.0.0
     * @access public
     *
     * @param bool $escape Prepends category IDs with @
     * @param bool $forceRefresh Reset the cache and pull fresh permission values.
     * @return array Protected local _CategoryPermissions
     */
    public static function categoryPermissions($escape = false, $forceRefresh = false) {
        if (is_null(self::$categoryPermissions) || $forceRefresh) {
            $session = Gdn::session();

            if ((is_object($session->User) && $session->User->Admin)) {
                self::$categoryPermissions = true;
            } elseif (c('Garden.Permissions.Disabled.Category')) {
                if ($session->checkPermission('Vanilla.Discussions.View')) {
                    self::$categoryPermissions = true;
                } else {
                    self::$categoryPermissions = []; // no permission
                }
            } else {
                $categories = CategoryModel::categories();
                $iDs = [];

                foreach ($categories as $iD => $category) {
                    if ($category['PermsDiscussionsView']) {
                        $iDs[] = $iD;
                    }
                }

                // Check to see if the user has permission to all categories. This is for speed.
                $categoryCount = count($categories);

                if (count($iDs) == $categoryCount) {
                    self::$categoryPermissions = true;
                } else {
                    self::$categoryPermissions = [];
                    foreach ($iDs as $iD) {
                        self::$categoryPermissions[] = ($escape ? '@' : '').$iD;
                    }
                }
            }
        }

        return self::$categoryPermissions;
    }

    public function fetchPageInfo($url, $throwError = false) {
        $pageInfo = fetchPageInfo($url, 3);

        $title = val('Title', $pageInfo, '');
        if ($title == '') {
            if ($throwError) {
                throw new Gdn_UserException(t("The page didn't contain any information."));
            }

            $title = formatString(t('Undefined discussion subject.'), ['Url' => $url]);
        } else {
            if ($strip = c('Vanilla.Embed.StripPrefix')) {
                $title = stringBeginsWith($title, $strip, true, true);
            }

            if ($strip = c('Vanilla.Embed.StripSuffix')) {
                $title = stringEndsWith($title, $strip, true, true);
            }
        }
        $title = trim($title);

        $description = val('Description', $pageInfo, '');
        $images = val('Images', $pageInfo, []);
        $body = formatString(t('EmbeddedDiscussionFormat'), [
            'Title' => $title,
            'Excerpt' => $description,
            'Image' => (count($images) > 0 ? img(val(0, $images), ['class' => 'LeftAlign']) : ''),
            'Url' => $url
        ]);
        if ($body == '') {
            $body = $url;
        }
        if ($body == '') {
            $body = formatString(t('EmbeddedNoBodyFormat.'), ['Url' => $url]);
        }

        $result = [
            'Name' => $title,
            'Body' => $body,
            'Format' => 'Html'];

        return $result;
    }

    /**
     * Get the count of discussions for an individual category.
     *
     * @param int|array $categoryID The category to get the count of or an array of category IDs to get the count of.
     * @return int Returns the count of discussions.
     */
    public function getCountForCategory($categoryID) {
        $count = 0;
        foreach ((array)$categoryID as $id) {
            $category = CategoryModel::categories((int)$id);
            $count += (int)val('CountDiscussions', $category, 0);
        }
        return $count;
    }

    /**
     * Count how many discussions match the given criteria.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $wheres SQL conditions.
     * @param null $unused Not used.
     * @return int Number of discussions.
     */
    public function getCount($wheres = [], $unused = null) {
        // Get permissions.
        $perms = self::categoryPermissions();

        // No permissions... That is sad :(
        if (!$perms) {
            return 0;
        }

        $wheres = $this->combineWheres($this->getWheres(), $wheres);

        $this->EventArguments['Wheres'] = &$wheres;
        $this->fireEvent('BeforeGetCount'); // @see 'BeforeGet' for consistency in count vs. results

        // This should not happen but let's throw a warning just in case.
        if (!is_array($wheres)) {
            trigger_error('Wheres needs to be an array.', E_USER_DEPRECATED);
            $wheres = [];
        }

        $hasWhere = !empty($wheres);
        $whereOnCategories = $hasWhere && isset($wheres['d.CategoryID']);
        $whereOnCategoriesOnly = $whereOnCategories && count($wheres) === 1;

        // We have access to everything and are requesting only by categories. Let's use the cache!
        if ($perms === true && $whereOnCategoriesOnly) {
            return $this->getCountForCategory($wheres['d.CategoryID']);
        }

        // Only keep the categories we actually want and have permission to.
        if ($whereOnCategories && is_array($perms)) {
            $categoryIDs = (array)$wheres['d.CategoryID'];
            if ($categoryIDs) {
                $perms = array_intersect($categoryIDs, $perms);
            }
        }

        // Use the cache if we are requesting only by categories or have no where at all.
        // In those cases we are gonna use the cached count on the categories we have permission to.
        if (!$hasWhere || $whereOnCategoriesOnly) {
            $categories = CategoryModel::categories();

            // We have permission to everything.
            if ($perms === true) {
                $perms = array_keys($categories);
            }

            $count = 0;
            foreach ($perms as $categoryID) {
                if (isset($categories[$categoryID])) {
                    $count += (int)$categories[$categoryID]['CountDiscussions'];
                }
            }

            return $count;
        }

        // Filter the results by permissions.
        if (is_array($perms)) {
            $this->SQL->whereIn('c.CategoryID', $perms);
        }

        return $this->SQL
            ->select('d.DiscussionID', 'count', 'CountDiscussions')
            ->from('Discussion d')
            ->join('Category c', 'd.CategoryID = c.CategoryID')
            ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.Gdn::session()->UserID, 'left')
            ->where($wheres)
            ->get()
            ->firstRow()
            ->CountDiscussions;
    }

    /**
     * Count how many discussions match the given criteria.
     *
     * @deprecated since 2.3
     *
     * @param array $wheres SQL conditions.
     * @return int Number of discussions.
     * @since 2.0.0
     */
    public function getUnreadCount($wheres = '') {
        if (is_array($wheres) && count($wheres) == 0) {
            $wheres = '';
        }

        // Check permission and limit to categories as necessary
        $perms = self::categoryPermissions();

        if (!$wheres || (count($wheres) == 1 && isset($wheres['d.CategoryID']))) {
            // Grab the counts from the faster category cache.
            if (isset($wheres['d.CategoryID'])) {
                $categoryIDs = (array)$wheres['d.CategoryID'];
                if ($perms === false) {
                    $categoryIDs = [];
                } elseif (is_array($perms)) {
                    $categoryIDs = array_intersect($categoryIDs, $perms);
                }

                if (count($categoryIDs) == 0) {
                    return 0;
                } else {
                    $perms = $categoryIDs;
                }
            }

            $categories = CategoryModel::categories();
            $count = 0;

            foreach ($categories as $cat) {
                if (is_array($perms) && !in_array($cat['CategoryID'], $perms)) {
                    continue;
                }
                $count += (int)$cat['CountDiscussions'];
            }
            return $count;
        }

        if ($perms !== true) {
            $this->SQL->whereIn('c.CategoryID', $perms);
        }

        $this->EventArguments['Wheres'] = &$wheres;
        $this->fireEvent('BeforeGetUnreadCount'); // @see 'BeforeGet' for consistency in count vs. results

        $this->SQL
            ->select('d.DiscussionID', 'count', 'CountDiscussions')
            ->from('Discussion d')
            ->join('Category c', 'd.CategoryID = c.CategoryID')
            ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.Gdn::session()->UserID, 'left')
            ->where('d.CountComments >', 'COALESCE(w.CountComments, 0)', true, false)
            ->where($wheres);

        $result = $this->SQL
            ->get()
            ->firstRow()
            ->CountDiscussions;

        return $result;
    }

    /**
     * Get data for a single discussion by ForeignID.
     *
     * @param int $foreignID Foreign ID of discussion to get.
     * @param string $type The record type or an empty string for any record type.
     * @return stdClass SQL result.
     * @since 2.0.18
     */
    public function getForeignID($foreignID, $type = '') {
        $hash = foreignIDHash($foreignID);
        $session = Gdn::session();
        $this->fireEvent('BeforeGetForeignID');
        $this->SQL
            ->select('d.*')
            ->select('ca.Name', '', 'Category')
            ->select('ca.UrlCode', '', 'CategoryUrlCode')
            ->select('ca.PermissionCategoryID')
            ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
            ->select('w.CountComments', '', 'CountCommentWatch')
            ->select('w.Participated')
            ->select('d.DateLastComment', '', 'LastDate')
            ->select('d.LastCommentUserID', '', 'LastUserID')
            ->select('lcu.Name', '', 'LastName')
            ->select('iu.Name', '', 'InsertName')
            ->select('iu.Photo', '', 'InsertPhoto')
            ->from('Discussion d')
            ->join('Category ca', 'd.CategoryID = ca.CategoryID', 'left')
            ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$session->UserID, 'left')
            ->join('User iu', 'd.InsertUserID = iu.UserID', 'left')// Insert user
            ->join('Comment lc', 'd.LastCommentID = lc.CommentID', 'left')// Last comment
            ->join('User lcu', 'lc.InsertUserID = lcu.UserID', 'left')// Last comment user
            ->where('d.ForeignID', $hash);

        if ($type != '') {
            $this->SQL->where('d.Type', $type);
        }

        $discussion = $this->SQL
            ->get()
            ->firstRow();

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->addDenormalizedViews($discussion);
        }

        return $discussion;
    }

    /**
     * Get data for a single discussion by ID.
     *
     * @param int $discussionID Unique ID of discussion to get.
     * @param string $dataSetType One of the **DATASET_TYPE_*** constants.
     * @param array $options An array of extra options for the query.
     * @return mixed SQL result.
     */
    public function getID($discussionID, $dataSetType = DATASET_TYPE_OBJECT, $options = []) {
        $session = Gdn::session();
        $this->fireEvent('BeforeGetID');

        $this->options($options);

        $discussion = $this->SQL
            ->select('d.*')
            ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
            ->select('w.CountComments', '', 'CountCommentWatch')
            ->select('w.Participated')
            ->select('d.DateLastComment', '', 'LastDate')
            ->select('d.LastCommentUserID', '', 'LastUserID')
            ->from('Discussion d')
            ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$session->UserID, 'left')
            ->where('d.DiscussionID', $discussionID)
            ->get()
            ->firstRow();

        if (!$discussion) {
            return $discussion;
        }

        $this->calculate($discussion);

        // Join in the users.
        $discussion = [$discussion];
        Gdn::userModel()->joinUsers($discussion, ['LastUserID', 'InsertUserID']);
        $discussion = $discussion[0];

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->addDenormalizedViews($discussion);
        }

        return $dataSetType == DATASET_TYPE_ARRAY ? (array)$discussion : $discussion;
    }

    /**
     * Get discussions that have IDs in the provided array.
     *
     * @param array $discussionIDs Array of DiscussionIDs to get.
     * @return Gdn_DataSet SQL result.
     * @since 2.0.18
     */
    public function getIn($discussionIDs) {
        $session = Gdn::session();
        $this->fireEvent('BeforeGetIn');
        $result = $this->SQL
            ->select('d.*')
            ->select('ca.Name', '', 'Category')
            ->select('ca.UrlCode', '', 'CategoryUrlCode')
            ->select('ca.PermissionCategoryID')
            ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
            ->select('w.CountComments', '', 'CountCommentWatch')
            ->select('w.Participated')
            ->select('d.DateLastComment', '', 'LastDate')
            ->select('d.LastCommentUserID', '', 'LastUserID')
            ->select('lcu.Name', '', 'LastName')
            ->select('iu.Name', '', 'InsertName')
            ->select('iu.Photo', '', 'InsertPhoto')
            ->from('Discussion d')
            ->join('Category ca', 'd.CategoryID = ca.CategoryID', 'left')
            ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$session->UserID, 'left')
            ->join('User iu', 'd.InsertUserID = iu.UserID', 'left')// Insert user
            ->join('Comment lc', 'd.LastCommentID = lc.CommentID', 'left')// Last comment
            ->join('User lcu', 'lc.InsertUserID = lcu.UserID', 'left')// Last comment user
            ->whereIn('d.DiscussionID', $discussionIDs)
            ->get();

        // Splitting views off to side table. Aggregate cached keys here.
        if (c('Vanilla.Views.Denormalize', false)) {
            $this->addDenormalizedViews($result);
        }

        return $result;
    }

    /**
     * Get discussions sort order based on config and optional user preference.
     *
     * @return string Column name.
     */
    public static function getSortField() {
        deprecated("getSortField", "getOrderBy");
        $sortField = c('Vanilla.Discussions.SortField', 'd.DateLastComment');
        if (c('Vanilla.Discussions.UserSortField')) {
            $sortField = Gdn::session()->getPreference('Discussions.SortField', $sortField);
        }

        return $sortField;
    }

    /**
     *
     *
     * @param int $discussionID
     * @return mixed|null
     */
    public static function getViewsFallback($discussionID) {
        // Not found. Check main table.
        $views = Gdn::sql()
            ->select('CountViews')
            ->from('Discussion')
            ->where('DiscussionID', $discussionID)
            ->get()
            ->value('CountViews', null);

        // Found. Insert into denormalized table and return.
        if (!is_null($views)) {
            return $views;
        }

        return null;
    }

    /**
     * Marks the specified announcement as dismissed by the specified user.
     *
     * @param int $discussionID Unique ID of discussion being affected.
     * @param int $userID Unique ID of the user being affected.
     */
    public function dismissAnnouncement($discussionID, $userID) {
        $count = $this->SQL
            ->select('UserID')
            ->from('UserDiscussion')
            ->where('DiscussionID', $discussionID)
            ->where('UserID', $userID)
            ->get()
            ->numRows();

        $countComments = $this->SQL
            ->select('CountComments')
            ->from('Discussion')
            ->where('DiscussionID', $discussionID)
            ->get()
            ->firstRow()
            ->CountComments;

        if ($count > 0) {
            $this->SQL
                ->update('UserDiscussion')
                ->set('CountComments', $countComments)
                ->set('DateLastViewed', Gdn_Format::toDateTime())
                ->set('Dismissed', '1')
                ->where('DiscussionID', $discussionID)
                ->where('UserID', $userID)
                ->put();
        } else {
            $this->SQL->options('Ignore', true);
            $this->SQL->insert(
                'UserDiscussion',
                [
                    'UserID' => $userID,
                    'DiscussionID' => $discussionID,
                    'CountComments' => $countComments,
                    'DateLastViewed' => Gdn_Format::toDateTime(),
                    'Dismissed' => '1'
                ]
            );
        }
    }

    /**
     * An event firing wrapper for Gdn_Model::setField().
     *
     * @param int $rowID
     * @param string $property
     * @param mixed $value
     */
    public function setField($rowID, $property, $value = false) {
        if (!is_array($property)) {
            $property = [$property => $value];
        }

        $this->EventArguments['DiscussionID'] = $rowID;
        if (!is_array($property)) {
            $this->EventArguments['SetField'] = [$property => $value];
        } else {
            $this->EventArguments['SetField'] = $property;
        }

        parent::setField($rowID, $property, $value);
        $this->fireEvent('AfterSetField');
    }

    /**
     * Inserts or updates the discussion via form values.
     *
     * Events: BeforeSaveDiscussion, AfterValidateDiscussion, AfterSaveDiscussion.
     *
     * @param array $formPostValues Data sent from the form model.
     * @param array $settings
     * - CheckPermission - Check permissions during insert. Default true.
     *
     * @return int $discussionID Unique ID of the discussion.
     */
    public function save($formPostValues, $settings = false) {
        // Define the primary key in this model's table.
        $this->defineSchema();

        // If the site isn't configured to use categories, don't allow one to be set.
        if (!c('Vanilla.Categories.Use', true)) {
            unset($formPostValues['CategoryID']);
        }

        // Add & apply any extra validation rules:
        if (array_key_exists('Body', $formPostValues)) {
            $this->Validation->applyRule('Body', 'Required');
            $this->Validation->addRule('MeAction', 'function:ValidateMeAction');
            $this->Validation->applyRule('Body', 'MeAction');
            $maxCommentLength = Gdn::config('Vanilla.Comment.MaxLength');
            if (is_numeric($maxCommentLength) && $maxCommentLength > 0) {
                $this->Validation->setSchemaProperty('Body', 'Length', $maxCommentLength);
                $this->Validation->applyRule('Body', 'Length');
            }
        }

        // Validate category permissions.
        $categoryID = val('CategoryID', $formPostValues);
        if ($categoryID !== false) {
            $checkPermission = val('CheckPermission', $settings, true);
            $category = CategoryModel::categories($categoryID);
            if (!$category) {
                $this->Validation->addValidationResult('CategoryID', "@Category {$categoryID} does not exist.");
            } elseif ($checkPermission && !CategoryModel::checkPermission($category, 'Vanilla.Discussions.Add')) {
                $this->Validation->addValidationResult('CategoryID', 'You do not have permission to post in this category');
            }
        }

        // Get the DiscussionID from the form so we know if we are inserting or updating.
        $discussionID = val('DiscussionID', $formPostValues, '');

        // See if there is a source ID.
        if (val('SourceID', $formPostValues)) {
            $discussionID = $this->SQL->getWhere('Discussion', arrayTranslate($formPostValues, ['Source', 'SourceID']))->value('DiscussionID');
            if ($discussionID) {
                $formPostValues['DiscussionID'] = $discussionID;
            }
        } elseif (val('ForeignID', $formPostValues)) {
            $discussionID = $this->SQL->getWhere('Discussion', ['ForeignID' => $formPostValues['ForeignID']])->value('DiscussionID');
            if ($discussionID) {
                $formPostValues['DiscussionID'] = $discussionID;
            }
        }

        $insert = $discussionID == '' ? true : false;
        $this->EventArguments['Insert'] = $insert;

        if ($insert) {
            unset($formPostValues['DiscussionID']);
            // If no category ID is defined, grab the first available.
            if (!val('CategoryID', $formPostValues) && !c('Vanilla.Categories.Use')) {
                $formPostValues['CategoryID'] = val('CategoryID', CategoryModel::defaultCategory(), -1);
            }

            $this->addInsertFields($formPostValues);

            // The UpdateUserID used to be required. Just add it if it still is.
            if (!$this->Schema->getProperty('UpdateUserID', 'AllowNull', true)) {
                $formPostValues['UpdateUserID'] = $formPostValues['InsertUserID'];
            }

            // $FormPostValues['LastCommentUserID'] = $Session->UserID;
            $formPostValues['DateLastComment'] = $formPostValues['DateInserted'];
        } else {
            // Add the update fields.
            $this->addUpdateFields($formPostValues);
        }

        // Pinned-to-Announce translation
        $isPinned = val('Pinned', $formPostValues, null);
        if ($isPinned !== null) {
            $announce = 0;
            $isPinned = filter_var($isPinned, FILTER_VALIDATE_BOOLEAN);
            if ($isPinned) {
                $pinLocation = strtolower(val('PinLocation', $formPostValues, 'category'));
                switch ($pinLocation) {
                    case 'recent':
                        $announce = 1;
                        break;
                    default:
                        $announce = 2;
                }

            }
            $formPostValues['Announce'] = $announce;
            unset($announce);
        }

        // Set checkbox values to zero if they were unchecked
        if (val('Announce', $formPostValues, '') === false) {
            $formPostValues['Announce'] = 0;
        }

        if (val('Closed', $formPostValues, '') === false) {
            $formPostValues['Closed'] = 0;
        }

        if (val('Sink', $formPostValues, '') === false) {
            $formPostValues['Sink'] = 0;
        }

        //	Prep and fire event
        $this->EventArguments['FormPostValues'] = &$formPostValues;
        $this->EventArguments['DiscussionID'] = $discussionID;
        $this->fireEvent('BeforeSaveDiscussion');

        // Validate the form posted values
        $this->validate($formPostValues, $insert);
        $validationResults = $this->validationResults();

        // If the body is not required, remove it's validation errors.
        $bodyRequired = c('Vanilla.DiscussionBody.Required', true);
        if (!$bodyRequired && array_key_exists('Body', $validationResults)) {
            unset($validationResults['Body']);
        }

        if (count($validationResults) == 0) {
            // Backward compatible check for flood control
            if (!val('SpamCheck', $this, true)) {
                deprecated('DiscussionModel->SpamCheck attribute', 'FloodControlTrait->setFloodControlEnabled()');
                $this->setFloodControlEnabled(false);
            }

            // If the post is new and it validates, make sure the user isn't spamming
            if (!$insert || !$this->checkUserSpamming(Gdn::session()->UserID, $this->floodGate)) {
                // Get all fields on the form that relate to the schema
                $fields = $this->Validation->schemaValidationFields();

                // Check for spam.
                $spam = SpamModel::isSpam('Discussion', $fields);
                if ($spam) {
                    return SPAM;
                }

                // Get DiscussionID if one was sent
                $discussionID = intval(val('DiscussionID', $fields, 0));

                // Remove the primary key from the fields for saving.
                unset($fields['DiscussionID']);
                $storedCategoryID = false;

                if ($discussionID > 0) {
                    // Updating
                    $stored = $this->getID($discussionID, DATASET_TYPE_OBJECT);

                    // Block Format change if we're forcing the formatter.
                    if (c('Garden.ForceInputFormatter')) {
                        unset($fields['Format']);
                    }

                    $isValid = true;
                    $invalidReturnType = false;
                    $insertUserID = val('InsertUserID', $stored);
                    $dateInserted = val('DateInserted', $stored);
                    $this->EventArguments['DiscussionData'] = array_merge($fields, ['DiscussionID' => $discussionID, 'InsertUserID' => $insertUserID,'DateInserted' => $dateInserted]);
                    $this->EventArguments['IsValid'] = &$isValid;
                    $this->EventArguments['InvalidReturnType'] = &$invalidReturnType;
                    $this->fireEvent('AfterValidateDiscussion');

                    if (!$isValid) {
                        return $invalidReturnType;
                    }

                    // Clear the cache if necessary.
                    $cacheKeys = [];
                    if (val('Announce', $stored) != val('Announce', $fields)) {
                        $cacheKeys[] = $this->getAnnouncementCacheKey();
                        $cacheKeys[] = $this->getAnnouncementCacheKey(val('CategoryID', $stored));
                    }
                    if (val('CategoryID', $stored) != val('CategoryID', $fields)) {
                        $cacheKeys[] = $this->getAnnouncementCacheKey(val('CategoryID', $fields));
                    }
                    foreach ($cacheKeys as $cacheKey) {
                        Gdn::cache()->remove($cacheKey);
                    }

                    // The primary key was removed from the form fields, but we need it for logging.
                    LogModel::logChange('Edit', 'Discussion', array_merge($fields, ['DiscussionID' => $discussionID]));

                    self::serializeRow($fields);
                    $this->SQL->put($this->Name, $fields, [$this->PrimaryKey => $discussionID]);

                    if (val('CategoryID', $stored) != val('CategoryID', $fields)) {
                        $storedCategoryID = val('CategoryID', $stored);
                    }

                } else {
                    // Inserting.
                    if (!val('Format', $fields) || c('Garden.ForceInputFormatter')) {
                        $fields['Format'] = c('Garden.InputFormatter', '');
                    }

                    if (c('Vanilla.QueueNotifications')) {
                        $fields['Notified'] = ActivityModel::SENT_PENDING;
                    }

                    // Check for approval
                    $approvalRequired = checkRestriction('Vanilla.Approval.Require');
                    if ($approvalRequired && !val('Verified', Gdn::session()->User)) {
                        LogModel::insert('Pending', 'Discussion', $fields);
                        return UNAPPROVED;
                    }

                    $isValid = true;
                    $invalidReturnType = false;
                    $this->EventArguments['DiscussionData'] = $fields;
                    $this->EventArguments['IsValid'] = &$isValid;
                    $this->EventArguments['InvalidReturnType'] = &$invalidReturnType;
                    $this->fireEvent('AfterValidateDiscussion');

                    if (!$isValid) {
                        return $invalidReturnType;
                    }

                    // Create discussion
                    $this->serializeRow($fields);
                    $discussionID = $this->SQL->insert($this->Name, $fields);
                    $fields['DiscussionID'] = $discussionID;

                    // Update cached last post info for a category.
                    CategoryModel::updateLastPost($fields);

                    // Clear the cache if necessary.
                    if (val('Announce', $fields)) {
                        Gdn::cache()->remove($this->getAnnouncementCacheKey(val('CategoryID', $fields)));

                        if (val('Announce', $fields) == 1) {
                            Gdn::cache()->remove($this->getAnnouncementCacheKey());
                        }
                    }

                    // Update the user's discussion count.
                    $insertUser = Gdn::userModel()->getID($fields['InsertUserID']);
                    $this->updateUserDiscussionCount($fields['InsertUserID'], val('CountDiscussions', $insertUser, 0) > 100);

                    // Mark the user as participated and update DateLastViewed.
                    $this->SQL->replace(
                        'UserDiscussion',
                        ['Participated' => 1, 'DateLastViewed' => Gdn_Format::toDateTime()],
                        ['DiscussionID' => $discussionID, 'UserID' => val('InsertUserID', $fields)]
                    );

                    // Assign the new DiscussionID to the comment before saving.
                    $formPostValues['IsNewDiscussion'] = true;
                    $formPostValues['DiscussionID'] = $discussionID;

                    // Do data prep.
                    $discussionName = val('Name', $fields, '');
                    $story = val('Body', $fields, '');
                    $notifiedUsers = [];

                    $userModel = Gdn::userModel();
                    $activityModel = new ActivityModel();

                    if (val('Type', $formPostValues)) {
                        $code = 'HeadlineFormat.Discussion.'.$formPostValues['Type'];
                    } else {
                        $code = 'HeadlineFormat.Discussion';
                    }

                    $headlineFormat = t($code, '{ActivityUserID,user} started a new discussion: <a href="{Url,html}">{Data.Name,text}</a>');
                    $category = CategoryModel::categories(val('CategoryID', $fields));
                    $activity = [
                        'ActivityType' => 'Discussion',
                        'ActivityUserID' => $fields['InsertUserID'],
                        'HeadlineFormat' => $headlineFormat,
                        'RecordType' => 'Discussion',
                        'RecordID' => $discussionID,
                        'Route' => discussionUrl($fields, '', '/'),
                        'Data' => [
                            'Name' => $discussionName,
                            'Category' => val('Name', $category)
                        ]
                    ];

                    // Allow simple fulltext notifications
                    if (c('Vanilla.Activity.ShowDiscussionBody', false)) {
                        $activity['Story'] = $story;
                    }

                    // Notify all of the users that were mentioned in the discussion.
                    $usernames = getMentions($discussionName.' '.$story);

                    // Use our generic Activity for events, not mentions
                    $this->EventArguments['Activity'] = $activity;

                    // Notify everyone that has advanced notifications.
                    if (!c('Vanilla.QueueNotifications')) {
                        try {
                            $fields['DiscussionID'] = $discussionID;
                            $this->notifyNewDiscussion($fields, $activityModel, $activity);
                        } catch (Exception $ex) {
                            throw $ex;
                        }
                    }

                    // Notifications for mentions
                    foreach ($usernames as $username) {
                        $user = $userModel->getByUsername($username);
                        if (!$user) {
                            continue;
                        }

                        // Check user can still see the discussion.
                        if (!$this->canView($fields, $user->UserID)) {
                            continue;
                        }

                        $activity['HeadlineFormat'] = t('HeadlineFormat.Mention', '{ActivityUserID,user} mentioned you in <a href="{Url,html}">{Data.Name,text}</a>');

                        $activity['NotifyUserID'] = val('UserID', $user);
                        $activityModel->queue($activity, 'Mention');
                    }

                    // Throw an event for users to add their own events.
                    $this->EventArguments['Discussion'] = $fields;
                    $this->EventArguments['NotifiedUsers'] = $notifiedUsers;
                    $this->EventArguments['MentionedUsers'] = $usernames;
                    $this->EventArguments['ActivityModel'] = $activityModel;
                    $this->fireEvent('BeforeNotification');

                    // Send all notifications.
                    $activityModel->saveQueue();
                }

                // Get CategoryID of this discussion
                $discussion = $this->getID($discussionID, DATASET_TYPE_OBJECT);

                // Update discussion counter for affected categories.
                if ($insert || $storedCategoryID) {
                    CategoryModel::instance()->incrementLastDiscussion($discussion);
                }

                if ($storedCategoryID) {
                    $this->updateDiscussionCount($storedCategoryID);
                }

                // Fire an event that the discussion was saved.
                $this->EventArguments['FormPostValues'] = $formPostValues;
                $this->EventArguments['Fields'] = $fields;
                $this->EventArguments['DiscussionID'] = $discussionID;
                $this->fireEvent('AfterSaveDiscussion');
            }
        }

        return $discussionID;
    }

    /**
     *
     * @param int|array|stdClass $discussion
     * @param ActivityModel $activityModel
     * @param array $activity
     */
    public function notifyNewDiscussion($discussion, $activityModel, $activity) {
        if (is_numeric($discussion)) {
            $discussion = $this->getID($discussion);
        }

        $categoryID = val('CategoryID', $discussion);

        // Figure out the category that governs this notification preference.
        $i = 0;
        $category = CategoryModel::categories($categoryID);
        if (!$category) {
            return;
        }

        while ($category['Depth'] > 2 && $i < 20) {
            if (!$category || $category['Archived']) {
                return;
            }
            $i++;
            $category = CategoryModel::categories($category['ParentCategoryID']);
        }

        // Grab all of the users that need to be notified.
        $data = $this->SQL
            ->whereIn('Name', ['Preferences.Email.NewDiscussion.'.$category['CategoryID'], 'Preferences.Popup.NewDiscussion.'.$category['CategoryID']])
            ->get('UserMeta')->resultArray();

        $notifyUsers = [];
        foreach ($data as $row) {
            if (!$row['Value']) {
                continue;
            }

            $userID = $row['UserID'];
            // Check user can still see the discussion.
            if (!$this->canView($discussion, $userID)) {
                continue;
            }

            $name = $row['Name'];
            if (strpos($name, '.Email.') !== false) {
                $notifyUsers[$userID]['Emailed'] = ActivityModel::SENT_PENDING;
            } elseif (strpos($name, '.Popup.') !== false) {
                $notifyUsers[$userID]['Notified'] = ActivityModel::SENT_PENDING;
            }
        }

        $insertUserID = val('InsertUserID', $discussion);
        foreach ($notifyUsers as $userID => $prefs) {
            if ($userID == $insertUserID) {
                continue;
            }

            $activity['NotifyUserID'] = $userID;
            $activity['Emailed'] = val('Emailed', $prefs, false);
            $activity['Notified'] = val('Notified', $prefs, false);
            $activityModel->queue($activity);
        }
    }

    /**
     * Update the CountDiscussions value on the category based on the CategoryID being saved.
     *
     * @param int $categoryID Unique ID of category we are updating.
     * @param array|false $discussion The discussion to update the count for or **false** for all of them.
     */
    public function updateDiscussionCount($categoryID, $discussion = false) {
        $discussionID = val('DiscussionID', $discussion, false);
        if (strcasecmp($categoryID, 'All') == 0) {
            $exclude = (bool)Gdn::config('Vanilla.Archive.Exclude');
            $archiveDate = Gdn::config('Vanilla.Archive.Date');
            $params = [];
            $where = '';

            if ($exclude && $archiveDate) {
                $where = 'where d.DateLastComment > :ArchiveDate';
                $params[':ArchiveDate'] = $archiveDate;
            }

            // Update all categories.
            $sql = "update :_Category c
            left join (
              select
                d.CategoryID,
                coalesce(count(d.DiscussionID), 0) as CountDiscussions,
                coalesce(sum(d.CountComments), 0) as CountComments
              from :_Discussion d
              $where
              group by d.CategoryID
            ) d
              on c.CategoryID = d.CategoryID
            set
               c.CountDiscussions = coalesce(d.CountDiscussions, 0),
               c.CountComments = coalesce(d.CountComments, 0)";
            $sql = str_replace(':_', $this->Database->DatabasePrefix, $sql);
            $this->Database->query($sql, $params, 'DiscussionModel_UpdateDiscussionCount');

        } elseif (is_numeric($categoryID)) {
            $this->SQL
                ->select('d.DiscussionID', 'count', 'CountDiscussions')
                ->select('d.CountComments', 'sum', 'CountComments')
                ->from('Discussion d')
                ->where('d.CategoryID', $categoryID);

            $this->addArchiveWhere();

            $data = $this->SQL->get()->firstRow();
            $countDiscussions = (int)getValue('CountDiscussions', $data, 0);
            $countComments = (int)getValue('CountComments', $data, 0);

            $cacheAmendment = [
                'CountDiscussions' => $countDiscussions,
                'CountComments' => $countComments
            ];

            if ($discussionID) {
                $cacheAmendment = array_merge($cacheAmendment, [
                    'LastDiscussionID' => $discussionID,
                    'LastCommentID' => null,
                    'LastDateInserted' => val('DateInserted', $discussion)
                ]);
            }

            $categoryModel = new CategoryModel();
            $categoryModel->setField($categoryID, $cacheAmendment);
            $categoryModel->setRecentPost($categoryID);
        }
    }

    /**
     * @param int|array|stdClass $discussion The discussion ID or discussion.
     * @throws Exception
     * @deprecated
     */
    public function incrementNewDiscussion($discussion) {
        deprecated('DiscussionModel::incrementNewDiscussion', 'CategoryModel::incrementLastDiscussion');

        CategoryModel::instance()->incrementLastDiscussion($discussion);
    }

    /**
     * Update a user's discussion count.
     *
     * @param int $userID The user to calculate.
     * @param bool $inc Whether to increment of recalculate from scratch.
     */
    public function updateUserDiscussionCount($userID, $inc = false) {
        if ($inc) {
            $user = Gdn::userModel()->getID($userID);

            $countDiscussions = val('CountDiscussions', $user);
            // Increment if 100 or greater; Recalculate on 120, 140 etc.
            if ($countDiscussions >= 100 && $countDiscussions % 20 !== 0) {
                $this->SQL->update('User')
                    ->set('CountDiscussions', 'CountDiscussions + 1', false)
                    ->where('UserID', $userID)
                    ->put();

                Gdn::userModel()->updateUserCache($userID, 'CountDiscussions', $countDiscussions + 1);
                return;
            }
        }

        $countDiscussions = $this->SQL
            ->select('DiscussionID', 'count', 'CountDiscussions')
            ->from('Discussion')
            ->where('InsertUserID', $userID)
            ->get()->value('CountDiscussions', 0);

        // Save the count to the user table
        Gdn::userModel()->setField($userID, 'CountDiscussions', $countDiscussions);
    }

    /**
     * Update and get bookmark count for the specified user.
     *
     * @param int $userID Unique ID of user to update.
     * @return int Total number of bookmarks user has.
     */
    public function setUserBookmarkCount($userID) {
        $count = $this->userBookmarkCount($userID);
        Gdn::userModel()->setField($userID, 'CountBookmarks', $count);

        return $count;
    }

    /**
     * Updates a discussion field.
     *
     * By default, this toggles the specified between '1' and '0'. If $forceValue
     * is provided, the field is set to this value instead. An example use is
     * announcing and unannouncing a discussion.
     *
     * @param int $discussionID Unique ID of discussion being updated.
     * @param string $property Name of field to be updated.
     * @param mixed $forceValue If set, overrides toggle behavior with this value.
     * @return mixed Value that was ultimately set for the field.
     */
    public function setProperty($discussionID, $property, $forceValue = null) {
        if ($forceValue !== null) {
            $value = $forceValue;
        } else {
            $discussion = $this->getID($discussionID);
            $value = ($discussion->$property == '1' ? '0' : '1');
        }
        $this->SQL
            ->update('Discussion')
            ->set($property, $value)
            ->where('DiscussionID', $discussionID)
            ->put();

        return $value;
    }

    /**
     * Sets the discussion score for specified user.
     *
     * @param int $discussionID Unique ID of discussion to update.
     * @param int $userID Unique ID of user setting score.
     * @param int $score New score for discussion.
     * @return int Total score.
     */
    public function setUserScore($discussionID, $userID, $score) {
        // Insert or update the UserDiscussion row
        $this->SQL->replace(
            'UserDiscussion',
            ['Score' => $score],
            ['DiscussionID' => $discussionID, 'UserID' => $userID]
        );

        // Get the total new score
        $totalScore = $this->SQL->select('Score', 'sum', 'TotalScore')
            ->from('UserDiscussion')
            ->where('DiscussionID', $discussionID)
            ->get()
            ->firstRow()
            ->TotalScore;

        // Update the Discussion's cached version
        $this->SQL->update('Discussion')
            ->set('Score', $totalScore)
            ->where('DiscussionID', $discussionID)
            ->put();

        return $totalScore;
    }

    /**
     * Gets the discussion score for specified user.
     *
     * @param int $discussionID Unique ID of discussion getting score for.
     * @param int $userID Unique ID of user whose score we're getting.
     * @return int Total score.
     */
    public function getUserScore($discussionID, $userID) {
        $data = $this->SQL->select('Score')
            ->from('UserDiscussion')
            ->where('DiscussionID', $discussionID)
            ->where('UserID', $userID)
            ->get()
            ->firstRow();

        return $data ? $data->Score : 0;
    }

    /**
     * Increments view count for the specified discussion.
     *
     * @param int $discussionID Unique ID of discussion to get +1 view.
     */
    public function addView($discussionID) {
        $incrementBy = 0;
        if (c('Vanilla.Views.Denormalize', false) &&
            Gdn::cache()->activeEnabled() &&
            Gdn::cache()->type() != Gdn_Cache::CACHE_TYPE_NULL
        ) {
            $writebackLimit = c('Vanilla.Views.DenormalizeWriteback', 10);
            $cacheKey = sprintf(DiscussionModel::CACHE_DISCUSSIONVIEWS, $discussionID);

            // Increment. If not success, create key.
            $views = Gdn::cache()->increment($cacheKey);
            if ($views === Gdn_Cache::CACHEOP_FAILURE) {
                Gdn::cache()->store($cacheKey, 1);
            }

            // Every X views, writeback to Discussions
            if (($views % $writebackLimit) == 0) {
                $incrementBy = floor($views / $writebackLimit) * $writebackLimit;
                Gdn::cache()->decrement($cacheKey, $incrementBy);
            }
        } else {
            $incrementBy = 1;
        }

        if ($incrementBy) {
            $this->SQL
                ->update('Discussion')
                ->set('CountViews', "CountViews + {$incrementBy}", false)
                ->where('DiscussionID', $discussionID)
                ->put();
        }

    }

    /**
     * Bookmarks (or unbookmarks) a discussion for the specified user.
     *
     * @param int $discussionID The unique id of the discussion.
     * @param int $userID The unique id of the user.
     * @param bool|null $bookmarked Whether or not to bookmark or unbookmark. Pass null to toggle the bookmark.
     * @return bool The new value of bookmarked.
     */
    public function bookmark($discussionID, $userID, $bookmarked = null) {
        // Get the current user discussion record.
        $userDiscussion = $this->SQL->getWhere(
            'UserDiscussion',
            ['DiscussionID' => $discussionID, 'UserID' => $userID]
        )->firstRow(DATASET_TYPE_ARRAY);

        if ($userDiscussion) {
            if ($bookmarked === null) {
                $bookmarked = !$userDiscussion['Bookmarked'];
            }

            // Update the bookmarked value.
            $this->SQL->put(
                'UserDiscussion',
                ['Bookmarked' => (int)$bookmarked],
                ['DiscussionID' => $discussionID, 'UserID' => $userID]
            );
        } else {
            if ($bookmarked === null) {
                $bookmarked = true;
            }

            // Insert the new bookmarked value.
            $this->SQL->options('Ignore', true)
                ->insert('UserDiscussion', [
                    'UserID' => $userID,
                    'DiscussionID' => $discussionID,
                    'Bookmarked' => (int)$bookmarked
                ]);
        }

        $this->EventArguments['DiscussionID'] = $discussionID;
        $this->EventArguments['UserID'] = $userID;
        $this->EventArguments['Bookmarked'] = $bookmarked;
        $this->fireEvent('AfterBookmark');

        return (bool)$bookmarked;
    }

    /**
     * Bookmarks (or unbookmarks) a discussion for specified user.
     *
     * Events: AfterBookmarkDiscussion.
     *
     * @param int $discussionID Unique ID of discussion to (un)bookmark.
     * @param int $userID Unique ID of user doing the (un)bookmarking.
     * @param object &$discussion Discussion data.
     * @return bool Current state of the bookmark (TRUE for bookmarked, FALSE for unbookmarked).
     */
    public function bookmarkDiscussion($discussionID, $userID, &$discussion = null) {
        $state = '1';

        $discussionData = $this->SQL
            ->select('d.*')
            ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
            ->select('w.CountComments', '', 'CountCommentWatch')
            ->select('w.UserID', '', 'WatchUserID')
            ->select('w.Participated')
            ->select('d.DateLastComment', '', 'LastDate')
            ->select('d.LastCommentUserID', '', 'LastUserID')
            ->select('lcu.Name', '', 'LastName')
            ->from('Discussion d')
            ->join('UserDiscussion w', "d.DiscussionID = w.DiscussionID and w.UserID = $userID", 'left')
            ->join('User lcu', 'd.LastCommentUserID = lcu.UserID', 'left')// Last comment user
            ->where('d.DiscussionID', $discussionID)
            ->get();

        $this->addDiscussionColumns($discussionData);
        $discussion = $discussionData->firstRow();

        if ($discussion->WatchUserID == '') {
            $this->SQL->options('Ignore', true);
            $this->SQL
                ->insert('UserDiscussion', [
                    'UserID' => $userID,
                    'DiscussionID' => $discussionID,
                    'Bookmarked' => $state
                ]);
            $discussion->Bookmarked = true;
        } else {
            $state = ($discussion->Bookmarked == '1' ? '0' : '1');
            $this->SQL
                ->update('UserDiscussion')
                ->set('Bookmarked', $state)
                ->where('UserID', $userID)
                ->where('DiscussionID', $discussionID)
                ->put();
            $discussion->Bookmarked = $state;
        }

        // Update the cached bookmark count on the discussion
        $bookmarkCount = $this->bookmarkCount($discussionID);
        $this->SQL->update('Discussion')
            ->set('CountBookmarks', $bookmarkCount)
            ->where('DiscussionID', $discussionID)
            ->put();
        $this->CountDiscussionBookmarks = $bookmarkCount;


        // Prep and fire event
        $this->EventArguments['Discussion'] = $discussion;
        $this->EventArguments['State'] = $state;
        $this->fireEvent('AfterBookmarkDiscussion');

        return $state == '1' ? true : false;
    }

    /**
     * Gets number of bookmarks specified discussion has (all users).
     *
     * @param int $discussionID Unique ID of discussion for which to tally bookmarks.
     * @return int Total number of bookmarks.
     */
    public function bookmarkCount($discussionID) {
        $data = $this->SQL
            ->select('DiscussionID', 'count', 'Count')
            ->from('UserDiscussion')
            ->where('DiscussionID', $discussionID)
            ->where('Bookmarked', '1')
            ->get()
            ->firstRow();

        return $data !== false ? $data->Count : 0;
    }

    /**
     * Gets number of bookmarks specified user has.
     *
     * @param int $userID Unique ID of user for which to tally bookmarks.
     * @return int Total number of bookmarks.
     */
    public function userBookmarkCount($userID) {
        $data = $this->SQL
            ->select('ud.DiscussionID', 'count', 'Count')
            ->from('UserDiscussion ud')
            ->join('Discussion d', 'd.DiscussionID = ud.DiscussionID')
            ->where('ud.UserID', $userID)
            ->where('ud.Bookmarked', '1')
            ->get()
            ->firstRow();

        return $data !== false ? $data->Count : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($where = [], $options = []) {
        if (is_numeric($where)) {
            deprecated('DiscussionModel->delete(int)', 'DiscussionModel->deleteID(int)');

            $result = $this->deleteID($where, $options);
            return $result;
        }

        throw new \BadMethodCallException("DiscussionModel->delete() is not supported.", 400);
    }

    /**
     * Delete a discussion. Update and/or delete all related data.
     *
     * Events: DeleteDiscussion.
     *
     * @param int $discussionID Unique ID of discussion to delete.
     * @param array $options Additional options to control the delete behavior. Not used for discussions.
     * @return bool Always returns **true**.
     */
    public function deleteID($discussionID, $options = []) {
        // Retrieve the users who have bookmarked this discussion.
        $bookmarkData = $this->getBookmarkUsers($discussionID);

        $data = $this->SQL
            ->select('*')
            ->from('Discussion')
            ->where('DiscussionID', $discussionID)
            ->get()->firstRow(DATASET_TYPE_ARRAY);

        $userID = false;
        $categoryID = false;
        if ($data) {
            $userID = $data['InsertUserID'];
            $categoryID = $data['CategoryID'];
        }

        // Prep and fire event
        $this->EventArguments['DiscussionID'] = $discussionID;
        $this->EventArguments['Discussion'] = $data;
        $this->fireEvent('DeleteDiscussion');

        // Setup logging.
        $log = val('Log', $options, true);
        $logOptions = val('LogOptions', $options, []);
        if ($log === true) {
            $log = 'Delete';
        }

        LogModel::beginTransaction();

        // Log all of the comment deletes.
        $comments = $this->SQL->getWhere('Comment', ['DiscussionID' => $discussionID])->resultArray();
        $totalComments = count($comments);

        if ($totalComments > 0 && $totalComments <= 25) {
            // A smaller number of comments should just be stored with the record.
            $data['_Data']['Comment'] = $comments;
            LogModel::insert($log, 'Discussion', $data, $logOptions);
        } else {
            LogModel::insert($log, 'Discussion', $data, $logOptions);
            foreach ($comments as $comment) {
                LogModel::insert($log, 'Comment', $comment, $logOptions);
            }
        }

        LogModel::endTransaction();

        $this->SQL->delete('Comment', ['DiscussionID' => $discussionID]);
        $this->SQL->delete('Discussion', ['DiscussionID' => $discussionID]);

        $this->SQL->delete('UserDiscussion', ['DiscussionID' => $discussionID]);
        $this->updateDiscussionCount($categoryID);

        // Update the last post info for the category and its parents.
        CategoryModel::instance()->refreshAggregateRecentPost($categoryID, true);

        // Decrement CountAllDiscussions for category and its parents.
        CategoryModel::decrementAggregateCount($categoryID, CategoryModel::AGGREGATE_DISCUSSION);

        // Decrement CountAllDiscussions for category and its parents.
        if ($totalComments > 0) {
            CategoryModel::decrementAggregateCount($categoryID, CategoryModel::AGGREGATE_COMMENT, $totalComments);
        }

        // Get the user's discussion count.
        $this->updateUserDiscussionCount($userID);

        // Update bookmark counts for users who had bookmarked this discussion
        foreach ($bookmarkData->result() as $user) {
            $this->setUserBookmarkCount($user->UserID);
        }

        return true;
    }

    /**
     * Convert tags from stored format to user-presentable format.
     *
     * @param string $tags A string encoded with {@link dbencode()}.
     * @return string Comma-separated tags.
     * @since 2.1
     */
    private function formatTags($tags) {
        // Don't bother if there aren't any tags
        if (!$tags) {
            return '';
        }

        // Get the array.
        if (preg_match('`^(a:)|{|\[`', $tags)) {
            $tagsArray = dbdecode($tags);
        } else {
            $tagsArray = $tags;
        }

        // Compensate for deprecated space-separated format
        if (is_string($tagsArray) && $tagsArray == $tags) {
            $tagsArray = explode(' ', $tags);
        }

        // Safe format
        $tagsArray = Gdn_Format::text($tagsArray);

        // Send back an comma-separated string
        return (is_array($tagsArray)) ? implode(',', $tagsArray) : '';
    }

    /**
     * We don't use this functionality anymore. Previously, you had to register any sorting field before sorting with it.
     *
     * @deprecated
     */
    public static function allowedSortFields() {
        deprecated("allowedSortFields");
    }

    /**
     * Tests whether a user has permission to view a specific discussion.
     *
     * @param object|array|integer $discussion The discussion ID or the discussion to test.
     * @param integer $userID The ID of the user to test permission for. If empty, it defaults to Session user.
     * @return bool Whether the user can view the discussion.
     */
    public function canView($discussion, $userID = 0) {
        $canView = $this->checkPermission($discussion, 'Vanilla.Discussions.View', $userID);
        return $canView;
    }

    /**
     * Tests whether a user has permission for a discussion by checking category-specific permissions.
     *
     * Fires an event that can override the calculated permission.
     *
     * @param object|array|integer $discussion The discussion ID or the discussion to test.
     * @param string $permission The category permission to test against the user.
     * @param integer $userID The ID of the user to test permission for. If empty, it defaults to Session user.
     * @return bool Whether the user has the specified permission privileges to the discussion.
     * @throws Exception Throws an exception when {@link $permission} is invalid.
     */
    public function checkPermission($discussion, $permission, $userID = 0) {
        // Either the permission string is a full permission, or we prepend 'Vanilla.Discussions.' to the permission.
        if (strpos($permission, '.') === false) {
            $permission = ucfirst(strtolower($permission));
            if (in_array($permission, $this->permissionTypes)) {
                $permission = 'Vanilla.Discussions.'.$permission;
            } else {
                throw new Exception(t('Unexpected discussion permission.'));
            }
        }
        // Default to session user.
        if (!$userID) {
            $userID = Gdn::session()->UserID;
        }
        // Fetch discussion.
        if (is_numeric($discussion)) {
            $discussion = $this->getID($discussion);
        }
        $userModel = Gdn::userModel();
        // Get category permission.
        $hasPermission = $userID && $userModel->getCategoryViewPermission($userID, val('CategoryID', $discussion), $permission);
        // Check if we've timed out.
        if (strpos(strtolower($permission), 'edit' !== false)) {
            $hasPermission &= self::editContentTimeout($discussion);
        }
        // Fire event to override permission ruling.
        $this->EventArguments['Discussion'] = $discussion;
        $this->EventArguments['UserID'] = $userID;
        $this->EventArguments['Permission'] = $permission;
        $this->EventArguments['HasPermission'] = &$hasPermission;
        $this->fireEvent('checkPermission');

        return $hasPermission;
    }

    /** SORT/FILTER */


    /**
     * Retrieves valid set key and filter keys pairs from an array, and returns the setKey => filterKey values.
     *
     * Works real well with unfiltered request arguments. (i.e., Gdn::request()->get()) Will only return safe
     * set key and filter key pairs from the filters array or an empty array if not found.
     *
     * @param array $array The array to get the filters from.
     * @return array The valid filters from the passed array or an empty array.
     */
    protected function getFiltersFromArray($array) {
        $filterKeys = [];
        foreach (self::getAllowedFilters() as $filterSet) {
            $filterSetKey = val('key', $filterSet);
            // Check if any of our filters are in the array. Filter key value is unsafe.
            if ($filterKey = val($filterSetKey, $array)) {
                // Check that value is in filter array to ensure safety.
                if (val($filterKey, val('filters', $filterSet))) {
                    // Value is safe.
                    $filterKeys[$filterSetKey] = $filterKey;
                } else {
                    Logger::log(
                        Logger::NOTICE,
                        'Filter: {filterSetKey} => {$filterKey} does not exist in the DiscussionModel\'s allowed filters array.',
                        ['filterSetKey' => $filterSetKey, 'filterKey' => $filterKey]
                    );
                }
            }
        }
        return $filterKeys;
    }

    /**
     * Retrieves the sort key from an array and if the value is valid, returns it.
     *
     * Works real well with unfiltered request arguments. (i.e., Gdn::request()->get()) Will only return a safe sort key
     * from the sort array or an empty string if not found.
     *
     * @param array $array The array to get the sort from.
     * @return string The valid sort from the passed array or an empty string.
     */
    protected function getSortFromArray($array) {
        $unsafeSortKey = val('sort', $array);
        foreach (self::getAllowedSorts() as $sort) {
            if ($unsafeSortKey == val('key', $sort)) {
                // Sort key is valid.
                return val('key', $sort);
            }
        }
        if ($unsafeSortKey) {
            Logger::log(
                Logger::NOTICE,
                'Sort: {unsafeSortKey} does not exist in the DiscussionModel\'s allowed sorts array.',
                ['unsafeSortKey' => $unsafeSortKey]
            );
        }
        return '';
    }

    /**
     * Checks the allowed sorts array for the string and it is valid, returns it the string.
     *
     * If not, returns an empty string. Will only return a safe sort key from the sort array or an empty string if not
     * found.
     *
     * @param string $string The string to get the sort from.
     * @return string A valid sort key or an empty string.
     */
    protected function getSortFromString($string) {
        if (val($string, self::$allowedSorts)) {
            // Sort key is valid.
            return $string;
        } else {
            Logger::log(
                Logger::NOTICE,
                'Sort "{sort}" does not exist in the DiscussionModel\'s allowed sorts array.',
                ['sort' => $string]
            );
            return '';
        }
    }

    /**
     * Takes a collection of filters and returns the corresponding filter key/value array [setKey => filterKey].
     *
     * @param array $filters The filters to get the keys for.
     * @return array The filter key array.
     */
    protected function getKeysFromFilters($filters) {
        $filterKeyValues = [];
        foreach ($filters as $filter) {
            if (isset($filter['setKey']) && isset($filter['key'])) {
                $filterKeyValues[val('setKey', $filter)] = val('key', $filter);
            }
        }
        return $filterKeyValues;
    }


    /**
     * Takes an array of filter key/values [setKey => filterKey] and returns a collection of filters.
     *
     * @param array $filterKeyValues The filters key array to get the filter for.
     * @return array An array of filters.
     */
    protected function getFiltersFromKeys($filterKeyValues) {
        $filters = [];
        $allFilters = self::getAllowedFilters();
        foreach ($filterKeyValues as $key => $value) {
            if (isset($allFilters[$key]['filters'][$value])) {
                $filters[] = $allFilters[$key]['filters'][$value];
            }
        }
        return $filters;
    }

    /**
     * @param string $sortKey
     * @return array
     */
    protected function getSortFromKey($sortKey) {
        return val($sortKey, self::getAllowedSorts(), []);
    }

    /**
     * Get the current sort/filter query string.
     *
     * You can pass no parameters or pass either a new filter key or sort key to build a new query string, leaving the
     * other properties intact.
     *
     * @param string $selectedSort
     * @param array $selectedFilters
     * @param string $sortKeyToSet The key name of the sort in the sorts array.
     * @param array $filterKeysToSet An array of filters, where the key is the key of the filterSet in the filters array
     * and the value is the key of the filter.
     * @return string The current or amended query string for sort and filter.
     */
    public static function getSortFilterQueryString($selectedSort, $selectedFilters, $sortKeyToSet = '', $filterKeysToSet = []) {
        $filterString = '';
        $filterKeys = array_merge($selectedFilters, $filterKeysToSet);

        // Build the sort query string
        foreach ($filterKeys as $setKey => $filterKey) {
            // If the preference is none, don't show it.
            if ($filterKey != self::EMPTY_FILTER_KEY) {
                if (!empty($filterString)) {
                    $filterString .= '&';
                }
                $filterString .= $setKey.'='.$filterKey;
            }
        }

        $sortString = '';
        if (!$sortKeyToSet) {
            $sort = $selectedSort;
            if ($sort) {
                $sortString = 'sort='.$sort;
            }
        } else {
            $sortString = 'sort='.$sortKeyToSet;
        }

        $queryString = '';
        if (!empty($sortString) && !empty($filterString)) {
            $queryString = '?'.$sortString.'&'.$filterString;
        } elseif (!empty($sortString)) {
            $queryString = '?'.$sortString;
        } elseif (!empty($filterString)) {
            $queryString = '?'.$filterString;
        }

        return $queryString;
    }

    /**
     * Add a sort to the allowed sorts array.
     *
     * @param string $key The key name of the sort. Appears in the query string, should be url-friendly.
     * @param string $name The display name of the sort.
     * @param string|array $orderBy An array indicating order by fields and their directions in the format:
     *      array('field1' => 'direction', 'field2' => 'direction')
     * @param array $categoryIDs The IDs of the categories that this sort will work on. If empty, sort is global.
     */
    public static function addSort($key, $name, $orderBy, $categoryIDs = []) {
        self::$allowedSorts[$key] = ['key' => $key, 'name' => $name, 'orderBy' => $orderBy, 'categories' => $categoryIDs];
    }

    /**
     * Add a filter to the allowed filters array.
     *
     * @param string $key The key name of the filter. Appears in the query string, should be url-friendly.
     * @param string $name The display name of the filter. Usually appears as an option in the UI.
     * @param array $wheres The where array query to execute for the filter. Uses
     * @param string $group (optional) The nav module will group together any items with the same group name.
     * @param string $setKey The key name of the filter set.
     */
    public static function addFilter($key, $name, $wheres, $group = '', $setKey = 'filter') {
        if (!val($setKey, self::getAllowedFilters())) {
            self::addFilterSet($setKey);
        }
        self::$allowedFilters[$setKey]['filters'][$key] = ['key' => $key, 'setKey' => $setKey, 'name' => $name, 'wheres' => $wheres];
        if ($group) {
            self::$allowedFilters[$setKey]['filters'][$key]['group'] = $group;
        }
    }

    /**
     * Adds a filter set to the allowed filters array.
     *
     * @param string $setKey The key name of the filter set.
     * @param string $setName The name of the filter set. Appears in the UI.
     * @param array $categoryIDs The IDs of the categories that this filter will work on. If empty, filter is global.
     */
    public static function addFilterSet($setKey, $setName = '', $categoryIDs = []) {
        if (!$setName) {
            $setName = t('All Discussions');
        }
        self::$allowedFilters[$setKey]['key'] = $setKey;
        self::$allowedFilters[$setKey]['name'] = $setName;
        self::$allowedFilters[$setKey]['categories'] = $categoryIDs;

        // Add a way to let users clear any filters they've added.
        self::addClearFilter($setKey, $setName);
    }

    /**
     * If you don't want to use any of the default sorts, use this little buddy.
     */
    public static function clearSorts() {
        self::$allowedSorts = [];
    }

    /**
     * Removes a sort from the allowed sort array with the passed key.
     *
     * @param string $key The key of the sort to remove.
     */
    public static function removeSort($key) {
        if (val($key, self::$allowedSorts)) {
            unset(self::$allowedSorts[$key]);
        }
    }

    /**
     * Removes a filters from the allowed filter array with the passed filter key/values.
     *
     * @param array $filterKeys The key/value pairs of the filters to remove.
     */
    public static function removeFilter($filterKeys) {
        foreach ($filterKeys as $setKey => $filterKey) {
            if (isset(self::$allowedFilters[$setKey]['filters'][$filterKey])) {
                unset(self::$allowedFilters[$setKey]['filters'][$filterKey]);
            }
        }
    }

    /**
     * Removes a filter set from the allowed filter array with the passed set key.
     *
     * @param string $setKey The key of the filter to remove.
     */
    public static function removeFilterSet($setKey) {
        if (val($setKey, self::$allowedFilters)) {
            unset(self::$allowedFilters[$setKey]);
        }
    }

    /**
     * Adds an option to a filter set filters array to clear any existing filters on the data.
     *
     * @param string $setKey The key name of the filter set to add the option to.
     * @param string $setName The display name of the option. Usually the human-readable set name.
     */
    protected static function addClearFilter($setKey, $setName = '') {
        self::$allowedFilters[$setKey]['filters'][self::EMPTY_FILTER_KEY] = [
            'key' => self::EMPTY_FILTER_KEY,
            'setKey' => $setKey,
            'name' => $setName,
            'wheres' => [], 'group' => 'default'
        ];
    }
}
