<?php
/**
 * Comment model
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Manages discussion comments data.
 */
class CommentModel extends Gdn_Model {

    use \Vanilla\FloodControlTrait;

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

    /** @var bool */
    public $pageCache;

    /**
     * @var \Vanilla\CacheInterface Object used to store the FloodControl data.
     */
    protected $floodGate;

    /**
     * @var CommentModel $instance;
     */
    private static $instance;

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct() {
        parent::__construct('Comment');
        $this->floodGate = FloodControlHelper::configure($this, 'Vanilla', 'Comment');
        $this->pageCache = Gdn::cache()->activeEnabled() && c('Properties.CommentModel.pageCache', false);
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
     */
    public function commentQuery($fireEvent = true, $join = true) {
        $this->SQL->select('c.*')
//         ->select('du.Name', '', 'DeleteName')
//         ->selectCase('c.DeleteUserID', array('null' => '0', '' => '1'), 'Deleted')
//         ->join('User du', 'c.DeleteUserID = du.UserID', 'left');
            ->from('Comment c');

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
                list($field, $dir) = $defaultOrder;
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
        list($where, $options) = $this->splitWhere($where, ['joinUsers' => true]);

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
            Gdn::userModel()->joinUsers($result, ['InsertUserID', 'UpdateUserID']);
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

        Gdn::userModel()->joinUsers($result, ['InsertUserID', 'UpdateUserID']);

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
            ->join('Discussion d', 'c.DiscussionID = d.DiscussionID')
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
        Gdn::userModel()->joinUsers($data, ['InsertUserID', 'UpdateUserID']);

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
     * @return Gdn_DataSet SQL results.
     */
    public function getByUser2($userID, $limit, $offset, $lastCommentID = false, $after = null, $order = 'desc') {
        $perms = DiscussionModel::categoryPermissions();

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

        Gdn::userModel()->joinUsers($data, ['InsertUserID', 'UpdateUserID']);

        $this->EventArguments['Comments'] =& $data;
        $this->fireEvent('AfterGet');

        return $data;
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
     * @return Gdn_DataSet SQL results.
     */
    public function lookup(array $where = [], $permissionFilter = true, $limit = null, $offset = 0, $order = 'desc') {
        if ($limit === null) {
            $limit = $this->getDefaultLimit();
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

        $query = $this->SQL
            ->select('c.*')
            ->select('d.CategoryID')
            ->from('Comment c')
            ->join('Discussion d', 'c.DiscussionID = d.DiscussionID')
            ->orderBy('c.CommentID', $order);
        if (!empty($where)) {
            $query->where($where);
        }
        if ($limit) {
            $query->limit($limit);
        }
        if ($offset) {
            $query->offset($offset);
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
     * Set the order of the comments or return current order.
     *
     * Getter/setter for $this->_OrderBy.
     *
     * @since 2.0.0
     * @access public
     *
     * @param mixed Field name(s) to order results by. May be a string or array of strings.
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
     */
    public function setWatch($discussion, $limit, $offset, $totalComments) {

        $newComments = false;

        $session = Gdn::session();
        if ($session->UserID > 0) {
            // Max comments we could have seen
            $countWatch = $limit + $offset;
            if ($countWatch > $totalComments) {
                $countWatch = $totalComments;
            }

            // This dicussion looks familiar...
            if (is_numeric($discussion->CountCommentWatch)) {
                if ($countWatch < $discussion->CountCommentWatch) {
                    $countWatch = $discussion->CountCommentWatch;
                }

                if (isset($discussion->DateLastViewed)) {
                    $newComments |= Gdn_Format::toTimestamp($discussion->DateLastComment) > Gdn_Format::toTimestamp($discussion->DateLastViewed);
                }

                if ($totalComments > $discussion->CountCommentWatch) {
                    $newComments |= true;
                }

                // Update the watch data.
                if ($newComments) {
                    // Only update the watch if there are new comments.
                    $this->SQL->put(
                        'UserDiscussion',
                        [
                            'CountComments' => $countWatch,
                            'DateLastViewed' => Gdn_Format::toDateTime()
                        ],
                        [
                            'UserID' => $session->UserID,
                            'DiscussionID' => $discussion->DiscussionID
                        ]
                    );
                }

            } else {
                // Make sure the discussion isn't archived.
                $archiveDate = c('Vanilla.Archive.Date', false);
                if (!$archiveDate || (Gdn_Format::toTimestamp($discussion->DateLastComment) > Gdn_Format::toTimestamp($archiveDate))) {
                    $newComments = true;

                    // Insert watch data.
                    $this->SQL->options('Ignore', true);
                    $this->SQL->insert(
                        'UserDiscussion',
                        [
                            'UserID' => $session->UserID,
                            'DiscussionID' => $discussion->DiscussionID,
                            'CountComments' => $countWatch,
                            'DateLastViewed' => Gdn_Format::toDateTime()
                        ]
                    );
                }
            }

            /**
             * Fuzzy way of trying to automatically mark a cateogyr read again
             * if the user reads all the comments on the first few pages.
             */

            // If this discussion is in a category that has been marked read,
            // check if reading this thread causes it to be completely read again
            $categoryID = val('CategoryID', $discussion);
            if ($categoryID) {
                $category = CategoryModel::categories($categoryID);
                if ($category) {
                    $dateMarkedRead = val('DateMarkedRead', $category);
                    if ($dateMarkedRead) {
                        // Fuzzy way of looking back about 2 pages into the past
                        $lookBackCount = c('Vanilla.Discussions.PerPage', 50) * 2;

                        // Find all discussions with content from after DateMarkedRead
                        $discussionModel = new DiscussionModel();
                        $discussions = $discussionModel->get(0, 101, [
                            'CategoryID' => $categoryID,
                            'DateLastComment>' => $dateMarkedRead
                        ]);
                        unset($discussionModel);

                        // Abort if we get back as many as we asked for, meaning a
                        // lot has happened.
                        $numDiscussions = $discussions->numRows();
                        if ($numDiscussions <= $lookBackCount) {
                            // Loop over these and see if any are still unread
                            $markAsRead = true;
                            while ($discussion = $discussions->nextRow(DATASET_TYPE_ARRAY)) {
                                if ($discussion['Read']) {
                                    continue;
                                }
                                $markAsRead = false;
                                break;
                            }

                            // Mark this category read if all the new content is read
                            if ($markAsRead) {
                                $categoryModel = new CategoryModel();
                                $categoryModel->saveUserTree($categoryID, ['DateMarkedRead' => Gdn_Format::toDateTime()]);
                                unset($categoryModel);
                            }

                        }
                    }
                }
            }

        }
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
     * @since 2.0.0
     * @access public
     *
     * @param array $where Conditions
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
     * @since 2.0.0
     * @access public
     *
     * @param int $commentID Unique ID of the comment.
     * @param string $resultType Format to return comment in.
     * @param array $options options to pass to the database.
     * @return mixed SQL result in format specified by $resultType.
     */
    public function getID($commentID, $resultType = DATASET_TYPE_OBJECT, $options = []) {
        $this->options($options);

        $this->commentQuery(false); // FALSE supresses FireEvent
        $comment = $this->SQL
            ->where('c.CommentID', $commentID)
            ->get()
            ->firstRow($resultType);

        if ($comment) {
            $this->calculate($comment);
        }
        return $comment;
    }

    /**
     * Get single comment by ID as SQL result data.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $commentID Unique ID of the comment.
     * @return object SQL result.
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
            list($expr, $value) = $this->_WhereFromOrderBy($part, $comment, '');

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
     * @param array $settings Currently unused.
     * @return int $commentID
     * @since 2.0.0
     */
    public function save($formPostValues, $settings = false) {
        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules:
        $this->Validation->applyRule('Body', 'Required');
        $this->Validation->addRule('MeAction', 'function:ValidateMeAction');
        $this->Validation->applyRule('Body', 'MeAction');
        $maxCommentLength = Gdn::config('Vanilla.Comment.MaxLength');
        if (is_numeric($maxCommentLength) && $maxCommentLength > 0) {
            $this->Validation->setSchemaProperty('Body', 'Length', $maxCommentLength);
            $this->Validation->applyRule('Body', 'Length');
        }
        $minCommentLength = c('Vanilla.Comment.MinLength');
        if ($minCommentLength && is_numeric($minCommentLength)) {
            $this->Validation->setSchemaProperty('Body', 'MinLength', $minCommentLength);
            $this->Validation->addRule('MinTextLength', 'function:ValidateMinTextLength');
            $this->Validation->applyRule('Body', 'MinTextLength');
        }

        // Validate $CommentID and whether this is an insert
        $commentID = val('CommentID', $formPostValues);
        $commentID = is_numeric($commentID) && $commentID > 0 ? $commentID : false;
        $insert = $commentID === false;
        if ($insert) {
            $this->addInsertFields($formPostValues);
        } else {
            $this->addUpdateFields($formPostValues);
        }

        // Prep and fire event
        $this->EventArguments['FormPostValues'] = &$formPostValues;
        $this->EventArguments['CommentID'] = $commentID;
        $this->fireEvent('BeforeSaveComment');

        // Validate the form posted values
        if ($this->validate($formPostValues, $insert)) {
            // Backward compatible check for flood control
            if (!val('SpamCheck', $this, true)) {
                deprecated('DiscussionModel->SpamCheck attribute', 'FloodControlTrait->setFloodControlEnabled()');
                $this->setFloodControlEnabled(false);
            }

            // If the post is new and it validates, check for spam
            if (!$insert || !$this->checkUserSpamming(Gdn::session()->UserID, $this->floodGate)) {
                $fields = $this->Validation->schemaValidationFields();
                unset($fields[$this->PrimaryKey]);

                $commentData = $commentID ? array_merge($fields, ['CommentID' => $commentID]) : $fields;
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

                if ($insert === false) {
                    // Log the save.
                    LogModel::logChange('Edit', 'Comment', array_merge($fields, ['CommentID' => $commentID]));
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
                        $discussionModel = new DiscussionModel();
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
                    $this->EventArguments['CommentID'] = $commentID;
                    $this->EventArguments['Insert'] = $insert;

                    // IsNewDiscussion is passed when the first comment for new discussions are created.
                    $this->EventArguments['IsNewDiscussion'] = val('IsNewDiscussion', $formPostValues);
                    $this->fireEvent('AfterSaveComment');
                }
            }
        }

        // Update discussion's comment count
        $discussionID = val('DiscussionID', $formPostValues);
        $this->updateCommentCount($discussionID, ['Slave' => false]);

        return $commentID;
    }

    /**
     * Insert or update meta data about the comment.
     *
     * Updates unread comment totals, bookmarks, and activity. Sends notifications.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $CommentID Unique ID for this comment.
     * @param int $Insert Used as a boolean for whether this is a new comment.
     * @param bool $CheckExisting Not used.
     * @param bool $IncUser Whether or not to just increment the user's comment count rather than recalculate it.
     */
    public function save2($CommentID, $Insert, $CheckExisting = true, $IncUser = false) {
        $Session = Gdn::session();
        $discussionModel = new DiscussionModel();

        // Load comment data
        $Fields = $this->getID($CommentID, DATASET_TYPE_ARRAY);

        // Clear any session stashes related to this discussion
        $DiscussionModel = new DiscussionModel();
        $DiscussionID = val('DiscussionID', $Fields);
        $Discussion = $DiscussionModel->getID($DiscussionID);
        $Session->setPublicStash('CommentForForeignID_'.getValue('ForeignID', $Discussion), null);

        // Make a quick check so that only the user making the comment can make the notification.
        // This check may be used in the future so should not be depended on later in the method.
        if (Gdn::controller()->deliveryType() === DELIVERY_TYPE_ALL && $Fields['InsertUserID'] != $Session->UserID) {
            return;
        }

        // Update the discussion author's CountUnreadDiscussions (ie.
        // the number of discussions created by the user that s/he has
        // unread messages in) if this comment was not added by the
        // discussion author.
        $this->updateUser($Fields['InsertUserID'], $IncUser && $Insert);

        // Mark the user as participated.
        $this->SQL->replace(
            'UserDiscussion',
            ['Participated' => 1],
            ['DiscussionID' => $DiscussionID, 'UserID' => val('InsertUserID', $Fields)]
        );

        if ($Insert) {
            // UPDATE COUNT AND LAST COMMENT ON CATEGORY TABLE
            if ($Discussion->CategoryID > 0) {
                CategoryModel::instance()->incrementLastComment($Fields);
            }

            // Prepare the notification queue.
            $ActivityModel = new ActivityModel();
            $HeadlineFormat = t('HeadlineFormat.Comment', '{ActivityUserID,user} commented on <a href="{Url,html}">{Data.Name,text}</a>');
            $Category = CategoryModel::categories($Discussion->CategoryID);
            $Activity = [
                'ActivityType' => 'Comment',
                'ActivityUserID' => $Fields['InsertUserID'],
                'HeadlineFormat' => $HeadlineFormat,
                'RecordType' => 'Comment',
                'RecordID' => $CommentID,
                'Route' => "/discussion/comment/$CommentID#Comment_$CommentID",
                'Data' => [
                    'Name' => $Discussion->Name,
                    'Category' => val('Name', $Category),
                ]
            ];

            // Allow simple fulltext notifications
            if (c('Vanilla.Activity.ShowCommentBody', false)) {
                $Activity['Story'] = val('Body', $Fields);
                $Activity['Format'] = val('Format', $Fields);
            }

            // Pass generic activity to events.
            $this->EventArguments['Activity'] = $Activity;


            // Notify users who have bookmarked the discussion.
            $BookmarkData = $DiscussionModel->getBookmarkUsers($DiscussionID);
            foreach ($BookmarkData->result() as $Bookmark) {
                // Check user can still see the discussion.
                if (!$discussionModel->canView($Discussion, $Bookmark->UserID)) {
                    continue;
                }

                $Activity['NotifyUserID'] = $Bookmark->UserID;
                $Activity['Data']['Reason'] = 'bookmark';
                $ActivityModel->queue($Activity, 'BookmarkComment', ['CheckRecord' => true]);
            }

            // Notify users who have participated in the discussion.
            $ParticipatedData = $DiscussionModel->getParticipatedUsers($DiscussionID);
            foreach ($ParticipatedData->result() as $UserRow) {
                if (!$discussionModel->canView($Discussion, $UserRow->UserID)) {
                    continue;
                }

                $Activity['NotifyUserID'] = $UserRow->UserID;
                $Activity['Data']['Reason'] = 'participated';
                $ActivityModel->queue($Activity, 'ParticipateComment', ['CheckRecord' => true]);
            }

            // Record user-comment activity.
            if ($Discussion != false) {
                $InsertUserID = val('InsertUserID', $Discussion);
                // Check user can still see the discussion.
                if ($discussionModel->canView($Discussion, $InsertUserID)) {
                    $Activity['NotifyUserID'] = $InsertUserID;
                    $Activity['Data']['Reason'] = 'mine';
                    $ActivityModel->queue($Activity, 'DiscussionComment');
                }
            }

            // Record advanced notifications.
            if ($Discussion !== false) {
                $Activity['Data']['Reason'] = 'advanced';
                $this->recordAdvancedNotications($ActivityModel, $Activity, $Discussion);
            }

            // Notify any users who were mentioned in the comment.
            $Usernames = getMentions($Fields['Body']);
            $userModel = Gdn::userModel();
            foreach ($Usernames as $i => $Username) {
                $User = $userModel->getByUsername($Username);
                if (!$User) {
                    unset($Usernames[$i]);
                    continue;
                }

                // Check user can still see the discussion.
                if (!$discussionModel->canView($Discussion, $User->UserID)) {
                    continue;
                }

                $HeadlineFormatBak = $Activity['HeadlineFormat'];
                $Activity['HeadlineFormat'] = t('HeadlineFormat.Mention', '{ActivityUserID,user} mentioned you in <a href="{Url,html}">{Data.Name,text}</a>');
                $Activity['NotifyUserID'] = $User->UserID;
                $Activity['Data']['Reason'] = 'mention';
                $ActivityModel->queue($Activity, 'Mention');
                $Activity['HeadlineFormat'] = $HeadlineFormatBak;
            }
            unset($Activity['Data']['Reason']);

            // Throw an event for users to add their own events.
            $this->EventArguments['Comment'] = $Fields;
            $this->EventArguments['Discussion'] = $Discussion;
            $this->EventArguments['NotifiedUsers'] = array_keys(ActivityModel::$Queue);
            $this->EventArguments['MentionedUsers'] = $Usernames;
            $this->EventArguments['ActivityModel'] = $ActivityModel;
            $this->fireEvent('BeforeNotification');

            // Send all notifications.
            $ActivityModel->saveQueue();
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
            ->whereIn('Name', ['Preferences.Email.NewComment.'.$category['CategoryID'], 'Preferences.Popup.NewComment.'.$category['CategoryID']])
            ->get('UserMeta')->resultArray();

        $notifyUsers = [];
        foreach ($data as $row) {
            if (!$row['Value']) {
                continue;
            }

            $userID = $row['UserID'];
            // Check user can still see the discussion.
            $discussionModel = new DiscussionModel();
            if (!$discussionModel->canView($discussion, $userID)) {
                continue;
            }

            $name = $row['Name'];
            if (strpos($name, '.Email.') !== false) {
                $notifyUsers[$userID]['Emailed'] = ActivityModel::SENT_PENDING;
            } elseif (strpos($name, '.Popup.') !== false) {
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
     * @since 2.0.0
     * @access public
     *
     * @param int $discussionID Unique ID of the discussion we are updating.
     * @param array $options
     *
     * @since 2.3 Added the $options parameter.
     */
    public function updateCommentCount($discussion, $options = []) {
        // Get the discussion.
        if (is_numeric($discussion)) {
            $this->options($options);
            $discussion = $this->SQL->getWhere('Discussion', ['DiscussionID' => $discussion])->firstRow(DATASET_TYPE_ARRAY);
        }
        $discussionID = $discussion['DiscussionID'];

        $this->fireEvent('BeforeUpdateCommentCountQuery');

        $this->options($options);
        $data = $this->SQL
            ->select('c.CommentID', 'min', 'FirstCommentID')
            ->select('c.CommentID', 'max', 'LastCommentID')
            ->select('c.DateInserted', 'max', 'DateLastComment')
            ->select('c.CommentID', 'count', 'CountComments')
            ->from('Comment c')
            ->where('c.DiscussionID', $discussionID)
            ->get()->firstRow(DATASET_TYPE_ARRAY);

        $this->EventArguments['Discussion'] =& $discussion;
        $this->EventArguments['Counts'] =& $data;
        $this->fireEvent('BeforeUpdateCommentCount');

        if ($discussion) {
            if ($data) {
                $this->SQL->update('Discussion');
                if (!$discussion['Sink'] && $data['DateLastComment']) {
                    $this->SQL->set('DateLastComment', $data['DateLastComment']);
                } elseif (!$data['DateLastComment'])
                    $this->SQL->set('DateLastComment', $discussion['DateInserted']);

                $this->SQL
                    ->set('FirstCommentID', $data['FirstCommentID'])
                    ->set('LastCommentID', $data['LastCommentID'])
                    ->set('CountComments', $data['CountComments'])
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
                    ->where('DiscussionID', $discussionID);
            }
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
        if ($inc) {
            // Just increment the comment count.
            $this->SQL
                ->update('User')
                ->set('CountComments', 'CountComments + 1', false)
                ->where('UserID', $userID)
                ->put();
        } else {
            // Retrieve a comment count
            $countComments = $this->SQL
                ->select('c.CommentID', 'count', 'CountComments')
                ->from('Comment c')
                ->where('c.InsertUserID', $userID)
                ->get()
                ->firstRow()
                ->CountComments;

            // Save to the attributes column of the user table for this user.
            Gdn::userModel()->setField($userID, 'CountComments', $countComments);
        }
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
     * @since 2.0.0
     * @access public
     *
     * @param int $commentID Unique ID of the comment to be deleted.
     * @param array $options Additional options for the delete.
     * @param bool Always returns TRUE.
     */
    public function deleteID($commentID, $options = []) {
        $this->EventArguments['CommentID'] = $commentID;

        $comment = $this->getID($commentID, DATASET_TYPE_ARRAY);
        if (!$comment) {
            return false;
        }
        $discussion = $this->SQL->getWhere('Discussion', ['DiscussionID' => $comment['DiscussionID']])->firstRow(DATASET_TYPE_ARRAY);

        // Decrement the UserDiscussion comment count if the user has seen this comment
        $offset = $this->getOffset($commentID);
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
        $this->SQL->delete('Comment', ['CommentID' => $commentID]);

        // Update the comment count
        $this->updateCommentCount($discussion, ['Slave' => false]);

        // Update the user's comment count
        $this->updateUser($comment['InsertUserID']);

        // Update the category.
        $categoryID = val('CategoryID', $discussion);
        $category = CategoryModel::categories($categoryID);
        if ($category && $category['LastCommentID'] == $commentID) {
            $categoryModel = new CategoryModel();
            $categoryModel->setRecentPost($category['CategoryID']);
        }
        // Decrement CountAllComments for category and its parents.
        CategoryModel::decrementAggregateCount($categoryID, CategoryModel::AGGREGATE_COMMENT);

        // Clear the page cache.
        $this->removePageCache($comment['DiscussionID']);
        return true;
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
}
