<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\EventManager;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Models\ModelCache;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\TreeBuilder;

/**
 * Manages categories as a whole.
 *
 * This is a bridge class to aid in refactoring. This functionality will be rolled into the {@link CategoryModel}.
 */
class CategoryCollection
{
    use \Vanilla\Events\DirtyRecordTrait;

    /**
     * @var string The cache key prefix that stores categories by ID.
     */
    private const CACHE_CATEGORY = "/cat/";

    /**
     * @var string The cache key prefix that stores category IDs by slug (URL code).
     */
    private const CACHE_CATEGORY_SLUG = "/catslug/";

    /**
     * @var string The cache key prefix that stores category descendant IDs by slug (URL code).
     */
    private const CACHE_CATEGORY_DESCENDANTS = "/catdescendants/";

    /**
     * @var string The cache key prefix that stores category descendant IDs by slug (URL code).
     */
    private const CACHE_CATEGORY_CHILD_IDS = "/cat_child_ids/";

    /**
     * @var int The absolute select limit of the categories.
     */
    private $absoluteLimit;

    /**
     * @var Gdn_Cache The cache dependency.
     */
    private $cache;

    /** @var ModelCache */
    private $modelCache;

    /** @var bool */
    private $cacheReadOnly = false;

    /**
     * @var int
     */
    private $cacheInc;

    /**
     * @var callable The callback used to calculate individual categories.
     */
    private $staticCalculator;

    /**
     * @var callable The callback used to calculate request specific data on a calculator.
     */
    private $userCalculator;

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
    public function __construct(Gdn_SQLDriver $sql = null, Gdn_Cache $cache = null)
    {
        $this->absoluteLimit = c("Vanilla.Categories.QueryLimit", 300);

        if ($sql === null) {
            $sql = Gdn::sql();
        }
        $this->sql = $sql;

        $this->cache = $cache ?? (Gdn::cache() ?? new Gdn_Dirtycache());
        $this->modelCache = new ModelCache("catcollection", $this->cache);

        $this->setStaticCalculator([$this, "defaultCalculator"]);
        $this->setUserCalculator(function (&$category) {
            // do nothing
        });
    }

    /**
     * Get a clean SQL driver instance.
     *
     * @return \Gdn_SQLDriver
     */
    protected function createSql(): \Gdn_SQLDriver
    {
        $sql = clone $this->sql;
        $sql->reset();
        return $sql;
    }

    /**
     * Get the calculator.
     *
     * @return callable Returns the calculator.
     */
    public function getStaticCalculator()
    {
        return $this->staticCalculator;
    }

    /**
     * Set the calculator.
     *
     * @param callable $staticCalculator The new calculator.
     * @return CategoryCollection Returns `$this` for fluent calls.
     */
    public function setStaticCalculator(callable $staticCalculator)
    {
        $this->staticCalculator = $staticCalculator;
        return $this;
    }

    /**
     * Flush our local in memory caches.
     */
    public function flushLocalCache()
    {
        $this->categories = [];
        $this->categorySlugs = [];
    }

    /**
     * Flush the entire category cache.
     */
    public function flushCache()
    {
        $this->flushLocalCache();
        $this->modelCache->invalidateAll();
        $this->cache->increment(self::CACHE_CATEGORY . "inc", 1, [Gdn_Cache::FEATURE_INITIAL => 1]);
        $this->cacheInc = null;
        /** @var EventManager $eventManager */
        $eventManager = \Gdn::getContainer()->get(EventManager::class);
        $eventManager->fire("flushCategoryCache");
    }

    /**
     * Get the config.
     *
     * @return Gdn_Configuration Returns the config.
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set the config.
     *
     * @param Gdn_Configuration $config The config.
     * @return CategoryCollection Returns `$this` for fluent calls.
     */
    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Lookup a category by its URL slug.
     *
     * @param string $code The URL slug of the category.
     * @return array|null Returns a category or **null** if one isn't found.
     */
    public function getByUrlCode($code)
    {
        return $this->get($code);
    }

    /**
     * Lookup a category by either ID or slug.
     *
     * @param int|string $categoryID The category ID to get.
     * @return array|null Returns a category or **null** if one isn't found.
     */
    public function get($categoryID): ?array
    {
        // Figure out the ID.
        if (empty($categoryID)) {
            return null;
        } elseif (is_int($categoryID)) {
            $id = $categoryID;
        } elseif (!empty($this->categorySlugs[strtolower($categoryID)])) {
            $id = $this->categorySlugs[strtolower($categoryID)];
        } else {
            // The ID still might not be found here.
            $id = $this->cache->get($this->cacheKey(self::CACHE_CATEGORY_SLUG, $categoryID));
        }

        if ($id) {
            $id = (int) $id;

            if (isset($this->categories[$id])) {
                return $this->categories[$id] ?: null;
            } else {
                $cacheKey = $this->cacheKey(self::CACHE_CATEGORY, $id);
                $category = $this->cache->get($cacheKey);

                if (!empty($category)) {
                    $this->calculateDynamic($category);
                    $this->categories[$id] = $category;
                    $this->categorySlugs[strtolower($category["UrlCode"])] = $id;
                    return $category;
                }

                $category = $this->sql->getWhere("Category", ["CategoryID" => $id])->firstRow(DATASET_TYPE_ARRAY);
            }
        } else {
            $category = $this->sql->getWhere("Category", ["UrlCode" => $categoryID])->firstRow(DATASET_TYPE_ARRAY);
        }

        if (!empty($category)) {
            // This category came from the database, so must be calculated.
            $this->calculateStatic($category);

            $this->cacheStore($this->cacheKey(self::CACHE_CATEGORY, $category["CategoryID"]), $category);
            $this->cacheStore(
                $this->cacheKey(self::CACHE_CATEGORY_SLUG, $category["UrlCode"]),
                (int) $category["CategoryID"]
            );

            $this->calculateDynamic($category);
            $this->setLocal($category);

            return $category;
        } else {
            // Mark the category as not found for future searches.
            if ($id) {
                $this->categories[$id] = false;
            }
            if (is_string($categoryID)) {
                $this->categorySlugs[strtolower($categoryID)] = false;
            }

            return null;
        }
    }

    /**
     * Set a category in the local cache.
     *
     * @param array $category
     */
    public function setLocal(array $category): void
    {
        $this->categories[(int) $category["CategoryID"]] = $category;
        $this->categorySlugs[strtolower($category["UrlCode"])] = (int) $category["CategoryID"];
    }

    /**
     * Check whether or not the local cache has a category.
     *
     * @param int $id The ID of the category.
     * @return bool
     */
    public function hasLocal($id): bool
    {
        return isset($this->categories[$id]);
    }

    /**
     * Generate a full cache key.
     *
     * All cache keys should be generated using this function to support cache increments.
     *
     * @param string $type One of the **$CACHE_*** pseudo-constants.
     * @param string|int|array $id The identifier in the cache.
     * @return string Returns the cache key.
     */
    private function cacheKey(string $type, $id): string
    {
        if (is_string($id)) {
            $id = strtolower($id);
        } elseif (is_array($id)) {
            $id = strtolower(implode($id));
        }
        return $this->getCacheInc() . $type . $id;
    }

    /**
     * Get the cache increment.
     *
     * The cache is flushed after major operations by incrementing a scoped key.
     */
    private function getCacheInc(): int
    {
        if ($this->cacheInc === null) {
            $this->cacheInc = (int) $this->cache->get(self::CACHE_CATEGORY . "inc");
        }
        return $this->cacheInc;
    }

    /**
     * Calculate static data on a category.
     *
     * @param array &$category The category to calculate.
     */
    private function calculateStatic(&$category)
    {
        if ($category["CategoryID"] > 0) {
            call_user_func_array($this->staticCalculator, [&$category]);
        }
    }

    /**
     * Calculate request-specific data on a category.
     *
     * @param array &$category The category to calculate.
     */
    private function calculateDynamic(&$category)
    {
        if ($category["CategoryID"] > 0) {
            call_user_func_array($this->userCalculator, [&$category]);
        }
    }

    /**
     * Get the children of a category.
     *
     * @param int $categoryID The category to get the children for.
     * @return array Returns an array of categories.
     */
    public function getChildren($categoryID)
    {
        $categories = $this->getChildrenByParents([$categoryID]);
        return $categories;
    }

    /**
     * Get all of the ancestor categories above this one.
     *
     * @param int|string $categoryID The category ID or url code.
     * @param bool $includeHeadings Whether or not to include heading categories.
     * @return array
     */
    public function getAncestors($categoryID, $includeHeadings = false)
    {
        $result = [];

        $category = $this->get($categoryID);

        if (!$category) {
            return $result;
        }

        // Build up the ancestor array by tracing back through parents.
        $result[] = $category;
        $max = 20;
        while ($category = $this->get($category["ParentCategoryID"])) {
            // Check for an infinite loop.
            if ($max <= 0) {
                break;
            }
            $max--;

            if ($category["CategoryID"] == -1) {
                break;
            }

            if ($includeHeadings || $category["DisplayAs"] !== "Heading") {
                $result[] = $category;
            }
        }
        $result = array_reverse($result);
        return $result;
    }

    /**
     * Get several categories by ID.
     *
     * @param array $categoryIDs An array of category IDs.
     * @return array Returns an array of categories, indexed by ID.
     */
    public function getMulti(array $categoryIDs)
    {
        $categories = array_fill_keys($categoryIDs, null);

        // Look in our internal cache.
        $internalCategories = array_intersect_key($this->categories, $categories);
        $categories = array_replace($categories, $internalCategories);

        // Look in the global cache.
        $keys = [];
        foreach (array_diff_key($categories, $internalCategories) as $id => $null) {
            $keys[] = $this->cacheKey(self::CACHE_CATEGORY, $id);
        }
        if (!empty($keys)) {
            $cacheCategories = $this->cache->get($keys);
            if (!empty($cacheCategories)) {
                foreach ($cacheCategories as $key => $category) {
                    $this->calculateDynamic($category);
                    $this->categories[(int) $category["CategoryID"]] = $category;
                    $this->categorySlugs[strtolower($category["UrlCode"])] = (int) $category["CategoryID"];

                    $categories[(int) $category["CategoryID"]] = $category;
                }
            }
        }

        // Look in the database.
        $dbCategoryIDs = [];
        foreach ($categories as $id => $row) {
            if (!$row) {
                $dbCategoryIDs[] = $id;
            }
        }
        if (!empty($dbCategoryIDs)) {
            $dbCategories = $this->sql->getWhere("Category", ["CategoryID" => $dbCategoryIDs])->resultArray();
            foreach ($dbCategories as &$category) {
                $this->calculateStatic($category);

                $this->cacheStore($this->cacheKey(self::CACHE_CATEGORY, $category["CategoryID"]), $category);
                $this->cacheStore(
                    $this->cacheKey(self::CACHE_CATEGORY_SLUG, $category["UrlCode"]),
                    (int) $category["CategoryID"]
                );

                $this->calculateDynamic($category);
                $this->categories[(int) $category["CategoryID"]] = $category;
                $this->categorySlugs[strtolower($category["UrlCode"])] = (int) $category["CategoryID"];

                $categories[(int) $category["CategoryID"]] = $category;
            }
        }

        return $categories;
    }

    /**
     * Get all of the categories from a root.
     *
     * @param int $parentID The ID of the parent category.
     * @param array $options An array of options to affect the fetching.
     *
     * - maxDepth: The maximum depth of the tree.
     * - collapseCategories: Stop when looking at a categories that contain categories.
     * - permission: The permission to use when looking at the tree.
     * @return array
     */
    public function getTree($parentID = -1, $options = [])
    {
        $tree = [];
        $categories = [];
        $parentID = $parentID ?: -1;
        $defaultOptions = [
            "maxdepth" => 3,
            "collapsecategories" => false,
            "permission" => "PermsDiscussionsView",
        ];
        $dirtyRecords = $options[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;
        $options = array_change_key_case($options) ?: [];
        $options = $options + $defaultOptions;

        $currentDepth = 1;
        $parents = [$parentID];
        for ($i = 0; $i < $options["maxdepth"]; $i++) {
            $children = $this->getChildrenByParents($parents, $options["permission"], [
                DirtyRecordModel::DIRTY_RECORD_OPT => $dirtyRecords,
            ]);
            if (empty($children)) {
                break;
            }

            // Go through the children and wire them up.
            $parents = [];
            foreach ($children as $child) {
                $category = $child;
                $category["Children"] = [];

                // Skip the fake root.
                if ($category["CategoryID"] == -1) {
                    continue;
                }

                $category["Depth"] = $currentDepth;

                $categories[$category["CategoryID"]] = $category;
                if (!isset($categories[$category["ParentCategoryID"]])) {
                    $tree[] = &$categories[$category["CategoryID"]];
                } else {
                    $categories[$category["ParentCategoryID"]]["Children"][] = &$categories[$category["CategoryID"]];
                }

                if (!$options["collapsecategories"] || !in_array($child["DisplayAs"], ["Categories", "Flat"])) {
                    $parents[] = $child["CategoryID"];
                }
            }

            // Get the IDs for the next depth of children.
            $currentDepth++;
        }

        return $tree;
    }

    /**
     * Get the id's of a category's descendants.
     *
     * @param int $parentID
     *
     * @return array
     */
    public function getDescendantIDs(int $parentID = -1): array
    {
        $cacheKey = $this->cacheKey(self::CACHE_CATEGORY_DESCENDANTS, $parentID);
        $cachedIDs = $this->cache->get($this->cacheKey(self::CACHE_CATEGORY_DESCENDANTS, $parentID));
        if ($cachedIDs !== Gdn_Cache::CACHEOP_FAILURE) {
            return $cachedIDs;
        }

        $resultIDs = [];
        $seenParentIDs = [];

        $sql = $this->createSql();
        $allIDs = $sql
            ->select("CategoryID, ParentCategoryID")
            ->get("Category")
            ->resultArray();
        $categoryIDByParentID = ArrayUtils::arrayColumnArrays($allIDs, "CategoryID", "ParentCategoryID");

        $getChildren = function (int $categoryID) use (
            &$resultIDs,
            &$seenParentIDs,
            &$categoryIDByParentID,
            &$getChildren
        ) {
            if (in_array($categoryID, $seenParentIDs)) {
                return;
            } else {
                $seenParentIDs[] = $categoryID;
            }

            $children = $categoryIDByParentID[$categoryID] ?? [];
            foreach ($children as $childID) {
                $resultIDs[] = $childID;

                $getChildren($childID);
            }
        };

        $getChildren($parentID);

        $this->cacheStore($cacheKey, $resultIDs);
        return $resultIDs;
    }

    /**
     * Build a SQL query to select childCategories. Defaults to only selecting IDs.
     *
     * @param int[] $parentIDs
     * @param string $selects Set specific selects for the query.
     *
     * @return Gdn_SQLDriver
     */
    private function makeGetChildQuery(array $parentIDs, string $selects = "*"): Gdn_SQLDriver
    {
        return $this->createSql()
            ->from("Category")
            ->select($selects)
            ->where("ParentCategoryID", $parentIDs)
            ->where("CategoryID >", 0)
            ->limit($this->absoluteLimit)
            ->orderBy("ParentCategoryID, Sort");
    }

    /**
     * Get child IDs of some parents.
     *
     * @param int[] $parentIDs
     *
     * @return int[]
     */
    public function getChildIDs(array $parentIDs): array
    {
        return $this->modelCache->getCachedOrHydrate(
            ["getChildIDs" => true, "parentIDs" => $parentIDs],
            function () use ($parentIDs) {
                $rows = $this->makeGetChildQuery($parentIDs, "CategoryID")
                    ->get()
                    ->resultArray();
                return array_column($rows, "CategoryID");
            }
        );
    }

    /**
     * Get all of the children of a parent category.
     *
     * @param int[] $parentIDs The IDs of the parent categories.
     * @param string $permission The name of the permission to check.
     * @param array $options
     * @return array Returns an array of child categories.
     */
    private function getChildrenByParents(
        array $parentIDs,
        $permission = "PermsDiscussionsView",
        array $options = []
    ): array {
        $needsDirtyJoin = $options[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;

        if ($needsDirtyJoin) {
            $sql = $this->makeGetChildQuery($parentIDs);
            $this->joinDirtyRecordTable($sql, "CategoryID", "category");
            $children = $sql->get()->resultArray();
            array_walk($children, [$this, "calculateStatic"]);
            array_walk($children, [$this, "calculateDynamic"]);
        } else {
            $childIDs = $this->getChildIDs($parentIDs);
            $children = $this->getMulti($childIDs);
        }

        // Remove categories without permission.
        if ($permission) {
            $children = array_filter($children, function ($category) use ($permission) {
                return (bool) val($permission, $category);
            });
        }

        return $children;
    }

    /**
     * Flatten a tree that was returned from {@link getTree}.
     *
     * @param array $categories The array of root categories.
     * @return array Returns an array of categories.
     */
    public function flattenTree(array $categories)
    {
        $result = self::treeBuilder()->flattenTree($categories);
        return $result;
    }

    /**
     * Get a tree builder instance for categories.
     *
     * @return TreeBuilder
     */
    public static function treeBuilder(): TreeBuilder
    {
        $builder = TreeBuilder::create("CategoryID", "ParentCategoryID")
            ->setAllowUnreachableNodes(true)
            ->setRootID(-1)
            ->setChildrenFieldName("Children")
            ->setSorter(function (array $catA, array $catB) {
                return ($catA["Sort"] ?? 0) <=> ($catB["Sort"] ?? 0);
            });
        return $builder;
    }

    /**
     * Update an existing category, handling its tree properties and caching.
     *
     * @param array $category The category to update.
     * @return bool Returns **true** if the category updated or **false** otherwise.
     */
    public function update(array $category)
    {
        if (empty($category["CategoryID"])) {
            throw new Gdn_UserException("Category ID is required.");
        }

        $category += [
            "DateUpdated" => Gdn_Format::toDateTime(),
            "UpdateUserID" => 1,
        ];

        // Get the current category.
        $oldCategory = $this->get($category["CategoryID"]);

        if (!$oldCategory) {
            $inserted = $this->insert($category);
            if ($inserted) {
                return $category["CategoryID"];
            } else {
                return false;
            }
        } else {
            $this->sql->put("Category", $category, ["CategoryID" => $category["CategoryID"]]);
            $this->refreshCache($category["CategoryID"]);

            // Did my parent change?
            if ((int) $oldCategory["ParentCategoryID"] !== (int) $category["ParentCategoryID"]) {
                // Increment the new parent and decrement the old parent.
                $this->sql->put(
                    "Category",
                    ["CountCategories-" => 1],
                    ["CategoryID" => $oldCategory["ParentCategoryID"]]
                );
                $this->sql->put("Category", ["CountCategories+" => 1], ["CategoryID" => $category["ParentCategoryID"]]);
                $this->refreshCache($oldCategory["ParentCategoryID"]);
                $this->refreshCache($category["ParentCategoryID"]);
            }
            return true;
        }
    }

    /**
     * Insert a new category, handling its tree properties and caching.
     *
     * This method is currently only to to be used in a support role.
     *
     * @param array $category The new category.
     */
    public function insert(array $category)
    {
        $category += [
            "DateInserted" => Gdn_Format::toDateTime(),
            "InsertUserID" => 1,
            "ParentCategoryID" => -1,
        ];

        // Filter out fields that aren't in the table.
        $category = array_intersect_key($category, $this->getSchema()->fields());
        $categoryID = $this->sql->insert("Category", $category);

        if ($categoryID) {
            // Update my parent's count.
            $this->sql->put("Category", ["CountCategories+" => 1], ["CategoryID" => $category["ParentCategoryID"]]);

            $this->refreshCache($category["CategoryID"]);
            $this->refreshCache($category["ParentCategoryID"]);
        }

        return $categoryID;
    }

    /**
     * Get the schema.
     *
     * @return Gdn_Schema Returns the schema.
     */
    private function getSchema()
    {
        if ($this->schema === null) {
            $this->schema = new Gdn_Schema("Category", $this->sql->Database);
        }
        return $this->schema;
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
    public function refreshCache($categoryID)
    {
        $category = $this->sql->getWhere("Category", ["CategoryID" => $categoryID])->firstRow(DATASET_TYPE_ARRAY);
        if ($category) {
            $this->calculateStatic($category);
            $this->cacheStore($this->cacheKey(self::CACHE_CATEGORY, $category["CategoryID"]), $category);
            $this->cacheStore(
                $this->cacheKey(self::CACHE_CATEGORY_SLUG, $category["UrlCode"]),
                (int) $category["CategoryID"]
            );
            $this->calculateDynamic($category);
            $this->categories[(int) $category["CategoryID"]] = $category;
            $this->categorySlugs[strtolower($category["UrlCode"])] = (int) $category["CategoryID"];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the dynamic calculator.
     *
     * @return callable Returns the dynamicCalculator.
     */
    public function getUserCalculator()
    {
        return $this->userCalculator;
    }

    /**
     * Set the dynamic calculator.
     *
     * @param callable $userCalculator The new dynamic calculator.
     * @return CategoryCollection Returns `$this` for fluent calls.
     */
    public function setUserCalculator($userCalculator)
    {
        $this->userCalculator = $userCalculator;
        return $this;
    }

    /**
     * Reset the local cache.
     */
    public function reset(): void
    {
        $this->categorySlugs = [];
        $this->categories = [];
    }

    /**
     * Calculate dynamic data on a category.
     *
     * This method is passed as a callback by default in {@link setCalculator}, but may not show up as used.
     *
     * @param array &$category The category to calculate.
     */
    private function defaultCalculator(&$category)
    {
        //        $category['Url'] = self::categoryUrl($category, false, '/');
        $category["ChildIDs"] = [];
        //        if (val('Photo', $category)) {
        //            $category['PhotoUrl'] = Gdn_Upload::url($category['Photo']);
        //        } else {
        //            $category['PhotoUrl'] = '';
        //        }

        CategoryModel::calculateDisplayAs($category);

        if (!val("CssClass", $category)) {
            $category["CssClass"] = "Category-" . $category["UrlCode"];
        }

        if (isset($category["AllowedDiscussionTypes"]) && is_string($category["AllowedDiscussionTypes"])) {
            $category["AllowedDiscussionTypes"] = dbdecode($category["AllowedDiscussionTypes"]);
        }
    }

    /**
     * Get a value from the config.
     *
     * @param string $key The config key.
     * @param mixed $default The default to return if the config isn't found.
     * @return mixed Returns the config value or {@link $default} if it isn't found.
     */
    private function config($key, $default = null)
    {
        if ($this->config !== null) {
            return $this->config->get($key, $default);
        } else {
            return $default;
        }
    }

    /**
     * Store an item in the cache, accounting for the read-only flag.
     *
     * @param string $key
     * @param mixed $value
     * @param array $options
     * @return void
     */
    private function cacheStore($key, $value, $options = []): void
    {
        if ($this->cacheReadOnly) {
            return;
        }

        $this->cache->store($key, $value, $options);
    }

    /**
     * Is this collection avoiding write operations to the cache?
     *
     * @return boolean
     */
    public function isCacheReadOnly(): bool
    {
        return $this->cacheReadOnly;
    }

    /**
     * Should this collection avoid writing to the cache?
     *
     * @param boolean $cacheReadOnly
     * @return void
     */
    public function setCacheReadOnly(bool $cacheReadOnly): void
    {
        $this->cacheReadOnly = $cacheReadOnly;
    }
}
