<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

/**
 * Manages categories as a whole.
 *
 * This is a bridge class to aid in refactoring. This functionality will be rolled into the {@link CategoryModel}.
 */
class CategoryCollection {
    /**
     * @var string The cache key prefix that stores categories by ID.
     */
    private static $CACHE_CATEGORY = '/cat/';
    /**
     * @var string The cache key prefix that stores category IDs by slug (URL code).
     */
    private static $CACHE_CATEGORY_SLUG = '/catslug/';

    /**
     * @var Gdn_Cache The cache dependency.
     */
    private $cache;

    /**
     * @var int
     */
    private $cacheInc;

    /**
     * @var Gdn_Configuration The config dependency.
     */
    private $config;

    /**
     * @var Gdn_SQLDriver The database layer dependency.
     */
    private $sql;

    /**
     * @var array The categories that have been retrieved, indexed by categoryID.
     */
    private $categories = [];

    /**
     * @var array An array that maps category slug to category ID.
     */
    private $categorySlugs = [];

    /**
     * @var Gdn_Schema The category table schema.
     */
    private $schema;

    /**
     * Initialize a new instance of the {@link CategoryCollection} class.
     *
     * @param Gdn_SQLDriver|null $sql The database layer dependency.
     * @param Gdn_Cache|null $cache The cache layer dependency.
     */
    public function __construct(Gdn_SQLDriver $sql = null, Gdn_Cache $cache = null) {
        if ($sql === null) {
            $sql = Gdn::sql();
        }
        $this->sql = $sql;

        if ($cache === null) {
            $cache = Gdn::cache();
        }
        $this->cache = $cache;
    }

    /**
     * Calculate dynamic data on a category.
     *
     * @param array &$category The category to calculate.
     */
    private function calculate(&$category) {
        $category['CountAllDiscussions'] = $category['CountDiscussions'];
        $category['CountAllComments'] = $category['CountComments'];
//        $category['Url'] = self::categoryUrl($category, false, '/');
        $category['ChildIDs'] = [];
//        if (val('Photo', $category)) {
//            $category['PhotoUrl'] = Gdn_Upload::url($category['Photo']);
//        } else {
//            $category['PhotoUrl'] = '';
//        }

        if ($category['DisplayAs'] == 'Default') {
            if ($category['Depth'] <= $this->config('Vanilla.Categories.NavDepth', 0)) {
                $category['DisplayAs'] = 'Categories';
            } elseif ($category['Depth'] == ($this->config('Vanilla.Categories.NavDepth', 0) + 1) && $this->config('Vanilla.Categories.DoHeadings')) {
                $category['DisplayAs'] = 'Heading';
            } else {
                $category['DisplayAs'] = 'Discussions';
            }
        }

        if (!val('CssClass', $category)) {
            $category['CssClass'] = 'Category-'.$category['UrlCode'];
        }

        if (isset($category['AllowedDiscussionTypes']) && is_string($category['AllowedDiscussionTypes'])) {
            $category['AllowedDiscussionTypes'] = dbdecode($category['AllowedDiscussionTypes']);
        }
    }

    /**
     * Get the cache increment.
     *
     * The cache is flushed after major operations by incrementing a scoped key.
     */
    private function getCacheInc() {
        if ($this->cacheInc === null) {
            $this->cacheInc = (int)$this->cache->get(self::$CACHE_CATEGORY.'inc');
        }
        return $this->cacheInc;
    }

    /**
     * Flush the entire category cache.
     */
    public function flushCache() {
        $this->categories = [];
        $this->categorySlugs = [];
        $this->cache->increment(self::$CACHE_CATEGORY.'inc', 1, [Gdn_Cache::FEATURE_INITIAL => 1]);
    }

    /**
     * Generate a full cache key.
     *
     * All cache keys should be generated using this function to support cache increments.
     *
     * @param string $type One of the **$CACHE_*** pseudo-constants.
     * @param string|int $id The identifier in the cache.
     * @return string Returns the cache key.
     */
    private function cacheKey($type, $id) {
        switch ($type) {
            case self::$CACHE_CATEGORY;
            case self::$CACHE_CATEGORY_SLUG;
                $r = $this->getCacheInc().$type.$id;
                return $r;
            default:
                throw new \InvalidArgumentException("Cache type '$type' is invalid.'", 500);
        }
    }

    /**
     * Get a value from the config.
     *
     * @param string $key The config key.
     * @param mixed $default The default to return if the config isn't found.
     * @return mixed Returns the config value or {@link $default} if it isn't found.
     */
    private function config($key, $default = null) {
        if ($this->config !== null) {
            return $this->config->get($key, $default);
        } else {
            return $default;
        }
    }

    /**
     * Get the config.
     *
     * @return Gdn_Configuration Returns the config.
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * Set the config.
     *
     * @param Gdn_Configuration $config The config.
     * @return CategoryCollection Returns `$this` for fluent calls.
     */
    public function setConfig($config) {
        $this->config = $config;
        return $this;
    }

    /**
     * Get the schema.
     *
     * @return Gdn_Schema Returns the schema.
     */
    private function getSchema() {
        if ($this->schema === null) {
            $this->schema = new Gdn_Schema('Category', $this->sql->Database);
        }
        return $this->schema;
    }

    /**
     * Lookup a category by its URL slug.
     *
     * @param string $code The URL slug of the category.
     * @return array|null Returns a category or **null** if one isn't found.
     */
    public function getByUrlCode($code) {
        return $this->get($code);
    }

    /**
     * Lookup a category by either ID or slug.
     *
     * @param int $categoryID The category ID to get.
     * @return array|null Returns a category or **null** if one isn't found.
     */
    public function get($categoryID) {
        // Figure out the ID.
        if (is_int($categoryID)) {
            $id = $categoryID;
        } elseif (isset($this->categorySlugs[$categoryID])) {
            $id = $this->categorySlugs[$categoryID];
        } else {
            // The ID still might not be found here.
            $id = $this->cache->get($this->cacheKey(self::$CACHE_CATEGORY_SLUG, $categoryID));
        }

        if ($id) {
            $id = (int)$id;

            if (isset($this->categories[$id])) {
                return $this->categories[$id];
            } else {
                $category = $this->cache->get($this->cacheKey(self::$CACHE_CATEGORY, $id));

                if (!empty($category)) {
                    $this->categories[$id] = $category;
                    $this->categorySlugs[$category['UrlCode']] = $id;
                    return $category;
                }

                $category = $this->sql->getWhere('Category', ['CategoryID' => $id])->firstRow(DATASET_TYPE_ARRAY);
            }
        } else {
            $category = $this->sql->getWhere('Category', ['UrlCode' => $categoryID])->firstRow(DATASET_TYPE_ARRAY);
        }

        if (!empty($category)) {
            // This category came from the database, so must be calculated.
            $this->calculate($category);

            $this->categories[(int)$category['CategoryID']] = $category;
            $this->categorySlugs[$category['UrlCode']] = (int)$category['CategoryID'];

            $this->cache->store(
                $this->cacheKey(self::$CACHE_CATEGORY, $category['CategoryID']),
                $category
            );
            $this->cache->store(
                $this->cacheKey(self::$CACHE_CATEGORY_SLUG, $category['UrlCode']),
                (int)$category['CategoryID']
            );

            return $category;
        } else {
            // Mark the category as not found for future searches.
            if ($id) {
                $this->categories[$id] = false;
            }
            if (is_string($categoryID)) {
                $this->categorySlugs[$categoryID] = false;
            }

            return null;
        }
    }

    /**
     * Get the children of a category.
     *
     * @param int $categoryID The category to get the children for.
     * @return array Returns an array of categories.
     */
    public function getChildren($categoryID) {
        $children = $this
            ->sql
            ->select('CategoryID')
            ->getWhere('Category', ['ParentCategoryID' => $categoryID])
            ->resultArray();
        $ids = array_column($children, 'CategoryID');
        $categories = $this->getMulti($ids);
        return $categories;
    }

    /**
     * Get several categories by ID.
     *
     * @param array $categoryIDs An array of category IDs.
     * @return array Returns an array of categories, indexed by ID.
     */
    public function getMulti(array $categoryIDs) {
        $categories = array_fill_keys($categoryIDs, null);

        // Look in our internal cache.
        $internalCategories = array_intersect_key($this->categories, $categories);
        $categories = array_replace($categories, $internalCategories);

        // Look in the global cache.
        $keys = [];
        foreach (array_diff_key($categories, $internalCategories) as $id => $null) {
            $keys[] = $this->cacheKey(self::$CACHE_CATEGORY, $id);
        }
        if (!empty($keys)) {
            $cacheCategories = $this->cache->get($keys);
            foreach ($cacheCategories as $key => $category) {
                $this->categories[(int)$category['CategoryID']] = $category;
                $this->categorySlugs[$category['UrlCode']] = (int)$category['CategoryID'];

                $categories[(int)$category['CategoryID']] = $category;
            }
        }

        // Look in the database.
        $dbCategoryIDs = [];
        foreach ($categories as $id => $row) {
            if (!$row) {
                $dbCategoryIDs[] = $id;
            }
        }
        $dbCategories = $this->sql->getWhere('Category', ['CategoryID' => $dbCategoryIDs])->resultArray();
        foreach ($dbCategories as &$category) {
            $this->calculate($category);

            $this->cache->store(
                $this->cacheKey(self::$CACHE_CATEGORY, $category['CategoryID']),
                $category
            );
            $this->cache->store(
                $this->cacheKey(self::$CACHE_CATEGORY_SLUG, $category['UrlCode']),
                (int)$category['CategoryID']
            );

            $this->categories[(int)$category['CategoryID']] = $category;
            $this->categorySlugs[$category['UrlCode']] = (int)$category['CategoryID'];

            $categories[(int)$category['CategoryID']] = $category;
        }

        return $categories;
    }

    /**
     * Insert a new category, handling its tree properties and caching.
     *
     * This method is currently only to to be used in a support role.
     *
     * @param array $category The new category.
     */
    public function insert(array $category) {
        $category += [
            'DateInserted' => Gdn_Format::toDateTime(),
            'InsertUserID' => 1,
            'ParentCategoryID' => -1,
        ];

        // Filter out fields that aren't in the table.
        $category = array_intersect_key($category, $this->getSchema()->fields());
        $categoryID = $this->sql->insert('Category', $category);

        if ($categoryID) {
            // Update my parent's count.
            $this->sql->put('Category', ['CountCategories+' => 1], ['CategoryID' => $category['ParentCategoryID']]);

            $this->refreshCache($category['CategoryID']);
            $this->refreshCache($category['ParentCategoryID']);
        }

        return $categoryID;
    }

    /**
     * Update an existing category, handling its tree properties and caching.
     *
     * @param array $category The category to update.
     * @return bool Returns **true** if the category updated or **false** otherwise.
     */
    public function update(array $category) {
        if (empty($category['CategoryID'])) {
            throw new Gdn_UserException("Category ID is required.");
        }

        $category += [
            'DateUpdated' => Gdn_Format::toDateTime(),
            'UpdateUserID' => 1,
        ];

        // Get the current category.
        $oldCategory = $this->get($category['CategoryID']);

        if (!$oldCategory) {
            $inserted = $this->insert($category);
            if ($inserted) {
                return $category['CategoryID'];
            } else {
                return false;
            }
        } else {
            $this->sql->put('Category', $category, ['CategoryID' => $category['CategoryID']]);
            $this->refreshCache($category['CategoryID']);

            // Did my parent change?
            if ((int)$oldCategory['ParentCategoryID'] !== (int)$category['ParentCategoryID']) {
                // Increment the new parent and decrement the old parent.
                $this->sql->put(
                    'Category',
                    ['CountCategories-' => 1],
                    ['CategoryID' => $oldCategory['ParentCategoryID']]
                );
                $this->sql->put(
                    'Category',
                    ['CountCategories+' => 1],
                    ['CategoryID' => $category['ParentCategoryID']]
                );
                $this->refreshCache($oldCategory['ParentCategoryID']);
                $this->refreshCache($category['ParentCategoryID']);
            }
            return true;
        }
    }

    /**
     * Refresh a category in the cache from the database.
     *
     * This function is public for now, but should only be called from within the {@link CategoryModel}. Eventually it
     * will be privatized.
     *
     * @param int $categoryID The category to refresh.
     * @return bool Returns **true** if the category was refreshed or **false** otherwise.
     */
    public function refreshCache($categoryID) {
        $category = $this->sql->getWhere('Category', ['CategoryID' => $categoryID])->firstRow(DATASET_TYPE_ARRAY);
        if ($category) {
            $this->calculate($category);
            $this->cache->store(
                $this->cacheKey(self::$CACHE_CATEGORY, $category['CategoryID']),
                $category
            );
            $this->cache->store(
                $this->cacheKey(self::$CACHE_CATEGORY_SLUG, $category['UrlCode']),
                (int)$category['CategoryID']
            );

            $this->categories[(int)$category['CategoryID']] = $category;
            $this->categorySlugs[$category['UrlCode']] = (int)$category['CategoryID'];
            return true;
        } else {
            return false;
        }
    }
}
