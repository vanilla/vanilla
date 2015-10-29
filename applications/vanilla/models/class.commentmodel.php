<?php
/**
 * Comment model
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Manages discussion comments data.
 */
class CommentModel extends VanillaModel {

    /** Threshold. */
    const COMMENT_THRESHOLD_SMALL = 1000;

    /** Threshold. */
    const COMMENT_THRESHOLD_LARGE = 50000;

    /** Trigger to recalculate counter. */
    const COUNT_RECALC_MOD = 50;

    /** @var array List of fields to order results by. */
    protected $_OrderBy = array(array('c.DateInserted', ''));

    /** @var array Wheres. */
    protected $_Where = array();

    /** @var bool */
    public $pageCache;

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct() {
        parent::__construct('Comment');
        $this->pageCache = Gdn::cache()->activeEnabled() && c('Properties.CommentModel.pageCache', false);
        $this->fireEvent('AfterConstruct');
    }

    /**
     *
     *
     * @param $Result
     * @param $PageWhere
     * @param $DiscussionID
     * @param $Page
     * @param null $Limit
     */
    public function cachePageWhere($Result, $PageWhere, $DiscussionID, $Page, $Limit = null) {
        if (!$this->pageCache || !empty($this->_Where) || $this->_OrderBy[0][0] != 'c.DateInserted' || $this->_OrderBy[0][1] == 'desc') {
            return;
        }

        if (count($Result) == 0) {
            return;
        }

        $ConfigLimit = c('Vanilla.Comments.PerPage', 30);

        if (!$Limit) {
            $Limit = $ConfigLimit;
        }

        if ($Limit != $ConfigLimit) {
            return;
        }

        if (is_array($PageWhere)) {
            $Curr = array_values($PageWhere);
        } else {
            $Curr = false;
        }

        $New = array(GetValueR('0.DateInserted', $Result));

        if (count($Result) >= $Limit) {
            $New[] = valr(($Limit - 1).'.DateInserted', $Result);
        }

        if ($Curr != $New) {
            trace('CommentModel->CachePageWhere()');

            $CacheKey = "Comment.Page.$Limit.$DiscussionID.$Page";
            Gdn::cache()->store($CacheKey, $New, array(Gdn_Cache::FEATURE_EXPIRY => 86400));

            trace($New, $CacheKey);
//         Gdn::controller()->setData('_PageCacheStore', array($CacheKey, $New));
        }
    }

    /**
     * Select the data for a single comment.
     *
     * @since 2.0.0
     * @access public
     *
     * @param bool $FireEvent Kludge to fix VanillaCommentReplies plugin.
     */
    public function commentQuery($FireEvent = true, $Join = true) {
        $this->SQL->select('c.*')
//         ->select('du.Name', '', 'DeleteName')
//         ->SelectCase('c.DeleteUserID', array('null' => '0', '' => '1'), 'Deleted')
//         ->join('User du', 'c.DeleteUserID = du.UserID', 'left');
            ->from('Comment c');

        if ($Join) {
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

        if ($FireEvent) {
            $this->fireEvent('AfterCommentQuery');
        }
    }

    /**
     * Get comments for a discussion.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $DiscussionID Which discussion to get comment from.
     * @param int $Limit Max number to get.
     * @param int $Offset Number to skip.
     * @return object SQL results.
     */
    public function get($DiscussionID, $Limit, $Offset = 0) {
        $this->CommentQuery(true, false);
        $this->EventArguments['DiscussionID'] =& $DiscussionID;
        $this->EventArguments['Limit'] =& $Limit;
        $this->EventArguments['Offset'] =& $Offset;
        $this->fireEvent('BeforeGet');

        $Page = PageNumber($Offset, $Limit);
        $PageWhere = $this->PageWhere($DiscussionID, $Page, $Limit);

        if ($PageWhere) {
            $this->SQL
                ->where('c.DiscussionID', $DiscussionID);

            $this->SQL->where($PageWhere)->limit($Limit + 10);
            $this->orderBy($this->SQL);
        } else {
            // Do an inner-query to force late-loading of comments.
            $Sql2 = clone $this->SQL;
            $Sql2->reset();
            $Sql2->select('CommentID')
                ->from('Comment c')
                ->where('c.DiscussionID', $DiscussionID, true, false)
                ->limit($Limit, $Offset);
            $this->orderBy($Sql2);
            $Select = $Sql2->GetSelect();

            $Px = $this->SQL->Database->DatabasePrefix;
            $this->SQL->Database->DatabasePrefix = '';

            $this->SQL->join("($Select) c2", "c.CommentID = c2.CommentID");
            $this->SQL->Database->DatabasePrefix = $Px;

//         $this->SQL->limit($Limit, $Offset);
        }

        $this->where($this->SQL);

        $Result = $this->SQL->get();

        Gdn::userModel()->joinUsers($Result, array('InsertUserID', 'UpdateUserID'));

        $this->setCalculatedFields($Result);

        $this->EventArguments['Comments'] =& $Result;
        $this->CachePageWhere($Result->result(), $PageWhere, $DiscussionID, $Page, $Limit);
        $this->fireEvent('AfterGet');

        return $Result;
    }

    /**
     * Get comments for a user.
     *
     * @since 2.0.17
     * @access public
     *
     * @param int $UserID Which user to get comments for.
     * @param int $Limit Max number to get.
     * @param int $Offset Number to skip.
     * @return object SQL results.
     */
    public function getByUser($UserID, $Limit, $Offset = 0) {
        // Get category permissions
        $Perms = DiscussionModel::CategoryPermissions();

        // Build main query
        $this->CommentQuery(true, false);
        $this->fireEvent('BeforeGet');
        $this->SQL
            ->select('d.Name', '', 'DiscussionName')
            ->join('Discussion d', 'c.DiscussionID = d.DiscussionID')
            ->where('c.InsertUserID', $UserID)
            ->orderBy('c.CommentID', 'desc')
            ->limit($Limit, $Offset);

        // Verify permissions (restricting by category if necessary)
        if ($Perms !== true) {
            $this->SQL
                ->join('Category ca', 'd.CategoryID = ca.CategoryID', 'left')
                ->whereIn('d.CategoryID', $Perms);
        }

        //$this->orderBy($this->SQL);

        $Data = $this->SQL->get();
        Gdn::userModel()->joinUsers($Data, array('InsertUserID', 'UpdateUserID'));

        return $Data;

    }

    /**
     *
     * Get comments for a user. This is an optimized version of CommentModel->GetByUser().
     *
     * @since 2.1
     * @access public
     *
     * @param int $UserID Which user to get comments for.
     * @param int $Limit Max number to get.
     * @param int $Offset Number to skip.
     * @param int $LastCommentID A hint for quicker paging.
     * @return Gdn_DataSet SQL results.
     */
    public function getByUser2($UserID, $Limit, $Offset, $LastCommentID = false) {
        $Perms = DiscussionModel::CategoryPermissions();

        if (is_array($Perms) && empty($Perms)) {
            return new Gdn_DataSet(array());
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
            ->where('c.InsertUserID', $UserID)
            ->orderBy('c.CommentID', 'desc');

        if ($LastCommentID) {
            // The last comment id from the last page was given and can be used as a hint to speed up the query.
            $this->SQL
                ->where('c.CommentID <', $LastCommentID)
                ->limit($Limit);
        } else {
            $this->SQL->limit($Limit, $Offset);
        }

        $Data = $this->SQL->get();


        $Result =& $Data->result();
        $this->LastCommentCount = $Data->numRows();
        if (count($Result) > 0) {
            $this->LastCommentID = $Result[count($Result) - 1]->CommentID;
        } else {
            $this->LastCommentID = null;
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

        Gdn::userModel()->joinUsers($Data, array('InsertUserID', 'UpdateUserID'));

        $this->EventArguments['Comments'] =& $Data;
        $this->fireEvent('AfterGet');

        return $Data;
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
    public function orderBy($Value = null) {
        if ($Value === null) {
            return $this->_OrderBy;
        }

        if (is_string($Value)) {
            $Value = array($Value);
        }

        if (is_array($Value)) {
            // Set the order of this object.
            $OrderBy = array();

            foreach ($Value as $Part) {
                if (StringEndsWith($Part, ' desc', true)) {
                    $OrderBy[] = array(substr($Part, 0, -5), 'desc');
                } elseif (StringEndsWith($Part, ' asc', true))
                    $OrderBy[] = array(substr($Part, 0, -4), 'asc');
                else {
                    $OrderBy[] = array($Part, 'asc');
                }
            }
            $this->_OrderBy = $OrderBy;
        } elseif (is_a($Value, 'Gdn_SQLDriver')) {
            // Set the order of the given sql.
            foreach ($this->_OrderBy as $Parts) {
                $Value->orderBy($Parts[0], $Parts[1]);
            }
        }
    }

    public function pageWhere($DiscussionID, $Page, $Limit) {
        if (!$this->pageCache || !empty($this->_Where) || $this->_OrderBy[0][0] != 'c.DateInserted' || $this->_OrderBy[0][1] == 'desc') {
            return false;
        }

        if ($Limit != c('Vanilla.Comments.PerPage', 30)) {
            return false;
        }

        $CacheKey = "Comment.Page.$Limit.$DiscussionID.$Page";
        $Value = Gdn::cache()->get($CacheKey);
        trace('CommentModel->PageWhere()');
        trace($Value, $CacheKey);
//      Gdn::controller()->setData('_PageCache', array($CacheKey, $Value));
        if ($Value === false) {
            return false;
        } elseif (is_array($Value)) {
            $Result = array('DateInserted >=' => $Value[0]);
            if (isset($Value[1])) {
                $Result['DateInserted <='] = $Value[1];
            }
            return $Result;
        }
        return false;
    }

    /**
     * Sets the UserComment Score value.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $CommentID Unique ID of comment we're setting the score for.
     * @param int $UserID Unique ID of user scoring the comment.
     * @param int $Score Score being assigned to the comment.
     * @return int New total score for the comment.
     */
    public function setUserScore($CommentID, $UserID, $Score) {
        // Insert or update the UserComment row
        $this->SQL->replace(
            'UserComment',
            array('Score' => $Score),
            array('CommentID' => $CommentID, 'UserID' => $UserID)
        );

        // Get the total new score
        $TotalScore = $this->SQL->select('Score', 'sum', 'TotalScore')
            ->from('UserComment')
            ->where('CommentID', $CommentID)
            ->get()
            ->firstRow()
            ->TotalScore;

        // Update the comment's cached version
        $this->SQL->update('Comment')
            ->set('Score', $TotalScore)
            ->where('CommentID', $CommentID)
            ->put();

        return $TotalScore;
    }

    /**
     * Gets the UserComment Score value for the specified user.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $CommentID Unique ID of comment we're getting the score for.
     * @param int $UserID Unique ID of user who scored the comment.
     * @return int Current score for the comment.
     */
    public function getUserScore($CommentID, $UserID) {
        $Data = $this->SQL->select('Score')
            ->from('UserComment')
            ->where('CommentID', $CommentID)
            ->where('UserID', $UserID)
            ->get()
            ->firstRow();

        return $Data ? $Data->Score : 0;
    }

    /**
     * Record the user's watch data.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object $Discussion Discussion being watched.
     * @param int $Limit Max number to get.
     * @param int $Offset Number to skip.
     * @param int $TotalComments Total in entire discussion (hard limit).
     */
    public function setWatch($Discussion, $Limit, $Offset, $TotalComments) {

        $NewComments = false;

        $Session = Gdn::session();
        if ($Session->UserID > 0) {
            // Max comments we could have seen
            $CountWatch = $Limit + $Offset;
            if ($CountWatch > $TotalComments) {
                $CountWatch = $TotalComments;
            }

            // This dicussion looks familiar...
            if (is_numeric($Discussion->CountCommentWatch)) {
                if ($CountWatch < $Discussion->CountCommentWatch) {
                    $CountWatch = $Discussion->CountCommentWatch;
                }

                if (isset($Discussion->DateLastViewed)) {
                    $NewComments |= Gdn_Format::toTimestamp($Discussion->DateLastComment) > Gdn_Format::toTimestamp($Discussion->DateLastViewed);
                }

                if ($TotalComments > $Discussion->CountCommentWatch) {
                    $NewComments |= true;
                }

                // Update the watch data.
                if ($NewComments) {
                    // Only update the watch if there are new comments.
                    $this->SQL->put(
                        'UserDiscussion',
                        array(
                            'CountComments' => $CountWatch,
                            'DateLastViewed' => Gdn_Format::toDateTime()
                        ),
                        array(
                            'UserID' => $Session->UserID,
                            'DiscussionID' => $Discussion->DiscussionID
                        )
                    );
                }

            } else {
                // Make sure the discussion isn't archived.
                $ArchiveDate = c('Vanilla.Archive.Date', false);
                if (!$ArchiveDate || (Gdn_Format::toTimestamp($Discussion->DateLastComment) > Gdn_Format::toTimestamp($ArchiveDate))) {
                    $NewComments = true;

                    // Insert watch data.
                    $this->SQL->Options('Ignore', true);
                    $this->SQL->insert(
                        'UserDiscussion',
                        array(
                            'UserID' => $Session->UserID,
                            'DiscussionID' => $Discussion->DiscussionID,
                            'CountComments' => $CountWatch,
                            'DateLastViewed' => Gdn_Format::toDateTime()
                        )
                    );
                }
            }

            /**
             * Fuzzy way of trying to automatically mark a cateogyr read again
             * if the user reads all the comments on the first few pages.
             */

            // If this discussion is in a category that has been marked read,
            // check if reading this thread causes it to be completely read again
            $CategoryID = val('CategoryID', $Discussion);
            if ($CategoryID) {
                $Category = CategoryModel::categories($CategoryID);
                if ($Category) {
                    $DateMarkedRead = val('DateMarkedRead', $Category);
                    if ($DateMarkedRead) {
                        // Fuzzy way of looking back about 2 pages into the past
                        $LookBackCount = c('Vanilla.Discussions.PerPage', 50) * 2;

                        // Find all discussions with content from after DateMarkedRead
                        $DiscussionModel = new DiscussionModel();
                        $Discussions = $DiscussionModel->get(0, 101, array(
                            'CategoryID' => $CategoryID,
                            'DateLastComment>' => $DateMarkedRead
                        ));
                        unset($DiscussionModel);

                        // Abort if we get back as many as we asked for, meaning a
                        // lot has happened.
                        $NumDiscussions = $Discussions->numRows();
                        if ($NumDiscussions <= $LookBackCount) {
                            // Loop over these and see if any are still unread
                            $MarkAsRead = true;
                            while ($Discussion = $Discussions->NextRow(DATASET_TYPE_ARRAY)) {
                                if ($Discussion['Read']) {
                                    continue;
                                }
                                $MarkAsRead = false;
                                break;
                            }

                            // Mark this category read if all the new content is read
                            if ($MarkAsRead) {
                                $CategoryModel = new CategoryModel();
                                $CategoryModel->SaveUserTree($CategoryID, array('DateMarkedRead' => Gdn_Format::toDateTime()));
                                unset($CategoryModel);
                            }

                        }
                    }
                }
            }

        }
    }

    /**
     * Count total comments in a discussion specified by ID.
     *
     * Events: BeforeGetCount
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $DiscussionID Unique ID of discussion we're counting comments from.
     * @return object SQL result.
     */
    public function getCount($DiscussionID) {
        $this->fireEvent('BeforeGetCount');

        if (!empty($this->_Where)) {
            return false;
        }

        return $this->SQL->select('CommentID', 'count', 'CountComments')
            ->from('Comment')
            ->where('DiscussionID', $DiscussionID)
            ->get()
            ->firstRow()
            ->CountComments;
    }

    /**
     * Count total comments in a discussion specified by $Where conditions.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $Where Conditions
     * @return object SQL result.
     */
    public function getCountWhere($Where = false) {
        if (is_array($Where)) {
            $this->SQL->where($Where);
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
     * @param int $CommentID Unique ID of the comment.
     * @param string $ResultType Format to return comment in.
     * @param array $Options options to pass to the database.
     * @return mixed SQL result in format specified by $ResultType.
     */
    public function getID($CommentID, $ResultType = DATASET_TYPE_OBJECT, $Options = array()) {
        $this->Options($Options);

        $this->CommentQuery(false); // FALSE supresses FireEvent
        $Comment = $this->SQL
            ->where('c.CommentID', $CommentID)
            ->get()
            ->firstRow($ResultType);

        if ($Comment) {
            $this->Calculate($Comment);
        }
        return $Comment;
    }

    /**
     * Get single comment by ID as SQL result data.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $CommentID Unique ID of the comment.
     * @return object SQL result.
     */
    public function getIDData($CommentID, $Options = array()) {
        $this->fireEvent('BeforeGetIDData');
        $this->CommentQuery(false); // FALSE supresses FireEvent
        $this->Options($Options);

        return $this->SQL
            ->where('c.CommentID', $CommentID)
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
     * @param int $DiscussionID Unique ID of the discusion.
     * @param int $LastCommentID Unique ID of the comment.
     * @return object SQL result.
     */
    public function getNew($DiscussionID, $LastCommentID) {
        $this->CommentQuery();
        $this->fireEvent('BeforeGetNew');
        $this->orderBy($this->SQL);
        $Comments = $this->SQL
            ->where('c.DiscussionID', $DiscussionID)
            ->where('c.CommentID >', $LastCommentID)
            ->get();

        $this->setCalculatedFields($Comments);
        return $Comments;
    }

    /**
     * Gets the offset of the specified comment in its related discussion.
     *
     * Events: BeforeGetOffset
     *
     * @since 2.0.0
     * @access public
     *
     * @param mixed $Comment Unique ID or or a comment object for which the offset is being defined.
     * @return object SQL result.
     */
    public function getOffset($Comment) {
        $this->fireEvent('BeforeGetOffset');

        if (is_numeric($Comment)) {
            $Comment = $this->getID($Comment);
        }

        $this->SQL
            ->select('c.CommentID', 'count', 'CountComments')
            ->from('Comment c')
            ->where('c.DiscussionID', val('DiscussionID', $Comment));

        $this->SQL->beginWhereGroup();

        // Figure out the where clause based on the sort.
        foreach ($this->_OrderBy as $Part) {
            //$Op = count($this->_OrderBy) == 1 || isset($PrevWhere) ? '=' : '';
            list($Expr, $Value) = $this->_WhereFromOrderBy($Part, $Comment, '');

            if (!isset($PrevWhere)) {
                $this->SQL->where($Expr, $Value);
            } else {
                $this->SQL->orOp();
                $this->SQL->beginWhereGroup();
                $this->SQL->orWhere($PrevWhere[0], $PrevWhere[1]);
                $this->SQL->where($Expr, $Value);
                $this->SQL->endWhereGroup();
            }

            $PrevWhere = $this->_WhereFromOrderBy($Part, $Comment, '==');
        }

        $this->SQL->endWhereGroup();

        return $this->SQL
            ->get()
            ->firstRow()
            ->CountComments;
    }

    public function getUnreadOffset($DiscussionID, $UserID = null) {
        if ($UserID == null) {
            $UserID = Gdn::session()->UserID;
        }
        if ($UserID == 0) {
            return 0;
        }

        // See of the user has read the discussion.
        $UserDiscussion = $this->SQL->getWhere('UserDiscussion', array('DiscussionID' => $DiscussionID, 'UserID' => $UserID))->firstRow(DATASET_TYPE_ARRAY);
        if (empty($UserDiscussion)) {
            return 0;
        }

        return $UserDiscussion['CountComments'];
    }

    /**
     * Builds Where statements for GetOffset method.
     *
     * @since 2.0.0
     * @access protected
     * @see CommentModel::GetOffset()
     *
     * @param array $Part Value from $this->_OrderBy.
     * @param object $Comment
     * @param string $Op Comparison operator.
     * @return array Expression and value.
     */
    protected function _WhereFromOrderBy($Part, $Comment, $Op = '') {
        if (!$Op || $Op == '=') {
            $Op = ($Part[1] == 'desc' ? '>' : '<').$Op;
        } elseif ($Op == '==')
            $Op = '=';

        $Expr = $Part[0].' '.$Op;
        if (preg_match('/c\.(\w*\b)/', $Part[0], $Matches)) {
            $Field = $Matches[1];
        } else {
            $Field = $Part[0];
        }
        $Value = val($Field, $Comment);
        if (!$Value) {
            $Value = 0;
        }

        return array($Expr, $Value);
    }

    /**
     * Insert or update core data about the comment.
     *
     * Events: BeforeSaveComment, AfterSaveComment.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $FormPostValues Data from the form model.
     * @return int $CommentID
     */
    public function save($FormPostValues) {
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
        $MinCommentLength = c('Vanilla.Comment.MinLength');
        if ($MinCommentLength && is_numeric($MinCommentLength)) {
            $this->Validation->SetSchemaProperty('Body', 'MinLength', $MinCommentLength);
            $this->Validation->addRule('MinTextLength', 'function:ValidateMinTextLength');
            $this->Validation->applyRule('Body', 'MinTextLength');
        }

        // Validate $CommentID and whether this is an insert
        $CommentID = arrayValue('CommentID', $FormPostValues);
        $CommentID = is_numeric($CommentID) && $CommentID > 0 ? $CommentID : false;
        $Insert = $CommentID === false;
        if ($Insert) {
            $this->AddInsertFields($FormPostValues);
        } else {
            $this->AddUpdateFields($FormPostValues);
        }

        // Prep and fire event
        $this->EventArguments['FormPostValues'] = &$FormPostValues;
        $this->EventArguments['CommentID'] = $CommentID;
        $this->fireEvent('BeforeSaveComment');

        // Validate the form posted values
        if ($this->validate($FormPostValues, $Insert)) {
            // If the post is new and it validates, check for spam
            if (!$Insert || !$this->CheckForSpam('Comment')) {
                $Fields = $this->Validation->SchemaValidationFields();
                $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);

                if ($Insert === false) {
                    // Log the save.
                    LogModel::LogChange('Edit', 'Comment', array_merge($Fields, array('CommentID' => $CommentID)));
                    // Save the new value.
                    $this->SerializeRow($Fields);
                    $this->SQL->put($this->Name, $Fields, array('CommentID' => $CommentID));
                } else {
                    // Make sure that the comments get formatted in the method defined by Garden.
                    if (!val('Format', $Fields) || c('Garden.ForceInputFormatter')) {
                        $Fields['Format'] = Gdn::config('Garden.InputFormatter', '');
                    }

                    // Check for spam
                    $Spam = SpamModel::IsSpam('Comment', $Fields);
                    if ($Spam) {
                        return SPAM;
                    }

                    // Check for approval
                    $ApprovalRequired = CheckRestriction('Vanilla.Approval.Require');
                    if ($ApprovalRequired && !val('Verified', Gdn::session()->User)) {
                        $DiscussionModel = new DiscussionModel();
                        $Discussion = $DiscussionModel->getID(val('DiscussionID', $Fields));
                        $Fields['CategoryID'] = val('CategoryID', $Discussion);
                        LogModel::insert('Pending', 'Comment', $Fields);
                        return UNAPPROVED;
                    }

                    // Create comment.
                    $this->SerializeRow($Fields);
                    $CommentID = $this->SQL->insert($this->Name, $Fields);
                }
                if ($CommentID) {
                    $this->EventArguments['CommentID'] = $CommentID;
                    $this->EventArguments['Insert'] = $Insert;

                    // IsNewDiscussion is passed when the first comment for new discussions are created.
                    $this->EventArguments['IsNewDiscussion'] = val('IsNewDiscussion', $FormPostValues);
                    $this->fireEvent('AfterSaveComment');
                }
            }
        }

        // Update discussion's comment count
        $DiscussionID = val('DiscussionID', $FormPostValues);
        $this->UpdateCommentCount($DiscussionID, array('Slave' => false));

        return $CommentID;
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
        $UserModel = Gdn::userModel();

        // Load comment data
        $Fields = $this->getID($CommentID, DATASET_TYPE_ARRAY);

        // Clear any session stashes related to this discussion
        $DiscussionModel = new DiscussionModel();
        $DiscussionID = val('DiscussionID', $Fields);
        $Discussion = $DiscussionModel->getID($DiscussionID);
        $Session->setPublicStash('CommentForForeignID_'.GetValue('ForeignID', $Discussion), null);

        // Make a quick check so that only the user making the comment can make the notification.
        // This check may be used in the future so should not be depended on later in the method.
        if (Gdn::controller()->deliveryType() === DELIVERY_TYPE_ALL && $Fields['InsertUserID'] != $Session->UserID) {
            return;
        }

        // Update the discussion author's CountUnreadDiscussions (ie.
        // the number of discussions created by the user that s/he has
        // unread messages in) if this comment was not added by the
        // discussion author.
        $this->UpdateUser($Fields['InsertUserID'], $IncUser && $Insert);

        // Mark the user as participated.
        $this->SQL->replace(
            'UserDiscussion',
            array('Participated' => 1),
            array('DiscussionID' => $DiscussionID, 'UserID' => val('InsertUserID', $Fields))
        );

        if ($Insert) {
            // UPDATE COUNT AND LAST COMMENT ON CATEGORY TABLE
            if ($Discussion->CategoryID > 0) {
                $Category = CategoryModel::categories($Discussion->CategoryID);

                if ($Category) {
                    $CountComments = val('CountComments', $Category, 0) + 1;

                    if ($CountComments < self::COMMENT_THRESHOLD_SMALL || ($CountComments < self::COMMENT_THRESHOLD_LARGE && $CountComments % self::COUNT_RECALC_MOD == 0)) {
                        $CountComments = $this->SQL
                            ->select('CountComments', 'sum', 'CountComments')
                            ->from('Discussion')
                            ->where('CategoryID', $Discussion->CategoryID)
                            ->get()
                            ->firstRow()
                            ->CountComments;
                    }
                }
                $CategoryModel = new CategoryModel();

                $CategoryModel->setField($Discussion->CategoryID, array(
                    'LastDiscussionID' => $DiscussionID,
                    'LastCommentID' => $CommentID,
                    'CountComments' => $CountComments,
                    'LastDateInserted' => $Fields['DateInserted']
                ));

                // Update the cache.
                $CategoryCache = array(
                    'LastTitle' => $Discussion->Name, // kluge so JoinUsers doesn't wipe this out.
                    'LastUserID' => $Fields['InsertUserID'],
                    'LastUrl' => DiscussionUrl($Discussion).'#latest'
                );
                CategoryModel::SetCache($Discussion->CategoryID, $CategoryCache);
            }

            // Prepare the notification queue.
            $ActivityModel = new ActivityModel();
            $HeadlineFormat = t('HeadlineFormat.Comment', '{ActivityUserID,user} commented on <a href="{Url,html}">{Data.Name,text}</a>');
            $Category = CategoryModel::categories($Discussion->CategoryID);
            $Activity = array(
                'ActivityType' => 'Comment',
                'ActivityUserID' => $Fields['InsertUserID'],
                'HeadlineFormat' => $HeadlineFormat,
                'RecordType' => 'Comment',
                'RecordID' => $CommentID,
                'Route' => "/discussion/comment/$CommentID#Comment_$CommentID",
                'Data' => array(
                    'Name' => $Discussion->Name,
                    'Category' => val('Name', $Category),
                )
            );

            // Allow simple fulltext notifications
            if (c('Vanilla.Activity.ShowCommentBody', false)) {
                $Activity['Story'] = val('Body', $Fields);
                $Activity['Format'] = val('Format', $Fields);
            }

            // Pass generic activity to events.
            $this->EventArguments['Activity'] = $Activity;


            // Notify users who have bookmarked the discussion.
            $BookmarkData = $DiscussionModel->GetBookmarkUsers($DiscussionID);
            foreach ($BookmarkData->result() as $Bookmark) {
                // Check user can still see the discussion.
                if (!$UserModel->GetCategoryViewPermission($Bookmark->UserID, $Discussion->CategoryID)) {
                    continue;
                }

                $Activity['NotifyUserID'] = $Bookmark->UserID;
                $Activity['Data']['Reason'] = 'bookmark';
                $ActivityModel->Queue($Activity, 'BookmarkComment', array('CheckRecord' => true));
            }

            // Notify users who have participated in the discussion.
            $ParticipatedData = $DiscussionModel->GetParticipatedUsers($DiscussionID);
            foreach ($ParticipatedData->result() as $UserRow) {
                if (!$UserModel->GetCategoryViewPermission($UserRow->UserID, $Discussion->CategoryID)) {
                    continue;
                }

                $Activity['NotifyUserID'] = $UserRow->UserID;
                $Activity['Data']['Reason'] = 'participated';
                $ActivityModel->Queue($Activity, 'ParticipateComment', array('CheckRecord' => true));
            }

            // Record user-comment activity.
            if ($Discussion != false) {
                $InsertUserID = val('InsertUserID', $Discussion);
                // Check user can still see the discussion.
                if ($UserModel->GetCategoryViewPermission($InsertUserID, $Discussion->CategoryID)) {
                    $Activity['NotifyUserID'] = $InsertUserID;
                    $Activity['Data']['Reason'] = 'mine';
                    $ActivityModel->Queue($Activity, 'DiscussionComment');
                }
            }

            // Record advanced notifications.
            if ($Discussion !== false) {
                $Activity['Data']['Reason'] = 'advanced';
                $this->RecordAdvancedNotications($ActivityModel, $Activity, $Discussion);
            }

            // Notify any users who were mentioned in the comment.
            $Usernames = GetMentions($Fields['Body']);
            foreach ($Usernames as $i => $Username) {
                $User = $UserModel->GetByUsername($Username);
                if (!$User) {
                    unset($Usernames[$i]);
                    continue;
                }

                // Check user can still see the discussion.
                if (!$UserModel->GetCategoryViewPermission($User->UserID, $Discussion->CategoryID)) {
                    continue;
                }

                $HeadlineFormatBak = $Activity['HeadlineFormat'];
                $Activity['HeadlineFormat'] = t('HeadlineFormat.Mention', '{ActivityUserID,user} mentioned you in <a href="{Url,html}">{Data.Name,text}</a>');
                $Activity['NotifyUserID'] = $User->UserID;
                $Activity['Data']['Reason'] = 'mention';
                $ActivityModel->Queue($Activity, 'Mention');
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
            $ActivityModel->SaveQueue();
        }
    }

    /**
     * Record advanced notifications for users.
     *
     * @param ActivityModel $ActivityModel
     * @param array $Activity
     * @param array $Discussion
     * @param array $NotifiedUsers
     */
    public function recordAdvancedNotications($ActivityModel, $Activity, $Discussion) {
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
            ->whereIn('Name', array('Preferences.Email.NewComment.'.$Category['CategoryID'], 'Preferences.Popup.NewComment.'.$Category['CategoryID']))
            ->get('UserMeta')->resultArray();

        $NotifyUsers = array();
        foreach ($Data as $Row) {
            if (!$Row['Value']) {
                continue;
            }

            $UserID = $Row['UserID'];
            // Check user can still see the discussion.
            if (!Gdn::userModel()->GetCategoryViewPermission($UserID, $Category['CategoryID'])) {
                continue;
            }

            $Name = $Row['Name'];
            if (strpos($Name, '.Email.') !== false) {
                $NotifyUsers[$UserID]['Emailed'] = ActivityModel::SENT_PENDING;
            } elseif (strpos($Name, '.Popup.') !== false) {
                $NotifyUsers[$UserID]['Notified'] = ActivityModel::SENT_PENDING;
            }
        }

        foreach ($NotifyUsers as $UserID => $Prefs) {
            $Activity['NotifyUserID'] = $UserID;
            $Activity['Emailed'] = val('Emailed', $Prefs, false);
            $Activity['Notified'] = val('Notified', $Prefs, false);
            $ActivityModel->Queue($Activity);
        }
    }

    public function removePageCache($DiscussionID, $From = 1) {
        if (!$this->pageCache) {
            return;
        }

        $CountComments = $this->SQL->getWhere('Discussion', array('DiscussionID' => $DiscussionID))->value('CountComments');
        $Limit = c('Vanilla.Comments.PerPage', 30);
        $PageCount = PageNumber($CountComments, $Limit) + 1;

        for ($Page = $From; $Page <= $PageCount; $Page++) {
            $CacheKey = "Comment.Page.$Limit.$DiscussionID.$Page";
            Gdn::cache()->Remove($CacheKey);
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
     * @param int $DiscussionID Unique ID of the discussion we are updating.
     * @param array $Options
     *
     * @since 2.3 Added the $Options parameter.
     */
    public function updateCommentCount($Discussion, $Options = array()) {
        // Get the discussion.
        if (is_numeric($Discussion)) {
            $this->Options($Options);
            $Discussion = $this->SQL->getWhere('Discussion', array('DiscussionID' => $Discussion))->firstRow(DATASET_TYPE_ARRAY);
        }
        $DiscussionID = $Discussion['DiscussionID'];

        $this->fireEvent('BeforeUpdateCommentCountQuery');

        $this->Options($Options);
        $Data = $this->SQL
            ->select('c.CommentID', 'min', 'FirstCommentID')
            ->select('c.CommentID', 'max', 'LastCommentID')
            ->select('c.DateInserted', 'max', 'DateLastComment')
            ->select('c.CommentID', 'count', 'CountComments')
            ->from('Comment c')
            ->where('c.DiscussionID', $DiscussionID)
            ->get()->firstRow(DATASET_TYPE_ARRAY);

        $this->EventArguments['Discussion'] =& $Discussion;
        $this->EventArguments['Counts'] =& $Data;
        $this->fireEvent('BeforeUpdateCommentCount');

        if ($Discussion) {
            if ($Data) {
                $this->SQL->update('Discussion');
                if (!$Discussion['Sink'] && $Data['DateLastComment']) {
                    $this->SQL->set('DateLastComment', $Data['DateLastComment']);
                } elseif (!$Data['DateLastComment'])
                    $this->SQL->set('DateLastComment', $Discussion['DateInserted']);

                $this->SQL
                    ->set('FirstCommentID', $Data['FirstCommentID'])
                    ->set('LastCommentID', $Data['LastCommentID'])
                    ->set('CountComments', $Data['CountComments'])
                    ->where('DiscussionID', $DiscussionID)
                    ->put();

                // Update the last comment's user ID.
                $this->SQL
                    ->update('Discussion d')
                    ->update('Comment c')
                    ->set('d.LastCommentUserID', 'c.InsertUserID', false)
                    ->where('d.DiscussionID', $DiscussionID)
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
                    ->where('DiscussionID', $DiscussionID);
            }
        }
    }

    /**
     * Update UserDiscussion so users don't have incorrect counts.
     *
     * @since 2.0.18
     * @access public
     *
     * @param int $DiscussionID Unique ID of the discussion we are updating.
     */
    public function updateUserCommentCounts($DiscussionID) {
        $Sql = "update ".$this->Database->DatabasePrefix."UserDiscussion ud
         set CountComments = (
            select count(c.CommentID)+1
            from ".$this->Database->DatabasePrefix."Comment c
            where c.DateInserted < ud.DateLastViewed
         )
         where DiscussionID = $DiscussionID";
        $this->SQL->query($Sql);
    }

    /**
     * Update user's total comment count.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $UserID Unique ID of the user to be updated.
     */
    public function updateUser($UserID, $Inc = false) {
        if ($Inc) {
            // Just increment the comment count.
            $this->SQL
                ->update('User')
                ->set('CountComments', 'CountComments + 1', false)
                ->where('UserID', $UserID)
                ->put();
        } else {
            // Retrieve a comment count
            $CountComments = $this->SQL
                ->select('c.CommentID', 'count', 'CountComments')
                ->from('Comment c')
                ->where('c.InsertUserID', $UserID)
                ->get()
                ->firstRow()
                ->CountComments;

            // Save to the attributes column of the user table for this user.
            Gdn::userModel()->setField($UserID, 'CountComments', $CountComments);
        }
    }

    /**
     * Delete a comment.
     *
     * This is a hard delete that completely removes it from the database.
     * Events: DeleteComment.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $CommentID Unique ID of the comment to be deleted.
     * @param array $Options Additional options for the delete.
     * @param bool Always returns TRUE.
     */
    public function delete($CommentID, $Options = array()) {
        $this->EventArguments['CommentID'] = $CommentID;

        $Comment = $this->getID($CommentID, DATASET_TYPE_ARRAY);
        if (!$Comment) {
            return false;
        }
        $Discussion = $this->SQL->getWhere('Discussion', array('DiscussionID' => $Comment['DiscussionID']))->firstRow(DATASET_TYPE_ARRAY);

        // Decrement the UserDiscussion comment count if the user has seen this comment
        $Offset = $this->GetOffset($CommentID);
        $this->SQL->update('UserDiscussion')
            ->set('CountComments', 'CountComments - 1', false)
            ->where('DiscussionID', $Comment['DiscussionID'])
            ->where('CountComments >', $Offset)
            ->put();

        $this->EventArguments['Discussion'] = $Discussion;
        $this->fireEvent('DeleteComment');

        // Log the deletion.
        $Log = val('Log', $Options, 'Delete');
        LogModel::insert($Log, 'Comment', $Comment, val('LogOptions', $Options, array()));

        // Delete the comment.
        $this->SQL->delete('Comment', array('CommentID' => $CommentID));

        // Update the comment count
        $this->UpdateCommentCount($Discussion, array('Slave' => false));

        // Update the user's comment count
        $this->UpdateUser($Comment['InsertUserID']);

        // Update the category.
        $Category = CategoryModel::categories(val('CategoryID', $Discussion));
        if ($Category && $Category['LastCommentID'] == $CommentID) {
            $CategoryModel = new CategoryModel();
            $CategoryModel->SetRecentPost($Category['CategoryID']);
        }

        // Clear the page cache.
        $this->RemovePageCache($Comment['DiscussionID']);
        return true;
    }

    /**
     * Modifies comment data before it is returned.
     *
     * @since 2.1a32
     * @access public
     *
     * @param object $Data SQL result.
     */
    public function setCalculatedFields(&$Data) {
        $Result = &$Data->result();
        foreach ($Result as &$Comment) {
            $this->Calculate($Comment);
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
    public function calculate($Comment) {

        // Do nothing yet.
        if ($Attributes = val('Attributes', $Comment)) {
            setValue('Attributes', $Comment, unserialize($Attributes));
        }

        $this->EventArguments['Comment'] = $Comment;
        $this->fireEvent('SetCalculatedFields');
    }

    public function where($Value = null) {
        if ($Value === null) {
            return $this->_Where;
        } elseif (!$Value)
            $this->_Where = array();
        elseif (is_a($Value, 'Gdn_SQLDriver')) {
            if (!empty($this->_Where)) {
                $Value->where($this->_Where);
            }
        } else {
            $this->_Where = $Value;
        }
    }
}
