<?php
/**
 * Category model
 *
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0
 */

use Garden\Container\ContainerException;
use Garden\EventManager;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Community\Events\SubscriptionChangeEvent;
use Vanilla\Community\Schemas\CategoryFragmentSchema;
use Vanilla\Contracts\LocaleInterface;
use Vanilla\Dashboard\Models\AggregateCountableInterface;
use Vanilla\Dashboard\Models\PermissionJunctionModelInterface;
use Vanilla\Database\SetLiterals\RawExpression;
use Vanilla\Events\LegacyDirtyRecordTrait;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Models\PostTypeModel;
use Vanilla\Forum\Digest\DigestModel;
use Vanilla\ImageSrcSet\ImageSrcSet;
use Vanilla\ImageSrcSet\ImageSrcSetService;
use Vanilla\Layout\LayoutViewModel;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Models\ModelCache;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Permissions;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\CallbackJob;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Schema\RangeExpression;
use Vanilla\SchemaFactory;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\DebugUtils;
use Vanilla\Utility\Deprecation;
use Vanilla\Utility\InstanceValidatorSchema;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\SystemCallableInterface;
use Webmozart\Assert\Assert;
use Garden\Events\EventFromRowInterface;
use Vanilla\Contracts\Models\CrawlableInterface;
use Garden\Events\ResourceEvent;
use Vanilla\Community\Events\CategoryEvent;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\ApiUtils;
use Vanilla\Dashboard\Models\BannerImageModel;
use Vanilla\Formatting\DateTimeFormatter;
use Vanilla\Formatting\Formats\TextFormat;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Manages discussion categories' data.
 */
class CategoryModel extends Gdn_Model implements
    LoggerAwareInterface,
    EventFromRowInterface,
    CrawlableInterface,
    PermissionJunctionModelInterface,
    SystemCallableInterface,
    AggregateCountableInterface
{
    use LegacyDirtyRecordTrait;
    use LoggerAwareTrait;

    public const CONF_CATEGORY_FOLLOWING = "Vanilla.EnableCategoryFollowing";
    public const PERM_DISCUSSION_VIEW = "Vanilla.Discussions.View";
    public const PERM_JUNCTION_TABLE = "Category";
    public const RECORD_TYPE = "category";
    private const ADJUST_COUNT_DECREMENT = "decrement";
    private const ADJUST_COUNT_INCREMENT = "increment";

    /** @var string UserMeta key for determining whether a user is following a category */
    public const PREFERENCE_FOLLOW = "Preferences.Follow.%d";

    public const PREFERENCE_DIGEST_EMAIL = "Preferences.Email.Digest.%d";

    /** UserMeta key for determining whether a user should receive in-app discussion notifications for a category. */
    public const PREFERENCE_DISCUSSION_APP = "Preferences.Popup.NewDiscussion.%d";

    /** UserMeta key for determining whether a user should receive email discussion notifications for a category. */
    public const PREFERENCE_DISCUSSION_EMAIL = "Preferences.Email.NewDiscussion.%d";

    /** UserMeta key for determining whether a user should receive in-app comment notifications for a category. */
    public const PREFERENCE_COMMENT_APP = "Preferences.Popup.NewComment.%d";

    /** UserMeta key for determining whether a user should receive in-app comment notifications for a category. */
    public const PREFERENCE_COMMENT_EMAIL = "Preferences.Email.NewComment.%d";

    public const CATEGORY_PREFERENCES = [
        self::PREFERENCE_DIGEST_EMAIL => [
            "eventTrue" => "digest_subscribe",
            "eventFalse" => "digest_unsubscribe",
            "subscription" => "Email Digest",
        ],
        self::PREFERENCE_FOLLOW => [
            "eventTrue" => "category_follow",
            "eventFalse" => "category_unfollow",
            "subscription" => "Follow",
        ],
        self::PREFERENCE_DISCUSSION_APP => [
            "eventTrue" => "inApp_newDiscussions_subscribe",
            "eventFalse" => "inApp_newDiscussions_unsubscribe",
            "subscription" => "Popup Discussions",
        ],
        self::PREFERENCE_DISCUSSION_EMAIL => [
            "eventTrue" => "email_newDiscussions_subscribe",
            "eventFalse" => "email_newDiscussions_unsubscribe",
            "subscription" => "Email Discussions",
        ],
        self::PREFERENCE_COMMENT_APP => [
            "eventTrue" => "inApp_newComments_subscribe",
            "eventFalse" => "inApp_newComments_unsubscribe",
            "subscription" => "Popup Comments",
        ],
        self::PREFERENCE_COMMENT_EMAIL => [
            "eventTrue" => "email_newComments_subscribe",
            "eventFalse" => "email_newComments_unsubscribe",
            "subscription" => "Email Comments",
        ],
    ];

    public const DEFAULT_PREFERENCE_DATE_FOLLOWED = "2023-04-18 23:59:59";

    /** Cache key. */
    const CACHE_KEY = "Categories";

    /** Cache time to live. */
    const CACHE_TTL = 600;

    /** Cache grace. */
    const CACHE_GRACE = 60;

    /** Cache key. */
    const MASTER_VOTE_KEY = "Categories.Rebuild.Vote";

    const DEFAULT_FOLLOWED_CATEGORIES_KEY = "Preferences.CategoryFollowed.Defaults";

    /** The default maximum number of categories a user can follow. */
    const MAX_FOLLOWED_CATEGORIES_DEFAULT = 500;

    /** Flag for aggregating comment counts. */
    const AGGREGATE_COMMENT = "comment";

    /** Flag for aggregating discussion counts. */
    const AGGREGATE_DISCUSSION = "discussion";

    /** Default execution timeout for iterative category content deletes. */
    private const DELETE_TIMEOUT_DEFAULT = 10;

    /* Constants for category display options. */
    const DISPLAY_FLAT = "Flat";
    const DISPLAY_HEADING = "Heading";
    const DISPLAY_DISCUSSIONS = "Discussions";

    const DISPLAY_NESTED = "Categories";
    const LAYOUT_CATEGORY_LIST = "categoryList";
    const LAYOUT_NESTED_CATEGORY_LIST = "nestedCategoryList";
    const LAYOUT_DISCUSSION_CATEGORY_PAGE = "discussionCategoryPage";

    public const OUTPUT_PREFERENCE_FOLLOW = "preferences.followed";

    public const OUTPUT_PREFERENCE_DISCUSSION_APP = "preferences.popup.posts";

    public const OUTPUT_PREFERENCE_DISCUSSION_EMAIL = "preferences.email.posts";

    public const OUTPUT_PREFERENCE_COMMENT_APP = "preferences.popup.comments";

    public const OUTPUT_PREFERENCE_COMMENT_EMAIL = "preferences.email.comments";

    public const OUTPUT_PREFERENCE_DIGEST = "preferences.email.digest";

    /** @var int The tippy-top of the category tree. */
    public const ROOT_ID = -1;

    /** @var bool Was a cache-clearing job scheduled? */
    private static $isClearScheduled = false;

    /** @var bool Whether to allow the calculation of Headings in the `calculateDisplayAs` method */
    private static $stopHeadingsCalculation = false;

    /** @var array An array of fields to set locally after a category is fetched. */
    private static $toLazySet = [];

    /**
     * @var CategoryCollection $collection;
     */
    private $collection;

    /** @var LocaleInterface */
    private $locale;

    /** @var EventManager */
    private $eventManager;

    /** @var array[] */
    private static $deferredCache = [];
    /** @var boolean */
    private static $deferredCacheScheduled = false;

    /**
     * @deprecated 2.6
     * @var bool
     */
    public $Watching = false;

    /** @var array Merged Category data, including Pure + UserCategory. */
    public static $Categories = null;

    /** @var array Valid values => labels for DisplayAs column. */
    private static $displayAsOptions = [
        self::DISPLAY_DISCUSSIONS => "Discussions",
        self::DISPLAY_NESTED => "Nested",
        self::DISPLAY_FLAT => "Flat",
        self::DISPLAY_HEADING => "Heading",
    ];

    /**
     * @var bool Whether or not to join users to recent posts.
     * Forums with a lot of categories may need to optimize using this setting and simpler views.
     */
    public $JoinRecentUsers = true;

    /**
     * @var bool Whether or not to join GDN_UserCategoryInformation in {@link CategoryModel::calculateUser()}.
     */
    private $joinUserCategory = false;

    /** @var Permissions */
    private $guestPermissions;

    /** @var Schema */
    private $schemaInstance;

    /** @var ModelCache */
    private $modelCache;

    /** @var ImageSrcSetService */
    private $imageSrcSetService;

    private bool $unfilteredSearchCategories = false;

    private ?array $preferenceMap = null;

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct()
    {
        parent::__construct("Category");
        $this->locale = Gdn::getContainer()->get(\Vanilla\Contracts\LocaleInterface::class);
        $this->imageSrcSetService = Gdn::getContainer()->get(ImageSrcSetService::class);
        $this->collection = $this->createCollection();
        $this->eventManager = Gdn::getContainer()->get(EventManager::class);
        $this->modelCache = new ModelCache("CategoryModel", Gdn::cache());
        $this->logger = Gdn::getContainer()->get(\Psr\Log\LoggerInterface::class);
    }

    /**
     * Clear the cache on update.
     */
    public function onUpdate()
    {
        parent::onUpdate();
        $this->modelCache->invalidateAll();
    }

    /**
     * @inheritdoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["deleteIDIterable", "recalculateAllCountAlls"];
    }

    /**
     * @inheritdoc
     */
    public function onPermissionChange(): void
    {
        // This model doesn't currently keep any cache of permissions.
    }

    /**
     * @inheritdoc
     */
    public function getJunctions(): ?array
    {
        try {
            $this->defineSchema();
        } catch (Throwable $e) {
            // It's possible we may be starting a session to try and structure the category.
            // If that's the case we can't let this fail.
            // In any case without a structured category table we are in no position to start enforcing permissions from them.
            return null;
        }
        $ids = $this->modelCache->getCachedOrHydrate(["junctionExclusions" => true], function () {
            $rows = $this->createSql()
                ->select("c.CategoryID")
                ->from("Category c")
                ->where("c.PermissionCategoryID", "c.CategoryID", true, false)
                ->where("c.CategoryID >", 0)
                ->get()
                ->resultArray();

            return array_column($rows, "CategoryID");
        });

        return [
            "Category" => $ids,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getJunctionAliases(): ?array
    {
        try {
            $this->defineSchema();
        } catch (Throwable $e) {
            // It's possible we may be starting a session to try and structure the category.
            // If that's the case we can't let this fail.
            // In any case without a structured category table we are in no position to start enforcing permissions from them.
            return null;
        }

        $aliases = $this->modelCache->getCachedOrHydrate(["junctionAliases"], function () {
            $rows = $this->createSql()
                ->select(["c.CategoryID", "c.PermissionCategoryID"])
                ->where("c.CategoryID <>", "c.PermissionCategoryID", false, false)
                ->where("c.PermissionCategoryID <>", Permissions::GLOBAL_JUNCTION_ID) // Exclude ones pointing to the root.
                ->get("Category c")
                ->resultArray();

            $aliases = array_column($rows, "PermissionCategoryID", "CategoryID");

            return [
                "Category" => $aliases,
            ];
        });

        return $aliases;
    }

    /**
     * Get the scope for a knowledge base.
     *
     * @param int $categoryID
     *
     * @return string
     */
    public function getRecordScope(int $categoryID): string
    {
        if (!$this->guestPermissions) {
            if (!Gdn::config("Garden.Installed")) {
                // Everything is "public" until the site is actually setup.
                // This ensures initial site records are created properly.
                return CrawlableRecordSchema::SCOPE_PUBLIC;
            }

            $this->guestPermissions = Gdn::userModel()->getGuestPermissions();
        }

        $permissionCategoryID = self::permissionCategory($categoryID)["CategoryID"];
        $guestCanView = $this->guestPermissions->has("Vanilla.Discussions.View", $permissionCategoryID);
        return $guestCanView ? CrawlableRecordSchema::SCOPE_PUBLIC : CrawlableRecordSchema::SCOPE_RESTRICTED;
    }

    /**
     * The shared instance of this object.
     *
     * @return CategoryModel Returns the instance.
     */
    public static function instance()
    {
        return Gdn::getContainer()->get(CategoryModel::class);
    }

    /**
     * Checks the allowed discussion types on a category.
     *
     * @param array $permissionCategory The permission category of the category.
     * @param array $category The category we're checking the permission on.
     * @param null $sender
     * @param bool $checkCategory
     * @return array The allowed discussion types on the category.
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public static function getAllowedDiscussionData($permissionCategory, $category = [], $sender = null): array
    {
        $permissionCategory = self::permissionCategory($permissionCategory);
        $allowed = val("AllowedDiscussionTypes", $permissionCategory);

        $allTypes = DiscussionModel::discussionTypes();
        if (empty($allowed) || !is_array($allowed)) {
            $allowedTypes = $allTypes;
        } else {
            $allowedTypes = array_intersect_key($allTypes, array_flip($allowed));
        }

        Gdn::pluginManager()->EventArguments["AllowedDiscussionTypes"] = &$allowedTypes;
        Gdn::pluginManager()->EventArguments["Category"] = $category;
        Gdn::pluginManager()->EventArguments["PermissionCategory"] = $permissionCategory;
        Gdn::pluginManager()->EventArguments["sender"] = $sender;
        Gdn::pluginManager()
            ->fireAs("CategoryModel")
            ->fireEvent("AllowedDiscussionTypes");

        return $allowedTypes;
    }

    /**
     * Return post type data array formatted for rendering the new post widget.
     *
     * @param mixed $category
     * @return array
     * @throws Exception
     */
    public static function getAllowedPostTypeData(mixed $category = null, ?int $groupID = null): array
    {
        $postTypeModel = self::getPostTypeModel();
        $postTypes = empty($category)
            ? $postTypeModel->getAvailablePostTypes()
            : $postTypeModel->getAllowedPostTypesByCategory((array) $category);

        // Convert post types to expected format for new post widget.
        $result = [];
        foreach ($postTypes as $postType) {
            $result[$postType["postTypeID"]] = $postType + [
                "apiType" => $postType["postTypeID"],
                "Singular" => $postType["name"],
                "Plural" => $postType["name"],
                "AddUrl" => "/post/{$postType["postTypeID"]}",
                "AddText" => $postType["postButtonLabel"],
                "AddIcon" => $postType["postButtonIcon"] ?? ($postType["parentPostType"]["postButtonIcon"] ?? null),
            ];
        }

        $result = Gdn::eventManager()->fireFilter("getAllowedPostTypeData", $result, $category, $groupID);
        return $result;
    }

    /**
     * @return PostTypeModel
     */
    private static function getPostTypeModel(): PostTypeModel
    {
        return Gdn::getContainer()->get(PostTypeModel::class);
    }

    /**
     * Get the names of the allowed discussion types for a category. This is really just a convenience method
     * that returns the 'apiType' field from getAllowedDiscussionData().
     *
     * @param mixed $category
     * @return array
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public static function getAllowedDiscussionTypes($category): array
    {
        if ($category instanceof stdClass) {
            $category = (array) $category;
        }
        $category = ArrayUtils::pascalCase($category);
        $permissionCategory = self::permissionCategory($category["CategoryID"]);
        $allowedTypesData = self::getAllowedDiscussionData($permissionCategory, $category);

        $allowedTypes = array_column($allowedTypesData, "apiType");
        return $allowedTypes;
    }

    /**
     * Get a categories allowed discussion types.
     *
     * This respects enabled types and the category record.
     *
     * @param array $row
     *
     * @return array
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function getCategoryAllowedDiscussionTypes(array &$row): array
    {
        if (empty($row["AllowedDiscussionTypes"])) {
            $row["AllowedDiscussionTypes"] = [];
        }
        $categoryAllowedDiscussionTypes = $row["AllowedDiscussionTypes"];
        $allowedDiscussionTypes = self::getAllowedDiscussionData($row);
        $allowedDiscussionTypes = array_keys($allowedDiscussionTypes);

        return array_intersect($categoryAllowedDiscussionTypes, $allowedDiscussionTypes);
    }

    /**
     * Load every category from the cache or the database.
     */
    private static function loadAllCategories()
    {
        // Try and get the categories from the cache.
        $categoriesCache = Gdn::cache()->get(self::CACHE_KEY);
        $rebuild = true;

        // If we received a valid data structure, extract the embedded expiry
        // and re-store the real categories on our static property.
        if (is_array($categoriesCache)) {
            // Test if it's time to rebuild
            $rebuildAfter = val("expiry", $categoriesCache, null);
            if (!is_null($rebuildAfter) && time() < $rebuildAfter) {
                $rebuild = false;
            }
            self::$Categories = val("categories", $categoriesCache, null);
        }
        unset($categoriesCache);

        if ($rebuild) {
            // Try to get a rebuild lock
            $haveRebuildLock = self::rebuildLock();
            if ($haveRebuildLock || !self::$Categories) {
                self::$Categories = static::instance()->loadAllCategoriesDb();
                self::$deferredCache = [];
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
     * Get all categories visible to a user.
     *
     * @param int $userID The userID to lookup by.
     * @param string $permission The permission to check for.
     *
     * @return int[] An array of categoryIDs.
     */
    public function getCategoryIDsWithPermissionForUser(int $userID, string $permission): array
    {
        $userModel = \Gdn::userModel();
        $roleModel = \Gdn::getContainer()->get(RoleModel::class);
        $userRoleIDs =
            $userID === UserModel::GUEST_USER_ID
                ? $roleModel->getDefaultRoles(RoleModel::TYPE_GUEST)
                : $userModel->getRoleIDs($userID);
        // Use this in our cache key so that we invalidate this cache when permissions are changed.
        $permissionsIncrement = $userModel->getPermissionsIncrement();

        return $this->modelCache->getCachedOrHydrate(
            ["userCategoryIDs", "roleIDs" => $userRoleIDs, "permInc" => $permissionsIncrement, $permission],
            function () use ($userRoleIDs, $permission) {
                return $this->createSql()
                    ->select("C.CategoryID")
                    ->distinct()
                    ->from("Category C")
                    ->join("Permission P", "C.PermissionCategoryID = P.JunctionID AND P.JunctionTable = 'Category'")
                    ->join("Role R", "R.RoleID = P.RoleID ")
                    ->where([
                        "P.JunctionTable" => "Category",
                        "R.RoleID" => $userRoleIDs,
                        "P.{$permission}" => 1,
                    ])
                    ->get()
                    ->column("CategoryID");
            },
            [\Gdn_Cache::FEATURE_EXPIRY => 60 * 60]
        );
    }

    /**
     * Calculate the user-specific information on a category.
     *
     * @param array &$category The category to calculate.
     * @param bool|null $addUserCategory
     */
    private function calculateUser(array &$category, $addUserCategory = null)
    {
        if ($category["UserCalculated"] ?? false) {
            // Don't recalculate categories that have already been calculated.
            return;
        }
        $category["UserCalculated"] = true;
        // Kludge to make sure that the url is absolute when reaching the user's screen (or API).
        $category["Url"] = self::categoryUrl($category, "", true);

        if (!isset($category["PhotoUrl"])) {
            if ($photo = $category["Photo"] ?? false) {
                $category["PhotoUrl"] = Gdn_Upload::url($photo);
            }
        }

        if (!empty($category["LastUrl"])) {
            $category["LastUrl"] = url($category["LastUrl"], "//");
        }

        $category["PermsDiscussionsView"] = self::checkPermission($category, "Vanilla.Discussions.View");
        $category["PermsDiscussionsAdd"] = self::checkPermission($category, "Vanilla.Discussions.Add");
        $category["PermsDiscussionsEdit"] = self::checkPermission($category, "Vanilla.Discussions.Edit");
        $category["PermsCommentsAdd"] = self::checkPermission($category, "Vanilla.Comments.Add");

        $code = $category["UrlCode"];
        $category["Name"] = Gdn::translate("Categories." . $code . ".Name", [
            "default" => $category["Name"],
            "optional" => true,
        ]);
        $category["Description"] = Gdn::translate("Categories." . $code . ".Description", [
            "default" => $category["Description"],
            "optional" => true,
        ]);

        if ($addUserCategory || ($addUserCategory === null && $this->joinUserCategory())) {
            $userCategories = $this->getUserCategories();

            $dateMarkedRead = $category["DateMarkedRead"] ?? false;
            $userData = $userCategories[$category["CategoryID"]] ?? [];
            if (!empty($userData)) {
                $userDateMarkedRead = $userData["DateMarkedRead"];

                if (
                    !$dateMarkedRead ||
                    ($userDateMarkedRead &&
                        Gdn_Format::toTimestamp($userDateMarkedRead) > Gdn_Format::toTimestamp($dateMarkedRead))
                ) {
                    $category["DateMarkedRead"] = $userDateMarkedRead;
                    $dateMarkedRead = $userDateMarkedRead;
                }

                $category["Unfollow"] = $userData["Unfollow"];
            } else {
                $category["Unfollow"] = false;
            }

            // Calculate the following field.
            $following = !((bool) ($category["Archived"] ?? false) || (bool) ($userData["Unfollow"] ?? false));
            $category["Following"] = $following;

            $category["Followed"] = boolval($userData["Followed"] ?? false);

            // Calculate the read field.
            if (strcasecmp($category["DisplayAs"], "heading") === 0) {
                $category["Read"] = false;
            } elseif ($dateMarkedRead) {
                if ($lastDateInserted = $category["LastDateInserted"] ?? false) {
                    $category["Read"] =
                        Gdn_Format::toTimestamp($dateMarkedRead) >= Gdn_Format::toTimestamp($lastDateInserted);
                } else {
                    $category["Read"] = true;
                }
            } else {
                $category["Read"] = false;
            }
        }
    }

    /**
     * Get searchable category IDs.
     *
     * @param int|null $categoryID The root category ID.
     * @param bool|null $followedCategories If set, include or exclude followed categories.
     * @param bool|null $includeChildCategories Get child category IDs as well.
     * @param bool|null $includeArchivedCategories If set include archived categories.
     * @param array|null $categoryIDs
     * @param string|null $categorySearch
     * @return int[] CategoryIDs.
     */
    public function getSearchCategoryIDs(
        ?int $categoryID = null,
        ?bool $followedCategories = null,
        ?bool $includeChildCategories = null,
        ?bool $includeArchivedCategories = null,
        ?array $categoryIDs = null,
        ?string $categorySearch = null,
        bool $filterDiscussionsAdd = false
    ): array {
        $categoryFilter = [
            "forceArrayReturn" => true,
            "filterDiscussionsAdd" => $filterDiscussionsAdd,
        ];
        if (!$includeArchivedCategories) {
            $categoryFilter["filterArchivedCategories"] = true;
        }

        if ($this->unfilteredSearchCategories) {
            $resultIDs = array_column(self::categories(), "CategoryID");
        } else {
            $resultIDs = $this->getVisibleCategoryIDs($categoryFilter);
        }

        if ($followedCategories) {
            $followedCategories = $this->getFollowed(Gdn::session()->UserID);
            $followCategoryIDs = array_column($followedCategories, "CategoryID");
            $resultIDs = array_intersect($resultIDs, $followCategoryIDs);
        }

        if ($categoryID !== null) {
            if ($includeChildCategories) {
                $selectedCategoryIDs = array_merge($this->getCategoryDescendantIDs($categoryID), [$categoryID]);
            } else {
                $selectedCategoryIDs = [$categoryID];
            }
            $resultIDs = array_intersect($selectedCategoryIDs, $resultIDs);
        } elseif (!empty($categoryIDs)) {
            if ($includeChildCategories) {
                $categoryIDs = array_unique(array_merge($this->getCategoriesDescendantIDs($categoryIDs), $categoryIDs));
            }
            $resultIDs = array_intersect($categoryIDs, $resultIDs);
        }

        if ($categorySearch !== "Discussion" || $categoryID === null || empty($resultIDs)) {
            // Make sure 0 (allowing other record types) makes it in.
            $resultIDs[] = 0;
        }

        return $resultIDs;
    }

    /**
     * Toggle the flag which determines if categories for search should be filtered based on permissions.
     *
     * @param bool $unfiltered
     * @return self
     */
    public function setUnfilteredSearchCategories(bool $unfiltered): self
    {
        $this->unfilteredSearchCategories = $unfiltered;
        return $this;
    }

    /**
     * Get descendant categories.
     *
     * @param int[] $categoryIDs
     * @return int[] CategoryIDs.
     */
    public function getCategoriesDescendantIDs(array $categoryIDs): array
    {
        $mergedCategories = [];
        foreach ($categoryIDs as $categoryID) {
            $selectedCategoryIDs = $this->getCategoryDescendantIDs($categoryID);
            if (!empty($selectedCategoryIDs)) {
                $mergedCategories += array_merge($selectedCategoryIDs, [$categoryID]);
            }
        }
        return !empty($mergedCategories) ? $mergedCategories : $categoryIDs;
    }

    /**
     * Get the per-category information for a user.
     *
     * @param int $userID
     * @return array|mixed
     */
    private function getUserCategories($userID = null)
    {
        if ($userID === null) {
            $userID = Gdn::session()->UserID;
        }

        if ($userID) {
            $key = "UserCategory_" . $userID;
            $userData = Gdn::cache()->get($key);
            if ($userData === Gdn_Cache::CACHEOP_FAILURE) {
                $sql = clone $this->SQL;
                $sql->reset();

                $userData = $sql->getWhere("UserCategory", ["UserID" => $userID])->resultArray();
                $userData = array_column($userData, null, "CategoryID");
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
     * Get all the categories from the DB.
     *
     * @return array
     */
    protected function loadAllCategoriesDb(): array
    {
        $sql = clone $this->SQL;
        $sql->reset();

        $sql->select("c.*")
            ->from("Category c")
            //->select('lc.DateInserted', '', 'DateLastComment')
            //->join('Comment lc', 'c.LastCommentID = lc.CommentID', 'left')
            ->orderBy("c.TreeLeft");

        $categories = array_merge([], $sql->get()->resultArray());
        $categories = Gdn_DataSet::index($categories, "CategoryID");

        $this::sortFlatCategories($categories);

        return $categories;
    }

    /**
     * Get the maximum number of available pages when viewing a list of categories.
     *
     * @return int
     */
    public function getMaxPages()
    {
        $maxPages = (int) c("Vanilla.Categories.MaxPages") ?: 100;
        return $maxPages;
    }

    /**
     * Check if digest is enabled for the site.
     *
     * @return bool
     */
    public static function isDigestEnabled(): bool
    {
        return Gdn::config("Garden.Digest.Enabled");
    }

    /**
     * Get the display type for the root category.
     *
     * @return string
     */
    public static function getRootDisplayAs()
    {
        return c("Vanilla.RootCategory.DisplayAs", "Categories");
    }

    /**
     * Get a list of a user's followed categories with email digest enabled.
     *
     * @param int $userID The target user's ID.
     * @return array
     */
    public function getDigestEnabledCategories(int $userID): array
    {
        $userData = $this->createSql()
            ->select("CategoryID")
            ->getWhere("UserCategory", [
                "UserID" => $userID,
                "Followed" => 1,
                "DigestEnabled" => 1,
            ])
            ->resultArray();
        return array_column($userData, "CategoryID");
    }

    /**
     * Get a count of users having digest enabled for the particular category
     *
     * @param int $categoryID
     * @return int
     */
    public function getDigestEnabledUserCountForCategory(int $categoryID): int
    {
        $userOnClause =
            Gdn::config()->get(DigestModel::AUTOSUBSCRIBE_DEFAULT_PREFERENCE) == 1
                ? 'u.UserID = um.UserID and um.QueryValue in ("Preferences.Email.DigestEnabled.1","Preferences.Email.DigestEnabled.3") AND u.Deleted = 0'
                : 'u.UserID = um.UserID and um.QueryValue = "Preferences.Email.DigestEnabled.1" AND u.Deleted = 0';

        if (!self::categories($categoryID)) {
            return 0;
        }
        return $this->createSql()
            ->select("*", "count", "total")
            ->from("UserMeta um")
            ->join("User u", $userOnClause)
            ->join("UserCategory uc", "um.UserID = uc.UserID ")
            ->where(["uc.CategoryID" => $categoryID, "uc.DigestEnabled" => 1])
            ->get()
            ->column("total")[0];
    }

    /**
     * Get a list of a user's followed categories.
     *
     * @param int $userID The target user's ID.
     * @return int[]
     * @throws Exception
     */
    public function getFollowed($userID): array
    {
        $key = "Follow_{$userID}";
        $result = Gdn::cache()->get($key);
        if ($result === Gdn_Cache::CACHEOP_FAILURE) {
            $sql = clone $this->SQL;
            $sql->reset();

            $userCategoryData = $sql
                ->select("uc.*")
                ->from("UserCategory uc")
                ->join("Category c", "uc.CategoryID = c.CategoryID")
                ->where([
                    "UserID" => $userID,
                    "Followed" => 1,
                ])
                ->get()
                ->resultArray();

            $result = array_column($userCategoryData, null, "CategoryID");
            Gdn::cache()->store($key, $result);
        } elseif (count($result) > 0) {
            // Check that the followed categories exists
            $categoryIDs = array_keys($result);
            $categoriesCount = $this->createSql()->getCount("Category", ["CategoryID" => $categoryIDs]);

            // If the cached count differs from the db count, invalidate cache and recount.
            if ($categoriesCount != count($categoryIDs)) {
                Gdn::cache()->remove("Follow_{$userID}");
                return $this->getFollowed($userID);
            }
        }

        return $result;
    }

    /**
     * Get category preference keys without the placeholders for the categoryID.
     *
     * @return array
     */
    public function getGenericCategoryPreferenceKeys(): array
    {
        $preferences = [
            self::stripCategoryPreferenceKey(self::PREFERENCE_FOLLOW),
            self::stripCategoryPreferenceKey(self::PREFERENCE_DISCUSSION_APP),
            self::stripCategoryPreferenceKey(self::PREFERENCE_DISCUSSION_EMAIL),
            self::stripCategoryPreferenceKey(self::PREFERENCE_COMMENT_APP),
            self::stripCategoryPreferenceKey(self::PREFERENCE_COMMENT_EMAIL),
        ];

        if (self::isDigestEnabled()) {
            $preferences[] = self::stripCategoryPreferenceKey(self::PREFERENCE_DIGEST_EMAIL);
        }

        $modifiedPreferences = $this->getEventManager()->fire("categoryModel_getGenericPreferenceKeys", $preferences);

        if (is_array($modifiedPreferences) && !empty($modifiedPreferences)) {
            $preferences = $modifiedPreferences[0];
        }

        return $preferences;
    }

    /**
     * Convert existing default preference to the new format
     * This is a temporary function to help existing users migrate their current default category setting to the new format
     *
     * @param array $preferences
     * @return array
     * @deprecated
     * @todo : Remove this function and its references after 2023.014 release
     */
    public function convertOldPreferencesToNew(array $preferences): array
    {
        foreach ($preferences as $key => $preference) {
            //old format should have "postNotifications" as key, if the key doesn't exist then break as it's already in new format
            if (!array_key_exists("postNotifications", $preference)) {
                break;
            }
            $newPreference = [];
            //default preference will always have the followed flag enabled so set them directly
            $newPreference[CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW] = true;
            //Enable new preference for popup post if the old notification type have discussion or all
            if ($preference["postNotifications"] == "discussions" || $preference["postNotifications"] == "all") {
                $newPreference[CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP] = true;
            }
            //Enable comment popup if old notification preference is marked "all"
            if ($preference["postNotifications"] == "all") {
                $newPreference[CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_APP] = true;
            }
            //Remove old notification reference
            unset($preferences[$key]["postNotifications"], $preferences[$key]["name"]);
            //if email notification is enabled in old preference add them to the new structure
            if (array_key_exists("useEmailNotifications", $preference)) {
                $newPreference[CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL] =
                    $preference["useEmailNotifications"];
                $newPreference[CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_EMAIL] =
                    $preference["useEmailNotifications"];
                unset($preferences[$key]["useEmailNotifications"]);
            }
            $preferences[$key]["preferences"] = $newPreference;
        }

        return $preferences;
    }

    /**
     * Set whether a user is following a category.
     *
     * @param int $userID The target user's ID.
     * @param int $categoryID The target category's ID.
     * @param bool|null $followed True for following. False for not following. Null for toggle.
     * @param bool $digestEnabled True for enabling email digest, false otherwise
     * @return bool A boolean value representing the user's resulting "follow" status for the category.
     * @throws InvalidArgumentException
     * @throws ClientException
     */
    public function follow($userID, $categoryID, $followed = null, bool $digestEnabled = false)
    {
        $validationOptions = [
            "options" => [
                "min_range" => 1,
            ],
        ];
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
            throw new InvalidArgumentException("Category not found.");
        } elseif ($category["DisplayAs"] !== "Discussions" && !$isFollowed) {
            throw new InvalidArgumentException("Category not configured to display as discussions.");
        }

        if ($followed == 1) {
            $followedCategories = $this->getFollowed($userID);
            $maxFollowedCategories = $this->getMaxFollowedCategories();
            if (count($followedCategories) >= $maxFollowedCategories) {
                throw new ClientException(
                    sprintf(
                        t(
                            "You are already following the maximum of %d categories. Unfollow a category before following another."
                        ),
                        $maxFollowedCategories
                    ),
                    400,
                    [
                        "actionButton" => [
                            "label" => "Review your followed categories",
                            "url" => "/profile/followed-content",
                            "target" => "_blank",
                        ],
                    ]
                );
            }
        } else {
            //force '$digestEnabled' to be false if the user is not following a category
            $digestEnabled = false;
        }
        $fields = $this->getInsertUpdateCategoryPreferenceFields($followed, (int) $digestEnabled);

        $this->SQL->replace("UserCategory", $fields, ["UserID" => $userID, "CategoryID" => $categoryID]);
        static::clearUserCache();
        Gdn::cache()->remove("Follow_{$userID}");

        $result = $this->isFollowed($userID, $categoryID);
        return $result;
    }

    /**
     * Get insert update fields for category following
     *
     * @param int $followed
     * @param int $digestEnabled
     * @return array
     */
    private function getInsertUpdateCategoryPreferenceFields(int $followed, int $digestEnabled): array
    {
        $fields = ["Followed" => $followed, "Unfollow" => $followed ? 0 : 1, "DigestEnabled" => $digestEnabled];
        $fields[$followed ? "DateFollowed" : "DateUnfollowed"] = DateTimeFormatter::getCurrentDateTime();
        return $fields;
    }
    /**
     * Get the enabled status of category following, returned as a boolean value.
     *
     * @return bool
     */
    public function followingEnabled()
    {
        $result = boolval(c(\CategoryModel::CONF_CATEGORY_FOLLOWING));
        return $result;
    }

    /**
     * Get the maximum number of categories a user is allowed to follow.
     *
     * @return mixed
     */
    public function getMaxFollowedCategories()
    {
        $result = c("Vanilla.MaxFollowedCategories", self::MAX_FOLLOWED_CATEGORIES_DEFAULT);
        return $result;
    }
    /**
     * Is the specified user following the specified category?
     *
     * @param int $userID The target user's ID.
     * @param int $categoryID The target category's ID.
     * @return bool
     */
    public function isFollowed($userID, $categoryID)
    {
        $followed = $this->getFollowed($userID);
        $result = array_key_exists($categoryID, $followed);

        return $result;
    }

    /**
     * Check if the user has any followed categories
     *
     * @param int $userID
     * @return bool
     */
    public function hasFollowed(int $userID): bool
    {
        $followed = $this->getFollowed($userID);
        return (bool) count($followed);
    }

    /**
     * Check if the user has any un-followed categories
     *
     * @param int $userID
     * @return bool
     */
    public function hasUnfollowed(int $userID): bool
    {
        $where = [
            "UserID" => $userID,
            "Unfollow" => 1,
        ];
        return (bool) $this->SQL->getCount("UserCategory", $where);
    }

    /**
     * Get user's category IDs, taking into account permissions, muting and, optionally, the HideAllDiscussions field,
     *
     * @deprecated 2.6
     * @param bool $honorHideAllDiscussion Whether or not the HideAllDiscussions flag will be checked on categories.
     * @return array|bool Category IDs or true if all categories are watched.
     */
    public static function categoryWatch($honorHideAllDiscussion = true)
    {
        deprecated(__METHOD__, __CLASS__ . "::getVisibleCategoryIDs");
        $categories = self::categories();
        $allCount = count($categories);

        $watch = [];

        foreach ($categories as $categoryID => $category) {
            if ($honorHideAllDiscussion && val("HideAllDiscussions", $category)) {
                continue;
            }

            if ($category["PermsDiscussionsView"] && $category["Following"]) {
                $watch[] = $categoryID;
            }
        }

        Gdn::pluginManager()->EventArguments["CategoryIDs"] = &$watch;
        Gdn::pluginManager()
            ->fireAs("CategoryModel")
            ->fireEvent("CategoryWatch");

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
     *   - filterArchivedCategories (bool): Filter out categories that are archived.
     *   - forceArrayReturn (bool): Force an array return value.
     *   - filterNonDiscussionCategories (bool) : Filter out categories with no discussion in them
     *   - filterSiteSections (bool) : Filter out categories that are not in the current site section
     *   - filterDiscussionsAdd (bool): Filter out categories that don't have Discussions.Add or are invalid for posts.
     * @return array|bool An array of filtered categories or true if no categories were filtered.
     */
    public function getVisibleCategories(array $options = [])
    {
        $unfiltered = true;

        if ($options["forceArrayReturn"] ?? false) {
            // We want to get the categories back no matter what.
            $unfiltered = false;
        }

        $filterSiteSections = $options["filterSiteSections"] ?? true;

        if ($filterSiteSections && $this->eventManager->hasHandler("getAlternateVisibleCategories")) {
            $categories = $this->eventManager->fireFilter("getAlternateVisibleCategories", []);
            $unfiltered = false;
        } else {
            $categories = self::categories();
        }

        $result = [];

        // Options
        $filterHideDiscussions = $options["filterHideDiscussions"] ?? false;
        $filterArchivedCategories = $options["filterArchivedCategories"] ?? false;
        $filterNonPostableCategories = $options["filterNonPostableCategories"] ?? false;
        $filterNonDiscussionCategories = $options["filterNonDiscussionCategories"] ?? false;

        foreach ($categories as $categoryID => $category) {
            if (!($category["PermsDiscussionsView"] ?? false)) {
                $unfiltered = false;
                continue;
            }
            if ($filterHideDiscussions && ($category["HideAllDiscussions"] ?? false)) {
                $unfiltered = false;
                continue;
            }

            if ($filterArchivedCategories && ($category["Archived"] ?? false)) {
                $unfiltered = false;
                continue;
            }

            if ($filterNonDiscussionCategories && $category["CountDiscussions"] == 0) {
                $unfiltered = false;
                continue;
            }

            if ($filterNonPostableCategories) {
                if ($category["CategoryID"] <= 0 || !$category["PermsDiscussionsView"]) {
                    continue;
                }
                // Adjust the filtering even more by removing ones that would of appeared, but disabled
                if ($category["DisplayAs"] !== "Discussions" || !$category["AllowDiscussions"]) {
                    continue;
                }
                // IMPORTANT... overriding AllowedDiscussionTypes to show the actual data, would default to 0 if custom permissions weren't selected in the dashboard
                $permissionCategory = CategoryModel::permissionCategory($category);
                $allowedDiscussionTypes = CategoryModel::getAllowedDiscussionData($permissionCategory, $category);
                $category["AllowedDiscussionTypes"] = $allowedDiscussionTypes;
            }

            // Filter out categories that cannot be posted into.
            if (($options["filterDiscussionsAdd"] ?? false) && !$category["PermsDiscussionsAdd"]) {
                $unfiltered = false;
                continue;
            }

            $lazyPermSet = self::$toLazySet[$categoryID]["PermsDiscussionsView"] ?? false;
            if (!$category["PermsDiscussionsView"]) {
                if (!$lazyPermSet) {
                    $unfiltered = false;
                    continue;
                }
            }

            $result[] = $category;
        }

        if ($unfiltered) {
            $result = true;
        }

        // Allow addons to modify the visible categories.
        $result = $this->eventManager->fireFilter("categoryModel_visibleCategories", $result);

        if (is_array($result)) {
            // Sort the tree.
            $result = self::sortCategoriesAsTree($result);
        }

        return $result;
    }

    /**
     * Get a list of IDs of categories visible to the current user.
     *
     * @param array $options Options compatible with `CategoryModel::getVisibleCategories()`.
     * @return array|bool An array of filtered category IDs or true if no categories were filtered.
     * @see CategoryModel::categoryWatch
     */
    public function getVisibleCategoryIDs(array $options = [])
    {
        $categoryModel = self::instance();
        $result = $categoryModel->getVisibleCategories($options);
        if (is_array($result)) {
            $result = array_column($result, "CategoryID");
        }

        /** @var EventManager $eventManager */
        $eventManager = Gdn::getContainer()->get(EventManager::class);

        // Backwards-compatible CategoryModel::categoryWatch event.
        $eventManager->fireDeprecated("categoryModel_categoryWatch", $categoryModel, ["CategoryIDs" => &$result]);

        return $result;
    }

    /**
     *  * Get a list of IDs of discussion types based off visibleCategories that you can post within ie not archived or displayed as other than a discussion
     *
     * @return array An array of filtered discussionType IDs
     */
    public function getPostableDiscussionTypes()
    {
        $categoryModel = self::instance();
        $visibleCategories = $categoryModel->getVisibleCategories([
            "forceArrayReturn" => true,
            "filterArchivedCategories" => true,
            "filterNonPostableCategories" => true,
        ]);

        $result = [];
        foreach ($visibleCategories as $category) {
            if (!isset($category["AllowedDiscussionTypes"])) {
                continue;
            }
            foreach ($category["AllowedDiscussionTypes"] as $discussionType) {
                $result[] = strtolower($discussionType["apiType"] ?? $discussionType["Singular"]);
            }
        }

        $unique = array_unique($result);
        return array_values($unique);
    }

    /**
     * Gets either every category or a single category.
     *
     * @param int|string|bool $ID Either the category ID or the category url code.
     * If nothing is passed then all categories are returned.
     * @return array Returns either one or all categories.
     * @since 2.0.18
     */
    public static function categories($ID = false)
    {
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
                    if (strcasecmp($Category["UrlCode"], $Code) === 0) {
                        $ID = $Category["CategoryID"];
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
    protected static function rebuildLock($release = false)
    {
        static $isMaster = null;
        if ($release) {
            Gdn::cache()->remove(self::MASTER_VOTE_KEY);
            return;
        }
        if (is_null($isMaster)) {
            // Vote for master
            $instanceKey = getmypid();
            $masterKey = Gdn::cache()->add(self::MASTER_VOTE_KEY, $instanceKey, [
                Gdn_Cache::FEATURE_EXPIRY => self::CACHE_GRACE,
            ]);

            $isMaster = $instanceKey == $masterKey;
        }
        return (bool) $isMaster;
    }

    /**
     * Build and augment the category cache.
     *
     * @param int $categoryID The category to
     *
     */
    protected static function buildCache($categoryID = null)
    {
        self::calculateData(self::$Categories);
        self::joinRecentPosts(self::$Categories, $categoryID);

        $expiry = self::CACHE_TTL + self::CACHE_GRACE;
        Gdn::cache()->store(
            self::CACHE_KEY,
            [
                "expiry" => time() + $expiry,
                "categories" => self::$Categories,
            ],
            [
                Gdn_Cache::FEATURE_EXPIRY => $expiry,
            ]
        );
    }

    /**
     * Calculate the dynamic fields of a category.
     *
     * @param array &$category The category to calculate.
     */
    public static function calculate(array &$category)
    {
        $category["Url"] = self::categoryUrl($category, false, "/");

        if ($photo = $category["Photo"] ?? false) {
            $category["PhotoUrl"] = Gdn_Upload::url($photo);
        } else {
            $category["PhotoUrl"] = "";
        }

        self::calculateDisplayAs($category);

        if (!($category["CssClass"] ?? false)) {
            // Our validation rule is that the CssClass should be no longer than 50 chars, so if we're auto-generating one,
            // make sure we respect the rule.
            $category["CssClass"] = substr(
                "Category-" . $category["CategoryID"] . "-" . $category["UrlCode"] ?? "",
                0,
                50
            );
        }

        if (isset($category["AllowedDiscussionTypes"]) && is_string($category["AllowedDiscussionTypes"])) {
            $category["AllowedDiscussionTypes"] = dbdecode($category["AllowedDiscussionTypes"]);
        }

        $set = self::$toLazySet[$category["CategoryID"]] ?? null;
        if ($set !== null) {
            $category = array_replace($category, $set);
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
    public static function calculateDisplayAs(&$category)
    {
        if ($category["DisplayAs"] === "Default") {
            if ($category["Depth"] <= c("Vanilla.Categories.NavDepth", 0)) {
                $category["DisplayAs"] = "Categories";
            } elseif (
                $category["Depth"] == c("Vanilla.Categories.NavDepth", 0) + 1 &&
                c("Vanilla.Categories.DoHeadings") &&
                !self::$stopHeadingsCalculation
            ) {
                $category["DisplayAs"] = "Heading";
            } else {
                $category["DisplayAs"] = "Discussions";
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
    public function setStopHeadingsCalculation($stopHeadingCalculation)
    {
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
    public static function calculateData(&$data)
    {
        foreach ($data as &$category) {
            self::calculate($category);
        }
    }

    /**
     * Clear individual category and collection data caches.
     *
     * @param bool $schedule Should the action be deferred as a scheduled job?
     */
    public static function clearCache(bool $schedule = false)
    {
        $doClear = function () {
            self::$deferredCache = [];
            self::$deferredCacheScheduled = false;
            self::$Categories = null;
            self::$isClearScheduled = false;
            $instance = self::instance();
            $instance->modelCache->invalidateAll();
            Gdn::cache()->remove(self::CACHE_KEY);
            $instance->collection->flushCache();
        };

        if ($schedule) {
            if (self::$isClearScheduled !== true) {
                Gdn::getScheduler()->addJobDescriptor(
                    new NormalJobDescriptor(CallbackJob::class, ["callback" => $doClear])
                );
                self::$isClearScheduled = true;
            }
        } else {
            $doClear();
        }
    }

    /**
     * Clear the cached UserCategory data for a specific user.
     *
     * @param int|null $userID The user to clear. Use `null` for the current user.
     */
    public static function clearUserCache($userID = null)
    {
        if ($userID === null) {
            $userID = Gdn::session()->UserID;
        }

        $key = "UserCategory_" . $userID;
        Gdn::cache()->remove($key);

        // User category data may be cached here.
        self::$Categories = null;
        self::instance()->collection->flushLocalCache();
    }

    /**
     * @inheritdoc
     */
    public function calculateAggregates(string $aggregateName, int $from, int $to)
    {
        // A few of the counts here don't work very well with a range expression, so we will calculate out the IDs directly.
        $categoryIDs = $this->createSql()
            ->select("CategoryID")
            ->where(["CategoryID" => new RangeExpression(">=", $from, "<=", $to)])
            ->get("Category")
            ->column("CategoryID");
        $this->counts($aggregateName, [
            "CategoryID" => $categoryIDs,
        ]);
    }

    /**
     * Recalculate the counts for category data.
     *
     * @param string $column
     * @param  mixed $where
     * @return array
     */
    public function counts(string $column, $where = []): array
    {
        // hack to support dbamodel
        if (!is_array($where)) {
            $where = [];
        }
        $result = ["Complete" => true];
        switch ($column) {
            case "CountChildCategories":
                $this->recalculateTree($where);
                break;
            case "CountDiscussions":
                $this->Database->query(
                    DBAModel::getCountSQL("count", "Category", "Discussion", "", "", "", "", $where)
                );
                break;
            case "CountComments":
                $this->Database->query(
                    DBAModel::getCountSQL("sum", "Category", "Discussion", $column, "CountComments", "", "", $where)
                );
                break;
            case "CountAll":
            case "CountAllDiscussions":
            case "CountAllComments":
                self::recalculateAggregateCounts();
                break;
            case "LastPost":
                $categoryIDs = $this->createSql()
                    ->select("CategoryID")
                    ->from("Category")
                    ->where($where)
                    ->get()
                    ->column("CategoryID");

                foreach ($categoryIDs as $categoryID) {
                    $this->refreshAggregateRecentPost($categoryID, false);
                }
                break;
            case "CountFollowers":
                $subquery = $this->createSql()
                    ->select("*", "count")
                    ->from("UserCategory uc")
                    ->where("uc.CategoryID", "c.CategoryID", true, false)
                    ->where("uc.Followed", "1", true, false)
                    ->getSelect();
                $this->createSql()
                    ->update("Category c")
                    ->set("c.CountFollowers", "($subquery)", false, false)
                    ->where($where)
                    ->put();
                break;
        }
        self::clearCache();
        return $result;
    }

    /**
     *
     *
     * @return mixed
     * @deprecated
     */
    public static function defaultCategory()
    {
        deprecated("CategoryModel->defaultCategory", "");
        foreach (self::categories() as $category) {
            if ($category["CategoryID"] > 0) {
                return $category;
            }
        }
    }

    /**
     * Get a fragment of the root category for display.
     */
    public function getRootCategoryForDisplay()
    {
        $category = self::categories(-1);
        $name = Gdn::config("Garden.Title");
        $category["Name"] = !empty($name) ? $name : "Vanilla";
        $category["Url"] = Gdn::request()->getSimpleUrl("/categories");
        $category["UrlCode"] = "";
        $category["AllowedDiscussionTypes"] = [];
        return $category;
    }

    /**
     * Add multi-dimensional category data to an array.
     *
     * @param array $rows Results we need to associate category data with.
     * @param string $field
     */
    public function expandCategories(array &$rows, string $field = "Category")
    {
        if (count($rows) === 0) {
            // Nothing to do here.
            return;
        }

        reset($rows);
        $single = is_string(key($rows));

        $populate = function (array &$row, string $field) {
            $categoryID = $row["CategoryID"] ?? ($row["categoryID"] ?? ($row["ParentRecordID"] ?? false));

            if ($categoryID) {
                $category = self::categories($categoryID);
                if ($categoryID === -1) {
                    setValue($field, $row, $this->getRootCategoryForDisplay());
                } elseif ($category) {
                    $discussionTypes = is_array($category)
                        ? $this->getCategoryAllowedDiscussionTypes($category)
                        : ["Discussion"];
                    $discussionTypes = array_map("lcfirst", $discussionTypes);
                    $discussionTypes = array_values($discussionTypes);
                    $category["AllowedDiscussionTypes"] = $discussionTypes;
                    setValue($field, $row, $category);
                }
            }
        };

        // Inject those categories.
        if ($single) {
            $populate($rows, $field);
        } else {
            foreach ($rows as &$row) {
                $populate($row, $field);
            }
        }
    }

    /**
     * Whether a category allows posts. Returns true if the display type is discussions or it's the root category.
     *
     * @param int|array $categoryOrCategoryID
     * @return bool
     * @throws NotFoundException
     */
    public static function doesCategoryAllowPosts($categoryOrCategoryID): bool
    {
        $category = is_numeric($categoryOrCategoryID)
            ? self::categories($categoryOrCategoryID)
            : ArrayUtils::pascalCase($categoryOrCategoryID);
        if (!$category) {
            throw new \Garden\Web\Exception\NotFoundException("Category");
        }
        return strtolower($category["DisplayAs"]) === "discussions" || $category["CategoryID"] === -1;
    }

    /**
     * Checks if a category allows posts and throws an error if not.
     *
     * @param int|array $categoryOrCategoryID
     * @throws ForbiddenException Throws an exception if category does not allow posting.
     * @throws NotFoundException
     */
    public static function checkCategoryAllowsPosts($categoryOrCategoryID): void
    {
        $category = is_numeric($categoryOrCategoryID)
            ? self::categories($categoryOrCategoryID)
            : ArrayUtils::pascalCase($categoryOrCategoryID);
        $canPost = self::doesCategoryAllowPosts($category);
        if (!$canPost) {
            throw new ForbiddenException(
                sprintft(
                    "You are not allowed to post in categories with a display type of %s.",
                    t($category["DisplayAs"])
                )
            );
        }
    }

    /**
     * Remove categories that a user does not have permission to view.
     *
     * @param array $categoryIDs An array of categories to filter.
     * @return array Returns an array of category IDs that are okay to view.
     */
    public static function filterCategoryPermissions($categoryIDs)
    {
        $permissionCategories = static::getByPermission("Discussions.View");

        if ($permissionCategories === true) {
            return $categoryIDs;
        } else {
            $permissionCategoryIDs = array_keys($permissionCategories);
            // Reindex the result.  array_intersect leaves the original, potentially incomplete, numeric indexes.
            return array_values(array_intersect($categoryIDs, $permissionCategoryIDs));
        }
    }

    /**
     * Filter a set of categories to only ones the user can view.
     *
     * @param array $categories Full category records.
     * @param string $permission Permission to filter categories by.
     * @return array
     */
    public static function filterExistingCategoryPermissions(
        array $categories,
        $permission = "PermsDiscussionsView"
    ): array {
        $result = [];
        foreach ($categories as $category) {
            if ($category[$permission] ?? false) {
                $result[] = $category;
            }
        }
        return $result;
    }

    /**
     * Check a category's permission.
     *
     * @param int|array|object $category The category to check.
     * @param string|array $permission The permission(s) to check.
     * @param bool $fullMatch Whether or not the permission has to be a full match.
     * @return bool Returns **true** if the current user has the permission or **false** otherwise.
     */
    public static function checkPermission($category, $permission, $fullMatch = true)
    {
        if (is_array($category)) {
            $categoryID = $category["CategoryID"] ?? false;
        } elseif (is_object($category)) {
            $categoryID = $category->CategoryID ?? false;
        } else {
            $categoryID = $category;
        }

        return Gdn::session()->checkPermission(
            $permission,
            $fullMatch,
            "Category",
            $categoryID,
            Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION
        );
    }

    /**
     * Get the child categories of a category.
     *
     * @param int $categoryID The category to get the children of.
     */
    public static function getChildren($categoryID)
    {
        $categories = self::instance()->collection->getChildren($categoryID);
        return $categories;
    }

    /**
     * Cast a category ID or slug to be passed to the various {@link CategoryCollection} methods.
     *
     * @param int|string|null $category The category ID or slug.
     * @return int|string|null Returns the cast category ID.
     */
    private static function castID($category)
    {
        if (empty($category)) {
            return null;
        } elseif (is_numeric($category)) {
            return (int) $category;
        } else {
            return (string) $category;
        }
    }

    /**
     * Get a category tree based on, but not including a parent category.
     *
     * @param int|string $id The parent category ID or slug.
     * @param array $options See {@link CategoryCollection::getTree()}.
     * @return array Returns an array of categories with child categories in the **Children** key.
     */
    public function getChildTree($id, $options = [])
    {
        $category = $this->getOne($id);

        $options = array_change_key_case($options ?: []) + [
            "collapsecategories" => true,
        ];

        $tree = $this->collection->getTree((int) val("CategoryID", $category), $options);
        return $tree;
    }

    /**
     * Returns an icon name, given a display as value.
     *
     * @param string $displayAs The display as value.
     * @return string The corresponding icon name.
     */
    private static function displayAsIconName($displayAs)
    {
        switch (strtolower($displayAs)) {
            case "heading":
                return "heading";
            case "categories":
                return "nested";
            case "flat":
                return "flat";
            case "discussions":
            default:
                return "discussions";
        }
    }

    /**
     * Puts together a dropdown for a category's settings.
     *
     * @param object|array $category The category to get the settings dropdown for.
     * @return DropdownModule The dropdown module for the settings.
     */
    public static function getCategoryDropdown($category)
    {
        $triggerIcon = dashboardSymbol(self::displayAsIconName($category["DisplayAs"]));

        $cdd = new DropdownModule("", "", "dropdown-category-options", "dropdown-menu-right");
        $cdd->setTrigger($triggerIcon, "button", "btn", "caret-down", "", ["data-id" => val("CategoryID", $category)]);
        $cdd->setView("dropdown-twbs");
        $cdd->setForceDivider(true);

        $cdd->addGroup("", "edit")
            ->addLink(t("View"), $category["Url"], "edit.view")
            ->addLink(t("Edit"), "/vanilla/settings/editcategory?categoryid={$category["CategoryID"]}", "edit.edit")
            ->addGroup(t("Display as"), "displayas");

        foreach (CategoryModel::getDisplayAsOptions() as $displayAs => $label) {
            $cssClass = strcasecmp($displayAs, $category["DisplayAs"]) === 0 ? "selected" : "";
            $icon = dashboardSymbol(self::displayAsIconName($displayAs));

            $cdd->addLink(
                t($label),
                "#",
                "displayas." . strtolower($displayAs),
                "js-displayas " . $cssClass,
                [],
                ["icon" => $icon, "attributes" => ["data-displayas" => strtolower($displayAs)]],
                false
            );
        }

        $cdd->addGroup("", "actions")->addLink(
            t("Add Subcategory"),
            "/vanilla/settings/addcategory?parent={$category["CategoryID"]}",
            "actions.add"
        );

        if (val("CanDelete", $category, true) && $category["CountCategories"] === 0) {
            $cdd->addGroup("", "delete")->addLink(
                t("Delete"),
                "/vanilla/settings/deletecategory?categoryid={$category["CategoryID"]}",
                "delete.delete",
                "",
                [],
                [
                    "attributes" => [
                        "data-categoryid" => $category["CategoryID"],
                        "data-countDiscussions" => $category["CountDiscussions"],
                    ],
                ]
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
    public function getTree($categoryID, array $options = [])
    {
        $result = $this->collection->getTree($categoryID, $options);
        return $result;
    }

    /**
     * Get the descendant categoryIDs that the user has permission to view.
     *
     * @param int $categoryID
     * @return array
     */
    public function getCategoryDescendantIDs(int $categoryID): array
    {
        $descendantIDs = $this->collection->getDescendantIDs($categoryID);
        $visibleIDs = $this->getVisibleCategoryIDs();
        if ($visibleIDs === true) {
            return $descendantIDs;
        } else {
            return array_values(array_intersect($descendantIDs, $visibleIDs));
        }
    }

    /**
     * @param int|string $id The parent category ID or slug.
     * @param int|null $offset Offset results by given value.
     * @param int|null $limit Total number of results should not exceed this value.
     * @param string|null $filter Restrict results to only those with names matching this value, if provided.
     * @param string $orderFields
     * @param string $orderDirection
     * @param array $options
     * @return array
     */
    public function getTreeAsFlat(
        $id,
        $offset = null,
        $limit = null,
        $filter = null,
        $orderFields = "Name",
        $orderDirection = "asc",
        array $options = []
    ) {
        $joinDirtyRecords = $options[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;
        if ($joinDirtyRecords) {
            $this->applyDirtyWheres();
        }

        $query = $this->SQL
            ->from("Category")
            ->where("DisplayAs <>", "Heading")
            ->where("ParentCategoryID", $id)
            ->limit($limit, $offset)
            ->orderBy($orderFields, $orderDirection);

        if ($filter) {
            $query->like("Name", $filter);
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
    public static function filterChildren(&$categories, $childField = "Children")
    {
        foreach ($categories as &$category) {
            $children = &$category[$childField];
            if (in_array($category["DisplayAs"], ["Categories", "Flat"])) {
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
    public function filterFollowing($categories)
    {
        $result = [];
        foreach ($categories as $category) {
            if (val("Following", $category)) {
                if (!empty($category["Children"])) {
                    $category["Children"] = $this->filterFollowing($category["Children"]);
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
    public function flattenCategories(array $categories)
    {
        self::calculateData($categories);
        self::joinUserData($categories);

        foreach ($categories as &$category) {
            // Fix the depth to be relative, not global.
            $category["Depth"] = 1;

            // We don't have children, but trees are expected to have this key.
            $category["Children"] = [];
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
    public static function getByPermission(
        $permission = "Discussions.Add",
        $categoryID = null,
        $filter = [],
        $permFilter = []
    ) {
        static $map = ["Discussions.Add" => "PermsDiscussionsAdd", "Discussions.View" => "PermsDiscussionsView"];
        $field = $map[$permission];
        $permFilters = [];

        $result = [];
        $categories = self::categories();
        foreach ($categories as $iD => $category) {
            if (!$category[$field]) {
                continue;
            }

            if ($categoryID != $iD) {
                if ($category["CategoryID"] <= 0) {
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
                    $permCategory = val($category["PermissionCategoryID"], $categories);
                    if ($permCategory) {
                        if (!isset($permFilters[$permCategory["CategoryID"]])) {
                            $permFilters[$permCategory["CategoryID"]] = self::where($permCategory, $permFilter);
                        }

                        $exclude = !$permFilters[$permCategory["CategoryID"]];
                    } else {
                        $exclude = true;
                    }
                }

                if ($exclude) {
                    continue;
                }

                if ($category["DisplayAs"] == "Heading") {
                    if ($permission == "Discussions.Add") {
                        continue;
                    } else {
                        $category["PermsDiscussionsAdd"] = false;
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
    public static function where($row, $where)
    {
        if (empty($where)) {
            return true;
        }

        foreach ($where as $key => $value) {
            $rowValue = val($key, $row);

            // If there are no discussion types set then all discussion types are allowed.
            if ($key == "AllowedDiscussionTypes" && empty($rowValue)) {
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
     * @param int|false $timestamp The time the points were given.
     */
    public static function givePoints(
        int $userID,
        int $points,
        string $source = "Other",
        int $categoryID = 0,
        $timestamp = false
    ) {
        // Figure out whether or not the category tracks points by CateoryID or by PointsCategoryID.
        $category = self::categories($categoryID);

        if (isset($category["PointsCategoryID"]) && $category["PointsCategoryID"]) {
            $categoryID = val("PointsCategoryID", $category);
        }

        UserModel::givePoints($userID, $points, [$source, "CategoryID" => $categoryID], $timestamp);
    }

    /**
     *
     *
     * @param array|Gdn_DataSet &$data Dataset.
     * @param string $column Name of database column.
     * @param array $options The 'Join' key may contain array of columns to join on.
     * @since 2.0.18
     */
    public static function joinCategories(&$data, $column = "CategoryID", $options = [])
    {
        $join = val("Join", $options, ["Name" => "Category", "PermissionCategoryID", "UrlCode" => "CategoryUrlCode"]);

        if ($data instanceof Gdn_DataSet) {
            $data2 = $data->result();
        } else {
            $data2 = &$data;
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
    private function gatherLastIDs($categoryTree, &$result = null)
    {
        if ($result === null) {
            $result = [];
        }

        foreach ($categoryTree as $category) {
            $result["{$category["LastDiscussionID"]}/{$category["LastCommentID"]}"] = [
                "DiscussionID" => $category["LastDiscussionID"],
                "CommentID" => $category["LastCommentID"],
            ];

            if (!empty($category["Children"])) {
                $this->gatherLastIDs($category["Children"], $result);
            }
        }
    }

    /**
     * Build the database category fields related to recent posts.
     *
     * @param array|object $discussion
     * @param array|object $comment
     * @return array
     */
    private static function postDBFields($discussion, $comment = null)
    {
        $result = [
            "LastCommentID" => null,
            "LastDateInserted" => null,
            "LastDiscussionID" => null,
        ];

        if ($discussion) {
            $result["LastCommentID"] = val("LastCommentID", $discussion);
            $result["LastDateInserted"] = val("DateLastComment", $discussion);
            $result["LastDiscussionID"] = val("DiscussionID", $discussion);
            $result["LastCategoryID"] = val("CategoryID", $discussion);
        }

        return $result;
    }

    /**
     * Join recent posts and users to a category tree.
     *
     * @param array &$categoryTree A category tree obtained with {@link CategoryModel::getChildTree()}.
     */
    public function joinRecent(&$categoryTree)
    {
        // Gather all of the IDs from the posts.
        $this->gatherLastIDs($categoryTree, $ids);
        $discussionIDs = array_unique(array_column($ids, "DiscussionID"));

        $discussionsWhere = [
            "DiscussionID" => $discussionIDs,
        ];
        if (!empty($discussionIDs)) {
            $discussions = $this->createSql()
                ->from("Discussion")
                ->select([
                    "DiscussionID",
                    "CategoryID",
                    "Name",
                    "InsertUserID",
                    "DateInserted",
                    "DateLastComment",
                    "LastCommentID",
                    "LastCommentUserID",
                ])
                ->where($discussionsWhere)
                ->get()
                ->resultArray();
            $discussions = array_column($discussions, null, "DiscussionID");
        } else {
            $discussions = [];
        }

        $commentUserIDs = array_column($discussions, "LastCommentUserID");
        $discussionUserIDs = array_column($discussions, "InsertUserID");
        $userIDs = array_values(array_filter(array_unique(array_merge($commentUserIDs, $discussionUserIDs))));
        // Just gather the users into the local cache.
        Gdn::userModel()->getIDs($userIDs);

        $this->joinRecentInternal($categoryTree, $discussions);
    }

    /**
     * This method supports {@link CategoryModel::joinRecent()}.
     *
     * @param array &$categoryTree The array of categories in tree format.
     * @param array $discussions An array of discussions indexed by discussion ID.
     */
    private function joinRecentInternal(&$categoryTree, $discussions)
    {
        foreach ($categoryTree as &$category) {
            $discussion = val($category["LastDiscussionID"], $discussions, null);

            if (!empty($discussion)) {
                // If the use doesn't have permission to view the discussion, then sanitize the data.
                $hasPermission = self::checkPermission($discussion["CategoryID"], "Vanilla.Discussions.View");
                if (!$hasPermission) {
                    $discussion["Name"] = t("(Restricted Content)");
                }

                $category["LastTitle"] = $discussion["Name"];
                $category["LastUrl"] = DiscussionModel::discussionUrl($discussion, false, true) . "#latest";
                $category["LastDiscussionUserID"] = $discussion["InsertUserID"];
                $category["LastUserID"] = $discussion["LastCommentUserID"] ?? $discussion["InsertUserID"];
                $user = Gdn::userModel()->getID($category["LastUserID"]);
                foreach (["Name", "Email", "Photo"] as $field) {
                    $category["Last" . $field] = val($field, $user);
                }
            } else {
                $category["LastTitle"] = null;
                $category["LastUrl"] = null;
                $category["LastDiscussionUserID"] = null;
                $category["LastUserID"] = null;
                $category["LastTitle"] = null;
            }

            if (!empty($category["Children"])) {
                $this->joinRecentInternal($category["Children"], $discussions);
            }
        }
    }

    /**
     * @deprecated Use {@link CategoryModel::joinRecent()}
     *
     * @param $data
     *
     * @return bool
     */
    public static function joinRecentPosts(&$data)
    {
        self::instance()->joinRecent($data);
        return true;
    }

    /**
     *
     *
     * @param null $category
     * @param null $categories
     */
    public static function joinRecentChildPosts(&$category = null, &$categories = null)
    {
        if ($categories === null) {
            $categories = &self::$Categories;
        }

        if ($category === null) {
            $category = &$categories[-1];
        }

        if (!isset($category["ChildIDs"])) {
            return;
        }

        $lastTimestamp = Gdn_Format::toTimestamp($category["LastDateInserted"]);
        $lastCategoryID = null;

        if ($category["DisplayAs"] == "Categories") {
            // This is an overview category so grab it's recent data from its children.
            foreach ($category["ChildIDs"] as $categoryID) {
                if (!isset($categories[$categoryID])) {
                    continue;
                }

                $childCategory = &$categories[$categoryID];
                if ($childCategory["DisplayAs"] == "Categories") {
                    self::joinRecentChildPosts($childCategory, $categories);
                }
                $timestamp = Gdn_Format::toTimestamp($childCategory["LastDateInserted"]);

                if ($lastTimestamp === false || $lastTimestamp < $timestamp) {
                    $lastTimestamp = $timestamp;
                    $lastCategoryID = $categoryID;
                }
            }

            if ($lastCategoryID) {
                $lastCategory = $categories[$lastCategoryID];

                $category["LastCommentID"] = $lastCategory["LastCommentID"];
                $category["LastDiscussionID"] = $lastCategory["LastDiscussionID"];
                $category["LastDateInserted"] = $lastCategory["LastDateInserted"];
                $category["LastTitle"] = $lastCategory["LastTitle"];
                $category["LastUserID"] = $lastCategory["LastUserID"];
                $category["LastDiscussionUserID"] = $lastCategory["LastDiscussionUserID"];
                $category["LastUrl"] = $lastCategory["LastUrl"];
                $category["LastCategoryID"] = $lastCategory["CategoryID"];
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
     *
     * @param array $categories
     * @param bool $addUserCategory
     * @param int | null $userID
     */
    public static function joinUserData(array &$categories, bool $addUserCategory = true, ?int $userID = null)
    {
        $iDs = array_column($categories, "CategoryID", "CategoryID");
        $categories = array_combine($iDs, $categories);

        if ($addUserCategory) {
            $userData = self::instance()->getUserCategories($userID);

            foreach ($iDs as $iD) {
                $category = $categories[$iD];

                $dateMarkedRead = $category["DateMarkedRead"] ?? false;
                $row = $userData[$iD] ?? [];
                if (!empty($row)) {
                    $userDateMarkedRead = $row["DateMarkedRead"];

                    if (
                        !$dateMarkedRead ||
                        ($userDateMarkedRead &&
                            Gdn_Format::toTimestamp($userDateMarkedRead) > Gdn_Format::toTimestamp($dateMarkedRead))
                    ) {
                        $categories[$iD]["DateMarkedRead"] = $userDateMarkedRead;
                        $dateMarkedRead = $userDateMarkedRead;
                    }

                    $categories[$iD]["Unfollow"] = $row["Unfollow"];
                } else {
                    $categories[$iD]["Unfollow"] = false;
                }

                // Calculate the following field.
                $following = !((bool) ($category["Archived"] ?? false) || (bool) ($row["Unfollow"] ?? false));
                $categories[$iD]["Following"] = $following;

                $categories[$iD]["Followed"] = boolval($row["Followed"] ?? false);

                $categories[$iD]["DateFollowed"] = $row["DateFollowed"] ?? null;

                // Calculate the read field.
                if ($category["DisplayAs"] == self::DISPLAY_HEADING) {
                    $categories[$iD]["Read"] = false;
                } elseif ($dateMarkedRead) {
                    if ($lastDateInserted = $category["LastDateInserted"] ?? false) {
                        $categories[$iD]["Read"] =
                            Gdn_Format::toTimestamp($dateMarkedRead) >= Gdn_Format::toTimestamp($lastDateInserted);
                    } else {
                        $categories[$iD]["Read"] = true;
                    }
                } else {
                    $categories[$iD]["Read"] = false;
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
     * Join allowed post types on categories.
     *
     * @param array $categories
     * @return void
     */
    public static function joinPostTypes(array &$categories): void
    {
        $postTypeModel = self::getPostTypeModel();
        $allowedPostTypes = $postTypeModel->getAllowedPostTypes();
        $postTypesByCategory = PostTypeModel::indexPostTypesByCategory($allowedPostTypes);
        $categoryAssociations = $postTypeModel->getCategoryAssociations();
        $categoryAssociations = ArrayUtils::arrayColumnArrays($categoryAssociations, "postTypeID", "categoryID");

        foreach ($categories as &$category) {
            $category["hasRestrictedPostTypes"] = !empty($categoryAssociations[$category["CategoryID"]]);
            $category["allowedPostTypeIDs"] = array_column(
                $postTypesByCategory[$category["CategoryID"]] ?? [],
                "postTypeID"
            );
            $allowedPostTypesOptions = array_values(
                array_intersect_key(
                    array_column($allowedPostTypes, null, "postTypeID"),
                    array_flip($category["allowedPostTypeIDs"])
                )
            );
            $category["allowedPostTypeOptions"] = self::normalizeCategoryPostType($allowedPostTypesOptions);
            $category["AllowedDiscussionTypes"] = array_unique(
                array_intersect($category["allowedPostTypeIDs"], array_values(PostTypeModel::LEGACY_TYPE_MAP))
            );
        }
    }

    /**
     * Normalize the postTypes data to be return by the index.
     *
     * @param array $postTypes
     * @return array
     */
    private static function normalizeCategoryPostType(array $postTypes): array
    {
        $result = [];
        foreach ($postTypes as $postType) {
            $result[] = [
                "postTypeID" => $postType["postTypeID"],
                "name" => $postType["name"],
                "parentPostTypeID" => $postType["parentPostTypeID"],
                "postHelperText" => $postType["postHelperText"],
                "layoutViewType" => $postType["layoutViewType"],
            ];
        }

        return $result;
    }

    /**
     * Delete a category.
     *
     * {@inheritdoc}
     */
    public function delete($where = [], $options = [])
    {
        if (is_numeric($where) || is_object($where)) {
            deprecated("CategoryModel->delete()", "CategoryModel->deleteandReplace()");

            $result = $this->deleteAndReplace($where, $options);
            return $result;
        }

        throw new \BadMethodCallException("CategoryModel->delete() is not supported.", 400);
    }

    /**
     * Delete a category.
     *
     * @param int $id The ID of the category to delete.
     * @param array $options An array of options to affect the behavior of the delete.
     *
     * - **newCategoryID**: The new category to point discussions to.
     * @return true
     */
    public function deleteID($id, $options = [])
    {
        $this->deleteAndReplace($id, val("newCategoryID", $options));
        return true;
    }

    /**
     * Method used to format category data
     *
     * @param array $row
     */
    public function fixRow(array &$row): void
    {
        if (!empty($row["LastTitle"])) {
            $row["LastTitle"] = Gdn::formatService()->renderPlainText($row["LastTitle"], TextFormat::FORMAT_KEY);
        }
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array|object $dbRecord Database record.
     * @param array $expand Expand options.
     *
     * @return array Return a Schema record.
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function normalizeRow($dbRecord, $expand = [])
    {
        if (is_object($dbRecord)) {
            $dbRecord = (array) $dbRecord;
        }
        $this->fixRow($dbRecord);
        if ($dbRecord["CategoryID"] === -1) {
            $dbRecord["Url"] = url("/categories", true);
            $dbRecord["DisplayAs"] = "Discussions";
        } else {
            $dbRecord["Url"] = self::categoryUrl($dbRecord, "", true);
        }

        if ($dbRecord["ParentCategoryID"] <= 0) {
            $dbRecord["ParentCategoryID"] = null;
        }

        if (ModelUtils::isExpandOption("lastPost", $expand) && !empty($dbRecord["LastDiscussionID"])) {
            $recentPost = [];
            $valid = true;
            $mappings = [
                "discussionID" => "LastDiscussionID",
                "commentID" => "LastCommentID",
                "name" => "LastTitle",
                "url" => "LastUrl",
                "dateInserted" => "LastDateInserted",
                "insertUserID" => "LastUserID",
            ];
            foreach ($mappings as $key => $mapping) {
                if (empty($dbRecord[$mapping]) && $mapping != "LastCommentID") {
                    $valid = false;
                    break;
                }
                $recentPost[$key] =
                    $key == "name"
                        ? Gdn::formatService()->renderPlainText($dbRecord[$mapping], TextFormat::FORMAT_KEY)
                        : $dbRecord[$mapping];
            }
            if ($valid) {
                $dbRecord["lastPost"] = $recentPost;
                if (!empty($dbRecord["LastUser"])) {
                    $dbRecord["lastPost"]["insertUser"] = $dbRecord["LastUser"];
                    unset($dbRecord["LastUser"]);
                }
            }
        }

        $dbRecord["Name"] = empty($dbRecord["Name"]) ? t("Untitled") : $dbRecord["Name"];
        $dbRecord["UrlCode"] = empty($dbRecord["UrlCode"]) ? " " : $dbRecord["UrlCode"];
        $dbRecord["CustomPermissions"] = $dbRecord["PermissionCategoryID"] === $dbRecord["CategoryID"];
        $dbRecord["Description"] = $dbRecord["Description"] ?: "";
        $displayAs = $dbRecord["DisplayAs"] ?? "";
        $dbRecord["DisplayAs"] = $displayAs ? strtolower($displayAs) : "discussions";
        $allowedDiscussionTypes = self::getAllowedDiscussionTypes($dbRecord);

        $dbDiscussionTypes = array_map(
            "strtolower",
            is_array($dbRecord["AllowedDiscussionTypes"]) ? $dbRecord["AllowedDiscussionTypes"] : ["Discussion"]
        );

        $dbRecord["AllowedDiscussionTypes"] = array_intersect($allowedDiscussionTypes, $dbDiscussionTypes);
        // Reindex array values, otherwise it _may_ fail validation.
        $dbRecord["AllowedDiscussionTypes"] = array_values($dbRecord["AllowedDiscussionTypes"]);

        if (!empty($dbRecord["Children"]) && is_array($dbRecord["Children"])) {
            $dbRecord["Children"] = array_map([$this, "normalizeRow"], $dbRecord["Children"]);
        }

        $dbRecord["isArchived"] = $dbRecord["Archived"];
        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);

        if (ModelUtils::isExpandOption(ModelUtils::EXPAND_CRAWL, $expand)) {
            $schemaRecord["canonicalID"] = "category_{$schemaRecord["categoryID"]}";
            $schemaRecord["scope"] = $this->getRecordScope($schemaRecord["categoryID"]);
            $schemaRecord["excerpt"] = $schemaRecord["description"];
            $schemaRecord["image"] = null;

            // Some plugins may create a different "type" field on the category. Our crawler is not aware of this, so we override it for the moment.
            $schemaRecord["type"] = "category";
            /** @var SiteSectionModel $siteSectionModel */
            $siteSectionModel = Gdn::getContainer()->get(SiteSectionModel::class);
            $siteSection = $siteSectionModel->getSiteSectionForAttribute("allCategories", $dbRecord["CategoryID"]);
            $schemaRecord["locale"] = $siteSection->getContentLocale();
        }

        $schemaRecord["iconUrl"] = $dbRecord["Photo"]
            ? (Gdn_UploadImage::url($dbRecord["Photo"]) ?:
            null) // In case false is returned.
            : null;
        $schemaRecord["bannerUrl"] = BannerImageModel::getBannerImageUrl($dbRecord["CategoryID"]) ?: null;

        // We add Images srcsets.
        $schemaRecord["iconUrlSrcSet"] = $this->imageSrcSetService->getResizedSrcSet($schemaRecord["iconUrl"]);
        $schemaRecord["bannerUrlSrcSet"] = $this->imageSrcSetService->getResizedSrcSet($schemaRecord["bannerUrl"]);

        return $schemaRecord;
    }

    /**
     * Get long runner count of total items to process.
     *
     * @param int $categoryID
     * @param array $options
     * @return int
     */
    public function getTotalCount(int $categoryID, array $options = []): int
    {
        $category = self::categories($categoryID);
        return $category["CountDiscussions"];
    }

    /**
     * Delete a category and all its discussions, individually.
     *
     * This method acts as a generator, yielding boolean false values until all discussions have been processed, at
     * which point the method will yield a boolean true.
     *
     * @param int $categoryID
     * @param array $options
     * @return Generator
     */
    public function deleteIDIterable(int $categoryID, array $options = []): Generator
    {
        $options += [
            "newCategoryID" => null,
        ];
        yield new LongRunnerQuantityTotal([$this, "getTotalCount"], [$categoryID, $options]);
        /** @var DiscussionModel $discussionModel */
        $discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
        if ($options["newCategoryID"]) {
            foreach ($discussionModel->moveByCategory($categoryID, $options["newCategoryID"]) as $d) {
                yield;
            }
        } else {
            foreach ($discussionModel->deleteByCategory($categoryID) as $d) {
                yield;
            }
        }
        $this->prepareForDelete($categoryID, false);
        $this->deleteInternal($categoryID, true);
        return LongRunner::FINISHED;
    }

    /**
     * Delete a category.
     * If $newCategoryID is:
     *  - A valid categoryID, every discussions and sub-categories will be moved to the new category.
     *  - Not a valid categoryID, all its discussions and sub-categories will be recursively deleted.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int|object $category The category to delete
     * @param int $newCategoryID ID of the category that will replace this one.
     */
    public function deleteAndReplace($category, $newCategoryID)
    {
        // Coerce the category into an object for deletion.
        if (is_numeric($category)) {
            $category = $this->getID($category, DATASET_TYPE_OBJECT);
        }

        if (is_array($category)) {
            $category = (object) $category;
        }

        // Don't do anything if the required category object & properties are not defined.
        if (
            !is_object($category) ||
            !property_exists($category, "CategoryID") ||
            !property_exists($category, "ParentCategoryID") ||
            !property_exists($category, "AllowDiscussions") ||
            !property_exists($category, "Name") ||
            $category->CategoryID <= 0
        ) {
            throw new \InvalidArgumentException(t("Invalid category for deletion."), 400);
        }

        $this->legacyDelete($category->CategoryID, $newCategoryID);
    }

    /**
     * Legacy method of deleting a category via direct database queries.
     *
     * @param int $categoryID
     * @param int $newCategoryID
     */
    private function legacyDelete($categoryID, $newCategoryID): void
    {
        static $recursionLevel = 0;

        // If there is a replacement category...
        if ($newCategoryID > 0) {
            $this->replaceCategory($categoryID, $newCategoryID, true);
        } else {
            $this->prepareForDelete($categoryID);

            // Recursively delete child categories and their content.
            $children = self::flattenTree($this->collection->getTree($categoryID));
            $recursionLevel++;
            foreach ($children as $child) {
                self::legacyDelete($child["CategoryID"], 0);
            }
            $recursionLevel--;
        }

        $this->deleteInternal($categoryID, $recursionLevel === 0);
        $this->deleteUserCategory(["CategoryID" => $categoryID]);
    }

    /**
     * Delete records from `GDN_UserCategory`.`
     *
     * @param array $where
     * @return int|false Returns the number of rows deleted or **false** on failure.
     */
    public static function deleteUserCategory(array $where)
    {
        $sql = \Gdn::sql();
        return $sql->delete("UserCategory", $where);
    }

    /**
     * This method gets the category ID for a given category slug.
     * If it's already a category ID, it is returned as-is.
     *
     * @param mixed $categorySlug
     * @return int|null
     */
    public function ensureCategoryID($categorySlug): ?int
    {
        if (is_numeric($categorySlug)) {
            // If the slug is numeric it's the categoryID.
            return (int) $categorySlug;
        }

        if (is_string($categorySlug)) {
            // If the slug isn't numeric, we fetch the categoryID from its slug.
            $categorySlug = urldecode($categorySlug);
            $category = (array) $this->getByCode($categorySlug);
            return (int) $category["CategoryID"];
        }

        return null;
    }

    /**
     * Get data for a single category selected by Url Code. Disregards permissions.
     *
     * @param $code
     * @return object SQL results.
     * @since 2.0.0
     * @access public
     *
     */
    public function getByCode($code)
    {
        return $this->SQL->getWhere("Category", ["UrlCode" => $code])->firstRow();
    }

    /**
     * Get data for a single category selected by ID. Disregards permissions.
     *
     * @param int $id The unique ID of category we're getting data for.
     * @param string $datasetType Not used.
     * @param array $options Not used.
     * @return object|array SQL results.
     */
    public function getID($id, $datasetType = DATASET_TYPE_OBJECT, $options = [])
    {
        $category = $this->SQL->getWhere("Category", ["CategoryID" => $id])->firstRow($datasetType);
        if (val("AllowedDiscussionTypes", $category) && is_string(val("AllowedDiscussionTypes", $category))) {
            setValue("AllowedDiscussionTypes", $category, dbdecode(val("AllowedDiscussionTypes", $category)));
        }

        return $category;
    }

    /**
     * @return array
     */
    public static function getDisplayAsOptions()
    {
        return self::$displayAsOptions;
    }

    /**
     * Get a single category from the collection.
     *
     * @param string|int $id The category code or ID.
     */
    private function getOne($id)
    {
        if (is_numeric($id)) {
            $id = (int) $id;
        }

        $category = $this->collection->get($id);
        return $category;
    }

    /**
     * Get list of categories (disregarding user permission for admins).
     *
     * @since 2.0.0
     *
     * @return object SQL results.
     */
    public function getAll()
    {
        $categoryData = $this->SQL
            ->select("c.*")
            ->from("Category c")
            ->orderBy("TreeLeft", "asc")
            ->get();

        $resultArray = $categoryData->resultArray();
        self::calculateData($resultArray);
        $categoryData->overrideResult($resultArray);
        return $categoryData;
    }

    /**
     * Return the number of descendants for a specific category.
     */
    public function getDescendantCountByCode($code)
    {
        $category = $this->getByCode($code);
        if ($category) {
            return round(($category->TreeRight - $category->TreeLeft - 1) / 2);
        }

        return 0;
    }

    /**
     * Get every ancestor categories above this one.
     * @param int|string|null|false $Category The category ID or url code.
     * @param bool $checkPermissions Whether or not to only return the categories with view permission.
     * @param bool $includeHeadings Whether or not to include heading categories.
     * @return array
     */
    public static function getAncestors($categoryID, $checkPermissions = true, $includeHeadings = false)
    {
        $result = [];

        if ($categoryID === false || $categoryID === null) {
            return [];
        }

        $category = self::categories($categoryID);

        if (!isset($category)) {
            return $result;
        }

        // Build up the ancestor array by tracing back through parents.
        $result[$category["CategoryID"]] = $category;
        $max = 20;
        while (isset($category["ParentCategoryID"]) && ($category = self::categories($category["ParentCategoryID"]))) {
            // Check for an infinite loop.
            if ($max <= 0) {
                break;
            }
            $max--;

            if ($category["CategoryID"] == -1) {
                break;
            }

            if ($checkPermissions && !$category["PermsDiscussionsView"]) {
                $category = self::categories($category["ParentCategoryID"]);
                continue;
            }

            // Return by ID or code.
            if (is_numeric($categoryID)) {
                $iD = $category["CategoryID"];
            } else {
                $iD = $category["UrlCode"];
            }

            if ($includeHeadings || $category["DisplayAs"] !== self::DISPLAY_HEADING) {
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
    public function getDescendantsByCode($code)
    {
        deprecated("CategoryModel::GetDescendantsByCode", "CategoryModel::GetAncestors");

        // SELECT title FROM tree WHERE lft < 4 AND rgt > 5 ORDER BY lft ASC;
        return $this->SQL
            ->select(
                "c.ParentCategoryID, c.CategoryID, c.TreeLeft, c.TreeRight, c.Depth, c.Name, c.Description, c.CountDiscussions, c.CountComments, c.AllowDiscussions, c.UrlCode"
            )
            ->from("Category c")
            ->join("Category d", "c.TreeLeft < d.TreeLeft and c.TreeRight > d.TreeRight")
            ->where("d.UrlCode", $code)
            ->orderBy("c.TreeLeft", "asc")
            ->get();
    }

    /**
     * Get the role specific permissions for a category.
     *
     * @param int $categoryID The ID of the category to get the permissions for.
     * @return array Returns an array of permissions.
     */
    public function getRolePermissions($categoryID)
    {
        $permissions = Gdn::permissionModel()->getJunctionPermissions(["JunctionID" => $categoryID], "Category");
        $result = [];

        foreach ($permissions as $perm) {
            $row = ["RoleID" => $perm["RoleID"]];
            unset($perm["Name"], $perm["RoleID"], $perm["JunctionID"], $perm["JunctionTable"], $perm["JunctionColumn"]);
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
    public static function getSubtree($parentCategory, $includeParent = true)
    {
        $parent = self::instance()->getOne($parentCategory);
        if ($parent === null) {
            return [];
        }

        if (val("DisplayAs", $parent) === self::DISPLAY_FLAT) {
            $categories = self::instance()->getTreeAsFlat($parent["CategoryID"]);
        } else {
            $categories = self::instance()->collection->getTree($parent["CategoryID"], ["maxdepth" => 10]);
            $categories = self::instance()->flattenTree($categories);
        }

        if ($includeParent) {
            $parent["Depth"] = 1;
            $result = [$parent["CategoryID"] => $parent];

            foreach ($categories as $category) {
                $category["Depth"]--;
                $result[$category["CategoryID"]] = $category;
            }
        } else {
            $result = array_column($categories, null, "CategoryID");
        }
        return $result;
    }

    /**
     * Get complete category/ies data.
     *
     * @param $categoryID
     * @param $permissions
     * @return Gdn_DataSet
     */
    public function getFull($categoryID = false, $permissions = false)
    {
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
            case "Vanilla.Discussions.Add":
                $permissions = "PermsDiscussionsAdd";
                break;
            case "Vanilla.Disussions.Edit":
                $permissions = "PermsDiscussionsEdit";
                break;
            default:
                $permissions = "PermsDiscussionsView";
                break;
        }

        $iDs = array_keys($categories);
        foreach ($iDs as $iD) {
            if ($iD < 0) {
                unset($categories[$iD]);
            } elseif (!$categories[$iD][$permissions]) {
                unset($categories[$iD]);
            } elseif (is_array($categoryID) && !in_array($iD, $categoryID)) {
                unset($categories[$iD]);
            }
        }

        //self::joinRecentPosts($Categories);
        foreach ($categories as &$category) {
            if ($category["ParentCategoryID"] <= 0) {
                self::joinRecentChildPosts($category, $categories);
            }
        }

        // This join users call can be very slow on forums with a lot of categories so we can disable it here.
        if ($this->JoinRecentUsers) {
            Gdn::userModel()->joinUsers($categories, ["LastUserID"]);
        }

        $result = new Gdn_DataSet($categories, DATASET_TYPE_ARRAY);
        $result->datasetType(DATASET_TYPE_OBJECT);
        return $result;
    }

    /**
     * Get a list of categories, considering several filters
     *
     * @param array|false $restrictIDs Optional list of category ids to mask the dataset
     * @param string|false $permissions Optional permission to require. Defaults to Vanilla.Discussions.View.
     * @param array|false $excludeWhere Exclude categories with any of these flags
     * @return \Gdn_DataSet
     */
    public function getFiltered($restrictIDs = false, $permissions = false, $excludeWhere = false)
    {
        // Get the current category list
        $categories = self::categories();

        // Filter out the categories we aren't supposed to view.
        if ($restrictIDs && !is_array($restrictIDs)) {
            $restrictIDs = [$restrictIDs];
        } else {
            $restrictIDs = $this->getVisibleCategoryIDs(["filterHideDiscussions" => true]);
        }

        switch ($permissions) {
            case "Vanilla.Discussions.Add":
                $permissions = "PermsDiscussionsAdd";
                break;
            case "Vanilla.Disussions.Edit":
                $permissions = "PermsDiscussionsEdit";
                break;
            default:
                $permissions = "PermsDiscussionsView";
                break;
        }

        $iDs = array_keys($categories);
        foreach ($iDs as $iD) {
            // Exclude the root category
            if ($iD < 0) {
                unset($categories[$iD]);
            }
            // No categories where we don't have permission
            elseif (!$categories[$iD][$permissions]) {
                unset($categories[$iD]);
            }

            // No categories whose filter fields match the provided filter values
            elseif (is_array($excludeWhere)) {
                foreach ($excludeWhere as $filter => $filterValue) {
                    if (val($filter, $categories[$iD], false) == $filterValue) {
                        unset($categories[$iD]);
                    }
                }
            }
            // No categories that are otherwise filtered out
            elseif (is_array($restrictIDs) && !in_array($iD, $restrictIDs)) {
                unset($categories[$iD]);
            }
        }

        Gdn::userModel()->joinUsers($categories, ["LastUserID"]);

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
    public function getFullByUrlCode($urlCode)
    {
        $data = (object) self::categories($urlCode);

        // Check to see if the user has permission for this category.
        // Get the category IDs.
        $categoryIDs = DiscussionModel::categoryPermissions();
        if (is_array($categoryIDs) && !in_array(val("CategoryID", $data), $categoryIDs)) {
            $data = false;
        }
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getWhere(
        $where = false,
        $orderFields = "",
        $orderDirection = "asc",
        $limit = false,
        $offset = false
    ) {
        if (!is_array($where)) {
            $where = [];
        }

        if (array_key_exists("Followed", $where)) {
            if ($where["Followed"]) {
                $followed = $this->getFollowed(Gdn::session()->UserID);
                $categoryIDs = array_column($followed, "CategoryID");

                if (isset($where["CategoryID"])) {
                    $where["CategoryID"] = array_values(array_intersect((array) $where["CategoryID"], $categoryIDs));
                } else {
                    $where["CategoryID"] = $categoryIDs;
                }
            }
            unset($where["Followed"]);
        }
        $result = parent::getWhere($where, $orderFields, $orderDirection, $limit, $offset);
        return $result;
    }

    /**
     * Get a set of categoryIDs for some where query.
     *
     * @param array{string, mixed} $where
     *
     * @return int[]
     */
    public function selectCachedIDs(
        array $where,
        $orderFields = "",
        $orderDirection = "asc",
        $limit = false,
        $offset = false
    ): array {
        $result = $this->modelCache->getCachedOrHydrate(
            [$where, "ids"],
            function () use ($where, $orderFields, $orderDirection, $limit, $offset) {
                $ids = $this->createSql()
                    ->from($this->getTableName())
                    ->select("CategoryID")
                    ->where($where)
                    ->orderBy($orderFields, $orderDirection)
                    ->limit($limit)
                    ->offset($offset)
                    ->get()
                    ->column("CategoryID");
                return $ids;
            },
            [\Gdn_Cache::FEATURE_EXPIRY => 60 * 60]
        );
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getCount($wheres = "")
    {
        if (array_key_exists("Followed", (array) $wheres)) {
            if ($wheres["Followed"]) {
                $followed = $this->getFollowed(Gdn::session()->UserID);
                $categoryIDs = array_column($followed, "CategoryID");

                if (isset($wheres["CategoryID"])) {
                    $wheres["CategoryID"] = array_values(array_intersect((array) $wheres["CategoryID"], $categoryIDs));
                } else {
                    $wheres["CategoryID"] = $categoryIDs;
                }
            }
            unset($wheres["Followed"]);
        }

        return parent::getCount($wheres);
    }

    /**
     * A simplified version of GetWhere that polls the cache instead of the database.
     * @param array $where
     * @return array
     * @since 2.2.2
     */
    public function getWhereCache($where)
    {
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
    public function hasChildren($categoryID)
    {
        $childData = $this->SQL
            ->select("CategoryID")
            ->from("Category")
            ->where("ParentCategoryID", $categoryID)
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
    public static function joinModerators(&$data, $permission = "Vanilla.Comments.Edit", $column = "Moderators")
    {
        $moderators = Gdn::sql()
            ->select("u.UserID, u.Name, u.Photo, u.Email")
            ->select("p.JunctionID as CategoryID")
            ->from("User u")
            ->join("UserRole ur", "ur.UserID = u.UserID")
            ->join("Permission p", "ur.RoleID = p.RoleID")
            ->where("`" . $permission . "`", 1)
            ->get()
            ->resultArray();

        $moderators = Gdn_DataSet::index($moderators, "CategoryID", ["Unique" => false]);

        foreach ($data as &$category) {
            $iD = val("PermissionCategoryID", $category);
            $mods = val($iD, $moderators, []);
            $modIDs = [];
            $uniqueMods = [];
            foreach ($mods as $mod) {
                if (!in_array($mod["UserID"], $modIDs)) {
                    $modIDs[] = $mod["UserID"];
                    $uniqueMods[] = $mod;
                }
            }
            setValue($column, $category, $uniqueMods);
        }
    }

    /**
     * Return the category that contains the permissions for the given category.
     *
     * @param mixed $category
     * @since 2.2
     */
    public static function permissionCategory($category)
    {
        if (empty($category)) {
            return self::categories(-1);
        }

        if (!is_array($category) && !is_object($category)) {
            $category = self::categories($category);
        }

        if (empty($category)) {
            return self::categories(-1);
        }

        $permissionCategory = self::categories(val("PermissionCategoryID", $category));
        if (empty($permissionCategory)) {
            return self::categories(-1);
        }

        // Ensure all of our values are processed properly.
        self::calculate($permissionCategory);
        return $permissionCategory;
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
    public function rebuildTree($bySort = false)
    {
        // Grab all of the categories.
        if ($bySort) {
            $order = "Sort, Name";
        } else {
            $order = "TreeLeft, Sort, Name";
        }

        $categories = $this->SQL->get("Category", $order);
        $categories = Gdn_DataSet::index($categories->resultArray(), "CategoryID");

        // Make sure the tree has a root.
        if (!isset($categories[-1])) {
            $rootCat = [
                "CategoryID" => -1,
                "TreeLeft" => 1,
                "TreeRight" => 4,
                "Depth" => 0,
                "InsertUserID" => 1,
                "UpdateUserID" => 1,
                "DateInserted" => Gdn_Format::toDateTime(),
                "DateUpdated" => Gdn_Format::toDateTime(),
                "Name" => "Root",
                "UrlCode" => "",
                "Description" => "Root of category tree. Users should never see this.",
                "PermissionCategoryID" => -1,
                "Sort" => 0,
                "ParentCategoryID" => null,
                "CountCategories" => 0,
            ];
            $categories[-1] = $rootCat;
            $this->SQL->insert("Category", $rootCat);
        }

        // Build a tree structure out of the categories.
        $root = null;
        foreach ($categories as &$cat) {
            if (!isset($cat["CategoryID"])) {
                continue;
            }

            // Backup category settings for efficient database saving.
            try {
                $cat["_TreeLeft"] = $cat["TreeLeft"];
                $cat["_TreeRight"] = $cat["TreeRight"];
                $cat["_Depth"] = $cat["Depth"];
                $cat["_PermissionCategoryID"] = $cat["PermissionCategoryID"];
                $cat["_ParentCategoryID"] = $cat["ParentCategoryID"];
                $cat["_CountCategories"] = $cat["CountCategories"];
            } catch (Exception $ex) {
                // Suppress exceptions from bubbling up.
            }

            if ($cat["CategoryID"] == -1) {
                $root = &$cat;
                continue;
            }

            $parentID = $cat["ParentCategoryID"];
            if (!$parentID) {
                $parentID = -1;
                $cat["ParentCategoryID"] = $parentID;
            }
            if (!isset($categories[$parentID]["Children"])) {
                $categories[$parentID]["Children"] = [];
            }
            $categories[$parentID]["Children"][] = &$cat;
        }
        unset($cat);

        // Reset CountCategories based on children.
        foreach ($categories as $cat) {
            if (isset($cat["CategoryID"])) {
                $categories[$cat["CategoryID"]]["CountCategories"] = count($cat["Children"] ?? []);
            }
        }

        // Set the tree attributes of the tree.
        $this->_SetTree($root);
        unset($root);

        // Save the tree structure.
        foreach ($categories as $cat) {
            if (!isset($cat["CategoryID"])) {
                continue;
            }

            if (
                $cat["_TreeLeft"] != $cat["TreeLeft"] ||
                $cat["_TreeRight"] != $cat["TreeRight"] ||
                $cat["_Depth"] != $cat["Depth"] ||
                $cat["PermissionCategoryID"] != $cat["PermissionCategoryID"] ||
                $cat["_ParentCategoryID"] != $cat["ParentCategoryID"] ||
                $cat["Sort"] != $cat["TreeLeft"] ||
                $cat["_CountCategories"] != $cat["CountCategories"]
            ) {
                $this->SQL->put(
                    "Category",
                    [
                        "TreeLeft" => $cat["TreeLeft"],
                        "TreeRight" => $cat["TreeRight"],
                        "Depth" => $cat["Depth"],
                        "PermissionCategoryID" => $cat["PermissionCategoryID"],
                        "ParentCategoryID" => $cat["ParentCategoryID"],
                        "Sort" => $cat["TreeLeft"],
                        "CountCategories" => $cat["CountCategories"],
                    ],
                    ["CategoryID" => $cat["CategoryID"]]
                );
            }
        }
        self::clearCache();

        // Make sure local instance is reset.
        if ($this !== self::instance()) {
            $this->collection->flushCache();
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
    protected function _SetTree(&$node, $left = 1, $depth = 0)
    {
        $right = $left + 1;

        if (isset($node["Children"])) {
            foreach ($node["Children"] as &$child) {
                $right = $this->_SetTree($child, $right, $depth + 1);
                $child["ParentCategoryID"] = $node["CategoryID"];
                if ($child["PermissionCategoryID"] != $child["CategoryID"]) {
                    $child["PermissionCategoryID"] = val("PermissionCategoryID", $node, $child["CategoryID"]);
                }
            }
            unset($node["Children"]);
        }

        $node["TreeLeft"] = $left;
        $node["TreeRight"] = $right;
        $node["Depth"] = $depth;

        return $right + 1;
    }

    /**
     * Save a subtree.
     *
     * @param array $subtree A nested array where each array contains a CategoryID and optional Children element.
     * @parem int $parentID Parent ID of the subtree
     */
    public function saveSubtree($subtree, $parentID)
    {
        $this->saveSubtreeInternal($subtree, $parentID);
    }

    /**
     * Save a subtree.
     *
     * @param array $subtree A nested array where each array contains a CategoryID and optional Children element.
     * @param int|null $parentID The parent ID of the subtree.
     * @param bool $rebuild Whether or not to rebuild the nested set after saving.
     */
    private function saveSubtreeInternal($subtree, $parentID = null, $rebuild = true)
    {
        $order = 1;
        foreach ($subtree as $row) {
            $save = [];
            $category = $this->collection->get((int) $row["CategoryID"]);
            if (!$category) {
                $this->Validation->addValidationResult("CategoryID", "@Category {$row["CategoryID"]} does not exist.");
                continue;
            }

            if ($category["Sort"] != $order) {
                $save["Sort"] = $order;
            }

            if ($parentID !== null && $category["ParentCategoryID"] != $parentID) {
                $save["ParentCategoryID"] = $parentID;

                if ($category["PermissionCategoryID"] != $category["CategoryID"]) {
                    $parentCategory = $this->collection->get((int) $parentID);
                    $save["PermissionCategoryID"] = $parentCategory["PermissionCategoryID"];
                }
            }

            if (!empty($save)) {
                $this->setField($category["CategoryID"], $save);
            }

            if (!empty($row["Children"])) {
                $this->saveSubtreeInternal($row["Children"], $category["CategoryID"], false);
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
    public function saveTree($treeArray)
    {
        // Grab all of the categories so that permissions can be properly saved.
        $permTree = $this->SQL
            ->select("CategoryID, PermissionCategoryID, TreeLeft, TreeRight, Depth, Sort, ParentCategoryID")
            ->from("Category")
            ->get();
        $permTree = $permTree->index($permTree->resultArray(), "CategoryID");

        // The tree must be walked in order for the permissions to save properly.
        usort($treeArray, ["CategoryModel", "_TreeSort"]);
        $saves = [];

        foreach ($treeArray as $i => $node) {
            $categoryID = val("item_id", $node);
            if ($categoryID == "root") {
                $categoryID = -1;
            }

            $parentCategoryID = val("parent_id", $node);
            if (in_array($parentCategoryID, ["root", "none"])) {
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
                if ($permTree[$categoryID]["PermissionCategoryID"] != $permissionCategoryID) {
                    $permCatChanged = true;
                }
                $permTree[$categoryID]["PermissionCategoryID"] = $permissionCategoryID;
            }
            $permTree[$categoryID]["Touched"] = true;

            // Only update if the tree doesn't match the database.
            $row = $permTree[$categoryID];
            if (
                $node["left"] != $row["TreeLeft"] ||
                $node["right"] != $row["TreeRight"] ||
                $node["depth"] != $row["Depth"] ||
                $parentCategoryID != $row["ParentCategoryID"] ||
                $node["left"] != $row["Sort"] ||
                $permCatChanged
            ) {
                $set = [
                    "TreeLeft" => $node["left"],
                    "TreeRight" => $node["right"],
                    "Depth" => $node["depth"],
                    "Sort" => $node["left"],
                    "ParentCategoryID" => $parentCategoryID,
                    "PermissionCategoryID" => $permissionCategoryID,
                ];

                $this->SQL->update("Category", $set, ["CategoryID" => $categoryID])->put();

                self::refreshCacheDeferred($categoryID);
                $saves[] = array_merge(["CategoryID" => $categoryID], $set);
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
    public function joinUserCategory()
    {
        return $this->joinUserCategory;
    }

    /**
     * Set whether or not to join information from GDN_UserCategory in {@link CategoryModel::calculateUser()}.
     *
     * @param boolean $joinUserCategory The new value to set.
     * @return CategoryModel Returns `$this` for fluent calls.
     */
    public function setJoinUserCategory($joinUserCategory)
    {
        $this->joinUserCategory = $joinUserCategory;
        return $this;
    }

    /**
     * Create a new category collection tied to this model.
     *
     * @return CategoryCollection Returns a new collection.
     */
    public function createCollection(): CategoryCollection
    {
        try {
            $collection = gdn::getContainer()->get(CategoryCollection::class);

            // Inject the calculator dependency.
            $collection->setConfig(Gdn::config());
            $collection->setStaticCalculator(function (&$category) {
                self::calculate($category);
            });

            $collection->setUserCalculator(function (&$category) {
                $this->calculateUser($category);
            });

            return $collection;
        } catch (Throwable $t) {
            throw new RuntimeException("Couldn't instantiate CategoryCollection", 500, $t);
        }
    }

    /**
     * @return CategoryCollection
     */
    public function getCollection(): CategoryCollection
    {
        return $this->collection;
    }

    /**
     * Sort a list of categories as if they were a tree and were flattened.
     * Any categories that could not be resolved into the tree are added at the end.
     *
     * @param array $categories The categories input.
     *
     * @return array The sorted categories.
     */
    public static function sortCategoriesAsTree(array $categories): array
    {
        $result = CategoryCollection::treeBuilder()->sort($categories);
        return $result;
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
    protected function _treeSort($a, $b)
    {
        if ($a["left"] > $b["left"]) {
            return 1;
        } elseif ($a["left"] < $b["left"]) {
            return -1;
        } else {
            return 0;
        }
    }

    /**
     * Saves the category.
     *
     * @param array $formPostValues The values being posted back from the form.
     * @param array|false $settings Additional settings to affect saving.
     * @return int ID of the saved category.
     */
    public function save($formPostValues, $settings = false)
    {
        // Define the primary key in this model's table.
        $this->defineSchema();

        // Get data from form
        $CategoryID = val("CategoryID", $formPostValues, false);
        $UrlCode = val("UrlCode", $formPostValues, "");
        $AllowDiscussions = val("AllowDiscussions", $formPostValues, 1);
        $CustomPermissions =
            (bool) val("CustomPermissions", $formPostValues) || is_array(val("Permissions", $formPostValues));
        $CustomPoints = val("CustomPoints", $formPostValues, null);
        $allowedDiscussionTypes =
            isset($formPostValues["AllowedDiscussionTypes"]) && is_array($formPostValues["AllowedDiscussionTypes"])
                ? $formPostValues["AllowedDiscussionTypes"]
                : null;
        $SetPostWithLegacyTypes = false;
        $allowedPostTypeIDs = $formPostValues["allowedPostTypeIDs"] ?? null;

        if (isset($allowedDiscussionTypes)) {
            $formPostValues["AllowedDiscussionTypes"] = dbencode($formPostValues["AllowedDiscussionTypes"]);
        } elseif (!empty($allowedPostTypeIDs)) {
            $legacyTypesFromPostTypes = array_intersect($allowedPostTypeIDs, PostTypeModel::LEGACY_TYPE_MAP);
            $legacyTypesFromPostTypes = array_values(array_map("ucfirst", $legacyTypesFromPostTypes));
            $formPostValues["AllowedDiscussionTypes"] = dbencode($legacyTypesFromPostTypes);
            $SetPostWithLegacyTypes = count($legacyTypesFromPostTypes) > 0;
        } else {
            $formPostValues["AllowedDiscussionTypes"] = null;
        }

        // Is this a new category?
        $Insert = $CategoryID === false;
        if ($Insert) {
            $this->addInsertFields($formPostValues);
        }

        // Kludge to allow resetting an existing category's permissions as part of an update.
        $resetPermissions =
            !$Insert && array_key_exists("Permissions", $formPostValues) && $formPostValues["Permissions"] === null;

        $this->addUpdateFields($formPostValues);

        // Add some extra validation to the url code if one is provided.
        if ($Insert || array_key_exists("UrlCode", $formPostValues)) {
            $this->Validation->applyRule("UrlCode", "Required");
            $this->Validation->applyRule("UrlCode", "UrlStringRelaxed");

            // Url slugs cannot be the name of a CategoriesController method or fully numeric.
            $this->Validation->addRule("CategorySlug", "function:validateCategoryUrlCode");
            $this->Validation->applyRule(
                "UrlCode",
                "CategorySlug",
                "Url code cannot be numeric, contain spaces or be the name of an internal method."
            );

            // Make sure that the UrlCode is unique among categories.
            $this->SQL
                ->select("CategoryID")
                ->from("Category")
                ->where("UrlCode", $UrlCode);

            if ($CategoryID) {
                $this->SQL->where("CategoryID <>", $CategoryID);
            }

            if ($this->SQL->get()->numRows()) {
                $this->Validation->addValidationResult(
                    "UrlCode",
                    "The specified url code is already in use by another category."
                );
            }
        } else {
            // Prevent validation from a previous save.
            $this->Validation->unapplyRule("UrlCode");
        }

        if (isset($formPostValues["ParentCategoryID"])) {
            if (empty($formPostValues["ParentCategoryID"])) {
                $formPostValues["ParentCategoryID"] = -1;
            } else {
                $parent = CategoryModel::categories($formPostValues["ParentCategoryID"]);
                if (!$parent) {
                    $formPostValues["ParentCategoryID"] = -1;
                }
            }
        }
        // Apply
        $newFeaturedSort = $this->calcFeaturedSort($CategoryID, $formPostValues);
        if ($newFeaturedSort !== null) {
            $formPostValues["SortFeatured"] = $newFeaturedSort;
        }
        // Prep and fire event.
        $this->EventArguments["FormPostValues"] = &$formPostValues;
        $this->EventArguments["CategoryID"] = $CategoryID;
        $this->fireEvent("BeforeSaveCategory");

        // Validate the form posted values.
        if ($this->validate($formPostValues, $Insert)) {
            $Fields = $this->Validation->schemaValidationFields();
            $Fields = $this->coerceData($Fields);
            unset($Fields["CategoryID"]);
            $Fields["AllowDiscussions"] = isset($Fields["AllowDiscussions"])
                ? (bool) $Fields["AllowDiscussions"]
                : (bool) $AllowDiscussions;

            if ($Insert === false) {
                $OldCategory = $this->getID($CategoryID, DATASET_TYPE_ARRAY);
                if (null === $AllowDiscussions) {
                    $AllowDiscussions = $OldCategory["AllowDiscussions"]; // Force the allowdiscussions property
                }
                $Fields["AllowDiscussions"] = (bool) $AllowDiscussions;

                // Figure out custom points.
                if ($CustomPoints !== null) {
                    if ($CustomPoints) {
                        $Fields["PointsCategoryID"] = $CategoryID;
                    } else {
                        $Parent = self::categories(val("ParentCategoryID", $Fields, $OldCategory["ParentCategoryID"]));
                        $Fields["PointsCategoryID"] = val("PointsCategoryID", $Parent, 0);
                    }
                }

                $this->update($Fields, ["CategoryID" => $CategoryID]);

                // Check for a change in the parent category.
                if (
                    isset($Fields["ParentCategoryID"]) &&
                    $OldCategory["ParentCategoryID"] != $Fields["ParentCategoryID"]
                ) {
                    $this->rebuildTree();
                }
            } else {
                $CategoryID = $this->insert($Fields);

                if ($CategoryID) {
                    if ($CustomPermissions || $SetPostWithLegacyTypes) {
                        $this->SQL->put(
                            "Category",
                            ["PermissionCategoryID" => $CategoryID],
                            ["CategoryID" => $CategoryID]
                        );
                    }
                    if ($CustomPoints) {
                        $this->SQL->put("Category", ["PointsCategoryID" => $CategoryID], ["CategoryID" => $CategoryID]);
                    }
                }

                $this->rebuildTree(); // Safeguard to make sure that treeleft and treeright cols are added
            }

            // Save the permissions
            if ($CategoryID) {
                // Check to see if this category uses custom permissions.
                if ($CustomPermissions) {
                    $permissionModel = Gdn::permissionModel();

                    if (is_array(val("Permissions", $formPostValues))) {
                        // The permissions were posted in an API format provided by settings/getcategory
                        $permissions = val("Permissions", $formPostValues);
                        foreach ($permissions as &$perm) {
                            $perm["JunctionTable"] = "Category";
                            $perm["JunctionColumn"] = "PermissionCategoryID";
                            $perm["JunctionID"] = $CategoryID;
                        }
                    } else {
                        // The permissions were posted in the web format provided by settings/addcategory and settings/editcategory
                        $permissions = $permissionModel->pivotPermissions(val("Permission", $formPostValues, []), [
                            "JunctionID" => $CategoryID,
                        ]);
                    }

                    if ($settings["overWrite"] ?? empty($settings)) {
                        $permissionModel->saveAll($permissions, [
                            "JunctionID" => $CategoryID,
                            "JunctionTable" => "Category",
                        ]);
                    } else {
                        foreach ($permissions as $perm) {
                            $permissionModel->save($perm);
                        }
                    }

                    if (!$Insert) {
                        // Figure out my last permission and tree info.
                        $Data = $this->SQL
                            ->select("PermissionCategoryID, TreeLeft, TreeRight")
                            ->from("Category")
                            ->where("CategoryID", $CategoryID)
                            ->get()
                            ->firstRow(DATASET_TYPE_ARRAY);

                        // Update this category's permission.
                        $this->SQL->put(
                            "Category",
                            ["PermissionCategoryID" => $CategoryID],
                            ["CategoryID" => $CategoryID]
                        );

                        // Update all of my children that shared my last category permission.
                        $this->SQL->put(
                            "Category",
                            ["PermissionCategoryID" => $CategoryID],
                            [
                                "TreeLeft >" => $Data["TreeLeft"],
                                "TreeRight <" => $Data["TreeRight"],
                                "PermissionCategoryID" => $Data["PermissionCategoryID"],
                            ]
                        );
                    }
                } elseif (!$Insert && $resetPermissions) {
                    // Figure out my parent's permission.
                    $NewPermissionID = $this->SQL
                        ->select("p.PermissionCategoryID")
                        ->from("Category c")
                        ->join("Category p", "c.ParentCategoryID = p.CategoryID")
                        ->where("c.CategoryID", $CategoryID)
                        ->get()
                        ->value("PermissionCategoryID", 0);

                    if ($NewPermissionID != $CategoryID) {
                        // Update all of my children that shared my last permission.
                        $this->SQL->put(
                            "Category",
                            ["PermissionCategoryID" => $NewPermissionID],
                            ["PermissionCategoryID" => $CategoryID]
                        );
                    }

                    // Delete my custom permissions.
                    $this->SQL->delete("Permission", [
                        "JunctionTable" => "Category",
                        "JunctionColumn" => "PermissionCategoryID",
                        "JunctionID" => $CategoryID,
                    ]);
                }
                $postTypeModel = self::getPostTypeModel();

                if (isset($formPostValues["allowedPostTypeIDs"])) {
                    $postTypeModel->putPostTypesForCategory($CategoryID, $formPostValues["allowedPostTypeIDs"]);
                }

                if (isset($allowedDiscussionTypes)) {
                    $this->handleLegacyTypes($CategoryID, $allowedDiscussionTypes);
                }
            }

            self::clearCache();
            // Force the user permissions to refresh.
            Gdn::userModel()->clearPermissions();
            $this->guestPermissions = null;

            $this->recalculateTree();

            // Dispatch resource events.
            $this->dispatchInsertUpdateEvent(
                $CategoryID,
                $Insert ? ResourceEvent::ACTION_INSERT : ResourceEvent::ACTION_UPDATE
            );
            if ($Insert && isset($formPostValues["ParentCategoryID"]) && $formPostValues["ParentCategoryID"] > -1) {
                $parentID = $formPostValues["ParentCategoryID"];
                // Counts are updated.
                $this->addDirtyRecord("category", $parentID);
            }

            // Let the world know we succeeded in our mission.
            $this->EventArguments["CategoryID"] = $CategoryID;
            $this->fireEvent("AfterSaveCategory");
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
    public function saveUserTree($categoryID, $set)
    {
        $categories = $this->getSubtree($categoryID);
        foreach ($categories as $category) {
            $this->SQL->replace("UserCategory", $set, [
                "UserID" => Gdn::session()->UserID,
                "CategoryID" => $category["CategoryID"],
            ]);
        }
        $key = "UserCategory_" . Gdn::session()->UserID;
        Gdn::cache()->remove($key);
    }

    /**
     * Grab and update the category cache
     *
     * @since 2.0.18
     * @access public
     *
     * @param int|bool $iD
     * @param array|bool $data
     */
    private static function setCache($iD = false, $data = false)
    {
        self::instance()->collection->refreshCache((int) $iD);
        $refreshedRow = Gdn::sql()
            ->getWhere("Category", ["CategoryID" => $iD])
            ->firstRow(DATASET_TYPE_ARRAY);
        if ($refreshedRow) {
            self::calculate($refreshedRow);
        }

        $categories = Gdn::cache()->get(self::CACHE_KEY);
        self::$Categories = null;

        if (!$categories) {
            return;
        }

        // Extract actual category list, remove key if malformed
        if (!$iD || !is_array($categories) || !array_key_exists("categories", $categories)) {
            Gdn::cache()->remove(self::CACHE_KEY);
            return;
        }
        $categories = $categories["categories"];

        // Check for category in list, otherwise remove key if not found
        if (!array_key_exists($iD, $categories)) {
            Gdn::cache()->remove(self::CACHE_KEY);
            return;
        }

        $category = $categories[$iD];
        $category = array_merge($category, $refreshedRow ?: [], $data);
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
     * @param int $rowID
     * @param array|string $property
     * @param bool|false $value
     * @return array|string
     */
    public function setField($rowID, $property, $value = false)
    {
        if (!is_array($property)) {
            $property = [$property => $value];
        }

        if (isset($property["AllowedDiscussionTypes"]) && is_array($property["AllowedDiscussionTypes"])) {
            $this->handleLegacyTypes($rowID, $property["AllowedDiscussionTypes"]);
            $property["AllowedDiscussionTypes"] = dbencode($property["AllowedDiscussionTypes"]);
        }
        $newFeaturedSort = $this->calcFeaturedSort($rowID, $property);
        if ($newFeaturedSort !== null) {
            $property["SortFeatured"] = $newFeaturedSort;
        }

        $this->SQL->put($this->Name, $property, ["CategoryID" => $rowID]);

        // Set the cache.
        self::refreshCacheDeferred($rowID);
        $this->addDirtyRecord("category", $rowID);

        return $property;
    }

    /**
     * Increment position of the last featured category.
     *
     * @param int|null $categoryID A category ID or slug.
     * @param array $changedCategoryData The modified category fields.
     *
     * @return int|null The new sort value or null if nothing changed.
     */
    public function calcFeaturedSort(?int $categoryID, array $changedCategoryData): ?int
    {
        if ($categoryID === null) {
            return $this->getFeaturedSortIncrement();
        }

        $existingCategory = self::categories($categoryID);
        $newIsFeatured = $changedCategoryData["Featured"] ?? null;
        $existingIsFeatured = $existingCategory["Featured"] ?? null;
        $didFeaturedChange = $existingIsFeatured !== $newIsFeatured;
        if ($newIsFeatured === null || !$didFeaturedChange) {
            // Nothing is changing here, no need to continue.
            return null;
        }

        return $this->getFeaturedSortIncrement();
    }

    /**
     * Get the next-highest featured sort value.
     *
     * @return int
     */
    private function getFeaturedSortIncrement(): int
    {
        // Figure out what the featured count is.
        $lastCategoryFeatured = $this->getWhere(["Featured" => true], "SortFeatured", "desc", 1)->firstRow(
            DATASET_TYPE_ARRAY
        );

        return $lastCategoryFeatured ? $lastCategoryFeatured["SortFeatured"] + 1 : 0;
    }

    /**
     * Set a property of a currently-loaded category in memory.
     *
     * @param int $id
     * @param string $property
     * @param string|int|bool $value
     * @return bool
     */
    public static function setLocalField($id, $property, $value): void
    {
        // Make sure the change will be applied to the collection if it's there.
        // If it isn't there then `toLazySet` will take care of it later.
        // https://github.com/vanilla/support/issues/2923
        $collection = self::instance()->getCollection();
        if ($collection->hasLocal($id)) {
            $c = $collection->get($id);
            $c[$property] = $value;
            self::instance()
                ->getCollection()
                ->setLocal($c);
        }

        if (isset(self::$Categories[$id])) {
            self::$Categories[$id][$property] = $value;
        }
        self::$toLazySet[$id][$property] = $value;
    }

    /**
     * Set the most recent post info for a category, based on itself and all its children.
     *
     * @param int $categoryID
     * @param bool $updateAncestors
     */
    public function refreshAggregateRecentPost($categoryID, $updateAncestors = false)
    {
        $categoryAndDescendantIDs = array_merge([$categoryID], $this->getCategoriesDescendantIDs([$categoryID]));

        $discussion = $this->createSql()
            ->from("Discussion")
            ->select(["CategoryID", "LastCommentID", "DiscussionID", "DateInserted", "DateLastComment"])
            ->where(["CategoryID" => $categoryAndDescendantIDs])
            ->orderBy(["-DateLastComment", "-DiscussionID"])
            ->limit(1)
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);
        $db = static::postDBFields($discussion);
        $this->setField($categoryID, $db);
        static::refreshCacheDeferred($categoryID);

        if ($updateAncestors) {
            // Grab this category's ancestors, pop this category off the end and reverse order for traversal.
            $ancestors = self::instance()->collection->getAncestors($categoryID, true);
            array_pop($ancestors);
            $ancestors = array_reverse($ancestors);
            $lastInserted = empty($db["LastDateInserted"]) ? null : strtotime($db["LastDateInserted"]);
            if (is_array($discussion) && array_key_exists("CategoryID", $discussion)) {
                $lastCategoryID = $discussion["CategoryID"];
            } else {
                $lastCategoryID = false;
            }

            foreach ($ancestors as $row) {
                // If this ancestor already has a newer discussion, stop.
                if (
                    !$lastInserted ||
                    (isset($row["LastDateInserted"]) && $lastInserted < strtotime($row["LastDateInserted"]))
                ) {
                    // Make sure this latest discussion is even valid.
                    $discussionExists =
                        DiscussionModel::instance()->getCount([
                            "DiscussionID" => $row["LastDiscussionID"],
                        ]) > 0;
                    if ($discussionExists) {
                        // If the previous discussion still exists, we trust it.
                        break;
                    } else {
                        // In this particular case the current "LastDiscussion" was deleted. To properly handle this we need to do a full refresh of this category.
                        $this->refreshAggregateRecentPost($row["CategoryID"], false);
                        // Continue so the other ancestors are updated.
                        continue;
                    }
                }
                $currentCategoryID = val("CategoryID", $row);
                self::instance()->setField($currentCategoryID, $db);
                CategoryModel::refreshCacheDeferred($currentCategoryID);

                if ($lastCategoryID) {
                    self::instance()->setField($currentCategoryID, "LastCategoryID", $lastCategoryID);
                }
            }
        }
    }

    /**
     * Build URL to a category page.
     *
     * @param array|object|string|int $category A category object/array, slug, or ID.
     * @param string|int $page The page of the categories.
     * @param bool|string $withDomain What domain type to apply.
     *
     * @return string
     */
    public static function categoryUrl($category, $page = "", $withDomain = true)
    {
        if (function_exists("categoryUrl")) {
            return categoryUrl($category, $page, $withDomain);
        } else {
            return self::createRawCategoryUrl($category, $page, $withDomain);
        }
    }

    /**
     * Do NOT CALL THIS DIRECTLY.
     * It only exists to break an infinite loop between the global categoryUrl and CategoryModel::categoryUrl functions.
     *
     * @param array|object|string|int $category A category object/array, slug, or ID.
     * @param string|int $page The page of the categories.
     * @param bool|string $withDomain What domain type to apply.
     *
     * @internal Don't use unless you are the global categoryUrl function.
     *
     * @return string
     */
    public static function createRawCategoryUrl($category, $page = "", $withDomain = true)
    {
        if (empty($category)) {
            return url("/categories", $withDomain);
        }
        // Custom category url's through events.
        $eventManager = Gdn::eventManager();
        if ($eventManager->hasHandler("customCategoryUrl")) {
            return $eventManager->fireFilter("customCategoryUrl", "", $category, $page, $withDomain);
        }

        if (is_string($category)) {
            $category = self::categories($category);
        }
        $category = (array) $category;

        $result = "/categories/" . rawurlencode($category["UrlCode"]);
        if ($page && $page > 1) {
            $result .= "/p" . $page;
        }
        return url($result, $withDomain);
    }

    /**
     * Get a category field from a category or one of it's parents if it's not present.
     *
     * @param array|object|string|int $category A category object/array, slug, or ID.
     * @param string $field The field to look at.
     * @param mixed $default
     *
     * @return mixed
     */
    public function getCategoryFieldRecursive($category, string $field, $default = null)
    {
        if (is_null($category)) {
            return $default;
        }
        if (is_int($category) || is_string($category)) {
            // If we have an ID or slug, go fetch the category
            $category = CategoryModel::categories($category);
            if (!$category) {
                return $default;
            }
        }

        if (is_object($category)) {
            $category = (array) $category;
        }

        /** @var int[] $seenIDs */
        $seenIDs = [];
        $emptyValues = [null, ""];
        $getCategoryField = function (array $category) use (
            $field,
            $default,
            &$seenIDs,
            $emptyValues,
            &$getCategoryField
        ) {
            $categoryID = $category["CategoryID"];
            if ($categoryID < 1) {
                // We've reached the root of the category tree and didn't find anything.
                return $default;
            }

            $fieldValue = $category[$field] ?? null;
            // Sometimes our DB uses empty strings.
            if (!in_array($fieldValue, $emptyValues, true)) {
                // we have a value.
                return $fieldValue;
            }

            // Maybe we have a parent.
            $parentID = $category["ParentCategoryID"] ?? null;
            if ($parentID === null || $parentID === $categoryID || in_array($parentID, $seenIDs)) {
                // Infinite recursion guard.
                return $default;
            } else {
                $seenIDs[] = $categoryID;
                $parent = CategoryModel::categories($parentID);
                if (!$parent) {
                    return $default;
                }
                return $getCategoryField($parent);
            }
        };

        // Now we have an array category for sure.
        return $getCategoryField($category);
    }

    /**
     * Get the category nav depth.
     *
     * @return int Returns the nav depth as an integer.
     */
    public function getNavDepth()
    {
        return (int) c("Vanilla.Categories.NavDepth", 0);
    }

    /**
     * Get the maximum display depth for categories.
     *
     * @return int Returns the display depth as an integer.
     */
    public function getMaxDisplayDepth()
    {
        return (int) c("Vanilla.Categories.MaxDisplayDepth", 3);
    }

    /**
     * Get category-specific user meta data (e.g. preferences).
     *
     * @param int $userID
     * @param int|null $categoryID
     * @return array
     */
    private function getUserMeta(int $userID, ?int $categoryID = null): array
    {
        $names = $this->getCategoryPreferencesWithReference();
        $userMetaModel = Gdn::userMetaModel();
        $sql = $userMetaModel->createSql()->where("UserID", $userID);
        $sql->beginWhereGroup();
        foreach ($names as $name) {
            if ($categoryID !== null) {
                $sql->orWhere("Name", sprintf($name, $categoryID));
            } else {
                $sql->orLike("Name", str_replace("%d", "%", $name), null);
            }
        }
        $sql->endWhereGroup();
        $result = array_column($sql->get($userMetaModel->Name)->resultArray(), null, "Name");
        return $result;
    }

    /**
     * Get all of a user's category preferences.
     *
     * @param int $userID
     * @return array[]
     */
    public function getPreferences(int $userID): array
    {
        $userMeta = $this->getUserMeta($userID);
        $userCategory = array_column($this->getUserCategories($userID), null, "CategoryID");

        $categoryIDs = array_keys($userCategory);
        $categoryIDs = array_combine($categoryIDs, $categoryIDs);
        foreach ($userMeta as $name => $value) {
            $id = substr(strrchr($name, "."), 1);
            if (empty($id) || ($id = filter_var($id, FILTER_VALIDATE_INT)) === false) {
                continue;
            }
            $categoryIDs[$id] = $id;
        }

        $result = [];
        foreach ($categoryIDs as $categoryID) {
            $category = self::categories($categoryID);
            if (!is_array($category)) {
                continue;
            }

            $preferences = $this->generatePreferences($categoryID, $userMeta, $userCategory[$categoryID] ?? null);

            $result[$categoryID] = [
                "categoryID" => $categoryID,
                "name" => $category["Name"],
                "url" => $category["Url"],
                "preferences" => $preferences,
            ];
        }

        return $result;
    }

    /**
     * Get a user's preferences for a single category.
     *
     * @param int $userID
     * @param int $categoryID
     * @return array
     */
    public function getPreferencesByCategoryID(int $userID, int $categoryID): array
    {
        $userMeta = $this->getUserMeta($userID, $categoryID);
        $userCategories = array_column($this->getUserCategories($userID), null, "CategoryID");
        $userCategory = $userCategories[$categoryID] ?? [];
        $result = $this->generatePreferences($categoryID, $userMeta, $userCategory);
        return $result;
    }

    /***
     * set default category preferences for a user
     *
     * @param int $userID
     * @param bool $existingUser
     * @return void
     */
    public function setDefaultCategoryPreferences(int $userID, bool $existingUser = false): void
    {
        $defaultPreferences = Gdn::config()->get(self::DEFAULT_FOLLOWED_CATEGORIES_KEY, false);
        if (!$defaultPreferences) {
            return;
        }
        $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
        if (!$user || $user["Deleted"] > 0) {
            throw new NotFoundException("User");
        }
        if ($existingUser) {
            $followed = $this->hasFollowed($userID);
            $unFollowed = $this->hasUnfollowed($userID);
            if ($followed || $unFollowed) {
                return;
            }
        }
        try {
            $defaultPreferences = json_decode($defaultPreferences, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($defaultPreferences)) {
                throw new JsonException("Invalid format received");
            }
            //@todo : remove me later past 2023.014 release
            $defaultPreferences = $this->convertOldPreferencesToNew($defaultPreferences);
            foreach ($defaultPreferences as $categoryPreference) {
                $categoryID = $categoryPreference["categoryID"];
                $category = $this->getOne($categoryID);
                if (empty($category) || !self::checkPermission($categoryID, "Vanilla.Discussions.View")) {
                    continue;
                }
                if ($category["DisplayAs"] !== "Discussions") {
                    continue;
                }
                // set the preferences here and it should be all set
                $preferencesToSet = $this->getGenericCategoryPreferenceKeys();
                $categoryPreference = $this->normalizePreferencesInput($categoryPreference["preferences"] ?? []);
                $preferences = [];
                foreach ($preferencesToSet as $pref) {
                    if (isset($categoryPreference[$pref])) {
                        $preferences[$pref] = $categoryPreference[$pref];
                    }
                }

                $this->setPreferences($userID, $categoryID, $preferences, true);
            }
        } catch (JsonException $exception) {
            $this->logger->notice("Invalid format received for the category default configuration.", [
                Vanilla\Logger::FIELD_CHANNEL => Vanilla\Logger::CHANNEL_APPLICATION,
                Vanilla\Logger::FIELD_EVENT => "configuration",
            ]);
        } catch (\Throwable $exception) {
            ErrorLogger::error(
                "Setting Default Category Preference for the user failed",
                ["categoryFollowing"],
                [
                    "exception" => $exception,
                ]
            );
        }
    }

    /**
     * Get a list of the valid Category Preferences.
     *
     * @return string[]
     */
    public function getCategoryPreferences(): array
    {
        $this->preferenceMap = [
            self::OUTPUT_PREFERENCE_FOLLOW => "Preferences.Follow",
            self::OUTPUT_PREFERENCE_DISCUSSION_APP => "Preferences.Popup.NewDiscussion",
            self::OUTPUT_PREFERENCE_DISCUSSION_EMAIL => "Preferences.Email.NewDiscussion",
            self::OUTPUT_PREFERENCE_COMMENT_APP => "Preferences.Popup.NewComment",
            self::OUTPUT_PREFERENCE_COMMENT_EMAIL => "Preferences.Email.NewComment",
        ];
        if (CategoryModel::isDigestEnabled()) {
            $this->preferenceMap[self::OUTPUT_PREFERENCE_DIGEST] = "Preferences.Email.Digest";
        }

        $result = $this->eventManager->fire("categoryModel_getPreferenceMap", $this->preferenceMap);

        if (is_array($result) && !empty($result)) {
            $this->preferenceMap = $result[0];
        }

        return $this->preferenceMap;
    }

    /**
     * Get a list of the valid Category Preferences with the suffix `.%d`.
     *
     * @return string[]
     */
    public function getCategoryPreferencesWithReference(): array
    {
        $preferences = $this->getCategoryPreferences();

        foreach ($preferences as $key => $value) {
            $preferences[$key] = $value . ".%d";
        }

        return $preferences;
    }

    /**
     * Convert preference field names and values for db input.
     *
     * @param array $preferencesToInput
     * @return array
     */
    public function normalizePreferencesInput(array $preferencesToInput): array
    {
        $preferencesMap = $this->getCategoryPreferences();
        $input = [];
        foreach ($preferencesToInput as $apiName => $value) {
            if (isset($preferencesMap[$apiName])) {
                $input[$preferencesMap[$apiName]] = (bool) $value;
            }
        }

        return $input;
    }

    /**
     *  Remove category from the default categories list if they exist in the list
     *
     * @param int $deletedCategoryId
     * @return void
     * @throws JsonException
     */
    public function removeDefaultCategory(int $deletedCategoryId): void
    {
        $defaultPreferences = Gdn::config()->get(self::DEFAULT_FOLLOWED_CATEGORIES_KEY, false);
        // If there are no default preferences, we don't need to do anything.
        if (!$defaultPreferences) {
            return;
        }
        $defaultPreferences = json_decode($defaultPreferences, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($defaultPreferences)) {
            throw new JsonException("Invalid format received");
        }
        $defaultCategories = array_column($defaultPreferences, "categoryID");
        $index = array_search($deletedCategoryId, $defaultCategories);
        if ($index === false) {
            return;
        }

        // Remove the category from the default preferences
        unset($defaultPreferences[$index]);
        $defaultPreferences = array_values($defaultPreferences);

        Gdn::config()->set(self::DEFAULT_FOLLOWED_CATEGORIES_KEY, json_encode($defaultPreferences));
    }

    /**
     * Set a user's preferences for a single category.
     *
     * @param int $userID
     * @param int $categoryID
     * @param array $preferences
     * @param bool $isDefault
     * @return void
     */
    public function setPreferences(int $userID, int $categoryID, array $preferences, bool $isDefault = false): void
    {
        $preferencesToRecord = [];
        $currentPrefs = $this->getPreferencesByCategoryID($userID, $categoryID);
        foreach ($preferences as $pref => $value) {
            if (!isset($currentPrefs[$pref])) {
                throw new InvalidArgumentException("Unknown preference: {$pref}");
                // We'll set the preference if it's different from the current one or if it's the followed preference.
            } elseif ($value != $currentPrefs[$pref]) {
                $preferencesToRecord[$pref . "." . $categoryID] = $value;
            }
        }
        $currentlyFollowed = $this->isFollowed($userID, $categoryID);

        if (!$currentlyFollowed && !isset($preferences[self::stripCategoryPreferenceKey(self::PREFERENCE_FOLLOW)])) {
            throw new ForbiddenException("You must follow a category to set its notification preferences.");
        }

        $followingPref = $preferences[self::stripCategoryPreferenceKey(self::PREFERENCE_FOLLOW)] ?? true;
        $newDigestPref = $preferences[self::stripCategoryPreferenceKey(self::PREFERENCE_DIGEST_EMAIL)] ?? false;
        $existingDigestPref = $currentPrefs[self::stripCategoryPreferenceKey(self::PREFERENCE_DIGEST_EMAIL)] ?? false;

        if ($currentlyFollowed != $followingPref || (!$currentlyFollowed && !$followingPref)) {
            try {
                $this->follow($userID, $categoryID, $followingPref, $newDigestPref);
            } catch (InvalidArgumentException $throwable) {
                throw new \Error("Failed setting users notification preference.", 0, $throwable);
            }

            // If we're unfollowing, wipe out all the preferences.
            if (!$followingPref) {
                foreach (self::CATEGORY_PREFERENCES as $pref => $value) {
                    if ($currentPrefs[self::stripCategoryPreferenceKey($pref)] ?? false) {
                        $preferencesToRecord[sprintf($pref, $categoryID)] = false;
                    }
                }
            }
        } elseif ($newDigestPref != $existingDigestPref) {
            $this->SQL
                ->update(
                    "UserCategory",
                    ["DigestEnabled" => $newDigestPref],
                    ["CategoryID" => $categoryID, "UserID" => $userID]
                )
                ->put();
            $preferencesToRecord[self::stripCategoryPreferenceKey(self::PREFERENCE_DIGEST_EMAIL)] = $newDigestPref;
        }

        $userMetaModel = \Gdn::getContainer()->get(UserMetaModel::class);
        foreach ($preferencesToRecord as $metaName => $metaValue) {
            $userMetaModel->setUserMeta($userID, $metaName, $metaValue);
        }
        self::clearUserCache($userID);
        // update follower count on new inserts and when user unfollows a category
        if (($currentlyFollowed && !$followingPref) || !$currentlyFollowed) {
            $this->updateFollowerCount($categoryID);
        }

        if (!$isDefault) {
            $this->dispatchSubscriptionEvent($userID, $categoryID, $preferencesToRecord);
        }
    }

    /**
     * Strip a category preference key of its placeholder.
     *
     * @param $preferenceKey
     * @return string
     */
    public static function stripCategoryPreferenceKey($preferenceKey): string
    {
        return rtrim($preferenceKey, ".%d");
    }

    /**
     * Dispatch a subscription event for the category
     *
     * @param int $userId
     * @param int $categoryID
     * @param ?array $notificationPreferences
     * @return void
     */
    private function dispatchSubscriptionEvent(int $userId, int $categoryID, ?array $notificationPreferences): void
    {
        $sender = Gdn::userModel()->currentFragment();
        $senderSchema = new UserFragmentSchema();
        $sender = $senderSchema->validate($sender);
        $currentlyFollowed = $this->isFollowed($userId, $categoryID);
        if (!$currentlyFollowed) {
            //user has unsubscribed
            $userUnFollowedCategory = $this->getUnfollowedData($userId, $categoryID);
            $category = $userUnFollowedCategory[$categoryID];
        } else {
            $userFollowedCategories = $this->getFollowed($userId);
            //We don't get a followed category if the user has unfollowed the category
            $category = $userFollowedCategories[$categoryID];
        }
        $category["totalFollowedCount"] = $this->getTotalFollowedCount($categoryID);
        $category["totalDigestCount"] = $this->getDigestEnabledUserCountForCategory($categoryID);
        foreach (self::CATEGORY_PREFERENCES as $preference => $analyticsArray) {
            $value = $notificationPreferences[sprintf($preference, $categoryID)] ?? null;
            if ($value === null) {
                continue;
            }
            $action = $value ? $analyticsArray["eventTrue"] : $analyticsArray["eventFalse"];
            $categorySubscriptionData = [
                "category" => $category,
                "user" => ["userID" => $userId],
                "subscription" => ($value ? t("Enabled") : t("Disabled")) . " " . $analyticsArray["subscription"],
                "type" => $action,
            ];
            $categorySubscriptionChangeEvent = new SubscriptionChangeEvent(
                $action,
                ["subscriptionChange" => $categorySubscriptionData],
                $sender
            );
            $this->eventManager->dispatch($categorySubscriptionChangeEvent);
        }
    }

    /**
     * Get total subscribers for a category
     *
     * @param int $categoryID
     * @return int
     */
    public function getTotalFollowedCount(int $categoryID): int
    {
        $category = $this->getID($categoryID, DATASET_TYPE_ARRAY);
        if (!$category) {
            throw new InvalidArgumentException("Category not found.");
        }
        return $category["CountFollowers"] ?? 0;
    }

    /**
     * Update the follower count for a category.
     *
     * @param int $categoryID
     * @return void
     */
    public function updateFollowerCount(int $categoryID): void
    {
        $count = $this->createSql()->getCount("UserCategory", ["CategoryID" => $categoryID, "Followed" => 1]);
        $this->setField($categoryID, "CountFollowers", $count);
    }

    /**
     * Get data on users unfollowed Category
     *
     * @param int $userID
     * @param int|null $categoryID
     * @return array
     */
    public function getUnfollowedData(int $userID, ?int $categoryID = null): array
    {
        $where = [
            "UserID" => $userID,
            "Unfollow" => 1,
        ];
        if (!empty($categoryID)) {
            $where["CategoryID"] = $categoryID;
        }

        $userData = $this->SQL->getWhere("UserCategory", $where)->resultArray();
        if (empty($userData)) {
            return [];
        }
        return array_column($userData, null, "CategoryID");
    }

    /**
     * Generate user preference for a specific category based on stored preferences
     *
     * @param int $categoryID
     * @param array $userMeta
     * @param array|null $userCategory
     * @return array
     */
    private function generatePreferences(int $categoryID, array $userMeta, ?array $userCategory): array
    {
        $preferences = $this->getCategoryPreferencesWithReference();

        foreach ($preferences as $preference) {
            $categoryPreferences[self::stripCategoryPreferenceKey($preference)] =
                $userMeta[sprintf($preference, $categoryID)]["Value"] ?? false;
        }

        $categoryPreferences[self::stripCategoryPreferenceKey(self::PREFERENCE_FOLLOW)] =
            $userCategory["Followed"] ?? false;

        if (self::isDigestEnabled()) {
            $categoryPreferences[self::stripCategoryPreferenceKey(self::PREFERENCE_DIGEST_EMAIL)] =
                $userCategory["DigestEnabled"] ?? false;
        }

        return array_map("boolval", $categoryPreferences);
    }

    /**
     * Create a schema instance representing a user's category preferences.
     *
     * @return Schema
     */
    public function preferencesSchema(): Schema
    {
        $result = SchemaFactory::parse(
            [
                self::stripCategoryPreferenceKey(self::PREFERENCE_FOLLOW) => ["type" => "boolean"],
                self::stripCategoryPreferenceKey(self::PREFERENCE_DISCUSSION_APP) => ["type" => "boolean"],
                self::stripCategoryPreferenceKey(self::PREFERENCE_DISCUSSION_EMAIL) => ["type" => "boolean"],
                self::stripCategoryPreferenceKey(self::PREFERENCE_COMMENT_APP) => ["type" => "boolean"],
                self::stripCategoryPreferenceKey(self::PREFERENCE_COMMENT_EMAIL) => ["type" => "boolean"],
            ],
            "CategoryPreferences"
        );
        return $result;
    }

    /**
     * Get a category fragment schema with the addition of a user preferences field.
     *
     * @return Schema
     */
    public function fragmentWithPreferencesSchema(): Schema
    {
        $fragmentSchema = $this->fragmentSchema();
        $preferencesSchema = SchemaFactory::parse(
            [
                "preferences" => $this->preferencesSchema(),
            ],
            "CategoryFragmentPreferences"
        );
        $result = $preferencesSchema->merge($fragmentSchema);
        return $result;
    }

    /**
     * Recalculate the dynamic tree columns in the category.
     * @param  array $where
     */
    public function recalculateTree(array $where = []): array
    {
        $px = $this->Database->DatabasePrefix;
        $result = ["Complete" => false];
        $params = [];
        if (in_array(-1, $where["CategoryID"] ?? [])) {
            $p = array_search(-1, $where["CategoryID"]);
            unset($where["CategoryID"][$p]);
            if (empty($where["CategoryID"])) {
                // Nothing left to do. -1 was our only category.
                return ["Complete" => true];
            }
        }
        // closure function evaluate conditions
        $addWhere = function (string $sql, string $condition, string $whereAdd = "where") use ($where, &$params) {
            if (empty($where["CategoryID"])) {
                return $sql;
            }
            $params = array_merge($params, $where["CategoryID"]);
            return $sql .
                " $whereAdd " .
                "{$condition} IN (" .
                implode(",", array_fill(0, count($where["CategoryID"]), "?")) .
                ")";
        };

        // Update the child counts and reset the depth.
        $parentQuery =
            $addWhere(
                "select ParentCategoryID, count(ParentCategoryID) as CountCategories
                    from {$px}Category",
                "ParentCategoryID"
            ) . " group by ParentCategoryID";

        $sql = <<<SQL
            update {$px}Category c
            join ({$parentQuery}) c2
                on c.CategoryID = c2.ParentCategoryID
            set c.CountCategories = c2.CountCategories,
                c.Depth = 0
SQL;
        $this->Database->query($sql, $params);

        // Update the first pass of the categories.
        $updateSql = <<<SQL
update {$px}Category p
join {$px}Category c
	on c.ParentCategoryID = p.CategoryID
set c.Depth = p.Depth + 1
where p.CategoryID = -1 and c.CategoryID <> -1
SQL;
        $params = [];

        $this->Database->query($addWhere($updateSql, "c.CategoryID", "and"), $params);

        // Update the child categories depth-by-depth.
        $sql = <<<SQL
update {$px}Category p
join {$px}Category c
	on c.ParentCategoryID = p.CategoryID
set c.Depth = p.Depth + 1
where p.Depth = ?
SQL;
        $updatedCounts = false;

        for ($i = 1; $i < 25; $i++) {
            $params = [$i];
            $this->Database->query($addWhere($sql, "c.ParentCategoryID", "and"), $params);

            if (val("RowCount", $this->Database->LastInfo) == 0) {
                break;
            } else {
                $updatedCounts = true;
            }
        }

        if ($updatedCounts) {
            $this->collection->flushCache();
            self::clearCache();
        }
        $result["Complete"] = true;
        return $result;
    }

    /**
     * Return a flattened version of a tree.
     *
     * @param array $categories The category tree.
     * @return array Returns the flattened category tree.
     */
    public static function flattenTree($categories)
    {
        return self::instance()->collection->flattenTree($categories);
    }

    /**
     * Adjust the aggregate post counts for a category, using the provided offset to increment or decrement the value.
     *
     * @param int $categoryID
     * @param string $type
     * @param int $offset A value, positive or negative, to offset a category's current aggregate post counts.
     * @param bool $cache This param was implemented just for particular patch
     *        check details https://github.com/vanilla/vanilla/issues/7105
     *        and https://github.com/vanilla/vanilla/pull/7843
     *        please avoid of using it.
     */
    private static function adjustAggregateCounts($categoryID, $type, $offset, bool $cache = true)
    {
        $offset = intval($offset);

        if (empty($categoryID)) {
            return;
        }

        // Iterate through the category and its ancestors, adjusting aggregate counts based on $offset.
        $updatedCategories = [];
        if ($categoryID) {
            $categories = self::instance()->collection->getAncestors($categoryID, true);

            if (empty($categories)) {
                return;
            }

            foreach ($categories as $current) {
                $targetID = $current["CategoryID"] ?? false;
                $updatedCategories[] = $targetID;

                Gdn::sql()->update("Category");
                switch ($type) {
                    case self::AGGREGATE_COMMENT:
                        Gdn::sql()->set("CountAllComments", "CountAllComments + {$offset}", false);
                        break;
                    case self::AGGREGATE_DISCUSSION:
                        Gdn::sql()->set("CountAllDiscussions", "CountAllDiscussions + {$offset}", false);
                        break;
                }
                Gdn::sql()
                    ->where("CategoryID", $targetID)
                    ->put();
            }
        }

        // Update the cache.
        if ($cache) {
            foreach ($updatedCategories as $categoryID) {
                self::refreshCacheDeferred($categoryID);
            }
        }
    }

    /**
     * Move upward through the category tree, incrementing aggregate post counts.
     *
     * @param int $categoryID A valid category ID.
     * @param string $type One of the CategoryModel::AGGREGATE_* constants.
     * @param int $offset The value to increment the aggregate counts by.
     * @param bool $cache This param was implemented just for particular patch
     *        check details https://github.com/vanilla/vanilla/issues/7105
     *        and https://github.com/vanilla/vanilla/pull/7843
     *        please avoid of using it.
     */
    public static function incrementAggregateCount($categoryID, $type, $offset = 1, bool $cache = true)
    {
        if ($offset === 0) {
            return;
        }
        // Make sure we're dealing with a positive offset.
        $offset = abs($offset);
        self::adjustAggregateCounts($categoryID, $type, $offset, $cache);
    }

    /**
     * Move upward through the category tree, decrementing aggregate post counts.
     *
     * @param int $categoryID A valid category ID.
     * @param string $type One of the CategoryModel::AGGREGATE_* constants.
     * @param int $offset The value to increment the aggregate counts by.
     * @param bool $cache This param was implemented just for particular patch
     *        check details https://github.com/vanilla/vanilla/issues/7105
     *        and https://github.com/vanilla/vanilla/pull/7843
     *        please avoid of using it.
     */
    public static function decrementAggregateCount($categoryID, $type, $offset = 1, bool $cache = true)
    {
        if ($offset === 0) {
            return;
        }
        // Make sure we're dealing with a negative offset.
        $offset = -1 * abs($offset);
        self::adjustAggregateCounts($categoryID, $type, $offset, $cache);
    }

    /**
     * @return Generator
     */
    public function recalculateAllCountAlls(): Generator
    {
        yield new LongRunnerQuantityTotal(function () {
            return 1;
        });
        self::recalculateAggregateCounts();
        return LongRunner::FINISHED;
    }

    /**
     * Recalculate all aggregate post count columns for all categories.
     *
     * @return void
     * @throws Gdn_UserException
     */
    private static function recalculateAggregateCounts()
    {
        // First grab the max depth so you know where to loop.
        $depth = Gdn::sql()
            ->select("Depth", "max")
            ->from("Category")
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);
        $depth = (int) val("Depth", $depth, 0);

        if ($depth === 0) {
            return;
        }

        $prefix = Gdn::database()->DatabasePrefix;

        // Clear everything out

        Gdn::sql()
            ->update("Category", [
                "CountAllDiscussions" => new RawExpression("CountDiscussions"),
                "CountAllComments" => new RawExpression("CountComments"),
            ])
            ->put();

        while ($depth > 0) {
            $parentQuery = "select
                            c2.ParentCategoryID,
                            sum(CountAllDiscussions) as CountAllDiscussions,
                            sum(CountAllComments) as CountAllComments
                        from {$prefix}Category c2
                        where c2.Depth = :Depth";
            $params = [":Depth" => $depth];
            $parentQuery .= " group by c2.ParentCategoryID";

            $sql = "update {$prefix}Category c
                    join ({$parentQuery}) c2
                    on c.CategoryID = c2.ParentCategoryID
                set
                    c.CountAllDiscussions = c.CountDiscussions + c2.CountAllDiscussions,
                    c.CountAllComments = c.CountComments + c2.CountAllComments
                where c.Depth = :ParentDepth";
            $params = array_merge($params, [":ParentDepth" => $depth - 1]);

            Gdn::database()->query($sql, $params);
            $depth--;
        }

        self::instance()->clearCache();
    }

    /**
     * Search for categories by name.
     *
     * @param string $name The whole or partial category name to search for.
     * @param int|null $parentCategoryID Parent categoryID to filter by.
     * @param array $where Where expression.
     * @param bool $expandParent Expand the parent category record.
     * @param int|null $limit Limit the total number of results.
     * @param int|null $offset Offset the results.
     * @return array
     * @throws Exception
     */
    public function searchByName(
        string $name,
        ?int $parentCategoryID,
        array $where = [],
        bool $expandParent = false,
        ?int $limit = null,
        ?int $offset = null,
        bool $filterDiscussionsAdd = false
    ) {
        if ($limit !== null && filter_var($limit, FILTER_VALIDATE_INT) === false) {
            $limit = null;
        }
        if ($offset !== null && filter_var($offset, FILTER_VALIDATE_INT) === false) {
            $offset = null;
        }

        $searchableIDs = $this->getSearchCategoryIDs(
            $parentCategoryID,
            null,
            true,
            true,
            filterDiscussionsAdd: $filterDiscussionsAdd
        );
        $where["CategoryID"] = $searchableIDs;

        $query = $this->SQL
            ->from("Category c")
            ->where("CategoryID >", 0)
            ->where($where)
            ->like("Name", $name)
            ->orderBy("Name");

        if ($limit !== null) {
            $offset = $offset === null ? false : $offset;
            $query->limit($limit, $offset);
        }

        $categories = $query->get()->resultArray();

        $result = [];
        foreach ($categories as $category) {
            self::calculate($category);
            if ($category["DisplayAs"] === self::DISPLAY_HEADING) {
                continue;
            }

            self::calculateUser($category);

            if ($expandParent) {
                if ($category["ParentCategoryID"] > 0) {
                    $parent = static::categories($category["ParentCategoryID"]);
                    self::calculate($category);
                    $category["Parent"] = $parent;
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
    public static function setAsLastCategory($categoryID)
    {
        $categories = self::instance()->collection->getAncestors($categoryID, true);

        foreach ($categories as $current) {
            $targetID = val("CategoryID", $current);
            self::instance()->setField($targetID, ["LastCategoryID" => $categoryID]);
        }
    }

    /**
     * Get the schema for categories joined to records.
     *
     * @return Schema Returns a schema.
     */
    public function fragmentSchema(): Schema
    {
        $result = SchemaFactory::get(CategoryFragmentSchema::class);
        return $result;
    }

    /**
     * Get a category fragment by its ID.
     *
     * @param int|null $categoryID
     *
     * @return array|null
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function getFragmentByID(?int $categoryID): ?array
    {
        if ($categoryID === null) {
            return null;
        }
        $category = CategoryModel::categories($categoryID);
        if (empty($category)) {
            return null;
        }
        $normalized = $this->normalizeRow($category);
        return ArrayUtils::pluck($normalized, CategoryFragmentSchema::fieldNames());
    }

    /**
     * Sorts child categories alphabetically if the parent display type is 'Flat'
     * @param array $categories
     */
    public static function sortFlatCategories(array &$categories): void
    {
        $categories = array_column($categories, null, "CategoryID");

        uasort($categories, function ($a, $b) use ($categories) {
            if (
                $a["ParentCategoryID"] !== $b["ParentCategoryID"] ||
                ($categories[$a["ParentCategoryID"]]["DisplayAs"] ?? null) !== self::DISPLAY_FLAT
            ) {
                return $a["TreeLeft"] <=> $b["TreeLeft"];
            } else {
                return strcasecmp($a["Name"], $b["Name"]);
            }
        });
    }

    /**
     * @inheritdoc
     */
    public function getCrawlInfo(): array
    {
        $r = \Vanilla\Models\LegacyModelUtils::getCrawlInfoFromPrimaryKey(
            $this,
            "/api/v2/categories?sort=-categoryID&expand=crawl",
            "categoryID"
        );
        $r["min"] = max($r["min"], 1); // kludge around root category
        return $r;
    }

    /**
     * Output schema.
     *
     * @return Schema
     */
    public function schema(): Schema
    {
        if (!$this->schemaInstance) {
            $this->schemaInstance = Schema::parse([
                "categoryID:i" => "The ID of the category.",
                "name:s" => [
                    "description" => "The name of the category.",
                    "x-localize" => true,
                ],
                "description:s|n" => [
                    "description" => "The description of the category.",
                    "minLength" => 0,
                    "x-localize" => true,
                ],
                "parentCategoryID:i|n" => "Parent category ID.",
                "customPermissions:b" => "Are custom permissions set for this category?",
                "isArchived:b" => "The archived state of this category.",
                "urlcode:s" => "The URL code of the category.",
                "url:s" => "The URL to the category.",
                "displayAs:s" => [
                    "description" => "The display style of the category.",
                    "enum" => ["categories", "discussions", "flat", "heading"],
                    "default" => "discussions",
                ],
                "iconUrl:s|n?",
                "iconUrlSrcSet?" => new InstanceValidatorSchema(ImageSrcSet::class),
                "dateInserted:dt?",
                "dateUpdated:dt|n?",
                "bannerUrl:s|n?",
                "bannerUrlSrcSet?" => new InstanceValidatorSchema(ImageSrcSet::class),
                "countCategories:i" => "Total number of child categories.",
                "countDiscussions:i" => "Total discussions in the category.",
                "countComments:i" => "Total comments in the category.",
                "countAllDiscussions:i" => "Total of all discussions in a category and its children.",
                "countAllComments:i" => "Total of all comments in a category and its children.",
                "countFollowers:i" => "Total followers in the category.",
                "followed:b?" => [
                    "default" => false,
                    "description" => "Is the category being followed by the current user?",
                ],
                "dateFollowed:dt?" => "Date time the user started following the category",
                "breadcrumbs:a?" => new InstanceValidatorSchema(Breadcrumb::class),
                "featured:b?" => "Featured category.",
                "allowedDiscussionTypes:a",
                "permissions:o?",
                "insertUserID:i",
                "updateUserID:i|n?",
            ]);

            if (PostTypeModel::isPostTypesFeatureEnabled()) {
                $this->schemaInstance->merge(
                    Schema::parse(["allowedPostTypeIDs:a?", "allowedPostTypeOptions:a?", "hasRestrictedPostTypes:b?"])
                );
            }
        }
        return $this->schemaInstance;
    }

    /**
     * Dispatch an insert/update event for a particular categoryID.
     *
     * @param int $categoryID
     * @param string $type One of the resource event types.
     * @throws ContainerException
     * @throws ValidationException
     * @throws \Garden\Container\NotFoundException
     */
    private function dispatchInsertUpdateEvent(int $categoryID, string $type)
    {
        // Dispatch resource events.
        $category = self::categories($categoryID);
        if ($category) {
            $this->eventManager->dispatch($this->eventFromRow($category, $type, Gdn::userModel()->currentFragment()));
        }
    }

    /**
     * Generate a comment event object, based on a database row.
     *
     * @param array $row
     * @param string $action
     * @param null $sender
     *
     * @return ResourceEvent
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws ValidationException
     */
    public function eventFromRow(array $row, string $action, $sender = null): ResourceEvent
    {
        Gdn::userModel()->expandUsers($row, ["InsertUserID"]);
        $category = $this->normalizeRow($row);
        $category = $this->schema()->validate($category);

        if ($sender) {
            $senderSchema = new UserFragmentSchema();
            $sender = $senderSchema->validate($sender);
        }

        $result = new CategoryEvent($action, ["category" => $category], $sender);
        return $result;
    }

    /**
     * Reset all local variables used for internal caching.
     */
    public static function reset(): void
    {
        self::$Categories = null;
        self::$isClearScheduled = false;
        self::$stopHeadingsCalculation = false;
        self::$toLazySet = [];
        self::instance()
            ->getCollection()
            ->reset();
    }

    /**
     * Permanently remove a category.
     *
     * @param int $categoryID
     * @param bool $rebuildTree
     * @throws ContainerException
     * @throws JsonException
     * @throws ValidationException
     * @throws \Garden\Container\NotFoundException
     */
    private function deleteInternal(int $categoryID, bool $rebuildTree): void
    {
        /** @var LayoutViewModel $layoutViewModel */
        $layoutViewModel = Gdn::getContainer()->get(LayoutViewModel::class);

        $eventCategory = self::categories($categoryID);
        $deleteEvent = $this->eventFromRow(
            $eventCategory,
            ResourceEvent::ACTION_DELETE,
            Gdn::userModel()->currentFragment()
        );

        // Delete the category
        $this->SQL->delete("Category", ["CategoryID" => $categoryID]);
        $this->eventManager->dispatch($deleteEvent);

        if ($rebuildTree) {
            $this->rebuildTree();
            $this->recalculateTree();
        }

        // We delete layoutViews associated with the deleted category.
        $layoutViewModel->delete(["recordType" => "category", "recordID" => $categoryID]);

        // Remove the category from the default preferences if it's there
        $this->removeDefaultCategory($categoryID);

        // Let the world know we completed our mission.
        $this->EventArguments["CategoryID"] = $categoryID;
        $this->fireEvent("AfterDeleteCategory");
    }

    /**
     * Cleanup associated records in preparation for deleting a category.
     *
     * @param int $categoryID
     */
    private function prepareForDelete(int $categoryID, bool $deleteContent = true): void
    {
        $this->deletePermissions($categoryID);

        // Delete discussions in this category
        $this->SQL->delete("Discussion", ["CategoryID" => $categoryID]);

        if ($deleteContent) {
            // Delete comments in this category
            $this->SQL
                ->from("Comment c")
                ->join("Discussion d", "c.DiscussionID = d.DiscussionID")
                ->where("d.CategoryID", $categoryID)
                ->delete();

            // Make inherited permission local permission
            $this->SQL
                ->update("Category")
                ->set("PermissionCategoryID", 0)
                ->where("PermissionCategoryID", $categoryID)
                ->where("CategoryID <>", $categoryID)
                ->put();

            // Delete tags
            $this->SQL->delete("Tag", ["CategoryID" => $categoryID]);
            $this->SQL->delete("TagDiscussion", ["CategoryID" => $categoryID]);
        }
    }

    /**
     * Update references to one category with another.
     *
     * @param int $categoryID
     * @param int $newCategoryID
     * @param bool $updateCounts
     */
    private function replaceCategory(int $categoryID, int $newCategoryID, bool $updateCounts): void
    {
        $this->deletePermissions($categoryID);

        // Update children categories
        $this->SQL
            ->update("Category")
            ->set("ParentCategoryID", $newCategoryID)
            ->where("ParentCategoryID", $categoryID)
            ->put();

        // Update permission categories.
        $this->SQL
            ->update("Category")
            ->set("PermissionCategoryID", $newCategoryID)
            ->where("PermissionCategoryID", $categoryID)
            ->where("CategoryID <>", $categoryID)
            ->put();

        // Update discussions
        $this->SQL
            ->update("Discussion")
            ->set("CategoryID", $newCategoryID)
            ->where("CategoryID", $categoryID)
            ->put();

        // Update tags
        $this->SQL
            ->update("Tag")
            ->set("CategoryID", $newCategoryID)
            ->where("CategoryID", $categoryID)
            ->put();

        $this->SQL
            ->update("TagDiscussion")
            ->set("CategoryID", $newCategoryID)
            ->where("CategoryID", $categoryID)
            ->put();

        if ($updateCounts) {
            // Update the discussion count
            $count = $this->SQL
                ->select("DiscussionID", "count", "DiscussionCount")
                ->from("Discussion")
                ->where("CategoryID", $newCategoryID)
                ->get()
                ->firstRow()->DiscussionCount;

            if (!is_numeric($count)) {
                $count = 0;
            }

            $this->SQL
                ->update("Category")
                ->set("CountDiscussions", $count)
                ->where("CategoryID", $newCategoryID)
                ->put();
        }
    }

    /**
     * Delete permission entries associated with a particular category.
     *
     * @param int $categoryID
     */
    private function deletePermissions(int $categoryID): void
    {
        // Remove permissions related to category
        $permissionModel = Gdn::permissionModel();
        $permissionModel->delete(null, "Category", "CategoryID", $categoryID);
    }

    /**
     * SetDeferredCache
     *
     * @param int $id
     */
    private static function refreshCacheDeferred(int $id): void
    {
        self::$deferredCache[$id] = true;

        if (self::$deferredCacheScheduled !== true) {
            // Remember this will run immediately when testing
            self::$deferredCacheScheduled = true;
            Gdn::getScheduler()->addJobDescriptor(
                new NormalJobDescriptor(CallbackJob::class, [
                    "callback" => function () {
                        while (count(self::$deferredCache) > 0) {
                            $id = key(self::$deferredCache);
                            try {
                                // Empty value here
                                self::setCache($id, []);
                                unset(self::$deferredCache[$id]);
                            } catch (Throwable $t) {
                                CategoryModel::instance()->logger->error(@"Error Clearing Category $id Cache", [
                                    Logger::FIELD_EVENT => "Cache",
                                    Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                                    "errorMessage" => $t->getMessage(),
                                    "errorTrace" => DebugUtils::stackTraceString($t->getTrace()),
                                ]);
                                self::$deferredCache = [];
                                CategoryModel::clearCache();
                                throw $t;
                            }
                        }
                        self::$deferredCacheScheduled = false;
                    },
                ])
            );
        }
    }

    /**
     * Wraps the self::permissionCategory method to get the AllowFileUploads setting.
     * It will either be from the passed in $category or from the root category if
     * custom category permissions are turned off.
     *
     * @param mixed $category
     * @return bool
     */
    public static function checkAllowFileUploads($category): bool
    {
        $permissionCategory = self::permissionCategory($category);

        return (bool) ($permissionCategory["AllowFileUploads"] ?? true);
    }

    /**
     * Calculate Category Layout View type based on category displayAs setting.
     *
     * @param int $categoryID
     * @return string
     */
    public function calculateCategoryLayoutViewType(int $categoryID): string
    {
        if ($categoryID === CategoryModel::ROOT_ID) {
            return CategoryModel::LAYOUT_CATEGORY_LIST;
        }

        $category = $this->getID($categoryID, DATASET_TYPE_ARRAY);
        if (!$category) {
            throw new NotFoundException("Category");
        }

        switch ($category["DisplayAs"]) {
            case CategoryModel::DISPLAY_DISCUSSIONS:
                return CategoryModel::LAYOUT_DISCUSSION_CATEGORY_PAGE;
            case CategoryModel::DISPLAY_FLAT:
            case CategoryModel::DISPLAY_NESTED:
                return CategoryModel::LAYOUT_NESTED_CATEGORY_LIST;
            case CategoryModel::DISPLAY_HEADING:
                throw new ClientException(t("Heading categories cannot be viewed directly."), 400);
            default:
                return CategoryModel::LAYOUT_CATEGORY_LIST;
        }
    }

    /**
     * Convert preference field names and values for api output.
     *
     * @param array $preferencesToOutput
     * @return array
     */
    public function normalizePreferencesOutput(array $preferencesToOutput): array
    {
        $preferencesMap = array_flip($this->getCategoryPreferences());
        $output = [];
        foreach ($preferencesMap as $dbName => $apiName) {
            $output[$apiName] = $preferencesToOutput[$dbName] ?? false;
        }

        return $output;
    }

    /**
     * Schema validator to validate that array contains valid category IDs.
     *
     * @return callable
     */
    public static function createCategoryIDsValidator(): callable
    {
        return function (array $categoryIDs, ValidationField $field) {
            foreach ($categoryIDs as $categoryID) {
                if (!CategoryModel::categories($categoryID)) {
                    $field->addError("Invalid Category", [
                        "code" => 403,
                        "messageCode" => "The category {$categoryID} is not a valid category.",
                    ]);

                    return Invalid::value();
                }
            }
            return true;
        };
    }

    /**
     * Check if post type is allowed for a category.
     *
     * @param array $category
     * @param string $type
     * @return bool
     * @throws Exception
     */
    public function isPostTypeAllowed(array $category, string $type, bool $exclusive = false): bool
    {
        if (PostTypeModel::isPostTypesFeatureEnabled()) {
            $allowedPostTypes = self::getPostTypeModel()->getAllowedPostTypesByCategory($category);
            return in_array($type, array_column($allowedPostTypes, "postTypeID"));
        }

        $allowedTypes = $category["AllowedDiscussionTypes"] ?? [];

        $allowedTypes = array_map("strtolower", $allowedTypes);
        $type = strtolower($type);

        if (empty($allowedTypes) && !$exclusive) {
            return true;
        }
        return in_array($type, $allowedTypes);
    }

    /**
     * Handles converting legacy discussion types to post type IDs.
     *
     * @param int $categoryID
     * @param array $allowedDiscussionTypes Legacy types.
     * @return void
     * @throws Throwable
     */
    private function handleLegacyTypes(int $categoryID, array $allowedDiscussionTypes)
    {
        // Convert from legacy discussion types to post type IDs.
        $convertedPostTypeIDs = PostTypeModel::convertFromLegacyTypes($allowedDiscussionTypes);

        // For legacy types we need to remove the **base** post types that were not selected.
        $unselectedPostTypeIDs = array_diff(array_values(PostTypeModel::LEGACY_TYPE_MAP), $convertedPostTypeIDs);

        self::getPostTypeModel()->putPostTypesForCategory(
            $categoryID,
            $convertedPostTypeIDs,
            addOnly: true,
            removePostTypeIDs: $unselectedPostTypeIDs
        );
    }

    /**
     * Get ancestor category IDs for the given category IDs.
     *
     * @param int[] $categoryIDs
     * @return int[] CategoryIDs.
     */
    public function getCategoriesAncestorIDs(array $categoryIDs, bool $inclusive = true): array
    {
        $allAncestorIDs = [];

        foreach ($categoryIDs as $categoryID) {
            $ancestors = $this->getAncestors($categoryID, false, true);
            $ancestorIDs = array_column($ancestors, "CategoryID");
            $allAncestorIDs = array_merge($allAncestorIDs, $ancestorIDs);
        }

        if ($inclusive) {
            $allAncestorIDs = array_merge($allAncestorIDs, $categoryIDs);
        }

        return array_unique($allAncestorIDs);
    }
}
