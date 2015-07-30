<?php
/**
 * Discussion model
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Manages discussions data.
 */
class DiscussionModel extends VanillaModel {

    /** Cache key. */
    const CACHE_DISCUSSIONVIEWS = 'discussion.%s.countviews';

    /** @var array */
    protected static $_CategoryPermissions = null;

    /** @var array */
    protected static $_DiscussionTypes = null;

    /** @var bool */
    public $Watching = false;

    /** @var array Column names to allow sorting by. */
    protected static $AllowedSortFields = array('d.DateLastComment', 'd.DateInserted', 'd.DiscussionID');

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


            Gdn::pluginManager()->EventArguments['Types'] =& $DiscussionTypes;
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
                ->select('d.Announce', '', 'IsAnnounce');
        }

        $this->AddArchiveWhere($this->SQL);

        if ($Offset !== false && $Limit !== false) {
            $this->SQL->limit($Limit, $Offset);
        }

        // Get preferred sort order
        $SortField = self::GetSortField();

        $this->EventArguments['SortField'] = &$SortField;
        $this->EventArguments['SortDirection'] = c('Vanilla.Discussions.SortDirection', 'desc');
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

        // Whitelist sorting options
        if (!in_array($SortField, array('d.DiscussionID', 'd.DateLastComment', 'd.DateInserted'))) {
            trigger_error("You are sorting discussions by a possibly sub-optimal column.", E_USER_NOTICE);
        }

        $SortDirection = $this->EventArguments['SortDirection'];
        if ($SortDirection != 'asc') {
            $SortDirection = 'desc';
        }

        $this->SQL->orderBy($SortField, $SortDirection);

        // Set range and fetch
        $Data = $this->SQL->get();

        // If not looking at discussions filtered by bookmarks or user, filter announcements out.
        if (!$IncludeAnnouncements) {
            if (!isset($Wheres['w.Bookmarked']) && !isset($Wheres['d.InsertUserID'])) {
                $this->RemoveAnnouncements($Data);
            }
        }

        // Join in the users.
        Gdn::userModel()->joinUsers($Data, array('FirstUserID', 'LastUserID'));
        CategoryModel::JoinCategories($Data);

        // Change discussions returned based on additional criteria
        $this->AddDiscussionColumns($Data);

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->AddDenormalizedViews($Data);
        }

        // Prep and fire event
        $this->EventArguments['Data'] = $Data;
        $this->fireEvent('AfterAddColumns');

        return $Data;
    }

    public function getWhere($Where = array(), $Offset = 0, $Limit = false) {
        if (!$Limit) {
            $Limit = c('Vanilla.Discussions.PerPage', 30);
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

        // Get preferred sort order
        $SortField = self::GetSortField();

        $this->EventArguments['SortField'] = &$SortField;
        $this->EventArguments['SortDirection'] = c('Vanilla.Discussions.SortDirection', 'desc');
        $this->EventArguments['Wheres'] =& $Where;
        $this->fireEvent('BeforeGet');

        // Whitelist sorting options
        if (!in_array($SortField, self::AllowedSortFields())) {
            $SortField = 'd.DateLastComment';
        }

        $SortDirection = $this->EventArguments['SortDirection'];
        if ($SortDirection != 'asc') {
            $SortDirection = 'desc';
        }

        // Build up the base query. Self-join for optimization.
        $Sql->select('d2.*')
            ->from('Discussion d')
            ->join('Discussion d2', 'd.DiscussionID = d2.DiscussionID')
            ->orderBy($SortField, $SortDirection)
            ->limit($Limit, $Offset);

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
        foreach ($Where as $Key => $Value) {
            if (strpos($Key, '.') === false) {
                $Where['d.'.$Key] = $Value;
                unset($Where[$Key]);
            }
        }

        $Sql->where($Where);

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

        $this->SQL->orderBy($SortField, $SortDirection);

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
        $Discussion->Attributes = @unserialize($Discussion->Attributes);
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

        $this->EventArguments['Discussion'] = $Discussion;
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
        } elseif (is_array($CategoryID) || $CategoryID > 0) {
            $this->SQL->where('d.CategoryID', $CategoryID);
        }

        $AnnouncementIDs = $this->SQL->get()->resultArray();
        $AnnouncementIDs = consolidateArrayValuesByKey($AnnouncementIDs, 'DiscussionID');

        // Short circuit querying when there are no announcements.
        if (count($AnnouncementIDs) == 0) {
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
//      if (is_array($Wheres))
//         $this->SQL->where($Wheres);
//
//      $this->SQL
//         ->where('d.Announce', '1');

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

        $this->SQL
            ->orderBy(self::GetSortField(), 'desc')
            ->limit($Limit, $Offset);

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
        $PageInfo = FetchPageInfo($Url, 3, $ThrowError);

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

        // Join in the users.
        $Discussion = array($Discussion);
        Gdn::userModel()->joinUsers($Discussion, array('LastUserID', 'InsertUserID'));
        $Discussion = $Discussion[0];

        $this->Calculate($Discussion);

        if (c('Vanilla.Views.Denormalize', false)) {
            $this->AddDenormalizedViews($Discussion);
        }

        return $Discussion;
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
        $SortField = c('Vanilla.Discussions.SortField', 'd.DateLastComment');
        if (c('Vanilla.Discussions.UserSortField')) {
            $SortField = Gdn::session()->GetPreference('Discussions.SortField', $SortField);
        }

        return $SortField;
    }

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
        $this->EventArguments['SetField'] = $Property;

        parent::SetField($RowID, $Property, $Value);
        $this->fireEvent('AfterSetField');
    }

    /**
     * Inserts or updates the discussion via form values.
     *
     * Events: BeforeSaveDiscussion, AfterSaveDiscussion.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $FormPostValues Data sent from the form model.
     * @return int $DiscussionID Unique ID of the discussion.
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

        // Validate category permissions.
        $CategoryID = val('CategoryID', $FormPostValues);
        if ($CategoryID > 0) {
            $Category = CategoryModel::categories($CategoryID);
            if ($Category && !$Session->checkPermission('Vanilla.Discussions.Add', true, 'Category', val('PermissionCategoryID', $Category))) {
                $this->Validation->addValidationResult('CategoryID', 'You do not have permission to post in this category');
            }
        }

        // Get the DiscussionID from the form so we know if we are inserting or updating.
        $DiscussionID = arrayValue('DiscussionID', $FormPostValues, '');

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
                $FormPostValues['CategoryID'] = val('CategoryID', CategoryModel::DefaultCategory(), -1);
            }

            $this->AddInsertFields($FormPostValues);

            // The UpdateUserID used to be required. Just add it if it still is.
            if (!$this->Schema->GetProperty('UpdateUserID', 'AllowNull', true)) {
                $FormPostValues['UpdateUserID'] = $FormPostValues['InsertUserID'];
            }

            // $FormPostValues['LastCommentUserID'] = $Session->UserID;
            $FormPostValues['DateLastComment'] = $FormPostValues['DateInserted'];
        } else {
            // Add the update fields.
            $this->AddUpdateFields($FormPostValues);
        }

        // Set checkbox values to zero if they were unchecked
        if (ArrayValue('Announce', $FormPostValues, '') === false) {
            $FormPostValues['Announce'] = 0;
        }

        if (ArrayValue('Closed', $FormPostValues, '') === false) {
            $FormPostValues['Closed'] = 0;
        }

        if (ArrayValue('Sink', $FormPostValues, '') === false) {
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
            if (!$Insert || !$this->CheckForSpam('Discussion')) {
                // Get all fields on the form that relate to the schema
                $Fields = $this->Validation->SchemaValidationFields();

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

                    // Clear the cache if necessary.
                    $CacheKeys = array();
                    if (val('Announce', $Stored) != val('Announce', $Fields)) {
                        $CacheKeys[] = $this->GetAnnouncementCacheKey();
                        $CacheKeys[] = $this->GetAnnouncementCacheKey(val('CategoryID', $Stored));
                    }
                    if (val('CategoryID', $Stored) != val('CategoryID', $Fields)) {
                        $CacheKeys[] = $this->GetAnnouncementCacheKey(val('CategoryID', $Fields));
                    }
                    foreach ($CacheKeys as $CacheKey) {
                        Gdn::cache()->Remove($CacheKey);
                    }

                    self::SerializeRow($Fields);
                    $this->SQL->put($this->Name, $Fields, array($this->PrimaryKey => $DiscussionID));

                    setValue('DiscussionID', $Fields, $DiscussionID);
                    LogModel::LogChange('Edit', 'Discussion', (array)$Fields, $Stored);

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

                    // Check for spam.
                    $Spam = SpamModel::IsSpam('Discussion', $Fields);
                    if ($Spam) {
                        return SPAM;
                    }

                    // Check for approval
                    $ApprovalRequired = CheckRestriction('Vanilla.Approval.Require');
                    if ($ApprovalRequired && !val('Verified', Gdn::session()->User)) {
                        LogModel::insert('Pending', 'Discussion', $Fields);
                        return UNAPPROVED;
                    }

                    // Create discussion
                    $this->SerializeRow($Fields);
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
                        CategoryModel::SetCache($Fields['CategoryID'], $CategoryCache);

                        // Clear the cache if necessary.
                        if (val('Announce', $Fields)) {
                            Gdn::cache()->Remove($this->GetAnnouncementCacheKey(val('CategoryID', $Fields)));
                        }
                    }

                    // Update the user's discussion count.
                    $InsertUser = Gdn::userModel()->getID($Fields['InsertUserID']);
                    $this->UpdateUserDiscussionCount($Fields['InsertUserID'], val('CountDiscussions', $InsertUser, 0) > 100);

                    // Mark the user as participated.
                    $this->SQL->replace(
                        'UserDiscussion',
                        array('Participated' => 1),
                        array('DiscussionID' => $DiscussionID, 'UserID' => val('InsertUserID', $Fields))
                    );

                    // Assign the new DiscussionID to the comment before saving.
                    $FormPostValues['IsNewDiscussion'] = true;
                    $FormPostValues['DiscussionID'] = $DiscussionID;

                    // Do data prep.
                    $DiscussionName = arrayValue('Name', $Fields, '');
                    $Story = arrayValue('Body', $Fields, '');
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
                    $Usernames = GetMentions($DiscussionName.' '.$Story);
                    $Usernames = array_unique($Usernames);

                    // Use our generic Activity for events, not mentions
                    $this->EventArguments['Activity'] = $Activity;

                    // Notify everyone that has advanced notifications.
                    if (!c('Vanilla.QueueNotifications')) {
                        try {
                            $Fields['DiscussionID'] = $DiscussionID;
                            $this->NotifyNewDiscussion($Fields, $ActivityModel, $Activity);
                        } catch (Exception $Ex) {
                            throw $Ex;
                        }
                    }

                    // Notifications for mentions
                    foreach ($Usernames as $Username) {
                        $User = $UserModel->GetByUsername($Username);
                        if (!$User) {
                            continue;
                        }

                        // Check user can still see the discussion.
                        if (!$UserModel->GetCategoryViewPermission($User->UserID, val('CategoryID', $Fields))) {
                            continue;
                        }

                        $Activity['HeadlineFormat'] = t('HeadlineFormat.Mention', '{ActivityUserID,user} mentioned you in <a href="{Url,html}">{Data.Name,text}</a>');

                        $Activity['NotifyUserID'] = val('UserID', $User);
                        $ActivityModel->Queue($Activity, 'Mention');
                    }

                    // Throw an event for users to add their own events.
                    $this->EventArguments['Discussion'] = $Fields;
                    $this->EventArguments['NotifiedUsers'] = $NotifiedUsers;
                    $this->EventArguments['MentionedUsers'] = $Usernames;
                    $this->EventArguments['ActivityModel'] = $ActivityModel;
                    $this->fireEvent('BeforeNotification');

                    // Send all notifications.
                    $ActivityModel->SaveQueue();
                }

                // Get CategoryID of this discussion

                $Discussion = $this->getID($DiscussionID, DATASET_TYPE_OBJECT);
                $CategoryID = val('CategoryID', $Discussion, false);

                // Update discussion counter for affected categories.
                if ($Insert || $StoredCategoryID) {
                    $this->IncrementNewDiscussion($Discussion);
                }

                if ($StoredCategoryID) {
                    $this->UpdateDiscussionCount($StoredCategoryID);
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
     * Delete a discussion. Update and/or delete all related data.
     *
     * Events: DeleteDiscussion.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $DiscussionID Unique ID of discussion to delete.
     * @return bool Always returns TRUE.
     */
    public function delete($DiscussionID, $Options = array()) {
        // Retrieve the users who have bookmarked this discussion.
        $BookmarkData = $this->GetBookmarkUsers($DiscussionID);

        $Data = $this->SQL
            ->select('*')
            ->from('Discussion')
            ->where('DiscussionID', $DiscussionID)
            ->get()->firstRow(DATASET_TYPE_ARRAY);

        $UserID = false;
        $CategoryID = false;
        if ($Data) {
            $UserID = $Data['InsertUserID'];
            $CategoryID = $Data['CategoryID'];
        }

        // Prep and fire event
        $this->EventArguments['DiscussionID'] = $DiscussionID;
        $this->EventArguments['Discussion'] = $Data;
        $this->fireEvent('DeleteDiscussion');

        // Execute deletion of discussion and related bits
        $this->SQL->delete('Draft', array('DiscussionID' => $DiscussionID));

        $Log = val('Log', $Options, true);
        $LogOptions = val('LogOptions', $Options, array());
        if ($Log === true) {
            $Log = 'Delete';
        }

        LogModel::BeginTransaction();

        // Log all of the comment deletes.
        $Comments = $this->SQL->getWhere('Comment', array('DiscussionID' => $DiscussionID))->resultArray();

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

        $this->SQL->delete('Comment', array('DiscussionID' => $DiscussionID));
        $this->SQL->delete('Discussion', array('DiscussionID' => $DiscussionID));

        $this->SQL->delete('UserDiscussion', array('DiscussionID' => $DiscussionID));
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

        // Get the array
        $TagsArray = Gdn_Format::Unserialize($Tags);

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
     * Getter/setter for protected $AllowedSortFields array.
     */
    public static function allowedSortFields($Allowed = null) {
        if (is_array($Allowed)) {
            self::$AllowedSortFields = $Allowed;
        }

        return self::$AllowedSortFields;
    }
}
