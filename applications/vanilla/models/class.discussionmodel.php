<?php
/**
 * Discussion model
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Manages discussions data.
 */
class DiscussionModel extends VanillaModel {

    use StaticInitializer;

    /** Cache key. */
    const CACHE_DISCUSSIONVIEWS = 'discussion.%s.countviews';

    /** @var string Default column to order by. */
    const DEFAULT_ORDER_BY_FIELD = 'DateLastComment';

    /** @var string The filter key for clearing-type filters. */
    const EMPTY_FILTER_KEY = 'none';

    /** @var array */
    protected static $_CategoryPermissions = null;

    /** @var array */
    protected static $_DiscussionTypes = null;

    /** @var bool */
    public $Watching = false;

    /** @var array Discussion Permissions */
    protected $permissionTypes = array('Add', 'Announce', 'Close', 'Delete', 'Edit', 'Sink', 'View');

    /**
     * @var array The sorts that are accessible via GET. Each sort corresponds with an order by clause.
     *
     * Each sort in the array has the following properties:
     * - **key**: string - The key name of the sort. Appears in the query string, should be url-friendly.
     * - **name**: string - The display name of the sort.
     * - **orderBy**: string - An array indicating order by fields and their directions in the format: array('field1' => 'direction', 'field2' => 'direction')
     */
    protected static $allowedSorts = array(
        'hot' => array('key' => 'hot', 'name' => 'Hot', 'orderBy' => array('DateLastComment' => 'desc')),
        'top' => array('key' => 'top', 'name' => 'Top', 'orderBy' => array('Score' => 'desc', 'DateInserted' => 'desc')),
        'new' => array('key' => 'new', 'name' => 'New', 'orderBy' => array('DateInserted' => 'desc'))
    );

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
     * @var string The sort key of the order by we apply in the query.
     */
    protected $sort = '';

    /**
     * @var string The filter keys of the wheres we apply in the query.
     */
    protected $filters = [];

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct() {
        parent::__construct('Discussion');
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
     * The sort property is a string. This setter also accepts an array and checks if the sort key exists
     * on the array. Will only set the sort property if it exists in the allowed sorts array.
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
    }

    /**
     * Determines whether or not the current user can edit a discussion.
     *
     * @param object|array $discussion The discussion to examine.
     * @param int $timeLeft Sets the time left to edit or 0 if not applicable.
     * @return bool Returns true if the user can edit or false otherwise.
     */
    public static function canEdit($discussion, &$timeLeft = 0) {
        if (!($permissionCategoryID = val('PermissionCategoryID', $discussion))) {
            $category = CategoryModel::categories(val('CategoryID', $discussion));
            $permissionCategoryID = val('PermissionCategoryID', $category);
        }

        // Users with global edit permission can edit.
        if (Gdn::session()->checkPermission('Vanilla.Discussions.Edit', true, 'Category', $permissionCategoryID)) {
            return true;
        }

        // Non-mods can't edit if they aren't the author.
        if (Gdn::session()->UserID != val('InsertUserID', $discussion)) {
            return false;
        }

        return self::editContentTimeout($discussion, $timeLeft);
    }

    /**
     * Checks whether the time frame when a discussion can be edited has passed.
     *
     * @param object|array $discussion The discussion to examine.
     * @param int $timeLeft Sets the time left to edit or 0 if not applicable.
     * @return bool Whether the time to edit the discussion has passed.
     */
    public static function editContentTimeout($discussion, &$timeLeft = 0) {
        // Determine if we still have time to edit.
        $timeInserted = strtotime(val('DateInserted', $discussion));
        $editContentTimeout = c('Garden.EditContentTimeout', -1);

        $canEdit = $editContentTimeout == -1 || $timeInserted + $editContentTimeout > time();

        if ($canEdit && $editContentTimeout > 0) {
            $timeLeft = $timeInserted + $editContentTimeout - time();
        }

        return $canEdit;
    }

    public function counts($Column, $From = false, $To = false, $Max = false) {
        $Result = array('Complete' => true);
        switch ($Column) {
            case 'CountComments':
                $this->Database->query(DBAModel::GetCountSQL('count', 'Discussion', 'Comment'));
                break;
            case 'FirstCommentID':
                $this->Database->query(DBAModel::GetCountSQL('min', 'Discussion', 'Comment', $Column));
                break;
            case 'LastCommentID':
                $this->Database->query(DBAModel::GetCountSQL('max', 'Discussion', 'Comment', $Column));
                break;
            case 'DateLastComment':
                $this->Database->query(DBAModel::GetCountSQL('max', 'Discussion', 'Comment', $Column, 'DateInserted'));
                $this->SQL
                    ->update('Discussion')
                    ->set('DateLastComment', 'DateInserted', false, false)
                    ->where('DateLastComment', null)
                    ->put();
                break;
            case 'LastCommentUserID':
                if (!$Max) {
                    // Get the range for this update.
                    $DBAModel = new DBAModel();
                    list($Min, $Max) = $DBAModel->PrimaryKeyRange('Discussion');

                    if (!$From) {
                        $From = $Min;
                        $To = $Min + DBAModel::$ChunkSize - 1;
                    }
                }
                $this->SQL
                    ->update('Discussion d')
                    ->join('Comment c', 'c.CommentID = d.LastCommentID')
                    ->set('d.LastCommentUserID', 'c.InsertUserID', false, false)
                    ->where('d.DiscussionID >=', $From)
                    ->where('d.DiscussionID <=', $To)
                    ->put();
                $Result['Complete'] = $To >= $Max;

                $Percent = round($To * 100 / $Max);
                if ($Percent > 100 || $Result['Complete']) {
                    $Result['Percent'] = '100%';
                } else {
                    $Result['Percent'] = $Percent.'%';
                }


                $From = $To + 1;
                $To = $From + DBAModel::$ChunkSize - 1;
                $Result['Args']['From'] = $From;
                $Result['Args']['To'] = $To;
                $Result['Args']['Max'] = $Max;
                break;
            default:
                throw new Gdn_UserException("Unknown column $Column");
        }
        return $Result;
    }

    /**
     * Builds base SQL query for discussion data.
     *
     * Events: AfterDiscussionSummaryQuery.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $AdditionalFields Allows selection of additional fields as Alias=>Table.Fieldname.
     */
    public function discussionSummaryQuery($AdditionalFields = array(), $Join = true) {
        // Verify permissions (restricting by category if necessary)
        if ($this->Watching) {
            $Perms = CategoryModel::CategoryWatch();
        } else {
            $Perms = self::CategoryPermissions();
        }

        if ($Perms !== true) {
            $this->SQL->whereIn('d.CategoryID', $Perms);
        }

        // Buid main query
        $this->SQL
            ->select('d.*')
            ->select('d.InsertUserID', '', 'FirstUserID')
            ->select('d.DateInserted', '', 'FirstDate')
            ->select('d.DateLastComment', '', 'LastDate')
            ->select('d.LastCommentUserID', '', 'LastUserID')
            ->from('Discussion d');

        if ($Join) {
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

        // Add any additional fields that were requested
        if (is_array($AdditionalFields)) {
            foreach ($AdditionalFields as $Alias => $Field) {
                // See if a new table needs to be joined to the query.
                $TableAlias = explode('.', $Field);
                $TableAlias = $TableAlias[0];
                if (array_key_exists($TableAlias, $Tables)) {
                    $Join = $Tables[$TableAlias];
                    $this->SQL->join($Join[0], $Join[1]);
                    unset($Tables[$TableAlias]);
                }

                // Select the field.
                $this->SQL->select($Field, '', is_numeric($Alias) ? '' : $Alias);
            }
        }

        $this->fireEvent('AfterDiscussionSummaryQuery');
    }

    public static function discussionTypes() {
        if (!self::$_DiscussionTypes) {
            $DiscussionTypes = array('Discussion' => array(
                'Singular' => 'Discussion',
                'Plural' => 'Discussions',
                'AddUrl' => '/post/discussion',
                'AddText' => 'New Discussion'
            ));


            Gdn::pluginManager()->EventArguments['Types'] = &$DiscussionTypes;
            Gdn::pluginManager()->FireAs('DiscussionModel')->fireEvent('DiscussionTypes');
            self::$_DiscussionTypes = $DiscussionTypes;
            unset(Gdn::pluginManager()->EventArguments['Types']);
        }
        return self::$_DiscussionTypes;
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
     * @param int $Offset Number of discussions to skip.
     * @param int $Limit Max number of discussions to return.
     * @param array $Wheres SQL conditions.
     * @param array $AdditionalFields Allows selection of additional fields as Alias=>Table.Fieldname.
     * @return Gdn_DataSet SQL result.
     */
    public function get($Offset = '0', $Limit = '', $Wheres = '', $AdditionalFields = null) {
        if ($Limit == '') {
            $Limit = Gdn::config('Vanilla.Discussions.PerPage', 50);
        }

        $Offset = !is_numeric($Offset) || $Offset < 0 ? 0 : $Offset;

        $Session = Gdn::session();
        $UserID = $Session->UserID > 0 ? $Session->UserID : 0;
        $this->DiscussionSummaryQuery($AdditionalFields, false);

        if ($UserID > 0) {
            $this->SQL
                ->select('w.UserID', '', 'WatchUserID')
                ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
                ->select('w.CountComments', '', 'CountCommentWatch')
                ->select('w.Participated')
                ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$UserID, 'left');
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

        $this->AddArchiveWhere($this->SQL);

        if ($Offset !== false && $Limit !== false) {
            $this->SQL->limit($Limit, $Offset);
        }

        // Get preferred sort order
        $orderBy = $this->getOrderBy();

        $this->EventArguments['OrderFields'] = &$orderBy;
        $this->EventArguments['Wheres'] = &$Wheres;
        $this->fireEvent('BeforeGet'); // @see 'BeforeGetCount' for consistency in results vs. counts

        $IncludeAnnouncements = false;
        if (strtolower(val('Announce', $Wheres)) == 'all') {
            $IncludeAnnouncements = true;
            unset($Wheres['Announce']);
        }

        if (is_array($Wheres)) {
            $this->SQL->where($Wheres);
        }

        foreach ($orderBy as $orderField => $direction) {
            $this->SQL->orderBy($this->addFieldPrefix($orderField), $direction);
        }

        // Set range and fetch
        $Data = $this->SQL->get();

        // If not looking at discussions filtered by bookmarks or user, filter announcements out.
        if (!$IncludeAnnouncements) {
            if (!isset($Wheres['w.Bookmarked']) && !isset($Wheres['d.InsertUserID'])) {
                $this->RemoveAnnouncements($Data);
            }
        }

        // Change discussions returned based on additional criteria
        $this->AddDiscussionColumns($Data);

        // Join in the users.
        Gdn::userModel()->joinUsers($Data, array('FirstUserID', 'LastUserID'));
        CategoryModel::JoinCategories($Data);

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->AddDenormalizedViews($Data);
        }

        // Prep and fire event
        $this->EventArguments['Data'] = $Data;
        $this->fireEvent('AfterAddColumns');

        return $Data;
    }

    /**
     * Get a list of the most recent discussions.
     *
     * @param array|false $Where The where condition of the get.
     * @param bool|false|int $Limit The number of discussion to return.
     * @param int|false $Offset The offset within the total set.
     * @return Gdn_DataSet Returns a <a href='psi_element://Gdn_DataSet'>Gdn_DataSet</a> of discussions.
     * of discussions.
     */
    public function getWhereRecent($Where = array(), $Limit = false, $Offset = false) {
        $result = $this->getWhere($Where, '', '', $Limit, $Offset);
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

            if ($categoryIDs) {
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
     * Get a list of discussions.
     *
     * This method call will remove announcements and may not return exactly {@link $Limit} records for optimization.
     * You can set `$Where['d.Announce'] = 'all'` to return announcements.
     *
     * @param array|false $Where The where condition of the get.
     * @param string $OrderFields The field to order the discussions by.
     * @param string $OrderDirection The order, either **asc** or **desc**.
     * @param int|false $Limit The number of discussion to return.
     * @param int|false $Offset The offset within the total set.
     * @return Gdn_DataSet Returns a {@link Gdn_DataSet} of discussions.
     */
    public function getWhere($Where = false, $OrderFields = '', $OrderDirection = '', $Limit = false, $Offset = false) {
        // Add backwards compatibility for the old way getWhere() was called.
        if (is_numeric($OrderFields)) {
            deprecated('DiscussionModel->getWhere($where, $limit, ...)', 'DiscussionModel->getWhereRecent()');
            $Limit = $OrderFields;
            $OrderFields = '';
        }
        if (is_numeric($OrderDirection)) {
            deprecated('DiscussionModel->getWhere($where, $limit, $offset)', 'DiscussionModel->getWhereRecent()');
            $Offset = $OrderDirection;
            $OrderDirection = '';
        }

        if ($Limit === 0) {
            trigger_error("You should not supply 0 to for $Limit in DiscussionModel->getWhere()", E_USER_NOTICE);
        }
        if (empty($Limit)) {
            $Limit = c('Vanilla.Discussions.PerPage', 30);
        }
        if (empty($Offset)) {
            $Offset = 0;
        }

        if (!is_array($Where)) {
            $Where = array();
        }

        $Sql = $this->SQL;

        // Determine category watching
        if ($this->Watching && !isset($Where['d.CategoryID'])) {
            $Watch = CategoryModel::CategoryWatch();
            if ($Watch !== true) {
                $Where['d.CategoryID'] = $Watch;
            }
        }

        $Where = $this->combineWheres($this->getWheres(), $Where);

        if (empty($OrderFields)) {
            $orderBy = $this->getOrderBy();
        } elseif (is_string($OrderFields)) {
            if ($OrderDirection != 'asc') {
                $OrderDirection = 'desc';
            }
            $orderBy = [$OrderFields => $OrderDirection];
        }

        $this->EventArguments['OrderBy'] = &$orderBy;
        $this->EventArguments['Wheres'] = &$Where;
        $this->fireEvent('BeforeGet');

        // Build up the base query. Self-join for optimization.
        $Sql->select('d2.*')
            ->from('Discussion d')
            ->join('Discussion d2', 'd.DiscussionID = d2.DiscussionID')
            ->limit($Limit, $Offset);

        foreach ($orderBy as $field => $direction) {
            $Sql->orderBy($this->addFieldPrefix($field), $direction);
        }

        // Verify permissions (restricting by category if necessary)
        $Perms = self::CategoryPermissions();

        if ($Perms !== true) {
            if (isset($Where['d.CategoryID'])) {
                $Where['d.CategoryID'] = array_values(array_intersect((array)$Where['d.CategoryID'], $Perms));
            } else {
                $Where['d.CategoryID'] = $Perms;
            }
        }

        // Check to see whether or not we are removing announcements.
        if (strtolower(val('Announce', $Where)) == 'all') {
            $RemoveAnnouncements = false;
            unset($Where['Announce']);
        } elseif (strtolower(val('d.Announce', $Where)) == 'all') {
            $RemoveAnnouncements = false;
            unset($Where['d.Announce']);
        } else {
            $RemoveAnnouncements = true;
        }

        // Make sure there aren't any ambiguous discussion references.
        $safeWheres = [];
        foreach ($Where as $Key => $Value) {
            $safeWheres[$this->addFieldPrefix($Key)] = $Value;
        }
        $Sql->where($safeWheres);

        // Add the UserDiscussion query.
        if (($UserID = Gdn::session()->UserID) > 0) {
            $Sql
                ->join('UserDiscussion w', "w.DiscussionID = d2.DiscussionID and w.UserID = $UserID", 'left')
                ->select('w.UserID', '', 'WatchUserID')
                ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
                ->select('w.CountComments', '', 'CountCommentWatch')
                ->select('w.Participated');
        }

        $Data = $Sql->get();
        $Result =& $Data->result();

        // Change discussions returned based on additional criteria
        $this->AddDiscussionColumns($Data);

        // If not looking at discussions filtered by bookmarks or user, filter announcements out.
        if ($RemoveAnnouncements && !isset($Where['w.Bookmarked']) && !isset($Wheres['d.InsertUserID'])) {
            $this->RemoveAnnouncements($Data);
        }

        // Join in the users.
        Gdn::userModel()->joinUsers($Data, array('FirstUserID', 'LastUserID'));
        CategoryModel::JoinCategories($Data);

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->AddDenormalizedViews($Data);
        }

        // Prep and fire event
        $this->EventArguments['Data'] = $Data;
        $this->fireEvent('AfterAddColumns');

        return $Data;
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
     * @since 2.0.0
     * @access public
     *
     * @param int $Offset Number of discussions to skip.
     * @param int $Limit Max number of discussions to return.
     * @param array $Wheres SQL conditions.
     * @param array $AdditionalFields Allows selection of additional fields as Alias=>Table.Fieldname.
     * @return Gdn_DataSet SQL result.
     */
    public function getUnread($Offset = '0', $Limit = '', $Wheres = '', $AdditionalFields = null) {
        if ($Limit == '') {
            $Limit = Gdn::config('Vanilla.Discussions.PerPage', 50);
        }

        $Offset = !is_numeric($Offset) || $Offset < 0 ? 0 : $Offset;

        $Session = Gdn::session();
        $UserID = $Session->UserID > 0 ? $Session->UserID : 0;
        $this->DiscussionSummaryQuery($AdditionalFields, false);

        if ($UserID > 0) {
            $this->SQL
                ->select('w.UserID', '', 'WatchUserID')
                ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
                ->select('w.CountComments', '', 'CountCommentWatch')
                ->select('w.Participated')
                ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$UserID, 'left')
                //->beginWhereGroup()
                //->where('w.DateLastViewed', null)
                //->orWhere('d.DateLastComment >', 'w.DateLastViewed')
                //->endWhereGroup()
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

        $this->AddArchiveWhere($this->SQL);


        $this->SQL->limit($Limit, $Offset);

        $this->EventArguments['SortField'] = c('Vanilla.Discussions.SortField', 'd.DateLastComment');
        $this->EventArguments['SortDirection'] = c('Vanilla.Discussions.SortDirection', 'desc');
        $this->EventArguments['Wheres'] = &$Wheres;
        $this->fireEvent('BeforeGetUnread'); // @see 'BeforeGetCount' for consistency in results vs. counts

        $IncludeAnnouncements = false;
        if (strtolower(val('Announce', $Wheres)) == 'all') {
            $IncludeAnnouncements = true;
            unset($Wheres['Announce']);
        }

        if (is_array($Wheres)) {
            $this->SQL->where($Wheres);
        }

        // Get sorting options from config
        $SortField = $this->EventArguments['SortField'];
        if (!in_array($SortField, array('d.DiscussionID', 'd.DateLastComment', 'd.DateInserted'))) {
            trigger_error("You are sorting discussions by a possibly sub-optimal column.", E_USER_NOTICE);
        }

        $SortDirection = $this->EventArguments['SortDirection'];
        if ($SortDirection != 'asc') {
            $SortDirection = 'desc';
        }

        $this->SQL->orderBy($this->addFieldPrefix($SortField), $SortDirection);

        // Set range and fetch
        $Data = $this->SQL->get();

        // If not looking at discussions filtered by bookmarks or user, filter announcements out.
        if (!$IncludeAnnouncements) {
            if (!isset($Wheres['w.Bookmarked']) && !isset($Wheres['d.InsertUserID'])) {
                $this->RemoveAnnouncements($Data);
            }
        }

        // Change discussions returned based on additional criteria
        $this->AddDiscussionColumns($Data);

        // Join in the users.
        Gdn::userModel()->joinUsers($Data, array('FirstUserID', 'LastUserID'));
        CategoryModel::JoinCategories($Data);

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->AddDenormalizedViews($Data);
        }

        // Prep and fire event
        $this->EventArguments['Data'] = $Data;
        $this->fireEvent('AfterAddColumns');

        return $Data;
    }

    /**
     * Removes undismissed announcements from the data.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object $Data SQL result.
     */
    public function removeAnnouncements($Data) {
        $Result =& $Data->result();
        $Unset = false;

        foreach ($Result as $Key => &$Discussion) {
            if (isset($this->_AnnouncementIDs)) {
                if (in_array($Discussion->DiscussionID, $this->_AnnouncementIDs)) {
                    unset($Result[$Key]);
                    $Unset = true;
                }
            } elseif ($Discussion->Announce && $Discussion->Dismissed == 0) {
                // Unset discussions that are announced and not dismissed
                unset($Result[$Key]);
                $Unset = true;
            }
        }
        if ($Unset) {
            // Make sure the discussions are still in order for json encoding.
            $Result = array_values($Result);
        }
    }

    /**
     * Add denormalized views to discussions
     *
     * WE NO LONGER NEED THIS SINCE THE LOGIC HAS BEEN CHANGED.
     *
     * @deprecated since version 2.1.26a
     * @param type $Discussions
     */
    public function addDenormalizedViews(&$Discussions) {

        if ($Discussions instanceof Gdn_DataSet) {
            $Result = $Discussions->result();
            foreach ($Result as &$Discussion) {
                $CacheKey = sprintf(DiscussionModel::CACHE_DISCUSSIONVIEWS, $Discussion->DiscussionID);
                $CacheViews = Gdn::cache()->get($CacheKey);
                if ($CacheViews !== Gdn_Cache::CACHEOP_FAILURE) {
                    $Discussion->CountViews += $CacheViews;
                }
            }
        } else {
            if (isset($Discussions->DiscussionID)) {
                $Discussion = $Discussions;
                $CacheKey = sprintf(DiscussionModel::CACHE_DISCUSSIONVIEWS, $Discussion->DiscussionID);
                $CacheViews = Gdn::cache()->get($CacheKey);
                if ($CacheViews !== Gdn_Cache::CACHEOP_FAILURE) {
                    $Discussion->CountViews += $CacheViews;
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
     * @param object $Data SQL result.
     */
    public function addDiscussionColumns($Data) {
        // Change discussions based on archiving.
        $Result = &$Data->result();
        foreach ($Result as &$Discussion) {
            $this->Calculate($Discussion);
        }
    }

    public function calculate(&$Discussion) {
        $ArchiveTimestamp = Gdn_Format::toTimestamp(Gdn::config('Vanilla.Archive.Date', 0));

        // Fix up output
        $Discussion->Name = Gdn_Format::text($Discussion->Name);
        $Discussion->Attributes = dbdecode($Discussion->Attributes);
        $Discussion->Url = DiscussionUrl($Discussion);
        $Discussion->Tags = $this->FormatTags($Discussion->Tags);

        // Join in the category.
        $Category = CategoryModel::categories($Discussion->CategoryID);
        if (!$Category) {
            $Category = false;
        }
        $Discussion->Category = $Category['Name'];
        $Discussion->CategoryUrlCode = $Category['UrlCode'];
        $Discussion->PermissionCategoryID = $Category['PermissionCategoryID'];

        // Add some legacy calculated columns.
        if (!property_exists($Discussion, 'FirstUserID')) {
            $Discussion->FirstUserID = $Discussion->InsertUserID;
            $Discussion->FirstDate = $Discussion->DateInserted;
            $Discussion->LastUserID = $Discussion->LastCommentUserID;
            $Discussion->LastDate = $Discussion->DateLastComment;
        }

        // Add the columns from UserDiscussion if they don't exist.
        if (!property_exists($Discussion, 'CountCommentWatch')) {
            $Discussion->WatchUserID = null;
            $Discussion->DateLastViewed = null;
            $Discussion->Dismissed = 0;
            $Discussion->Bookmarked = 0;
            $Discussion->CountCommentWatch = null;
        }

        // Allow for discussions to be archived
        if ($Discussion->DateLastComment && Gdn_Format::toTimestamp($Discussion->DateLastComment) <= $ArchiveTimestamp) {
            $Discussion->Closed = '1';
            if ($Discussion->CountCommentWatch) {
                $Discussion->CountUnreadComments = $Discussion->CountComments - $Discussion->CountCommentWatch;
            } else {
                $Discussion->CountUnreadComments = 0;
            }
            // Allow for discussions to just be new.
        } elseif ($Discussion->CountCommentWatch === null) {
            $Discussion->CountUnreadComments = true;

        } else {
            $Discussion->CountUnreadComments = $Discussion->CountComments - $Discussion->CountCommentWatch;
        }

        if (!property_exists($Discussion, 'Read')) {
            $Discussion->Read = !(bool)$Discussion->CountUnreadComments;
            if ($Category && !is_null($Category['DateMarkedRead'])) {
                // If the category was marked explicitly read at some point, see if that applies here
                if ($Category['DateMarkedRead'] > $Discussion->DateLastComment) {
                    $Discussion->Read = true;
                }

                if ($Discussion->Read) {
                    $Discussion->CountUnreadComments = 0;
                }
            }
        }

        // Logic for incomplete comment count.
        if ($Discussion->CountCommentWatch == 0 && $DateLastViewed = val('DateLastViewed', $Discussion)) {
            $Discussion->CountUnreadComments = true;
            if (Gdn_Format::toTimestamp($DateLastViewed) >= Gdn_Format::toTimestamp($Discussion->LastDate)) {
                $Discussion->CountCommentWatch = $Discussion->CountComments;
                $Discussion->CountUnreadComments = 0;
            }
        }
        if ($Discussion->CountUnreadComments === null) {
            $Discussion->CountUnreadComments = 0;
        } elseif ($Discussion->CountUnreadComments < 0)
            $Discussion->CountUnreadComments = 0;

        $Discussion->CountCommentWatch = is_numeric($Discussion->CountCommentWatch) ? $Discussion->CountCommentWatch : null;

        if ($Discussion->LastUserID == null) {
            $Discussion->LastUserID = $Discussion->InsertUserID;
            $Discussion->LastDate = $Discussion->DateInserted;
        }

        $this->EventArguments['Discussion'] = &$Discussion;
        $this->fireEvent('SetCalculatedFields');
    }

    /**
     * Add SQL Where to account for archive date.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object $Sql Gdn_SQLDriver
     */
    public function addArchiveWhere($Sql = null) {
        if (is_null($Sql)) {
            $Sql = $this->SQL;
        }

        $Exclude = Gdn::config('Vanilla.Archive.Exclude');
        if ($Exclude) {
            $ArchiveDate = Gdn::config('Vanilla.Archive.Date');
            if ($ArchiveDate) {
                $Sql->where('d.DateLastComment >', $ArchiveDate);
            }
        }
    }


    /**
     * Gets announced discussions.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $Wheres SQL conditions.
     * @return object SQL result.
     */
    public function getAnnouncements($Wheres = '') {
        $Wheres = $this->combineWheres($this->getWheres(), $Wheres);
        $Session = Gdn::session();
        $Limit = Gdn::config('Vanilla.Discussions.PerPage', 50);
        $Offset = 0;
        $UserID = $Session->UserID > 0 ? $Session->UserID : 0;
        $CategoryID = val('d.CategoryID', $Wheres, 0);
        $GroupID = val('d.GroupID', $Wheres, 0);
        // Get the discussion IDs of the announcements.
        $CacheKey = $this->GetAnnouncementCacheKey($CategoryID);
        if ($GroupID == 0) {
            $this->SQL->Cache($CacheKey);
        }
        $this->SQL->select('d.DiscussionID')
            ->from('Discussion d');

        if (!is_array($CategoryID) && ($CategoryID > 0 || $GroupID > 0)) {
            $this->SQL->where('d.Announce >', '0');
        } else {
            $this->SQL->where('d.Announce', 1);
        }
        if ($GroupID > 0) {
            $this->SQL->where('d.GroupID', $GroupID);
        } elseif (is_array($CategoryID)) {
            $this->SQL->whereIn('d.CategoryID', $CategoryID);
        } elseif ($CategoryID > 0) {
            $this->SQL->where('d.CategoryID', $CategoryID);
        }

        $AnnouncementIDs = $this->SQL->get()->resultArray();
        $AnnouncementIDs = array_column($AnnouncementIDs, 'DiscussionID');

        // Short circuit querying when there are no announcements.
        if (count($AnnouncementIDs) == 0) {
            $this->_AnnouncementIDs = $AnnouncementIDs;
            return new Gdn_DataSet();
        }

        $this->DiscussionSummaryQuery(array(), false);

        if (!empty($Wheres)) {
            $this->SQL->where($Wheres);
        }

        if ($UserID) {
            $this->SQL->select('w.UserID', '', 'WatchUserID')
                ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
                ->select('w.CountComments', '', 'CountCommentWatch')
                ->select('w.Participated')
                ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$UserID, 'left');
        } else {
            // Don't join in the user table when we are a guest.
            $this->SQL->select('null as WatchUserID, null as DateLastViewed, null as Dismissed, null as Bookmarked, null as CountCommentWatch');
        }

        // Add conditions passed.
        $this->SQL->whereIn('d.DiscussionID', $AnnouncementIDs);

        // If we aren't viewing announcements in a category then only show global announcements.
        if (!$Wheres || is_array($CategoryID)) {
            $this->SQL->where('d.Announce', 1);
        } else {
            $this->SQL->where('d.Announce >', 0);
        }

        // If we allow users to dismiss discussions, skip ones this user dismissed
        if (c('Vanilla.Discussions.Dismiss', 1) && $UserID) {
            $this->SQL
                ->where('coalesce(w.Dismissed, \'0\')', '0', false);
        }

        $this->SQL->limit($Limit, $Offset);

        $orderBy = $this->getOrderBy();
        foreach ($orderBy as $field => $direction) {
            $this->SQL->orderBy($this->addFieldPrefix($field), $direction);
        }

        $Data = $this->SQL->get();

        // Save the announcements that were fetched for later removal.
        $AnnouncementIDs = array();
        foreach ($Data as $Row) {
            $AnnouncementIDs[] = val('DiscussionID', $Row);
        }
        $this->_AnnouncementIDs = $AnnouncementIDs;

        $this->AddDiscussionColumns($Data);

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->AddDenormalizedViews($Data);
        }

        Gdn::userModel()->joinUsers($Data, array('FirstUserID', 'LastUserID'));
        CategoryModel::JoinCategories($Data);

        // Prep and fire event
        $this->EventArguments['Data'] = $Data;
        $this->fireEvent('AfterAddColumns');

        return $Data;
    }

    /**
     * @param int $CategoryID Category ID,
     * @return string $Key CacheKey name to be used for cache.
     */
    public function getAnnouncementCacheKey($CategoryID = 0) {
        $Key = 'Announcements';
        if (!is_array($CategoryID) && $CategoryID > 0) {
            $Key .= ':'.$CategoryID;
        }
        return $Key;
    }

    /**
     * Gets all users who have bookmarked the specified discussion.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $DiscussionID Unique ID to find bookmarks for.
     * @return object SQL result.
     */
    public function getBookmarkUsers($DiscussionID) {
        return $this->SQL
            ->select('UserID')
            ->from('UserDiscussion')
            ->where('DiscussionID', $DiscussionID)
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
     * @param int $UserID Which user to get discussions for.
     * @param int $Limit Max number to get.
     * @param int $Offset Number to skip.
     * @param int $LastDiscussionID A hint for quicker paging.
     * @param int $WatchUserID User to use for read/unread data.
     * @return Gdn_DataSet SQL results.
     */
    public function getByUser($UserID, $Limit, $Offset, $LastDiscussionID = false, $WatchUserID = false) {
        $Perms = DiscussionModel::CategoryPermissions();

        if (is_array($Perms) && empty($Perms)) {
            return new Gdn_DataSet(array());
        }

        // Allow us to set perspective of a different user.
        if (!$WatchUserID) {
            $WatchUserID = $UserID;
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
            ->where('d.InsertUserID', $UserID)
            ->orderBy('d.DiscussionID', 'desc');

        // Join in the watch data.
        if ($WatchUserID > 0) {
            $this->SQL
                ->select('w.UserID', '', 'WatchUserID')
                ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
                ->select('w.CountComments', '', 'CountCommentWatch')
                ->select('w.Participated')
                ->join('UserDiscussion w', 'd2.DiscussionID = w.DiscussionID and w.UserID = '.$WatchUserID, 'left');
        } else {
            $this->SQL
                ->select('0', '', 'WatchUserID')
                ->select('now()', '', 'DateLastViewed')
                ->select('0', '', 'Dismissed')
                ->select('0', '', 'Bookmarked')
                ->select('0', '', 'CountCommentWatch')
                ->select('d.Announce', '', 'IsAnnounce');
        }

        if ($LastDiscussionID) {
            // The last comment id from the last page was given and can be used as a hint to speed up the query.
            $this->SQL
                ->where('d.DiscussionID <', $LastDiscussionID)
                ->limit($Limit);
        } else {
            $this->SQL->limit($Limit, $Offset);
        }

        $this->fireEvent('BeforeGetByUser');

        $Data = $this->SQL->get();


        $Result =& $Data->result();
        $this->LastDiscussionCount = $Data->numRows();

        if (count($Result) > 0) {
            $this->LastDiscussionID = $Result[count($Result) - 1]->DiscussionID;
        } else {
            $this->LastDiscussionID = null;
        }

        // Now that we have th comments we can filter out the ones we don't have permission to.
        if ($Perms !== true) {
            $Remove = array();

            foreach ($Data->result() as $Index => $Row) {
                if (!in_array($Row->CategoryID, $Perms)) {
                    $Remove[] = $Index;
                }
            }

            if (count($Remove) > 0) {
                foreach ($Remove as $Index) {
                    unset($Result[$Index]);
                }
                $Result = array_values($Result);
            }
        }

        // Change discussions returned based on additional criteria
        $this->AddDiscussionColumns($Data);

        // Join in the users.
        Gdn::userModel()->joinUsers($Data, array('FirstUserID', 'LastUserID'));
        CategoryModel::JoinCategories($Data);

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->AddDenormalizedViews($Data);
        }

        $this->EventArguments['Data'] =& $Data;
        $this->fireEvent('AfterAddColumns');

        return $Data;
    }

    /**
     * Get all the users that have participated in the discussion.
     * @param int $DiscussionID
     * @return Gdn_DataSet
     */
    public function getParticipatedUsers($DiscussionID) {
        return $this->SQL
            ->select('UserID')
            ->from('UserDiscussion')
            ->where('DiscussionID', $DiscussionID)
            ->where('Participated', '1')
            ->get();
    }

    /**
     * Identify current user's category permissions and set as local array.
     *
     * @since 2.0.0
     * @access public
     *
     * @param bool $Escape Prepends category IDs with @
     * @return array Protected local _CategoryPermissions
     */
    public static function categoryPermissions($Escape = false) {
        if (is_null(self::$_CategoryPermissions)) {
            $Session = Gdn::session();

            if ((is_object($Session->User) && $Session->User->Admin)) {
                self::$_CategoryPermissions = true;
            } elseif (c('Garden.Permissions.Disabled.Category')) {
                if ($Session->checkPermission('Vanilla.Discussions.View')) {
                    self::$_CategoryPermissions = true;
                } else {
                    self::$_CategoryPermissions = array(); // no permission
                }
            } else {
                $Categories = CategoryModel::categories();
                $IDs = array();

                foreach ($Categories as $ID => $Category) {
                    if ($Category['PermsDiscussionsView']) {
                        $IDs[] = $ID;
                    }
                }

                // Check to see if the user has permission to all categories. This is for speed.
                $CategoryCount = count($Categories);

                if (count($IDs) == $CategoryCount) {
                    self::$_CategoryPermissions = true;
                } else {
                    self::$_CategoryPermissions = array();
                    foreach ($IDs as $ID) {
                        self::$_CategoryPermissions[] = ($Escape ? '@' : '').$ID;
                    }
                }
            }
        }

        return self::$_CategoryPermissions;
    }

    public function fetchPageInfo($Url, $ThrowError = false) {
        $PageInfo = fetchPageInfo($Url, 3, $ThrowError);

        $Title = val('Title', $PageInfo, '');
        if ($Title == '') {
            if ($ThrowError) {
                throw new Gdn_UserException(t("The page didn't contain any information."));
            }

            $Title = formatString(t('Undefined discussion subject.'), array('Url' => $Url));
        } else {
            if ($Strip = c('Vanilla.Embed.StripPrefix')) {
                $Title = stringBeginsWith($Title, $Strip, true, true);
            }

            if ($Strip = c('Vanilla.Embed.StripSuffix')) {
                $Title = StringEndsWith($Title, $Strip, true, true);
            }
        }
        $Title = trim($Title);

        $Description = val('Description', $PageInfo, '');
        $Images = val('Images', $PageInfo, array());
        $Body = formatString(t('EmbeddedDiscussionFormat'), array(
            'Title' => $Title,
            'Excerpt' => $Description,
            'Image' => (count($Images) > 0 ? img(val(0, $Images), array('class' => 'LeftAlign')) : ''),
            'Url' => $Url
        ));
        if ($Body == '') {
            $Body = $Url;
        }
        if ($Body == '') {
            $Body = formatString(t('EmbeddedNoBodyFormat.'), array('Url' => $Url));
        }

        $Result = array(
            'Name' => $Title,
            'Body' => $Body,
            'Format' => 'Html');

        return $Result;
    }

    /**
     * Count how many discussions match the given criteria.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $Wheres SQL conditions.
     * @param bool $ForceNoAnnouncements Not used.
     * @return int Number of discussions.
     */
    public function getCount($Wheres = '', $ForceNoAnnouncements = false) {
        $Wheres = $this->combineWheres($this->getWheres(), $Wheres);
        if (is_array($Wheres) && count($Wheres) == 0) {
            $Wheres = '';
        }

        // Check permission and limit to categories as necessary
        if ($this->Watching) {
            $Perms = CategoryModel::CategoryWatch();
        } else {
            $Perms = self::CategoryPermissions();
        }

        if (!$Wheres || (count($Wheres) == 1 && isset($Wheres['d.CategoryID']))) {
            // Grab the counts from the faster category cache.
            if (isset($Wheres['d.CategoryID'])) {
                $CategoryIDs = (array)$Wheres['d.CategoryID'];
                if ($Perms === false) {
                    $CategoryIDs = array();
                } elseif (is_array($Perms))
                    $CategoryIDs = array_intersect($CategoryIDs, $Perms);

                if (count($CategoryIDs) == 0) {
                    return 0;
                } else {
                    $Perms = $CategoryIDs;
                }
            }

            $Categories = CategoryModel::categories();

//         $CountOld = 0;
//         foreach ($Categories as $Cat) {
//            if (is_array($Perms) && !in_array($Cat['CategoryID'], $Perms))
//               continue;
//            $CountOld += (int)$Cat['CountDiscussions'];
//         }

            if (!is_array($Perms)) {
                $Perms = array_keys($Categories);
            }

            $Count = 0;
            foreach ($Perms as $CategoryID) {
                if (isset($Categories[$CategoryID])) {
                    $Count += (int)$Categories[$CategoryID]['CountDiscussions'];
                }
            }

//         if ($Count !== $CountOld) {
//            throw new Exception("Category Count error!", 500);
//         }

            return $Count;
        }

        if ($Perms !== true) {
            $this->SQL->whereIn('c.CategoryID', $Perms);
        }

        $this->EventArguments['Wheres'] = &$Wheres;
        $this->fireEvent('BeforeGetCount'); // @see 'BeforeGet' for consistency in count vs. results

        $this->SQL
            ->select('d.DiscussionID', 'count', 'CountDiscussions')
            ->from('Discussion d')
            ->join('Category c', 'd.CategoryID = c.CategoryID')
            ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.Gdn::session()->UserID, 'left')
            ->where($Wheres);

        $Result = $this->SQL
            ->get()
            ->firstRow()
            ->CountDiscussions;

        return $Result;
    }

    /**
     * Count how many discussions match the given criteria.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $Wheres SQL conditions.
     * @param bool $ForceNoAnnouncements Not used.
     * @return int Number of discussions.
     */
    public function getUnreadCount($Wheres = '', $ForceNoAnnouncements = false) {
        if (is_array($Wheres) && count($Wheres) == 0) {
            $Wheres = '';
        }

        // Check permission and limit to categories as necessary
        if ($this->Watching) {
            $Perms = CategoryModel::CategoryWatch();
        } else {
            $Perms = self::CategoryPermissions();
        }

        if (!$Wheres || (count($Wheres) == 1 && isset($Wheres['d.CategoryID']))) {
            // Grab the counts from the faster category cache.
            if (isset($Wheres['d.CategoryID'])) {
                $CategoryIDs = (array)$Wheres['d.CategoryID'];
                if ($Perms === false) {
                    $CategoryIDs = array();
                } elseif (is_array($Perms))
                    $CategoryIDs = array_intersect($CategoryIDs, $Perms);

                if (count($CategoryIDs) == 0) {
                    return 0;
                } else {
                    $Perms = $CategoryIDs;
                }
            }

            $Categories = CategoryModel::categories();
            $Count = 0;

            foreach ($Categories as $Cat) {
                if (is_array($Perms) && !in_array($Cat['CategoryID'], $Perms)) {
                    continue;
                }
                $Count += (int)$Cat['CountDiscussions'];
            }
            return $Count;
        }

        if ($Perms !== true) {
            $this->SQL->whereIn('c.CategoryID', $Perms);
        }

        $this->EventArguments['Wheres'] = &$Wheres;
        $this->fireEvent('BeforeGetUnreadCount'); // @see 'BeforeGet' for consistency in count vs. results

        $this->SQL
            ->select('d.DiscussionID', 'count', 'CountDiscussions')
            ->from('Discussion d')
            ->join('Category c', 'd.CategoryID = c.CategoryID')
            ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.Gdn::session()->UserID, 'left')
            //->beginWhereGroup()
            //->where('w.DateLastViewed', null)
            //->orWhere('d.DateLastComment >', 'w.DateLastViewed')
            //->endWhereGroup()
            ->where('d.CountComments >', 'COALESCE(w.CountComments, 0)', true, false)
            ->where($Wheres);

        $Result = $this->SQL
            ->get()
            ->firstRow()
            ->CountDiscussions;

        return $Result;
    }

    /**
     * Get data for a single discussion by ForeignID.
     *
     * @since 2.0.18
     * @access public
     *
     * @param int $ForeignID Foreign ID of discussion to get.
     * @return object SQL result.
     */
    public function getForeignID($ForeignID, $Type = '') {
        $Hash = ForeignIDHash($ForeignID);
        $Session = Gdn::session();
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
            ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$Session->UserID, 'left')
            ->join('User iu', 'd.InsertUserID = iu.UserID', 'left')// Insert user
            ->join('Comment lc', 'd.LastCommentID = lc.CommentID', 'left')// Last comment
            ->join('User lcu', 'lc.InsertUserID = lcu.UserID', 'left')// Last comment user
            ->where('d.ForeignID', $Hash);

        if ($Type != '') {
            $this->SQL->where('d.Type', $Type);
        }

        $Discussion = $this->SQL
            ->get()
            ->firstRow();

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->AddDenormalizedViews($Discussion);
        }

        return $Discussion;
    }

    /**
     * Get data for a single discussion by ID.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $DiscussionID Unique ID of discussion to get.
     * @return object SQL result.
     */
    public function getID($DiscussionID, $DataSetType = DATASET_TYPE_OBJECT, $Options = array()) {
        $Session = Gdn::session();
        $this->fireEvent('BeforeGetID');

        $this->Options($Options);

        $Discussion = $this->SQL
            ->select('d.*')
            ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
            ->select('w.CountComments', '', 'CountCommentWatch')
            ->select('w.Participated')
            ->select('d.DateLastComment', '', 'LastDate')
            ->select('d.LastCommentUserID', '', 'LastUserID')
            ->from('Discussion d')
            ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$Session->UserID, 'left')
            ->where('d.DiscussionID', $DiscussionID)
            ->get()
            ->firstRow();

        if (!$Discussion) {
            return $Discussion;
        }

        $this->Calculate($Discussion);

        // Join in the users.
        $Discussion = array($Discussion);
        Gdn::userModel()->joinUsers($Discussion, array('LastUserID', 'InsertUserID'));
        $Discussion = $Discussion[0];

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->AddDenormalizedViews($Discussion);
        }

        return $DataSetType == DATASET_TYPE_ARRAY ? (array)$Discussion : $Discussion;
    }

    /**
     * Get discussions that have IDs in the provided array.
     *
     * @since 2.0.18
     * @access public
     *
     * @param array $DiscussionIDs Array of DiscussionIDs to get.
     * @return object SQL result.
     */
    public function getIn($DiscussionIDs) {
        $Session = Gdn::session();
        $this->fireEvent('BeforeGetIn');
        $Result = $this->SQL
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
            ->join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$Session->UserID, 'left')
            ->join('User iu', 'd.InsertUserID = iu.UserID', 'left')// Insert user
            ->join('Comment lc', 'd.LastCommentID = lc.CommentID', 'left')// Last comment
            ->join('User lcu', 'lc.InsertUserID = lcu.UserID', 'left')// Last comment user
            ->whereIn('d.DiscussionID', $DiscussionIDs)
            ->get();

        // Spliting views off to side table. Aggregate cached keys here.
        if (c('Vanilla.Views.Denormalize', false)) {
            $this->AddDenormalizedViews($Result);
        }

        return $Result;
    }

    /**
     * Get discussions sort order based on config and optional user preference.
     *
     * @return string Column name.
     */
    public static function getSortField() {
        deprecated("getSortField", "getOrderBy");
        $SortField = c('Vanilla.Discussions.SortField', 'd.DateLastComment');
        if (c('Vanilla.Discussions.UserSortField')) {
            $SortField = Gdn::session()->GetPreference('Discussions.SortField', $SortField);
        }

        return $SortField;
    }

    /**
     *
     *
     * @param $DiscussionID
     * @return mixed|null
     */
    public static function getViewsFallback($DiscussionID) {
        // Not found. Check main table.
        $Views = val('CountViews', Gdn::sql()
            ->select('CountViews')
            ->from('Discussion')
            ->where('DiscussionID', $DiscussionID)
            ->get()->firstRow(DATASET_TYPE_ARRAY), null);

        // Found. Insert into denormalized table and return.
        if (!is_null($Views)) {
            return $Views;
        }

        return null;
    }

    /**
     * Marks the specified announcement as dismissed by the specified user.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $DiscussionID Unique ID of discussion being affected.
     * @param int $UserID Unique ID of the user being affected.
     */
    public function dismissAnnouncement($DiscussionID, $UserID) {
        $Count = $this->SQL
            ->select('UserID')
            ->from('UserDiscussion')
            ->where('DiscussionID', $DiscussionID)
            ->where('UserID', $UserID)
            ->get()
            ->numRows();

        $CountComments = $this->SQL
            ->select('CountComments')
            ->from('Discussion')
            ->where('DiscussionID', $DiscussionID)
            ->get()
            ->firstRow()
            ->CountComments;

        if ($Count > 0) {
            $this->SQL
                ->update('UserDiscussion')
                ->set('CountComments', $CountComments)
                ->set('DateLastViewed', Gdn_Format::toDateTime())
                ->set('Dismissed', '1')
                ->where('DiscussionID', $DiscussionID)
                ->where('UserID', $UserID)
                ->put();
        } else {
            $this->SQL->Options('Ignore', true);
            $this->SQL->insert(
                'UserDiscussion',
                array(
                    'UserID' => $UserID,
                    'DiscussionID' => $DiscussionID,
                    'CountComments' => $CountComments,
                    'DateLastViewed' => Gdn_Format::toDateTime(),
                    'Dismissed' => '1'
                )
            );
        }
    }

    /**
     * Evented wrapper for Gdn_Model::SetField
     *
     * @param integer $RowID
     * @param string $Property
     * @param mixed $Value
     */
    public function setField($RowID, $Property, $Value = false) {
        if (!is_array($Property)) {
            $Property = array($Property => $Value);
        }

        $this->EventArguments['DiscussionID'] = $RowID;
        if (!is_array($Property)) {
            $this->EventArguments['SetField'] = array($Property => $Value);
        } else {
            $this->EventArguments['SetField'] = $Property;
        }

        parent::SetField($RowID, $Property, $Value);
        $this->fireEvent('AfterSetField');
    }

    /**
     * Inserts or updates the discussion via form values.
     *
     * Events: BeforeSaveDiscussion, AfterValidateDiscussion, AfterSaveDiscussion.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $FormPostValues Data sent from the form model.
     * @param array $Settings Currently unused.
     * @return int $DiscussionID Unique ID of the discussion.
     */
    public function save($FormPostValues, $Settings = false) {
        $Session = Gdn::session();

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules:
        $this->Validation->applyRule('Body', 'Required');
        $this->Validation->addRule('MeAction', 'function:ValidateMeAction');
        $this->Validation->applyRule('Body', 'MeAction');
        $MaxCommentLength = Gdn::config('Vanilla.Comment.MaxLength');
        if (is_numeric($MaxCommentLength) && $MaxCommentLength > 0) {
            $this->Validation->SetSchemaProperty('Body', 'Length', $MaxCommentLength);
            $this->Validation->applyRule('Body', 'Length');
        }

        // Validate category permissions.
        $CategoryID = val('CategoryID', $FormPostValues);
        if ($CategoryID > 0) {
            $Category = CategoryModel::categories($CategoryID);
            if ($Category && !$Session->checkPermission('Vanilla.Discussions.Add', true, 'Category', val('PermissionCategoryID', $Category))) {
                $this->Validation->addValidationResult('CategoryID', 'You do not have permission to post in this category');
            }
        }

        // Get the DiscussionID from the form so we know if we are inserting or updating.
        $DiscussionID = val('DiscussionID', $FormPostValues, '');

        // See if there is a source ID.
        if (val('SourceID', $FormPostValues)) {
            $DiscussionID = $this->SQL->getWhere('Discussion', arrayTranslate($FormPostValues, array('Source', 'SourceID')))->value('DiscussionID');
            if ($DiscussionID) {
                $FormPostValues['DiscussionID'] = $DiscussionID;
            }
        } elseif (val('ForeignID', $FormPostValues)) {
            $DiscussionID = $this->SQL->getWhere('Discussion', array('ForeignID' => $FormPostValues['ForeignID']))->value('DiscussionID');
            if ($DiscussionID) {
                $FormPostValues['DiscussionID'] = $DiscussionID;
            }
        }

        $Insert = $DiscussionID == '' ? true : false;
        $this->EventArguments['Insert'] = $Insert;

        if ($Insert) {
            unset($FormPostValues['DiscussionID']);
            // If no categoryid is defined, grab the first available.
            if (!val('CategoryID', $FormPostValues) && !c('Vanilla.Categories.Use')) {
                $FormPostValues['CategoryID'] = val('CategoryID', CategoryModel::defaultCategory(), -1);
            }

            $this->addInsertFields($FormPostValues);

            // The UpdateUserID used to be required. Just add it if it still is.
            if (!$this->Schema->getProperty('UpdateUserID', 'AllowNull', true)) {
                $FormPostValues['UpdateUserID'] = $FormPostValues['InsertUserID'];
            }

            // $FormPostValues['LastCommentUserID'] = $Session->UserID;
            $FormPostValues['DateLastComment'] = $FormPostValues['DateInserted'];
        } else {
            // Add the update fields.
            $this->addUpdateFields($FormPostValues);
        }

        // Set checkbox values to zero if they were unchecked
        if (val('Announce', $FormPostValues, '') === false) {
            $FormPostValues['Announce'] = 0;
        }

        if (val('Closed', $FormPostValues, '') === false) {
            $FormPostValues['Closed'] = 0;
        }

        if (val('Sink', $FormPostValues, '') === false) {
            $FormPostValues['Sink'] = 0;
        }

        //	Prep and fire event
        $this->EventArguments['FormPostValues'] = &$FormPostValues;
        $this->EventArguments['DiscussionID'] = $DiscussionID;
        $this->fireEvent('BeforeSaveDiscussion');

        // Validate the form posted values
        $this->validate($FormPostValues, $Insert);
        $ValidationResults = $this->validationResults();

        // If the body is not required, remove it's validation errors.
        $BodyRequired = c('Vanilla.DiscussionBody.Required', true);
        if (!$BodyRequired && array_key_exists('Body', $ValidationResults)) {
            unset($ValidationResults['Body']);
        }

        if (count($ValidationResults) == 0) {
            // If the post is new and it validates, make sure the user isn't spamming
            if (!$Insert || !$this->checkForSpam('Discussion')) {
                // Get all fields on the form that relate to the schema
                $Fields = $this->Validation->schemaValidationFields();

                // Check for spam.
                $spam = SpamModel::isSpam('Discussion', $Fields);
                if ($spam) {
                    return SPAM;
                }

                // Get DiscussionID if one was sent
                $DiscussionID = intval(val('DiscussionID', $Fields, 0));

                // Remove the primary key from the fields for saving.
                unset($Fields['DiscussionID']);
                $StoredCategoryID = false;

                if ($DiscussionID > 0) {
                    // Updating
                    $Stored = $this->getID($DiscussionID, DATASET_TYPE_OBJECT);

                    // Block Format change if we're forcing the formatter.
                    if (c('Garden.ForceInputFormatter')) {
                        unset($Fields['Format']);
                    }

                    $isValid = true;
                    $invalidReturnType = false;
                    $this->EventArguments['DiscussionData'] = array_merge($Fields, array('DiscussionID' => $DiscussionID));
                    $this->EventArguments['IsValid'] = &$isValid;
                    $this->EventArguments['InvalidReturnType'] = &$invalidReturnType;
                    $this->fireEvent('AfterValidateDiscussion');

                    if (!$isValid) {
                        return $invalidReturnType;
                    }

                    // Clear the cache if necessary.
                    $CacheKeys = array();
                    if (val('Announce', $Stored) != val('Announce', $Fields)) {
                        $CacheKeys[] = $this->getAnnouncementCacheKey();
                        $CacheKeys[] = $this->getAnnouncementCacheKey(val('CategoryID', $Stored));
                    }
                    if (val('CategoryID', $Stored) != val('CategoryID', $Fields)) {
                        $CacheKeys[] = $this->getAnnouncementCacheKey(val('CategoryID', $Fields));
                    }
                    foreach ($CacheKeys as $CacheKey) {
                        Gdn::cache()->remove($CacheKey);
                    }

                    self::serializeRow($Fields);
                    $this->SQL->put($this->Name, $Fields, array($this->PrimaryKey => $DiscussionID));

                    setValue('DiscussionID', $Fields, $DiscussionID);
                    LogModel::logChange('Edit', 'Discussion', (array)$Fields, $Stored);

                    if (val('CategoryID', $Stored) != val('CategoryID', $Fields)) {
                        $StoredCategoryID = val('CategoryID', $Stored);
                    }

                } else {
                    // Inserting.
                    if (!val('Format', $Fields) || c('Garden.ForceInputFormatter')) {
                        $Fields['Format'] = c('Garden.InputFormatter', '');
                    }

                    if (c('Vanilla.QueueNotifications')) {
                        $Fields['Notified'] = ActivityModel::SENT_PENDING;
                    }

                    // Check for approval
                    $ApprovalRequired = checkRestriction('Vanilla.Approval.Require');
                    if ($ApprovalRequired && !val('Verified', Gdn::session()->User)) {
                        LogModel::insert('Pending', 'Discussion', $Fields);
                        return UNAPPROVED;
                    }

                    $isValid = true;
                    $invalidReturnType = false;
                    $this->EventArguments['DiscussionData'] = $Fields;
                    $this->EventArguments['IsValid'] = &$isValid;
                    $this->EventArguments['InvalidReturnType'] = &$invalidReturnType;
                    $this->fireEvent('AfterValidateDiscussion');

                    if (!$isValid) {
                        return $invalidReturnType;
                    }

                    // Create discussion
                    $this->serializeRow($Fields);
                    $DiscussionID = $this->SQL->insert($this->Name, $Fields);
                    $Fields['DiscussionID'] = $DiscussionID;

                    // Update the cache.
                    if ($DiscussionID && Gdn::cache()->activeEnabled()) {
                        $CategoryCache = array(
                            'LastDiscussionID' => $DiscussionID,
                            'LastCommentID' => null,
                            'LastTitle' => Gdn_Format::text($Fields['Name']), // kluge so JoinUsers doesn't wipe this out.
                            'LastUserID' => $Fields['InsertUserID'],
                            'LastDateInserted' => $Fields['DateInserted'],
                            'LastUrl' => DiscussionUrl($Fields)
                        );
                        CategoryModel::setCache($Fields['CategoryID'], $CategoryCache);

                        // Clear the cache if necessary.
                        if (val('Announce', $Fields)) {
                            Gdn::cache()->remove($this->getAnnouncementCacheKey(val('CategoryID', $Fields)));

                            if (val('Announce', $Fields) == 1) {
                                Gdn::cache()->remove($this->getAnnouncementCacheKey());
                            }
                        }
                    }

                    // Update the user's discussion count.
                    $InsertUser = Gdn::userModel()->getID($Fields['InsertUserID']);
                    $this->updateUserDiscussionCount($Fields['InsertUserID'], val('CountDiscussions', $InsertUser, 0) > 100);

                    // Mark the user as participated and update DateLastViewed.
                    $this->SQL->replace(
                        'UserDiscussion',
                        array('Participated' => 1, 'DateLastViewed' => Gdn_Format::toDateTime()),
                        array('DiscussionID' => $DiscussionID, 'UserID' => val('InsertUserID', $Fields))
                    );

                    // Assign the new DiscussionID to the comment before saving.
                    $FormPostValues['IsNewDiscussion'] = true;
                    $FormPostValues['DiscussionID'] = $DiscussionID;

                    // Do data prep.
                    $DiscussionName = val('Name', $Fields, '');
                    $Story = val('Body', $Fields, '');
                    $NotifiedUsers = array();

                    $UserModel = Gdn::userModel();
                    $ActivityModel = new ActivityModel();

                    if (val('Type', $FormPostValues)) {
                        $Code = 'HeadlineFormat.Discussion.'.$FormPostValues['Type'];
                    } else {
                        $Code = 'HeadlineFormat.Discussion';
                    }

                    $HeadlineFormat = t($Code, '{ActivityUserID,user} started a new discussion: <a href="{Url,html}">{Data.Name,text}</a>');
                    $Category = CategoryModel::categories(val('CategoryID', $Fields));
                    $Activity = array(
                        'ActivityType' => 'Discussion',
                        'ActivityUserID' => $Fields['InsertUserID'],
                        'HeadlineFormat' => $HeadlineFormat,
                        'RecordType' => 'Discussion',
                        'RecordID' => $DiscussionID,
                        'Route' => DiscussionUrl($Fields),
                        'Data' => array(
                            'Name' => $DiscussionName,
                            'Category' => val('Name', $Category)
                        )
                    );

                    // Allow simple fulltext notifications
                    if (c('Vanilla.Activity.ShowDiscussionBody', false)) {
                        $Activity['Story'] = $Story;
                    }

                    // Notify all of the users that were mentioned in the discussion.
                    $Usernames = getMentions($DiscussionName.' '.$Story);

                    // Use our generic Activity for events, not mentions
                    $this->EventArguments['Activity'] = $Activity;

                    // Notify everyone that has advanced notifications.
                    if (!c('Vanilla.QueueNotifications')) {
                        try {
                            $Fields['DiscussionID'] = $DiscussionID;
                            $this->notifyNewDiscussion($Fields, $ActivityModel, $Activity);
                        } catch (Exception $Ex) {
                            throw $Ex;
                        }
                    }

                    // Notifications for mentions
                    foreach ($Usernames as $Username) {
                        $User = $UserModel->getByUsername($Username);
                        if (!$User) {
                            continue;
                        }

                        // Check user can still see the discussion.
                        if (!$this->canView($Fields, $User->UserID)) {
                            continue;
                        }

                        $Activity['HeadlineFormat'] = t('HeadlineFormat.Mention', '{ActivityUserID,user} mentioned you in <a href="{Url,html}">{Data.Name,text}</a>');

                        $Activity['NotifyUserID'] = val('UserID', $User);
                        $ActivityModel->queue($Activity, 'Mention');
                    }

                    // Throw an event for users to add their own events.
                    $this->EventArguments['Discussion'] = $Fields;
                    $this->EventArguments['NotifiedUsers'] = $NotifiedUsers;
                    $this->EventArguments['MentionedUsers'] = $Usernames;
                    $this->EventArguments['ActivityModel'] = $ActivityModel;
                    $this->fireEvent('BeforeNotification');

                    // Send all notifications.
                    $ActivityModel->saveQueue();
                }

                // Get CategoryID of this discussion

                $Discussion = $this->getID($DiscussionID, DATASET_TYPE_OBJECT);
                $CategoryID = val('CategoryID', $Discussion, false);

                // Update discussion counter for affected categories.
                if ($Insert || $StoredCategoryID) {
                    $this->incrementNewDiscussion($Discussion);
                }

                if ($StoredCategoryID) {
                    $this->updateDiscussionCount($StoredCategoryID);
                }

                // Fire an event that the discussion was saved.
                $this->EventArguments['FormPostValues'] = $FormPostValues;
                $this->EventArguments['Fields'] = $Fields;
                $this->EventArguments['DiscussionID'] = $DiscussionID;
                $this->fireEvent('AfterSaveDiscussion');
            }
        }

        return $DiscussionID;
    }

    /**
     *
     * @param type $Discussion
     * @param type $NotifiedUsers
     * @param ActivityModel $ActivityModel
     */
    public function notifyNewDiscussion($Discussion, $ActivityModel, $Activity) {
        if (is_numeric($Discussion)) {
            $Discussion = $this->getID($Discussion);
        }

        $CategoryID = val('CategoryID', $Discussion);

        // Figure out the category that governs this notification preference.
        $i = 0;
        $Category = CategoryModel::categories($CategoryID);
        if (!$Category) {
            return;
        }

        while ($Category['Depth'] > 2 && $i < 20) {
            if (!$Category || $Category['Archived']) {
                return;
            }
            $i++;
            $Category = CategoryModel::categories($Category['ParentCategoryID']);
        }

        // Grab all of the users that need to be notified.
        $Data = $this->SQL
            ->whereIn('Name', array('Preferences.Email.NewDiscussion.'.$Category['CategoryID'], 'Preferences.Popup.NewDiscussion.'.$Category['CategoryID']))
            ->get('UserMeta')->resultArray();

//      decho($Data, 'Data');


        $NotifyUsers = array();
        foreach ($Data as $Row) {
            if (!$Row['Value']) {
                continue;
            }

            $UserID = $Row['UserID'];
            // Check user can still see the discussion.
            if (!$this->canView($Discussion, $UserID)) {
                continue;
            }

            $Name = $Row['Name'];
            if (strpos($Name, '.Email.') !== false) {
                $NotifyUsers[$UserID]['Emailed'] = ActivityModel::SENT_PENDING;
            } elseif (strpos($Name, '.Popup.') !== false) {
                $NotifyUsers[$UserID]['Notified'] = ActivityModel::SENT_PENDING;
            }
        }

//      decho($NotifyUsers);

        $InsertUserID = val('InsertUserID', $Discussion);
        foreach ($NotifyUsers as $UserID => $Prefs) {
            if ($UserID == $InsertUserID) {
                continue;
            }

            $Activity['NotifyUserID'] = $UserID;
            $Activity['Emailed'] = val('Emailed', $Prefs, false);
            $Activity['Notified'] = val('Notified', $Prefs, false);
            $ActivityModel->Queue($Activity);

//         decho($Activity, 'die');
        }

//      die();
    }

    /**
     * Updates the CountDiscussions value on the category based on the CategoryID
     * being saved.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $CategoryID Unique ID of category we are updating.
     */
    public function updateDiscussionCount($CategoryID, $Discussion = false) {
        $DiscussionID = val('DiscussionID', $Discussion, false);
        if (strcasecmp($CategoryID, 'All') == 0) {
            $Exclude = (bool)Gdn::config('Vanilla.Archive.Exclude');
            $ArchiveDate = Gdn::config('Vanilla.Archive.Date');
            $Params = array();
            $Where = '';

            if ($Exclude && $ArchiveDate) {
                $Where = 'where d.DateLastComment > :ArchiveDate';
                $Params[':ArchiveDate'] = $ArchiveDate;
            }

            // Update all categories.
            $Sql = "update :_Category c
            left join (
              select
                d.CategoryID,
                coalesce(count(d.DiscussionID), 0) as CountDiscussions,
                coalesce(sum(d.CountComments), 0) as CountComments
              from :_Discussion d
              $Where
              group by d.CategoryID
            ) d
              on c.CategoryID = d.CategoryID
            set
               c.CountDiscussions = coalesce(d.CountDiscussions, 0),
               c.CountComments = coalesce(d.CountComments, 0)";
            $Sql = str_replace(':_', $this->Database->DatabasePrefix, $Sql);
            $this->Database->query($Sql, $Params, 'DiscussionModel_UpdateDiscussionCount');

        } elseif (is_numeric($CategoryID)) {
            $this->SQL
                ->select('d.DiscussionID', 'count', 'CountDiscussions')
                ->select('d.CountComments', 'sum', 'CountComments')
                ->from('Discussion d')
                ->where('d.CategoryID', $CategoryID);

            $this->AddArchiveWhere();

            $Data = $this->SQL->get()->firstRow();
            $CountDiscussions = (int)GetValue('CountDiscussions', $Data, 0);
            $CountComments = (int)GetValue('CountComments', $Data, 0);

            $CacheAmendment = array(
                'CountDiscussions' => $CountDiscussions,
                'CountComments' => $CountComments
            );

            if ($DiscussionID) {
                $CacheAmendment = array_merge($CacheAmendment, array(
                    'LastDiscussionID' => $DiscussionID,
                    'LastCommentID' => null,
                    'LastDateInserted' => val('DateInserted', $Discussion)
                ));
            }

            $CategoryModel = new CategoryModel();
            $CategoryModel->setField($CategoryID, $CacheAmendment);
            $CategoryModel->SetRecentPost($CategoryID);
        }
    }

    public function incrementNewDiscussion($Discussion) {
        if (is_numeric($Discussion)) {
            $Discussion = $this->getID($Discussion);
        }

        if (!$Discussion) {
            return;
        }

        $this->SQL->update('Category')
            ->set('CountDiscussions', 'CountDiscussions + 1', false)
            ->set('LastDiscussionID', val('DiscussionID', $Discussion))
            ->set('LastCommentID', null)
            ->set('LastDateInserted', val('DateInserted', $Discussion))
            ->where('CategoryID', val('CategoryID', $Discussion))
            ->put();

        $Category = CategoryModel::categories(val('CategoryID', $Discussion));
        if ($Category) {
            CategoryModel::SetCache($Category['CategoryID'], array(
                'CountDiscussions' => $Category['CountDiscussions'] + 1,
                'LastDiscussionID' => val('DiscussionID', $Discussion),
                'LastCommentID' => null,
                'LastDateInserted' => val('DateInserted', $Discussion),
                'LastTitle' => Gdn_Format::text(val('Name', $Discussion, t('No Title'))),
                'LastUserID' => val('InsertUserID', $Discussion),
                'LastDiscussionUserID' => val('InsertUserID', $Discussion),
                'LastUrl' => DiscussionUrl($Discussion, false, '//').'#latest'));
        }
    }

    public function updateUserDiscussionCount($UserID, $Inc = false) {
        if ($Inc) {
            $User = Gdn::userModel()->getID($UserID);

            $CountDiscussions = val('CountDiscussions', $User);
            // Increment if 100 or greater; Recalc on 120, 140 etc.
            if ($CountDiscussions >= 100 && $CountDiscussions % 20 !== 0) {
                $this->SQL->update('User')
                    ->set('CountDiscussions', 'CountDiscussions + 1', false)
                    ->where('UserID', $UserID)
                    ->put();

                Gdn::userModel()->UpdateUserCache($UserID, 'CountDiscussions', $CountDiscussions + 1);
                return;
            }
        }

        $CountDiscussions = $this->SQL
            ->select('DiscussionID', 'count', 'CountDiscussions')
            ->from('Discussion')
            ->where('InsertUserID', $UserID)
            ->get()->value('CountDiscussions', 0);

        // Save the count to the user table
        Gdn::userModel()->setField($UserID, 'CountDiscussions', $CountDiscussions);
    }

    /**
     * Update and get bookmark count for the specified user.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $UserID Unique ID of user to update.
     * @return int Total number of bookmarks user has.
     */
    public function setUserBookmarkCount($UserID) {
        $Count = $this->UserBookmarkCount($UserID);
        Gdn::userModel()->setField($UserID, 'CountBookmarks', $Count);

        return $Count;
    }

    /**
     * Updates a discussion field.
     *
     * By default, this toggles the specified between '1' and '0'. If $ForceValue
     * is provided, the field is set to this value instead. An example use is
     * announcing and unannouncing a discussion.
     *
     * @param int $DiscussionID Unique ID of discussion being updated.
     * @param string $Property Name of field to be updated.
     * @param mixed $ForceValue If set, overrides toggle behavior with this value.
     * @return mixed Value that was ultimately set for the field.
     */
    public function setProperty($DiscussionID, $Property, $ForceValue = null) {
        if ($ForceValue !== null) {
            $Value = $ForceValue;
        } else {
            $Value = '1';
            $Discussion = $this->getID($DiscussionID);
            $Value = ($Discussion->$Property == '1' ? '0' : '1');
        }
        $this->SQL
            ->update('Discussion')
            ->set($Property, $Value)
            ->where('DiscussionID', $DiscussionID)
            ->put();

        return $Value;
    }

    /**
     * Sets the discussion score for specified user.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $DiscussionID Unique ID of discussion to update.
     * @param int $UserID Unique ID of user setting score.
     * @param int $Score New score for discussion.
     * @return int Total score.
     */
    public function setUserScore($DiscussionID, $UserID, $Score) {
        // Insert or update the UserDiscussion row
        $this->SQL->replace(
            'UserDiscussion',
            array('Score' => $Score),
            array('DiscussionID' => $DiscussionID, 'UserID' => $UserID)
        );

        // Get the total new score
        $TotalScore = $this->SQL->select('Score', 'sum', 'TotalScore')
            ->from('UserDiscussion')
            ->where('DiscussionID', $DiscussionID)
            ->get()
            ->firstRow()
            ->TotalScore;

        // Update the Discussion's cached version
        $this->SQL->update('Discussion')
            ->set('Score', $TotalScore)
            ->where('DiscussionID', $DiscussionID)
            ->put();

        return $TotalScore;
    }

    /**
     * Gets the discussion score for specified user.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $DiscussionID Unique ID of discussion getting score for.
     * @param int $UserID Unique ID of user whose score we're getting.
     * @return int Total score.
     */
    public function getUserScore($DiscussionID, $UserID) {
        $Data = $this->SQL->select('Score')
            ->from('UserDiscussion')
            ->where('DiscussionID', $DiscussionID)
            ->where('UserID', $UserID)
            ->get()
            ->firstRow();

        return $Data ? $Data->Score : 0;
    }

    /**
     * Increments view count for the specified discussion.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $DiscussionID Unique ID of discussion to get +1 view.
     */
    public function addView($DiscussionID) {
        $IncrementBy = 0;
        if (c('Vanilla.Views.Denormalize', false) &&
            Gdn::cache()->activeEnabled() &&
            Gdn::cache()->Type() != Gdn_Cache::CACHE_TYPE_NULL
        ) {
            $WritebackLimit = c('Vanilla.Views.DenormalizeWriteback', 10);
            $CacheKey = sprintf(DiscussionModel::CACHE_DISCUSSIONVIEWS, $DiscussionID);

            // Increment. If not success, create key.
            $Views = Gdn::cache()->increment($CacheKey);
            if ($Views === Gdn_Cache::CACHEOP_FAILURE) {
                Gdn::cache()->store($CacheKey, 1);
            }

            // Every X views, writeback to Discussions
            if (($Views % $WritebackLimit) == 0) {
                $IncrementBy = floor($Views / $WritebackLimit) * $WritebackLimit;
                Gdn::cache()->Decrement($CacheKey, $IncrementBy);
            }
        } else {
            $IncrementBy = 1;
        }

        if ($IncrementBy) {
            $this->SQL
                ->update('Discussion')
                ->set('CountViews', "CountViews + {$IncrementBy}", false)
                ->where('DiscussionID', $DiscussionID)
                ->put();
        }

    }

    /**
     * Bookmarks (or unbookmarks) a discussion for the specified user.
     *
     * @param int $DiscussionID The unique id of the discussion.
     * @param int $UserID The unique id of the user.
     * @param bool|null $Bookmarked Whether or not to bookmark or unbookmark. Pass null to toggle the bookmark.
     * @return bool The new value of bookmarked.
     */
    public function bookmark($DiscussionID, $UserID, $Bookmarked = null) {
        // Get the current user discussion record.
        $UserDiscussion = $this->SQL->getWhere(
            'UserDiscussion',
            array('DiscussionID' => $DiscussionID, 'UserID' => $UserID)
        )->firstRow(DATASET_TYPE_ARRAY);

        if ($UserDiscussion) {
            if ($Bookmarked === null) {
                $Bookmarked = !$UserDiscussion['Bookmarked'];
            }

            // Update the bookmarked value.
            $this->SQL->put(
                'UserDiscussion',
                array('Bookmarked' => (int)$Bookmarked),
                array('DiscussionID' => $DiscussionID, 'UserID' => $UserID)
            );
        } else {
            if ($Bookmarked === null) {
                $Bookmarked = true;
            }

            // Insert the new bookmarked value.
            $this->SQL->Options('Ignore', true)
                ->insert('UserDiscussion', array(
                    'UserID' => $UserID,
                    'DiscussionID' => $DiscussionID,
                    'Bookmarked' => (int)$Bookmarked
                ));
        }

        $this->EventArguments['DiscussionID'] = $DiscussionID;
        $this->EventArguments['UserID'] = $UserID;
        $this->EventArguments['Bookmarked'] = $Bookmarked;
        $this->fireEvent('AfterBookmark');

        return (bool)$Bookmarked;
    }

    /**
     * Bookmarks (or unbookmarks) a discussion for specified user.
     *
     * Events: AfterBookmarkDiscussion.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $DiscussionID Unique ID of discussion to (un)bookmark.
     * @param int $UserID Unique ID of user doing the (un)bookmarking.
     * @param object $Discussion Discussion data.
     * @return bool Current state of the bookmark (TRUE for bookmarked, FALSE for unbookmarked).
     */
    public function bookmarkDiscussion($DiscussionID, $UserID, &$Discussion = null) {
        $State = '1';

        $DiscussionData = $this->SQL
            ->select('d.*')
            ->select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
            ->select('w.CountComments', '', 'CountCommentWatch')
            ->select('w.UserID', '', 'WatchUserID')
            ->select('w.Participated')
            ->select('d.DateLastComment', '', 'LastDate')
            ->select('d.LastCommentUserID', '', 'LastUserID')
            ->select('lcu.Name', '', 'LastName')
            ->from('Discussion d')
            ->join('UserDiscussion w', "d.DiscussionID = w.DiscussionID and w.UserID = $UserID", 'left')
            ->join('User lcu', 'd.LastCommentUserID = lcu.UserID', 'left')// Last comment user
            ->where('d.DiscussionID', $DiscussionID)
            ->get();

        $this->AddDiscussionColumns($DiscussionData);
        $Discussion = $DiscussionData->firstRow();

        if ($Discussion->WatchUserID == '') {
            $this->SQL->Options('Ignore', true);
            $this->SQL
                ->insert('UserDiscussion', array(
                    'UserID' => $UserID,
                    'DiscussionID' => $DiscussionID,
                    'Bookmarked' => $State
                ));
            $Discussion->Bookmarked = true;
        } else {
            $State = ($Discussion->Bookmarked == '1' ? '0' : '1');
            $this->SQL
                ->update('UserDiscussion')
                ->set('Bookmarked', $State)
                ->where('UserID', $UserID)
                ->where('DiscussionID', $DiscussionID)
                ->put();
            $Discussion->Bookmarked = $State;
        }

        // Update the cached bookmark count on the discussion
        $BookmarkCount = $this->BookmarkCount($DiscussionID);
        $this->SQL->update('Discussion')
            ->set('CountBookmarks', $BookmarkCount)
            ->where('DiscussionID', $DiscussionID)
            ->put();
        $this->CountDiscussionBookmarks = $BookmarkCount;


        // Prep and fire event
        $this->EventArguments['Discussion'] = $Discussion;
        $this->EventArguments['State'] = $State;
        $this->fireEvent('AfterBookmarkDiscussion');

        return $State == '1' ? true : false;
    }

    /**
     * Gets number of bookmarks specified discussion has (all users).
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $DiscussionID Unique ID of discussion for which to tally bookmarks.
     * @return int Total number of bookmarks.
     */
    public function bookmarkCount($DiscussionID) {
        $Data = $this->SQL
            ->select('DiscussionID', 'count', 'Count')
            ->from('UserDiscussion')
            ->where('DiscussionID', $DiscussionID)
            ->where('Bookmarked', '1')
            ->get()
            ->firstRow();

        return $Data !== false ? $Data->Count : 0;
    }

    /**
     * Gets number of bookmarks specified user has.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $UserID Unique ID of user for which to tally bookmarks.
     * @return int Total number of bookmarks.
     */
    public function userBookmarkCount($UserID) {
        $Data = $this->SQL
            ->select('ud.DiscussionID', 'count', 'Count')
            ->from('UserDiscussion ud')
            ->join('Discussion d', 'd.DiscussionID = ud.DiscussionID')
            ->where('ud.UserID', $UserID)
            ->where('ud.Bookmarked', '1')
            ->get()
            ->firstRow();

        return $Data !== false ? $Data->Count : 0;
    }

    /**
     * Delete a discussion.
     *
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
     * @return bool Always returns TRUE.
     * @since 2.0.0
     */
    public function deleteID($discussionID, $options = array()) {
        // Retrieve the users who have bookmarked this discussion.
        $BookmarkData = $this->GetBookmarkUsers($discussionID);

        $Data = $this->SQL
            ->select('*')
            ->from('Discussion')
            ->where('DiscussionID', $discussionID)
            ->get()->firstRow(DATASET_TYPE_ARRAY);

        $UserID = false;
        $CategoryID = false;
        if ($Data) {
            $UserID = $Data['InsertUserID'];
            $CategoryID = $Data['CategoryID'];
        }

        // Prep and fire event
        $this->EventArguments['DiscussionID'] = $discussionID;
        $this->EventArguments['Discussion'] = $Data;
        $this->fireEvent('DeleteDiscussion');

        // Execute deletion of discussion and related bits
        $this->SQL->delete('Draft', array('DiscussionID' => $discussionID));

        $Log = val('Log', $options, true);
        $LogOptions = val('LogOptions', $options, array());
        if ($Log === true) {
            $Log = 'Delete';
        }

        LogModel::BeginTransaction();

        // Log all of the comment deletes.
        $Comments = $this->SQL->getWhere('Comment', array('DiscussionID' => $discussionID))->resultArray();

        if (count($Comments) > 0 && count($Comments) < 50) {
            // A smaller number of comments should just be stored with the record.
            $Data['_Data']['Comment'] = $Comments;
            LogModel::insert($Log, 'Discussion', $Data, $LogOptions);
        } else {
            LogModel::insert($Log, 'Discussion', $Data, $LogOptions);
            foreach ($Comments as $Comment) {
                LogModel::insert($Log, 'Comment', $Comment, $LogOptions);
            }
        }

        LogModel::EndTransaction();

        $this->SQL->delete('Comment', array('DiscussionID' => $discussionID));
        $this->SQL->delete('Discussion', array('DiscussionID' => $discussionID));

        $this->SQL->delete('UserDiscussion', array('DiscussionID' => $discussionID));
        $this->UpdateDiscussionCount($CategoryID);

        // Get the user's discussion count.
        $this->UpdateUserDiscussionCount($UserID);

        // Update bookmark counts for users who had bookmarked this discussion
        foreach ($BookmarkData->result() as $User) {
            $this->SetUserBookmarkCount($User->UserID);
        }

        return true;
    }

    /**
     * Convert tags from stored format to user-presentable format.
     *
     * @since 2.1
     * @access protected
     *
     * @param string Serialized array.
     * @return string Comma-separated tags.
     */
    protected function formatTags($Tags) {
        // Don't bother if there aren't any tags
        if (!$Tags) {
            return '';
        }

        // Get the array.
        if (preg_match('`^(a:)|{|\[`', $Tags)) {
            $TagsArray = dbdecode($Tags);
        } else {
            $TagsArray = $Tags;
        }

        // Compensate for deprecated space-separated format
        if (is_string($TagsArray) && $TagsArray == $Tags) {
            $TagsArray = explode(' ', $Tags);
        }

        // Safe format
        $TagsArray = Gdn_Format::text($TagsArray);

        // Send back an comma-separated string
        return (is_array($TagsArray)) ? implode(',', $TagsArray) : '';
    }

    /**
     * We don't use this functionality anymore. Previously, you had to register any sorting field before sorting with it.
     */
    public static function allowedSortFields($Allowed = null) {
        deprecated("allowedSortFields");
    }

    /**
     * Tests whether a user has permission to view a specific discussion.
     *
     * @param object|array|integer $discussion The discussion ID or the discussion to test.
     * @param integer $userID The ID of the user to test permission for. If empty, it defaults to Session user.
     * @return bool Whether the user can view the discussion.
     * @throws Exception
     */
    public function canView($discussion, $userID = 0) {
        $canView = $this->checkPermission($discussion, 'Vanilla.Discussions.View', $userID);
        return $canView;
    }

    /**
     * Tests whether a user has permission for a discussion by checking category-specific permissions.
     * Fires an event that can override the calculated permission.
     *
     * @param object|array|integer $discussion The discussion ID or the discussion to test.
     * @param string $permission The category permission to test against the user.
     * @param integer $userID The ID of the user to test permission for. If empty, it defaults to Session user.
     * @return bool Whether the user has the specified permission privileges to the discussion.
     * @throws Exception
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
                        Logger::NOTICE, 'Filter: '.$filterSetKey.' => '.htmlentities($filterKey)
                        .' does not exist in the DiscussionModel\'s allowed filters array.'
                    );
                }
            }
        }
        return $filterKeys;
    }

    /**
     * Retrieves the sort key from an array and if the value is valid, returns it. Works real well with unfiltered
     * request arguments. (i.e., Gdn::request()->get()) Will only return a safe sort key from the sort array or an
     * empty string if not found.
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
                Logger::NOTICE, 'Sort: '.htmlentities($unsafeSortKey)
                .' does not exist in the DiscussionModel\'s allowed sorts array.'
            );
        }
        return '';
    }

    /**
     * Checks the allowed sorts array for the string and it is valid, returns it the string. If not, returns an empty
     * string. Will only return a safe sort key from the sort array or an empty string if not found.
     *
     * @param array $string The string to get the sort from.
     * @return string A valid sort key or an empty string.
     */
    protected function getSortFromString($string) {
        if (val($string, self::$allowedSorts)) {
            // Sort key is valid.
            return $string;
        }
        else {
            Logger::log(
                Logger::NOTICE, 'Sort: '.htmlentities($string)
                .' does not exist in the DiscussionModel\'s allowed sorts array.'
            );
        }
        return '';
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
        foreach ($filterKeyValues as $key => $value) {
            $allFilters = self::getAllowedFilters();
            foreach ($filterKeyValues as $key => $value) {
                if (isset($allFilters[$key]['filters'][$value])) {
                    $filters[] = $allFilters[$key]['filters'][$value];
                }
            }
        }
        return $filters;
    }

    /**
     * @param $sortKey
     * @return bool|mixed
     */
    protected function getSortFromKey($sortKey) {
        return val($sortKey, self::getAllowedSorts(), []);
    }

    /**
     * Get the current sort/filter query string by passing no parameters or pass either a new filter key or sort key
     * to build a new query string, leaving the other properties intact.
     *
     * @param string $selectedSort
     * @param array $selectedFilters
     * @param string $sortKeyToSet The key name of the sort in the sorts array.
     * @param array $filterKeysToSet An array of filters, where the key is the key of the filterSet
     *      in the filters array and the value is the key of the filter.
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
        self::$allowedSorts[$key] = array('key' => $key, 'name' => $name, 'orderBy' => $orderBy, 'categories' => $categoryIDs);
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
        self::$allowedFilters[$setKey]['filters'][$key] = array('key' => $key, 'setKey' => $setKey, 'name' => $name, 'wheres' => $wheres);
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
            $setName = sprintf(t('All %s'), t('Discussions'));
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
        self::$allowedSorts = array();
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
        self::$allowedFilters[$setKey]['filters'][self::EMPTY_FILTER_KEY] = array(
            'key' => self::EMPTY_FILTER_KEY,
            'setKey' => $setKey,
            'name' => sprintf(t('Clear %s'), $setName),
            'wheres' => array(), 'group' => 'default'
        );
    }
}
