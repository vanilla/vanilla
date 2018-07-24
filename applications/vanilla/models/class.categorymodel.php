<?php
/**
 * Category model
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

use Garden\EventManager;

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

    /** The default maximum number of categories a user can follow. */
    const MAX_FOLLOWED_CATEGORIES_DEFAULT = 100;

    /** Flag for aggregating comment counts. */
    const AGGREGATE_COMMENT = 'comment';

    /** Flag for aggregating discussion counts. */
    const AGGREGATE_DISCUSSION = 'discussion';

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

    /** @var EventManager */
    private $eventManager;

    /**
     * @deprecated 2.6
     * @var bool
     */
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
        $this->eventManager = Gdn::getContainer()->get(EventManager::class);
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
     * @param array $permissionCategory The permission category of the category.
     * @param array $category The category we're checking the permission on.
     * @return array The allowed discussion types on the category.
     * @throws Exception
     */
    public static function allowedDiscussionTypes($permissionCategory, $category = []) {
        $permissionCategory = self::permissionCategory($permissionCategory);
        $allowed = val('AllowedDiscussionTypes', $permissionCategory);
        $allTypes = DiscussionModel::discussionTypes();
        if (empty($allowed) || !is_array($allowed)) {
            $allowedTypes = $allTypes;
        } else {
            $allowedTypes = array_intersect_key($allTypes, array_flip($allowed));
        }
        Gdn::pluginManager()->EventArguments['AllowedDiscussionTypes'] = &$allowedTypes;
        Gdn::pluginManager()->EventArguments['Category'] = $category;
        Gdn::pluginManager()->EventArguments['PermissionCategory'] = $permissionCategory;
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
                $sql = Gdn::sql();
                $sql = clone $sql;
                $sql->reset();

                $sql->select('c.*')
                    ->from('Category c')
                    //->select('lc.DateInserted', '', 'DateLastComment')
                    //->join('Comment lc', 'c.LastCommentID = lc.CommentID', 'left')
                    ->orderBy('c.TreeLeft');

                self::$Categories = array_merge([], $sql->get()->resultArray());
                self::$Categories = Gdn_DataSet::index(self::$Categories, 'CategoryID');
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
        // Kludge to make sure that the url is absolute when reaching the user's screen (or API).
        $category['Url'] = self::categoryUrl($category, '', true);

        if (!isset($category['PhotoUrl'])) {
            if ($photo = val('Photo', $category)) {
                $category['PhotoUrl'] = Gdn_Upload::url($photo);
            }
        }

        if (!empty($category['LastUrl'])) {
            $category['LastUrl'] = url($category['LastUrl'], '//');
        }

        $category['PermsDiscussionsView'] = self::checkPermission($category, 'Vanilla.Discussions.View');
        $category['PermsDiscussionsAdd'] = self::checkPermission($category, 'Vanilla.Discussions.Add');
        $category['PermsDiscussionsEdit'] = self::checkPermission($category, 'Vanilla.Discussions.Edit');
        $category['PermsCommentsAdd'] = self::checkPermission($category, 'Vanilla.Comments.Add');

        $code = $category['UrlCode'];
        $category['Name'] = translateContent("Categories.".$code.".Name", $category['Name']);
        $category['Description'] = translateContent("Categories.".$code.".Description", $category['Description']);

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
            $following = !((bool)val('Archived', $category) || (bool)val('Unfollow', $userData, false));
            $category['Following'] = $following;

            $category['Followed'] = boolval($userData['Followed']);

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
     * Get the per-category information for a user.
     *
     * @param int $userID
     * @return array|mixed
     */
    private function getUserCategories($userID = null) {
        if ($userID === null) {
            $userID = Gdn::session()->UserID;
        }

        if ($userID) {
            $key = 'UserCategory_'.$userID;
            $userData = Gdn::cache()->get($key);
            if ($userData === Gdn_Cache::CACHEOP_FAILURE) {
                $sql = clone $this->SQL;
                $sql->reset();

                $userData = $sql->getWhere('UserCategory', ['UserID' => $userID])->resultArray();
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
     * Get the maximum number of available pages when viewing a list of categories.
     *
     * @return int
     */
    public function getMaxPages() {
        $maxPages = (int)c('Vanilla.Categories.MaxPages') ?: 100;
        return $maxPages;
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
     * Get a list of a user's followed categories.
     *
     * @param int $userID The target user's ID.
     * @return array
     */
    public function getFollowed($userID) {
        $key = "Follow_{$userID}";
        $result = Gdn::cache()->get($key);
        if ($result === Gdn_Cache::CACHEOP_FAILURE) {
            $sql = clone $this->SQL;
            $sql->reset();

            $userData = $sql->getWhere('UserCategory', [
                'UserID' => $userID,
                'Followed' => 1
            ])->resultArray();
            $result = array_column($userData, null, 'CategoryID');
            Gdn::cache()->store($key, $result);
        }

        return $result;
    }

    /**
     * Set whether a user is following a category.
     *
     * @param int $userID The target user's ID.
     * @param int $categoryID The target category's ID.
     * @param bool|null $followed True for following. False for not following. Null for toggle.
     * @return bool A boolean value representing the user's resulting "follow" status for the category.
     */
    public function follow($userID, $categoryID, $followed = null) {
        $validationOptions = ['options' => [
            'min_range' => 1
        ]];
        if (!($userID = filter_var($userID, FILTER_VALIDATE_INT, $validationOptions))) {
            throw new InvalidArgumentException('Invalid $userID');
        }
        if (!($categoryID = filter_var($categoryID, FILTER_VALIDATE_INT, $validationOptions))) {
            throw new InvalidArgumentException('Invalid $categoryID');
        }

        $isFollowed = $this->isFollowed($userID, $categoryID);
        if ($followed === null) {
            $followed = !$isFollowed;
        }
        $followed = $followed ? 1 : 0;

        $category = static::categories($categoryID);
        if (!is_array($category)) {
            throw new InvalidArgumentException('Category not found.');
        } elseif ($category['DisplayAs'] !== 'Discussions' && !$isFollowed) {
            throw new InvalidArgumentException('Category not configured to display as discussions.');
        }

        $this->SQL->replace(
            'UserCategory',
            ['Followed' => $followed],
            ['UserID' => $userID, 'CategoryID' => $categoryID]
        );
        static::clearUserCache();
        Gdn::cache()->remove("Follow_{$userID}");

        $result = $this->isFollowed($userID, $categoryID);
        return $result;
    }

    /**
     * Get the enabled status of category following, returned as a boolean value.
     *
     * @return bool
     */
    public function followingEnabled() {
        $result = boolval(c('Vanilla.EnableCategoryFollowing'));
        return $result;
    }

    /**
     * Get the maximum number of categories a user is allowed to follow.
     *
     * @return mixed
     */
    public function getMaxFollowedCategories() {
        $result = c('Vanilla.MaxFollowedCategories', self::MAX_FOLLOWED_CATEGORIES_DEFAULT);
        return $result;
    }
    /**
     * Is the specified user following the specified category?
     *
     * @param int $userID The target user's ID.
     * @param int $categoryID The target category's ID.
     * @return bool
     */
    public function isFollowed($userID, $categoryID) {
        $followed = $this->getFollowed($userID);
        $result = array_key_exists($categoryID, $followed);

        return $result;
    }

    /**
     * Get user's category IDs, taking into account permissions, muting and, optionally, the HideAllDiscussions field,
     *
     * @deprecated 2.6
     * @param bool $honorHideAllDiscussion Whether or not the HideAllDiscussions flag will be checked on categories.
     * @return array|bool Category IDs or true if all categories are watched.
     */
    public static function categoryWatch($honorHideAllDiscussion = true) {
        deprecated(__METHOD__, __CLASS__.'::getVisibleCategoryIDs');
        $categories = self::categories();
        $allCount = count($categories);

        $watch = [];

        foreach ($categories as $categoryID => $category) {
            if ($honorHideAllDiscussion && val('HideAllDiscussions', $category)) {
                continue;
            }

            if ($category['PermsDiscussionsView'] && $category['Following']) {
                $watch[] = $categoryID;
            }
        }

        Gdn::pluginManager()->EventArguments['CategoryIDs'] = &$watch;
        Gdn::pluginManager()->fireAs('CategoryModel')->fireEvent('CategoryWatch');

        if ($allCount == count($watch)) {
            return true;
        }

        return $watch;
    }

    /**
     * Get a list of IDs of categories visible to the current user.
     *
     * @param array $options
     *   - filterHideDiscussions (bool): Filter out categories with a truthy HideAllDiscussions column?
     * @return array|bool An array of filtered categories or true if no categories were filtered.
     */
    public function getVisibleCategories(array $options = []) {
        $categories = self::categories();
        $unfiltered = true;
        $result = [];

        // Options
        $filterHideDiscussions = $options['filterHideDiscussions'] ?? false;

        foreach ($categories as $categoryID => $category) {
            if ($filterHideDiscussions && val('HideAllDiscussions', $category)) {
                $unfiltered = false;
                continue;
            }

            if ($category['PermsDiscussionsView']) {
                $result[] = $category;
            } elseif ($unfiltered) {
                $unfiltered = false;
            }
        }

        if ($unfiltered) {
            $result = true;
        }

        // Allow addons to modify the visible categories.
        $result = $this->eventManager->fireFilter('categoryModel_visibleCategories', $result);

        return $result;
    }

    /**
     * Get a list of IDs of categories visible to the current user.
     *
     * @see CategoryModel::categoryWatch
     * @param array $options Options compatible with CategoryModel::getVisibleCategories
     * @return array|bool An array of filtered category IDs or true if no categories were filtered.
     */
    public function getVisibleCategoryIDs(array $options = []) {
        $categoryModel = self::instance();
        $result = $categoryModel->getVisibleCategories($options);
        if (is_array($result)) {
            $result = array_column($result, 'CategoryID');
        }

        /** @var EventManager $eventManager */
        $eventManager = Gdn::getContainer()->get(EventManager::class);

        // Backwards-compatible CategoryModel::categoryWatch event.
        $eventManager->fireDeprecated('categoryModel_categoryWatch', $categoryModel, ['CategoryIDs' => &$result]);

        return $result;
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
            $masterKey = Gdn::cache()->add(self::MASTER_VOTE_KEY, $instanceKey, [
                Gdn_Cache::FEATURE_EXPIRY => self::CACHE_GRACE
            ]);

            $isMaster = ($instanceKey == $masterKey);
        }
        return (bool)$isMaster;
    }

    /**
     * Build and augment the category cache.
     *
     * @param int $categoryID The category to
     *
     */
    protected static function buildCache($categoryID = null) {
        self::calculateData(self::$Categories);
        self::joinRecentPosts(self::$Categories, $categoryID);

        $expiry = self::CACHE_TTL + self::CACHE_GRACE;
        Gdn::cache()->store(self::CACHE_KEY, [
            'expiry' => time() + $expiry,
            'categories' => self::$Categories
        ], [
            Gdn_Cache::FEATURE_EXPIRY => $expiry,
            Gdn_Cache::FEATURE_SHARD => self::$ShardCache
        ]);
    }

    /**
     * Calculate the dynamic fields of a category.
     *
     * @param array &$category The category to calculate.
     */
    private static function calculate(&$category) {
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
     * @param array $data Dataset.
     */
    private static function calculateData(&$data) {
        foreach ($data as &$category) {
            self::calculate($category);
        }

        $keys = array_reverse(array_keys($data));
        foreach ($keys as $key) {
            $cat = $data[$key];
            $parentID = $cat['ParentCategoryID'];

            if (isset($data[$parentID]) && $parentID != $key) {
                if (isset($cat['CountAllDiscussions'])) {
                    $data[$parentID]['CountAllDiscussions'] += $cat['CountAllDiscussions'];
                }
                if (isset($cat['CountAllComments'])) {
                    $data[$parentID]['CountAllComments'] += $cat['CountAllComments'];
                }
                if (empty($data[$parentID]['ChildIDs'])) {
                    $data[$parentID]['ChildIDs'] = [];
                }
                array_unshift($data[$parentID]['ChildIDs'], $key);
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
     * Clear the cached UserCategory data for a specific user.
     *
     * @param int|null $userID The user to clear. Use `null` for the current user.
     */
    public static function clearUserCache($userID = null) {
        if ($userID === null) {
            $userID = Gdn::session()->UserID;
        }

        $key = 'UserCategory_'.$userID;
        Gdn::cache()->remove($key);
    }

    /**
     * @param $column
     * @return array
     */
    public function counts($column) {
        $result = ['Complete' => true];
        switch ($column) {
            case 'CountDiscussions':
                $this->Database->query(DBAModel::getCountSQL('count', 'Category', 'Discussion'));
                break;
            case 'CountComments':
                $this->Database->query(DBAModel::getCountSQL('sum', 'Category', 'Discussion', $column, 'CountComments'));
                break;
            case 'CountAllDiscussions':
            case 'CountAllComments':
                self::recalculateAggregateCounts();
                break;
            case 'LastDiscussionID':
                $this->Database->query(DBAModel::getCountSQL('max', 'Category', 'Discussion'));
                break;
            case 'LastCommentID':
                $data = $this->SQL
                    ->select('d.CategoryID')
                    ->select('c.CommentID', 'max', 'LastCommentID')
                    ->select('d.DiscussionID', 'max', 'LastDiscussionID')
                    ->select('c.DateInserted', 'max', 'DateLastComment')
                    ->from('Comment c')
                    ->join('Discussion d', 'd.DiscussionID = c.DiscussionID')
                    ->groupBy('d.CategoryID')
                    ->get()->resultArray();

                // Now we have to grab the discussions associated with these comments.
                $commentIDs = array_column($data, 'LastCommentID');

                // Grab the discussions for the comments.
                $this->SQL
                    ->select('c.CommentID, c.DiscussionID')
                    ->from('Comment c')
                    ->whereIn('c.CommentID', $commentIDs);

                $discussions = $this->SQL->get()->resultArray();
                $discussions = Gdn_DataSet::index($discussions, ['CommentID']);

                foreach ($data as $row) {
                    $categoryID = (int)$row['CategoryID'];
                    $category = CategoryModel::categories($categoryID);
                    $commentID = $row['LastCommentID'];
                    $discussionID = valr("$commentID.DiscussionID", $discussions, null);

                    $dateLastComment = Gdn_Format::toTimestamp($row['DateLastComment']);

                    $discussionModel = new DiscussionModel();
                    $latestDiscussion = $discussionModel->getID($category['LastDiscussionID']);
                    $dateLastDiscussion = Gdn_Format::toTimestamp(val('DateInserted', $latestDiscussion));

                    $set = ['LastCommentID' => $commentID];

                    if ($discussionID) {
                        if ($dateLastComment >= $dateLastDiscussion) {
                            // The most recent discussion is from this comment.
                            $set['LastDiscussionID'] = $discussionID;
                        } else {
                            // The most recent discussion has no comments.
                            $set['LastCommentID'] = null;
                        }
                    } else {
                        // Something went wrong.
                        $set['LastCommentID'] = null;
                        $set['LastDiscussionID'] = null;
                    }

                    $this->setField($categoryID, $set);
                }
                break;
            case 'LastDateInserted':
                $categories = $this->SQL
                    ->select('ca.CategoryID')
                    ->select('d.DateInserted', '', 'DateLastDiscussion')
                    ->select('c.DateInserted', '', 'DateLastComment')
                    ->from('Category ca')
                    ->join('Discussion d', 'd.DiscussionID = ca.LastDiscussionID')
                    ->join('Comment c', 'c.CommentID = ca.LastCommentID')
                    ->get()->resultArray();

                foreach ($categories as $category) {
                    $dateLastDiscussion = val('DateLastDiscussion', $category);
                    $dateLastComment = val('DateLastComment', $category);

                    $maxDate = $dateLastComment;
                    if (is_null($dateLastComment) || $dateLastDiscussion > $maxDate) {
                        $maxDate = $dateLastDiscussion;
                    }

                    if (is_null($maxDate)) {
                        continue;
                    }

                    $categoryID = (int)$category['CategoryID'];
                    $this->setField($categoryID, 'LastDateInserted', $maxDate);
                }
                break;
        }
        self::clearCache();
        return $result;
    }

    /**
     *
     *
     * @return mixed
     */
    public static function defaultCategory() {
        foreach (self::categories() as $category) {
            if ($category['CategoryID'] > 0) {
                return $category;
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

        $options = array_change_key_case($options ?: []) + [
            'collapsecategories' => true
        ];

        $tree = $this->collection->getTree((int)val('CategoryID', $category), $options);
        return $tree;
    }

    /**
     * Returns an icon name, given a display as value.
     *
     * @param string $displayAs The display as value.
     * @return string The corresponding icon name.
     */
    private static function displayAsIconName($displayAs) {
        switch (strtolower($displayAs)) {
            case 'heading':
                return 'heading';
            case 'categories':
                return 'nested';
            case 'flat':
                return 'flat';
            case 'discussions':
            default:
                return 'discussions';
        }
    }

    /**
     * Puts together a dropdown for a category's settings.
     *
     * @param object|array $category The category to get the settings dropdown for.
     * @return DropdownModule The dropdown module for the settings.
     */
    public static function getCategoryDropdown($category) {

        $triggerIcon = dashboardSymbol(self::displayAsIconName($category['DisplayAs']));

        $cdd = new DropdownModule('', '', 'dropdown-category-options', 'dropdown-menu-right');
        $cdd->setTrigger($triggerIcon, 'button', 'btn', 'caret-down', '', ['data-id' => val('CategoryID', $category)]);
        $cdd->setView('dropdown-twbs');
        $cdd->setForceDivider(true);

        $cdd->addGroup('', 'edit')
            ->addLink(t('View'), $category['Url'], 'edit.view')
            ->addLink(t('Edit'), "/vanilla/settings/editcategory?categoryid={$category['CategoryID']}", 'edit.edit')
            ->addGroup(t('Display as'), 'displayas');

        foreach (CategoryModel::getDisplayAsOptions() as $displayAs => $label) {
            $cssClass = strcasecmp($displayAs, $category['DisplayAs']) === 0 ? 'selected': '';
            $icon = dashboardSymbol(self::displayAsIconName($displayAs));

            $cdd->addLink(
                t($label),
                '#',
                'displayas.'.strtolower($displayAs),
                'js-displayas '.$cssClass,
                [],
                ['icon' => $icon, 'attributes' => ['data-displayas' => strtolower($displayAs)]],
                false
            );
        }

        $cdd->addGroup('', 'actions')
            ->addLink(
                t('Add Subcategory'),
                "/vanilla/settings/addcategory?parent={$category['CategoryID']}",
                'actions.add'
            );

        if (val('CanDelete', $category, true)) {
            $cdd->addGroup('', 'delete')
                ->addLink(
                    t('Delete'),
                    "/vanilla/settings/deletecategory?categoryid={$category['CategoryID']}",
                    'delete.delete',
                    'js-modal'
                );
        }

        return $cdd;
    }

    /**
     * Get a category tree.
     *
     * @param int $categoryID
     * @param array $options
     * @return array
     */
    public function getTree($categoryID, array $options = []) {
        $result = $this->collection->getTree($categoryID, $options);
        return $result;
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

        $categories = $query->get()->resultArray();
        $categories = $this->flattenCategories($categories);

        return $categories;
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
     * Prepare an array of category rows for display as a flat list.
     *
     * @param array $categories Category rows.
     * @return array
     */
    public function flattenCategories(array $categories) {
        self::calculateData($categories);
        self::joinUserData($categories);

        foreach ($categories as &$category) {
            // Fix the depth to be relative, not global.
            $category['Depth'] = 1;

            // We don't have children, but trees are expected to have this key.
            $category['Children'] = [];
        }

        return $categories;
    }

    /**
     *
     *
     * @param string $permission
     * @param null $categoryID
     * @param array $filter
     * @param array $permFilter
     * @return array
     */
    public static function getByPermission($permission = 'Discussions.Add', $categoryID = null, $filter = [], $permFilter = []) {
        static $map = ['Discussions.Add' => 'PermsDiscussionsAdd', 'Discussions.View' => 'PermsDiscussionsView'];
        $field = $map[$permission];
        $permFilters = [];

        $result = [];
        $categories = self::categories();
        foreach ($categories as $iD => $category) {
            if (!$category[$field]) {
                continue;
            }

            if ($categoryID != $iD) {
                if ($category['CategoryID'] <= 0) {
                    continue;
                }

                $exclude = false;
                foreach ($filter as $key => $value) {
                    if (isset($category[$key]) && $category[$key] != $value) {
                        $exclude = true;
                        break;
                    }
                }

                if (!empty($permFilter)) {
                    $permCategory = val($category['PermissionCategoryID'], $categories);
                    if ($permCategory) {
                        if (!isset($permFilters[$permCategory['CategoryID']])) {
                            $permFilters[$permCategory['CategoryID']] = self::where($permCategory, $permFilter);
                        }

                        $exclude = !$permFilters[$permCategory['CategoryID']];
                    } else {
                        $exclude = true;
                    }
                }

                if ($exclude) {
                    continue;
                }

                if ($category['DisplayAs'] == 'Heading') {
                    if ($permission == 'Discussions.Add') {
                        continue;
                    } else {
                        $category['PermsDiscussionsAdd'] = false;
                    }
                }
            }

            $result[$iD] = $category;
        }
        return $result;
    }

    /**
     *
     *
     * @param $row
     * @param $where
     * @return bool
     */
    public static function where($row, $where) {
        if (empty($where)) {
            return true;
        }

        foreach ($where as $key => $value) {
            $rowValue = val($key, $row);

            // If there are no discussion types set then all discussion types are allowed.
            if ($key == 'AllowedDiscussionTypes' && empty($rowValue)) {
                continue;
            }

            if (is_array($rowValue)) {
                if (is_array($value)) {
                    // If both items are arrays then all values in the filter must be in the row.
                    if (count(array_intersect($value, $rowValue)) < count($value)) {
                        return false;
                    }
                } elseif (!in_array($value, $rowValue)) {
                    return false;
                }
            } elseif (is_array($value)) {
                if (!in_array($rowValue, $value)) {
                    return false;
                }
            } else {
                if ($rowValue != $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Give a user points specific to this category.
     *
     * @param int $userID The user to give the points to.
     * @param int $points The number of points to give.
     * @param string $source The source of the points.
     * @param int $categoryID The category to give the points for.
     * @param int $timestamp The time the points were given.
     */
    public static function givePoints($userID, $points, $source = 'Other', $categoryID = 0, $timestamp = false) {
        // Figure out whether or not the category tracks points seperately.
        if ($categoryID) {
            $category = self::categories($categoryID);
            if ($category) {
                $categoryID = val('PointsCategoryID', $category);
            } else {
                $categoryID = 0;
            }
        }

        UserModel::givePoints($userID, $points, [$source, 'CategoryID' => $categoryID], $timestamp);
    }

    /**
     *
     *
     * @param array|Gdn_DataSet &$data Dataset.
     * @param string $column Name of database column.
     * @param array $options The 'Join' key may contain array of columns to join on.
     * @since 2.0.18
     */
    public static function joinCategories(&$data, $column = 'CategoryID', $options = []) {
        $join = val('Join', $options, ['Name' => 'Category', 'PermissionCategoryID', 'UrlCode' => 'CategoryUrlCode']);

        if ($data instanceof Gdn_DataSet) {
            $data2 = $data->result();
        } else {
            $data2 =& $data;
        }

        foreach ($data2 as &$row) {
            $iD = val($column, $row);
            $category = self::categories($iD);
            foreach ($join as $n => $v) {
                if (is_numeric($n)) {
                    $n = $v;
                }

                if ($category) {
                    $value = $category[$n];
                } else {
                    $value = null;
                }

                setValue($v, $row, $value);
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
     * Given a discussion, update its category's last post info and counts.
     *
     * @param int|array|stdClass $discussion The discussion ID or discussion.
     */
    public function incrementLastDiscussion($discussion) {
        // Lookup the discussion record, if necessary. We need at least a discussion to continue.
        if (filter_var($discussion, FILTER_VALIDATE_INT) !== false) {
            $discussion = DiscussionModel::instance()->getID($discussion);
        }
        if (!$discussion) {
            return;
        }
        $discussionID = val('DiscussionID', $discussion);

        $categoryID = val('CategoryID', $discussion);
        $category = CategoryModel::categories($categoryID);
        if (!$category) {
            return;
        }

        $countDiscussions = val('CountDiscussions', $category, 0);
        $countDiscussions++;

        // setField will update these values in the DB, as well as the cache.
        self::instance()->setField($categoryID, [
            'CountDiscussions' => $countDiscussions,
            'LastCategoryID' => $categoryID
        ]);

        // Update the cached last post info with whatever we have.
        self::updateLastPost($discussion);

        // Update the aggregate discussion count for this category and all its parents.
        self::incrementAggregateCount($categoryID, self::AGGREGATE_DISCUSSION);

        // Set the new LastCategoryID.
        self::setAsLastCategory($categoryID);
    }

    /**
     * Given a comment, update its category's last post info and counts.
     *
     * @param int|array|object $comment A comment ID or array representing a comment.
     */
    public function incrementLastComment($comment) {
        if (filter_var($comment, FILTER_VALIDATE_INT) !== false) {
            $comment = CommentModel::instance()->getID($comment);
        }
        if (!$comment) {
            return;
        }
        $commentID = val('CommentID', $comment);
        $discussionID = val('DiscussionID', $comment);

        // Lookup the discussion record.
        $discussion = DiscussionModel::instance()->getID($discussionID);
        if (!$discussion) {
            return;
        }
        $categoryID = val('CategoryID', $discussion);

        // Grab the full category record.
        $category = CategoryModel::categories($categoryID);
        if (!$category) {
            return;
        }

        // We may or may not perform a MySQL sum to update the count. Verify using threshold constants.
        $countComments = val('CountComments', $category, 0);
        $countBelowThreshold = $countComments < CommentModel::COMMENT_THRESHOLD_SMALL;
        $countScheduledUpdate = ($countComments < CommentModel::COMMENT_THRESHOLD_LARGE && $countComments % CommentModel::COUNT_RECALC_MOD == 0);

        if ($countBelowThreshold || $countScheduledUpdate) {
            $countComments = Gdn::sql()->select('CountComments', 'sum', 'CountComments')
                ->from('Discussion')
                ->where('CategoryID', $categoryID)
                ->get()
                ->firstRow()
                ->CountComments;
        } else {
            // No SQL sum means we're going with a regular ole PHP increment.
            $countComments++;
        }

        // setField will update these values in the DB, as well as the cache.
        self::instance()->setField($categoryID, [
            'CountComments' => $countComments,
            'LastCommentID' => $commentID,
            'LastDiscussionID' => $discussionID,
            'LastDateInserted' => val('DateInserted', $comment)
        ]);

        // Update the cached last post info with whatever we have.
        self::updateLastPost($discussion, $comment);

        // Update the aggregate comment count for this category and all its parents.
        self::incrementAggregateCount($categoryID, self::AGGREGATE_COMMENT);

        // Set the new LastCategoryID.
        self::setAsLastCategory($categoryID);
    }

    /**
     * Update the latest post info for a category and its ancestors.
     *
     * @param int|array|object $discussion
     * @param int|array|object $comment
     */
    public static function updateLastPost($discussion, $comment = null) {
        // Make sure we at least have a discussion to work with.
        if (is_numeric($discussion)) {
            $discussion = DiscussionModel::instance()->getID($discussion);
        }
        if (!$discussion) {
            return;
        }
        $discussionID = val('DiscussionID', $discussion);
        $categoryID = val('CategoryID', $discussion);

        // Should we attempt to fetch a comment?
        if (is_numeric($comment)) {
            $comment = CommentModel::instance()->getID($comment);
        }

        // Discussion-related field values.
        $cache = static::postCacheFields($discussion, $comment);
        $db = static::postDBFields($discussion, $comment);

        $categories = self::instance()->collection->getAncestors($categoryID, true);
        foreach ($categories as $row) {
            $currentCategoryID = val('CategoryID', $row);
            self::instance()->setField($currentCategoryID, $db);
            CategoryModel::setCache($currentCategoryID, $cache);
        }
    }

    /**
     * Build the cached category fields related to recent posts.
     *
     * @param array|object $discussion
     * @param array|object $comment
     * @return array
     */
    private static function postCacheFields($discussion, $comment = null) {
        $result = [
            'LastDiscussionUserID' => null,
            'LastTitle' => null,
            'LastUrl' => null,
            'LastUserID' => null
        ];

        if ($discussion) {
            // Discussion-related field values.
            $result['LastDiscussionUserID'] = val('InsertUserID', $discussion);
            $result['LastTitle'] = Gdn_Format::text(val('Name', $discussion, t('No Title')));
            $result['LastUrl'] = discussionUrl($discussion, false, '//') . '#latest';
            $result['LastUserID'] = val('InsertUserID', $discussion);

            // If we have a valid comment, override some of the last post field info with its values.
            if ($comment) {
                $result['LastUserID'] = val('InsertUserID', $comment);
            }
        }

        return $result;
    }

    /**
     * Build the database category fields related to recent posts.
     *
     * @param array|object $discussion
     * @param array|object $comment
     * @return array
     */
    private static function postDBFields($discussion, $comment = null) {
        $result = [
            'LastCommentID' => null,
            'LastDateInserted' => null,
            'LastDiscussionID' => null
        ];

        if ($discussion) {
            $result['LastCommentID'] = null;
            $result['LastDateInserted'] = val('DateInserted', $discussion);
            $result['LastDiscussionID'] = val('DiscussionID', $discussion);

            // If we have a valid comment, override some of the last post field info with its values.
            if ($comment) {
                $result['LastCommentID'] = val('CommentID', $comment);
                $result['LastDateInserted'] = val('DateInserted', $comment);
            }
        }

        return $result;
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
     * @param $data
     * @param null $categoryID
     * @return bool
     */
    public static function joinRecentPosts(&$data, $categoryID = null) {
        $discussionIDs = [];
        $commentIDs = [];
        $joined = false;

        foreach ($data as &$row) {
            if (!is_null($categoryID) && $row['CategoryID'] != $categoryID) {
                continue;
            }

            if (isset($row['LastTitle']) && $row['LastTitle']) {
                continue;
            }

            if ($row['LastDiscussionID']) {
                $discussionIDs[] = $row['LastDiscussionID'];
            }

            if ($row['LastCommentID']) {
                $commentIDs[] = $row['LastCommentID'];
            }
            $joined = true;
        }

        // Create a fresh copy of the Sql object so as not to pollute.
        $sql = clone Gdn::sql();
        $sql->reset();

        $discussions = null;

        // Grab the discussions.
        if (count($discussionIDs) > 0) {
            $discussions = $sql->whereIn('DiscussionID', $discussionIDs)->get('Discussion')->resultArray();
            $discussions = Gdn_DataSet::index($discussions, ['DiscussionID']);
        }

        if (count($commentIDs) > 0) {
            $comments = $sql->whereIn('CommentID', $commentIDs)->get('Comment')->resultArray();
            $comments = Gdn_DataSet::index($comments, ['CommentID']);
        }

        foreach ($data as &$row) {
            if (!is_null($categoryID) && $row['CategoryID'] != $categoryID) {
                continue;
            }

            $discussion = val($row['LastDiscussionID'], $discussions);
            $nameUrl = 'x';
            if ($discussion) {
                $row['LastTitle'] = Gdn_Format::text($discussion['Name']);
                $row['LastUserID'] = $discussion['InsertUserID'];
                $row['LastDiscussionUserID'] = $discussion['InsertUserID'];
                $row['LastDateInserted'] = $discussion['DateInserted'];
                $nameUrl = Gdn_Format::text($discussion['Name'], true);
                $row['LastUrl'] = discussionUrl($discussion, false, '/').'#latest';
            }
            if (!empty($comments) && ($comment = val($row['LastCommentID'], $comments))) {
                $row['LastUserID'] = $comment['InsertUserID'];
                $row['LastDateInserted'] = $comment['DateInserted'];
                $row['DateLastComment'] = $comment['DateInserted'];
            } else {
                $row['NoComment'] = true;
            }

            touchValue('LastTitle', $row, '');
            touchValue('LastUserID', $row, null);
            touchValue('LastDiscussionUserID', $row, null);
            touchValue('LastDateInserted', $row, null);
            touchValue('LastUrl', $row, null);
        }
        return $joined;
    }

    /**
     *
     *
     * @param null $category
     * @param null $categories
     */
    public static function joinRecentChildPosts(&$category = null, &$categories = null) {
        if ($categories === null) {
            $categories =& self::$Categories;
        }

        if ($category === null) {
            $category =& $categories[-1];
        }

        if (!isset($category['ChildIDs'])) {
            return;
        }

        $lastTimestamp = Gdn_Format::toTimestamp($category['LastDateInserted']);
        $lastCategoryID = null;

        if ($category['DisplayAs'] == 'Categories') {
            // This is an overview category so grab it's recent data from it's children.
            foreach ($category['ChildIDs'] as $categoryID) {
                if (!isset($categories[$categoryID])) {
                    continue;
                }

                $childCategory =& $categories[$categoryID];
                if ($childCategory['DisplayAs'] == 'Categories') {
                    self::joinRecentChildPosts($childCategory, $categories);
                }
                $timestamp = Gdn_Format::toTimestamp($childCategory['LastDateInserted']);

                if ($lastTimestamp === false || $lastTimestamp < $timestamp) {
                    $lastTimestamp = $timestamp;
                    $lastCategoryID = $categoryID;
                }
            }

            if ($lastCategoryID) {
                $lastCategory = $categories[$lastCategoryID];

                $category['LastCommentID'] = $lastCategory['LastCommentID'];
                $category['LastDiscussionID'] = $lastCategory['LastDiscussionID'];
                $category['LastDateInserted'] = $lastCategory['LastDateInserted'];
                $category['LastTitle'] = $lastCategory['LastTitle'];
                $category['LastUserID'] = $lastCategory['LastUserID'];
                $category['LastDiscussionUserID'] = $lastCategory['LastDiscussionUserID'];
                $category['LastUrl'] = $lastCategory['LastUrl'];
                $category['LastCategoryID'] = $lastCategory['CategoryID'];
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
     * Update &$categories in memory by applying modifiers from UserCategory for
     * the currently logged-in user.
     *
     * @since 2.0.18
     * @access public
     * @param array &$categories
     * @param bool $addUserCategory
     */
    public static function joinUserData(&$categories, $addUserCategory = true) {
        $iDs = array_keys($categories);

        if ($addUserCategory) {
            $userData = self::instance()->getUserCategories();

            foreach ($iDs as $iD) {
                $category = $categories[$iD];

                $dateMarkedRead = val('DateMarkedRead', $category);
                $row = val($iD, $userData);
                if ($row) {
                    $userDateMarkedRead = $row['DateMarkedRead'];

                    if (!$dateMarkedRead || ($userDateMarkedRead && Gdn_Format::toTimestamp($userDateMarkedRead) > Gdn_Format::toTimestamp($dateMarkedRead))) {
                        $categories[$iD]['DateMarkedRead'] = $userDateMarkedRead;
                        $dateMarkedRead = $userDateMarkedRead;
                    }

                    $categories[$iD]['Unfollow'] = $row['Unfollow'];
                } else {
                    $categories[$iD]['Unfollow'] = false;
                }

                // Calculate the following field.
                $following = !((bool)val('Archived', $category) || (bool)val('Unfollow', $row, false));
                $categories[$iD]['Following'] = $following;

                $categories[$iD]['Followed'] = boolval($row['Followed']);

                // Calculate the read field.
                if ($category['DisplayAs'] == 'Heading') {
                    $categories[$iD]['Read'] = false;
                } elseif ($dateMarkedRead) {
                    if (val('LastDateInserted', $category)) {
                        $categories[$iD]['Read'] = Gdn_Format::toTimestamp($dateMarkedRead) >= Gdn_Format::toTimestamp($category['LastDateInserted']);
                    } else {
                        $categories[$iD]['Read'] = true;
                    }
                } else {
                    $categories[$iD]['Read'] = false;
                }
            }

        }

        // Add permissions.
        foreach ($iDs as $cID) {
            $category = &$categories[$cID];
            self::instance()->calculateUser($category);
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
        }

        // Remove permissions related to category
        $permissionModel = Gdn::permissionModel();
        $permissionModel->delete(null, 'Category', 'CategoryID', $category->CategoryID);

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
            $count = $this->SQL
                ->select('DiscussionID', 'count', 'DiscussionCount')
                ->from('Discussion')
                ->where('CategoryID', $newCategoryID)
                ->get()
                ->firstRow()
                ->DiscussionCount;

            if (!is_numeric($count)) {
                $count = 0;
            }

            $this->SQL
                ->update('Category')->set('CountDiscussions', $count)
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
            $this->SQL->delete('Discussion', ['CategoryID' => $category->CategoryID]);

            // Make inherited permission local permission
            $this->SQL
                ->update('Category')
                ->set('PermissionCategoryID', 0)
                ->where('PermissionCategoryID', $category->CategoryID)
                ->where('CategoryID <>', $category->CategoryID)
                ->put();

            // Delete tags
            $this->SQL->delete('Tag', ['CategoryID' => $category->CategoryID]);
            $this->SQL->delete('TagDiscussion', ['CategoryID' => $category->CategoryID]);

            // Recursively delete child categories and their content.
            $children = self::flattenTree($this->collection->getTree($category->CategoryID));
            $recursionLevel++;
            foreach ($children as $child) {
                self::deleteAndReplace($child, 0);
            }
            $recursionLevel--;
        }

        // Delete the category
        $this->SQL->delete('Category', ['CategoryID' => $category->CategoryID]);

        // Make sure to reorganize the categories after deletes
        if ($recursionLevel === 0) {
            $this->rebuildTree();
        }

        // Let the world know we completed our mission.
        $this->EventArguments['CategoryID'] = $category->CategoryID;
        $this->fireEvent('AfterDeleteCategory');
    }

    /**
     * Get data for a single category selected by Url Code. Disregards permissions.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $codeID Unique Url Code of category we're getting data for.
     * @return object SQL results.
     */
    public function getByCode($code) {
        return $this->SQL->getWhere('Category', ['UrlCode' => $code])->firstRow();
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
        $category = $this->SQL->getWhere('Category', ['CategoryID' => $categoryID])->firstRow($datasetType);
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
     * @param string $orderFields Ignored.
     * @param string $orderDirection Ignored.
     * @param int $limit Ignored.
     * @param int $offset Ignored.
     * @return Gdn_DataSet SQL results.
     */
    public function get($orderFields = '', $orderDirection = 'asc', $limit = false, $offset = false) {
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

        $categoryData = $this->SQL->get();
        $this->addCategoryColumns($categoryData);
        return $categoryData;
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
        $categoryData = $this->SQL
            ->select('c.*')
            ->from('Category c')
            ->orderBy('TreeLeft', 'asc')
            ->get();

        $this->addCategoryColumns($categoryData);
        return $categoryData;
    }

    /**
     * Return the number of descendants for a specific category.
     */
    public function getDescendantCountByCode($code) {
        $category = $this->getByCode($code);
        if ($category) {
            return round(($category->TreeRight - $category->TreeLeft - 1) / 2);
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
        $max = 20;
        while ($category = self::instance()->getOne($category['ParentCategoryID'])) {
            // Check for an infinite loop.
            if ($max <= 0) {
                break;
            }
            $max--;

            if ($category['CategoryID'] == -1) {
                break;
            }

            if ($checkPermissions && !$category['PermsDiscussionsView']) {
                $category = self::instance()->getOne($category['ParentCategoryID']);
                continue;
            }

            // Return by ID or code.
            if (is_numeric($categoryID)) {
                $iD = $category['CategoryID'];
            } else {
                $iD = $category['UrlCode'];
            }

            if ($includeHeadings || $category['DisplayAs'] !== 'Heading') {
                $result[$iD] = $category;
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
     * @param string $code Where condition.
     * @return object DataSet
     */
    public function getDescendantsByCode($code) {
        deprecated('CategoryModel::GetDescendantsByCode', 'CategoryModel::GetAncestors');

        // SELECT title FROM tree WHERE lft < 4 AND rgt > 5 ORDER BY lft ASC;
        return $this->SQL
            ->select('c.ParentCategoryID, c.CategoryID, c.TreeLeft, c.TreeRight, c.Depth, c.Name, c.Description, c.CountDiscussions, c.CountComments, c.AllowDiscussions, c.UrlCode')
            ->from('Category c')
            ->join('Category d', 'c.TreeLeft < d.TreeLeft and c.TreeRight > d.TreeRight')
            ->where('d.UrlCode', $code)
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
            $categories = self::instance()->collection->getTree($parent['CategoryID'], ['maxdepth' => 10]);
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

    public function getFull($categoryID = false, $permissions = false) {

        // Get the current category list
        $categories = self::categories();

        // Filter out the categories we aren't supposed to view.
        if ($categoryID && !is_array($categoryID)) {
            $categoryID = [$categoryID];
        }

        if (!$categoryID) {
            $categoryID = CategoryModel::instance()->getVisibleCategoryIDs();
        }

        switch ($permissions) {
            case 'Vanilla.Discussions.Add':
                $permissions = 'PermsDiscussionsAdd';
                break;
            case 'Vanilla.Disussions.Edit':
                $permissions = 'PermsDiscussionsEdit';
                break;
            default:
                $permissions = 'PermsDiscussionsView';
                break;
        }

        $iDs = array_keys($categories);
        foreach ($iDs as $iD) {
            if ($iD < 0) {
                unset($categories[$iD]);
            } elseif (!$categories[$iD][$permissions])
                unset($categories[$iD]);
            elseif (is_array($categoryID) && !in_array($iD, $categoryID))
                unset($categories[$iD]);
        }

        //self::joinRecentPosts($Categories);
        foreach ($categories as &$category) {
            if ($category['ParentCategoryID'] <= 0) {
                self::joinRecentChildPosts($category, $categories);
            }
        }

        // This join users call can be very slow on forums with a lot of categories so we can disable it here.
        if ($this->JoinRecentUsers) {
            Gdn::userModel()->joinUsers($categories, ['LastUserID']);
        }

        $result = new Gdn_DataSet($categories, DATASET_TYPE_ARRAY);
        $result->datasetType(DATASET_TYPE_OBJECT);
        return $result;
    }

    /**
     * Get a list of categories, considering several filters
     *
     * @param array $restrictIDs Optional list of category ids to mask the dataset
     * @param string $permissions Optional permission to require. Defaults to Vanilla.Discussions.View.
     * @param array $excludeWhere Exclude categories with any of these flags
     * @return \Gdn_DataSet
     */
    public function getFiltered($restrictIDs = false, $permissions = false, $excludeWhere = false) {

        // Get the current category list
        $categories = self::categories();

        // Filter out the categories we aren't supposed to view.
        if ($restrictIDs && !is_array($restrictIDs)) {
            $restrictIDs = [$restrictIDs];
        } else {
            $restrictIDs = $this->getVisibleCategoryIDs(['filterHideDiscussions' => true]);
        }

        switch ($permissions) {
            case 'Vanilla.Discussions.Add':
                $permissions = 'PermsDiscussionsAdd';
                break;
            case 'Vanilla.Disussions.Edit':
                $permissions = 'PermsDiscussionsEdit';
                break;
            default:
                $permissions = 'PermsDiscussionsView';
                break;
        }

        $iDs = array_keys($categories);
        foreach ($iDs as $iD) {
            // Exclude the root category
            if ($iD < 0) {
                unset($categories[$iD]);
            } // No categories where we don't have permission
            elseif (!$categories[$iD][$permissions])
                unset($categories[$iD]);

            // No categories whose filter fields match the provided filter values
            elseif (is_array($excludeWhere)) {
                foreach ($excludeWhere as $filter => $filterValue) {
                    if (val($filter, $categories[$iD], false) == $filterValue) {
                        unset($categories[$iD]);
                    }
                }
            } // No categories that are otherwise filtered out
            elseif (is_array($restrictIDs) && !in_array($iD, $restrictIDs))
                unset($categories[$iD]);
        }

        Gdn::userModel()->joinUsers($categories, ['LastUserID']);

        $result = new Gdn_DataSet($categories, DATASET_TYPE_ARRAY);
        $result->datasetType(DATASET_TYPE_OBJECT);
        return $result;
    }

    /**
     * Get full data for a single category by its URL slug. Respects permissions.
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $urlCode Unique category slug from URL.
     * @return object SQL results.
     */
    public function getFullByUrlCode($urlCode) {
        $data = (object)self::categories($urlCode);

        // Check to see if the user has permission for this category.
        // Get the category IDs.
        $categoryIDs = DiscussionModel::categoryPermissions();
        if (is_array($categoryIDs) && !in_array(val('CategoryID', $data), $categoryIDs)) {
            $data = false;
        }
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getWhere($where = false, $orderFields = '', $orderDirection = 'asc', $limit = false, $offset = false) {
        if (!is_array($where)) {
            $where = [];
        }

        if (array_key_exists('Followed', $where)) {
            if ($where['Followed']) {
                $followed = $this->getFollowed(Gdn::session()->UserID);
                $categoryIDs = array_column($followed, 'CategoryID');

                if (isset($where['CategoryID'])) {
                    $where['CategoryID'] = array_values(array_intersect((array)$where['CategoryID'], $categoryIDs));
                } else {
                    $where['CategoryID'] = $categoryIDs;
                }
            }
            unset($where['Followed']);
        }

        $result = parent::getWhere($where, $orderFields, $orderDirection, $limit, $offset);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getCount($wheres = '') {
        if (array_key_exists('Followed', (array)$wheres)) {
            if ($wheres['Followed']) {
                $followed = $this->getFollowed(Gdn::session()->UserID);
                $categoryIDs = array_column($followed, 'CategoryID');

                if (isset($wheres['CategoryID'])) {
                    $wheres['CategoryID'] = array_values(array_intersect((array)$wheres['CategoryID'], $categoryIDs));
                } else {
                    $wheres['CategoryID'] = $categoryIDs;
                }
            }
            unset($wheres['Followed']);
        }

        return parent::getCount($wheres);
    }

    /**
     * A simplified version of GetWhere that polls the cache instead of the database.
     * @param array $where
     * @return array
     * @since 2.2.2
     */
    public function getWhereCache($where) {
        $result = [];

        foreach (self::categories() as $index => $row) {
            $match = true;
            foreach ($where as $column => $value) {
                $rowValue = val($column, $row, null);

                if ($rowValue != $value && !(is_array($value) && in_array($rowValue, $value))) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $result[$index] = $row;
            }
        }

        return $result;
    }

    /**
     * Check whether category has any children categories.
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $categoryID Unique ID for category being checked.
     * @return bool
     */
    public function hasChildren($categoryID) {
        $childData = $this->SQL
            ->select('CategoryID')
            ->from('Category')
            ->where('ParentCategoryID', $categoryID)
            ->get();
        return $childData->numRows() > 0 ? true : false;
    }

    /**
     *
     *
     * @since 2.0.0
     * @access public
     * @param array $data
     * @param string $permission
     * @param string $column
     */
    public static function joinModerators(&$data, $permission = 'Vanilla.Comments.Edit', $column = 'Moderators') {
        $moderators = Gdn::sql()
            ->select('u.UserID, u.Name, u.Photo, u.Email')
            ->select('p.JunctionID as CategoryID')
            ->from('User u')
            ->join('UserRole ur', 'ur.UserID = u.UserID')
            ->join('Permission p', 'ur.RoleID = p.RoleID')
            ->where('`'.$permission.'`', 1)
            ->get()->resultArray();

        $moderators = Gdn_DataSet::index($moderators, 'CategoryID', ['Unique' => false]);

        foreach ($data as &$category) {
            $iD = val('PermissionCategoryID', $category);
            $mods = val($iD, $moderators, []);
            $modIDs = [];
            $uniqueMods = [];
            foreach ($mods as $mod) {
                if (!in_array($mod['UserID'], $modIDs)) {
                    $modIDs[] = $mod['UserID'];
                    $uniqueMods[] = $mod;
                }

            }
            setValue($column, $category, $uniqueMods);
        }
    }

    /**
     *
     *
     * @param $categories
     * @param null $root
     * @return array
     */
    public static function makeTree($categories, $root = null) {
        $result = [];

        $categories = (array)$categories;

        if ($root) {
            $result = self::instance()->collection->getTree(
                (int)val('CategoryID', $root),
                ['depth' => self::instance()->getMaxDisplayDepth() ?: 10]
            );
            self::instance()->joinRecent($result);
        } else {
            // Make a tree out of all categories.
            foreach ($categories as $category) {
                if (isset($category['Depth']) && $category['Depth'] == 1) {
                    $row = $category;
                    $row['Children'] = self::_MakeTreeChildren($row, $categories, 0);
                    $result[] = $row;
                }
            }
        }
        return $result;
    }

    /**
     *
     *
     * @param $category
     * @param $categories
     * @param null $depthAdj
     * @return array
     */
    protected static function _MakeTreeChildren($category, $categories, $depthAdj = null) {
        if (is_null($depthAdj)) {
            $depthAdj = -val('Depth', $category);
        }

        $result = [];
        $childIDs = val('ChildIDs', $category);
        if (is_array($childIDs) && count($childIDs)) {
            foreach ($childIDs as $iD) {
                if (!isset($categories[$iD])) {
                    continue;
                }
                $row = (array)$categories[$iD];
                $row['Depth'] += $depthAdj;
                $row['Children'] = self::_MakeTreeChildren($row, $categories);
                $result[] = $row;
            }
        }
        return $result;
    }

    /**
     * Return the category that contains the permissions for the given category.
     *
     * @param mixed $category
     * @since 2.2
     */
    public static function permissionCategory($category) {
        if (empty($category)) {
            return self::categories(-1);
        }

        if (!is_array($category) && !is_object($category)) {
            $category = self::categories($category);
        }

        return self::categories(val('PermissionCategoryID', $category));
    }

    /**
     * Rebuilds the category tree. We are using the Nested Set tree model.
     *
     * @param bool $bySort Rebuild the tree by sort order instead of existing tree order.
     * @ref http://en.wikipedia.org/wiki/Nested_set_model
     *
     * @since 2.0.0
     * @access public
     */
    public function rebuildTree($bySort = false) {
        // Grab all of the categories.
        if ($bySort) {
            $order = 'Sort, Name';
        } else {
            $order = 'TreeLeft, Sort, Name';
        }

        $categories = $this->SQL->get('Category', $order);
        $categories = Gdn_DataSet::index($categories->resultArray(), 'CategoryID');

        // Make sure the tree has a root.
        if (!isset($categories[-1])) {
            $rootCat = ['CategoryID' => -1, 'TreeLeft' => 1, 'TreeRight' => 4, 'Depth' => 0, 'InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Gdn_Format::toDateTime(), 'DateUpdated' => Gdn_Format::toDateTime(), 'Name' => 'Root', 'UrlCode' => '', 'Description' => 'Root of category tree. Users should never see this.', 'PermissionCategoryID' => -1, 'Sort' => 0, 'ParentCategoryID' => null];
            $categories[-1] = $rootCat;
            $this->SQL->insert('Category', $rootCat);
        }

        // Build a tree structure out of the categories.
        $root = null;
        foreach ($categories as &$cat) {
            if (!isset($cat['CategoryID'])) {
                continue;
            }

            // Backup category settings for efficient database saving.
            try {
                $cat['_TreeLeft'] = $cat['TreeLeft'];
                $cat['_TreeRight'] = $cat['TreeRight'];
                $cat['_Depth'] = $cat['Depth'];
                $cat['_PermissionCategoryID'] = $cat['PermissionCategoryID'];
                $cat['_ParentCategoryID'] = $cat['ParentCategoryID'];
            } catch (Exception $ex) {
                // Suppress exceptions from bubbling up.
            }

            if ($cat['CategoryID'] == -1) {
                $root =& $cat;
                continue;
            }

            $parentID = $cat['ParentCategoryID'];
            if (!$parentID) {
                $parentID = -1;
                $cat['ParentCategoryID'] = $parentID;
            }
            if (!isset($categories[$parentID]['Children'])) {
                $categories[$parentID]['Children'] = [];
            }
            $categories[$parentID]['Children'][] =& $cat;
        }
        unset($cat);

        // Set the tree attributes of the tree.
        $this->_SetTree($root);
        unset($root);

        // Save the tree structure.
        foreach ($categories as $cat) {
            if (!isset($cat['CategoryID'])) {
                continue;
            }
            if ($cat['_TreeLeft'] != $cat['TreeLeft'] || $cat['_TreeRight'] != $cat['TreeRight'] || $cat['_Depth'] != $cat['Depth'] || $cat['PermissionCategoryID'] != $cat['PermissionCategoryID'] || $cat['_ParentCategoryID'] != $cat['ParentCategoryID'] || $cat['Sort'] != $cat['TreeLeft']) {
                $this->SQL->put(
                    'Category',
                    ['TreeLeft' => $cat['TreeLeft'], 'TreeRight' => $cat['TreeRight'], 'Depth' => $cat['Depth'], 'PermissionCategoryID' => $cat['PermissionCategoryID'], 'ParentCategoryID' => $cat['ParentCategoryID'], 'Sort' => $cat['TreeLeft']],
                    ['CategoryID' => $cat['CategoryID']]
                );
            }
        }
        self::setCache();
        $this->collection->flushCache();

        // Make sure the shared instance is reset.
        if ($this !== self::instance()) {
            self::instance()->collection->flushCache();
        }
    }

    /**
     *
     *
     * @since 2.0.18
     * @access protected
     * @param array $node
     * @param int $left
     * @param int $depth
     */
    protected function _SetTree(&$node, $left = 1, $depth = 0) {
        $right = $left + 1;

        if (isset($node['Children'])) {
            foreach ($node['Children'] as &$child) {
                $right = $this->_SetTree($child, $right, $depth + 1);
                $child['ParentCategoryID'] = $node['CategoryID'];
                if ($child['PermissionCategoryID'] != $child['CategoryID']) {
                    $child['PermissionCategoryID'] = val('PermissionCategoryID', $node, $child['CategoryID']);
                }
            }
            unset($node['Children']);
        }

        $node['TreeLeft'] = $left;
        $node['TreeRight'] = $right;
        $node['Depth'] = $depth;

        return $right + 1;
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
     * @param array $treeArray A fully defined nested set model of the category tree.
     */
    public function saveTree($treeArray) {
        // Grab all of the categories so that permissions can be properly saved.
        $permTree = $this->SQL->select('CategoryID, PermissionCategoryID, TreeLeft, TreeRight, Depth, Sort, ParentCategoryID')->from('Category')->get();
        $permTree = $permTree->index($permTree->resultArray(), 'CategoryID');

        // The tree must be walked in order for the permissions to save properly.
        usort($treeArray, ['CategoryModel', '_TreeSort']);
        $saves = [];

        foreach ($treeArray as $i => $node) {
            $categoryID = val('item_id', $node);
            if ($categoryID == 'root') {
                $categoryID = -1;
            }

            $parentCategoryID = val('parent_id', $node);
            if (in_array($parentCategoryID, ['root', 'none'])) {
                $parentCategoryID = -1;
            }

            $permissionCategoryID = valr("$categoryID.PermissionCategoryID", $permTree, 0);
            $permCatChanged = false;
            if ($permissionCategoryID != $categoryID) {
                // This category does not have custom permissions so must inherit its parent's permissions.
                $permissionCategoryID = valr("$parentCategoryID.PermissionCategoryID", $permTree, 0);
                if ($categoryID != -1 && !valr("$parentCategoryID.Touched", $permTree)) {
                    throw new Exception("Category $parentCategoryID not touched before touching $categoryID.");
                }
                if ($permTree[$categoryID]['PermissionCategoryID'] != $permissionCategoryID) {
                    $permCatChanged = true;
                }
                $permTree[$categoryID]['PermissionCategoryID'] = $permissionCategoryID;
            }
            $permTree[$categoryID]['Touched'] = true;

            // Only update if the tree doesn't match the database.
            $row = $permTree[$categoryID];
            if ($node['left'] != $row['TreeLeft'] || $node['right'] != $row['TreeRight'] || $node['depth'] != $row['Depth'] || $parentCategoryID != $row['ParentCategoryID'] || $node['left'] != $row['Sort'] || $permCatChanged) {
                $set = [
                    'TreeLeft' => $node['left'],
                    'TreeRight' => $node['right'],
                    'Depth' => $node['depth'],
                    'Sort' => $node['left'],
                    'ParentCategoryID' => $parentCategoryID,
                    'PermissionCategoryID' => $permissionCategoryID
                ];

                $this->SQL->update(
                    'Category',
                    $set,
                    ['CategoryID' => $categoryID]
                )->put();

                self::setCache($categoryID, $set);
                $saves[] = array_merge(['CategoryID' => $categoryID], $set);
            }
        }
        return $saves;
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
     * @param $a First element to compare.
     * @param $b Second element to compare.
     * @return int -1, 1, 0 (per usort)
     */
    protected function _treeSort($a, $b) {
        if ($a['left'] > $b['left']) {
            return 1;
        } elseif ($a['left'] < $b['left'])
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
            $this->Validation->addRule('CategorySlug', 'function:validateCategoryUrlCode');
            $this->Validation->applyRule('UrlCode', 'CategorySlug', 'Url code cannot be numeric, contain spaces or be the name of an internal method.');

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

                $this->update($Fields, ['CategoryID' => $CategoryID]);

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
                        $this->SQL->put('Category', ['PermissionCategoryID' => $CategoryID], ['CategoryID' => $CategoryID]);
                    }
                    if ($CustomPoints) {
                        $this->SQL->put('Category', ['PointsCategoryID' => $CategoryID], ['CategoryID' => $CategoryID]);
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
                        $permissions = $permissionModel->pivotPermissions(val('Permission', $FormPostValues, []), ['JunctionID' => $CategoryID]);
                    }
                    $permissionModel->saveAll($permissions, ['JunctionID' => $CategoryID, 'JunctionTable' => 'Category']);

                    if (!$Insert) {
                        // Figure out my last permission and tree info.
                        $Data = $this->SQL->select('PermissionCategoryID, TreeLeft, TreeRight')->from('Category')->where('CategoryID', $CategoryID)->get()->firstRow(DATASET_TYPE_ARRAY);

                        // Update this category's permission.
                        $this->SQL->put('Category', ['PermissionCategoryID' => $CategoryID], ['CategoryID' => $CategoryID]);

                        // Update all of my children that shared my last category permission.
                        $this->SQL->put(
                            'Category',
                            ['PermissionCategoryID' => $CategoryID],
                            ['TreeLeft >' => $Data['TreeLeft'], 'TreeRight <' => $Data['TreeRight'], 'PermissionCategoryID' => $Data['PermissionCategoryID']]
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
                            ['PermissionCategoryID' => $NewPermissionID],
                            ['PermissionCategoryID' => $CategoryID]
                        );

                        self::clearCache();
                    }

                    // Delete my custom permissions.
                    $this->SQL->delete(
                        'Permission',
                        ['JunctionTable' => 'Category', 'JunctionColumn' => 'PermissionCategoryID', 'JunctionID' => $CategoryID]
                    );
                }
            }

            // Force the user permissions to refresh.
            Gdn::userModel()->clearPermissions();

            // Let the world know we succeeded in our mission.
            $this->EventArguments['CategoryID'] = $CategoryID;
            $this->fireEvent('AfterSaveCategory');
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
     * @param int $categoryID
     * @param mixed $set
     */
    public function saveUserTree($categoryID, $set) {
        $categories = $this->getSubtree($categoryID);
        foreach ($categories as $category) {
            $this->SQL->replace(
                'UserCategory',
                $set,
                ['UserID' => Gdn::session()->UserID, 'CategoryID' => $category['CategoryID']]
            );
        }
        $key = 'UserCategory_'.Gdn::session()->UserID;
        Gdn::cache()->remove($key);
    }

    /**
     * Grab and update the category cache
     *
     * @since 2.0.18
     * @access public
     * @param int|bool $iD
     * @param array|bool $data
     */
    public static function setCache($iD = false, $data = false) {
        self::instance()->collection->refreshCache((int)$iD);

        $categories = Gdn::cache()->get(self::CACHE_KEY);
        self::$Categories = null;

        if (!$categories) {
            return;
        }

        // Extract actual category list, remove key if malformed
        if (!$iD || !is_array($categories) || !array_key_exists('categories', $categories)) {
            Gdn::cache()->remove(self::CACHE_KEY);
            return;
        }
        $categories = $categories['categories'];

        // Check for category in list, otherwise remove key if not found
        if (!array_key_exists($iD, $categories)) {
            Gdn::cache()->remove(self::CACHE_KEY);
            return;
        }

        $category = $categories[$iD];
        $category = array_merge($category, $data);
        $categories[$iD] = $category;

        // Update memcache entry
        self::$Categories = $categories;
        unset($categories);
        self::buildCache($iD);

        self::joinUserData(self::$Categories, true);
    }

    /**
     * Set a property on a category.
     *
     * @param int $iD
     * @param array|string $property
     * @param bool|false $value
     * @return array|string
     */
    public function setField($iD, $property, $value = false) {
        if (!is_array($property)) {
            $property = [$property => $value];
        }

        if (isset($property['AllowedDiscussionTypes']) && is_array($property['AllowedDiscussionTypes'])) {
            $property['AllowedDiscussionTypes'] = dbencode($property['AllowedDiscussionTypes']);
        }

        $this->SQL->put($this->Name, $property, ['CategoryID' => $iD]);

        // Set the cache.
        self::setCache($iD, $property);

        return $property;
    }

    /**
     * Set a property of a currently-loaded category in memory.
     *
     * @param $iD
     * @param $property
     * @param $value
     * @return bool
     */
    public static function setLocalField($iD, $property, $value) {
        // Make sure the field is here.
        if (!self::$Categories === null) {
            self::categories(-1);
        }

        if (isset(self::$Categories[$iD])) {
            self::$Categories[$iD][$property] = $value;
            return true;
        }
        return false;
    }

    /**
     * Set the most recent post info for a category, based on itself and all its children.
     *
     * @param int $categoryID
     * @param bool $updateAncestors
     */
    public function refreshAggregateRecentPost($categoryID, $updateAncestors = false) {
        $categories = CategoryModel::getSubtree($categoryID, true);
        $categoryIDs = array_column($categories, 'CategoryID');

        $discussion = $this->SQL->getWhere(
            'Discussion',
            ['CategoryID' => $categoryIDs],
            'DateLastComment',
            'desc',
        1)->firstRow(DATASET_TYPE_ARRAY);
        $comment = null;

        if (is_array($discussion)) {
            $comment = CommentModel::instance()->getID($discussion['LastCommentID']);
            $this->setField($categoryID, 'LastCategoryID', $discussion['CategoryID']);
        }

        $db = static::postDBFields($discussion, $comment);
        $cache = static::postCacheFields($discussion, $comment);
        $this->setField($categoryID, $db);
        static::setCache($categoryID, $cache);

        if ($updateAncestors) {
            // Grab this category's ancestors, pop this category off the end and reverse order for traversal.
            $ancestors = self::instance()->collection->getAncestors($categoryID, true);
            array_pop($ancestors);
            $ancestors = array_reverse($ancestors);
            $lastInserted = strtotime($db['LastDateInserted']) ?: 0;
            if (is_array($discussion) && array_key_exists('CategoryID', $discussion)) {
                $lastCategoryID = $discussion['CategoryID'];
            } else {
                $lastCategoryID = false;
            }

            foreach ($ancestors as $row) {
                // If this ancestor already has a newer discussion, stop.
                if ($lastInserted < strtotime($row['LastDateInserted'])) {
                    // Make sure this latest discussion is even valid.
                    $lastDiscussion = DiscussionModel::instance()->getID($row['LastDiscussionID']);
                    if ($lastDiscussion) {
                        break;
                    }
                }
                $currentCategoryID = val('CategoryID', $row);
                self::instance()->setField($currentCategoryID, $db);
                CategoryModel::setCache($currentCategoryID, $cache);

                if ($lastCategoryID) {
                    self::instance()->setField($currentCategoryID, 'LastCategoryID', $lastCategoryID);
                }
            }
        }
    }

    /**
     *
     *
     * @param $categoryID
     */
    public function setRecentPost($categoryID) {
        $row = $this->SQL->getWhere('Discussion', ['CategoryID' => $categoryID], 'DateLastComment', 'desc', 1)->firstRow(DATASET_TYPE_ARRAY);

        $fields = ['LastCommentID' => null, 'LastDiscussionID' => null];

        if ($row) {
            $fields['LastCommentID'] = $row['LastCommentID'];
            $fields['LastDiscussionID'] = $row['DiscussionID'];
        }
        $this->setField($categoryID, $fields);
        self::setCache($categoryID, ['LastTitle' => null, 'LastUserID' => null, 'LastDateInserted' => null, 'LastUrl' => null]);
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
            $construct = Gdn::database()->structure();
            $construct->table('Category')
                ->column('TreeLeft', 'int', true)
                ->column('TreeRight', 'int', true)
                ->column('Depth', 'int', true)
                ->column('CountComments', 'int', '0')
                ->column('LastCommentID', 'int', true)
                ->set(0, 0);

            // Insert the root node
            if ($this->SQL->getWhere('Category', ['CategoryID' => -1])->numRows() == 0) {
                $this->SQL->insert('Category', ['CategoryID' => -1, 'TreeLeft' => 1, 'TreeRight' => 4, 'Depth' => 0, 'InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Gdn_Format::toDateTime(), 'DateUpdated' => Gdn_Format::toDateTime(), 'Name' => t('Root Category Name', 'Root'), 'UrlCode' => '', 'Description' => t('Root Category Description', 'Root of category tree. Users should never see this.')]);
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
     * @param object $data SQL result.
     */
    public static function addCategoryColumns($data) {
        $result = &$data->result();
        $result2 = $result;
        foreach ($result as &$category) {
            if (!property_exists($category, 'CountAllDiscussions')) {
                $category->CountAllDiscussions = $category->CountDiscussions;
            }

            if (!property_exists($category, 'CountAllComments')) {
                $category->CountAllComments = $category->CountComments;
            }

            // Calculate the following field.
            $following = !((bool)val('Archived', $category) || (bool)val('Unfollow', $category));
            $category->Following = $following;

            $dateMarkedRead = val('DateMarkedRead', $category);
            $userDateMarkedRead = val('UserDateMarkedRead', $category);

            if (!$dateMarkedRead) {
                $dateMarkedRead = $userDateMarkedRead;
            } elseif ($userDateMarkedRead && Gdn_Format::toTimestamp($userDateMarkedRead) > Gdn_Format::toTimeStamp($dateMarkedRead))
                $dateMarkedRead = $userDateMarkedRead;

            // Set appropriate Last* columns.
            setValue('LastTitle', $category, val('LastDiscussionTitle', $category, null));
            $lastDateInserted = val('LastDateInserted', $category, null);

            if (val('LastCommentUserID', $category) == null) {
                setValue('LastCommentUserID', $category, val('LastDiscussionUserID', $category, null));
                setValue('DateLastComment', $category, val('DateLastDiscussion', $category, null));
                setValue('LastUserID', $category, val('LastDiscussionUserID', $category, null));

                $lastDiscussion = arrayTranslate($category, [
                    'LastDiscussionID' => 'DiscussionID',
                    'CategoryID' => 'CategoryID',
                    'LastTitle' => 'Name']);

                setValue('LastUrl', $category, discussionUrl($lastDiscussion, false, '/').'#latest');

                if (is_null($lastDateInserted)) {
                    setValue('LastDateInserted', $category, val('DateLastDiscussion', $category, null));
                }
            } else {
                $lastDiscussion = arrayTranslate($category, [
                    'LastDiscussionID' => 'DiscussionID',
                    'CategoryID' => 'CategoryID',
                    'LastTitle' => 'Name'
                ]);

                setValue('LastUserID', $category, val('LastCommentUserID', $category, null));
                setValue('LastUrl', $category, discussionUrl($lastDiscussion, false, '/').'#latest');

                if (is_null($lastDateInserted)) {
                    setValue('LastDateInserted', $category, val('DateLastComment', $category, null));
                }
            }

            $lastDateInserted = val('LastDateInserted', $category, null);
            if ($dateMarkedRead) {
                if ($lastDateInserted) {
                    $category->Read = Gdn_Format::toTimestamp($dateMarkedRead) >= Gdn_Format::toTimestamp($lastDateInserted);
                } else {
                    $category->Read = true;
                }
            } else {
                $category->Read = false;
            }

            foreach ($result2 as $category2) {
                if ($category2->TreeLeft > $category->TreeLeft && $category2->TreeRight < $category->TreeRight) {
                    $category->CountAllDiscussions += $category2->CountDiscussions;
                    $category->CountAllComments += $category2->CountComments;
                }
            }
        }
    }

    /**
     * Build URL to a category page.
     *
     * @param $category
     * @param string $page
     * @param bool|true $withDomain
     * @return string
     */
    public static function categoryUrl($category, $page = '', $withDomain = true) {
        if (function_exists('CategoryUrl')) {
            return categoryUrl($category, $page, $withDomain);
        }

        if (is_string($category)) {
            $category = CategoryModel::categories($category);
        }
        $category = (array)$category;

        $result = '/categories/'.rawurlencode($category['UrlCode']);
        if ($page && $page > 1) {
            $result .= '/p'.$page;
        }
        return url($result, $withDomain);
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
     * @param string $type
     * @param int $offset A value, positive or negative, to offset a category's current aggregate post counts.
     */
    private static function adjustAggregateCounts($categoryID, $type, $offset) {
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

                Gdn::sql()->update('Category');
                switch ($type) {
                    case self::AGGREGATE_COMMENT:
                        Gdn::sql()->set('CountAllComments', "CountAllComments + {$offset}", false);
                        break;
                    case self::AGGREGATE_DISCUSSION:
                        Gdn::sql()->set('CountAllDiscussions', "CountAllDiscussions + {$offset}", false);
                        break;
                }
                Gdn::sql()->where('CategoryID', $targetID)->put();
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
     * @param int $categoryID A valid category ID.
     * @param string $type One of the CategoryModel::AGGREGATE_* constants.
     * @param int $offset The value to increment the aggregate counts by.
     */
    public static function incrementAggregateCount($categoryID, $type, $offset = 1) {
        // Make sure we're dealing with a positive offset.
        $offset = abs($offset);
        self::adjustAggregateCounts($categoryID, $type, $offset);
    }

    /**
     * Move upward through the category tree, decrementing aggregate post counts.
     *
     * @param int $categoryID A valid category ID.
     * @param string $type One of the CategoryModel::AGGREGATE_* constants.
     * @param int $offset The value to increment the aggregate counts by.
     */
    public static function decrementAggregateCount($categoryID, $type, $offset = 1) {
        // Make sure we're dealing with a negative offset.
        $offset = (-1 * abs($offset));
        self::adjustAggregateCounts($categoryID, $type, $offset);
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

    /**
     * Search for categories by name.
     *
     * @param string $name The whole or partial category name to search for.
     * @param bool $expandParent Expand the parent category record.
     * @param int|null $limit Limit the total number of results.
     * @param int|null $offset Offset the results.
     * @return array
     */
    public function searchByName($name, $expandParent = false, $limit = null, $offset = null) {
        if ($limit !== null && filter_var($limit, FILTER_VALIDATE_INT) === false) {
            $limit = null;
        }
        if ($offset !== null && filter_var($offset, FILTER_VALIDATE_INT) === false) {
            $offset = null;
        }

        $query = $this->SQL
            ->from('Category c')
            ->where('CategoryID >', 0)
            ->where('DisplayAs <>', 'Heading')
            ->like('Name', $name)
            ->orderBy('Name');
        if ($limit !== null) {
            $offset = ($offset === null ? false : $offset);
            $query->limit($limit, $offset);
        }

        $categories = $query->get()->resultArray();

        $result = [];
        foreach ($categories as $category) {
            self::calculate($category);
            if ($category['DisplayAs'] === 'Heading') {
                continue;
            }

            self::calculateUser($category);

            if ($expandParent) {
                if ($category['ParentCategoryID'] > 0) {
                    $parent = static::categories($category['ParentCategoryID']);
                    self::calculate($category);
                    $category['Parent'] = $parent;
//                } else {
//                    $parent = null;
                }
            }

            $result[] = $category;
        }

        return $result;
    }

    /**
     * Update a category and its parents' LastCategoryID with the specified category's ID.
     *
     * @param int $categoryID A valid category ID.
     */
    public static function setAsLastCategory($categoryID) {
        $categories = self::instance()->collection->getAncestors($categoryID, true);

        foreach ($categories as $current) {
            $targetID = val('CategoryID', $current);
            self::instance()->setField($targetID, ['LastCategoryID' => $categoryID]);
        }
    }
}
