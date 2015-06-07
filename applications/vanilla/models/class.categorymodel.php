<?php
/**
 * Category model
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Manages discussion categories' data.
 */
class CategoryModel extends Gdn_Model {

    /** Cache key. */
    const CACHE_KEY = 'Categories';

    /** Cache time to live. */
    const CACHE_TTL = 600;

    /** Cache grace. */
    const CACHE_GRACE = 60;

    /** Cache key. */
    const MASTER_VOTE_KEY = 'Categories.Rebuild.Vote';

    /** @var bool */
    public $Watching = false;

    /** @var array Merged Category data, including Pure + UserCategory. */
    public static $Categories = null;

    /** @var bool Whether or not to explicitly shard the categories cache. */
    public static $ShardCache = false;

    /**
     * @var bool Whether or not to join users to recent posts.
     * Forums with a lot of categories may need to optimize using this setting and simpler views.
     */
    public $JoinRecentUsers = true;

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct() {
        parent::__construct('Category');
    }

    /**
     *
     *
     * @param $Category
     * @return array
     */
    public static function allowedDiscussionTypes($Category) {
        $Category = self::PermissionCategory($Category);
        $Allowed = val('AllowedDiscussionTypes', $Category);
        $AllTypes = DiscussionModel::DiscussionTypes();
        ;

        if (empty($Allowed) || !is_array($Allowed)) {
            return $AllTypes;
        } else {
            return array_intersect_key($AllTypes, array_flip($Allowed));
        }
    }

    /**
     *
     *
     * @since 2.0.18
     * @access public
     * @return array Category IDs.
     */
    public static function categoryWatch($AllDiscussions = true) {
        $Categories = self::categories();
        $AllCount = count($Categories);

        $Watch = array();

        foreach ($Categories as $CategoryID => $Category) {
            if ($AllDiscussions && val('HideAllDiscussions', $Category)) {
                continue;
            }

            if ($Category['PermsDiscussionsView'] && $Category['Following']) {
                $Watch[] = $CategoryID;
            }
        }

        Gdn::pluginManager()->EventArguments['CategoryIDs'] =& $Watch;
        Gdn::pluginManager()->fireEvent('CategoryWatch');

        if ($AllCount == count($Watch)) {
            return true;
        }

        return $Watch;
    }

    /**
     * Gets either all of the categories or a single category.
     *
     * @param int|string|bool $ID Either the category ID or the category url code.
     * If nothing is passed then all categories are returned.
     * @return array Returns either one or all categories.
     * @since 2.0.18
     */
    public static function categories($ID = false) {

        if (self::$Categories == null) {
            // Try and get the categories from the cache.
            $categoriesCache = Gdn::cache()->get(self::CACHE_KEY);
            $rebuild = true;

            // If we received a valid data structure, extract the embedded expiry
            // and re-store the real categories on our static property.
            if (is_array($categoriesCache)) {
                // Test if it's time to rebuild
                $rebuildAfter = val('expiry', $categoriesCache, null);
                if (!is_null($rebuildAfter) && time() < $rebuildAfter) {
                    $rebuild = false;
                }
                self::$Categories = val('categories', $categoriesCache, null);
            }
            unset($categoriesCache);

            if ($rebuild) {
                // Try to get a rebuild lock
                $haveRebuildLock = self::rebuildLock();
                if ($haveRebuildLock || !self::$Categories) {
                    $Sql = Gdn::sql();
                    $Sql = clone $Sql;
                    $Sql->reset();

                    $Sql->select('c.*')
                        ->from('Category c')
                        //->select('lc.DateInserted', '', 'DateLastComment')
                        //->join('Comment lc', 'c.LastCommentID = lc.CommentID', 'left')
                        ->orderBy('c.TreeLeft');

                    self::$Categories = array_merge(array(), $Sql->get()->resultArray());
                    self::$Categories = Gdn_DataSet::Index(self::$Categories, 'CategoryID');
                    self::BuildCache();

                    // Release lock
                    if ($haveRebuildLock) {
                        self::rebuildLock(true);
                    }
                }
            }

            if (self::$Categories) {
                self::JoinUserData(self::$Categories, true);
            } else {
                return null;
            }

        }

        if ($ID !== false) {
            if (!is_numeric($ID) && $ID) {
                $Code = $ID;
                foreach (self::$Categories as $Category) {
                    if (strcasecmp($Category['UrlCode'], $Code) === 0) {
                        $ID = $Category['CategoryID'];
                        break;
                    }
                }
            }

            if (isset(self::$Categories[$ID])) {
                $Result = self::$Categories[$ID];
                return $Result;
            } else {
                return null;
            }
        } else {
            $Result = self::$Categories;
            return $Result;
        }
    }

    /**
     * Request rebuild mutex
     *
     * Allows competing instances to "vote" on the process that gets to rebuild
     * the category cache.
     *
     * @return boolean whether we may rebuild
     */
    protected static function rebuildLock($release = false) {
        static $isMaster = null;
        if ($release) {
            Gdn::cache()->remove(self::MASTER_VOTE_KEY);
            return;
        }
        if (is_null($isMaster)) {
            // Vote for master
            $instanceKey = getmypid();
            $masterKey = Gdn::cache()->add(self::MASTER_VOTE_KEY, $instanceKey, array(
                Gdn_Cache::FEATURE_EXPIRY => self::CACHE_GRACE
            ));

            $isMaster = ($instanceKey == $masterKey);
        }
        return (bool)$isMaster;
    }

    /**
     * Build and augment the category cache
     *
     * @param integer $CategoryID restrict JoinRecentPosts to this ID
     *
     */
    protected static function buildCache($CategoryID = null) {
        self::CalculateData(self::$Categories);
        self::JoinRecentPosts(self::$Categories, $CategoryID);

        $expiry = self::CACHE_TTL + self::CACHE_GRACE;
        Gdn::cache()->store(self::CACHE_KEY, array(
            'expiry' => time() + $expiry,
            'categories' => self::$Categories
        ), array(
            Gdn_Cache::FEATURE_EXPIRY => $expiry,
            Gdn_Cache::FEATURE_SHARD => self::$ShardCache
        ));
    }

    /**
     *
     *
     * @since 2.0.18
     * @access public
     * @param array $Data Dataset.
     */
    protected static function calculateData(&$Data) {
        foreach ($Data as &$Category) {
            $Category['CountAllDiscussions'] = $Category['CountDiscussions'];
            $Category['CountAllComments'] = $Category['CountComments'];
            $Category['Url'] = self::CategoryUrl($Category, false, '/');
            $Category['ChildIDs'] = array();
            if (val('Photo', $Category)) {
                $Category['PhotoUrl'] = Gdn_Upload::url($Category['Photo']);
            } else {
                $Category['PhotoUrl'] = '';
            }

            if ($Category['DisplayAs'] == 'Default') {
                if ($Category['Depth'] <= c('Vanilla.Categories.NavDepth', 0)) {
                    $Category['DisplayAs'] = 'Categories';
                } elseif ($Category['Depth'] == (c('Vanilla.Categories.NavDepth', 0) + 1) && c('Vanilla.Categories.DoHeadings')) {
                    $Category['DisplayAs'] = 'Heading';
                } else {
                    $Category['DisplayAs'] = 'Discussions';
                }
            }

            if (!val('CssClass', $Category)) {
                $Category['CssClass'] = 'Category-'.$Category['UrlCode'];
            }

            if (isset($Category['AllowedDiscussionTypes']) && is_string($Category['AllowedDiscussionTypes'])) {
                $Category['AllowedDiscussionTypes'] = unserialize($Category['AllowedDiscussionTypes']);
            }
        }

        $Keys = array_reverse(array_keys($Data));
        foreach ($Keys as $Key) {
            $Cat = $Data[$Key];
            $ParentID = $Cat['ParentCategoryID'];

            if (isset($Data[$ParentID]) && $ParentID != $Key) {
                $Data[$ParentID]['CountAllDiscussions'] += $Cat['CountAllDiscussions'];
                $Data[$ParentID]['CountAllComments'] += $Cat['CountAllComments'];
                array_unshift($Data[$ParentID]['ChildIDs'], $Key);
            }
        }
    }

    public static function clearCache() {
        Gdn::cache()->Remove(self::CACHE_KEY);
    }

    public static function clearUserCache() {
        $Key = 'UserCategory_'.Gdn::session()->UserID;
        Gdn::cache()->Remove($Key);
    }

    public function counts($Column) {
        $Result = array('Complete' => true);
        switch ($Column) {
            case 'CountDiscussions':
                $this->Database->query(DBAModel::GetCountSQL('count', 'Category', 'Discussion'));
                break;
            case 'CountComments':
                $this->Database->query(DBAModel::GetCountSQL('sum', 'Category', 'Discussion', $Column, 'CountComments'));
                break;
            case 'LastDiscussionID':
                $this->Database->query(DBAModel::GetCountSQL('max', 'Category', 'Discussion'));
                break;
            case 'LastCommentID':
                $Data = $this->SQL
                    ->select('d.CategoryID')
                    ->select('c.CommentID', 'max', 'LastCommentID')
                    ->select('d.DiscussionID', 'max', 'LastDiscussionID')
                    ->select('c.DateInserted', 'max', 'DateLastComment')
                    ->select('d.DateInserted', 'max', 'DateLastDiscussion')
                    ->from('Comment c')
                    ->join('Discussion d', 'd.DiscussionID = c.DiscussionID')
                    ->groupBy('d.CategoryID')
                    ->get()->resultArray();

                // Now we have to grab the discussions associated with these comments.
                $CommentIDs = consolidateArrayValuesByKey($Data, 'LastCommentID');

                // Grab the discussions for the comments.
                $this->SQL
                    ->select('c.CommentID, c.DiscussionID')
                    ->from('Comment c')
                    ->whereIn('c.CommentID', $CommentIDs);

                $Discussions = $this->SQL->get()->resultArray();
                $Discussions = Gdn_DataSet::Index($Discussions, array('CommentID'));

                foreach ($Data as $Row) {
                    $CategoryID = (int)$Row['CategoryID'];
                    $Category = CategoryModel::categories($CategoryID);
                    $CommentID = $Row['LastCommentID'];
                    $DiscussionID = valr("$CommentID.DiscussionID", $Discussions, null);

                    $DateLastComment = Gdn_Format::toTimestamp($Row['DateLastComment']);
                    $DateLastDiscussion = Gdn_Format::toTimestamp($Row['DateLastDiscussion']);

                    $Set = array('LastCommentID' => $CommentID);

                    if ($DiscussionID) {
                        $LastDiscussionID = val('LastDiscussionID', $Category);

                        if ($DateLastComment >= $DateLastDiscussion) {
                            // The most recent discussion is from this comment.
                            $Set['LastDiscussionID'] = $DiscussionID;
                        } else {
                            // The most recent discussion has no comments.
                            $Set['LastCommentID'] = null;
                        }
                    } else {
                        // Something went wrong.
                        $Set['LastCommentID'] = null;
                        $Set['LastDiscussionID'] = null;
                    }

                    $this->setField($CategoryID, $Set);
                }
                break;
            case 'LastDateInserted':
                $Categories = $this->SQL
                    ->select('ca.CategoryID')
                    ->select('d.DateInserted', '', 'DateLastDiscussion')
                    ->select('c.DateInserted', '', 'DateLastComment')
                    ->from('Category ca')
                    ->join('Discussion d', 'd.DiscussionID = ca.LastDiscussionID')
                    ->join('Comment c', 'c.CommentID = ca.LastCommentID')
                    ->get()->resultArray();

                foreach ($Categories as $Category) {
                    $DateLastDiscussion = val('DateLastDiscussion', $Category);
                    $DateLastComment = val('DateLastComment', $Category);

                    $MaxDate = $DateLastComment;
                    if (is_null($DateLastComment) || $DateLastDiscussion > $MaxDate) {
                        $MaxDate = $DateLastDiscussion;
                    }

                    if (is_null($MaxDate)) {
                        continue;
                    }

                    $CategoryID = (int)$Category['CategoryID'];
                    $this->setField($CategoryID, 'LastDateInserted', $MaxDate);
                }
                break;
        }
        self::ClearCache();
        return $Result;
    }

    public static function defaultCategory() {
        foreach (self::categories() as $Category) {
            if ($Category['CategoryID'] > 0) {
                return $Category;
            }
        }
    }

    /**
     * Remove categories that a user does not have permission to view.
     *
     * @param array $categoryIDs An array of categories to filter.
     * @return array Returns an array of category IDs that are okay to view.
     */
    public static function filterCategoryPermissions($categoryIDs) {
        $permissionCategories = static::GetByPermission('Discussions.View');

        if ($permissionCategories === true) {
            return $categoryIDs;
        } else {
            $permissionCategoryIDs = array_keys($permissionCategories);
            return array_intersect($categoryIDs, $permissionCategoryIDs);
        }
    }

    public static function getByPermission($Permission = 'Discussions.Add', $CategoryID = null, $Filter = array(), $PermFilter = array()) {
        static $Map = array('Discussions.Add' => 'PermsDiscussionsAdd', 'Discussions.View' => 'PermsDiscussionsView');
        $Field = $Map[$Permission];
        $DoHeadings = c('Vanilla.Categories.DoHeadings');
        $PermFilters = array();

        $Result = array();
        $Categories = self::categories();
        foreach ($Categories as $ID => $Category) {
            if (!$Category[$Field]) {
                continue;
            }

            if ($CategoryID != $ID) {
                if ($Category['CategoryID'] <= 0) {
                    continue;
                }

                $Exclude = false;
                foreach ($Filter as $Key => $Value) {
                    if (isset($Category[$Key]) && $Category[$Key] != $Value) {
                        $Exclude = true;
                        break;
                    }
                }

                if (!empty($PermFilter)) {
                    $PermCategory = val($Category['PermissionCategoryID'], $Categories);
                    if ($PermCategory) {
                        if (!isset($PermFilters[$PermCategory['CategoryID']])) {
                            $PermFilters[$PermCategory['CategoryID']] = self::Where($PermCategory, $PermFilter);
                        }

                        $Exclude = !$PermFilters[$PermCategory['CategoryID']];
                    } else {
                        $Exclude = true;
                    }
                }

                if ($Exclude) {
                    continue;
                }

                if ($DoHeadings && $Category['Depth'] <= 1) {
                    if ($Permission == 'Discussions.Add') {
                        continue;
                    } else {
                        $Category['PermsDiscussionsAdd'] = false;
                    }
                }
            }

            $Result[$ID] = $Category;
        }
        return $Result;
    }

    public static function where($Row, $Where) {
        if (empty($Where)) {
            return true;
        }

        foreach ($Where as $Key => $Value) {
            $RowValue = val($Key, $Row);

            // If there are no discussion types set then all discussion types are allowed.
            if ($Key == 'AllowedDiscussionTypes' && empty($RowValue)) {
                continue;
            }

            if (is_array($RowValue)) {
                if (is_array($Value)) {
                    // If both items are arrays then all values in the filter must be in the row.
                    if (count(array_intersect($Value, $RowValue)) < count($Value)) {
                        return false;
                    }
                } elseif (!in_array($Value, $RowValue)) {
                    return false;
                }
            } elseif (is_array($Value)) {
                if (!in_array($RowValue, $Value)) {
                    return false;
                }
            } else {
                if ($RowValue != $Value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Give a user points specific to this category.
     *
     * @param int $UserID The user to give the points to.
     * @param int $Points The number of points to give.
     * @param string $Source The source of the points.
     * @param int $CategoryID The category to give the points for.
     * @param int $Timestamp The time the points were given.
     */
    public static function givePoints($UserID, $Points, $Source = 'Other', $CategoryID = 0, $Timestamp = false) {
        // Figure out whether or not the category tracks points seperately.
        if ($CategoryID) {
            $Category = self::categories($CategoryID);
            if ($Category) {
                $CategoryID = val('PointsCategoryID', $Category);
            } else {
                $CategoryID = 0;
            }
        }

        UserModel::GivePoints($UserID, $Points, array($Source, 'CategoryID' => $CategoryID), $Timestamp);
    }

    /**
     *
     *
     * @since 2.0.18
     * @access public
     * @param array $Data Dataset.
     * @param string $Column Name of database column.
     * @param array $Options 'Join' key may contain array of columns to join on.
     */
    public static function joinCategories(&$Data, $Column = 'CategoryID', $Options = array()) {
        $Join = val('Join', $Options, array('Name' => 'Category', 'PermissionCategoryID', 'UrlCode' => 'CategoryUrlCode'));

        if ($Data instanceof Gdn_DataSet) {
            $Data2 = $Data->result();
        } else {
            $Data2 =& $Data;
        }

        foreach ($Data2 as &$Row) {
            $ID = val($Column, $Row);
            $Category = self::categories($ID);
            foreach ($Join as $N => $V) {
                if (is_numeric($N)) {
                    $N = $V;
                }

                if ($Category) {
                    $Value = $Category[$N];
                } else {
                    $Value = null;
                }

                setValue($V, $Row, $Value);
            }
        }
    }

    /**
     *
     *
     * @param $Data
     * @param null $CategoryID
     * @return bool
     */
    public static function joinRecentPosts(&$Data, $CategoryID = null) {
        $DiscussionIDs = array();
        $CommentIDs = array();
        $Joined = false;

        foreach ($Data as &$Row) {
            if (!is_null($CategoryID) && $Row['CategoryID'] != $CategoryID) {
                continue;
            }

            if (isset($Row['LastTitle']) && $Row['LastTitle']) {
                continue;
            }

            if ($Row['LastDiscussionID']) {
                $DiscussionIDs[] = $Row['LastDiscussionID'];
            }

            if ($Row['LastCommentID']) {
                $CommentIDs[] = $Row['LastCommentID'];
            }
            $Joined = true;
        }

        // Create a fresh copy of the Sql object so as not to pollute.
        $Sql = clone Gdn::sql();
        $Sql->reset();

        // Grab the discussions.
        if (count($DiscussionIDs) > 0) {
            $Discussions = $Sql->whereIn('DiscussionID', $DiscussionIDs)->get('Discussion')->resultArray();
            $Discussions = Gdn_DataSet::Index($Discussions, array('DiscussionID'));
        }

        if (count($CommentIDs) > 0) {
            $Comments = $Sql->whereIn('CommentID', $CommentIDs)->get('Comment')->resultArray();
            $Comments = Gdn_DataSet::Index($Comments, array('CommentID'));
        }

        foreach ($Data as &$Row) {
            if (!is_null($CategoryID) && $Row['CategoryID'] != $CategoryID) {
                continue;
            }

            $Discussion = val($Row['LastDiscussionID'], $Discussions);
            $NameUrl = 'x';
            if ($Discussion) {
                $Row['LastTitle'] = Gdn_Format::text($Discussion['Name']);
                $Row['LastUserID'] = $Discussion['InsertUserID'];
                $Row['LastDiscussionUserID'] = $Discussion['InsertUserID'];
                $Row['LastDateInserted'] = $Discussion['DateInserted'];
                $NameUrl = Gdn_Format::text($Discussion['Name'], true);
                $Row['LastUrl'] = DiscussionUrl($Discussion, false, '/').'#latest';
            }
            $Comment = val($Row['LastCommentID'], $Comments);
            if ($Comment) {
                $Row['LastUserID'] = $Comment['InsertUserID'];
                $Row['LastDateInserted'] = $Comment['DateInserted'];
                $Row['DateLastComment'] = $Comment['DateInserted'];
            } else {
                $Row['NoComment'] = true;
            }

            touchValue('LastTitle', $Row, '');
            touchValue('LastUserID', $Row, null);
            touchValue('LastDiscussionUserID', $Row, null);
            touchValue('LastDateInserted', $Row, null);
            touchValue('LastUrl', $Row, null);
        }
        return $Joined;
    }

    /**
     *
     *
     * @param null $Category
     * @param null $Categories
     */
    public static function joinRecentChildPosts(&$Category = null, &$Categories = null) {
        if ($Categories === null) {
            $Categories =& self::$Categories;
        }

        if ($Category === null) {
            $Category =& $Categories[-1];
        }

        if (!isset($Category['ChildIDs'])) {
            return;
        }

        $LastTimestamp = Gdn_Format::toTimestamp($Category['LastDateInserted']);
        ;
        $LastCategoryID = null;

        if ($Category['DisplayAs'] == 'Categories') {
            // This is an overview category so grab it's recent data from it's children.
            foreach ($Category['ChildIDs'] as $CategoryID) {
                if (!isset($Categories[$CategoryID])) {
                    continue;
                }

                $ChildCategory =& $Categories[$CategoryID];
                if ($ChildCategory['DisplayAs'] == 'Categories') {
                    self::JoinRecentChildPosts($ChildCategory, $Categories);
                }
                $Timestamp = Gdn_Format::toTimestamp($ChildCategory['LastDateInserted']);

                if ($LastTimestamp === false || $LastTimestamp < $Timestamp) {
                    $LastTimestamp = $Timestamp;
                    $LastCategoryID = $CategoryID;
                }
            }

            if ($LastCategoryID) {
                $LastCategory = $Categories[$LastCategoryID];

                $Category['LastCommentID'] = $LastCategory['LastCommentID'];
                $Category['LastDiscussionID'] = $LastCategory['LastDiscussionID'];
                $Category['LastDateInserted'] = $LastCategory['LastDateInserted'];
                $Category['LastTitle'] = $LastCategory['LastTitle'];
                $Category['LastUserID'] = $LastCategory['LastUserID'];
                $Category['LastDiscussionUserID'] = $LastCategory['LastDiscussionUserID'];
                $Category['LastUrl'] = $LastCategory['LastUrl'];
                $Category['LastCategoryID'] = $LastCategory['CategoryID'];
//            $Category['LastName'] = $LastCategory['LastName'];
//            $Category['LastName'] = $LastCategory['LastName'];
//            $Category['LastEmail'] = $LastCategory['LastEmail'];
//            $Category['LastPhoto'] = $LastCategory['LastPhoto'];
            }
        }
    }

    /**
     * Add UserCategory modifiers
     *
     * Update &$Categories in memory by applying modifiers from UserCategory for
     * the currently logged-in user.
     *
     * @since 2.0.18
     * @access public
     * @param array &$Categories
     * @param bool $AddUserCategory
     */
    public static function joinUserData(&$Categories, $AddUserCategory = true) {
        $IDs = array_keys($Categories);
        $DoHeadings = c('Vanilla.Categories.DoHeadings');

        if ($AddUserCategory) {
            $SQL = clone Gdn::sql();
            $SQL->reset();

            if (Gdn::session()->UserID) {
                $Key = 'UserCategory_'.Gdn::session()->UserID;
                $UserData = Gdn::cache()->get($Key);
                if ($UserData === Gdn_Cache::CACHEOP_FAILURE) {
                    $UserData = $SQL->getWhere('UserCategory', array('UserID' => Gdn::session()->UserID))->resultArray();
                    $UserData = Gdn_DataSet::Index($UserData, 'CategoryID');
                    Gdn::cache()->store($Key, $UserData);
                }
            } else {
                $UserData = array();
            }

//         Gdn::controller()->setData('UserData', $UserData);

            foreach ($IDs as $ID) {
                $Category = $Categories[$ID];

                $DateMarkedRead = val('DateMarkedRead', $Category);
                $Row = val($ID, $UserData);
                if ($Row) {
                    $UserDateMarkedRead = $Row['DateMarkedRead'];

                    if (!$DateMarkedRead || ($UserDateMarkedRead && Gdn_Format::toTimestamp($UserDateMarkedRead) > Gdn_Format::toTimestamp($DateMarkedRead))) {
                        $Categories[$ID]['DateMarkedRead'] = $UserDateMarkedRead;
                        $DateMarkedRead = $UserDateMarkedRead;
                    }

                    $Categories[$ID]['Unfollow'] = $Row['Unfollow'];
                } else {
                    $Categories[$ID]['Unfollow'] = false;
                }

                // Calculate the following field.
                $Following = !((bool)GetValue('Archived', $Category) || (bool)GetValue('Unfollow', $Row, false));
                $Categories[$ID]['Following'] = $Following;

                // Calculate the read field.
                if ($DoHeadings && $Category['Depth'] <= 1) {
                    $Categories[$ID]['Read'] = false;
                } elseif ($DateMarkedRead) {
                    if (val('LastDateInserted', $Category)) {
                        $Categories[$ID]['Read'] = Gdn_Format::toTimestamp($DateMarkedRead) >= Gdn_Format::toTimestamp($Category['LastDateInserted']);
                    } else {
                        $Categories[$ID]['Read'] = true;
                    }
                } else {
                    $Categories[$ID]['Read'] = false;
                }
            }

        }

        // Add permissions.
        $Session = Gdn::session();
        foreach ($IDs as $CID) {
            $Category = $Categories[$CID];
            $Categories[$CID]['Url'] = url($Category['Url'], '//');
            if ($Photo = val('Photo', $Category)) {
                $Categories[$CID]['PhotoUrl'] = Gdn_Upload::url($Photo);
            }

            if (!empty($Category['LastUrl'])) {
                $Categories[$CID]['LastUrl'] = url($Category['LastUrl'], '//');
            }
            $Categories[$CID]['PermsDiscussionsView'] = $Session->checkPermission('Vanilla.Discussions.View', true, 'Category', $Category['PermissionCategoryID']);
            $Categories[$CID]['PermsDiscussionsAdd'] = $Session->checkPermission('Vanilla.Discussions.Add', true, 'Category', $Category['PermissionCategoryID']);
            $Categories[$CID]['PermsDiscussionsEdit'] = $Session->checkPermission('Vanilla.Discussions.Edit', true, 'Category', $Category['PermissionCategoryID']);
            $Categories[$CID]['PermsCommentsAdd'] = $Session->checkPermission('Vanilla.Comments.Add', true, 'Category', $Category['PermissionCategoryID']);
        }

        // Translate name and description
        foreach ($IDs as $ID) {
            $Code = $Categories[$ID]['UrlCode'];
            $Categories[$ID]['Name'] = TranslateContent("Categories.".$Code.".Name", $Categories[$ID]['Name']);
            $Categories[$ID]['Description'] = TranslateContent("Categories.".$Code.".Description", $Categories[$ID]['Description']);
        }
    }

    /**
     * Delete a single category and assign its discussions to another.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object $Category
     * @param int $ReplacementCategoryID Unique ID of category all discussion are being move to.
     */
    public function delete($Category, $ReplacementCategoryID) {
        // Don't do anything if the required category object & properties are not defined.
        if (!is_object($Category)
            || !property_exists($Category, 'CategoryID')
            || !property_exists($Category, 'ParentCategoryID')
            || !property_exists($Category, 'AllowDiscussions')
            || !property_exists($Category, 'Name')
            || $Category->CategoryID <= 0
        ) {
            throw new Exception(t('Invalid category for deletion.'));
        } else {
            // Remove permissions related to category
            $PermissionModel = Gdn::permissionModel();
            $PermissionModel->delete(null, 'Category', 'CategoryID', $Category->CategoryID);

            // If there is a replacement category...
            if ($ReplacementCategoryID > 0) {
                // Update children categories
                $this->SQL
                    ->update('Category')
                    ->set('ParentCategoryID', $ReplacementCategoryID)
                    ->where('ParentCategoryID', $Category->CategoryID)
                    ->put();

                // Update permission categories.
                $this->SQL
                    ->update('Category')
                    ->set('PermissionCategoryID', $ReplacementCategoryID)
                    ->where('PermissionCategoryID', $Category->CategoryID)
                    ->where('CategoryID <>', $Category->CategoryID)
                    ->put();

                // Update discussions
                $this->SQL
                    ->update('Discussion')
                    ->set('CategoryID', $ReplacementCategoryID)
                    ->where('CategoryID', $Category->CategoryID)
                    ->put();

                // Update the discussion count
                $Count = $this->SQL
                    ->select('DiscussionID', 'count', 'DiscussionCount')
                    ->from('Discussion')
                    ->where('CategoryID', $ReplacementCategoryID)
                    ->get()
                    ->firstRow()
                    ->DiscussionCount;

                if (!is_numeric($Count)) {
                    $Count = 0;
                }

                $this->SQL
                    ->update('Category')->set('CountDiscussions', $Count)
                    ->where('CategoryID', $ReplacementCategoryID)
                    ->put();

                // Update tags
                $this->SQL
                    ->update('Tag')
                    ->set('CategoryID', $ReplacementCategoryID)
                    ->where('CategoryID', $Category->CategoryID)
                    ->put();

                $this->SQL
                    ->update('TagDiscussion')
                    ->set('CategoryID', $ReplacementCategoryID)
                    ->where('CategoryID', $Category->CategoryID)
                    ->put();
            } else {
                // Delete comments in this category
                $this->SQL
                    ->from('Comment c')
                    ->join('Discussion d', 'c.DiscussionID = d.DiscussionID')
                    ->where('d.CategoryID', $Category->CategoryID)
                    ->delete();

                // Delete discussions in this category
                $this->SQL->delete('Discussion', array('CategoryID' => $Category->CategoryID));

                // Make inherited permission local permission
                $this->SQL
                    ->update('Category')
                    ->set('PermissionCategoryID', 0)
                    ->where('PermissionCategoryID', $Category->CategoryID)
                    ->where('CategoryID <>', $Category->CategoryID)
                    ->put();

                // Delete tags
                $this->SQL->delete('Tag', array('CategoryID' => $Category->CategoryID));
                $this->SQL->delete('TagDiscussion', array('CategoryID' => $Category->CategoryID));
            }

            // Delete the category
            $this->SQL->delete('Category', array('CategoryID' => $Category->CategoryID));
        }
        // Make sure to reorganize the categories after deletes
        $this->RebuildTree();
    }

    /**
     * Get data for a single category selected by Url Code. Disregards permissions.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $CodeID Unique Url Code of category we're getting data for.
     * @return object SQL results.
     */
    public function getByCode($Code) {
        return $this->SQL->getWhere('Category', array('UrlCode' => $Code))->firstRow();
    }

    /**
     * Get data for a single category selected by ID. Disregards permissions.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $CategoryID Unique ID of category we're getting data for.
     * @return object SQL results.
     */
    public function getID($CategoryID, $DatasetType = DATASET_TYPE_OBJECT) {
        $Category = $this->SQL->getWhere('Category', array('CategoryID' => $CategoryID))->firstRow($DatasetType);
        if (isset($Category->AllowedDiscussionTypes) && is_string($Category->AllowedDiscussionTypes)) {
            $Category->AllowedDiscussionTypes = unserialize($Category->AllowedDiscussionTypes);
        }

        return $Category;
    }

    /**
     * Get list of categories (respecting user permission).
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $OrderFields Ignored.
     * @param string $OrderDirection Ignored.
     * @param int $Limit Ignored.
     * @param int $Offset Ignored.
     * @return Gdn_DataSet SQL results.
     */
    public function get($OrderFields = '', $OrderDirection = 'asc', $Limit = false, $Offset = false) {
        $this->SQL
            ->select('c.ParentCategoryID, c.CategoryID, c.TreeLeft, c.TreeRight, c.Depth, c.Name, c.Description, c.CountDiscussions, c.AllowDiscussions, c.UrlCode')
            ->from('Category c')
            ->beginWhereGroup()
            ->permission('Vanilla.Discussions.View', 'c', 'PermissionCategoryID', 'Category')
            ->endWhereGroup()
            ->orWhere('AllowDiscussions', '0')
            ->orderBy('TreeLeft', 'asc');

        // Note: we are using the Nested Set tree model, so TreeLeft is used for sorting.
        // Ref: http://articles.sitepoint.com/article/hierarchical-data-database/2
        // Ref: http://en.wikipedia.org/wiki/Nested_set_model

        $CategoryData = $this->SQL->get();
        $this->AddCategoryColumns($CategoryData);
        return $CategoryData;
    }

    /**
     * Get list of categories (disregarding user permission for admins).
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $OrderFields Ignored.
     * @param string $OrderDirection Ignored.
     * @param int $Limit Ignored.
     * @param int $Offset Ignored.
     * @return object SQL results.
     */
    public function getAll() {
        $CategoryData = $this->SQL
            ->select('c.*')
            ->from('Category c')
            ->orderBy('TreeLeft', 'asc')
            ->get();

        $this->AddCategoryColumns($CategoryData);
        return $CategoryData;
    }

    /**
     * Return the number of descendants for a specific category.
     */
    public function getDescendantCountByCode($Code) {
        $Category = $this->GetByCode($Code);
        if ($Category) {
            return round(($Category->TreeRight - $Category->TreeLeft - 1) / 2);
        }

        return 0;
    }

    /**
     * Get all of the ancestor categories above this one.
     * @param int|string $Category The category ID or url code.
     * @param bool $checkPermissions Whether or not to only return the categories with view permission.
     * @param bool $includeHeadings Whether or not to include heading categories.
     * @return array
     */
    public static function getAncestors($categoryID, $checkPermissions = true, $includeHeadings = false) {
        $Categories = self::categories();
        $Result = array();

        // Grab the category by ID or url code.
        if (is_numeric($categoryID)) {
            if (isset($Categories[$categoryID])) {
                $Category = $Categories[$categoryID];
            }
        } else {
            foreach ($Categories as $ID => $Value) {
                if ($Value['UrlCode'] == $categoryID) {
                    $Category = $Categories[$ID];
                    break;
                }
            }
        }

        if (!isset($Category)) {
            return $Result;
        }

        // Build up the ancestor array by tracing back through parents.
        $Result[$Category['CategoryID']] = $Category;
        $Max = 20;
        while (isset($Categories[$Category['ParentCategoryID']])) {
            // Check for an infinite loop.
            if ($Max <= 0) {
                break;
            }
            $Max--;

            if ($checkPermissions && !$Category['PermsDiscussionsView']) {
                $Category = $Categories[$Category['ParentCategoryID']];
                continue;
            }

            if ($Category['CategoryID'] == -1) {
                break;
            }

            // Return by ID or code.
            if (is_numeric($categoryID)) {
                $ID = $Category['CategoryID'];
            } else {
                $ID = $Category['UrlCode'];
            }

            if ($includeHeadings || $Category['DisplayAs'] !== 'Heading') {
                $Result[$ID] = $Category;
            }

            $Category = $Categories[$Category['ParentCategoryID']];
        }
        $Result = array_reverse($Result, true); // order for breadcrumbs
        return $Result;
    }

    /**
     *
     *
     * @since 2.0.18
     * @acces public
     * @param string $Code Where condition.
     * @return object DataSet
     */
    public function getDescendantsByCode($Code) {
        Deprecated('CategoryModel::GetDescendantsByCode', 'CategoryModel::GetAncestors');

        // SELECT title FROM tree WHERE lft < 4 AND rgt > 5 ORDER BY lft ASC;
        return $this->SQL
            ->select('c.ParentCategoryID, c.CategoryID, c.TreeLeft, c.TreeRight, c.Depth, c.Name, c.Description, c.CountDiscussions, c.CountComments, c.AllowDiscussions, c.UrlCode')
            ->from('Category c')
            ->join('Category d', 'c.TreeLeft < d.TreeLeft and c.TreeRight > d.TreeRight')
            ->where('d.UrlCode', $Code)
            ->orderBy('c.TreeLeft', 'asc')
            ->get();
    }

    /**
     * Get the subtree starting at a given parent.
     *
     * @param string $parentCategory The ID or url code of the parent category.
     * @since 2.0.18
     * @param bool $includeParent Whether or not to include the parent in the result.
     * @param bool|int $adjustDepth Whether or not to adjust the depth or a number to adjust the depth by.
     * Passing `true` as this parameter will make the returned subtree look like the full tree which is useful for many
     * views that expect the full category tree.
     * @return array An array of categories.
     */
    public static function getSubtree($parentCategory, $includeParent = true, $adjustDepth = false) {
        $Result = array();
        $Category = self::categories($parentCategory);

        // Check to see if the depth should be adjusted.
        // This value is true if called by a dev or a number if called recursively.
        if ($adjustDepth === true) {
            $adjustDepth = -val('Depth', $Category) + ($includeParent ? 1 : 0);
        }

        if ($Category) {
            if ($includeParent) {
                if ($adjustDepth) {
                    $Category['Depth'] += $adjustDepth;
                }

                $Result[$Category['CategoryID']] = $Category;
            }
            $ChildIDs = val('ChildIDs', $Category, array());

            foreach ($ChildIDs as $ChildID) {
                $Result = array_replace($Result, self::GetSubtree($ChildID, true, $adjustDepth));
            }
        }
        return $Result;
    }

    public function getFull($CategoryID = false, $Permissions = false) {

        // Get the current category list
        $Categories = self::categories();

        // Filter out the categories we aren't supposed to view.
        if ($CategoryID && !is_array($CategoryID)) {
            $CategoryID = array($CategoryID);
        }

        if (!$CategoryID && $this->Watching) {
            $CategoryID = self::CategoryWatch(false);
        }

        switch ($Permissions) {
            case 'Vanilla.Discussions.Add':
                $Permissions = 'PermsDiscussionsAdd';
                break;
            case 'Vanilla.Disussions.Edit':
                $Permissions = 'PermsDiscussionsEdit';
                break;
            default:
                $Permissions = 'PermsDiscussionsView';
                break;
        }

        $IDs = array_keys($Categories);
        foreach ($IDs as $ID) {
            if ($ID < 0) {
                unset($Categories[$ID]);
            } elseif (!$Categories[$ID][$Permissions])
                unset($Categories[$ID]);
            elseif (is_array($CategoryID) && !in_array($ID, $CategoryID))
                unset($Categories[$ID]);
        }

        //self::JoinRecentPosts($Categories);
        foreach ($Categories as &$Category) {
            if ($Category['ParentCategoryID'] <= 0) {
                self::JoinRecentChildPosts($Category, $Categories);
            }
        }

        // This join users call can be very slow on forums with a lot of categories so we can disable it here.
        if ($this->JoinRecentUsers) {
            Gdn::userModel()->joinUsers($Categories, array('LastUserID'));
        }

        $Result = new Gdn_DataSet($Categories, DATASET_TYPE_ARRAY);
        $Result->DatasetType(DATASET_TYPE_OBJECT);
        return $Result;
    }

    /**
     * Get a list of categories, considering several filters
     *
     * @param array $RestrictIDs Optional list of category ids to mask the dataset
     * @param string $Permissions Optional permission to require. Defaults to Vanilla.Discussions.View.
     * @param array $ExcludeWhere Exclude categories with any of these flags
     * @return \Gdn_DataSet
     */
    public function getFiltered($RestrictIDs = false, $Permissions = false, $ExcludeWhere = false) {

        // Get the current category list
        $Categories = self::categories();

        // Filter out the categories we aren't supposed to view.
        if ($RestrictIDs && !is_array($RestrictIDs)) {
            $RestrictIDs = array($RestrictIDs);
        } elseif ($this->Watching)
            $RestrictIDs = self::CategoryWatch();

        switch ($Permissions) {
            case 'Vanilla.Discussions.Add':
                $Permissions = 'PermsDiscussionsAdd';
                break;
            case 'Vanilla.Disussions.Edit':
                $Permissions = 'PermsDiscussionsEdit';
                break;
            default:
                $Permissions = 'PermsDiscussionsView';
                break;
        }

        $IDs = array_keys($Categories);
        foreach ($IDs as $ID) {
            // Exclude the root category
            if ($ID < 0) {
                unset($Categories[$ID]);
            } // No categories where we don't have permission
            elseif (!$Categories[$ID][$Permissions])
                unset($Categories[$ID]);

            // No categories whose filter fields match the provided filter values
            elseif (is_array($ExcludeWhere)) {
                foreach ($ExcludeWhere as $Filter => $FilterValue) {
                    if (val($Filter, $Categories[$ID], false) == $FilterValue) {
                        unset($Categories[$ID]);
                    }
                }
            } // No categories that are otherwise filtered out
            elseif (is_array($RestrictIDs) && !in_array($ID, $RestrictIDs))
                unset($Categories[$ID]);
        }

        Gdn::userModel()->joinUsers($Categories, array('LastUserID'));

        $Result = new Gdn_DataSet($Categories, DATASET_TYPE_ARRAY);
        $Result->DatasetType(DATASET_TYPE_OBJECT);
        return $Result;
    }

    /**
     * Get full data for a single category by its URL slug. Respects permissions.
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $UrlCode Unique category slug from URL.
     * @return object SQL results.
     */
    public function getFullByUrlCode($UrlCode) {
        $Data = (object)self::categories($UrlCode);

        // Check to see if the user has permission for this category.
        // Get the category IDs.
        $CategoryIDs = DiscussionModel::CategoryPermissions();
        if (is_array($CategoryIDs) && !in_array(val('CategoryID', $Data), $CategoryIDs)) {
            $Data = false;
        }
        return $Data;
    }

    /**
     * A simplified version of GetWhere that polls the cache instead of the database.
     * @param array $Where
     * @return array
     * @since 2.2.2
     */
    public function getWhereCache($Where) {
        $Result = array();

        foreach (self::categories() as $Index => $Row) {
            $Match = true;
            foreach ($Where as $Column => $Value) {
                $RowValue = val($Column, $Row, null);

                if ($RowValue != $Value && !(is_array($Value) && in_array($RowValue, $Value))) {
                    $Match = false;
                    break;
                }
            }
            if ($Match) {
                $Result[$Index] = $Row;
            }
        }

        return $Result;
    }

    /**
     * Check whether category has any children categories.
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $CategoryID Unique ID for category being checked.
     * @return bool
     */
    public function hasChildren($CategoryID) {
        $ChildData = $this->SQL
            ->select('CategoryID')
            ->from('Category')
            ->where('ParentCategoryID', $CategoryID)
            ->get();
        return $ChildData->numRows() > 0 ? true : false;
    }

    /**
     *
     *
     * @since 2.0.0
     * @access public
     * @param array $Data
     * @param string $Permission
     * @param string $Column
     */
    public static function joinModerators($Data, $Permission = 'Vanilla.Comments.Edit', $Column = 'Moderators') {
        $Moderators = Gdn::sql()
            ->select('u.UserID, u.Name, u.Photo, u.Email')
            ->select('p.JunctionID as CategoryID')
            ->from('User u')
            ->join('UserRole ur', 'ur.UserID = u.UserID')
            ->join('Permission p', 'ur.RoleID = p.RoleID')
            ->where('`'.$Permission.'`', 1)
            ->get()->resultArray();

        $Moderators = Gdn_DataSet::Index($Moderators, 'CategoryID', array('Unique' => false));

        foreach ($Data as &$Category) {
            $ID = val('PermissionCategoryID', $Category);
            $Mods = val($ID, $Moderators, array());
            $ModIDs = array();
            $UniqueMods = array();
            foreach ($Mods as $Mod) {
                if (!in_array($Mod['UserID'], $ModIDs)) {
                    $ModIDs[] = $Mod['UserID'];
                    $UniqueMods[] = $Mod;
                }

            }
            setValue($Column, $Category, $UniqueMods);
        }
    }

    public static function makeTree($Categories, $Root = null) {
        $Result = array();

        $Categories = (array)$Categories;

        if ($Root) {
            $Root = (array)$Root;
            // Make the tree out of this category as a subtree.
            $DepthAdjust = -$Root['Depth'];
            $Result = self::_MakeTreeChildren($Root, $Categories, $DepthAdjust);
        } else {
            // Make a tree out of all categories.
            foreach ($Categories as $Category) {
                if (isset($Category['Depth']) && $Category['Depth'] == 1) {
                    $Row = $Category;
                    $Row['Children'] = self::_MakeTreeChildren($Row, $Categories, 0);
                    $Result[] = $Row;
                }
            }
        }
        return $Result;
    }

    protected static function _MakeTreeChildren($Category, $Categories, $DepthAdj = null) {
        if (is_null($DepthAdj)) {
            $DepthAdj = -val('Depth', $Category);
        }

        $Result = array();
        foreach ($Category['ChildIDs'] as $ID) {
            if (!isset($Categories[$ID])) {
                continue;
            }
            $Row = $Categories[$ID];
            $Row['Depth'] += $DepthAdj;
            $Row['Children'] = self::_MakeTreeChildren($Row, $Categories);
            $Result[] = $Row;
        }
        return $Result;
    }

    /**
     * Return the category that contains the permissions for the given category.
     *
     * @param mixed $Category
     * @since 2.2
     */
    public static function permissionCategory($Category) {
        if (empty($Category)) {
            return self::categories(-1);
        }

        if (!is_array($Category) && !is_object($Category)) {
            $Category = self::categories($Category);
        }

        return self::categories(val('PermissionCategoryID', $Category));
    }

    /**
     * Rebuilds the category tree. We are using the Nested Set tree model.
     *
     * @param bool $BySort Rebuild the tree by sort order instead of existing tree order.
     * @ref http://en.wikipedia.org/wiki/Nested_set_model
     *
     * @since 2.0.0
     * @access public
     */
    public function rebuildTree($BySort = false) {
        // Grab all of the categories.
        if ($BySort) {
            $Order = 'Sort, Name';
        } else {
            $Order = 'TreeLeft, Sort, Name';
        }

        $Categories = $this->SQL->get('Category', $Order);
        $Categories = Gdn_DataSet::Index($Categories->resultArray(), 'CategoryID');

        // Make sure the tree has a root.
        if (!isset($Categories[-1])) {
            $RootCat = array('CategoryID' => -1, 'TreeLeft' => 1, 'TreeRight' => 4, 'Depth' => 0, 'InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Gdn_Format::toDateTime(), 'DateUpdated' => Gdn_Format::toDateTime(), 'Name' => 'Root', 'UrlCode' => '', 'Description' => 'Root of category tree. Users should never see this.', 'PermissionCategoryID' => -1, 'Sort' => 0, 'ParentCategoryID' => null);
            $Categories[-1] = $RootCat;
            $this->SQL->insert('Category', $RootCat);
        }

        // Build a tree structure out of the categories.
        $Root = null;
        foreach ($Categories as &$Cat) {
            if (!isset($Cat['CategoryID'])) {
                continue;
            }

            // Backup category settings for efficient database saving.
            try {
                $Cat['_TreeLeft'] = $Cat['TreeLeft'];
                $Cat['_TreeRight'] = $Cat['TreeRight'];
                $Cat['_Depth'] = $Cat['Depth'];
                $Cat['_PermissionCategoryID'] = $Cat['PermissionCategoryID'];
                $Cat['_ParentCategoryID'] = $Cat['ParentCategoryID'];
            } catch (Exception $Ex) {
            }

            if ($Cat['CategoryID'] == -1) {
                $Root =& $Cat;
                continue;
            }

            $ParentID = $Cat['ParentCategoryID'];
            if (!$ParentID) {
                $ParentID = -1;
                $Cat['ParentCategoryID'] = $ParentID;
            }
            if (!isset($Categories[$ParentID]['Children'])) {
                $Categories[$ParentID]['Children'] = array();
            }
            $Categories[$ParentID]['Children'][] =& $Cat;
        }
        unset($Cat);

        // Set the tree attributes of the tree.
        $this->_SetTree($Root);
        unset($Root);

        // Save the tree structure.
        foreach ($Categories as $Cat) {
            if (!isset($Cat['CategoryID'])) {
                continue;
            }
            if ($Cat['_TreeLeft'] != $Cat['TreeLeft'] || $Cat['_TreeRight'] != $Cat['TreeRight'] || $Cat['_Depth'] != $Cat['Depth'] || $Cat['PermissionCategoryID'] != $Cat['PermissionCategoryID'] || $Cat['_ParentCategoryID'] != $Cat['ParentCategoryID'] || $Cat['Sort'] != $Cat['TreeLeft']) {
                $this->SQL->put(
                    'Category',
                    array('TreeLeft' => $Cat['TreeLeft'], 'TreeRight' => $Cat['TreeRight'], 'Depth' => $Cat['Depth'], 'PermissionCategoryID' => $Cat['PermissionCategoryID'], 'ParentCategoryID' => $Cat['ParentCategoryID'], 'Sort' => $Cat['TreeLeft']),
                    array('CategoryID' => $Cat['CategoryID'])
                );
            }
        }
        $this->SetCache();
    }

    /**
     *
     *
     * @since 2.0.18
     * @access protected
     * @param array $Node
     * @param int $Left
     * @param int $Depth
     */
    protected function _SetTree(&$Node, $Left = 1, $Depth = 0) {
        $Right = $Left + 1;

        if (isset($Node['Children'])) {
            foreach ($Node['Children'] as &$Child) {
                $Right = $this->_SetTree($Child, $Right, $Depth + 1);
                $Child['ParentCategoryID'] = $Node['CategoryID'];
                if ($Child['PermissionCategoryID'] != $Child['CategoryID']) {
                    $Child['PermissionCategoryID'] = val('PermissionCategoryID', $Node, $Child['CategoryID']);
                }
            }
            unset($Node['Children']);
        }

        $Node['TreeLeft'] = $Left;
        $Node['TreeRight'] = $Right;
        $Node['Depth'] = $Depth;

        return $Right + 1;
    }

    /**
     * Saves the category tree based on a provided tree array. We are using the
     * Nested Set tree model.
     *
     * @ref http://articles.sitepoint.com/article/hierarchical-data-database/2
     * @ref http://en.wikipedia.org/wiki/Nested_set_model
     *
     * @since 2.0.16
     * @access public
     *
     * @param array $TreeArray A fully defined nested set model of the category tree.
     */
    public function saveTree($TreeArray) {
        /*
          TreeArray comes in the format:
        '0' ...
          'item_id' => "root"
          'parent_id' => "none"
          'depth' => "0"
          'left' => "1"
          'right' => "34"
        '1' ...
          'item_id' => "1"
          'parent_id' => "root"
          'depth' => "1"
          'left' => "2"
          'right' => "3"
        etc...
        */

        // Grab all of the categories so that permissions can be properly saved.
        $PermTree = $this->SQL->select('CategoryID, PermissionCategoryID, TreeLeft, TreeRight, Depth, Sort, ParentCategoryID')->from('Category')->get();
        $PermTree = $PermTree->Index($PermTree->resultArray(), 'CategoryID');

        // The tree must be walked in order for the permissions to save properly.
        usort($TreeArray, array('CategoryModel', '_TreeSort'));
        $Saves = array();

        foreach ($TreeArray as $I => $Node) {
            $CategoryID = val('item_id', $Node);
            if ($CategoryID == 'root') {
                $CategoryID = -1;
            }

            $ParentCategoryID = val('parent_id', $Node);
            if (in_array($ParentCategoryID, array('root', 'none'))) {
                $ParentCategoryID = -1;
            }

            $PermissionCategoryID = valr("$CategoryID.PermissionCategoryID", $PermTree, 0);
            $PermCatChanged = false;
            if ($PermissionCategoryID != $CategoryID) {
                // This category does not have custom permissions so must inherit its parent's permissions.
                $PermissionCategoryID = valr("$ParentCategoryID.PermissionCategoryID", $PermTree, 0);
                if ($CategoryID != -1 && !GetValueR("$ParentCategoryID.Touched", $PermTree)) {
                    throw new Exception("Category $ParentCategoryID not touched before touching $CategoryID.");
                }
                if ($PermTree[$CategoryID]['PermissionCategoryID'] != $PermissionCategoryID) {
                    $PermCatChanged = true;
                }
                $PermTree[$CategoryID]['PermissionCategoryID'] = $PermissionCategoryID;
            }
            $PermTree[$CategoryID]['Touched'] = true;

            // Only update if the tree doesn't match the database.
            $Row = $PermTree[$CategoryID];
            if ($Node['left'] != $Row['TreeLeft'] || $Node['right'] != $Row['TreeRight'] || $Node['depth'] != $Row['Depth'] || $ParentCategoryID != $Row['ParentCategoryID'] || $Node['left'] != $Row['Sort'] || $PermCatChanged) {
                $Set = array(
                    'TreeLeft' => $Node['left'],
                    'TreeRight' => $Node['right'],
                    'Depth' => $Node['depth'],
                    'Sort' => $Node['left'],
                    'ParentCategoryID' => $ParentCategoryID,
                    'PermissionCategoryID' => $PermissionCategoryID
                );

                $this->SQL->update(
                    'Category',
                    $Set,
                    array('CategoryID' => $CategoryID)
                )->put();

                $Saves[] = array_merge(array('CategoryID' => $CategoryID), $Set);
            }
        }
        self::ClearCache();
        return $Saves;
    }

    /**
     * Utility method for sorting via usort.
     *
     * @since 2.0.18
     * @access protected
     * @param $A First element to compare.
     * @param $B Second element to compare.
     * @return int -1, 1, 0 (per usort)
     */
    protected function _TreeSort($A, $B) {
        if ($A['left'] > $B['left']) {
            return 1;
        } elseif ($A['left'] < $B['left'])
            return -1;
        else {
            return 0;
        }
    }

    /**
     * Saves the category.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $FormPostValue The values being posted back from the form.
     * @return int ID of the saved category.
     */
    public function save($FormPostValues) {
        // Define the primary key in this model's table.
        $this->defineSchema();

        // Get data from form
        $CategoryID = arrayValue('CategoryID', $FormPostValues);
        $NewName = arrayValue('Name', $FormPostValues, '');
        $UrlCode = arrayValue('UrlCode', $FormPostValues, '');
        $AllowDiscussions = arrayValue('AllowDiscussions', $FormPostValues, '');
        $CustomPermissions = (bool)GetValue('CustomPermissions', $FormPostValues);
        $CustomPoints = val('CustomPoints', $FormPostValues, null);

        if (isset($FormPostValues['AllowedDiscussionTypes']) && is_array($FormPostValues['AllowedDiscussionTypes'])) {
            $FormPostValues['AllowedDiscussionTypes'] = serialize($FormPostValues['AllowedDiscussionTypes']);
        }

        // Is this a new category?
        $Insert = $CategoryID > 0 ? false : true;
        if ($Insert) {
            $this->AddInsertFields($FormPostValues);
        }

        $this->AddUpdateFields($FormPostValues);
        $this->Validation->applyRule('UrlCode', 'Required');
        $this->Validation->applyRule('UrlCode', 'UrlStringRelaxed');

        // Make sure that the UrlCode is unique among categories.
        $this->SQL->select('CategoryID')
            ->from('Category')
            ->where('UrlCode', $UrlCode);

        if ($CategoryID) {
            $this->SQL->where('CategoryID <>', $CategoryID);
        }

        if ($this->SQL->get()->numRows()) {
            $this->Validation->addValidationResult('UrlCode', 'The specified url code is already in use by another category.');
        }

        //	Prep and fire event.
        $this->EventArguments['FormPostValues'] = &$FormPostValues;
        $this->EventArguments['CategoryID'] = $CategoryID;
        $this->fireEvent('BeforeSaveCategory');

        // Validate the form posted values
        if ($this->validate($FormPostValues, $Insert)) {
            $Fields = $this->Validation->SchemaValidationFields();
            $Fields = RemoveKeyFromArray($Fields, 'CategoryID');
            $AllowDiscussions = arrayValue('AllowDiscussions', $Fields) == '1' ? true : false;
            $Fields['AllowDiscussions'] = $AllowDiscussions ? '1' : '0';

            if ($Insert === false) {
                $OldCategory = $this->getID($CategoryID, DATASET_TYPE_ARRAY);
                if (null === val('AllowDiscussions', $FormPostValues, null)) {
                    $AllowDiscussions = $OldCategory['AllowDiscussions']; // Force the allowdiscussions property
                }                $Fields['AllowDiscussions'] = $AllowDiscussions ? '1' : '0';

                // Figure out custom points.
                if ($CustomPoints !== null) {
                    if ($CustomPoints) {
                        $Fields['PointsCategoryID'] = $CategoryID;
                    } else {
                        $Parent = self::categories(val('ParentCategoryID', $Fields, $OldCategory['ParentCategoryID']));
                        $Fields['PointsCategoryID'] = val('PointsCategoryID', $Parent, 0);
                    }
                }

                $this->update($Fields, array('CategoryID' => $CategoryID));

                // Check for a change in the parent category.
                if (isset($Fields['ParentCategoryID']) && $OldCategory['ParentCategoryID'] != $Fields['ParentCategoryID']) {
                    $this->RebuildTree();
                } else {
                    $this->SetCache($CategoryID, $Fields);
                }
            } else {
                $CategoryID = $this->insert($Fields);

                if ($CategoryID) {
                    if ($CustomPermissions) {
                        $this->SQL->put('Category', array('PermissionCategoryID' => $CategoryID), array('CategoryID' => $CategoryID));
                    }
                    if ($CustomPoints) {
                        $this->SQL->put('Category', array('PointsCategoryID' => $CategoryID), array('CategoryID' => $CategoryID));
                    }
                }

                $this->RebuildTree(); // Safeguard to make sure that treeleft and treeright cols are added
            }

            // Save the permissions
            if ($CategoryID) {
                // Check to see if this category uses custom permissions.
                if ($CustomPermissions) {
                    $PermissionModel = Gdn::permissionModel();
                    $Permissions = $PermissionModel->PivotPermissions(val('Permission', $FormPostValues, array()), array('JunctionID' => $CategoryID));
                    $PermissionModel->SaveAll($Permissions, array('JunctionID' => $CategoryID, 'JunctionTable' => 'Category'));

                    if (!$Insert) {
                        // Figure out my last permission and tree info.
                        $Data = $this->SQL->select('PermissionCategoryID, TreeLeft, TreeRight')->from('Category')->where('CategoryID', $CategoryID)->get()->firstRow(DATASET_TYPE_ARRAY);

                        // Update this category's permission.
                        $this->SQL->put('Category', array('PermissionCategoryID' => $CategoryID), array('CategoryID' => $CategoryID));

                        // Update all of my children that shared my last category permission.
                        $this->SQL->put(
                            'Category',
                            array('PermissionCategoryID' => $CategoryID),
                            array('TreeLeft >' => $Data['TreeLeft'], 'TreeRight <' => $Data['TreeRight'], 'PermissionCategoryID' => $Data['PermissionCategoryID'])
                        );

                        self::ClearCache();
                    }
                } elseif (!$Insert) {
                    // Figure out my parent's permission.
                    $NewPermissionID = $this->SQL
                        ->select('p.PermissionCategoryID')
                        ->from('Category c')
                        ->join('Category p', 'c.ParentCategoryID = p.CategoryID')
                        ->where('c.CategoryID', $CategoryID)
                        ->get()->value('PermissionCategoryID', 0);

                    if ($NewPermissionID != $CategoryID) {
                        // Update all of my children that shared my last permission.
                        $this->SQL->put(
                            'Category',
                            array('PermissionCategoryID' => $NewPermissionID),
                            array('PermissionCategoryID' => $CategoryID)
                        );

                        self::ClearCache();
                    }

                    // Delete my custom permissions.
                    $this->SQL->delete(
                        'Permission',
                        array('JunctionTable' => 'Category', 'JunctionColumn' => 'PermissionCategoryID', 'JunctionID' => $CategoryID)
                    );
                }
            }

            // Force the user permissions to refresh.
            Gdn::userModel()->ClearPermissions();

            // $this->RebuildTree();
        } else {
            $CategoryID = false;
        }

        return $CategoryID;
    }

    /**
     * Grab the Category IDs of the tree.
     *
     * @since 2.0.18
     * @access public
     * @param int $CategoryID
     * @param mixed $Set
     */
    public function saveUserTree($CategoryID, $Set) {
        $Categories = $this->GetSubtree($CategoryID);
        foreach ($Categories as $Category) {
            $this->SQL->replace(
                'UserCategory',
                $Set,
                array('UserID' => Gdn::session()->UserID, 'CategoryID' => $Category['CategoryID'])
            );
        }
        $Key = 'UserCategory_'.Gdn::session()->UserID;
        Gdn::cache()->Remove($Key);
    }

    /**
     * Grab and update the category cache
     *
     * @since 2.0.18
     * @access public
     * @param int $ID
     * @param array $Data
     */
    public static function setCache($ID = false, $Data = false) {
        $Categories = Gdn::cache()->get(self::CACHE_KEY);
        self::$Categories = null;

        if (!$Categories) {
            return;
        }

        // Extract actual category list, remove key if malformed
        if (!$ID || !is_array($Categories) || !array_key_exists('categories', $Categories)) {
            Gdn::cache()->Remove(self::CACHE_KEY);
            return;
        }
        $Categories = $Categories['categories'];

        // Check for category in list, otherwise remove key if not found
        if (!array_key_exists($ID, $Categories)) {
            Gdn::cache()->Remove(self::CACHE_KEY);
            return;
        }

        $Category = $Categories[$ID];
        $Category = array_merge($Category, $Data);
        $Categories[$ID] = $Category;

        // Update memcache entry
        self::$Categories = $Categories;
        unset($Categories);
        self::BuildCache($ID);

        self::JoinUserData(self::$Categories, true);
    }

    public function setField($ID, $Property, $Value = false) {
        if (!is_array($Property)) {
            $Property = array($Property => $Value);
        }

        if (isset($Property['AllowedDiscussionTypes']) && is_array($Property['AllowedDiscussionTypes'])) {
            $Property['AllowedDiscussionTypes'] = serialize($Property['AllowedDiscussionTypes']);
        }

        $this->SQL->put($this->Name, $Property, array('CategoryID' => $ID));

        // Set the cache.
        self::SetCache($ID, $Property);

        return $Property;
    }

    public static function setLocalField($ID, $Property, $Value) {
        // Make sure the field is here.
        if (!self::$Categories === null) {
            self::categories(-1);
        }

        if (isset(self::$Categories[$ID])) {
            self::$Categories[$ID][$Property] = $Value;
            return true;
        }
        return false;
    }

    public function setRecentPost($CategoryID) {
        $Row = $this->SQL->getWhere('Discussion', array('CategoryID' => $CategoryID), 'DateLastComment', 'desc', 1)->firstRow(DATASET_TYPE_ARRAY);

        $Fields = array('LastCommentID' => null, 'LastDiscussionID' => null);

        if ($Row) {
            $Fields['LastCommentID'] = $Row['LastCommentID'];
            $Fields['LastDiscussionID'] = $Row['DiscussionID'];
        }
        $this->setField($CategoryID, $Fields);
        $this->SetCache($CategoryID, array('LastTitle' => null, 'LastUserID' => null, 'LastDateInserted' => null, 'LastUrl' => null));
    }

    /**
     * If looking at the root node, make sure it exists and that the
     * nested set columns exist in the table.
     *
     * @since 2.0.15
     * @access public
     */
    public function applyUpdates() {
        if (!c('Vanilla.NestedCategoriesUpdate')) {
            // Add new columns
            $Construct = Gdn::database()->Structure();
            $Construct->table('Category')
                ->column('TreeLeft', 'int', true)
                ->column('TreeRight', 'int', true)
                ->column('Depth', 'int', true)
                ->column('CountComments', 'int', '0')
                ->column('LastCommentID', 'int', true)
                ->set(0, 0);

            // Insert the root node
            if ($this->SQL->getWhere('Category', array('CategoryID' => -1))->numRows() == 0) {
                $this->SQL->insert('Category', array('CategoryID' => -1, 'TreeLeft' => 1, 'TreeRight' => 4, 'Depth' => 0, 'InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Gdn_Format::toDateTime(), 'DateUpdated' => Gdn_Format::toDateTime(), 'Name' => t('Root Category Name', 'Root'), 'UrlCode' => '', 'Description' => t('Root Category Description', 'Root of category tree. Users should never see this.')));
            }

            // Build up the TreeLeft & TreeRight values.
            $this->RebuildTree();

            saveToConfig('Vanilla.NestedCategoriesUpdate', 1);
        }
    }

    /**
     * Modifies category data before it is returned.
     *
     * Adds CountAllDiscussions column to each category representing the sum of
     * discussions within this category as well as all subcategories.
     *
     * @since 2.0.17
     * @access public
     *
     * @param object $Data SQL result.
     */
    public static function addCategoryColumns($Data) {
        $Result = &$Data->result();
        $Result2 = $Result;
        foreach ($Result as &$Category) {
            if (!property_exists($Category, 'CountAllDiscussions')) {
                $Category->CountAllDiscussions = $Category->CountDiscussions;
            }

            if (!property_exists($Category, 'CountAllComments')) {
                $Category->CountAllComments = $Category->CountComments;
            }

            // Calculate the following field.
            $Following = !((bool)GetValue('Archived', $Category) || (bool)GetValue('Unfollow', $Category));
            $Category->Following = $Following;

            $DateMarkedRead = val('DateMarkedRead', $Category);
            $UserDateMarkedRead = val('UserDateMarkedRead', $Category);

            if (!$DateMarkedRead) {
                $DateMarkedRead = $UserDateMarkedRead;
            } elseif ($UserDateMarkedRead && Gdn_Format::toTimestamp($UserDateMarkedRead) > Gdn_Format::ToTimeStamp($DateMarkedRead))
                $DateMarkedRead = $UserDateMarkedRead;

            // Set appropriate Last* columns.
            setValue('LastTitle', $Category, val('LastDiscussionTitle', $Category, null));
            $LastDateInserted = val('LastDateInserted', $Category, null);

            if (val('LastCommentUserID', $Category) == null) {
                setValue('LastCommentUserID', $Category, val('LastDiscussionUserID', $Category, null));
                setValue('DateLastComment', $Category, val('DateLastDiscussion', $Category, null));
                setValue('LastUserID', $Category, val('LastDiscussionUserID', $Category, null));

                $LastDiscussion = arrayTranslate($Category, array(
                    'LastDiscussionID' => 'DiscussionID',
                    'CategoryID' => 'CategoryID',
                    'LastTitle' => 'Name'));

                setValue('LastUrl', $Category, DiscussionUrl($LastDiscussion, false, '/').'#latest');

                if (is_null($LastDateInserted)) {
                    setValue('LastDateInserted', $Category, val('DateLastDiscussion', $Category, null));
                }
            } else {
                $LastDiscussion = arrayTranslate($Category, array(
                    'LastDiscussionID' => 'DiscussionID',
                    'CategoryID' => 'CategoryID',
                    'LastTitle' => 'Name'
                ));

                setValue('LastUserID', $Category, val('LastCommentUserID', $Category, null));
                setValue('LastUrl', $Category, DiscussionUrl($LastDiscussion, false, '/').'#latest');

                if (is_null($LastDateInserted)) {
                    setValue('LastDateInserted', $Category, val('DateLastComment', $Category, null));
                }
            }

            $LastDateInserted = val('LastDateInserted', $Category, null);
            if ($DateMarkedRead) {
                if ($LastDateInserted) {
                    $Category->Read = Gdn_Format::toTimestamp($DateMarkedRead) >= Gdn_Format::toTimestamp($LastDateInserted);
                } else {
                    $Category->Read = true;
                }
            } else {
                $Category->Read = false;
            }

            foreach ($Result2 as $Category2) {
                if ($Category2->TreeLeft > $Category->TreeLeft && $Category2->TreeRight < $Category->TreeRight) {
                    $Category->CountAllDiscussions += $Category2->CountDiscussions;
                    $Category->CountAllComments += $Category2->CountComments;
                }
            }
        }
    }

    public static function categoryUrl($Category, $Page = '', $WithDomain = true) {
        if (function_exists('CategoryUrl')) {
            return CategoryUrl($Category, $Page, $WithDomain);
        }

        if (is_string($Category)) {
            $Category = CategoryModel::categories($Category);
        }
        $Category = (array)$Category;

        $Result = '/categories/'.rawurlencode($Category['UrlCode']);
        if ($Page && $Page > 1) {
            $Result .= '/p'.$Page;
        }
        return url($Result, $WithDomain);
    }
}
