<?php
/**
 * Category model
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
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

    /**
     * @var CategoryModel $instance;
     */
    private static $instance;

    /** @var bool Whether to allow the calculation of Headings in the `calculateDisplayAs` method */
    private static $stopHeadingsCalculation = false;

    /**
     * @var CategoryCollection $collection;
     */
    private $collection;

    /** @var bool */
    public $Watching = false;

    /** @var array Merged Category data, including Pure + UserCategory. */
    public static $Categories = null;

    /** @var array Valid values => labels for DisplayAs column. */
    private static $displayAsOptions = [
        'Discussions' => 'Discussions',
        'Categories' => 'Nested',
        'Flat' => 'Flat',
        'Heading' => 'Heading'
    ];

    /** @var bool Whether or not to explicitly shard the categories cache. */
    public static $ShardCache = false;

    /**
     * @var bool Whether or not to join users to recent posts.
     * Forums with a lot of categories may need to optimize using this setting and simpler views.
     */
    public $JoinRecentUsers = true;

    /**
     * @var bool Whether or not to join GDN_UserCategoryInformation in {@link CategoryModel::calculateUser()}.
     */
    private $joinUserCategory = false;

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct() {
        parent::__construct('Category');
        $this->collection = $this->createCollection();
    }

    /**
     * The shared instance of this object.
     *
     * @return CategoryModel Returns the instance.
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new CategoryModel();
        }
        return self::$instance;
    }


    /**
     * Checks the allowed discussion types on a category.
     *
     * @param array $PermissionCategory The permission category of the category.
     * @param array $category The category we're checking the permission on.
     * @return array The allowed discussion types on the category.
     * @throws Exception
     */
    public static function allowedDiscussionTypes($PermissionCategory, $category = []) {
        $PermissionCategory = self::permissionCategory($PermissionCategory);
        $Allowed = val('AllowedDiscussionTypes', $PermissionCategory);
        $AllTypes = DiscussionModel::discussionTypes();
        if (empty($Allowed) || !is_array($Allowed)) {
            $allowedTypes = $AllTypes;
        } else {
            $allowedTypes = array_intersect_key($AllTypes, array_flip($Allowed));
        }
        Gdn::pluginManager()->EventArguments['AllowedDiscussionTypes'] = &$allowedTypes;
        Gdn::pluginManager()->EventArguments['Category'] = $category;
        Gdn::pluginManager()->EventArguments['PermissionCategory'] = $PermissionCategory;
        Gdn::pluginManager()->fireAs('CategoryModel')->fireEvent('AllowedDiscussionTypes');

        return $allowedTypes;
    }

    /**
     * Load all of the categories from the cache or the database.
     */
    private static function loadAllCategories() {
        Logger::log(Logger::DEBUG, "CategoryModel::loadAllCategories");

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
                self::buildCache();

                // Release lock
                if ($haveRebuildLock) {
                    self::rebuildLock(true);
                }
            }
        }

        if (self::$Categories) {
            self::joinUserData(self::$Categories, true);
        }
    }

    /**
     * Calculate the user-specific information on a category.
     *
     * @param array &$category The category to calculate.
     * @param bool|null $addUserCategory
     */
    private function calculateUser(&$category, $addUserCategory = null) {
        $category['Url'] = url($category['Url'], '//');
        if ($Photo = val('Photo', $category)) {
            $category['PhotoUrl'] = Gdn_Upload::url($Photo);
        }

        if (!empty($category['LastUrl'])) {
            $category['LastUrl'] = url($category['LastUrl'], '//');
        }

        $category['PermsDiscussionsView'] = self::checkPermission($category, 'Vanilla.Discussions.View');
        $category['PermsDiscussionsAdd'] = self::checkPermission($category, 'Vanilla.Discussions.Add');
        $category['PermsDiscussionsEdit'] = self::checkPermission($category, 'Vanilla.Discussions.Edit');
        $category['PermsCommentsAdd'] = self::checkPermission($category, 'Vanilla.Comments.Add');

        $Code = $category['UrlCode'];
        $category['Name'] = translateContent("Categories.".$Code.".Name", $category['Name']);
        $category['Description'] = translateContent("Categories.".$Code.".Description", $category['Description']);

        if ($addUserCategory || ($addUserCategory === null && $this->joinUserCategory())) {
            $userCategories = $this->getUserCategories();

            $dateMarkedRead = val('DateMarkedRead', $category);
            $userData = val($category['CategoryID'], $userCategories);
            if ($userData) {
                $userDateMarkedRead = $userData['DateMarkedRead'];

                if (!$dateMarkedRead ||
                    ($userDateMarkedRead && Gdn_Format::toTimestamp($userDateMarkedRead) > Gdn_Format::toTimestamp($dateMarkedRead))) {

                    $category['DateMarkedRead'] = $userDateMarkedRead;
                    $dateMarkedRead = $userDateMarkedRead;
                }

                $category['Unfollow'] = $userData['Unfollow'];
            } else {
                $category['Unfollow'] = false;
            }

            // Calculate the following field.
            $Following = !((bool)val('Archived', $category) || (bool)val('Unfollow', $userData, false));
            $category['Following'] = $Following;

            // Calculate the read field.
            if (strcasecmp($category['DisplayAs'], 'heading') === 0) {
                $category['Read'] = false;
            } elseif ($dateMarkedRead) {
                if (val('LastDateInserted', $category)) {
                    $category['Read'] = Gdn_Format::toTimestamp($dateMarkedRead) >= Gdn_Format::toTimestamp($category['LastDateInserted']);
                } else {
                    $category['Read'] = true;
                }
            } else {
                $category['Read'] = false;
            }
        }
    }

    /**
     * Get the per-category information for the current user.
     *
     * @return array|mixed
     */
    private function getUserCategories() {
        if (Gdn::session()->UserID) {
            $key = 'UserCategory_'.Gdn::session()->UserID;
            $userData = Gdn::cache()->get($key);
            if ($userData === Gdn_Cache::CACHEOP_FAILURE) {
                $sql = clone $this->SQL;
                $sql->reset();

                $userData = $sql->getWhere('UserCategory', ['UserID' => Gdn::session()->UserID])->resultArray();
                $userData = array_column($userData, null, 'CategoryID');
                Gdn::cache()->store($key, $userData);
                return $userData;
            }
            return $userData;
        } else {
            $userData = [];
            return $userData;
        }
    }

    /**
     * Get the display type for the root category.
     *
     * @return string
     */
    public static function getRootDisplayAs() {
        return c('Vanilla.RootCategory.DisplayAs', 'Categories');
    }

    /**
     *
     *
     * @param bool $honorHideAllDiscussion Whether or not the HideAllDiscussions flag will be checked on categories.
     * @return array|bool Category IDs or true if all categories are watched.
     */
    public static function categoryWatch($honorHideAllDiscussion = true) {
        $Categories = self::categories();
        $AllCount = count($Categories);

        $Watch = array();

        foreach ($Categories as $CategoryID => $Category) {
            if ($honorHideAllDiscussion && val('HideAllDiscussions', $Category)) {
                continue;
            }

            if ($Category['PermsDiscussionsView'] && $Category['Following']) {
                $Watch[] = $CategoryID;
            }
        }

        Gdn::pluginManager()->EventArguments['CategoryIDs'] = &$Watch;
        Gdn::pluginManager()->fireAs('CategoryModel')->fireEvent('CategoryWatch');

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
        if ((is_int($ID) || is_string($ID)) && empty(self::$Categories)) {
            $category = self::instance()->getOne($ID);
            return $category;
        }

        if (self::$Categories == null) {
            self::loadAllCategories();

            if (self::$Categories === null) {
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
     * Request rebuild mutex.
     *
     * Allows competing instances to "vote" on the process that gets to rebuild
     * the category cache.
     *
     * @param bool $release
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
     * Build and augment the category cache.
     *
     * @param int $CategoryID The category to
     *
     */
    protected static function buildCache($CategoryID = null) {
        self::calculateData(self::$Categories);
        self::joinRecentPosts(self::$Categories, $CategoryID);

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
     * Calculate the dynamic fields of a category.
     *
     * @param array &$category The category to calculate.
     */
    private static function calculate(&$category) {
        $category['CountAllDiscussions'] = $category['CountDiscussions'];
        $category['CountAllComments'] = $category['CountComments'];
        $category['Url'] = self::categoryUrl($category, false, '/');
        if (val('Photo', $category)) {
            $category['PhotoUrl'] = Gdn_Upload::url($category['Photo']);
        } else {
            $category['PhotoUrl'] = '';
        }

        self::calculateDisplayAs($category);

        if (!val('CssClass', $category)) {
            $category['CssClass'] = 'Category-'.$category['UrlCode'];
        }

        if (isset($category['AllowedDiscussionTypes']) && is_string($category['AllowedDiscussionTypes'])) {
            $category['AllowedDiscussionTypes'] = dbdecode($category['AllowedDiscussionTypes']);
        }
    }

    /**
     * Maintains backwards compatibilty with `DisplayAs: Default`-type categories by calculating the DisplayAs
     * property into an expected DisplayAs type: Categories, Heading, or Discussions. Respects the now-deprecated
     * config setting `Vanilla.Categories.DoHeadings`. Once we can be sure that all instances have their
     * categories' DisplayAs properties explicitly set in the database (i.e., not `Default`) we can deprecate/remove
     * this function.
     *
     * @param $category The category to calculate the DisplayAs property for.
     */
    public static function calculateDisplayAs(&$category) {
        if ($category['DisplayAs'] === 'Default') {
            if ($category['Depth'] <= c('Vanilla.Categories.NavDepth', 0)) {
                $category['DisplayAs'] = 'Categories';
            } elseif (
                $category['Depth'] == (c('Vanilla.Categories.NavDepth', 0) + 1)
                && c('Vanilla.Categories.DoHeadings')
                && !self::$stopHeadingsCalculation
            ) {
                $category['DisplayAs'] = 'Heading';
            } else {
                $category['DisplayAs'] = 'Discussions';
            }
        }
    }

    /**
     * Checks to see if the passed category depth is greater than the NavDepth and if so, stops calculating
     * Headings as a DisplayAs property in the `calculateDisplayAs` method. Once we can be sure that all
     * instances have their categories' DisplayAs properties explicitly set in the database (i.e., not `Default`)
     * we can deprecate/remove this function.
     *
     * @param bool $stopHeadingCalculation
     * @return CategoryModel
     */
    public function setStopHeadingsCalculation($stopHeadingCalculation) {
        self::$stopHeadingsCalculation = $stopHeadingCalculation;
        return $this;
    }

    /**
     * Build calculated category data on the passed set.
     *
     * @since 2.0.18
     * @access public
     * @param array $Data Dataset.
     */
    private static function calculateData(&$Data) {
        foreach ($Data as &$Category) {
            self::calculate($Category);
        }

        $Keys = array_reverse(array_keys($Data));
        foreach ($Keys as $Key) {
            $Cat = $Data[$Key];
            $ParentID = $Cat['ParentCategoryID'];

            if (isset($Data[$ParentID]) && $ParentID != $Key) {
                $Data[$ParentID]['CountAllDiscussions'] += $Cat['CountAllDiscussions'];
                $Data[$ParentID]['CountAllComments'] += $Cat['CountAllComments'];
                if (empty($Data[$ParentID]['ChildIDs'])) {
                    $Data[$ParentID]['ChildIDs'] = [];
                }
                array_unshift($Data[$ParentID]['ChildIDs'], $Key);
            }
        }
    }

    /**
     *
     */
    public static function clearCache() {
        Gdn::cache()->remove(self::CACHE_KEY);
        self::instance()->collection->flushCache();
    }

    /**
     *
     */
    public static function clearUserCache() {
        $Key = 'UserCategory_'.Gdn::session()->UserID;
        Gdn::cache()->remove($Key);
    }

    /**
     * @param $Column
     * @return array
     */
    public function counts($Column) {
        $Result = array('Complete' => true);
        switch ($Column) {
            case 'CountDiscussions':
                $this->Database->query(DBAModel::getCountSQL('count', 'Category', 'Discussion'));
                break;
            case 'CountComments':
                $this->Database->query(DBAModel::getCountSQL('sum', 'Category', 'Discussion', $Column, 'CountComments'));
                break;
            case 'CountAllDiscussions':
            case 'CountAllComments':
                self::recalculateAggregateCounts();
                break;
            case 'LastDiscussionID':
                $this->Database->query(DBAModel::getCountSQL('max', 'Category', 'Discussion'));
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
                $CommentIDs = array_column($Data, 'LastCommentID');

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
        self::clearCache();
        return $Result;
    }

    /**
     *
     *
     * @return mixed
     */
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
        $permissionCategories = static::getByPermission('Discussions.View');

        if ($permissionCategories === true) {
            return $categoryIDs;
        } else {
            $permissionCategoryIDs = array_keys($permissionCategories);
            // Reindex the result.  array_intersect leaves the original, potentially incomplete, numeric indexes.
            return array_values(array_intersect($categoryIDs, $permissionCategoryIDs));
        }
    }

    /**
     * Check a category's permission.
     *
     * @param int|array|object $category The category to check.
     * @param string|array $permission The permission(s) to check.
     * @param bool $fullMatch Whether or not the permission has to be a full match.
     * @return bool Returns **true** if the current user has the permission or **false** otherwise.
     */
    public static function checkPermission($category, $permission, $fullMatch = true) {
        if (is_numeric($category)) {
            $category = static::categories($category);
        }
        
        $permissionCategoryID = val('PermissionCategoryID', $category, -1);

        $result = Gdn::session()->checkPermission($permission, $fullMatch, 'Category', $permissionCategoryID)
            || Gdn::session()->checkPermission($permission, $fullMatch, 'Category', val('CategoryID', $category));

        return $result;
    }

    /**
     * Get the child categories of a category.
     *
     * @param int $categoryID The category to get the children of.
     */
    public static function getChildren($categoryID) {
        $categories = self::instance()->collection->getChildren($categoryID);
        return $categories;
    }

    /**
     * Cast a category ID or slug to be passed to the various {@link CategoryCollection} methods.
     *
     * @param int|string|null $category The category ID or slug.
     * @return int|string|null Returns the cast category ID.
     */
    private static function castID($category) {
        if (empty($category)) {
            return null;
        } elseif (is_numeric($category)) {
            return (int)$category;
        } else {
            return (string)$category;
        }
    }

    /**
     * Get a category tree based on, but not including a parent category.
     *
     * @param int|string $id The parent category ID or slug.
     * @param array $options See {@link CategoryCollection::getTree()}.
     * @return array Returns an array of categories with child categories in the **Children** key.
     */
    public function getChildTree($id, $options = []) {
        $category = $this->getOne($id);

        $tree = $this->collection->getTree((int)val('CategoryID', $category), $options);
        self::filterChildren($tree);
        return $tree;
    }

    /**
     * @param int|string $id The parent category ID or slug.
     * @param int|null $offset Offset results by given value.
     * @param int|null $limit Total number of results should not exceed this value.
     * @param string|null $filter Restrict results to only those with names matching this value, if provided.
     * @param string $orderFields
     * @param string $orderDirection
     * @return array
     */
    public function getTreeAsFlat($id, $offset = null, $limit = null, $filter = null, $orderFields = 'Name', $orderDirection = 'asc') {
        $query = $this->SQL
            ->from('Category')
            ->where('DisplayAs <>', 'Heading')
            ->where('ParentCategoryID', $id)
            ->limit($limit, $offset)
            ->orderBy($orderFields, $orderDirection);

        if ($filter) {
            $query->like('Name', $filter);
        }

        $categoryTree = $query->get()->resultArray();
        self::calculateData($categoryTree);
        self::joinUserData($categoryTree);

        foreach ($categoryTree as &$category) {
            // Fix the depth to be relative, not global.
            $category['Depth'] = 1;

            // We don't have children, but trees are expected to have this key.
            $category['Children'] = [];
        }

        return $categoryTree;
    }

    /**
     * Recursively remove children from categories configured to display as "Categories" or "Flat".
     *
     * @param array $categories
     * @param string $childField
     */
    public static function filterChildren(&$categories, $childField = 'Children') {
        foreach ($categories as &$category) {
            $children = &$category[$childField];
            if (in_array($category['DisplayAs'], ['Categories', 'Flat'])) {
                    $children = [];
                } elseif (!empty($children)) {
                    static::filterChildren($children);
                }
         }
      }

    /**
     * Filter a category tree to only the followed categories.
     *
     * @param array $categories The category tree to filter.
     * @return array Returns a category tree.
     */
    public function filterFollowing($categories) {
        $result = [];
        foreach ($categories as $category) {
            if (val('Following', $category)) {
                if (!empty($category['Children'])) {
                    $category['Children'] = $this->filterFollowing($category['Children']);
                }
                $result[] = $category;
            }
        }
        return $result;
    }

    /**
     *
     *
     * @param string $Permission
     * @param null $CategoryID
     * @param array $Filter
     * @param array $PermFilter
     * @return array
     */
    public static function getByPermission($Permission = 'Discussions.Add', $CategoryID = null, $Filter = array(), $PermFilter = array()) {
        static $Map = array('Discussions.Add' => 'PermsDiscussionsAdd', 'Discussions.View' => 'PermsDiscussionsView');
        $Field = $Map[$Permission];
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

                if ($Category['DisplayAs'] == 'Heading') {
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

    /**
     *
     *
     * @param $Row
     * @param $Where
     * @return bool
     */
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
     * @param array|Gdn_DataSet &$Data Dataset.
     * @param string $Column Name of database column.
     * @param array $Options The 'Join' key may contain array of columns to join on.
     * @since 2.0.18
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
     * Gather all of the last discussion and comment IDs from the categories.
     *
     * @param array $categoryTree A nested array of categories.
     * @param array &$result Where to store the result.
     */
    private function gatherLastIDs($categoryTree, &$result = null) {
        if ($result === null) {
            $result = [];
        }

        foreach ($categoryTree as $category) {
            $result["{$category['LastDiscussionID']}/{$category['LastCommentID']}"] = [
                'DiscussionID' => $category['LastDiscussionID'],
                'CommentID' => $category['LastCommentID']
            ];

            if (!empty($category['Children'])) {
                $this->gatherLastIDs($category['Children'], $result);
            }
        }
    }

    /**
     * Join recent posts and users to a category tree.
     *
     * @param array &$categoryTree A category tree obtained with {@link CategoryModel::getChildTree()}.
     */
    public function joinRecent(&$categoryTree) {
        // Gather all of the IDs from the posts.
        $this->gatherLastIDs($categoryTree, $ids);
        $discussionIDs = array_unique(array_column($ids, 'DiscussionID'));
        $commentIDs = array_filter(array_unique(array_column($ids, 'CommentID')));

        if (!empty($discussionIDs)) {
            $discussions = $this->SQL->getWhere('Discussion', ['DiscussionID' => $discussionIDs])->resultArray();
            $discussions = array_column($discussions, null, 'DiscussionID');
        } else {
            $discussions = [];
        }

        if (!empty($commentIDs)) {
            $comments = $this->SQL->getWhere('Comment', ['CommentID' => $commentIDs])->resultArray();
            $comments = array_column($comments, null, 'CommentID');
        } else {
            $comments = [];
        }

        $userIDs = [];
        foreach ($ids as $row) {
            if (!empty($row['CommentID']) && !empty($comments[$row['CommentID']]['InsertUserID'])) {
                $userIDs[] = $comments[$row['CommentID']]['InsertUserID'];
            } elseif (!empty($row['DiscussionID']) && !empty($discussions[$row['DiscussionID']]['InsertUserID'])) {
                $userIDs[] = $discussions[$row['DiscussionID']]['InsertUserID'];
            }
        }
        // Just gather the users into the local cache.
        Gdn::userModel()->getIDs($userIDs);

        $this->joinRecentInternal($categoryTree, $discussions, $comments);
    }

    /**
     * This method supports {@link CategoryModel::joinRecent()}.
     *
     * @param array &$categoryTree The array of categories in tree format.
     * @param array $discussions An array of discussions indexed by discussion ID.
     * @param array $comments An array of comments indexed by comment ID.
     */
    private function joinRecentInternal(&$categoryTree, $discussions, $comments) {
        foreach ($categoryTree as &$category) {
            $discussion = val($category['LastDiscussionID'], $discussions, null);
            $comment = val($category['LastCommentID'], $comments, null);

            if (!empty($discussion)) {
                $category['LastTitle'] = $discussion['Name'];
                $category['LastUrl'] = discussionUrl($discussion, false, '/').'#latest';
                $category['LastDiscussionUserID'] = $discussion['InsertUserID'];
            }

            if (!empty($comment)) {
                $category['LastUserID'] = $comment['InsertUserID'];
            } elseif (!empty($discussion)) {
                $category['LastUserID'] = $discussion['InsertUserID'];
            } else {
                $category['LastTitle'] = '';
                $category['LastUserID'] = null;
            }
            $user = Gdn::userModel()->getID($category['LastUserID']);
                foreach (['Name', 'Email', 'Photo'] as $field) {
                    $category['Last'.$field] = val($field, $user);
                }

            if (!empty($category['Children'])) {
                $this->joinRecentInternal($category['Children'], $discussions, $comments);
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

        $Discussions = null;

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
            if (!empty($Comments) && ($Comment = val($Row['LastCommentID'], $Comments))) {
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

        if ($AddUserCategory) {
            $UserData = self::instance()->getUserCategories();

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
                $Following = !((bool)val('Archived', $Category) || (bool)val('Unfollow', $Row, false));
                $Categories[$ID]['Following'] = $Following;

                // Calculate the read field.
                if ($Category['DisplayAs'] == 'Heading') {
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
        foreach ($IDs as $CID) {
            $Category = &$Categories[$CID];
            self::instance()->calculateUser($Category);
        }
    }

    /**
     * Delete a category.
     *
     * {@inheritdoc}
     */
    public function delete($where = [], $options = []) {
        if (is_numeric($where) || is_object($where)) {
            deprecated('CategoryModel->delete()', 'CategoryModel->deleteandReplace()');

            $result = $this->deleteAndReplace($where, $options);
            return $result;
        }

        throw new \BadMethodCallException("CategoryModel->delete() is not supported.", 400);
    }

    /**
     * Delete a category.
     *
     * @param int $categoryID The ID of the category to delete.
     * @param array $options An array of options to affect the behavior of the delete.
     *
     * - **newCategoryID**: The new category to point discussions to.
     * @return bool Returns **true** on success or **false** otherwise.
     */
    public function deleteID($categoryID, $options = []) {
        $result = $this->deleteAndReplace($categoryID, val('newCategoryID', $options));
        return $result;
    }

    /**
     * Delete a category.
     * If $newCategoryID is:
     *  - a valid categoryID, every discussions and sub-categories will be moved to the new category.
     *  - not a valid categoryID, all its discussions and sub-category will be recursively deleted.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object $category The category to delete
     * @param int $newCategoryID ID of the category that will replace this one.
     */
    public function deleteAndReplace($category, $newCategoryID) {
        static $recursionLevel = 0;

        // Coerce the category into an object for deletion.
        if (is_numeric($category)) {
            $category = $this->getID($category, DATASET_TYPE_OBJECT);
        }

        if (is_array($category)) {
            $category = (object)$category;
        }

        // Don't do anything if the required category object & properties are not defined.
        if (!is_object($category)
            || !property_exists($category, 'CategoryID')
            || !property_exists($category, 'ParentCategoryID')
            || !property_exists($category, 'AllowDiscussions')
            || !property_exists($category, 'Name')
            || $category->CategoryID <= 0
        ) {
            throw new \InvalidArgumentException(t('Invalid category for deletion.'), 400);
        } else {
            // Remove permissions related to category
            $PermissionModel = Gdn::permissionModel();
            $PermissionModel->delete(null, 'Category', 'CategoryID', $category->CategoryID);

            // If there is a replacement category...
            if ($newCategoryID > 0) {
                // Update children categories
                $this->SQL
                    ->update('Category')
                    ->set('ParentCategoryID', $newCategoryID)
                    ->where('ParentCategoryID', $category->CategoryID)
                    ->put();

                // Update permission categories.
                $this->SQL
                    ->update('Category')
                    ->set('PermissionCategoryID', $newCategoryID)
                    ->where('PermissionCategoryID', $category->CategoryID)
                    ->where('CategoryID <>', $category->CategoryID)
                    ->put();

                // Update discussions
                $this->SQL
                    ->update('Discussion')
                    ->set('CategoryID', $newCategoryID)
                    ->where('CategoryID', $category->CategoryID)
                    ->put();

                // Update the discussion count
                $Count = $this->SQL
                    ->select('DiscussionID', 'count', 'DiscussionCount')
                    ->from('Discussion')
                    ->where('CategoryID', $newCategoryID)
                    ->get()
                    ->firstRow()
                    ->DiscussionCount;

                if (!is_numeric($Count)) {
                    $Count = 0;
                }

                $this->SQL
                    ->update('Category')->set('CountDiscussions', $Count)
                    ->where('CategoryID', $newCategoryID)
                    ->put();

                // Update tags
                $this->SQL
                    ->update('Tag')
                    ->set('CategoryID', $newCategoryID)
                    ->where('CategoryID', $category->CategoryID)
                    ->put();

                $this->SQL
                    ->update('TagDiscussion')
                    ->set('CategoryID', $newCategoryID)
                    ->where('CategoryID', $category->CategoryID)
                    ->put();
            } else {
                // Delete comments in this category
                $this->SQL
                    ->from('Comment c')
                    ->join('Discussion d', 'c.DiscussionID = d.DiscussionID')
                    ->where('d.CategoryID', $category->CategoryID)
                    ->delete();

                // Delete discussions in this category
                $this->SQL->delete('Discussion', array('CategoryID' => $category->CategoryID));

                // Make inherited permission local permission
                $this->SQL
                    ->update('Category')
                    ->set('PermissionCategoryID', 0)
                    ->where('PermissionCategoryID', $category->CategoryID)
                    ->where('CategoryID <>', $category->CategoryID)
                    ->put();

                // Delete tags
                $this->SQL->delete('Tag', array('CategoryID' => $category->CategoryID));
                $this->SQL->delete('TagDiscussion', array('CategoryID' => $category->CategoryID));

                // Recursively delete child categories and their content.
                $children = self::flattenTree($this->collection->getTree($category->CategoryID));
                $recursionLevel++;
                foreach ($children as $child) {
                    self::deleteAndReplace($child, 0);
                }
                $recursionLevel--;
            }

            // Delete the category
            $this->SQL->delete('Category', array('CategoryID' => $category->CategoryID));
        }

        // Make sure to reorganize the categories after deletes
        if ($recursionLevel === 0) {
            $this->rebuildTree();
        }
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
     *
     * @param int $categoryID The unique ID of category we're getting data for.
     * @param string $datasetType Not used.
     * @param array $options Not used.
     * @return object|array SQL results.
     */
    public function getID($categoryID, $datasetType = DATASET_TYPE_OBJECT, $options = []) {
        $category = $this->SQL->getWhere('Category', array('CategoryID' => $categoryID))->firstRow($datasetType);
        if (val('AllowedDiscussionTypes', $category) && is_string(val('AllowedDiscussionTypes', $category))) {
            setValue('AllowedDiscussionTypes', $category, dbdecode(val('AllowedDiscussionTypes', $category)));
        }

        return $category;
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
     * @return array
     */
    public static function getDisplayAsOptions() {
        return self::$displayAsOptions;
    }

    /**
     * Get a single category from the collection.
     *
     * @param string|int $id The category code or ID.
     */
    private function getOne($id) {
        if (is_numeric($id)) {
            $id = (int)$id;
        }

        $category = $this->collection->get($id);
        return $category;
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
        $result = [];

        $category = self::instance()->getOne($categoryID);

        if (!isset($category)) {
            return $result;
        }

        // Build up the ancestor array by tracing back through parents.
        $result[$category['CategoryID']] = $category;
        $Max = 20;
        while ($category = self::instance()->getOne($category['ParentCategoryID'])) {
            // Check for an infinite loop.
            if ($Max <= 0) {
                break;
            }
            $Max--;

            if ($category['CategoryID'] == -1) {
                break;
            }

            if ($checkPermissions && !$category['PermsDiscussionsView']) {
                $category = self::instance()->getOne($category['ParentCategoryID']);
                continue;
            }

            // Return by ID or code.
            if (is_numeric($categoryID)) {
                $ID = $category['CategoryID'];
            } else {
                $ID = $category['UrlCode'];
            }

            if ($includeHeadings || $category['DisplayAs'] !== 'Heading') {
                $result[$ID] = $category;
            }
        }
        $result = array_reverse($result, true); // order for breadcrumbs
        return $result;
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
     * Get the role specific permissions for a category.
     *
     * @param int $categoryID The ID of the category to get the permissions for.
     * @return array Returns an array of permissions.
     */
    public function getRolePermissions($categoryID) {
        $permissions = Gdn::permissionModel()->getJunctionPermissions(['JunctionID' => $categoryID], 'Category');
        $result = [];

        foreach ($permissions as $perm) {
            $row = ['RoleID' => $perm['RoleID']];
            unset($perm['Name'], $perm['RoleID'], $perm['JunctionID'], $perm['JunctionTable'], $perm['JunctionColumn']);
            $row += $perm;
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Get the subtree starting at a given parent.
     *
     * @param string $parentCategory The ID or url code of the parent category.
     * @since 2.0.18
     * @param bool $includeParent Whether or not to include the parent in the result.
     * @return array An array of categories.
     */
    public static function getSubtree($parentCategory, $includeParent = true) {
        $parent = self::instance()->getOne($parentCategory);
        if ($parent === null) {
            return [];
        }

        if (val('DisplayAs', $parent) === 'Flat') {
            $categories = self::instance()->getTreeAsFlat($parent['CategoryID']);
        } else {
            $categories = self::instance()->collection->getTree($parent['CategoryID'], ['depth' => 10]);
            $categories = self::instance()->flattenTree($categories);
        }

        if ($includeParent) {
            $parent['Depth'] = 1;
            $result = [$parent['CategoryID'] => $parent];

            foreach ($categories as $category) {
                $category['Depth']--;
                $result[$category['CategoryID']] = $category;
            }
        } else {
            $result = array_column($categories, null, 'CategoryID');
        }
        return $result;
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

    /**
     *
     *
     * @param $Categories
     * @param null $Root
     * @return array
     */
    public static function makeTree($Categories, $Root = null) {
        $Result = array();

        $Categories = (array)$Categories;

        if ($Root) {
            $Result = self::instance()->collection->getTree(
                (int)val('CategoryID', $Root),
                ['depth' => self::instance()->getMaxDisplayDepth() ?: 10]
            );
            self::instance()->joinRecent($Result);
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

    /**
     *
     *
     * @param $Category
     * @param $Categories
     * @param null $DepthAdj
     * @return array
     */
    protected static function _MakeTreeChildren($Category, $Categories, $DepthAdj = null) {
        if (is_null($DepthAdj)) {
            $DepthAdj = -val('Depth', $Category);
        }

        $Result = array();
        $childIDs = val('ChildIDs', $Category);
        if (is_array($childIDs) && count($childIDs)) {
            foreach ($childIDs as $ID) {
                if (!isset($Categories[$ID])) {
                    continue;
                }
                $Row = (array)$Categories[$ID];
                $Row['Depth'] += $DepthAdj;
                $Row['Children'] = self::_MakeTreeChildren($Row, $Categories);
                $Result[] = $Row;
            }
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
        self::setCache();
        $this->collection->flushCache();
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
     * Save a subtree.
     *
     * @param array $subtree A nested array where each array contains a CategoryID and optional Children element.
     * @parem int $parentID Parent ID of the subtree
     */
    public function saveSubtree($subtree, $parentID) {
        $this->saveSubtreeInternal($subtree, $parentID);
    }

    /**
     * Save a subtree.
     *
     * @param array $subtree A nested array where each array contains a CategoryID and optional Children element.
     * @param int|null $parentID The parent ID of the subtree.
     * @param bool $rebuild Whether or not to rebuild the nested set after saving.
     */
    private function saveSubtreeInternal($subtree, $parentID = null, $rebuild = true) {
        $order = 1;
        foreach ($subtree as $row) {
            $save = [];
            $category = $this->collection->get((int)$row['CategoryID']);
            if (!$category) {
                $this->Validation->addValidationResult("CategoryID", "@Category {$row['CategoryID']} does not exist.");
                continue;
            }

            if ($category['Sort'] != $order) {
                $save['Sort'] = $order;
            }

            if ($parentID !== null && $category['ParentCategoryID'] != $parentID) {
                $save['ParentCategoryID'] = $parentID;

                if ($category['PermissionCategoryID'] != $category['CategoryID']) {
                    $parentCategory = $this->collection->get((int)$parentID);
                    $save['PermissionCategoryID'] = $parentCategory['PermissionCategoryID'];
                }
            }

            if (!empty($save)) {
                $this->setField($category['CategoryID'], $save);
            }

            if (!empty($row['Children'])) {
                $this->saveSubtreeInternal($row['Children'], $category['CategoryID'], false);
            }

            $order++;
        }
        if ($rebuild) {
            $this->rebuildTree(true);
        }

        self::clearCache();
    }

    /**
     * Saves the category tree based on a provided tree array. We are using the
     * Nested Set tree model.
     *
     *   TreeArray comes in the format:
     *   '0' ...
     *     'item_id' => "root"
     *     'parent_id' => "none"
     *     'depth' => "0"
     *     'left' => "1"
     *     'right' => "34"
     *   '1' ...
     *     'item_id' => "1"
     *     'parent_id' => "root"
     *     'depth' => "1"
     *     'left' => "2"
     *     'right' => "3"
     *   etc...
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
                if ($CategoryID != -1 && !valr("$ParentCategoryID.Touched", $PermTree)) {
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

                self::setCache($CategoryID, $Set);
                $Saves[] = array_merge(array('CategoryID' => $CategoryID), $Set);
            }
        }
        return $Saves;
    }

    /**
     * Whether or not to join information from GDN_UserCategory in {@link CategoryModel::calculateUser()}.
     *
     * You only need the information from this table when looking at categories in a list. Controllers should set this
     * flag if they are going to be sending read/unread information with the category.
     *
     * @return boolean Returns the joinUserCategory.
     */
    public function joinUserCategory() {
        return $this->joinUserCategory;
    }

    /**
     * Set whether or not to join information from GDN_UserCategory in {@link CategoryModel::calculateUser()}.
     *
     * @param boolean $joinUserCategory The new value to set.
     * @return CategoryModel Returns `$this` for fluent calls.
     */
    public function setJoinUserCategory($joinUserCategory) {
        $this->joinUserCategory = $joinUserCategory;
        return $this;
    }

    /**
     * Create a new category collection tied to this model.
     *
     * @return CategoryCollection Returns a new collection.
     */
    public function createCollection(Gdn_SQLDriver $sql = null, Gdn_Cache $cache = null) {
        if ($sql === null) {
            $sql = $this->SQL;
    }
        if ($cache === null) {
            $cache = Gdn::cache();
        }
        $collection = new CategoryCollection($sql, $cache);
        // Inject the calculator dependency.
        $collection->setConfig(Gdn::config());
        $collection->setStaticCalculator(function (&$category) {
            self::calculate($category);
        });

        $collection->setUserCalculator(function (&$category) {
            $this->calculateUser($category);
        });
        return $collection;
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
    protected function _treeSort($A, $B) {
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
     * @param array|false $Settings Additional settings to affect saving.
     * @return int ID of the saved category.
     */
    public function save($FormPostValues, $Settings = false) {
        // Define the primary key in this model's table.
        $this->defineSchema();

        // Get data from form
        $CategoryID = val('CategoryID', $FormPostValues);
        $NewName = val('Name', $FormPostValues, '');
        $UrlCode = val('UrlCode', $FormPostValues, '');
        $AllowDiscussions = val('AllowDiscussions', $FormPostValues, '');
        $CustomPermissions = (bool)val('CustomPermissions', $FormPostValues) || is_array(val('Permissions', $FormPostValues));
        $CustomPoints = val('CustomPoints', $FormPostValues, null);

        if (isset($FormPostValues['AllowedDiscussionTypes']) && is_array($FormPostValues['AllowedDiscussionTypes'])) {
            $FormPostValues['AllowedDiscussionTypes'] = dbencode($FormPostValues['AllowedDiscussionTypes']);
        }

        // Is this a new category?
        $Insert = $CategoryID > 0 ? false : true;
        if ($Insert) {
            $this->addInsertFields($FormPostValues);
        }

        $this->addUpdateFields($FormPostValues);

        // Add some extra validation to the url code if one is provided.
        if ($Insert || array_key_exists('UrlCode', $FormPostValues)) {
            $this->Validation->applyRule('UrlCode', 'Required');
            $this->Validation->applyRule('UrlCode', 'UrlStringRelaxed');

            // Url slugs cannot be the name of a CategoriesController method or fully numeric.
            $this->Validation->addRule('CategorySlug', 'regex:/^(?!(all|archives|discussions|index|table|[0-9]+)$).*/');
            $this->Validation->applyRule('UrlCode', 'CategorySlug', 'Url code cannot be numeric or the name of an internal method.');

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
        }

        if (isset($FormPostValues['ParentCategoryID'])) {
            if (empty($FormPostValues['ParentCategoryID'])) {
                $FormPostValues['ParentCategoryID'] = -1;
            } else {
                $parent = CategoryModel::categories($FormPostValues['ParentCategoryID']);
                if (!$parent) {
                    $FormPostValues['ParentCategoryID'] = -1;
                }
            }
        }

        //	Prep and fire event.
        $this->EventArguments['FormPostValues'] = &$FormPostValues;
        $this->EventArguments['CategoryID'] = $CategoryID;
        $this->fireEvent('BeforeSaveCategory');

        // Validate the form posted values.
        if ($this->validate($FormPostValues, $Insert)) {
            $Fields = $this->Validation->schemaValidationFields();
            $Fields = $this->coerceData($Fields);
            unset($Fields['CategoryID']);
            $Fields['AllowDiscussions'] = (bool)val('AllowDiscussions', $Fields);

            if ($Insert === false) {
                $OldCategory = $this->getID($CategoryID, DATASET_TYPE_ARRAY);
                if (null === val('AllowDiscussions', $FormPostValues, null)) {
                    $AllowDiscussions = $OldCategory['AllowDiscussions']; // Force the allowdiscussions property
                }
                $Fields['AllowDiscussions'] = (bool)$AllowDiscussions;

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
                    $this->rebuildTree();
                } else {
                    self::setCache($CategoryID, $Fields);
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

                $this->rebuildTree(); // Safeguard to make sure that treeleft and treeright cols are added
            }

            // Save the permissions
            if ($CategoryID) {
                // Check to see if this category uses custom permissions.
                if ($CustomPermissions) {
                    $permissionModel = Gdn::permissionModel();

                    if (is_array(val('Permissions', $FormPostValues))) {
                        // The permissions were posted in an API format provided by settings/getcategory
                        $permissions = val('Permissions', $FormPostValues);
                        foreach ($permissions as &$perm) {
                            $perm['JunctionTable'] = 'Category';
                            $perm['JunctionColumn'] = 'PermissionCategoryID';
                            $perm['JunctionID'] = $CategoryID;
                        }
                    } else {
                        // The permissions were posted in the web format provided by settings/addcategory and settings/editcategory
                        $permissions = $permissionModel->pivotPermissions(val('Permission', $FormPostValues, array()), array('JunctionID' => $CategoryID));
                    }
                    $permissionModel->saveAll($permissions, array('JunctionID' => $CategoryID, 'JunctionTable' => 'Category'));

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

                        self::clearCache();
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

                        self::clearCache();
                    }

                    // Delete my custom permissions.
                    $this->SQL->delete(
                        'Permission',
                        array('JunctionTable' => 'Category', 'JunctionColumn' => 'PermissionCategoryID', 'JunctionID' => $CategoryID)
                    );
                }
            }

            // Force the user permissions to refresh.
            Gdn::userModel()->clearPermissions();

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
        Gdn::cache()->remove($Key);
    }

    /**
     * Grab and update the category cache
     *
     * @since 2.0.18
     * @access public
     * @param int|bool $ID
     * @param array|bool $Data
     */
    public static function setCache($ID = false, $Data = false) {
        self::instance()->collection->refreshCache((int)$ID);

        $Categories = Gdn::cache()->get(self::CACHE_KEY);
        self::$Categories = null;

        if (!$Categories) {
            return;
        }

        // Extract actual category list, remove key if malformed
        if (!$ID || !is_array($Categories) || !array_key_exists('categories', $Categories)) {
            Gdn::cache()->remove(self::CACHE_KEY);
            return;
        }
        $Categories = $Categories['categories'];

        // Check for category in list, otherwise remove key if not found
        if (!array_key_exists($ID, $Categories)) {
            Gdn::cache()->remove(self::CACHE_KEY);
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

    /**
     * Set a property on a category.
     *
     * @param int $ID
     * @param array|string $Property
     * @param bool|false $Value
     * @return array|string
     */
    public function setField($ID, $Property, $Value = false) {
        if (!is_array($Property)) {
            $Property = array($Property => $Value);
        }

        if (isset($Property['AllowedDiscussionTypes']) && is_array($Property['AllowedDiscussionTypes'])) {
            $Property['AllowedDiscussionTypes'] = dbencode($Property['AllowedDiscussionTypes']);
        }

        $this->SQL->put($this->Name, $Property, array('CategoryID' => $ID));

        // Set the cache.
        self::setCache($ID, $Property);

        return $Property;
    }

    /**
     * Set a property of a currently-loaded category in memory.
     *
     * @param $ID
     * @param $Property
     * @param $Value
     * @return bool
     */
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

    /**
     *
     *
     * @param $CategoryID
     */
    public function setRecentPost($CategoryID) {
        $Row = $this->SQL->getWhere('Discussion', array('CategoryID' => $CategoryID), 'DateLastComment', 'desc', 1)->firstRow(DATASET_TYPE_ARRAY);

        $Fields = array('LastCommentID' => null, 'LastDiscussionID' => null);

        if ($Row) {
            $Fields['LastCommentID'] = $Row['LastCommentID'];
            $Fields['LastDiscussionID'] = $Row['DiscussionID'];
        }
        $this->setField($CategoryID, $Fields);
        self::setCache($CategoryID, array('LastTitle' => null, 'LastUserID' => null, 'LastDateInserted' => null, 'LastUrl' => null));
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
            $this->rebuildTree();

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
            $Following = !((bool)val('Archived', $Category) || (bool)val('Unfollow', $Category));
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

    /**
     * Build URL to a category page.
     *
     * @param $Category
     * @param string $Page
     * @param bool|true $WithDomain
     * @return string
     */
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

    /**
     * Get the category nav depth.
     *
     * @return int Returns the nav depth as an integer.
     */
    public function getNavDepth() {
        return (int)c('Vanilla.Categories.NavDepth', 0);
    }

    /**
     * Get the maximum display depth for categories.
     *
     * @return int Returns the display depth as an integer.
     */
    public function getMaxDisplayDepth() {
        return (int)c('Vanilla.Categories.MaxDisplayDepth', 3);
    }

    /**
     * Recalculate the dynamic tree columns in the category.
     */
    public function recalculateTree() {
        $px = $this->Database->DatabasePrefix;

        // Update the child counts and reset the depth.
        $sql = <<<SQL
update {$px}Category c
join (
	select ParentCategoryID, count(ParentCategoryID) as CountCategories
	from {$px}Category
	group by ParentCategoryID
) c2
	on c.CategoryID = c2.ParentCategoryID
set c.CountCategories = c2.CountCategories,
    c.Depth = 0;
SQL;
        $this->Database->query($sql);

        // Update the first pass of the categories.
        $this->Database->query(<<<SQL
update {$px}Category p
join {$px}Category c
	on c.ParentCategoryID = p.CategoryID
set c.Depth = p.Depth + 1
where p.CategoryID = -1 and c.CategoryID <> -1;
SQL
        );

        // Update the child categories depth-by-depth.
        $sql = <<<SQL
update {$px}Category p
join {$px}Category c
	on c.ParentCategoryID = p.CategoryID
set c.Depth = p.Depth + 1
where p.Depth = :depth;
SQL;

        for ($i = 1; $i < 25; $i++) {
            $this->Database->query($sql, ['depth' => $i]);

            if (val('RowCount', $this->Database->LastInfo) == 0) {
                break;
    }
            }
        }

    /**
     * Return a flattened version of a tree.
     *
     * @param array $categories The category tree.
     * @return array Returns the flattened category tree.
     */
    public static function flattenTree($categories) {
        return self::instance()->collection->flattenTree($categories);
    }

    /**
     * Adjust the aggregate post counts for a category, using the provided offset to increment or decrement the value.
     *
     * @param int $categoryID
     * @param int $offset A value, positive or negative, to offset a category's current aggregate post counts.
     */
    private static function adjustAggregateCounts($categoryID, $offset) {
        $offset = intval($offset);

        if (empty($categoryID)) {
            return;
        }

        // Iterate through the category and its ancestors, adjusting aggregate counts based on $offset.
        $updatedCategories = [];
        if ($categoryID) {
            $categories = self::instance()->collection->getAncestors($categoryID, true);

            foreach ($categories as $current) {
                $targetID = val('CategoryID', $current);
                $updatedCategories[] = $targetID;

                Gdn::sql()
                    ->update('Category')
                    ->set('CountAllDiscussions', "CountAllDiscussions + {$offset}", false)
                    ->set('CountAllComments', "CountAllComments + {$offset}", false)
                    ->where('CategoryID', $targetID)
                    ->put();
            }
        }

        // Update the cache.
        $categoriesToUpdate = self::instance()->getWhere(['CategoryID' => $updatedCategories]);
        foreach ($categoriesToUpdate as $current) {
            $currentID = val('CategoryID', $current);
            $countAllDiscussions = val('CountAllDiscussions', $current);
            $countAllComments = val('CountAllComments', $current);
            self::setCache(
                $currentID,
                ['CountAllDiscussions' => $countAllDiscussions, 'CountAllComments' => $countAllComments]
            );
        }
    }

    /**
     * Move upward through the category tree, incrementing aggregate post counts.
     *
     * @param int $categoryID
     */
    public static function incrementAggregateCounts($categoryID) {
        self::adjustAggregateCounts($categoryID, 1);
    }

    /**
     * Move upward through the category tree, decrementing aggregate post counts.
     *
     * @param int $categoryID
     */
    public static function decrementAggregateCounts($categoryID) {
        self::adjustAggregateCounts($categoryID, -1);
    }

    /**
     * Recalculate all aggregate post count columns for all categories.
     *
     * @return void
     */
    private static function recalculateAggregateCounts() {
        // First grab the max depth so you know where to loop.
        $depth = Gdn::sql()
            ->select('Depth', 'max')
            ->from('Category')
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);
        $depth = (int)val('Depth', $depth, 0);

        if ($depth === 0) {
            return;
        }

        $prefix = Gdn::database()->DatabasePrefix;

        // Initialize with self count.
        Gdn::sql()
            ->update('Category')
            ->set('CountAllDiscussions', 'CountDiscussions', false)
            ->set('CountAllComments', 'CountComments', false)
            ->put();

        while ($depth > 0) {
            $sql = "update {$prefix}Category c
                    join (
                        select
                            c2.ParentCategoryID,
                            sum(CountAllDiscussions) as CountAllDiscussions,
                            sum(CountAllComments) as CountAllComments
                        from {$prefix}Category c2
                        where c2.Depth = :Depth
                        group by c2.ParentCategoryID
                    ) c2 on c.CategoryID = c2.ParentCategoryID
                set
                    c.CountAllDiscussions = c.CountAllDiscussions + c2.CountAllDiscussions,
                    c.CountAllComments = c.CountAllComments + c2.CountAllComments
                where c.Depth = :ParentDepth";
            Gdn::database()->query($sql, [':Depth' => $depth, ':ParentDepth' => ($depth - 1)]);
            $depth--;
        }

        self::instance()->clearCache();
    }
}
