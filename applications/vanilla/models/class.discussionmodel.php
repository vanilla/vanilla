<?php
/**
 * Discussion model
 *
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0
 */

use Garden\Container\ContainerException;
use Garden\Events\BulkUpdateEvent;
use Garden\Events\ResourceEvent;
use Garden\Events\EventFromRowInterface;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Attributes;
use Vanilla\Community\Events\DiscussionEvent;
use Vanilla\Community\Events\DiscussionQueryEvent;
use Vanilla\Community\Events\DiscussionStatusEvent;
use Vanilla\Community\Events\MachineTranslationEvent;
use Vanilla\Community\Schemas\PostFragmentSchema;
use Vanilla\Contracts\Formatting\FormatFieldInterface;
use Vanilla\Contracts\Models\CrawlableInterface;
use Vanilla\Contracts\Models\FragmentFetcherInterface;
use Vanilla\Contracts\Models\VectorizeInterface;
use Vanilla\Dashboard\AiSuggestionModel;
use Vanilla\Dashboard\DocumentModel;
use Vanilla\Dashboard\Models\AggregateCountableInterface;
use Vanilla\Dashboard\Models\AiSuggestionSourceService;
use Vanilla\Dashboard\Models\PremoderationModel;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\Dashboard\Models\UserMentionsInterface;
use Vanilla\Dashboard\Models\UserMentionsModel;
use Vanilla\Database\SetLiterals\RawExpression;
use Vanilla\Events\DiscussionPermissionQueryEvent;
use Vanilla\Events\LegacyDirtyRecordTrait;
use Vanilla\Exception\PermissionException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Formatting\DateTimeFormatter;
use Vanilla\Formatting\Exception\FormatterNotFoundException;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\FormatFieldTrait;
use Vanilla\Formatting\UpdateMediaTrait;
use Vanilla\Forum\Jobs\DeferredResourceEventJob;
use Vanilla\Forum\Models\ForumAggregateModel;
use Vanilla\Forum\Models\PostMetaModel;
use Vanilla\Forum\Models\PostTypeModel;
use Vanilla\ImageSrcSet\ImageSrcSetService;
use Vanilla\ImageSrcSet\MainImageSchema;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Logger;
use Vanilla\Permissions;
use Vanilla\Premoderation\PremoderationException;
use Vanilla\Premoderation\PremoderationItem;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerItemResultInterface;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Models\LegacyModelUtils;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Schema\RangeExpression;
use Vanilla\SchemaFactory;
use Vanilla\Search\SearchService;
use Vanilla\Search\SearchTypeQueryExtenderInterface;
use Vanilla\Site\OwnSite;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\DebugUtils;
use Vanilla\Utility\Deprecation;
use Vanilla\Utility\InstanceValidatorSchema;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\SystemCallableInterface;
use Vanilla\Formatting\Formats\TextFormat;

/**
 * Manages discussions data.
 */
class DiscussionModel extends Gdn_Model implements
    LoggerAwareInterface,
    FormatFieldInterface,
    EventFromRowInterface,
    CrawlableInterface,
    FragmentFetcherInterface,
    SystemCallableInterface,
    UserMentionsInterface,
    AggregateCountableInterface,
    VectorizeInterface
{
    use StaticInitializer;

    use \Vanilla\FloodControlTrait;

    use FormatFieldTrait;

    use UpdateMediaTrait;

    use LegacyDirtyRecordTrait;

    use LoggerAwareTrait;

    /** Cache key. */
    const CACHE_DISCUSSIONVIEWS = "discussion.%s.countviews";

    /** @var string Thus userID of whomever closed the discussion. */
    const CLOSED_BY_USER_ID = "ClosedByUserID";

    /** @var string Default column to order by. */
    const DEFAULT_ORDER_BY_FIELD = "DateLastComment";

    /** @var string The filter key for clearing-type filters. */
    const EMPTY_FILTER_KEY = "none";

    /** Max comments on a discussion before it cannot be auto-deleted by SPAM or moderation actions. */
    const DELETE_COMMENT_THRESHOLD = 10;

    /** @var int The maximum length */
    const MAX_POST_LENGTH = 50000;

    /** @var string for type redirect */
    const REDIRECT_TYPE = "redirect";

    /** @var string for type stub */
    const STUB_TYPE = "stub";

    /** @var string for type discussion */
    const DISCUSSION_TYPE = "Discussion";

    /** @var string announced discussion */
    const ANNOUNCEMENT_LABEL = "Announcement";

    /** @var string closed discussion */
    const CLOSED_LABEL = "Closed";

    /** @var string record type */
    public const RECORD_TYPE = "discussion";

    /** @var int */
    public const DEFAULT_STATUS_ID = 0;

    /** @var int */
    public const MAX_SNIPPET_LENGTH = 180;

    public const SORT_EXPIRIMENTAL_TRENDING = ModelUtils::SORT_TRENDING;

    /**
     * Each sort in the array has the following properties:
     * - **key**: string - The key name of the sort. Appears in the query string, should be url-friendly.
     * - **name**: string - The display name of the sort.
     * - **orderBy**: string - An array indicating order by fields and their directions in the format:
     *   `['field1' => 'direction', 'field2' => 'direction']`
     */
    public const DEFAULT_ALLOWED_SORTS = [
        "hot" => ["key" => "hot", "name" => "Hot", "orderBy" => ["DateLastComment" => "desc"]],
        "top" => ["key" => "top", "name" => "Top", "orderBy" => ["Score" => "desc", "DateInserted" => "desc"]],
        "new" => ["key" => "new", "name" => "New", "orderBy" => ["DateInserted" => "desc"]],
    ];

    /** @var CategoryModel */
    private $categoryModel;

    /** @var array|bool */
    private static $categoryPermissions = null;

    /** @var array */
    private static $discussionTypes = null;

    /** @var array */
    public static $dicussionPostType = null;

    // Maximum number of seconds a batch of deletes should last before a new batch needs to be scheduled.
    public const MAX_TIME_BATCH = 10;

    // Maximum number of discussions to delete.
    private const MAX_DELETE_LIMIT = 30;

    // Maximum number of discussions to move.
    private const MAX_MOVE_LIMIT = 30;

    // Use to continue a long-running action to delete a discussion with the same transaction ID.
    private const OPT_DELETE_SINGLE_TRANSACTION_ID = "deleteSingleTransactionID";

    public const GLOBAL_ANNOUNCEMENT = 1;

    public const CATEGORY_ANNOUNCEMENT = 2;

    public const ANNOUNCEMENT = [self::GLOBAL_ANNOUNCEMENT, self::CATEGORY_ANNOUNCEMENT];

    /**
     * @deprecated 2.6
     * @var bool
     */
    public $Watching = false;

    /** @var array Discussion Permissions */
    private $permissionTypes = ["Add", "Announce", "Close", "Delete", "Edit", "Sink", "View"];

    /**
     * @var array The sorts that are accessible via GET. Each sort corresponds with an order by clause.
     */
    protected static $allowedSorts = self::DEFAULT_ALLOWED_SORTS;

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
     * @var DiscussionModel $instance ;
     */
    private static $instance;

    /**
     * @var string The sort key of the order by we apply in the query.
     */
    protected $sort = "";

    /**
     * @var array The filter keys of the wheres we apply in the query.
     */
    protected $filters = [];

    /**
     * @var \Psr\SimpleCache\CacheInterface Object used to store the FloodControl data.
     */
    protected $floodGate;

    /**
     * @var DateTimeInterface
     */
    private $archiveDate;

    /** @var UserModel */
    private $userModel;

    /** @var TagModel */
    private $tagModel;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var OwnSite */
    private $ownSite;

    /** @var array */
    private $options;

    /** @var ImageSrcSetService */
    private $imageSrcSetService;

    /** @var UserMentionsModel */
    private $userMentionsModel;

    /** @var DiscussionStatusModel */
    private $discussionStatusModel;

    private AiSuggestionModel $aiSuggestionModel;

    private ReactionModel $reactionModel;

    private PostMetaModel $postMetaModel;

    private PostTypeModel $postTypeModel;

    /** @var DocumentModel */
    private DocumentModel $documentModel;

    /**
     * Clear out the statically cached values for tests.
     */
    public static function cleanForTests()
    {
        self::$instance = null;
        self::$discussionTypes = null;
        self::$categoryPermissions = null;
        self::$initStaticFired = false;
        self::$allowedSorts = self::DEFAULT_ALLOWED_SORTS;
    }

    /**
     * Class constructor. Defines the related database table name.
     *
     * @param Gdn_Validation $validation The validation dependency.
     */
    public function __construct(Gdn_Validation $validation = null)
    {
        parent::__construct("Discussion", $validation);
        $this->imageSrcSetService = Gdn::getContainer()->get(ImageSrcSetService::class);
        $this->floodGate = FloodControlHelper::configure($this, "Vanilla", "Discussion");

        Gdn::getContainer()->call(function (
            UserModel $userModel,
            CategoryModel $categoryModel,
            TagModel $tagModel,
            SiteSectionModel $siteSectionModel,
            UserMentionsModel $userMentionsModel,
            DiscussionStatusModel $discussionStatusModel,
            AiSuggestionModel $aiSuggestionModel,
            PostMetaModel $postMetaModel,
            PostTypeModel $postTypeModel,
            DocumentModel $documentModel
        ) {
            $this->categoryModel = $categoryModel;
            $this->userModel = $userModel;
            $this->tagModel = $tagModel;
            $this->siteSectionModel = $siteSectionModel;
            $this->userMentionsModel = $userMentionsModel;
            $this->discussionStatusModel = $discussionStatusModel;
            $this->aiSuggestionModel = $aiSuggestionModel;
            $this->postMetaModel = $postMetaModel;
            $this->postTypeModel = $postTypeModel;
            $this->documentModel = $documentModel;
        });
        $this->setFormatterService(Gdn::getContainer()->get(FormatService::class));
        $this->setMediaForeignTable($this->Name);
        $this->setMediaModel(Gdn::getContainer()->get(MediaModel::class));
        $this->setSessionInterface(Gdn::getContainer()->get("Session"));
        $this->ownSite = \Gdn::getContainer()->get(OwnSite::class);

        $this->addFilterField(["Sink", "Score"]);

        try {
            $this->setArchiveDate(Gdn::config("Vanilla.Archive.Date", ""));
        } catch (\Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_NOTICE);
        }
        $this->logger = Gdn::getContainer()->get(\Psr\Log\LoggerInterface::class);
        $this->reactionModel = Gdn::getContainer()->get(ReactionModel::class);
    }

    /**
     * @inheritdoc
     */
    public static function getSystemCallableMethods(): array
    {
        return [
            "deleteDiscussionsIterator",
            "deleteDiscussionIterator",
            "moveDiscussionsIterator",
            "closeDiscussionsIterator",
        ];
    }

    /**
     * The shared instance of this object.
     *
     * @return DiscussionModel Returns the instance.
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new DiscussionModel();
        }

        return self::$instance;
    }

    /**
     * Set model option
     *
     * @param string $option
     * @param mixed $value
     */
    public function setOption(string $option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
     * Get model option
     *
     * @param string $option
     * @param null $default
     * @return mixed|null
     */
    public function getOption(string $option, $default = null)
    {
        return $this->options[$option] ?? $default;
    }

    /**
     * Forces a date string into a DateTimeImmutable object. If the string is not a valid date string,
     * it returns null.
     *
     * @param ?string $date
     * @return DateTimeImmutable|null
     */
    private static function forceDateTime($date): ?DateTimeImmutable
    {
        try {
            $date = empty($date) ? null : new DateTimeImmutable($date);
        } catch (\Exception $ex) {
            $date = null;
        }

        return $date;
    }

    /**
     * Check if a user has view permission on a discussion.
     *
     * @param int $discussionID
     * @param bool $throw
     *
     * @return bool
     */
    public function hasViewPermission(int $discussionID, bool $throw = true): bool
    {
        $categoryID =
            $this->createSql()
                ->select("CategoryID")
                ->from("Discussion")
                ->where(["DiscussionID" => $discussionID])
                ->limit(1)
                ->get()
                ->firstRow()->CategoryID ?? null;
        if ($categoryID === null) {
            if ($throw) {
                throw new NotFoundException("Discussion", ["discussionID" => $discussionID]);
            } else {
                return false;
            }
        }

        return $this->categoryPermission("discussions.view", $categoryID, $throw);
    }

    /**
     * Verify the current user has a permission in a category.
     *
     * @param string|array $permission The permission slug(s) to check (e.g. Vanilla.Discussions.View).
     * @param int $categoryID The category's numeric ID.
     * @param bool $throw
     *
     * @throws PermissionException if the current user does not have the permission in the category.
     */
    public function categoryPermission(string|array $permission, int $categoryID, bool $throw = true): bool
    {
        $permissions = (array) $permission;

        if (
            !$this->getSessionInterface()
                ->getPermissions()
                ->hasAny(
                    $permissions,
                    $categoryID,
                    Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
                    CategoryModel::PERM_JUNCTION_TABLE
                )
        ) {
            if ($throw) {
                throw new PermissionException($permission);
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array The current sort array.
     * @throws Exception
     */
    public static function getAllowedSorts()
    {
        self::initStatic();

        return self::$allowedSorts;
    }

    /**
     * Get the registered filters.
     *
     * This method must never be called before plugins initialisation.
     *
     * @return array The current filter array.
     * @throws Exception
     */
    public static function getAllowedFilters()
    {
        self::initStatic();

        return self::$allowedFilters;
    }

    /**
     * @return string
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Set the discussion sort.
     *
     * This setter also accepts an array and checks if the sort key exists on the array. Will only set the sort property
     * if it exists in the allowed sorts array.
     *
     * @param string|array $sort The prospective sort to set.
     */
    public function setSort($sort)
    {
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
    public function setFilters($filters)
    {
        if (is_array($filters)) {
            $safeFilters = $this->getFiltersFromArray($filters);
            $this->filters = $safeFilters;
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function getDefaultSortKey()
    {
        $orderBy = self::getDefaultOrderBy(); // check config

        // Try to find a matching sort.
        foreach (self::getAllowedSorts() as $sort) {
            if (val("orderBy", $sort, []) == $orderBy) {
                return val("key", $sort, "");
            }
        }

        return "";
    }

    /**
     * Update the attachment status of attachemnts in particular discussion.
     *
     * @param int $discussionID The ID of the discussion.
     * @param bool $isUpdate Whether or not we are updating an existing discussion.
     */
    private function calculateMediaAttachments(int $discussionID, bool $isUpdate)
    {
        $discussionRow = $this->getID($discussionID, DATASET_TYPE_ARRAY);
        if ($discussionRow) {
            if ($isUpdate) {
                $this->flagInactiveMedia($discussionID, $discussionRow["Body"], $discussionRow["Format"]);
            }
            $this->refreshMediaAttachments($discussionID, $discussionRow["Body"], $discussionRow["Format"]);
        }
    }

    /**
     * Determines whether or not the current user can close a discussion.
     *
     * @param object|array $discussion
     * @return bool Returns true if the user can close or false otherwise.
     */
    public static function canClose($discussion): bool
    {
        if (is_object($discussion)) {
            $categoryID = $discussion->CategoryID ?? null;
            $insertUserID = $discussion->InsertUserID ?? 0;
            $isClosed = $discussion->Closed ?? null;
            $attributes = $discussion->Attributes ?? [];
        } else {
            $categoryID = $discussion["CategoryID"] ?? null;
            $insertUserID = $discussion["InsertUserID"] ?? 0;
            $isClosed = $discussion["Closed"] ?? null;
            $attributes = $discussion["Attributes"] ?? [];
        }

        if (CategoryModel::checkPermission($categoryID, "Vanilla.Discussions.Close")) {
            return true;
        }

        if ($isClosed && ($attributes[self::CLOSED_BY_USER_ID] ?? null) !== Gdn::session()->UserID) {
            return false;
        }

        if (
            Gdn::session()->UserID === $insertUserID &&
            Gdn::session()->checkPermission("Vanilla.Discussions.CloseOwn")
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determines whether or not the current user can edit a discussion.
     *
     * @param object|array $discussion The discussion to examine.
     * @param int $timeLeft Sets the time left to edit or 0 if not applicable.
     * @return bool Returns true if the user can edit or false otherwise.
     */
    public static function canEdit($discussion, &$timeLeft = 0)
    {
        $category = CategoryModel::categories(val("CategoryID", $discussion));

        // Users with global edit permission can edit.
        if (CategoryModel::checkPermission($category, "Vanilla.Discussions.Edit")) {
            return true;
        }

        // Make sure only moderators can edit closed things.
        if (val("Closed", $discussion)) {
            return false;
        }

        // Non-mods can't edit if they aren't the author.
        if (Gdn::session()->UserID != val("InsertUserID", $discussion)) {
            return false;
        }

        return parent::editContentTimeout($discussion, $timeLeft);
    }

    /**
     * @inheritDoc
     */
    public function calculateAggregates(string $aggregateName, int $from, int $to)
    {
        $where = ["DiscussionID" => new RangeExpression(">=", $from, "<=", $to)];
        $this->counts($aggregateName, $from, $to, $to, $where);
    }

    /**
     *
     * Update Discussion table aggregate counts
     *
     * @param string $column
     * @param mixed $from
     * @param mixed $to
     * @param mixed $max
     * @param array $where
     *
     * @return array
     * @throws Gdn_UserException
     *
     */
    public function counts(string $column, $from = false, $to = false, $max = false, array $where = [])
    {
        $result = ["Complete" => true];
        switch ($column) {
            case "CountComments":
                $this->Database->query(DBAModel::getCountSQL("count", "Discussion", "Comment", "", "", "", "", $where));
                break;
            case "FirstCommentID":
                $this->Database->query(
                    DBAModel::getCountSQL("min", "Discussion", "Comment", $column, "", "", "", $where)
                );
                break;
            case "LastCommentID":
                $this->Database->query(
                    DBAModel::getCountSQL("max", "Discussion", "Comment", $column, "", "", "", $where)
                );
                break;
            case "DateLastComment":
                $defaultDate = null;
                $countSql = DBAModel::getCountSQL(
                    "max",
                    "Discussion",
                    "Comment",
                    $column,
                    "DateInserted",
                    "",
                    "",
                    $where,
                    $defaultDate
                );
                $this->Database->query($countSql);
                $this->SQL
                    ->update("Discussion")
                    ->set("DateLastComment", "DateInserted", false, false)
                    ->where("DateLastComment", $defaultDate)
                    ->where($where);
                $this->SQL->put();
                break;
            case "LastCommentUserID":
                if (!$max) {
                    // Get the range for this update.
                    $dBAModel = new DBAModel();
                    [$min, $max] = $dBAModel->primaryKeyRange("Discussion");

                    if (!$from) {
                        $from = $min;
                        $to = $min + DBAModel::$ChunkSize - 1;
                    }
                }
                $this->SQL
                    ->update("Discussion d")
                    ->join("Comment c", "c.CommentID = d.LastCommentID")
                    ->set("d.LastCommentUserID", "c.InsertUserID", false, false)
                    ->where("d.DiscussionID >=", $from)
                    ->where("d.DiscussionID <=", $to)
                    ->put();
                $result["Complete"] = $to >= $max;

                $percent = round(($to * 100) / $max);
                if ($percent > 100 || $result["Complete"]) {
                    $result["Percent"] = "100%";
                } else {
                    $result["Percent"] = $percent . "%";
                }

                $from = $to + 1;
                $to = $from + DBAModel::$ChunkSize - 1;
                $result["Args"]["From"] = $from;
                $result["Args"]["To"] = $to;
                $result["Args"]["Max"] = $max;
                break;
            case "Hot":
                $this->updateColumnHot($where);
                break;
            default:
                throw new Gdn_UserException("Unknown column $column");
        }

        return $result;
    }

    /**
     * Builds base SQL query for discussion data.
     *
     * Events: AfterDiscussionSummaryQuery.
     *
     * @param array $additionalFields Allows selection of additional fields as Alias=>Table.Fieldname.
     * @param bool $join
     * @since 2.0.0
     * @access public
     *
     */
    public function discussionSummaryQuery($additionalFields = [], $join = true)
    {
        // Verify permissions (restricting by category if necessary)
        $perms = $this->categoryModel->getVisibleCategoryIDs();

        if ($perms !== true) {
            $this->SQL->whereIn("d.CategoryID", $perms);
        }

        // Buid main query
        $this->SQL
            ->select("d.*")
            ->select("d.InsertUserID", "", "FirstUserID")
            ->select("d.DateInserted", "", "FirstDate")
            ->select("d.DateLastComment", "", "LastDate")
            ->select("d.LastCommentUserID", "", "LastUserID")
            ->from("Discussion d");

        if ($join) {
            $this->SQL
                ->select("iu.Name", "", "FirstName") // <-- Need these for rss!
                ->select("iu.Photo", "", "FirstPhoto")
                ->select("iu.Email", "", "FirstEmail")
                ->join("User iu", "d.InsertUserID = iu.UserID", "left") // First comment author is also the discussion insertuserid

                ->select("lcu.Name", "", "LastName")
                ->select("lcu.Photo", "", "LastPhoto")
                ->select("lcu.Email", "", "LastEmail")
                ->join("User lcu", "d.LastCommentUserID = lcu.UserID", "left") // Last comment user

                ->select("ca.Name", "", "Category")
                ->select("ca.UrlCode", "", "CategoryUrlCode")
                ->select("ca.PermissionCategoryID")
                ->join("Category ca", "d.CategoryID = ca.CategoryID", "left"); // Category
        }

        // Add any additional fields that were requested.
        if (is_array($additionalFields)) {
            foreach ($additionalFields as $alias => $field) {
                // Select the field.
                $this->SQL->select($field, "", is_numeric($alias) ? "" : $alias);
            }
        }

        $this->fireEvent("AfterDiscussionSummaryQuery");
    }

    /**
     * Given a discussion type, get the layout view type for it.
     *
     * @param string|null $discussionType
     *
     * @return string
     */
    public function layoutViewTypeByDiscussionType(?string $discussionType): string
    {
        $discussionType = self::normalizeDiscussionType($discussionType);
        $discussionTypes = self::discussionTypes();
        $layoutViewTypesByDiscussionType = array_column($discussionTypes, "layoutViewType", "apiType");

        return $layoutViewTypesByDiscussionType[$discussionType] ?? "discussion";
    }

    /**
     * Get the allowed discussion types.
     *
     * @param null $category
     * @return array Returns an array of discussion type definitions.
     * @throws ContainerException
     */
    public static function discussionTypes($category = null): array
    {
        if (self::$discussionTypes === null) {
            $discussionTypes = [
                "Discussion" => [
                    "layoutViewType" => "discussion",
                    "apiType" => "discussion",
                    "Singular" => "Discussion",
                    "Plural" => "Discussions",
                    "AddUrl" => "/post/discussion",
                    "AddText" => "New Discussion",
                    "AddIcon" => "create-discussion",
                ],
            ];

            Gdn::pluginManager()->EventArguments["Category"] = &$category;
            Gdn::pluginManager()->EventArguments["Types"] = &$discussionTypes;
            Gdn::pluginManager()
                ->fireAs("DiscussionModel")
                ->fireEvent("DiscussionTypes");

            self::$discussionTypes = $discussionTypes;
        }

        return self::$discussionTypes;
    }

    /**
     * Get the API based discussion types.
     *
     * @return array
     * @throws ContainerException
     */
    public static function apiDiscussionTypes(): array
    {
        return array_column(\DiscussionModel::discussionTypes(), "apiType");
    }

    /**
     * Gets the data for multiple discussions based on the given criteria.
     *
     * Sorts results based on config options Vanilla.Discussions.SortField
     * and Vanilla.Discussions.SortDirection.
     * Events: BeforeGet, AfterAddColumns.
     *
     * @param int $orderFields Number of discussions to skip.
     * @param int|false $orderDirection Max number of discussions to return.
     * @param array $limit SQL conditions.
     * @param array $pageNumber Allows selection of additional fields as Alias=>Table.Fieldname.
     * @return Gdn_DataSet SQL result.
     * @throws Exception
     * @deprecated Don't use this method. It is not defined properly. Use `getWhere()` instead.
     */
    public function get($orderFields = 0, $orderDirection = false, $limit = [], $pageNumber = [])
    {
        // These are kludges for PHP 8 compatibility.
        $offset = $orderFields;
        $wheres = $limit;
        $limit = $orderDirection;
        $additionalFields = $pageNumber;

        if ($limit == false) {
            $limit = Gdn::config("Vanilla.Discussions.PerPage", 50);
        }

        $offset = !is_numeric($offset) || $offset < 0 ? 0 : $offset;

        $session = Gdn::session();
        $userID = $session->UserID > 0 ? $session->UserID : 0;
        $this->discussionSummaryQuery($additionalFields, false);

        if ($userID > 0) {
            $this->SQL
                ->select("w.UserID", "", "WatchUserID")
                ->select("w.DateLastViewed, w.Dismissed, w.Bookmarked")
                ->select("w.CountComments", "", "CountCommentWatch")
                ->select("w.Participated")
                ->join("UserDiscussion w", "d.DiscussionID = w.DiscussionID and w.UserID = " . $userID, "left");
        } else {
            $this->SQL
                ->select("0", "", "WatchUserID")
                ->select("now()", "", "DateLastViewed")
                ->select("0", "", "Dismissed")
                ->select("0", "", "Bookmarked")
                ->select("0", "", "CountCommentWatch")
                ->select("0", "", "Participated")
                ->select("0", "", "Read")
                ->select("d.Announce", "", "IsAnnounce");
        }

        if ($offset !== false && $limit !== false) {
            $this->SQL->limit($limit, $offset);
        }

        // Get preferred sort order
        $orderBy = $this->getOrderBy();

        $this->EventArguments["OrderFields"] = &$orderBy;
        $this->EventArguments["Wheres"] = &$wheres;
        $this->EventArguments["sql"] = &$this->SQL;
        $this->fireEvent("BeforeGet"); // @see 'BeforeGetCount' for consistency in results vs. counts
        $eventManager = $this->getEventManager();
        $eventManager->dispatch(new DiscussionQueryEvent($this->SQL));

        $removeAnnouncements = true;
        if (strtolower(val("Announce", $wheres)) == "all") {
            $removeAnnouncements = false;
            unset($wheres["Announce"]);
        }

        // If not looking at discussions filtered by bookmarks or user, filter announcements out.
        if ($removeAnnouncements && !isset($wheres["w.Bookmarked"]) && !isset($wheres["d.InsertUserID"])) {
            if ($userID > 0) {
                $this->SQL
                    ->beginWhereGroup()
                    ->where(["d.Announce" => 0])
                    ->orWhere(["w.Dismissed <>" => 0])
                    ->endWhereGroup();
            } else {
                $this->SQL->where(["Announce" => false]);
            }
        }

        if (is_array($wheres)) {
            $this->SQL->where($wheres);
        }

        foreach ($orderBy as $orderField => $direction) {
            $this->SQL->orderBy($this->addFieldPrefix($orderField), $direction);
        }

        // Set range and fetch
        $data = $this->SQL->get();

        // Change discussions returned based on additional criteria
        $this->addDiscussionColumns($data);

        // Join in the users.
        $this->userModel->joinUsers($data, ["FirstUserID", "LastUserID"]);
        CategoryModel::joinCategories($data);

        if ($this->shouldUseDenormalizedViews()) {
            $this->addDenormalizedViews($data);
        }

        // Prep and fire event
        $this->EventArguments["Data"] = $data;
        $this->fireEvent("AfterAddColumns");

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultLimit()
    {
        return (int) Gdn::config("Vanilla.Discussions.PerPage", 50);
    }

    /**
     * Get a list of the most recent discussions.
     *
     * @param array|false $where The where condition of the get.
     * @param bool|false|int $limit The number of discussion to return.
     * @param int|false $offset The offset within the total set.
     * @param bool $expand Expand relevant related records (e.g. category, users).
     * @return Gdn_DataSet Returns a <a href='psi_element://Gdn_DataSet'>Gdn_DataSet</a> of discussions.
     * of discussions.
     * @deprecated Use DiscussionModel::getWhere() instead.
     */
    public function getWhereRecent($where = [], $limit = false, $offset = false, $expand = true)
    {
        $where["Announce"] = $where["Announce"] ?? false;
        $result = $this->getWhere($where, "", "", $limit, $offset, $expand);
        return $result;
    }

    /**
     * Returns an array in the format [field => direction]. You can safely use return values from this function
     * in the orderBy() SQL function.
     *
     * @return array An array of field => direction values.
     * @since 2.3
     */
    protected function getOrderBy()
    {
        if ($key = self::getSort()) {
            $orderBy = val("orderBy", $this->getSortFromKey($key));
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
    public static function getDefaultOrderBy()
    {
        $orderField = c("Vanilla.Discussions.SortField", self::DEFAULT_ORDER_BY_FIELD);
        $orderDirection = c("Vanilla.Discussions.SortDirection", "desc");

        // Normalize any prefixed fields
        if (strpos($orderField, "d.") === 0) {
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
    protected function getWheres($categoryIDs = [])
    {
        $wheres = [];
        $filters = $this->getFiltersFromKeys($this->getFilters());

        foreach ($filters as $filter) {
            if (!empty($categoryIDs)) {
                $setKey = val("setKey", $filter);
                $filterSetCategories = val("categories", val($setKey, self::getAllowedFilters()));

                if (!empty($filterSetCategories) and array_diff($categoryIDs, $filterSetCategories)) {
                    $filter["wheres"] = [];
                }
            }
            $wheres = $this->combineWheres(val("wheres", $filter, []), $wheres);
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
    protected function combineWheres($newWheres, $wheres)
    {
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
     * Get the maximum number of discussion pages.
     *
     * @return int
     */
    public function getMaxPages()
    {
        return (int) c("Vanilla.Discussions.MaxPages");
    }

    /**
     * Get a list of discussions.
     *
     * @param array $where The where condition of the get.
     * @param string $orderFields The field to order the discussions by.
     * @param string $orderDirection The order, either **asc** or **desc**.
     * @param int|false $limit The number of discussion to return.
     * @param int|false $offset The offset within the total set.
     * @param bool $expand Expand relevant related records (e.g. category, users).
     * @param bool|string $filterType Query Filter.
     * @param bool|int $userID potential User ID.
     * @param array $selects Extra fields to select.
     * @return Gdn_DataSet Returns a {@link Gdn_DataSet} of discussions.
     * @throws Exception
     */
    public function getWhere(
        $where = [],
        $orderFields = "",
        $orderDirection = "",
        $limit = false,
        $offset = false,
        bool $expand = true,
        $filterType = false,
        $userID = false,
        array $selects = []
    ) {
        $limit = $limit ?? $this->getDefaultLimit();

        if (!empty($orderFields)) {
            $orderBy = [$orderFields => $orderDirection];
        } else {
            $orderBy = $this->getOrderBy();
        }

        $where = $this->combineWheres($this->getWheres(), $where);

        $this->EventArguments["OrderBy"] = &$orderBy;
        $this->EventArguments["Wheres"] = &$where;
        $this->EventArguments["Selects"] = &$selects;
        $this->getEventManager()->fire("discussionModel_beforeGet", $this, $this->EventArguments);
        $finalQuery = $this->getWhereQuery($where, $orderBy, $selects, $limit, $offset, $filterType, $userID);
        $eventManager = $this->getEventManager();
        $eventManager->dispatch(new DiscussionQueryEvent($finalQuery));
        $data = $finalQuery->get();

        // Change discussions returned based on additional criteria
        $this->addDiscussionColumns($data);

        // Join in users and categories.
        if ($expand) {
            $this->userModel->joinUsers($data, ["FirstUserID", "LastUserID"]);
            CategoryModel::joinCategories($data);
        }

        if (c("Vanilla.Views.Denormalize")) {
            $this->addDenormalizedViews($data);
        }

        // Prep and fire event
        $this->EventArguments["Data"] = $data;
        $this->getEventManager()->fire("discussionModel_afterAddColumns", $this);
        $this->SQL->reset();
        return $data;
    }

    /**
     * Get a query for list of discussions.
     *
     *
     * @param array $where The where condition of the get.
     * @param array $orderBy Order By array.
     * @param array $selects Select parameters.
     * @param int|false $limit The number of discussion to return.
     * @param int|false $offset The offset within the total set.
     * @param bool|string $filterType Query Filter.
     * @param bool|int $userID potential User ID.
     * @return Gdn_MySQLDriver Returns query of discussions.
     * @throws Exception
     */
    private function getWhereQuery(
        array $where = [],
        array $orderBy = [],
        array $selects = [],
        $limit = false,
        $offset = false,
        $filterType = false,
        $userID = false
    ): Gdn_MySQLDriver {
        $limit = $limit ?: $this->getDefaultLimit();
        $offset = $offset ?: 0;
        $userID = $userID ?: Gdn::session()->UserID;

        $joinDirtyRecords = $where[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;
        if (isset($where[DirtyRecordModel::DIRTY_RECORD_OPT])) {
            unset($where[DirtyRecordModel::DIRTY_RECORD_OPT]);
        }

        $where = $this->normalizeQuery($where, "d");

        // Verify permissions (restricting by category if necessary)
        $perms = self::categoryPermissions();
        if ($perms !== true) {
            if (isset($where["d.CategoryID"])) {
                $where["d.CategoryID"] = array_values(array_intersect((array) $where["d.CategoryID"], $perms));
            } else {
                $where["d.CategoryID"] = $perms;
            }
        }

        $sql = $this->SQL;

        $sql->select("d.*")->from("Discussion d");

        // Set Dirty records
        if ($joinDirtyRecords) {
            $this->applyDirtyWheres("d");
        }

        if (isset($where["d.Followed"])) {
            $followedCategory = $this->categoryModel->getFollowed(Gdn::session()->UserID);
            if (isset($where["d.CategoryID"])) {
                $where["d.CategoryID"] = array_values(
                    array_intersect($where["d.CategoryID"], array_keys($followedCategory))
                );
            } else {
                $where["d.CategoryID"] = array_keys($followedCategory);
            }

            unset($where["d.Followed"]);
        }

        if (isset($where["d.Type"])) {
            PostTypeModel::whereParentPostType($sql, $where["d.Type"], "d.");
            unset($where["d.Type"]);
        }

        if (isset($where["d.DiscussionID"]) && isset($where["d.InterestCategoryID"])) {
            $sql->beginWhereGroup()
                ->where("d.DiscussionID", $where["d.DiscussionID"])
                ->orWhere(
                    "d.CategoryID",
                    isset($where["d.CategoryID"])
                        ? array_intersect($where["d.CategoryID"], $where["d.InterestCategoryID"])
                        : $where["d.InterestCategoryID"]
                )
                ->endWhereGroup();
            unset($where["d.DiscussionID"]);
            unset($where["d.InterestCategoryID"]);
        } elseif (isset($where["d.InterestCategoryID"])) {
            $where["d.CategoryID"] = isset($where["d.CategoryID"])
                ? array_intersect($where["d.CategoryID"], $where["d.InterestCategoryID"])
                : $where["d.InterestCategoryID"];
            unset($where["d.InterestCategoryID"]);
        }

        // Add the UserDiscussion query.
        if ($userID > 0) {
            $sql->join("UserDiscussion ud", "ud.DiscussionID = d.DiscussionID and ud.UserID = $userID", "left")
                ->select("ud.UserID", "", "WatchUserID")
                ->select("ud.DateLastViewed, ud.Dismissed, ud.Bookmarked")
                ->select("ud.CountComments", "", "CountCommentWatch")
                ->select("ud.Participated");

            if ($filterType) {
                $where["ud." . ucfirst($filterType)] = 1;
            }
        }

        if (($where["d.Announce"] ?? null) === "all") {
            // This value historically meant to not do any announcement filtering because it was implicitly baked in.
            // Nowadays we should just strip it and do no filtering.
            unset($where["d.Announce"]);
        }

        if (isset($where["d.Announce"]) && $userID) {
            // If we have an Announce value and a sessioned user we need to or with UserDiscussion.

            $announceValue = (array) $where["d.Announce"];
            if (in_array(0, $announceValue)) {
                $sql->beginWhereGroup()
                    ->where(["d.Announce" => $announceValue])
                    ->orWhere(["ud.Dismissed <>" => 0])
                    ->endWhereGroup();
            } else {
                $sql->where(["d.Announce" => $where["d.Announce"]])
                    ->beginWhereGroup()
                    ->where(["ud.Dismissed <>" => 1])
                    ->orWhere("ud.Dismissed is null")
                    ->endWhereGroup();
            }
            unset($where["d.Announce"]);
        }

        $insertUserRoleIDs = $where["uri.RoleID"] ?? null;
        if (!empty($insertUserRoleIDs)) {
            $sql->join("UserRole uri", "d.InsertUserID = uri.UserID")->where("uri.RoleID", $insertUserRoleIDs);

            // Ensure rows aren't duplicated
            $sql->distinct();
        }
        unset($where["uri.RoleID"]);

        $this->EventArguments["SQL"] = $sql;
        $this->fireEvent("BeforeGetSubQuery");

        $sql->select($selects);
        $sql->where($where);

        if (!empty($orderBy)) {
            $sql->orderBy($orderBy);
        }

        $sql->limit($limit);
        $sql->offset($offset);

        return $sql;
    }

    /**
     *  Normalize a where rows by adding a prefix to specify the table to make sure there aren't any ambiguous references.
     *
     * @param array $where
     * @param string $prefix
     * @return array
     */
    public function normalizeQuery(array $where, string $prefix): array
    {
        $safeWheres = [];
        foreach ($where as $key => $value) {
            if ($value instanceof \Vanilla\Database\CallbackWhereExpression) {
                $safeWheres[$key] = $value;
                continue;
            }
            // Normalize the legacy UserDiscussion alias.
            if (str_contains($key, "w.")) {
                $this->logger->warning("Using deprecated `w` alias for UserDiscussion", [
                    "where" => $where,
                ]);
                $key = str_replace("w.", "ud.", $key);
            }

            if ($key) {
                $safeWheres[$this->addFieldPrefix($key, $prefix)] = $value;
            }
        }
        return $safeWheres;
    }

    /**
     * Get a count of the list of discussions.
     *
     * @param array $where The where condition of the get.
     * @param bool|string $filterType Query Filter.
     * @param bool|int $userID potential User ID.
     * @return int Returns query of discussions.
     * @throws Exception
     */
    public function getPagingCount(array $where = [], $filterType = false, $userID = false): int
    {
        $count = $this->getWhereQuery(
            $where,
            [],
            ["d.DiscussionID"],
            false,
            false,
            $filterType,
            $userID
        )->getPagingCount("d.DiscussionID");
        return $count;
    }

    /**
     * Adds a prefix to the field name if the field doesn't already have one.
     *
     * @param string $fieldName The name of the field.
     * @param string $prefix
     * @return string The fieldname with the prefix if one does not exist.
     */
    public function addFieldPrefix($fieldName, $prefix = "d")
    {
        // Make sure there aren't any ambiguous discussion references.
        if (strpos($fieldName, ".") === false) {
            $fieldName = ($fieldName[0] === "-" ? "-" : "") . $prefix . "." . ltrim($fieldName, "-");
        }

        return $fieldName;
    }

    /**
     * Add denormalized views to discussions.
     *
     * @param Gdn_DataSet $discussions
     */
    public function addDenormalizedViews(&$discussions)
    {
        $cache = \Gdn::cache();
        if ($discussions instanceof Gdn_DataSet) {
            $result = $discussions->result();
            $cacheKeys = [];
            foreach ($result as $discussion) {
                $discussionID = $discussion->DiscussionID;
                $cacheKey = sprintf(DiscussionModel::CACHE_DISCUSSIONVIEWS, $discussionID);
                $cacheKeys[] = $cacheKey;
            }
            $cached = $cache->get($cacheKeys) ?: [];

            foreach ($result as $i => &$discussion) {
                $discussionID = $discussion->DiscussionID;
                $cacheKey = sprintf(DiscussionModel::CACHE_DISCUSSIONVIEWS, $discussionID);

                $cachedViews = $cached[$cacheKey] ?? 0;
                $discussion->CountViews += $cachedViews;
            }
        } else {
            if (isset($discussions->DiscussionID)) {
                /** @var object $discussion */
                $discussion = $discussions;
                $cacheKey = sprintf(DiscussionModel::CACHE_DISCUSSIONVIEWS, $discussion->DiscussionID);
                $cacheViews = Gdn::cache()->get($cacheKey);
                if ($cacheViews !== Gdn_Cache::CACHEOP_FAILURE) {
                    $discussion->CountViews += $cacheViews;
                }
            }
        }
    }

    /**
     * Modifies discussion data before it is returned.
     *
     * Takes archiving into account and fixes inaccurate comment counts.
     *
     * @param object $data SQL result.
     * @since 2.0.0
     * @access public
     *
     */
    public function addDiscussionColumns($data)
    {
        // Change discussions based on archiving.
        $result = &$data->result();
        foreach ($result as &$discussion) {
            $this->calculate($discussion);
        }
    }

    /**
     * Fix order in sql result set.
     *
     * @param Gdn_DataSet $data
     * @param string $orderField
     * @param string $orderDirection
     */
    public function fixOrder(Gdn_DataSet $data, string $orderField, string $orderDirection)
    {
        // Change discussions order.
        $result = &$data->result();
        usort($result, function ($a, $b) use ($orderField, $orderDirection) {
            $order = $orderDirection === "asc" ? 1 : -1;
            if (is_array($a)) {
                return $order *
                    (($a[$orderField] ?? $a[ucfirst($orderField)]) <=> ($b[$orderField] ?? $b[ucfirst($orderField)]));
            } else {
                return $order *
                    (($a->$orderField ?? $a->{ucfirst($orderField)}) <=>
                        ($b->$orderField ?? $b->{ucfirst($orderField)}));
            }
        });
    }

    /**
     * Massage the data on a discussion row.
     *
     * @param object $discussion
     */
    public function calculate(&$discussion)
    {
        // Fix up output
        $discussion->Name = htmlspecialchars(trim($discussion->Name) ?: t("(Untitled)"));
        $discussion->Attributes = dbdecode($discussion->Attributes);
        $discussion->Url = discussionUrl($discussion);
        $discussion->CanonicalUrl = $discussion->Attributes["CanonicalUrl"] ?? $discussion->Url;
        $discussion->Tags = $this->formatTags($discussion->Tags);

        // Join in the category.
        $category = CategoryModel::categories($discussion->CategoryID);
        if (empty($category)) {
            $category = [
                "Name" => "",
                "UrlCode" => "",
                "PermissionCategoryID" => -1,
                "DateMarkedRead" => null,
            ];
        }
        $discussion->Category = $category["Name"];
        $discussion->CategoryUrlCode = $category["UrlCode"];
        $discussion->PermissionCategoryID = $category["PermissionCategoryID"];

        // Add some legacy calculated columns.
        if (!property_exists($discussion, "FirstUserID")) {
            $discussion->FirstUserID = $discussion->InsertUserID;
            $discussion->FirstDate = $discussion->DateInserted;
            $discussion->LastUserID = $discussion->LastCommentUserID;
            $discussion->LastDate = $discussion->DateLastComment;
        }

        // Add the columns from UserDiscussion if they don't exist.
        if (!property_exists($discussion, "CountCommentWatch")) {
            $discussion->WatchUserID = null;
            $discussion->DateLastViewed = null;
            $discussion->Dismissed = 0;
            $discussion->Bookmarked = 0;
            $discussion->CountCommentWatch = null;
        }

        // Allow for discussions to be archived.
        if ($this->isArchived($discussion->DateLastComment)) {
            $discussion->Closed = "1";
        }

        // Discussions are always unread to guests. Otherwise check Read status and Unread count.
        if (!Gdn::session()->isValid()) {
            $discussion->Read = false;
            $discussion->CountUnreadComments = true;
        } else {
            // If the category was marked explicitly read at some point, see if that applies here
            if ($category && !is_null($category["DateMarkedRead"])) {
                // If the discussion hasn't been viewed or was created after the category was marked read,
                // leave CountCountCommentWatch and DateLastViewed null. Otherwise, calculate the correct DateLastViewed.
                if (
                    !is_null($discussion->DateLastViewed) ||
                    self::maxDate($category["DateMarkedRead"], $discussion->DateInserted) ===
                        $category["DateMarkedRead"]
                ) {
                    // If it's not a newly created discussion, set DateLastViewed to whichever is most recent.
                    $discussion->DateLastViewed = self::maxDate(
                        $discussion->DateLastViewed,
                        $category["DateMarkedRead"]
                    );
                }
            }

            [$read, $count] = $this->calculateCommentReadData(
                $discussion->CountComments,
                $discussion->DateLastComment,
                $discussion->CountCommentWatch,
                $discussion->DateLastViewed
            );
            $discussion->Read = $read;
            $discussion->CountUnreadComments = $count;
        }

        if ($discussion->LastUserID == null) {
            $discussion->LastUserID = $discussion->InsertUserID;
            $discussion->LastDate = $discussion->DateInserted;
        }

        // Translate Announce to Pinned.
        $pinned = false;
        $pinLocation = null;
        if (property_exists($discussion, "Announce") && $discussion->Announce > 0) {
            $pinned = true;
            switch (intval($discussion->Announce)) {
                case self::GLOBAL_ANNOUNCEMENT:
                    $pinLocation = "recent";
                    break;
                case self::CATEGORY_ANNOUNCEMENT:
                    $pinLocation = "category";
            }
        }
        $discussion->pinned = $pinned;
        $discussion->pinLocation = $pinLocation;
        $this->EventArguments["Discussion"] = &$discussion;
        $this->fireEvent("SetCalculatedFields");
    }

    /**
     * Calculate if the user has read all the Comments in a discussion. If the data in the UserDiscussion Table
     * and the Discussion Table conflict, it returns the best guess of what the read status and number of
     * unread comments are. The data in the Discussion table
     * is more likely to be reliable, so when in doubt, rely on that data.
     *
     * @param int $discussionCommentCount Number of Comments according to the Discussion table.
     * @param string $discussionLastCommentDate Date of the last comment according to the Discussion table.
     * @param int|null $userReadComments Number of Comments the user has read according to the UserDiscussion table.
     * @param string|null $userLastReadDate Date the user last viewed the discussion or marked the
     * category read (or null), according to the UserDiscussion table.
     * @return array Returns an array where the first item is a boolean value and the second is a int > 0 or true.
     */
    public function calculateCommentReadData(
        int $discussionCommentCount,
        ?string $discussionLastCommentDate,
        ?int $userReadComments,
        ?string $userLastReadDate
    ): array {
        $discussionLastCommentDate = self::forceDateTime($discussionLastCommentDate);
        $userLastReadDate = self::forceDateTime($userLastReadDate);
        $isRead = true;
        $unreadCommentCount = $discussionCommentCount - $userReadComments;
        if ($discussionLastCommentDate > $userLastReadDate) {
            $isRead = false;

            // If the latest comment is later than last viewed and there are more comments read than comments
            // or read comments is null, set unread count to 1.
            if (
                $discussionCommentCount > 0 &&
                ($userReadComments >= $discussionCommentCount) | ($userReadComments === null)
            ) {
                $unreadCommentCount = 1;
            }
        }

        // If the user has viewed the discussion more recently than the last comment, but there are unread comments,
        // and the discussion is only one page long, set unread comments to 0.
        if ($userLastReadDate >= $discussionLastCommentDate && $unreadCommentCount >= 0) {
            $unreadCommentCount = 0;
        }

        // If the calculated number of unread comments is negative, set it to 0.
        if ($unreadCommentCount < 0 && $isRead) {
            $unreadCommentCount = 0;
        }

        // If the discussion has no comments and read status is false or both user categories are null,
        // set unread comments to true unless the discussion is archived (in which case, we don't want it showing up as new).
        if (($discussionCommentCount === 0 && !$isRead) | ($userReadComments === null && $userLastReadDate === null)) {
            if (!is_null($discussionLastCommentDate)) {
                $this->isArchived($discussionLastCommentDate->format(MYSQL_DATE_FORMAT))
                    ? ($unreadCommentCount = 0)
                    : ($unreadCommentCount = true);
            }
        }

        return [$isRead, $unreadCommentCount];
    }

    /**
     * Decide which of two dates is the most recent.
     *
     * @param string|null $dateOne
     * @param string|null $dateTwo
     * @return string|null Returns most recent date.
     * @throws Exception Emits Exception in case of an error.
     */
    public static function maxDate(?string $dateOne, ?string $dateTwo): ?string
    {
        $dateOne = self::forceDateTime($dateOne);
        $dateTwo = self::forceDateTime($dateTwo);
        $result = null;

        if ($dateOne < $dateTwo) {
            $result = $dateTwo;
        } elseif (empty($dateOne) && empty($dateTwo)) {
            $result = null;

            return $result;
        } else {
            $result = $dateOne;
        }

        $maxDate = $result->format(MYSQL_DATE_FORMAT);

        return $maxDate;
    }

    /**
     * Add SQL Where to account for archive date.
     *
     * @param Gdn_SQLDriver $sql
     * @deprecated
     */
    public function addArchiveWhere($sql = null)
    {
        deprecated("DiscussionModel::addArchiveWhere()");
    }

    /**
     * Gets announced discussions.
     *
     * @param array $wheres SQL conditions.
     * @param int $offset The number of records to skip.
     * @param int|false $limit The number of records to limit the query to.
     * @return Gdn_DataSet SQL result.
     * @throws Exception
     * @deprecated Use DiscussionModel::getWhere() and DiscussionModel::getAnnouncementWhere() instead.
     */
    public function getAnnouncements($wheres = [], $offset = 0, $limit = false)
    {
        $whereAnnounce = array_merge($wheres, ["Announce" => $this->getAnnouncementWhere($wheres, true)]);
        $data = $this->getWhere($whereAnnounce, "", "", $limit, $offset);
        return $data;
    }

    /**
     * Manage the various cases to fetch discussion announcements
     *
     * @param array $where
     * @param bool $includeAnnouncement
     * @return int|array
     * @throws Exception
     */
    public function getAnnouncementWhere(array $where, bool $includeAnnouncement = false)
    {
        $where = $this->normalizeQuery($where, "d");
        if (isset($where["d.Announce"])) {
            $announce = $where["d.Announce"];
        } elseif (isset($where["d.CategoryID"]) || isset($where["d.categoryID"]) || isset($where["d.groupID"])) {
            $announcementType = DiscussionModel::ANNOUNCEMENT;

            // If we have multiple categories then show only global announcements (e.g. followed categories page).
            $categoryID = $where["d.CategoryID"] ?? ($where["d.categoryID"] ?? null);
            if (is_array($categoryID)) {
                $announcementType = DiscussionModel::GLOBAL_ANNOUNCEMENT;
            }

            $announce = $includeAnnouncement ? $announcementType : [false];
        } else {
            $announce = $includeAnnouncement
                ? [DiscussionModel::GLOBAL_ANNOUNCEMENT]
                : [false, DiscussionModel::CATEGORY_ANNOUNCEMENT];
        }

        $this->EventArguments["Wheres"] = &$where;
        $this->fireEvent("beforeGetAnnouncements");

        return $announce;
    }

    /**
     * Gets announced discussions count.
     *
     * @param array $wheres SQL conditions.
     * @return int row count.
     * @throws Exception
     * @deprecated
     */
    public function getAnnouncementsPagingCount($wheres = []): int
    {
        $whereAnnounce = array_merge($wheres, ["Announce" => self::ANNOUNCEMENT]);
        return $this->getPagingCount($whereAnnounce);
    }

    /**
     * Gets all users who have bookmarked the specified discussion.
     *
     * @param int $discussionID Unique ID to find bookmarks for.
     * @return object SQL result.
     * @throws Exception
     * @since 2.0.0
     * @access public
     */
    public function getBookmarkUsers($discussionID)
    {
        return $this->SQL
            ->select("UserID")
            ->from("UserDiscussion")
            ->where("DiscussionID", $discussionID)
            ->where("Bookmarked", "1")
            ->get();
    }

    /**
     * Get the ids of users that have bookmarked a discussion.
     *
     * @param int $discussionID
     *
     * @return int[]
     * @throws Exception
     */
    public function getBookmarkUserIDs(int $discussionID): array
    {
        $result = $this->SQL
            ->select("UserID")
            ->from("UserDiscussion")
            ->where("DiscussionID", $discussionID)
            ->where("Bookmarked", true)
            ->get()
            ->column("UserID");
        return $result;
    }

    /**
     * Get discussions for a user.
     *
     * Events: BeforeGetByUser
     *
     * @param int $userID Which user to get discussions for.
     * @param int $limit Max number to get.
     * @param int $offset Number to skip.
     * @param int|false $lastDiscussionID A hint for quicker paging.
     * @param int|false $watchUserID User to use for read/unread data.
     * @param string $permission Permission to filter categories by.
     * @return Gdn_DataSet SQL results.
     * @throws Exception
     * @since 2.1
     * @access public
     *
     */
    public function getByUser(
        $userID,
        $limit,
        $offset,
        $lastDiscussionID = false,
        $watchUserID = false,
        string $permission = ""
    ) {
        // This will load all categories. (do not use unless necessary).
        if (!empty($permission)) {
            $categories = CategoryModel::categories();
            $perms = CategoryModel::filterExistingCategoryPermissions($categories, $permission);
            $perms = array_column($perms, "CategoryID");
        } else {
            $perms = DiscussionModel::categoryPermissions();
        }

        if (is_array($perms) && empty($perms)) {
            return new Gdn_DataSet([]);
        }

        // If no user was provided, view from the perspective of a guest.
        if (!filter_var($watchUserID, FILTER_VALIDATE_INT)) {
            $watchUserID = UserModel::GUEST_USER_ID;
        }

        // The point of this query is to select from one comment table, but filter and sort on another.
        // This puts the paging into an index scan rather than a table scan.
        $this->SQL
            ->select("d2.*")
            ->select("d2.InsertUserID", "", "FirstUserID")
            ->select("d2.DateInserted", "", "FirstDate")
            ->select("d2.DateLastComment", "", "LastDate")
            ->select("d2.LastCommentUserID", "", "LastUserID")
            ->from("Discussion d")
            ->join("Discussion d2", "d.DiscussionID = d2.DiscussionID")
            ->where("d.InsertUserID", $userID)
            ->orderBy("d.DateLastComment", "desc");

        // Join in the watch data.
        if ($watchUserID > UserModel::GUEST_USER_ID) {
            $this->SQL
                ->select("w.UserID", "", "WatchUserID")
                ->select("w.DateLastViewed, w.Dismissed, w.Bookmarked")
                ->select("w.CountComments", "", "CountCommentWatch")
                ->select("w.Participated")
                ->join("UserDiscussion w", "d2.DiscussionID = w.DiscussionID and w.UserID = " . $watchUserID, "left");
        } else {
            $this->SQL
                ->select((string) UserModel::GUEST_USER_ID, "", "WatchUserID")
                ->select("now()", "", "DateLastViewed")
                ->select("0", "", "Dismissed")
                ->select("0", "", "Bookmarked")
                ->select("0", "", "CountCommentWatch")
                ->select("d.Announce", "", "IsAnnounce");
        }

        if (!empty($lastDiscussionID)) {
            // The last comment id from the last page was given and can be used as a hint to speed up the query.
            $this->SQL->where("d.DiscussionID <", $lastDiscussionID)->limit($limit);
        } else {
            $this->SQL->limit($limit, $offset);
        }
        $eventManager = $this->getEventManager();
        $eventManager->dispatch(new DiscussionQueryEvent($this->SQL));
        $this->fireEvent("BeforeGetByUser");

        $data = $this->SQL->get();

        $result = &$data->result();
        $this->LastDiscussionCount = $data->numRows();

        if (count($result) > 0) {
            $this->LastDiscussionID = $result[count($result) - 1]->DiscussionID;
        } else {
            $this->LastDiscussionID = null;
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

        // Change discussions returned based on additional criteria
        $this->addDiscussionColumns($data);

        // Join in the users.
        $this->userModel->joinUsers($data, ["FirstUserID", "LastUserID"]);
        CategoryModel::joinCategories($data);

        if (c("Vanilla.Views.Denormalize", false)) {
            $this->addDenormalizedViews($data);
        }

        $this->EventArguments["Data"] = &$data;
        $this->fireEvent("AfterAddColumns");

        return $data;
    }

    /**
     * Get all the users that have participated in the discussion.
     *
     * @param int $discussionID
     * @return array
     * @throws Exception
     */
    public function getParticipatedUserIDs($discussionID): array
    {
        return $this->SQL
            ->select("UserID")
            ->from("UserDiscussion")
            ->where("DiscussionID", $discussionID)
            ->where("Participated", "1")
            ->get()
            ->column("UserID");
    }

    /**
     * Add category IDs to the current user's category-view permissions.
     *
     * @param int[] $categoryIDs
     * @return array|bool
     */
    public static function addCategoryPermissions(array $categoryIDs)
    {
        $permissions = self::categoryPermissions();

        if (!is_array($permissions)) {
            return $permissions;
        }

        $permissions = array_merge($permissions, $categoryIDs);
        self::$categoryPermissions = array_unique($permissions);

        return self::$categoryPermissions;
    }

    /**
     * Clear the category-view permissions cache.
     */
    public static function clearCategoryPermissions(): void
    {
        self::$categoryPermissions = null;
    }

    /**
     * Identify current user's category-view permissions and set as local array.
     *
     * @param bool $escape Prepends category IDs with @
     * @param bool $forceRefresh Reset the cache and pull fresh permission values.
     * @return array Protected local _CategoryPermissions
     * @since 2.0.0
     * @access public
     *
     */
    public static function categoryPermissions($escape = false, $forceRefresh = false)
    {
        if (is_null(self::$categoryPermissions) || $forceRefresh) {
            $session = Gdn::session();

            if (is_object($session->User) && $session->User->Admin) {
                self::$categoryPermissions = true;
            } elseif (c("Garden.Permissions.Disabled.Category")) {
                if ($session->checkPermission("Vanilla.Discussions.View")) {
                    self::$categoryPermissions = true;
                } else {
                    self::$categoryPermissions = []; // no permission
                }
            } else {
                $ids = CategoryModel::instance()->getVisibleCategoryIDs();

                if ($ids === true) {
                    self::$categoryPermissions = true;
                } else {
                    self::$categoryPermissions = [];
                    foreach ($ids as $id) {
                        self::$categoryPermissions[] = ($escape ? "@" : "") . $id;
                    }
                }
            }
        }

        return self::$categoryPermissions;
    }

    /**
     * Get discussion fragments for one or more IDs.
     *
     * @param int[] $ids The discussion IDs to search for.
     * @param array $options Custom options for the operation.
     * @return array
     * @throws ValidationException If there was an error encountered during fragment schema validation.
     */
    public function fetchFragments(array $ids, array $options = []): array
    {
        $ids = array_values($ids);
        $rows = $this->getWhere(["DiscussionID" => $ids])->resultArray();
        $fragments = [];

        $schema = SchemaFactory::get(PostFragmentSchema::class);
        foreach ($rows as $row) {
            $id = $row["DiscussionID"] ?? null;
            if ($id === null) {
                continue;
            }
            $fragments[$id] = $schema->validate($row);
        }

        return $fragments;
    }

    public function fetchPageInfo($url, $throwError = false)
    {
        $pageInfo = fetchPageInfo($url, 3);

        $title = val("Title", $pageInfo, "");
        if ($title == "") {
            if ($throwError) {
                throw new Gdn_UserException(t("The page didn't contain any information."));
            }

            $title = formatString(t("Undefined discussion subject."), ["Url" => $url]);
        } else {
            if ($strip = c("Vanilla.Embed.StripPrefix")) {
                $title = stringBeginsWith($title, $strip, true, true);
            }

            if ($strip = c("Vanilla.Embed.StripSuffix")) {
                $title = stringEndsWith($title, $strip, true, true);
            }
        }
        $title = trim($title);

        $description = val("Description", $pageInfo, "");
        $images = val("Images", $pageInfo, []);
        $body = formatString(t("EmbeddedDiscussionFormat"), [
            "Title" => $title,
            "Excerpt" => $description,
            "Image" => count($images) > 0 ? img(val(0, $images), ["class" => "LeftAlign"]) : "",
            "Url" => $url,
        ]);
        if ($body == "") {
            $body = $url;
        }
        if ($body == "") {
            $body = formatString(t("EmbeddedNoBodyFormat."), ["Url" => $url]);
        }

        $result = [
            "Name" => $title,
            "Body" => $body,
            "Format" => "Html",
        ];

        return $result;
    }

    /**
     * Get the count of discussions for an individual category.
     *
     * @param int|array $categoryID The category to get the count of or an array of category IDs to get the count of.
     * @return int Returns the count of discussions.
     */
    public function getCountForCategory($categoryID)
    {
        $count = 0;
        foreach ((array) $categoryID as $id) {
            $category = CategoryModel::categories((int) $id);
            $count += (int) val("CountDiscussions", $category, 0);
        }

        return $count;
    }

    /**
     * Count how many discussions match the given criteria.
     *
     * @param array $wheres SQL conditions.
     * @param null $unused Not used.
     * @return int Number of discussions.
     * @throws Exception
     * @since 2.0.0
     * @access public
     *
     */
    public function getCount($wheres = [], $unused = null)
    {
        $wheres = $this->normalizeQuery($wheres, "d");

        // Get permissions.
        $perms = self::categoryPermissions();

        // No permissions... That is sad :(
        if (!$perms) {
            return 0;
        }

        $wheres = $this->combineWheres($this->getWheres(), $wheres);

        $this->EventArguments["Wheres"] = &$wheres;
        $this->fireEvent("BeforeGetCount"); // @see 'BeforeGet' for consistency in count vs. results

        // This should not happen but let's throw a warning just in case.
        if (!is_array($wheres)) {
            trigger_error("Wheres needs to be an array.", E_USER_DEPRECATED);
            $wheres = [];
        }

        $hasWhere = !empty($wheres);
        $whereOnCategories = $hasWhere && isset($wheres["d.CategoryID"]);
        $whereOnCategoriesOnly = $whereOnCategories && count($wheres) === 1;

        // We have access to everything and are requesting only by categories. Let's use the cache!
        if ($perms === true && $whereOnCategoriesOnly) {
            return $this->getCountForCategory($wheres["d.CategoryID"]);
        }

        // Only keep the categories we actually want and have permission to.
        if ($whereOnCategories && is_array($perms)) {
            $categoryIDs = (array) $wheres["d.CategoryID"];
            if ($categoryIDs) {
                $perms = array_intersect($categoryIDs, $perms);
            }
        }

        // Use the cache if we are requesting only by categories or have no where at all.
        // In those cases we are gonna use the cached count on the categories we have permission to.
        if ($whereOnCategoriesOnly) {
            $count = 0;
            foreach ($perms as $categoryID) {
                $category = CategoryModel::categories($categoryID);
                if ($category) {
                    $count += (int) $category["CountDiscussions"];
                }
            }

            return $count;
        } elseif (!$hasWhere) {
            $categories = CategoryModel::instance()->getVisibleCategories();
            if ($categories === true) {
                $categories = CategoryModel::categories();
            }

            $count = 0;
            foreach ($categories as $category) {
                $count += (int) $category["CountDiscussions"];
            }

            return $count;
        }

        // Filter the results by permissions.
        if (is_array($perms)) {
            $this->SQL->whereIn("d.CategoryID", $perms);
        }

        if ($wheres[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false) {
            $this->applyDirtyWheres("d");
        }

        $query = $this->SQL
            ->select("d.DiscussionID", "count", "CountDiscussions")
            ->from("Discussion d")
            ->join("Category c", "d.CategoryID = c.CategoryID");

        $hasUserDiscussionFilter = false;
        foreach ($wheres as $key => $val) {
            if (stringBeginsWith($key, "ud.")) {
                $hasUserDiscussionFilter = true;
            }
        }

        if ($hasUserDiscussionFilter) {
            $query = $query->join(
                "UserDiscussion ud",
                "d.DiscussionID = ud.DiscussionID and ud.UserID = " . Gdn::session()->UserID,
                "left"
            );
        }
        return $query
            ->where($wheres)
            ->get()
            ->firstRow()->CountDiscussions;
    }

    /**
     * Count how many discussions match the given criteria.
     *
     * @param array|'' $wheres SQL conditions.
     * @return int Number of discussions.
     * @throws Exception
     * @since 2.0.0
     * @deprecated since 2.3
     *
     */
    public function getUnreadCount($wheres = "")
    {
        if (is_array($wheres) && count($wheres) == 0) {
            $wheres = "";
        }

        // Check permission and limit to categories as necessary
        $perms = self::categoryPermissions();

        if (!$wheres || (count($wheres) == 1 && isset($wheres["d.CategoryID"]))) {
            // Grab the counts from the faster category cache.
            if (isset($wheres["d.CategoryID"])) {
                $categoryIDs = (array) $wheres["d.CategoryID"];
                if ($perms === false) {
                    $categoryIDs = [];
                } elseif (is_array($perms)) {
                    $categoryIDs = array_intersect($categoryIDs, $perms);
                }

                if (count($categoryIDs) == 0) {
                    return 0;
                } else {
                    $perms = $categoryIDs;
                }
            }

            $categories = CategoryModel::categories();
            $count = 0;

            foreach ($categories as $cat) {
                if (is_array($perms) && !in_array($cat["CategoryID"], $perms)) {
                    continue;
                }
                $count += (int) $cat["CountDiscussions"];
            }

            return $count;
        }

        if ($perms !== true) {
            $this->SQL->whereIn("c.CategoryID", $perms);
        }

        $this->EventArguments["Wheres"] = &$wheres;
        $this->fireEvent("BeforeGetUnreadCount"); // @see 'BeforeGet' for consistency in count vs. results

        $this->SQL
            ->select("d.DiscussionID", "count", "CountDiscussions")
            ->from("Discussion d")
            ->join("Category c", "d.CategoryID = c.CategoryID")
            ->join(
                "UserDiscussion w",
                "d.DiscussionID = w.DiscussionID and w.UserID = " . Gdn::session()->UserID,
                "left"
            )
            ->where("d.CountComments >", "COALESCE(w.CountComments, 0)", true, false)
            ->where($wheres);

        $result = $this->SQL->get()->firstRow()->CountDiscussions;

        return $result;
    }

    /**
     * Get data for a single discussion by ForeignID.
     *
     * @param int $foreignID Foreign ID of discussion to get.
     * @param string $type The record type or an empty string for any record type.
     * @return stdClass SQL result.
     * @throws Exception
     * @since 2.0.18
     */
    public function getForeignID($foreignID, $type = "")
    {
        $hash = foreignIDHash($foreignID);
        $session = Gdn::session();
        $this->fireEvent("BeforeGetForeignID");
        $this->SQL
            ->select("d.*")
            ->select("ca.Name", "", "Category")
            ->select("ca.UrlCode", "", "CategoryUrlCode")
            ->select("ca.PermissionCategoryID")
            ->select("w.UserID", "", "WatchUserID")
            ->select("w.DateLastViewed, w.Dismissed, w.Bookmarked")
            ->select("w.CountComments", "", "CountCommentWatch")
            ->select("w.Participated")
            ->select("d.DateLastComment", "", "LastDate")
            ->select("d.LastCommentUserID", "", "LastUserID")
            ->select("lcu.Name", "", "LastName")
            ->select("iu.Name", "", "InsertName")
            ->select("iu.Photo", "", "InsertPhoto")
            ->from("Discussion d")
            ->join("Category ca", "d.CategoryID = ca.CategoryID", "left")
            ->join("UserDiscussion w", "d.DiscussionID = w.DiscussionID and w.UserID = " . $session->UserID, "left")
            ->join("User iu", "d.InsertUserID = iu.UserID", "left") // Insert user
            ->join("Comment lc", "d.LastCommentID = lc.CommentID", "left") // Last comment
            ->join("User lcu", "lc.InsertUserID = lcu.UserID", "left") // Last comment user
            ->where("d.ForeignID", $hash);

        if ($type != "") {
            PostTypeModel::whereParentPostType($this->SQL, [$type], "d.");
        }

        $discussion = $this->SQL->get()->firstRow();

        if (c("Vanilla.Views.Denormalize", false)) {
            $this->addDenormalizedViews($discussion);
        }

        return $discussion;
    }

    /**
     * Get data for a single discussion by ID.
     *
     * @param int $id Unique ID of discussion to get.
     * @param string $datasetType One of the **DATASET_TYPE_*** constants.
     * @param array $options An array of extra options for the query.
     * @return mixed SQL result.
     * @throws Exception
     */
    public function getID($id, $datasetType = DATASET_TYPE_OBJECT, $options = [])
    {
        $session = Gdn::session();

        $selects = [];
        $this->EventArguments["Selects"] = &$selects;
        $this->fireEvent("BeforeGetID");

        $this->options($options);

        $this->SQL
            ->select("d.*")
            ->select("w.UserID", "", "WatchUserID")
            ->select("w.DateLastViewed, w.Dismissed, w.Bookmarked")
            ->select("w.CountComments", "", "CountCommentWatch")
            ->select("w.Participated")
            ->select("d.DateLastComment", "", "LastDate")
            ->select("d.LastCommentUserID", "", "LastUserID");

        // Add select of additional fields to the SQL object.
        foreach ($selects as $select) {
            $this->SQL->select($select);
        }

        $data = $this->SQL
            ->from("Discussion d")
            ->join("UserDiscussion w", "d.DiscussionID = w.DiscussionID and w.UserID = " . $session->UserID, "left")
            ->where("d.DiscussionID", $id)
            ->get();

        $this->EventArguments["Data"] = $data;
        $this->getEventManager()->fire("discussionModel_afterAddColumns", $this);

        $discussion = $data->firstRow();

        if (!$discussion) {
            return $discussion;
        }

        $this->calculate($discussion);

        // Join in the users.
        $discussion = [$discussion];
        $this->userModel->joinUsers($discussion, ["LastUserID", "InsertUserID"]);
        $discussion = $discussion[0];

        if (c("Vanilla.Views.Denormalize", false)) {
            $this->addDenormalizedViews($discussion);
        }

        return $datasetType == DATASET_TYPE_ARRAY ? (array) $discussion : $discussion;
    }

    /**
     * Get discussions that have IDs in the provided array.
     *
     * @param array $discussionIDs Array of DiscussionIDs to get.
     * @return Gdn_DataSet SQL result.
     * @throws Exception
     * @since 2.0.18
     */
    public function getIn($discussionIDs)
    {
        $session = Gdn::session();
        $this->fireEvent("BeforeGetIn");
        $result = $this->SQL
            ->select("d.*")
            ->select("ca.Name", "", "Category")
            ->select("ca.UrlCode", "", "CategoryUrlCode")
            ->select("ca.PermissionCategoryID")
            ->select("w.DateLastViewed, w.Dismissed, w.Bookmarked")
            ->select("w.CountComments", "", "CountCommentWatch")
            ->select("w.Participated")
            ->select("w.UserID", "", "WatchUserID")
            ->select("d.DateLastComment", "", "LastDate")
            ->select("d.LastCommentUserID", "", "LastUserID")
            ->select("lcu.Name", "", "LastName")
            ->select("iu.Name", "", "InsertName")
            ->select("iu.Photo", "", "InsertPhoto")
            ->from("Discussion d")
            ->join("Category ca", "d.CategoryID = ca.CategoryID", "left")
            ->join("UserDiscussion w", "d.DiscussionID = w.DiscussionID and w.UserID = " . $session->UserID, "left")
            ->join("User iu", "d.InsertUserID = iu.UserID", "left") // Insert user
            ->join("Comment lc", "d.LastCommentID = lc.CommentID", "left") // Last comment
            ->join("User lcu", "lc.InsertUserID = lcu.UserID", "left") // Last comment user
            ->whereIn("d.DiscussionID", $discussionIDs)
            ->get();

        // Splitting views off to side table. Aggregate cached keys here.
        if (c("Vanilla.Views.Denormalize", false)) {
            $this->addDenormalizedViews($result);
        }

        return $result;
    }

    /**
     * Get discussions sort order based on config and optional user preference.
     *
     * @return string Column name.
     */
    public static function getSortField()
    {
        deprecated("getSortField", "getOrderBy");
        $sortField = c("Vanilla.Discussions.SortField", "d.DateLastComment");
        if (c("Vanilla.Discussions.UserSortField")) {
            $sortField = Gdn::session()->getPreference("Discussions.SortField", $sortField);
        }

        return $sortField;
    }

    /**
     * Get views fallback.
     *
     * @param int $discussionID
     * @return mixed|null
     * @throws Exception
     */
    public static function getViewsFallback($discussionID)
    {
        // Not found. Check main table.
        $views = Gdn::sql()
            ->select("CountViews")
            ->from("Discussion")
            ->where("DiscussionID", $discussionID)
            ->get()
            ->value("CountViews", null);

        // Found. Insert into denormalized table and return.
        if (!is_null($views)) {
            return $views;
        }

        return null;
    }

    /**
     * If a query asks for only discussion-type records, modifies the query to get 'discussion' and null values.
     *
     * @param array $wheres
     * @deprecated
     */
    public function modifyTypeDiscussionQueryClause(&$wheres)
    {
        if (isset($wheres["d.Type"]) && strtolower($wheres["d.Type"]) === "discussion") {
            $this->SQL
                ->beginWhereGroup()
                ->where("d.Type", "discussion")
                ->orWhere("d.Type is null")
                ->endWhereGroup();
            unset($wheres["d.Type"]);
        }
    }

    /**
     * Marks the specified announcement as dismissed by the specified user.
     *
     * @param int $discussionID Unique ID of discussion being affected.
     * @param int $userID Unique ID of the user being affected.
     * @param bool $dismissal
     * @throws Exception
     */
    public function dismissAnnouncement($discussionID, $userID, bool $dismissal = true)
    {
        $count = $this->SQL
            ->select("UserID")
            ->from("UserDiscussion")
            ->where("DiscussionID", $discussionID)
            ->where("UserID", $userID)
            ->get()
            ->numRows();

        $countComments = $this->SQL
            ->select("CountComments")
            ->from("Discussion")
            ->where("DiscussionID", $discussionID)
            ->get()
            ->firstRow()->CountComments;

        if ($count > 0) {
            $this->SQL
                ->update("UserDiscussion")
                ->set("CountComments", $countComments)
                ->set("DateLastViewed", DateTimeFormatter::getCurrentDateTime())
                ->set("Dismissed", $dismissal)
                ->where("DiscussionID", $discussionID)
                ->where("UserID", $userID)
                ->put();
        } else {
            $this->SQL->options("Ignore", true);
            $this->SQL->insert("UserDiscussion", [
                "UserID" => $userID,
                "DiscussionID" => $discussionID,
                "CountComments" => $countComments,
                "DateLastViewed" => DateTimeFormatter::getCurrentDateTime(),
                "Dismissed" => $dismissal,
            ]);
        }
    }

    /**
     * An event firing wrapper for Gdn_Model::setField().
     *
     * @param int $rowID
     * @param string|array $property
     * @param mixed $value
     * @param bool $isDiscussionInsert Set this if we are just doing some modification after the initial insert.
     * In that case we don't want to fire any update events.
     */
    public function setField($rowID, $property, $value = false, bool $isDiscussionInsert = false)
    {
        if (!is_array($property)) {
            $property = [$property => $value];
        }

        $this->EventArguments["DiscussionID"] = $rowID;
        $this->EventArguments["SetField"] = $property;

        parent::setField($rowID, $property, $value);
        if (!$isDiscussionInsert) {
            $this->addDirtyRecord("discussion", $rowID);
        }

        if (isset($property["Score"]) || (isset($property["CountComments"]) && !isset($property["hot"]))) {
            // update hot column value
            $this->updateColumnHot(["DiscussionID" => $rowID]);
        }

        if (isset($property["Type"]) && !isset($property["postTypeID"])) {
            // For legacy compatibility, null out the postTypeID if type is being updated without postTypeID.
            $this->update(["postTypeID" => null], ["DiscussionID" => $rowID]);
        }
        $this->fireEvent("AfterSetField");
    }

    /**
     * Dispatch an update event for a discussion whose status has changed.
     *
     * @param int $discussionID
     * @return DiscussionEvent
     * @throws ValidationException
     */
    private function statusChangeUpdate(int $discussionID): DiscussionEvent
    {
        // Fetch the row again.
        $newRow = $this->getID($discussionID, DATASET_TYPE_ARRAY);

        // KLUDGE: In the future this should just be an expand that gets expanded out on discussions by default.
        $discussionEvent = $this->eventFromRow(
            (array) $newRow,
            DiscussionStatusEvent::ACTION_DISCUSSION_STATUS,
            $this->userModel->currentFragment()
        );
        return $discussionEvent;
    }

    /**
     * @return ForumAggregateModel
     * @throws ContainerException
     */
    private function aggregateModel(): ForumAggregateModel
    {
        return Gdn::getContainer()->get(ForumAggregateModel::class);
    }

    /**
     * @return PremoderationModel
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    private function premoderationModel(): PremoderationModel
    {
        return Gdn::getContainer()->get(PremoderationModel::class);
    }

    /**
     * Inserts or updates the discussion via form values.
     *
     * Events: BeforeSaveDiscussion, AfterValidateDiscussion, AfterSaveDiscussion.
     *
     * @param array $formPostValues Data sent from the form model.
     * @param array|false $settings
     * - CheckPermission - Check permissions during insert. Default true.
     *
     * @return int $discussionID Unique ID of the discussion.
     * @throws ContainerException
     * @throws NotFoundException
     * @throws NotFoundException
     * @throws PremoderationException
     */
    public function save($formPostValues, $settings = false)
    {
        // Define the primary key in this model's table.
        $settings = is_array($settings) ? $settings : [];
        $this->defineSchema();

        // Get the DiscussionID from the form to know if we are inserting or updating a record.
        $discussionID = val("DiscussionID", $formPostValues, "");
        $insert = $discussionID == "" ? true : false;
        $currentDiscussionData = $insert ? [] : $this->getID($discussionID, DATASET_TYPE_ARRAY);
        $textUpdated = false;
        // Avoid polluting validation rules between operation types (e.g. persisting insert rules for an update).
        $this->Validation->reset();
        $validation = clone $this->Validation;

        // Add & apply any extra validation rules:
        if (array_key_exists("Body", $formPostValues)) {
            $validation->applyRule("Body", "Required");
            $validation->addRule("MeAction", "function:ValidateMeAction");
            $validation->applyRule("Body", "MeAction");
            $maxCommentLength = Gdn::config("Vanilla.Comment.MaxLength");
            $minCommentLength = Gdn::config("Vanilla.Comment.MinLength");
            $ignoreMinLength = $settings["ignoreMinLength"] ?? false;
            if (is_numeric($maxCommentLength) && $maxCommentLength > 0) {
                $validation->setSchemaProperty("Body", "maxPlainTextLength", $maxCommentLength);
                $validation->applyRule("Body", "plainTextLength");
            }

            if ($minCommentLength && is_numeric($minCommentLength) && !$ignoreMinLength) {
                $validation->setSchemaProperty("Body", "MinTextLength", $minCommentLength);
                $validation->applyRule("Body", "MinTextLength");
            } else {
                // Add min length if body is required.
                if (Gdn::config("Vanilla.DiscussionBody.Required", true) && !$ignoreMinLength) {
                    $validation->setSchemaProperty("Body", "MinTextLength", 1);
                    $validation->applyRule("Body", "MinTextLength");
                }
            }
        }

        // Add title length validation
        $maxTitleLength = Gdn::config("Vanilla.Discussion.Title.MaxLength");
        if ($maxTitleLength && array_key_exists("Name", $formPostValues)) {
            if (is_numeric($maxTitleLength) && $maxTitleLength > 0) {
                $validation->setSchemaProperty("Name", "maxPlainTextLength", $maxTitleLength);
                $validation->applyRule("Name", "plainTextLength");
            }
        }

        // Validate category permissions.
        $categoryID = val("CategoryID", $formPostValues);
        if ($categoryID !== false) {
            // Trim any leading '0's to prevent inserting discussion into restricted category (https://github.com/vanilla/vanilla-patches/issues/716)
            $categoryID = ltrim($categoryID, "0");
            $checkPermission = val("CheckPermission", $settings, true);
            $category = CategoryModel::categories($categoryID);
            if (
                $category &&
                $checkPermission &&
                !CategoryModel::checkPermission($category, "Vanilla.Discussions.Add")
            ) {
                $validation->addValidationResult("CategoryID", "You do not have permission to post in this category");
            }
        }

        // See if there is a source ID.
        if (val("SourceID", $formPostValues)) {
            $discussionID = $this->SQL
                ->getWhere("Discussion", arrayTranslate($formPostValues, ["Source", "SourceID"]))
                ->value("DiscussionID");
            if ($discussionID) {
                $formPostValues["DiscussionID"] = $discussionID;
            }
        } elseif (val("ForeignID", $formPostValues)) {
            $discussionID = $this->SQL
                ->getWhere("Discussion", ["ForeignID" => $formPostValues["ForeignID"]])
                ->value("DiscussionID");
            if ($discussionID) {
                $formPostValues["DiscussionID"] = $discussionID;
            }
        }

        $this->EventArguments["Insert"] = $insert;

        if ($insert) {
            unset($formPostValues["DiscussionID"]);

            $this->addInsertFields($formPostValues);

            // The UpdateUserID used to be required. Just add it if it still is.
            if (!$this->Schema->getProperty("UpdateUserID", "AllowNull", true)) {
                $formPostValues["UpdateUserID"] = $formPostValues["InsertUserID"];
            }

            // $FormPostValues['LastCommentUserID'] = $Session->UserID;
            $formPostValues["DateLastComment"] = $formPostValues["DateInserted"];
        } else {
            // Add the update fields.
            $this->addUpdateFields($formPostValues);
            $this->EventArguments["oldDiscussion"] = $this->getID($formPostValues["DiscussionID"]);
        }

        // Pinned-to-Announce translation
        $isPinned = val("Pinned", $formPostValues, null);
        if ($isPinned !== null) {
            $announce = 0;
            $isPinned = filter_var($isPinned, FILTER_VALIDATE_BOOLEAN);
            if ($isPinned) {
                $pinLocation = strtolower(val("PinLocation", $formPostValues, "category"));
                switch ($pinLocation) {
                    case "recent":
                        $announce = self::GLOBAL_ANNOUNCEMENT;
                        break;
                    default:
                        $announce = self::CATEGORY_ANNOUNCEMENT;
                }
            }
            $formPostValues["Announce"] = $announce;
            unset($announce);
        }

        // Set checkbox values to zero if they were unchecked
        if (val("Announce", $formPostValues, "") === false) {
            $formPostValues["Announce"] = 0;
        }

        if (val("Closed", $formPostValues, "") === false) {
            $formPostValues["Closed"] = 0;
        }

        if (val("Sink", $formPostValues, "") === false) {
            $formPostValues["Sink"] = 0;
        }

        if (
            (!key_exists("internalStatusID", $formPostValues) || !$formPostValues["internalStatusID"]) &&
            $discussionID === ""
        ) {
            $internalStatus = $this->discussionStatusModel->getDefaultInternalStatus();
            $formPostValues["internalStatusID"] = val("statusID", $internalStatus, 0);
        }

        //	Prep and fire event
        $this->EventArguments["FormPostValues"] = &$formPostValues;
        $this->EventArguments["DiscussionID"] = $discussionID;
        $this->EventArguments["Validation"] = $validation;
        $this->fireEvent("BeforeSaveDiscussion");

        // Validate the form posted values
        $validation->validate($formPostValues, $insert);
        // We are merging validation results for backward compatibility.
        $validationResults = array_merge($this->Validation->results(), $validation->results());
        $this->Validation->setResults($validationResults);

        // If the body is not required, remove it's validation errors.
        $bodyRequired = c("Vanilla.DiscussionBody.Required", true);
        if (!$bodyRequired && array_key_exists("Body", $validationResults)) {
            unset($validationResults["Body"]);
        }

        if (count($validationResults) == 0) {
            // Backward compatible check for flood control
            if (!val("SpamCheck", $this, true)) {
                deprecated("DiscussionModel->SpamCheck attribute", "FloodControlTrait->setFloodControlEnabled()");
                $this->setFloodControlEnabled(false);
            }

            // If the post is new and validates, make sure the user isn't spamming
            if (
                !$insert ||
                (($settings["skipSpamCheck"] ?? false) ||
                    !$this->checkUserSpamming(Gdn::session()->UserID, $this->floodGate))
            ) {
                $forcedFormat = $formPostValues["forcedFormat"] ?? false;
                // Get all fields on the form that relate to the schema
                $fields = $validation->schemaValidationFields();

                // Check for spam.
                if (!FeatureFlagHelper::featureEnabled("escalations")) {
                    $spam = SpamModel::isSpam("Discussion", $fields, ["action" => "update"]);
                    if ($spam) {
                        return SPAM;
                    }
                }

                // Get DiscussionID if one was sent
                $discussionID = intval(val("DiscussionID", $fields, 0));

                // Remove the primary key from the fields for saving.
                unset($fields["DiscussionID"]);
                if ($discussionID > 0) {
                    // Updating
                    $stored = $this->getID($discussionID, DATASET_TYPE_OBJECT);
                    if (!$stored) {
                        throw new NotFoundException("Discussion", [
                            "discussionID" => $discussionID,
                        ]);
                    }

                    // Make sure that the discussion get formatted in the method defined by Garden.
                    if (c("Garden.ForceInputFormatter")) {
                        $fields["Format"] = Gdn::config("Garden.InputFormatter", "");
                    }

                    if (!($settings["skipSpamCheck"] ?? false)) {
                        if (FeatureFlagHelper::featureEnabled("escalations")) {
                            $premodItem = new PremoderationItem(
                                userID: $this->sessionInterface->UserID,
                                userName: $this->sessionInterface->User->Name,
                                userEmail: $this->sessionInterface->User->Email,
                                recordName: $fields["Name"] ?? $stored->Name,
                                recordBody: $fields["Body"] ?? $stored->Body,
                                recordFormat: $fields["Format"] ?? $stored->Format,
                                isEdit: true,
                                placeRecordType: "category",
                                placeRecordID: $fields["CategoryID"] ?? $stored->CategoryID,
                                recordType: "discussion",
                                recordID: $discussionID,
                                rawRow: $fields
                            );
                            $this->premoderationModel()->premoderateItem($premodItem);
                        } else {
                            $isValid = true;
                            $invalidReturnType = false;
                            $insertUserID = val("InsertUserID", $stored);
                            $dateInserted = val("DateInserted", $stored);
                            $this->EventArguments["DiscussionData"] = array_merge($fields, [
                                "DiscussionID" => $discussionID,
                                "InsertUserID" => $insertUserID,
                                "DateInserted" => $dateInserted,
                            ]);
                            $this->EventArguments["IsValid"] = &$isValid;
                            $this->EventArguments["InvalidReturnType"] = &$invalidReturnType;
                            $this->fireEvent("AfterValidateDiscussion");

                            if (!$isValid) {
                                return $invalidReturnType;
                            }
                        }
                    }

                    // The primary key was removed from the form fields, but we need it for logging.
                    LogModel::logChange("Edit", "Discussion", array_merge($fields, ["DiscussionID" => $discussionID]));

                    self::serializeRow($fields);
                    $fields["hot"] = $this->getHotCalculationExpression();
                    $oldCategoryID = $stored->CategoryID ?? null;
                    $newCategoryID = $fields["CategoryID"] ?? null;
                    $hasCategoryUpdate =
                        $oldCategoryID !== null && $newCategoryID !== null && $oldCategoryID !== $newCategoryID;
                    $textUpdated =
                        $currentDiscussionData["Name"] !== ($fields["Name"] ?? $currentDiscussionData["Name"]) ||
                        $currentDiscussionData["Body"] !== ($fields["Body"] ?? $currentDiscussionData["Body"]);

                    $this->SQL->put($this->Name, $fields, [$this->PrimaryKey => $discussionID]);

                    if ($hasCategoryUpdate) {
                        $oldCategory = CategoryModel::categories($oldCategoryID);
                        $newCategory = CategoryModel::categories($newCategoryID);
                        $this->aggregateModel()->handleDiscussionMove((array) $stored, $oldCategory, $newCategory);
                        $this->getEventManager()->dispatch(
                            new BulkUpdateEvent(
                                "comment",
                                [
                                    "discussionID" => (int) $discussionID,
                                ],
                                [
                                    "categoryID" => (int) $newCategoryID,
                                ]
                            )
                        );
                    }
                } else {
                    // Inserting.
                    $format = $fields["Format"] ?? null;
                    if (!$format || c("Garden.ForceInputFormatter")) {
                        $fields["Format"] = $forcedFormat && $format ? $format : c("Garden.InputFormatter", "");
                    }

                    if (PostTypeModel::isPostTypesFeatureEnabled() && !isset($fields["postTypeID"])) {
                        $fields["Type"] ??= "Discussion";
                        $postTypes = CategoryModel::getAllowedPostTypeData();
                        foreach ($postTypes as $postType) {
                            if (($postType["name"] ?? null) === $fields["Type"]) {
                                $fields["postTypeID"] = $postType["postTypeID"];
                            }
                        }
                    }

                    // Check for approval
                    if (!($settings["skipSpamCheck"] ?? false)) {
                        if (FeatureFlagHelper::featureEnabled("escalations")) {
                            $premodItem = new PremoderationItem(
                                userID: $this->sessionInterface->UserID,
                                userName: $this->sessionInterface->User->Name,
                                userEmail: $this->sessionInterface->User->Email,
                                recordName: $fields["Name"],
                                recordBody: $fields["Body"],
                                recordFormat: $fields["Format"],
                                isEdit: false,
                                placeRecordType: "category",
                                placeRecordID: $fields["CategoryID"],
                                recordType: "discussion",
                                recordID: null,
                                rawRow: $fields
                            );
                            $this->premoderationModel()->premoderateItem($premodItem);
                        } else {
                            $approvalRequired = checkRestriction("Vanilla.Approval.Require");
                            if ($approvalRequired && !val("Verified", Gdn::session()->User)) {
                                LogModel::insert("Pending", "Discussion", $fields);

                                return UNAPPROVED;
                            }

                            $isValid = true;
                            $invalidReturnType = false;
                            $this->EventArguments["DiscussionData"] = $fields;
                            $this->EventArguments["IsValid"] = &$isValid;
                            $this->EventArguments["InvalidReturnType"] = &$invalidReturnType;
                            $this->fireEvent("AfterValidateDiscussion");

                            if (!$isValid) {
                                return $invalidReturnType;
                            }
                        }
                    }

                    // Create discussion
                    $this->serializeRow($fields);
                    $fields["hot"] = $this->getHotCalculationExpression();
                    $discussionID = $this->SQL->insert($this->Name, $fields);
                    $fields["DiscussionID"] = $discussionID;

                    // Update the user's discussion count.
                    $insertUser = $this->userModel->getID($fields["InsertUserID"]);
                    $this->updateUserDiscussionCount(
                        $fields["InsertUserID"],
                        val("CountDiscussions", $insertUser, 0) > 100
                    );

                    // Mark the user as participated and update DateLastViewed.
                    $this->SQL->replace(
                        "UserDiscussion",
                        ["Participated" => 1, "DateLastViewed" => DateTimeFormatter::getCurrentDateTime()],
                        ["DiscussionID" => $discussionID, "UserID" => val("InsertUserID", $fields)]
                    );

                    // Assign the new DiscussionID to the comment before saving.
                    $formPostValues["IsNewDiscussion"] = true;
                    $formPostValues["DiscussionID"] = $discussionID;

                    $discussion = $this->getID($discussionID, DATASET_TYPE_ARRAY);
                    if ($discussion) {
                        $this->aggregateModel()->handleDiscussionInsert($discussion);
                        $this->EventArguments["DiscussionData"] = $discussion;
                        // Send notifications
                        $notificationGenerator = Gdn::getContainer()->get(
                            \Vanilla\Models\CommunityNotificationGenerator::class
                        );
                        $notificationGenerator->notifyNewDiscussion($discussion);
                    }
                }
                if (!$insert && !empty($formPostValues["Announce"]) && !$currentDiscussionData["Announce"]) {
                    // If we are updating `announce` via update , make sure we send a notification.
                    $discussion = $this->getID($discussionID, DATASET_TYPE_ARRAY);
                    $this->getEventManager()->fire("discussionAnnounced", $discussion);
                }

                $discussion = $discussion ?? $this->getID($discussionID, DATASET_TYPE_ARRAY);
                if (isset($discussion["postTypeID"], $formPostValues["postMeta"])) {
                    $this->postMetaModel->updatePostMeta("discussion", $discussionID, $formPostValues["postMeta"]);
                }

                $this->calculateMediaAttachments($discussionID, !$insert);

                // Fire an event that the discussion was saved.
                $this->EventArguments["FormPostValues"] = $formPostValues;
                $this->EventArguments["Fields"] = $fields;
                $this->EventArguments["DiscussionID"] = $discussionID;
                $this->fireEvent("AfterSaveDiscussion");

                $event = new MachineTranslationEvent("discussion", [$discussionID]);
                $this->getEventManager()->dispatch($event);

                $action = $insert ? DiscussionEvent::ACTION_INSERT : DiscussionEvent::ACTION_UPDATE;
                \Gdn::scheduler()->addJobDescriptor(
                    new NormalJobDescriptor(DeferredResourceEventJob::class, [
                        "id" => $discussionID,
                        "model" => DiscussionModel::class,
                        "action" => $action,
                        "textUpdated" => $textUpdated,
                    ])
                );
            }
        }

        return $discussionID;
    }

    /**
     * Generate a discussion event object, based on a database row.
     *
     * @param array $row
     * @param string $action
     * @param null $sender
     * @return DiscussionEvent
     * @throws ContainerException
     * @throws ValidationException
     */
    public function eventFromRow(array $row, string $action, $sender = null, bool $isInternal = false): ResourceEvent
    {
        $this->userModel->expandUsers($row, ["InsertUserID", "LastUserID"]);
        $discussion = $this->normalizeRow($row, true);
        $this->tagModel->expandTagIDs($row);
        $discussion = $this->schema()->validate($discussion);

        if ($sender) {
            $senderSchema = new UserFragmentSchema();
            $sender = $senderSchema->validate($sender);
        }

        $result = new DiscussionEvent($action, ["discussion" => $discussion], $sender);
        $statusID = $isInternal ? $result->getInternalStatusID() : $result->getStatusID();
        $statusFragment = $this->discussionStatusModel->tryGetStatusFragment($statusID);
        if ($statusFragment !== null) {
            $result->setStatus($statusFragment);
        }
        // Schema validation may have stripped out the internalStatusID depending on the session user's permissions.
        if (isset($result->getPayload()["discussion"]["internalStatusID"])) {
            $internalStatusFragment = $this->discussionStatusModel->tryGetStatusFragment(
                $result->getInternalStatusID()
            );
            if ($internalStatusFragment !== null) {
                $result->setInternalStatus($internalStatusFragment);
            }
        }
        if (isset($row["oldStatusID"])) {
            $result->setOldStatusID($row["oldStatusID"]);
        }

        if (isset($this->EventArguments["oldDiscussion"])) {
            $result->setSourceAnnounce($this->EventArguments["oldDiscussion"]->Announce ?? 0);
        }

        return $result;
    }

    /**
     * Update a user's discussion count.
     *
     * @param int $userID The user to calculate.
     * @param bool $inc Whether to increment of recalculate from scratch.
     * @throws Exception
     */
    public function updateUserDiscussionCount($userID, $inc = false)
    {
        $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
        if ($inc) {
            $countDiscussions = val("CountDiscussions", $user);
            // Increment if 100 or greater; Recalculate on 120, 140 etc.
            if ($countDiscussions >= 100 && $countDiscussions % 20 !== 0) {
                $this->SQL
                    ->update("User")
                    ->set("CountDiscussions", "CountDiscussions + 1", false)
                    ->where("UserID", $userID)
                    ->put();

                $this->userModel->updateUserCache($userID, "CountDiscussions", $countDiscussions + 1);
                $this->addDirtyRecord("user", $userID);

                return;
            }
        }

        $countDiscussions = $this->SQL
            ->select("DiscussionID", "count", "CountDiscussions")
            ->from("Discussion")
            ->where("InsertUserID", $userID)
            ->get()
            ->value("CountDiscussions", 0);

        // Save the count to the user table
        $this->userModel->setField($userID, "CountDiscussions", $countDiscussions);
    }

    /**
     * Update and get bookmark count for the specified user.
     *
     * @param int $userID Unique ID of user to update.
     * @return int Total number of bookmarks user has.
     */
    public function setUserBookmarkCount($userID)
    {
        $count = $this->userBookmarkCount($userID);
        $this->userModel->setField($userID, "CountBookmarks", $count);

        return $count;
    }

    /**
     * Updates a discussion field.
     *
     * By default, this toggles the specified between '1' and '0'. If $forceValue
     * is provided, the field is set to this value instead. An example use is
     * announcing and unannouncing a discussion.
     *
     * @param int $rowID Unique ID of discussion being updated.
     * @param string $property Name of field to be updated.
     * @param mixed $forceValue If set, overrides toggle behavior with this value.
     * @return mixed Value that was ultimately set for the field.
     */
    public function setProperty($rowID, $property, $forceValue = null)
    {
        if ($forceValue !== null) {
            $value = $forceValue;
        } else {
            $discussion = $this->getID($rowID);
            $value = $discussion->$property == "1" ? "0" : "1";
        }
        $this->SQL
            ->update("Discussion")
            ->set($property, $value)
            ->where("DiscussionID", $rowID)
            ->put();

        return $value;
    }

    /**
     * Determine if we should use "view denormalization".
     *
     * This is when we defer writeback of a discussion's 'CountViews' to a buffer in memcached.
     */
    private function shouldUseDenormalizedViews(): bool
    {
        return c("Vanilla.Views.Denormalize", false) && Gdn::cache()->activeEnabled();
    }

    /**
     * Increments view count for the specified discussion.
     *
     * @param int $discussionID Unique ID of discussion to get +1 view.
     */
    public function addView($discussionID)
    {
        $incrementBy = 0;
        if ($this->shouldUseDenormalizedViews()) {
            $writebackLimit = c("Vanilla.Views.DenormalizeWriteback", 10);
            $cacheKey = sprintf(DiscussionModel::CACHE_DISCUSSIONVIEWS, $discussionID);

            // Increment.
            $views = Gdn::cache()->increment($cacheKey, 1, [Gdn_Cache::FEATURE_INITIAL => 1]);

            // Every X views, writeback to Discussions
            if ($views % $writebackLimit == 0) {
                $incrementBy = floor($views / $writebackLimit) * $writebackLimit;
                Gdn::cache()->decrement($cacheKey, $incrementBy);
            }
        } else {
            $incrementBy = 1;
        }

        if ($incrementBy) {
            $this->SQL
                ->update("Discussion")
                ->set("CountViews", "CountViews + {$incrementBy}", false)
                ->where("DiscussionID", $discussionID)
                ->put();
        }
    }

    /**
     * Bookmarks (or unbookmarks) a discussion for the specified user.
     *
     * @param int $discussionID The unique id of the discussion.
     * @param int $userID The unique id of the user.
     * @param bool|null $bookmarked Whether or not to bookmark or unbookmark. Pass null to toggle the bookmark.
     * @return bool The new value of bookmarked.
     */
    public function bookmark($discussionID, $userID, $bookmarked = null)
    {
        // Get the current user discussion record.
        $userDiscussion = $this->SQL
            ->getWhere("UserDiscussion", ["DiscussionID" => $discussionID, "UserID" => $userID])
            ->firstRow(DATASET_TYPE_ARRAY);

        if ($userDiscussion) {
            if ($bookmarked === null) {
                $bookmarked = !$userDiscussion["Bookmarked"];
            }

            // Update the bookmarked value.
            $this->SQL->put(
                "UserDiscussion",
                ["Bookmarked" => (int) $bookmarked],
                ["DiscussionID" => $discussionID, "UserID" => $userID]
            );
        } else {
            if ($bookmarked === null) {
                $bookmarked = true;
            }

            // Insert the new bookmarked value.
            $this->SQL->options("Ignore", true)->insert("UserDiscussion", [
                "UserID" => $userID,
                "DiscussionID" => $discussionID,
                "Bookmarked" => (int) $bookmarked,
            ]);
        }

        $this->recalculateBookmarkCounts($discussionID, [$userID]);

        $this->EventArguments["DiscussionID"] = $discussionID;
        $this->EventArguments["UserID"] = $userID;
        $this->EventArguments["Bookmarked"] = $bookmarked;
        $this->fireEvent("AfterBookmark");

        return (bool) $bookmarked;
    }

    /**
     * Get the count of discussions a user has participated in.
     *
     * @param int|null $userID
     *
     * @return int
     * @throws Exception
     */
    public function getCountParticipated(?int $userID = null): int
    {
        $session = $this->getSessionInterface();
        if ($userID === null) {
            if (!$session->isValid()) {
                throw new Exception(t("Could not get participated discussions for non logged-in user."));
            }
            $userID = $session->UserID;
        }

        $cache = \Gdn::cache();
        $key = "discussion/participatedCount/" . $userID;
        $count = $cache->get($key);
        if ($count === \Gdn_Cache::CACHEOP_FAILURE) {
            $sql = clone $this->SQL;
            $sql->reset();

            $sqlResult = $sql
                ->select("c.DiscussionID", "distinct", "NumDiscussions")
                ->from("Comment c")
                ->where("c.InsertUserID", $userID)
                // We dont' do a full count here, because it can easily time out in MySQL.
                ->groupBy("c.DiscussionID")
                ->get();

            if (!($sqlResult instanceof Gdn_DataSet)) {
                $count = 0;
            } else {
                $count = $sqlResult->numRows();
            }

            $cache->store($key, $count, [
                \Gdn_Cache::FEATURE_EXPIRY => 60 * 10, // 10 minutes.
            ]);
        }
        return $count;
    }

    /**
     * Bookmarks (or unbookmarks) a discussion for specified user.
     *
     * Events: AfterBookmarkDiscussion.
     *
     * @param int $discussionID Unique ID of discussion to (un)bookmark.
     * @param int $userID Unique ID of user doing the (un)bookmarking.
     * @param object &$discussion Discussion data.
     * @return bool Current state of the bookmark (TRUE for bookmarked, FALSE for unbookmarked).
     */
    public function bookmarkDiscussion($discussionID, $userID, &$discussion = null)
    {
        $state = "1";

        $discussionData = $this->SQL
            ->select("d.*")
            ->select("w.DateLastViewed, w.Dismissed, w.Bookmarked")
            ->select("w.CountComments", "", "CountCommentWatch")
            ->select("w.UserID", "", "WatchUserID")
            ->select("w.Participated")
            ->select("d.DateLastComment", "", "LastDate")
            ->select("d.LastCommentUserID", "", "LastUserID")
            ->select("lcu.Name", "", "LastName")
            ->from("Discussion d")
            ->join("UserDiscussion w", "d.DiscussionID = w.DiscussionID and w.UserID = $userID", "left")
            ->join("User lcu", "d.LastCommentUserID = lcu.UserID", "left") // Last comment user
            ->where("d.DiscussionID", $discussionID)
            ->get();

        $this->addDiscussionColumns($discussionData);
        $discussion = $discussionData->firstRow();

        if ($discussion->WatchUserID == "") {
            $this->SQL->options("Ignore", true);
            $this->SQL->insert("UserDiscussion", [
                "UserID" => $userID,
                "DiscussionID" => $discussionID,
                "Bookmarked" => $state,
            ]);
            $discussion->Bookmarked = true;
        } else {
            $state = $discussion->Bookmarked == "1" ? "0" : "1";
            $this->SQL
                ->update("UserDiscussion")
                ->set("Bookmarked", $state)
                ->where("UserID", $userID)
                ->where("DiscussionID", $discussionID)
                ->put();
            $discussion->Bookmarked = $state;
        }

        // Update the cached bookmark count on the discussion
        $bookmarkCount = $this->bookmarkCount($discussionID);
        $this->SQL
            ->update("Discussion")
            ->set("CountBookmarks", $bookmarkCount)
            ->where("DiscussionID", $discussionID)
            ->put();
        $this->CountDiscussionBookmarks = $bookmarkCount;

        // Prep and fire event
        $this->EventArguments["Discussion"] = $discussion;
        $this->EventArguments["State"] = $state;
        $this->fireEvent("AfterBookmarkDiscussion");

        return $state == "1" ? true : false;
    }

    /**
     * Recalculate userIDs for a discussion in bulk.
     *
     * Note: This may encounter difficulties in 50k+ users have bookmarked a discussion.
     * If this edge case ever comes to pass, or we want to make something recalculate bookmark counts for all users
     * then something more robust that batches users will need to be done.
     * An event like that would probably be better paired with an entire cache flush though.
     *
     * @param int $discussionID The discussion ID to recalculate users for.
     * @param int[] $userIDs Optional: Only recalculate bookmark counts for these users.
     * If omitted, recalculates bookmark counts for all users that bookmarked the discussion.
     */
    public function recalculateBookmarkCounts(int $discussionID, ?array $userIDs = null)
    {
        $userModel = \Gdn::userModel();
        $prefix = $this->Database->DatabasePrefix;
        $userIDsToUpdate = $userIDs ?? $this->getBookmarkUserIDs($discussionID);

        if (count($userIDsToUpdate) === 0) {
            // Nothing to do here.
            return;
        }

        $userIDPlaceholder = $this->SQL->parameterizeGroupValue($userIDsToUpdate);

        $sql = <<<SQL
            update {$prefix}User u
            left join (
                select UserID, COUNT(UserID) as CountBookmarks
                from {$prefix}UserDiscussion
                where Bookmarked = true
                and UserID in {$userIDPlaceholder}
                group by UserID
            ) ud on u.UserID = ud.UserID
            set
                u.CountBookmarks = coalesce(ud.CountBookmarks, 0)
            where u.UserID in {$userIDPlaceholder}
SQL;
        $this->Database->query($sql, array_merge($userIDsToUpdate, $userIDsToUpdate));

        // Many caches to clear.
        foreach ($userIDsToUpdate as $userID) {
            $userModel->clearCache($userID, ["user"]);
        }
    }

    /**
     * Gets number of bookmarks specified discussion has (all users).
     *
     * @param int $discussionID Unique ID of discussion for which to tally bookmarks.
     * @return int Total number of bookmarks.
     */
    public function bookmarkCount($discussionID)
    {
        $data = $this->SQL
            ->select("DiscussionID", "count", "Count")
            ->from("UserDiscussion")
            ->where("DiscussionID", $discussionID)
            ->where("Bookmarked", "1")
            ->get()
            ->firstRow();

        return $data !== false ? $data->Count : 0;
    }

    /**
     * Gets number of bookmarks specified user has.
     *
     * @param int $userID Unique ID of user for which to tally bookmarks.
     * @return int Total number of bookmarks.
     */
    public function userBookmarkCount($userID)
    {
        $data = $this->SQL
            ->select("ud.DiscussionID", "count", "Count")
            ->from("UserDiscussion ud")
            ->join("Discussion d", "d.DiscussionID = ud.DiscussionID")
            ->where("ud.UserID", $userID)
            ->where("ud.Bookmarked", "1")
            ->get()
            ->firstRow();

        return $data !== false ? $data->Count : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($where = [], $options = [])
    {
        if (is_numeric($where)) {
            deprecated("DiscussionModel->delete(int)", "DiscussionModel->deleteID(int)");

            $result = $this->deleteID($where, $options);

            return $result;
        }

        throw new \BadMethodCallException("DiscussionModel->delete() is not supported.", 400);
    }

    /**
     * Given a list of discussionIDs, filter out discussions the user does not have category permissions on.
     *
     * @param array $discussionIDs
     * @param string $categoryPermission
     * @return array
     */
    public function filterCategoryPermissions(array $discussionIDs, string $categoryPermission): array
    {
        $checked = $this->checkCategoryPermission($discussionIDs, $categoryPermission);
        return $checked["validIDs"];
    }

    /**
     * Check permissions on a list of discussionIDs.
     *
     * @param array $discussionIDs The discussionIDs to check.
     * @param string $categoryPermission The category permission to check for.
     *
     * @return array{validIDs: Array<int>, nonexistentIDs: Array<int>, noPermissionIDs: Array<int>}
     */
    public function checkCategoryPermission(array $discussionIDs, string $categoryPermission): array
    {
        $discussionData = $this->SQL
            ->select("DiscussionID, CategoryID")
            ->from("Discussion")
            ->whereIn("DiscussionID", $discussionIDs)
            ->get()
            ->resultArray();
        $foundDiscussionIDs = array_column($discussionData, "DiscussionID");
        $noPermissionIDs = [];

        foreach ($discussionData as $discussion) {
            if (!CategoryModel::checkPermission($discussion["CategoryID"], $categoryPermission)) {
                $noPermissionIDs[] = $discussion["DiscussionID"];
            }
        }

        return [
            "validIDs" => array_diff($foundDiscussionIDs, $noPermissionIDs),
            "nonexistentIDs" => array_diff($discussionIDs, $foundDiscussionIDs),
            "noPermissionIDs" => $noPermissionIDs,
        ];
    }

    /**
     * Given a list of discussionIDs, filter out discussions that don't exist.
     *
     * @param array $discussionIDs
     *
     * @return array
     */
    public function filterExistingDiscussionIDs(array $discussionIDs): array
    {
        $existingIDs = $this->createSql()
            ->select("DiscussionID")
            ->from("Discussion")
            ->whereIn("DiscussionID", $discussionIDs)
            ->get()
            ->column("DiscussionID");
        return $existingIDs;
    }

    /**
     * Get Max batch time.
     */
    public function getMaxBatchTime(): int
    {
        return Gdn::config("Vanilla.MaxBatchTime", self::MAX_TIME_BATCH);
    }

    /**
     * Create a redirect discussion.
     *
     * @param array $targetDiscussion Discussion to create a redirect for.
     * @param array|null $existingDiscussion An existing discussions to create the redirect from.
     * @param string|null $redirectUrl Force a specific redirect url.
     *
     * @return int|bool Redirect ID or false on failure.
     * @throws NotFoundException
     */
    public function createRedirect(
        array $targetDiscussion,
        array $existingDiscussion = null,
        string $redirectUrl = null
    ) {
        $this->defineSchema();
        $maxNameLength = $this->Schema->getField("Name")->Length;

        $redirectDiscussion = [
            "Name" => sliceString(sprintf(t("Moved: %s"), $targetDiscussion["Name"]), $maxNameLength),
            "Type" => "redirect",
            "Body" => formatString(t('This discussion has been <a href="{url,html}">moved</a>.'), [
                "url" => discussionUrl($targetDiscussion),
            ]),
            "Format" => "Html",
            "Closed" => true,
            // Preserve the previous insertion time (Should place it in the same spot of the list).
            "DateInserted" => $targetDiscussion["DateLastComment"],
            "CategoryID" => $targetDiscussion["CategoryID"],
        ];

        $this->EventArguments["redirectDiscussion"] = &$redirectDiscussion;

        // fire event
        $this->fireEvent("beforeRedirectDiscussionSave", $this->EventArguments);
        $forceFormatter = Gdn::config("Garden.ForceInputFormatter");
        // Pass a forced input formatter around this exception.
        if ($forceFormatter) {
            $inputFormat = Gdn::config("Garden.InputFormatter");
            Gdn::config()->saveToConfig("Garden.InputFormatter", "Html", false);
        }
        $redirectID = $this->save($redirectDiscussion);
        // Reset the input formatter
        if ($forceFormatter) {
            Gdn::config()->saveToConfig("Garden.InputFormatter", $inputFormat, false);
        }
        return $redirectID;
    }

    /**
     * Delete a discussion. Update and/or delete all related data.
     *
     * Events: DeleteDiscussion.
     *
     * @param int $id Unique ID of discussion to delete.
     * @param array $options Additional options to control the delete behavior. Not used for discussions.
     * @return bool Always returns true.
     * @deprecated User DiscussionModel::deleteDiscussionIterator().
     */
    public function deleteID($id, $options = [])
    {
        if (is_array($id)) {
            Deprecation::unsupportedParam(
                "id",
                json_encode($id),
                "Don't call DiscussionModel::deleteID() with multiple ids. Use DiscussionModel::deleteDiscussions()"
            );
            $r = true;
            foreach ($id as $dID) {
                $result = $this->deleteID($dID, $options);
                $r &= $result;
            }
            return $r;
        }

        ModelUtils::consumeGenerator($this->deleteDiscussionIterator($id, $options));
        return true;
    }

    /**
     * Iteratively delete each discussion within a category with a generator.
     *
     * This method uses a generator so that it can be timed out if it takes too long to run, in which case it can be
     * run again, picking up where it left off.
     *
     * @param int $categoryID
     * @param array $options
     * @return iterable
     */
    public function deleteByCategory(int $categoryID, array $options = []): iterable
    {
        $discussions = LegacyModelUtils::reduceTable($this, [
            "d.CategoryID" => $categoryID,
        ]);
        foreach ($discussions as $discussion) {
            $this->deleteID($discussion["DiscussionID"], $options);
            yield $discussion;
        }
    }

    /**
     * Iteratively move each discussion within a category using a generator.
     *
     * This method uses a generator so that it can be timed out if it takes too long to run, in which case it can be
     * run again, picking up where it left off.
     *
     * @param int $categoryID
     * @param int $newCategoryID
     * @param array $options
     * @return iterable
     */
    public function moveByCategory(int $categoryID, int $newCategoryID, array $options = []): iterable
    {
        $discussions = LegacyModelUtils::reduceTable($this, [
            "d.CategoryID" => $categoryID,
        ]);
        foreach ($discussions as $discussion) {
            $this->save(
                [
                    "DiscussionID" => $discussion["DiscussionID"],
                    "CategoryID" => $newCategoryID,
                ],
                $options
            );
            yield $discussion;
        }
    }

    /**
     * Convert tags from stored format to user-presentable format.
     *
     * @param string $tags A string encoded with {@link dbencode()}.
     * @return string Comma-separated tags.
     * @since 2.1
     */
    private function formatTags($tags)
    {
        // Don't bother if there aren't any tags
        if (!$tags) {
            return "";
        }

        // Get the array.
        if (preg_match("`^(a:)|{|\[`", $tags)) {
            $tagsArray = dbdecode($tags);
        } else {
            $tagsArray = $tags;
        }

        // Compensate for deprecated space-separated format
        if (is_string($tagsArray) && $tagsArray == $tags) {
            $tagsArray = explode(" ", $tags);
        }

        // Safe format
        $tagsArray = Gdn_Format::text($tagsArray);

        // Send back an comma-separated string
        return is_array($tagsArray) ? implode(",", $tagsArray) : "";
    }

    /**
     * We don't use this functionality anymore. Previously, you had to register any sorting field before sorting with it.
     *
     * @deprecated
     */
    public static function allowedSortFields()
    {
        deprecated("allowedSortFields");
    }

    /**
     * Tests whether a user has permission to view a specific discussion.
     *
     * @param object|array|integer $discussion The discussion ID or the discussion to test.
     * @param integer $userID The ID of the user to test permission for. If empty, it defaults to Session user.
     * @return bool Whether the user can view the discussion.
     */
    public function canView($discussion, $userID = 0)
    {
        $canView = $this->checkPermission($discussion, "Vanilla.Discussions.View", $userID);
        return $canView;
    }

    /**
     * Tests whether a user has permission to view a specific discussion, with consideration for mod permissions.
     *
     * @param object $discussion
     * @return bool whether the user can view a discussion.
     */
    public function canViewDiscussion($discussion): bool
    {
        $canViewDiscussion = false;
        $session = $this->sessionInterface;
        $userID = $session->UserID;
        $canView = $this->canView($discussion, $userID);
        $isModerator = $session->checkRankedPermission("Garden.Moderation.Manage");
        if ($canView || $isModerator) {
            $canViewDiscussion = true;
        }
        return $canViewDiscussion;
    }

    /**
     * Tests whether a user has permission for a discussion by checking category-specific permissions.
     *
     * Fires an event that can override the calculated permission.
     *
     * @param object|array|integer $discussion The discussion ID or the discussion to test.
     * @param string $permission The category permission to test against the user.
     * @param integer $userID The ID of the user to test permission for. If empty, it defaults to Session user.
     * @return bool Whether the user has the specified permission privileges to the discussion.
     * @throws Exception Throws an exception when {@link $permission} is invalid.
     */
    public function checkPermission($discussion, $permission, $userID = 0)
    {
        // Either the permission string is a full permission, or we prepend 'Vanilla.Discussions.' to the permission.
        if (strpos($permission, ".") === false) {
            $permission = ucfirst(strtolower($permission));
            if (in_array($permission, $this->permissionTypes)) {
                $permission = "Vanilla.Discussions." . $permission;
            } else {
                throw new Exception(t("Unexpected discussion permission."));
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
        $userModel = $this->userModel;
        // Get category permission.
        $categoryID = val("CategoryID", $discussion);
        if ($userID && Gdn::session()->UserID === $userID && $categoryID) {
            $hasPermission = CategoryModel::checkPermission($categoryID, $permission);
        } else {
            $hasPermission = $userID && $userModel->getCategoryViewPermission($userID, $categoryID, $permission);
        }
        // Check if we've timed out.
        if (strpos(strtolower($permission), "edit") !== false) {
            $hasPermission &= self::editContentTimeout($discussion);
        }
        // Fire event to override permission ruling.
        $this->EventArguments["Discussion"] = $discussion;
        $this->EventArguments["UserID"] = $userID;
        $this->EventArguments["Permission"] = $permission;
        $this->EventArguments["HasPermission"] = &$hasPermission;
        $this->fireEvent("checkPermission");

        return $hasPermission;
    }

    /** SORT/FILTER */

    /**
     * Retrieves valid set key and filter keys pairs from an array, and returns the setKey => filterKey values.
     *
     * Works real well with unfiltered request arguments. (i.e., Gdn::request()->get()) Will only return safe
     * set key and filter key pairs from the filters array or an empty array if not found.
     *
     * @param array $array The array to get the filters from.
     * @return array The valid filters from the passed array or an empty array.
     * @throws Exception
     */
    protected function getFiltersFromArray($array)
    {
        $filterKeys = [];
        foreach (self::getAllowedFilters() as $filterSet) {
            $filterSetKey = val("key", $filterSet);
            // Check if any of our filters are in the array. Filter key value is unsafe.
            if ($filterKey = val($filterSetKey, $array)) {
                // Check that value is in filter array to ensure safety.
                if (val($filterKey, val("filters", $filterSet))) {
                    // Value is safe.
                    $filterKeys[$filterSetKey] = $filterKey;
                } else {
                    $this->logger->notice(
                        "Filter: {filterSetKey} => {$filterKey} does not exist in the DiscussionModel\'s allowed filters array.",
                        ["filterSetKey" => $filterSetKey, "filterKey" => $filterKey]
                    );
                }
            }
        }
        return $filterKeys;
    }

    /**
     * Retrieves the sort key from an array and if the value is valid, returns it.
     *
     * Works real well with unfiltered request arguments. (i.e., Gdn::request()->get()) Will only return a safe sort key
     * from the sort array or an empty string if not found.
     *
     * @param array $array The array to get the sort from.
     * @return string The valid sort from the passed array or an empty string.
     * @throws Exception
     */
    protected function getSortFromArray($array)
    {
        $unsafeSortKey = val("sort", $array);
        foreach (self::getAllowedSorts() as $sort) {
            if ($unsafeSortKey == val("key", $sort)) {
                // Sort key is valid.
                return val("key", $sort);
            }
        }
        if ($unsafeSortKey) {
            $this->logger->notice(
                'Sort: {unsafeSortKey} does not exist in the DiscussionModel\'s allowed sorts array.',
                ["unsafeSortKey" => $unsafeSortKey]
            );
        }
        return "";
    }

    /**
     * Checks the allowed sorts array for the string and it is valid, returns it the string.
     *
     * If not, returns an empty string. Will only return a safe sort key from the sort array or an empty string if not
     * found.
     *
     * @param string $string The string to get the sort from.
     * @return string A valid sort key or an empty string.
     */
    protected function getSortFromString($string)
    {
        if (val($string, self::$allowedSorts)) {
            // Sort key is valid.
            return $string;
        } else {
            $this->logger->notice('Sort "{sort}" does not exist in the DiscussionModel\'s allowed sorts array.', [
                "sort" => $string,
            ]);
            return "";
        }
    }

    /**
     * Get the auto-archive date for discussions.
     *
     * @return DateTimeInterface|null
     */
    public function getArchiveDate(): ?DateTimeInterface
    {
        return $this->archiveDate;
    }

    /**
     * Set the archive date.
     *
     * @param DateTimeInterface|string $archiveDate A datetime or a string that can be converted to a date.
     */
    public function setArchiveDate($archiveDate): void
    {
        if (empty($archiveDate)) {
            $archiveDate = null;
        } elseif (is_string($archiveDate)) {
            $utc = new DateTimeZone("UTC");
            $now = new DateTimeImmutable("now", $utc);
            $archiveDate = new DateTimeImmutable($archiveDate, $utc);
            if ($archiveDate > $now) {
                // The date is in the future. Assume the user entered something like '3 days' instead of '-3 days'.
                $archiveDate = $now->sub($now->diff($archiveDate));
            }
        } elseif (!($archiveDate instanceof DateTimeInterface)) {
            throw new \InvalidArgumentException(
                "DiscussionModel::setArchiveDate() expects a string or DateTimeInterface"
            );
        }
        $this->archiveDate = $archiveDate;
    }

    /**
     * Resolve a discussion argument that could be either a discussion ID or a discussion row.
     *
     * @param array|int $discussion The discussion to resolve.
     * @return array Returns an array in the form `[$discussionID, $discussion]`.
     * @throws NotFoundException If a discussion is not found.
     */
    public function resolveDiscussionArg($discussion): array
    {
        if (is_numeric($discussion)) {
            $discussionID = $discussion;
            $discussion = $this->getID($discussionID, DATASET_TYPE_ARRAY);
            if (!$discussion) {
                throw new NotFoundException("Discussion is not found", ["discussion" => $discussion]);
            }
        } elseif (is_array($discussion)) {
            $discussionID = $discussion["DiscussionID"] ?? null;
        } else {
            throw new \InvalidArgumentException(
                "DiscussionModel::resolveDiscussionArg() expects an integer or array.",
                400
            );
        }
        return [$discussionID, $discussion];
    }

    /**
     * Move "every" comments from a discussion to another.
     *
     * @param int $sourceDiscussionID
     * @param int $destDiscussionID
     * @param int $limit
     * @return bool
     */
    private function moveComments(int $sourceDiscussionID, int $destDiscussionID, $limit = 100): bool
    {
        $done = false;

        /** @var CommentModel $commentModel */
        $commentModel = Gdn::getContainer()->get(CommentModel::class);

        // Obtain the comments for the discussion.
        $comments = $commentModel->getByDiscussion($sourceDiscussionID, $limit)->result(DATASET_TYPE_ARRAY);

        if (count($comments) > 0) {
            foreach ($comments as $comment) {
                $comment = (array) $comment;
                $comment["DiscussionID"] = $destDiscussionID;
                $commentModel->save($comment);
            }
        } else {
            $done = true;
        }

        return $done;
    }

    /**
     * Takes a collection of filters and returns the corresponding filter key/value array [setKey => filterKey].
     *
     * @param array $filters The filters to get the keys for.
     * @return array The filter key array.
     */
    protected function getKeysFromFilters($filters)
    {
        $filterKeyValues = [];
        foreach ($filters as $filter) {
            if (isset($filter["setKey"]) && isset($filter["key"])) {
                $filterKeyValues[val("setKey", $filter)] = val("key", $filter);
            }
        }
        return $filterKeyValues;
    }

    /**
     * Takes an array of filter key/values [setKey => filterKey] and returns a collection of filters.
     *
     * @param array $filterKeyValues The filters key array to get the filter for.
     * @return array An array of filters.
     * @throws Exception
     */
    protected function getFiltersFromKeys($filterKeyValues)
    {
        $filters = [];
        $allFilters = self::getAllowedFilters();
        foreach ($filterKeyValues as $key => $value) {
            if (isset($allFilters[$key]["filters"][$value])) {
                $filters[] = $allFilters[$key]["filters"][$value];
            }
        }
        return $filters;
    }

    /**
     * @param string $sortKey
     * @return array
     * @throws Exception
     */
    protected function getSortFromKey($sortKey)
    {
        return val($sortKey, self::getAllowedSorts(), []);
    }

    /**
     * Get the current sort/filter query string.
     *
     * You can pass no parameters or pass either a new filter key or sort key to build a new query string, leaving the
     * other properties intact.
     *
     * @param string $selectedSort
     * @param array $selectedFilters
     * @param string $sortKeyToSet The key name of the sort in the sorts array.
     * @param array $filterKeysToSet An array of filters, where the key is the key of the filterSet in the filters array
     * and the value is the key of the filter.
     * @return string The current or amended query string for sort and filter.
     */
    public static function getSortFilterQueryString(
        $selectedSort,
        $selectedFilters,
        $sortKeyToSet = "",
        $filterKeysToSet = []
    ) {
        $filterString = "";
        $filterKeys = array_merge($selectedFilters, $filterKeysToSet);

        // Build the sort query string
        foreach ($filterKeys as $setKey => $filterKey) {
            // If the preference is none, don't show it.
            if ($filterKey != self::EMPTY_FILTER_KEY) {
                if (!empty($filterString)) {
                    $filterString .= "&";
                }
                $filterString .= $setKey . "=" . $filterKey;
            }
        }

        $sortString = "";
        if (!$sortKeyToSet) {
            $sort = $selectedSort;
            if ($sort) {
                $sortString = "sort=" . $sort;
            }
        } else {
            $sortString = "sort=" . $sortKeyToSet;
        }

        $queryString = "";
        if (!empty($sortString) && !empty($filterString)) {
            $queryString = "?" . $sortString . "&" . $filterString;
        } elseif (!empty($sortString)) {
            $queryString = "?" . $sortString;
        } elseif (!empty($filterString)) {
            $queryString = "?" . $filterString;
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
    public static function addSort($key, $name, $orderBy, $categoryIDs = [])
    {
        self::$allowedSorts[$key] = [
            "key" => $key,
            "name" => $name,
            "orderBy" => $orderBy,
            "categories" => $categoryIDs,
        ];
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
    public static function addFilter($key, $name, $wheres, $group = "", $setKey = "filter")
    {
        if (!val($setKey, self::getAllowedFilters())) {
            self::addFilterSet($setKey);
        }
        self::$allowedFilters[$setKey]["filters"][$key] = [
            "key" => $key,
            "setKey" => $setKey,
            "name" => $name,
            "wheres" => $wheres,
        ];
        if ($group) {
            self::$allowedFilters[$setKey]["filters"][$key]["group"] = $group;
        }
    }

    /**
     * Adds a filter set to the allowed filters array.
     *
     * @param string $setKey The key name of the filter set.
     * @param string $setName The name of the filter set. Appears in the UI.
     * @param array $categoryIDs The IDs of the categories that this filter will work on. If empty, filter is global.
     * @param bool $display Display a UI filter.
     */
    public static function addFilterSet($setKey, $setName = "", $categoryIDs = [], $display = true)
    {
        if (!$setName) {
            $setName = t("All Discussions");
        }
        self::$allowedFilters[$setKey]["key"] = $setKey;
        self::$allowedFilters[$setKey]["name"] = $setName;
        self::$allowedFilters[$setKey]["categories"] = $categoryIDs;
        self::$allowedFilters[$setKey]["display"] = $display;

        // Add a way to let users clear any filters they've added.
        self::addClearFilter($setKey, $setName);
    }

    /**
     * If you don't want to use any of the default sorts, use this little buddy.
     */
    public static function clearSorts()
    {
        self::$allowedSorts = [];
    }

    /**
     * Removes a sort from the allowed sort array with the passed key.
     *
     * @param string $key The key of the sort to remove.
     */
    public static function removeSort($key)
    {
        if (val($key, self::$allowedSorts)) {
            unset(self::$allowedSorts[$key]);
        }
    }

    /**
     * Removes a filters from the allowed filter array with the passed filter key/values.
     *
     * @param array $filterKeys The key/value pairs of the filters to remove.
     */
    public static function removeFilter($filterKeys)
    {
        foreach ($filterKeys as $setKey => $filterKey) {
            if (isset(self::$allowedFilters[$setKey]["filters"][$filterKey])) {
                unset(self::$allowedFilters[$setKey]["filters"][$filterKey]);
            }
        }
    }

    /**
     * Removes a filter set from the allowed filter array with the passed set key.
     *
     * @param string $setKey The key of the filter to remove.
     */
    public static function removeFilterSet($setKey)
    {
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
    protected static function addClearFilter($setKey, $setName = "")
    {
        self::$allowedFilters[$setKey]["filters"][self::EMPTY_FILTER_KEY] = [
            "key" => self::EMPTY_FILTER_KEY,
            "setKey" => $setKey,
            "name" => $setName,
            "wheres" => [],
            "group" => "default",
        ];
    }

    /**
     * Get structured data about a discussion and its related records.
     *
     * @param array $discussion
     * @return array
     * @link http://schema.org/DiscussionForumPosting
     */
    public function structuredData(array $discussion): array
    {
        $name = $discussion["Name"] ?? "";
        $dateInserted = $discussion["DateInserted"] ?? "";
        $body = Gdn_Format::reduceWhiteSpaces(
            Gdn_Format::excerpt($discussion["Body"] ?? "", $discussion["Format"] ?? "Html")
        );

        $result = [
            "headline" => $name,
            "description" => sliceString($body, 500),
            "discussionUrl" => discussionUrl($discussion),
            "url" => discussionUrl($discussion),
            "text" => $body,
            "dateCreated" => $dateInserted,
        ];

        if (array_key_exists("InsertUserID", $discussion) && $discussion["InsertUserID"]) {
            $user = $this->userModel->getID($discussion["InsertUserID"], DATASET_TYPE_ARRAY);
            if ($user) {
                $result["author"] = [
                    "@context" => "https://schema.org",
                    "@type" => "Person",
                    "name" => $user["Name"],
                    "image" => userPhotoUrl($user),
                    "url" => url(userUrl($user), true),
                ];
            } else {
                $result["author"] = [
                    "@context" => "https://schema.org",
                    "@type" => "Person",
                    "name" => t("unknown"),
                    "image" => UserModel::getDefaultAvatarUrl(),
                    "url" => "https://example.com/unknown",
                ];
            }
        }
        return $result;
    }

    /**
     * Add discussion data to an array.
     *
     * @param array||Gdn_DataSet $dataSet Results we need to join discussion data to.
     * @param string $discussionID Column name for provided $data to get discussionIDs.
     * @param array $fields Optionally pass list of discussion fields to add to array.
     *        NOTE: $fields is an associative array of 'field' => 'alias'
     *              where 'field' - is discussion model column name (ex: Name, Body, Type)
     *              and 'alias' - is the column name to add|replace to $data array (ex: DiscussionName, DiscussionBody)
     */
    public function joinDiscussionData(&$dataSet, string $discussionID, array $fields)
    {
        if ($dataSet instanceof Gdn_DataSet) {
            $data = $dataSet->result();
            $arrayMode = $dataSet->datasetType() === DATASET_TYPE_ARRAY;
        } else {
            $data = &$dataSet;
            $arrayMode = true;
        }
        if ($arrayMode) {
            $discussionIDs = array_column($data, $discussionID);
        } else {
            $discussionIDs = [];
            foreach ($data as $obj) {
                $discussionIDs[] = $obj->$discussionID;
            }
        }

        // Get the discussions.
        $sql = $this->SQL->from("Discussion d");

        if (empty($fields)) {
            $sql->select("d.*");
        } else {
            $sql->select("d.DiscussionID");
            foreach ($fields as $field => $alias) {
                $sql->select($field, "", $alias);
            }
        }

        $discussions = $sql
            ->whereIn("d.DiscussionID", $discussionIDs)
            ->get()
            ->resultArray();

        $discussions = array_combine(array_column($discussions, "DiscussionID"), $discussions);

        foreach ($data as &$row) {
            $discussion = $arrayMode ? $discussions[$row[$discussionID]] : $discussions[$row->$discussionID];
            foreach ($fields as $field => $alias) {
                if ($arrayMode) {
                    $row[$alias] = $discussion[$alias];
                } else {
                    $row->$alias = $discussion[$alias];
                }
            }
        }
    }

    /**
     * Given a database row, massage the data into a more externally-useful format.
     *
     * @param array $row
     * @param array $expand
     * @param array $options
     * @return array
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException|FormatterNotFoundException
     */
    public function normalizeRow(array $row, $expand = [], array $options = []): array
    {
        $this->fixRow($row);
        // The stuff in tags is nonsense.
        $session = \Gdn::session();
        $discussionMoved = $row["Type"] === self::REDIRECT_TYPE && $row["Closed"];
        if ($session->User && !$discussionMoved) {
            $dateFirstVisit = val("DateFirstVisit", $session->User);
            $row["unread"] =
                isset($row["CountUnreadComments"]) &&
                $row["CountUnreadComments"] !== 0 &&
                ($row["CountUnreadComments"] !== true ||
                    ($dateFirstVisit && dateCompare($dateFirstVisit, $row["DateInserted"]) <= 0));
            if (
                isset($row["CountUnreadComments"]) &&
                $row["CountUnreadComments"] !== true &&
                $row["CountUnreadComments"] > 0
            ) {
                $row["countUnread"] = $row["CountUnreadComments"];
            }
        } else {
            $row["unread"] = false;
        }

        $row["statusID"] = $this->discussionStatusModel->filterActiveStatusID($row["statusID"] ?? null);

        // Handle legacy resolved fields
        $row["resolved"] = $row["internalStatusID"] === RecordStatusModel::DISCUSSION_STATUS_RESOLVED;

        // Hide away inactive record statuses.
        // This query should generally always be cached in

        // The Category key will hold a category fragment in API responses. Ditch the default string.
        if (array_key_exists("Category", $row) && !is_array($row["Category"])) {
            unset($row["Category"]);
        }

        $row["Announce"] = (bool) ($row["Announce"] ?? 0);
        $row["Dismissed"] = (bool) ($row["Dismissed"] ?? 0);
        $row["Url"] = discussionUrl($row);
        $row["CanonicalUrl"] =
            isset($row["CanonicalUrl"]) && is_string($row["CanonicalUrl"]) ? $row["CanonicalUrl"] : $row["Url"];

        $rawBody = $row["Body"] ?? "";
        $format = $row["Format"] ?? null;

        $bodyParsed = $this->formatterService->parse($rawBody, $format);
        if ($this->doFieldFormatting) {
            $row["Body"] = $this->formatterService->renderHTML($bodyParsed, $format);
        }
        $row["Attributes"] = new Attributes($row["Attributes"] ?? null);

        if (array_key_exists("Bookmarked", $row)) {
            $row["Bookmarked"] = (bool) ($row["Bookmarked"] ?? 0);
        }

        if (ModelUtils::isExpandOption("lastPost", $expand)) {
            $lastPost = [
                "discussionID" => $row["DiscussionID"] ?? null,
                "parentRecordType" => "discussion",
                "parentRecordID" => $row["DiscussionID"] ?? null,
                "insertUserID" => $row["LastUserID"] ?? null,
            ];
            $lastPost["dateInserted"] = $row["DateLastComment"] ?? ($row["DateInserted"] ?? null);
            if ($row["LastCommentID"]) {
                $lastPost["CommentID"] = $row["LastCommentID"] ?? null;
                $lastPost["CategoryID"] = $row["CategoryID"] ?? null;
                $lastPost["name"] = sprintft("Re: %s", $row["Name"] ?? "");
                $lastPost["url"] = isset($lastPost) ? commentUrl($lastPost, true) : null;
            } else {
                $lastPost["name"] = $row["Name"] ?? null;
                $lastPost["url"] = $row["Url"] ?? null;
            }

            if (
                ModelUtils::isExpandOption("lastPost.insertUser", $expand) ||
                (ModelUtils::isExpandOption("lastUser", $expand) && array_key_exists("LastUser", $row))
            ) {
                $lastPost["insertUser"] = $row["LastUser"];
                if (!ModelUtils::isExpandOption("lastUser", $expand)) {
                    unset($row["LastUser"]);
                }
            }

            $row["lastPost"] = $lastPost;
        }

        // This shouldn't be necessary, but the db allows nulls for dateLastComment.
        if (empty($row["DateLastComment"])) {
            $row["DateLastComment"] = $row["DateInserted"] ?? null;
        }

        if (ModelUtils::isExpandOption("excerpt", $expand)) {
            $row["excerpt"] = $this->formatterService->renderExcerpt(
                $bodyParsed,
                $format,
                $options["excerptLength"] ?? null
            );
        }
        if (ModelUtils::isExpandOption("snippet", $expand)) {
            $row["snippet"] = $this->formatterService->renderExcerpt($bodyParsed, $format, self::MAX_SNIPPET_LENGTH);
        }
        if (ModelUtils::isExpandOption("-body", $expand)) {
            unset($row["Body"]);
        }

        $row["Closed"] = isset($row["Closed"]) ? (bool) $row["Closed"] : false;

        if (ModelUtils::isExpandOption(ModelUtils::EXPAND_CRAWL, $expand)) {
            $row["canonicalID"] = "discussion_{$row["DiscussionID"]}";
            $row[
                "recordCollapseID"
            ] = "site{$this->ownSite->getSiteID()}_parentRecordType_discussion_parentRecordID{$row["DiscussionID"]}";
            $row["excerpt"] = $row["excerpt"] ?? $this->formatterService->renderExcerpt($bodyParsed, $format);
            $row["bodyPlainText"] = $row["Name"] . " \n " . Gdn::formatService()->renderPlainText($bodyParsed, $format);

            $row["image"] = $this->formatterService->parseImageUrls($bodyParsed, $format)[0] ?? null;
            $row["scope"] = $this->categoryModel->getRecordScope($row["CategoryID"]);
            $row["score"] = $row["Score"] ?? 0;
            $type = $row["Type"] ?? "";
            $row["Type"] = $type === self::REDIRECT_TYPE ? self::DISCUSSION_TYPE : $type;
            $siteSection = $this->siteSectionModel->getSiteSectionForAttribute("allCategories", $row["CategoryID"]);
            $row["locale"] = $siteSection->getContentLocale();

            if ($row["Closed"] ?? false) {
                $row["labelCodes"][] = self::CLOSED_LABEL;
            }

            if ($row["pinned"] ?? false) {
                $row["labelCodes"][] = self::ANNOUNCEMENT_LABEL;
            }

            $searchService = Gdn::getContainer()->get(SearchService::class);
            /** @var SearchTypeQueryExtenderInterface $extender */
            foreach ($searchService->getExtenders() as $extender) {
                $extender->extendRecord($row, "discussion");
            }
        }

        $scheme = new CamelCaseScheme();
        $result = $scheme->convertArrayKeys($row);
        $result["type"] = self::normalizeDiscussionType($result["type"] ?? null);

        // Get the discussion's parsed body's first image & get the srcset for it.
        $result["image"] = $this->formatterService->parseMainImage($bodyParsed, $format);

        // Return suggestions if suggestions are enabled and exist for the discussion
        if (FeatureFlagHelper::featureEnabled("AISuggestions")) {
            $aiConfig = AiSuggestionSourceService::aiSuggestionConfigs();
            if ($aiConfig["enabled"]) {
                $aiSuggestion = Gdn::getContainer()->get(AiSuggestionSourceService::class);
                $suggestAnswers = $aiSuggestion->checkIfUserHasEnabledAiSuggestions();
                if ($suggestAnswers) {
                    $result["suggestions"] = $this->aiSuggestionModel->getByDiscussionID($row["DiscussionID"]);
                    if (count($result["suggestions"]) > 0) {
                        $result["showSuggestions"] =
                            $result["attributes"]["showSuggestions"] ?? $result["countComments"] == 0;
                        if (isset($result["attributes"]["showSuggestions"])) {
                            unset($result["attributes"]["showSuggestions"]);
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Normalize a discussion type for output.
     *
     * @param string|null $discussionType
     *
     * @return string
     */
    public static function normalizeDiscussionType(?string $discussionType): string
    {
        if (!empty($discussionType)) {
            $discussionType = $discussionType === self::STUB_TYPE ? self::DISCUSSION_TYPE : $discussionType;
            return lcfirst($discussionType);
        } else {
            return "discussion";
        }
    }

    /**
     * Method to prevent encoding data twice.
     *
     * DiscussionModel::calculate applies htmlspecialchars to discussion name which could conflict when
     * data is encoded on the view.  As result characters will display in their entity codes.  Removing the
     * htmlspecialchars in the calculate method could result in several XSS vulnerabilities in Vanilla.  This
     * method is a utility method to avoid encoding data where it's not necessary.
     *
     * @param array $row The discussion record to fix.
     * @return array
     */
    public function fixRow(array &$row): array
    {
        if (array_key_exists("Name", $row)) {
            $row["Name"] = Gdn::formatService()->renderPlainText($row["Name"], TextFormat::FORMAT_KEY);
            $row["Name"] = htmlspecialchars_decode($row["Name"]);
        }
        return $row;
    }

    /**
     * Determine whether or not the discussion is archived based on its last comment date.
     *
     * @param string|null $dateLastComment
     * @return bool
     */
    public function isArchived($dateLastComment): bool
    {
        if (empty($dateLastComment) || $this->getArchiveDate() === null) {
            return false;
        }
        try {
            $dt = new DateTimeImmutable($dateLastComment, $this->getArchiveDate()->getTimezone());
        } catch (\Exception $ex) {
            trigger_error("DiscussionModel::isArchived() got an invalid dateLastComment.", E_USER_WARNING);
            return false;
        }
        if ($dt < $this->getArchiveDate()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get a schema instance comprised of standard discussion fields.
     *
     * @return Schema
     */
    public function schema(): Schema
    {
        $schema = [
            "discussionID:i" => "The ID of the discussion.",
            "discussionCollapseID:s?",
            "type:s|n?" => [
                "description" => "The type of this discussion if any.",
            ],
            "postTypeID?" => "The post type ID of this discussion.",
            "name:s" => [
                "description" => "The title of the discussion.",
                "x-localize" => true,
            ],
            "body:s?" => [
                "description" => "The body of the discussion.",
            ],
            "excerpt:s?" => [
                "description" => "Plain-text excerpt of the current discussion body.",
            ],
            "snippet:s?" => [
                "description" => "Plain-text snippet of discussion body for use in emails.",
            ],
            "bodyRaw:s?",
            "bodyPlainText:s?" => [
                "description" => "The body of the discussion in plain text.",
                "x-localize" => true,
            ],
            "categoryID:i" => "The category the discussion is in.",
            "dateInserted:dt" => "When the discussion was created.",
            "dateUpdated:dt|n" => "When the discussion was last updated.",
            "dateLastComment:dt|n" => "When the last comment was posted.",
            "insertUserID:i" => "The user that created the discussion.",
            "insertUser?" => SchemaFactory::get(UserFragmentSchema::class, "UserFragment"),
            "updateUserID:i|n",
            "lastUserID:i",
            "lastUser?" => SchemaFactory::get(UserFragmentSchema::class, "UserFragment"),
            "pinned:b?" => "Whether or not the discussion has been pinned.",
            "pinLocation:s|n" => [
                "enum" => ["category", "recent"],
                "description" =>
                    "The location for the discussion, if pinned. " .
                    '"category" are pinned to their own category. ' .
                    '"recent" are pinned to the recent discussions list, as well as their own category.',
            ],
            "closed:b" => "Whether the discussion is closed or open.",
            "announce:b" => "Whether the discussion is an announcement.",
            "sink:b" => "Whether or not the discussion has been sunk.",
            "countComments:i" => "The number of comments on the discussion.",
            "countViews:i" => "The number of views on the discussion.",
            "score:i|n" => "Total points associated with this post.",
            "hot:i|n?" => "Score points plus comments count.",
            "url:s?" => "The full URL to the discussion.",
            "canonicalUrl:s" => "The full canonical URL to the discussion.",
            "format:s?" => "Format of the discussion",
            "tagIDs:a?" => ["items" => ["type" => "integer"]],
            "labelCodes:a?" => ["items" => ["type" => "string"]],
            "lastPost?" => SchemaFactory::get(PostFragmentSchema::class, "PostFragment"),
            "breadcrumbs:a?" => new InstanceValidatorSchema(Breadcrumb::class),
            "groupID:i?" => [
                "x-null-value" => -1,
            ],
            "statusID:i" => [
                "default" => 0,
            ],
            "image?" => new MainImageSchema(),
            "attributes:o?" => [
                "type" => "object",
                "properties" => [
                    "statusNotes" => [
                        "type" => "string",
                    ],
                    "statusChanged" => [
                        "type" => "boolean",
                    ],
                ],
            ],
            "status?" => RecordStatusModel::getSchemaFragment(),
            "trending:o?",
            "reactions?" => $this->reactionModel->getReactionSummaryFragment(),
            "reportMeta?" => \Vanilla\Forum\Models\CommunityManagement\ReportModel::reportMetaSchema(),
            "attachments:a?",
            "countReports:i?",
            "suggestions:a?",
            "showSuggestions:b?",
            "postMeta?",
            "permissions:o?",
        ];
        if (\Gdn::session()->checkPermission("staff.allow")) {
            $schema["internalStatusID:i"] = ["default" => RecordStatusModel::DISCUSSION_STATUS_UNRESOLVED];
            $schema["internalStatus?"] = RecordStatusModel::getSchemaFragment();
            $schema["resolved:b"] = [
                "default" => false,
            ];
        }

        $result = Schema::parse($schema);
        return $result;
    }

    /**
     * Get a schema representing user-specific discussion fields.
     *
     * @return Schema
     */
    public function userDiscussionSchema(): Schema
    {
        $result = Schema::parse([
            "bookmarked:b" => "Whether or not the discussion is bookmarked by the current user.",
            "unread:b" => "Whether or not the discussion should have an unread indicator.",
            "countUnread:i?" => "The number of unread comments.",
            "dismissed:b" => "Whether or not the discussion has been dismissed by the current user.",
        ]);
        return $result;
    }

    /**
     * Decide whether to update a record, insert a new record, or do nothing.
     *
     * @param object|array $discussion Discussion being watched.
     * @param int $limit Max number to get.
     * @param int $offset Number to skip.
     * @param int $totalComments Total in entire discussion (hard limit).
     * @param string|null $maxDateInserted The most recent insert date of the viewed comments.
     * @return array Returns a 3-item array of types int, string|null, string|null.
     * @throws Exception Throws an exception if given an invalid timestamp.
     */
    public function calculateWatch($discussion, int $limit, int $offset, int $totalComments, ?string $maxDateInserted)
    {
        $newComments = false;
        // Max comments we could have seen.
        $countWatch = $limit + $offset;
        // If the conversation doesn't have any comments, use the date the discussion started.
        $maxDateInserted = is_null($maxDateInserted) ? $discussion->DateInserted : $maxDateInserted;
        //        $dateLastViewed = self::maxDate($discussion->DateLastViewed, $maxDateInserted);
        if ($countWatch > $totalComments) {
            $countWatch = $totalComments;
        }

        // This discussion looks familiar...
        if ($discussion->CountCommentWatch > 0 || !empty($discussion->WatchUserID ?? null) || $discussion->Bookmarked) {
            if ($countWatch < $discussion->CountCommentWatch) {
                $countWatch = (int) min($discussion->CountCommentWatch, $totalComments);
            }

            if (isset($discussion->DateLastViewed)) {
                $newComments |=
                    Gdn_Format::toTimestamp($discussion->DateLastComment) >
                    Gdn_Format::toTimestamp($discussion->DateLastViewed);
            }

            if (
                $totalComments > $discussion->CountCommentWatch ||
                $countWatch != $discussion->CountCommentWatch ||
                is_null($discussion->DateLastViewed)
            ) {
                $newComments = true;
            }

            $operation = $newComments ? "update" : null;
        } else {
            $operation = "insert";
        }

        $dateLastViewed = self::maxDate($discussion->DateLastViewed, $maxDateInserted);

        return [$countWatch, $dateLastViewed, $operation];
    }

    /**
     * Change a discussion's Closed value to 0.
     *
     * @param int $discussionID
     * @return int Unique ID of the saved discussion.
     */
    public function openDiscussion(int $discussionID): bool
    {
        $save = [
            "DiscussionID" => $discussionID,
            "Closed" => 0,
        ];
        // Fire event in case some addon needs to change other data during the opening.
        $this->EventArguments = ["save" => &$save];
        $this->fireEvent("beforeOpenDiscussion");

        // Remove the `ClosedByUserID` attribute, if it exists.
        $discussion = $this->getID($discussionID);
        if (isset($discussion->Attributes[self::CLOSED_BY_USER_ID])) {
            unset($discussion->Attributes[self::CLOSED_BY_USER_ID]);
            $this->setProperty($discussionID, "Attributes", dbencode($discussion->Attributes));
        }

        // Save the discussion
        return $this->save($this->EventArguments["save"]);
    }

    /**
     * Change a discussion's Closed value to 0.
     *
     * @param int $discussionID
     * @return int Unique ID of the saved discussion.
     */
    public function closeDiscussion(int $discussionID): bool
    {
        $save = [
            "DiscussionID" => $discussionID,
            "Closed" => 1,
        ];
        // Fire event in case some addon needs to change other data during the close.
        $this->EventArguments = ["save" => &$save];
        $this->fireEvent("beforeCloseDiscussion");

        // Set the `ClosedByUserID` attribute.
        $discussion = $this->getID($discussionID);
        $discussion->Attributes[DiscussionModel::CLOSED_BY_USER_ID] = Gdn::session()->UserID;
        $this->setProperty($discussionID, "Attributes", dbencode($discussion->Attributes));

        // Save the discussion
        $saveSuccess = $this->save($this->EventArguments["save"]);

        // Dispatch a Discussion event (close)
        $senderUserID = Gdn::session()->UserID;
        $sender = $senderUserID ? Gdn::userModel()->getFragmentByID($senderUserID) : null;

        $discussion = $this->getID($discussionID, DATASET_TYPE_ARRAY);
        $discussionEvent = $this->eventFromRow($discussion, DiscussionEvent::ACTION_CLOSE, $sender);
        $this->getEventManager()->dispatch($discussionEvent);

        return $saveSuccess;
    }

    /**
     * Record the user's watch data.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object|array $discussion Discussion being watched.
     * @param int $limit Max number to get.
     * @param int $offset Number to skip.
     * @param int $totalComments Total in entire discussion (hard limit).
     * @param string|null $maxDateInserted The most recent insert date of the viewed comments.
     * @throws Exception Throws an exception if given an invalid timestamp.
     */
    public function setWatch($discussion, $limit, $offset, $totalComments, $maxDateInserted = null)
    {
        $userID = Gdn::session()->UserID;
        if (!$userID) {
            return;
        }

        [$countWatch, $dateLastViewed, $op] = $this->calculateWatch(
            $discussion,
            $limit,
            $offset,
            $totalComments,
            $maxDateInserted
        );

        switch ($op) {
            case "update":
                $this->SQL->put(
                    "UserDiscussion",
                    [
                        "CountComments" => $countWatch,
                        "DateLastViewed" => $dateLastViewed,
                    ],
                    [
                        "UserID" => $userID,
                        "DiscussionID" => $discussion->DiscussionID,
                    ]
                );
                break;
            case "insert":
                // Insert watch data.
                $this->SQL->options("Ignore", true);
                $this->SQL->insert("UserDiscussion", [
                    "UserID" => $userID,
                    "DiscussionID" => $discussion->DiscussionID,
                    "CountComments" => $countWatch,
                    "DateLastViewed" => $dateLastViewed,
                ]);
                break;
        }

        // If there is a discrepancy between $countWatch and $discussion->CountCommentWatch,
        // update CountCommentWatch with the correct value.
        $discussion->CountCommentWatch = $countWatch;
        $this->markCategoryReadFuzzy($discussion);
    }

    /**
     * Mark categories that this discussion was in as read.
     *
     * This method was extracted from `DiscussionModel::setWatch()`.
     *
     * @param object $discussion
     */
    protected function markCategoryReadFuzzy($discussion): void
    {
        /**
         * Fuzzy way of trying to automatically mark a category read again
         * if the user reads all the comments on the first few pages.
         */

        // If this discussion is in a category that has been marked read,
        // check if reading this thread causes it to be completely read again.
        $categoryID = $discussion->CategoryID;
        if (!$categoryID) {
            return;
        }
        $category = CategoryModel::categories($categoryID);
        if (!$category) {
            return;
        }
        $wheres = ["CategoryID" => $categoryID];
        $dateMarkedRead = $category["DateMarkedRead"];
        if ($dateMarkedRead) {
            $wheres["DateLastComment>"] = $dateMarkedRead;
        }
        // Fuzzy way of looking back about 2 pages into the past.
        $lookBackCount = Gdn::config("Vanilla.Discussions.PerPage", 50) * 2;

        // Find all discussions with content from after DateMarkedRead.
        $discussionModel = new DiscussionModel();

        $discussions = $discussionModel->getWhere($wheres);
        unset($discussionModel);

        // Abort if we get back as many as we asked for, meaning a
        // lot has happened.
        if ($discussions->numRows() > $lookBackCount) {
            return;
        }

        // Loop over these discussions and exit if there are any unread discussions
        while ($discussion = $discussions->nextRow(DATASET_TYPE_ARRAY)) {
            if (!$discussion["Read"]) {
                return;
            }
        }

        // Mark this category read if all the new content is read.
        $categoryModel = new CategoryModel();
        $categoryModel->saveUserTree($categoryID, ["DateMarkedRead" => DateTimeFormatter::getCurrentDateTime()]);
        unset($categoryModel);
    }

    /**
     * @inheritDoc
     */
    public function getCrawlInfo(): array
    {
        $r = LegacyModelUtils::getCrawlInfoFromPrimaryKey(
            $this,
            "/api/v2/discussions?pinOrder=mixed&sort=-discussionID&expand=crawl,tagIDs,roles",
            "discussionID"
        );
        return $r;
    }

    /**
     * Return a URL for a discussion. This function is in here and not functions.general so that plugins can override.
     *
     * @param object|array $discussion
     * @param int|string $page
     * @param bool $withDomain
     * @return string
     */
    public static function discussionUrl($discussion, $page = "", $withDomain = true)
    {
        if (function_exists("discussionUrl")) {
            // Legacy overrides.
            return discussionUrl($discussion, $page, $withDomain);
        } else {
            return self::createRawDiscussionUrl($discussion, $page, $withDomain);
        }
    }

    /**
     * Build the default url for a discussion.
     *
     * @param object|array $discussion
     * @param int|string $page
     * @param bool $withDomain
     * @return string
     *
     * @internal Don't use unless you are the global discussionUrl function.
     */
    public static function createRawDiscussionUrl($discussion, $page = "", $withDomain = true)
    {
        $eventManager = \Gdn::eventManager();
        if ($eventManager->hasHandler("customDiscussionUrl")) {
            return $eventManager->fireFilter("customDiscussionUrl", "", $discussion, $page, $withDomain);
        }

        $discussion = (object) $discussion;
        $name = isset($discussion->Name) ? Gdn_Format::url($discussion->Name) : null;

        // Disallow an empty name slug in discussion URLs.
        if (empty($name)) {
            $name = "x";
        }

        $result = "/discussion/" . ($discussion->DiscussionID ?? "") . "/" . $name;

        if ($page) {
            if ($page > 1 || Gdn::session()->UserID) {
                $result .= "/p" . $page;
            }
        }

        return url($result, $withDomain);
    }

    /**
     * Generator for deleting multiple discussions, which can be a long-running process.
     *
     * User with LongRunner::run* methods.
     *
     * @param array $discussionIDs The discussionIDs to delete.
     * @param array $options Options for deletion.
     *
     * @return Generator<int, LongRunnerItemResultInterface, string, string|LongRunnerNextArgs>
     */
    public function deleteDiscussionsIterator(array $discussionIDs, array $options = []): Generator
    {
        $completedDiscussionIDs = [];

        try {
            $discussionIDs = array_unique($discussionIDs);

            yield new LongRunnerQuantityTotal([$this, "getTotalCount"], [$discussionIDs]);

            foreach ($discussionIDs as $discussionID) {
                try {
                    // Loop through and yield values from the single delete iterator.
                    // Notably we can't use "yield from" here because when WE need to handle the yield return value.
                    // And using yield from means that we only get the return value when the inner iterator finishes.
                    foreach ($this->deleteDiscussionIterator($discussionID, $options) as $discussionDeleteYielded) {
                        if ($discussionDeleteYielded instanceof LongRunnerSuccessID) {
                            // A discussion was deleted.
                            $completedDiscussionIDs[] = $discussionDeleteYielded->getRecordID();
                        }
                        yield $discussionDeleteYielded;
                    }
                    $completedDiscussionIDs[] = $discussionID;

                    // These transacton IDs are forwarded only for a single discussion.
                    unset($options[self::OPT_DELETE_SINGLE_TRANSACTION_ID]);
                } catch (Exception $e) {
                    if ($e instanceof LongRunnerTimeoutException) {
                        // Throw it back up to our next catch block.
                        throw $e;
                    }
                    yield new LongRunnerFailedID($discussionID, $e);
                }
            }
        } catch (LongRunnerTimeoutException $e) {
            // We might have been in the middle of a log transaction.
            // Preserve it for when we continue.
            $options[self::OPT_DELETE_SINGLE_TRANSACTION_ID] = LogModel::getTransactionID();

            return new LongRunnerNextArgs([array_diff($discussionIDs, $completedDiscussionIDs), $options]);
        }

        return LongRunner::FINISHED;
    }

    /**
     * Generator for deleting a discussion, which can be a long-running process.
     *
     * User with LongRunner::run* methods.
     *
     * @param int $discussionID The discussionID to delete.
     * @param array $options Options for deletion.
     *
     * @return Generator<int, LongRunnerItemResultInterface, string, string|LongRunnerNextArgs>
     */
    public function deleteDiscussionIterator(int $discussionID, array $options = []): Generator
    {
        // Prepare some dependencies that we can't inject because this is a legacy model.
        /** @var CommentModel $commentModel */
        $commentModel = \Gdn::getContainer()->get(CommentModel::class);

        $sql = $this->createSql();
        $db = $sql->Database;

        // Grab the existing row.
        $existingDiscussion = $sql
            ->select("*")
            ->from("Discussion")
            ->where("DiscussionID", $discussionID)
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);
        if (!$existingDiscussion) {
            // Discussion may have already been deleted.
            yield new LongRunnerSuccessID($discussionID);
            return LongRunner::FINISHED;
        }

        $insertUserID = $existingDiscussion["InsertUserID"];
        $categoryID = $existingDiscussion["CategoryID"];

        // Let other things know the discussion has been deleted.
        $this->fireEvent("deleteDiscussion", [
            "DiscussionID" => $discussionID,
            "Discussion" => $existingDiscussion,
        ]);

        // Prepare logging
        $logMethod = $options["Log"] ?? true;
        $logMethod = $logMethod === true ? "Delete" : $logMethod;
        $logOptions = $options["LogOptions"] ?? [];
        $logTransactionID = $options[self::OPT_DELETE_SINGLE_TRANSACTION_ID] ?? null;

        // Start up a common log transaction to tie all the deleted items together.
        $transactionID = LogModel::beginTransaction($logTransactionID);

        // Make sure if we get stopped while iterating, the next call will have the same transactionID.
        $options[self::OPT_DELETE_SINGLE_TRANSACTION_ID] = $transactionID;

        // Begin delete comments.
        $commentIDs = $sql
            ->select("CommentID")
            ->where(["DiscussionID" => $discussionID])
            ->get("Comment")
            ->column("CommentID");

        // Note: No exception catching in this loop.
        // If some comment fails to delete, we cannot proceed with deleting the discussion.
        // These runs counter to some other bulk actions which may wish to proceed (that don't have dependencies on each other).
        foreach ($commentIDs as $commentID) {
            // Use the comment model to delete the row.
            // This should ensure that all aggregate info is updated properly
            // And is in fact the reason this whole thing is an iterator.
            $deleteResult = $commentModel->deleteID($commentID, $options);
            $existingDiscussion["CountComments"]--;

            try {
                // Yield for the generator in case we hit a timeout.
                yield;
            } catch (LongRunnerTimeoutException $e) {
                return new LongRunnerNextArgs([$discussionID, $options]);
            }
        }

        // Now delete the discussion.
        $bookmarkedUserIDs = $this->getBookmarkUserIDs($discussionID);
        try {
            $db->beginTransaction();
            $this->SQL->delete("Discussion", ["DiscussionID" => $discussionID]);
            $event = new MachineTranslationEvent("discussion", [$discussionID]);
            $this->getEventManager()->dispatch($event);
            $this->SQL->delete("UserDiscussion", ["DiscussionID" => $discussionID]);
            LogModel::insert($logMethod, "Discussion", $existingDiscussion, $logOptions);
            LogModel::endTransaction();
            $db->commitTransaction();
        } catch (Throwable $t) {
            // Rollback the transaction.
            $db->rollbackTransaction();

            // Rethrow.
            throw $t;
        }

        // Fire of an action indicating the record was deleted.
        $dataObject = (object) $existingDiscussion;
        $this->calculate($dataObject);
        $this->logger->info("Deleting Discussion", [
            Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
            Logger::FIELD_TAGS => ["delete", "discussion"],
            "stackTrace" => DebugUtils::stackTraceString(debug_backtrace()),
        ]);
        $discussionEvent = $this->eventFromRow(
            (array) $dataObject,
            ResourceEvent::ACTION_DELETE,
            $this->userModel->currentFragment()
        );
        $this->getEventManager()->dispatch($discussionEvent);

        // Update some ancillary counts.
        $this->aggregateModel()->handleDiscussionDelete($existingDiscussion, $existingDiscussion["CategoryID"]);

        // Get the user's discussion count.
        $this->updateUserDiscussionCount($insertUserID);

        // Update bookmark counts for users who had bookmarked this discussion.
        $this->recalculateBookmarkCounts($discussionID, $bookmarkedUserIDs);

        yield new LongRunnerSuccessID($discussionID);
    }

    /**
     * If we can find a discussion, try to throw a better exception based on the log model.
     *
     * @param int $discussionID
     */
    public function tryThrowGoneException(int $discussionID)
    {
        $logRow = $this->createSql()
            ->from("Log")
            ->select(["Operation", "LogID"])
            ->where(["RecordType" => "Discussion", "RecordID" => $discussionID, "Operation" => ["Spam", "Delete"]])
            ->orderBy(["-DateInserted"])
            ->limit(1)
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);
        if (!$logRow) {
            return;
        }

        switch ($logRow["Operation"]) {
            case "Spam":
                throw new ClientException("Discussion has been marked as spam.", 410, [
                    "discussionID" => $discussionID,
                    "logID" => $logRow["LogID"],
                ]);
            case "Delete":
                throw new ClientException("Discussion has been deleted.", 410, [
                    "discussionID" => $discussionID,
                    "logID" => $logRow["LogID"],
                ]);
        }
    }

    /**
     * Move a single discussion into another category.
     *
     * @param array|int $discussionOrDiscussionID What discussion or discussionID should we move the discussion into.
     * @param array|int $categoryOrCategoryID What category or categoryID should we move the discussion into.
     * @param bool $addRedirects Should we create a redirect from this discussion.
     * @param string|null $postTypeID
     * @return bool True on success. Throws on failures.
     *
     * @throws ValidationException If saving didn't work.
     * @throws ClientException If the discussion can't be moved to that category.
     * @throws NotFoundException If the discussion or category didn't exist.
     */
    public function moveDiscussion(
        $discussionOrDiscussionID,
        $categoryOrCategoryID,
        bool $addRedirects,
        ?string $postTypeID = null
    ): bool {
        $newCategory = is_numeric($categoryOrCategoryID)
            ? CategoryModel::categories($categoryOrCategoryID)
            : $categoryOrCategoryID;
        if (!$newCategory) {
            throw new NotFoundException("Category", ["categoryID" => $categoryOrCategoryID]);
        }

        $discussion = is_numeric($discussionOrDiscussionID)
            ? $this->getID($discussionOrDiscussionID, DATASET_TYPE_ARRAY)
            : $discussionOrDiscussionID;
        if (!$discussion) {
            throw new NotFoundException("Discussion", ["discussionID" => $discussionOrDiscussionID]);
        }
        // Default/Empty/Null discussion type is a discussion.
        $discussion["Type"] = $discussion["Type"] ?? self::DISCUSSION_TYPE;

        $discussionID = $discussion["DiscussionID"];
        $newCategoryID = $newCategory["CategoryID"];

        // Only do category update if the target category is different.
        $discussionCategoryID = $discussion["CategoryID"] ?? null;
        if ($discussionCategoryID !== $newCategoryID) {
            $postingCategory = CategoryModel::doesCategoryAllowPosts($newCategoryID);

            if (!$postingCategory) {
                throw new ClientException("Destination category does not allow discussions.", 400, [
                    "discussionID" => $discussionID,
                    "destinationCategoryID" => $newCategoryID,
                ]);
            }

            $allowedDiscussionTypes = $newCategory["AllowedDiscussionTypes"];

            $allowedDiscussionTypes = is_array($allowedDiscussionTypes)
                ? $allowedDiscussionTypes
                : array_keys(\DiscussionModel::discussionTypes());

            $eventManager = $this->getEventManager();
            $allowedDiscussionTypes = $eventManager->fireFilter(
                "discussionModel_moveAllowedTypes",
                $allowedDiscussionTypes,
                $newCategory
            );

            $allowedDiscussionTypes = array_map("strtolower", $allowedDiscussionTypes);

            if (
                isset($discussion["Type"]) &&
                !empty($allowedDiscussionTypes) &&
                !in_array(strtolower($discussion["Type"]), $allowedDiscussionTypes)
            ) {
                throw new ClientException("Discussion type is not allowed in the destination category.", 400, [
                    "discussionID" => $discussionID,
                    "discussionType" => $discussion["Type"],
                    "allowedDiscussionTypes" => $allowedDiscussionTypes,
                ]);
            }

            // Create the redirect.
            if ($addRedirects) {
                $this->createRedirect($discussion);
                ModelUtils::validationResultToValidationException($this);
            }

            $save = [
                "CategoryID" => $newCategory["CategoryID"],
                "DiscussionID" => $discussionID,
            ];
            // Fire event in case some addon needs to change other data during the move.
            $this->EventArguments = [
                "discussion" => &$discussion,
                "save" => &$save,
            ];
            $this->fireEvent("beforeDiscussionMoveSave");

            // Move the discussion.
            $this->save($this->EventArguments["save"]);

            // throw any validation errors.
            ModelUtils::validationResultToValidationException($this);

            // Dispatch a Discussion event (move)
            $senderUserID = Gdn::session()->UserID;
            $sender = $senderUserID ? Gdn::userModel()->getFragmentByID($senderUserID) : null;

            $discussion = $this->getID($discussionID, DATASET_TYPE_ARRAY);
            $discussionEvent = $this->eventFromRow($discussion, DiscussionEvent::ACTION_MOVE, $sender);
            $discussionEvent->setSourceCategoryID($discussionCategoryID);
            $this->getEventManager()->dispatch($discussionEvent);
        }

        // Update the post type using the DiscussionTypeConverter if we are changing the post type.
        if (isset($postTypeID) && $discussion["postTypeID"] !== $postTypeID) {
            $discussionTypeConverter = Gdn::getContainer()->get(\Vanilla\DiscussionTypeConverter::class);
            $discussionTypeConverter->convert($discussion, $postTypeID, isCustomPostType: true);
        }
        return true;
    }

    /**
     * Get long runner count of total items to process.
     *
     * @param array $discussionIDs DiscussionIDs to move.
     *
     * @return int
     * @throws Exception
     */
    public function getTotalCount(array $discussionIDs): int
    {
        $discussionIDs = array_unique($discussionIDs);
        return $this->getCount(["d.DiscussionID" => $discussionIDs]);
    }

    /**
     * Redirect to the url specified by the discussion if it's a redirect.
     *
     * @param array $discussionRow
     */
    public function tryRedirectFromDiscussion(array $discussionRow): void
    {
        $type = $discussionRow["type"] ?? ($discussionRow["Type"] ?? null);
        $body = $discussionRow["body"] ?? ($discussionRow["Body"] ?? "");
        $format = $discussionRow["format"] ?? ($discussionRow["Format"] ?? null);

        if ($type && strtolower($type) === "redirect") {
            $renderedBody = \Gdn::formatService()->renderHTML($body, $format ?? null);
            if (preg_match('`href="([^"]+)"`i', $renderedBody, $matches)) {
                $url = $matches[1];
                redirectTo(htmlspecialchars_decode($url), 301);
            }
        }
    }

    /**
     * Create an iterable for moving discussions.
     *
     * @param array $discussionIDs DiscussionIDs to move.
     * @param int $categoryID CategoryID to move discussions into.
     * @param bool $addRedirects If a redirect needs to be created.
     * @param string|null $postTypeID
     *
     * @return Generator<int, LongRunnerItemResultInterface, null, string|LongRunnerNextArgs>
     */
    public function moveDiscussionsIterator(
        array $discussionIDs,
        int $categoryID,
        bool $addRedirects,
        ?string $postTypeID = null
    ): Generator {
        $discussionIDs = array_unique($discussionIDs);
        $handledDiscussionIDs = [];
        try {
            yield new LongRunnerQuantityTotal([$this, "getTotalCount"], [$discussionIDs]);

            foreach ($discussionIDs as $discussionID) {
                try {
                    $this->moveDiscussion($discussionID, $categoryID, $addRedirects, $postTypeID);
                    $handledDiscussionIDs[] = $discussionID;
                    yield new LongRunnerSuccessID($discussionID);
                } catch (Exception $e) {
                    if ($e instanceof LongRunnerTimeoutException) {
                        // Rethrow up to hit the outer catch.
                        throw $e;
                    }
                    $handledDiscussionIDs[] = $discussionID;
                    yield new LongRunnerFailedID($discussionID, $e);
                }
            }
        } catch (LongRunnerTimeoutException $e) {
            $remainingDiscussionIDs = array_diff($discussionIDs, $handledDiscussionIDs);
            return new LongRunnerNextArgs([$remainingDiscussionIDs, $categoryID, $addRedirects, $postTypeID]);
        }
    }

    /**
     * Create an iterable for closing/opening discussions. Assumes caller has validated existence of discussions and associated user permissions.
     *
     * @param array $discussionIDs DiscussionIDs to close/open.
     * @param bool $closing Close (true) or open (false) this set of discussions
     *
     * @return Generator<int, LongRunnerItemResultInterface, null, string|LongRunnerNextArgs>
     *
     * @throws NotFoundException If the category we are closing/opening doesn't exist.
     */
    public function closeDiscussionsIterator(array $discussionIDs, bool $closing): Generator
    {
        $discussionIDs = array_unique($discussionIDs);
        $handledDiscussionIDs = [];
        try {
            yield new LongRunnerQuantityTotal([$this, "getTotalCount"], [$discussionIDs]);

            foreach ($discussionIDs as $discussionID) {
                try {
                    if ($closing) {
                        $this->closeDiscussion($discussionID);
                    } else {
                        $this->openDiscussion($discussionID);
                    }
                    $handledDiscussionIDs[] = $discussionID;
                    yield new LongRunnerSuccessID($discussionID);
                } catch (Exception $e) {
                    if ($e instanceof LongRunnerTimeoutException) {
                        // Rethrow up to hit the outer catch.
                        throw $e;
                    }
                    $handledDiscussionIDs[] = $discussionID;
                    yield new LongRunnerFailedID($discussionID, $e);
                }
            }
        } catch (LongRunnerTimeoutException $e) {
            $remainingDiscussionIDs = array_diff($discussionIDs, $handledDiscussionIDs);
            return new LongRunnerNextArgs([$remainingDiscussionIDs, $closing]);
        }
        return LongRunner::FINISHED;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $user
     * @param array $record
     * @return bool
     */
    public function insertUserMentions(array $user, array $record): bool
    {
        $fields["userID"] = $user["userID"] ?? false;
        $fields["mentionedName"] = $user["name"] ?? false;
        $fields["recordID"] = $record["DiscussionID"] ?? ($record["discussionID"] ?? false);
        $fields["parentRecordID"] = $record["CategoryID"] ?? ($record["categoryID"] ?? null);

        if ($fields["userID"] && $fields["mentionedName"] && $fields["recordID"]) {
            $fields["recordType"] = $this->userMentionsModel::DISCUSSION;
            $fields["parentRecordType"] = "category";
            $fields["dateInserted"] = $this->userMentionsModel->getDate($record);
            $fields["status"] = $this->userMentionsModel::ACTIVE_STATUS;
            $this->userMentionsModel->insert($fields, [$this->userMentionsModel::OPT_REPLACE => true]);
        }

        return false;
    }

    /**
     *  Update the hot column value
     * @param array $where
     */

    public function updateColumnHot(array $where = [])
    {
        $this->createSql()
            ->update("Discussion")
            ->set("hot", $this->getHotCalculationExpression())
            ->where($where)
            ->put();
    }

    /**
     * Get a SQL expression to update the "hot" value of a discussion if there are comments on the discussion.
     *
     * @return RawExpression
     */
    public function getHotCalculationExpression(): RawExpression
    {
        return new RawExpression(
            <<<SQL
floor(
CASE WHEN CountComments = 0 AND coalesce(Score, 0) <= 0
    THEN
        0
    ELSE
        unix_timestamp(DateInserted) + unix_timestamp(
            date_add(
                greatest(
                    coalesce(DateUpdated, '1970-01-01'),
                    DateLastComment,
                    DateInserted
                ),
                interval(CountComments * 10)+ (coalesce(Score, 0) * 5) minute)
    )
END)
SQL
        );
    }

    /**
     * Given a SQL query where we have `GDN_Discussion as discussion` apply permission filters.
     *
     * @param Gdn_SQLDriver $sql
     *
     * @return void
     */
    public function applyDiscussionCategoryPermissionsWhere(Gdn_SQLDriver $sql): void
    {
        if (
            $this->getSessionInterface()
                ->getPermissions()
                ->has("site.manage")
        ) {
            $sql->where("d.DiscussionID IS NOT NULL");
            return;
        }

        $permCategoryIDs = $this->categoryModel->getCategoryIDsWithPermissionForUser(
            userID: Gdn::session()->UserID,
            permission: "Vanilla.Discussions.View"
        );

        $whereGroups = [
            [
                "d.CategoryID" => $permCategoryIDs,
            ],
        ];

        $event = new DiscussionPermissionQueryEvent($sql, $whereGroups);
        $this->getEventManager()->dispatch($event);
        $sql->applyWheresToQuery($event->getWhereGroups());
    }

    /**
     * Update either the Type column or postTypeID (if custom post types is enabled).
     *
     * @param int $id
     * @param string|null $to
     * @return void
     */
    public function setType(int $id, ?string $to, bool $forceNullDiscussion = false, ?array $postMeta = null): void
    {
        if (PostTypeModel::isPostTypesFeatureEnabled()) {
            // If Post Types is on, then we are converting to a custom post type.

            if (isset(PostTypeModel::LEGACY_TYPE_MAP[$to])) {
                // If this is a legacy type name, convert to post type ID first.
                $to = PostTypeModel::LEGACY_TYPE_MAP[$to];
            }
            $postType = $this->postTypeModel->getByID($to);
            if (!isset($postType)) {
                ErrorLogger::error("Failed to lookup post type named '$to'", ["discussions"]);
                return;
            }

            try {
                $this->SQL->Database->beginTransaction();
                $discussion = $this->getID($id, DATASET_TYPE_ARRAY);
                $fromType = $discussion["postTypeID"] ?? lcfirst($discussion["Type"]);
                if (isset($postMeta)) {
                    $this->postMetaModel->updatePostMeta("discussion", $id, $postMeta);
                } else {
                    $this->postMetaModel->movePostMeta($id, $fromType, $to);
                }

                $this->setField($id, [
                    "postTypeID" => $postType["postTypeID"],
                    "Type" =>
                        $postType["baseType"] === "discussion" && $forceNullDiscussion
                            ? null
                            : ucfirst($postType["baseType"]),
                ]);
                $this->SQL->Database->commitTransaction();
            } catch (Throwable $e) {
                $this->SQL->Database->rollbackTransaction();
                throw $e;
            }
        } else {
            $this->setField($id, "Type", $to === "Discussion" && $forceNullDiscussion ? null : $to);
        }
    }
}
