<?php
/**
 * Comment model
 *
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0
 */

use Garden\Container\ContainerException;
use Garden\EventManager;
use Garden\Events\ResourceEvent;
use Garden\Events\EventFromRowInterface;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\SimpleCache\CacheInterface;
use Vanilla\Community\Events\MachineTranslationEvent;
use Vanilla\Community\Schemas\CategoryFragmentSchema;
use Vanilla\Dashboard\DocumentModel;
use Vanilla\Forum\Models\AbstractCommentParentHandler;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Attributes;
use Vanilla\Community\Events\CommentQueryEvent;
use Vanilla\Community\Schemas\PostFragmentSchema;
use Vanilla\Dashboard\AiSuggestionModel;
use Vanilla\Dashboard\Models\PremoderationModel;
use Vanilla\Dashboard\Models\AiSuggestionSourceService;
use Vanilla\Dashboard\Models\UserMentionsInterface;
use Vanilla\Dashboard\Models\UserMentionsModel;
use Vanilla\Database\SetLiterals\Increment;
use Vanilla\Events\LegacyDirtyRecordTrait;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Formatting\Exception\FormatterNotFoundException;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\FormatFieldTrait;
use Vanilla\Formatting\UpdateMediaTrait;
use Vanilla\Forum\Jobs\DeferredResourceEventJob;
use Vanilla\Forum\Models\CommentThreadModel;
use Vanilla\Forum\Models\CommunityManagement\EscalationCommentModel;
use Vanilla\Forum\Models\DiscussionCommentModel;
use Vanilla\Forum\Models\ForumAggregateModel;
use Vanilla\ImageSrcSet\ImageSrcSetService;
use Vanilla\ImageSrcSet\MainImageSchema;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\LayoutModel;
use Vanilla\Layout\LayoutViewModel;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Models\Model;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Permissions;
use Vanilla\Premoderation\PremoderationException;
use Vanilla\Premoderation\PremoderationItem;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Schema\RangeExpression;
use Vanilla\SchemaFactory;
use Vanilla\Community\Events\CommentEvent;
use Vanilla\Contracts\Formatting\FormatFieldInterface;
use Vanilla\Site\OwnSite;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\DebugUtils;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\TwigStaticRenderer;
use Webmozart\Assert\Assert;
use Vanilla\Search\SearchService;
use Vanilla\Search\SearchTypeQueryExtenderInterface;

/**
 * Manages discussion comments data.
 */
class CommentModel extends Gdn_Model implements
    FormatFieldInterface,
    EventFromRowInterface,
    \Vanilla\Contracts\Models\CrawlableInterface,
    UserMentionsInterface,
    LoggerAwareInterface
{
    use \Vanilla\FloodControlTrait;

    use UpdateMediaTrait;

    use FormatFieldTrait;

    use LegacyDirtyRecordTrait;

    use LoggerAwareTrait;

    /** Threshold. */
    const COMMENT_THRESHOLD_SMALL = 1000;

    /** Threshold. */
    const COMMENT_THRESHOLD_LARGE = 50000;

    /** Trigger to recalculate counter. */
    const COUNT_RECALC_MOD = 50;

    /** @var array List of fields to order results by. */
    protected $_OrderBy = [["DateInserted", ""]];

    /** @var array Wheres. */
    protected $_Where = [];

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var array */
    private $options;

    /**
     * @var CacheInterface Object used to store the FloodControl data.
     */
    protected $floodGate;

    /** @var FormatService */
    private $formatterService;

    /**
     * @var CommentModel $instance;
     */
    private static $instance;

    /** @var UserModel */
    private $userModel;

    /** @var CategoryModel */
    private $categoryModel;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var OwnSite */
    private $ownSite;

    /** @var ImageSrcSetService */
    private $imageSrcSetService;

    /** @var UserMentionsModel */
    private $userMentionsModel;

    /** @var AiSuggestionModel */
    private $aiSuggestionModel;

    private CommentThreadModel $threadModel;

    private ReactionModel $reactionModel;

    /** @var array<string, AbstractCommentParentHandler> */
    private array $parentHandlers;

    /** @var LayoutViewModel */
    private LayoutViewModel $layoutViewModel;

    /** @var LayoutModel */
    private LayoutModel $layoutModel;

    /** @var DocumentModel */
    private DocumentModel $documentModel;
    /**
     * Class constructor. Defines the related database table name.
     *
     * @param Gdn_Validation $validation The validation dependency.
     */
    public function __construct(Gdn_Validation $validation = null)
    {
        parent::__construct("Comment", $validation);

        $this->imageSrcSetService = Gdn::getContainer()->get(ImageSrcSetService::class);

        $this->floodGate = FloodControlHelper::configure($this, "Vanilla", "Comment");

        $this->discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
        $this->userModel = Gdn::getContainer()->get(UserModel::class);
        $this->categoryModel = Gdn::getContainer()->get(CategoryModel::class);
        $this->siteSectionModel = Gdn::getContainer()->get(SiteSectionModel::class);
        $this->userMentionsModel = Gdn::getContainer()->get(UserMentionsModel::class);
        $this->setFormatterService(Gdn::getContainer()->get(FormatService::class));
        $this->setMediaForeignTable($this->Name);
        $this->setMediaModel(Gdn::getContainer()->get(MediaModel::class));
        $this->setSessionInterface(Gdn::getContainer()->get("Session"));
        $this->ownSite = \Gdn::getContainer()->get(OwnSite::class);
        $this->reactionModel = Gdn::getContainer()->get(ReactionModel::class);
        $this->aiSuggestionModel = Gdn::getContainer()->get(AiSuggestionModel::class);
        $this->threadModel = Gdn::getContainer()->get(CommentThreadModel::class);
        $this->layoutViewModel = Gdn::getContainer()->get(LayoutViewModel::class);
        $this->layoutModel = Gdn::getContainer()->get(LayoutModel::class);
        $this->documentModel = Gdn::getContainer()->get(DocumentModel::class);

        // Registering default parent types
        $this->registerCommentParentType(DiscussionCommentModel::class);
        $this->registerCommentParentType(EscalationCommentModel::class);
    }

    /**
     * @param Gdn_DatabaseStructure $structure
     * @return void
     */
    public static function structure(Gdn_DatabaseStructure $structure): void
    {
        $structure->table("Comment");
        $structure
            ->table("Comment")
            ->primaryKey("CommentID")
            ->column("InsertUserID", "int", true)
            // "Legacy" place to store discussion parentRecordID
            ->column("DiscussionID", "int", true)
            // Temporarily nullable for backwards compatibility.
            ->column("parentRecordType", "varchar(10)")
            ->column("parentRecordID", "int")
            ->column("parentCommentID", "int", true)
            ->column("UpdateUserID", "int", true)
            ->column("DeleteUserID", "int", true)
            ->column("Body", "mediumtext", false)
            ->column("Format", "varchar(20)", true)
            ->column("DateInserted", "datetime", null)
            ->column("DateDeleted", "datetime", true)
            ->column("DateUpdated", "datetime", true)
            ->column("InsertIPAddress", "ipaddress", true)
            ->column("UpdateIPAddress", "ipaddress", true)
            ->column("Flag", "tinyint", 0)
            ->column("Score", "float", null)
            ->column("depth", "int", 1)
            ->column("countChildComments", "int", 0)
            ->column("scoreChildComments", "int", 0)
            ->column("Attributes", "text", true)
            ->column("Sentiment", "tinyint(4)", true, "sentiment")
            ->set();

        // Indexes

        $structure
            ->table("Comment")
            ->createIndexIfNotExists("IX_Comment_parentCommentID", ["parentCommentID"])
            ->createIndexIfNotExists("IX_Comment_DateInserted_parentRecordType_parentRecordID", [
                "DateInserted",
                "parentRecordType",
                "parentRecordID",
            ])
            // Added as a covering index.
            // Name cannot include all fields due to max length on index names in MySQL.
            ->createIndexIfNotExists("IX_Comment_commentThread_dateInserted", [
                "parentRecordType",
                "parentRecordID",
                "DateInserted",
                "parentCommentID",
            ])
            // Not needed anymore due to the above covering index.
            ->dropIndexIfExists("IX_Comment_parentRecordType_parentRecordID_DateInserted")
            ->createIndexIfNotExists("IX_Comment_InsertUserID_parentRecordType_parentRecordID", [
                "InsertUserID",
                "parentRecordType",
                "parentRecordID",
            ])
            ->createIndexIfNotExists("IX_Comment_Score", ["Score"])
            ->createIndexIfNotExists("IX_Comment_parentRecord_InsertUserID_DateInserted", [
                "parentRecordType",
                "parentRecordID",
                "InsertUserID",
                "DateInserted",
            ])

            // Legacy indexes
            ->tryRenameIndex("IX_Comment_1", "IX_Comment_DiscussionID_DateInserted")
            ->createIndexIfNotExists("IX_Comment_DateInserted", ["DateInserted"])
            ->createIndexIfNotExists("IX_Comment_InsertUserID_DiscussionID", ["InsertUserID", "DiscussionID"])
            ->createIndexIfNotExists("IX_Comment_DiscussionID_DateInserted", ["DiscussionID", "DateInserted"])
            ->createIndexIfNotExists("IX_Comment_DiscussionID_InsertUserID_DateInserted", [
                "DiscussionID",
                "InsertUserID",
                "DateInserted",
            ])
            ->createIndexIfNotExists("IX_Sentiment_CommentID", ["Sentiment", "CommentID"]);

        // Allows the tracking of already-read comments & votes on a per-user basis.
        $structure
            ->table("UserComment")
            ->column("UserID", "int", false, "primary")
            ->column("CommentID", "int", false, "primary")
            ->column("Score", "float", null)
            ->column("DateLastViewed", "datetime", null) // null signals never
            ->set();
    }

    /**
     * @param string $parentRecordType
     * @param int $parentRecordID
     * @param bool $throw
     *
     * @return bool
     * @throws PermissionException
     * @throws ClientException
     */
    public function hasViewPermission(string $parentRecordType, int $parentRecordID, bool $throw = true): bool
    {
        $parentHandler = $this->getParentHandler($parentRecordType);
        return $parentHandler->hasViewPermission($parentRecordID, $throw);
    }

    /**
     * Get an automatic slot type based on the date of parent records.
     *
     * @param string $parentRecordType
     * @param int $parentRecordID
     * @return string
     * @throws ClientException
     */
    public function getAutoSlotType(string $parentRecordType, int $parentRecordID): string
    {
        return $this->getParentHandler($parentRecordType)->getAutoSlotType($parentRecordID);
    }

    /**
     * @return ForumAggregateModel
     */
    private function forumAggregateModel(): ForumAggregateModel
    {
        return Gdn::getContainer()->get(ForumAggregateModel::class);
    }

    /**
     * @return PremoderationModel
     */
    private function premoderationModel(): PremoderationModel
    {
        return Gdn::getContainer()->get(PremoderationModel::class);
    }

    /**
     * The shared instance of this object.
     *
     * @return CommentModel Returns the instance.
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new CommentModel();
        }
        return self::$instance;
    }

    /**
     * Create an optimized based comment query with required tables joined and permimssion filtering applied.
     *
     * @param array $parentRecordTypes
     * @param array $where
     * @param bool $joinUsers
     *
     * @return Gdn_SQLDriver
     */
    public function permissionedCommentQuery(
        #[JetBrains\PhpStorm\ExpectedValues(values: ["discussion", "escalation"])] array $parentRecordTypes,
        array $where,
        array $options = [],
        bool $joinUsers = false
    ): Gdn_SQLDriver {
        $subQuery = $this->permissionedCommentBaseQuery($parentRecordTypes, $where);
        $defaultOptions = [
            Model::OPT_LIMIT => $this->getDefaultLimit(),
            Model::OPT_OFFSET => 0,
            Model::OPT_ORDER => "DateInserted",
            Model::OPT_DIRECTION => "asc",
        ];
        $options += $defaultOptions;
        if ($options[Model::OPT_ORDER] === "dateUpdated") {
            $options[Model::OPT_ORDER] = "sortDateUpdated";
            $subQuery->select("c.dateUpdated, c.dateInserted", "COALESCE", "sortDateUpdated");
        } elseif (!empty($options[Model::OPT_ORDER]) && $options[Model::OPT_ORDER] !== ModelUtils::SORT_TRENDING) {
            $options[Model::OPT_ORDER] = "c.{$options[Model::OPT_ORDER]}";
        }
        $subQuery->applyModelOptions($options);

        // Outer query selects
        $outerQuery = $this->createSql()
            ->with("CommentBase", $subQuery)
            ->select("cb.*")
            ->select("c.*")
            ->from("@CommentBase cb")
            ->join("Comment c", "cb.CommentID = c.CommentID");

        if ($joinUsers) {
            $outerQuery
                ->select("iu.Name", "", "InsertName")
                ->select("iu.Photo", "", "InsertPhoto")
                ->select("iu.Email", "", "InsertEmail")
                ->join("User iu", "c.InsertUserID = iu.UserID", "left")
                ->select("uu.Name", "", "UpdateName")
                ->select("uu.Photo", "", "UpdatePhoto")
                ->select("uu.Email", "", "UpdateEmail")
                ->join("User uu", "c.UpdateUserID = uu.UserID", "left");
        }

        $this->addParentCommentQuery($outerQuery);

        return $outerQuery;
    }

    /**
     * Do a database join of user data.
     *
     * @param Gdn_SQLDriver $sql
     * @return Gdn_SQLDriver
     */
    private function joinUsersSql(Gdn_SQLDriver $sql): Gdn_SQLDriver
    {
        return $sql
            ->select("iu.Name", "", "InsertName")
            ->select("iu.Photo", "", "InsertPhoto")
            ->select("iu.Email", "", "InsertEmail")
            ->join("User iu", "c.InsertUserID = iu.UserID", "left")
            ->select("uu.Name", "", "UpdateName")
            ->select("uu.Photo", "", "UpdatePhoto")
            ->select("uu.Email", "", "UpdateEmail")
            ->join("User uu", "c.UpdateUserID = uu.UserID", "left");
    }

    /**
     * Create an optimized based comment query with required tables joined and permimssion filtering applied.
     *
     * @param array $parentRecordTypes
     * @param array $where
     *
     * @return Gdn_SQLDriver
     */
    private function permissionedCommentBaseQuery(
        #[JetBrains\PhpStorm\ExpectedValues(values: ["discussion", "escalation"])] array $parentRecordTypes,
        array $where
    ): Gdn_SQLDriver {
        Assert::notEmpty($parentRecordTypes, "You must specify parentRecordTypes.");

        $subQuery = $this->createSql()->from("Comment c");

        $permissionWheres = [];

        foreach ($parentRecordTypes as $parentRecordType) {
            $parentRecordType = strtolower($parentRecordType);
            $parentHandler = $this->getParentHandler($parentRecordType);
            $parentHandler->applyCommentQueryFiltering($subQuery, $permissionWheres, $where);
        }

        $parentName = $this->getParentRecordField("getParentNameField", $parentRecordTypes);
        $parentPlaceID = $this->getParentRecordField("getPlaceIDField", $parentRecordTypes);
        $parentPlaceRecordType = $this->getParentRecordField("getPlaceRecordTypeField", $parentRecordTypes);

        $subQuery
            ->select("c.CommentID")
            ->select($parentName, "coalesce", "parentRecordName")
            ->select($parentName, "coalesce", "DiscussionName") // Backwards compatibility
            ->select($parentPlaceID, "coalesce", "CategoryID")
            ->select($parentPlaceID, "coalesce", "placeRecordID")
            ->select($parentPlaceRecordType, "coalesce", "placeRecordType")
            ->select("c.DateInserted");

        // We only want records that joined on in some type of way.
        if (count($permissionWheres) !== 0) {
            $subQuery->beginWhereGroup();
            foreach ($permissionWheres as $permWhere) {
                $subQuery->orOp();
                $subQuery->beginWhereGroup();
                $subQuery->where($permWhere);
                $subQuery->endWhereGroup();
            }
            $subQuery->endWhereGroup();
        }
        // Legacy event, Currently used by PennyArcade theme.
        $sqlBefore = $this->SQL;
        $this->SQL = $subQuery;
        $this->fireEvent("BeforeGet");
        $subQuery = $this->SQL;
        $this->SQL = $sqlBefore;

        // Where on dirty records.
        $joinDirtyRecords = $where[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;
        if (isset($where[DirtyRecordModel::DIRTY_RECORD_OPT])) {
            unset($where[DirtyRecordModel::DIRTY_RECORD_OPT]);
        }
        if ($joinDirtyRecords) {
            $this->applyDirtyWheres("c", $subQuery);
        }

        // If we have a user role where, make sure we join on that table.
        $insertUserRoleIDs = $where["uri.RoleID"] ?? null;
        if (!empty($insertUserRoleIDs)) {
            $subQuery->join("UserRole uri", "c.InsertUserID = uri.UserID")->where("uri.RoleID", $insertUserRoleIDs);
            $subQuery->distinct();
        }

        // All fields should be associated with a table. If there isn't one, assign it to comments.
        foreach ($where as $field => $value) {
            if (!str_contains($field, ".")) {
                $where["c.{$field}"] = $value;
                unset($where[$field]);
            }
        }

        if (isset($where["c.DiscussionID"]) && !isset($where["c.parentRecordID"])) {
            $where["c.parentRecordType"] = "discussion";
            $where["c.parentRecordID"] = $where["c.DiscussionID"];
            unset($where["c.DiscussionID"]);
        }

        $subQuery->where($where);

        if (in_array("discussion", $parentRecordTypes)) {
            // Groups hooks here to join a group ID off the discussion.
            $extraSelects = \Gdn::eventManager()->fireFilter("commentModel_extraSelects", []);
            if (!empty($extraSelects)) {
                $subQuery->select($extraSelects);
            }
        }
        return $subQuery;
    }
    /**
     * {@inheritdoc}
     */
    public function get($orderFields = "", $orderDirection = "asc", $limit = false, $pageNumber = false)
    {
        if (is_numeric($orderFields)) {
            deprecated('CommentModel->get($discussionID, ...)', 'CommentModel->getByDiscussion($discussionID, ...)');
            return $this->getByDiscussion($orderFields, $orderDirection, $limit);
        }

        throw new \BadMethodCallException("CommentModel->get() is not supported.", 400);
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
    ): Gdn_DataSet {
        [$where, $options] = $this->splitWhere($where, ["joinUsers" => true, "joinDiscussions" => false]);

        $options += [
            Model::OPT_LIMIT => $limit,
            Model::OPT_OFFSET => $offset,
            Model::OPT_ORDER => $orderFields,
            Model::OPT_DIRECTION => $orderDirection,
        ];

        // Things need to opt-in to more than just discussion.
        $parentRecordType = $where["c.parentRecordType"] ?? ["discussion"];
        $result = $this->permissionedCommentQuery($parentRecordType, $where, $options)->get();

        if ($options["joinUsers"]) {
            $this->userModel->joinUsers($result, ["InsertUserID", "UpdateUserID"]);
        }

        if ($options["joinDiscussions"]) {
            $this->discussionModel->joinDiscussionData($result, "DiscussionID", $options["joinDiscussions"]);
        }
        $this->setCalculatedFields($result);

        $eventManager = $this->getEventManager();
        $eventManager->dispatch(new CommentQueryEvent($result));
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
    public function getByDiscussion(int $discussionID, $limit, $offset = 0, array $where = []): Gdn_DataSet
    {
        $where = array_merge($where, [
            "d.DiscussionID" => $discussionID,
        ]);

        $options = [
            Model::OPT_LIMIT => $limit,
            Model::OPT_OFFSET => $offset,
        ];
        $presetOrderBy = $this->orderBy();
        if (isset($presetOrderBy)) {
            $options[Model::OPT_ORDER] = $presetOrderBy[0][0];
            $options[Model::OPT_DIRECTION] = $presetOrderBy[0][1];
        }

        $result = $this->permissionedCommentQuery(["discussion"], where: $where, options: $options)->get();

        $this->userModel->joinUsers($result, ["InsertUserID", "UpdateUserID"]);

        $this->setCalculatedFields($result);

        $eventManager = $this->getEventManager();
        $eventManager->dispatch(new CommentQueryEvent($result));

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
     * @return Gdn_DataSet SQL results.
     */
    public function getByUser(int $userID, $limit, $offset = 0): Gdn_DataSet
    {
        $options = [
            Model::OPT_LIMIT => $limit,
            Model::OPT_OFFSET => $offset,
        ];

        // Build main query
        $data = $this->permissionedCommentQuery(
            // Only discussions supported here for now.
            parentRecordTypes: ["discussion"],
            where: ["c.InsertUserID" => $userID],
            options: $options
        )->get();

        $this->userModel->joinUsers($data, ["InsertUserID", "UpdateUserID"]);

        $eventManager = $this->getEventManager();
        $eventManager->dispatch(new CommentQueryEvent($data));

        return $data;
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
     * @inheritdoc
     */
    public function getDefaultLimit()
    {
        return c("Vanilla.Comments.PerPage", 30);
    }

    /**
     *
     * Get comments based on specific criteria, optionally filtered by user permissions.
     *
     * @param array $where Conditions for filtering comments with a WHERE clause.
     * @param array $options Standard model query options.
     * @return Gdn_DataSet SQL results.
     */
    public function selectComments(array $where = [], array $options = [])
    {
        $options += [
            Model::OPT_LIMIT => $this->getDefaultLimit(),
            Model::OPT_OFFSET => 0,
            Model::OPT_DIRECTION => "desc",
            Model::OPT_ORDER => "CommentID",
        ];

        $parentRecordTypes = isset($where["parentRecordType"])
            ? [$where["parentRecordType"]]
            : $this->getParentRecordTypes();
        $query = $this->permissionedCommentQuery($parentRecordTypes, $where, $options);
        if ($options[Model::OPT_ORDER] === "dateUpdated") {
            $options[Model::OPT_ORDER] = "sortDateUpdated";
            $query->select("c.dateUpdated, c.dateInserted", "COALESCE", "sortDateUpdated");
        } elseif (!empty($options[Model::OPT_ORDER]) && $options[Model::OPT_ORDER] !== ModelUtils::SORT_TRENDING) {
            $options[Model::OPT_ORDER] = "c.{$options[Model::OPT_ORDER]}";
        }

        unset($options[Model::OPT_LIMIT]);
        unset($options[Model::OPT_OFFSET]);
        $query->applyModelOptions($options);

        $result = $query->get();
        $eventManager = $this->getEventManager();
        $eventManager->dispatch(new CommentQueryEvent($result));
        return $result;
    }

    /**
     * Get a limited pagination count.
     *
     * @param array $where
     * @param int $maxLimit
     * @return int
     */
    public function selectPagingCount(array $where, int $maxLimit = 10000): int
    {
        $parentRecordTypes = isset($where["c.parentRecordType"]) ? [$where["c.parentRecordType"]] : ["discussion"];
        $baseQuery = $this->permissionedCommentBaseQuery($parentRecordTypes, $where);

        $count = $baseQuery->getPagingCount("c.CommentID", $maxLimit);
        return $count;
    }

    /**
     * Set the order of the comments or return current order.
     *
     * Getter/setter for $this->_OrderBy.
     *
     * @since 2.0.0
     * @access public
     *
     * @param mixed $value Field name(s) to order results by. May be a string or array of strings.
     * @return array $this->_OrderBy (optionally).
     */
    public function orderBy($value = null)
    {
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
                if (stringEndsWith($part, " desc", true)) {
                    $orderBy[] = [substr($part, 0, -5), "desc"];
                } elseif (stringEndsWith($part, " asc", true)) {
                    $orderBy[] = [substr($part, 0, -4), "asc"];
                } else {
                    $orderBy[] = [$part, "asc"];
                }
            }
            $this->_OrderBy = $orderBy;
        } elseif (is_a($value, "Gdn_SQLDriver")) {
            // Set the order of the given sql.
            foreach ($this->_OrderBy as $parts) {
                $value->orderBy($parts[0], $parts[1]);
            }
        }
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
     * @param string|null $maxDateInserted The most recent insert date of the viewed comments.
     * @deprecated Use `DiscussionModel::setWatch()` instead.
     */
    public function setWatch($discussion, $limit, $offset, $totalComments, $maxDateInserted = null)
    {
        deprecated("CommentModel::setWatch()", "DiscussionModel::setWatch()");

        /* @var DiscussionModel $discussionModel */
        $this->discussionModel->setWatch($discussion, $limit, $offset, $totalComments, $maxDateInserted);
    }

    /**
     * {@inheritdoc}
     */
    public function getCount($wheres = "")
    {
        if (is_numeric($wheres)) {
            deprecated("CommentModel->getCount(int)", "CommentModel->getCountByDiscussion()");
            return $this->getCountByDiscussion($wheres);
        }

        return parent::getCount($wheres);
    }

    /**
     * Get a schema instance comprised of standard comment fields.
     *
     * @return Schema
     * @throws ContainerException
     */
    public function schema(): Schema
    {
        $result = Schema::parse([
            "commentID:i" => "The ID of the comment.",
            "discussionID:i?" => "The ID of the discussion.",
            "parentRecordType:s?",
            "parentRecordID:i?",
            "parentCommentID:i?",
            "discussionCollapseID:s?",
            "name:s?" => [
                "description" => "The name of the comment",
                "x-localize" => true,
            ],
            "_name:s?",
            "categoryID:i?" => "The ID of the category of the comment",
            "category?" => new CategoryFragmentSchema(),
            "body:s?" => [
                "description" => "The body of the comment.",
            ],
            "draftID:i?" => [
                "description" => "The draft ID which should be deleted after this comment is saved.",
            ],
            "bodyRaw:s?",
            "bodyPlainText:s?" => [
                "description" => "The body of the comment in plain text.",
                "x-localize" => true,
            ],
            "dateInserted:dt" => "When the comment was created.",
            "dateUpdated:dt|n" => "When the comment was last updated.",
            "insertUserID:i" => "The user that created the comment.",
            "updateUserID:i|n",
            "score:i" => [
                "default" => 0,
            ],
            "depth:i" => [
                "default" => 1,
            ],
            "scoreChildComments:i" => [
                "default" => 0,
            ],
            "countChildComments:i" => [
                "default" => 0,
            ],
            "insertUser?" => SchemaFactory::get(UserFragmentSchema::class, "UserFragment"),
            "url:s?" => "The full URL to the comment.",
            "labelCodes:a?" => ["items" => ["type" => "string"]],
            "type:s?" => "Record type for search drivers.",
            "format:s?" => "The format of the comment",
            "groupID:i?" => [
                "x-null-value" => -1,
            ],
            "image?" => new MainImageSchema(),
            "reactions?" => $this->reactionModel->getReactionSummaryFragment(),
            "attachments:a?" =>
                "Attachments associated with this comment. Requires the 'Garden.Staff.Allow' permission.",
            "reportMeta?" => \Vanilla\Forum\Models\CommunityManagement\ReportModel::reportMetaSchema(),
            "countReports:i?",
            "suggestion?" => AiSuggestionSourceService::getSuggestionSchema(),
            ModelUtils::SORT_TRENDING . ":f?",
            ModelUtils::SORT_TRENDING . "Debug:o?",
        ]);

        $eventManager = Gdn::getContainer()->get(EventManager::class);
        $eventManager->fire("commentModel_commentSchema", $result);
        return $result;
    }

    /**
     * Count total comments in a discussion specified by ID.
     *
     * Events: BeforeGetCount
     *
     * @param int $discussionID Unique ID of discussion we're counting comments from.
     * @return int Resulting count.
     */
    public function getCountByDiscussion($discussionID): int
    {
        $this->fireEvent("BeforeGetCount");

        return $this->getCountWhere(["DiscussionID" => $discussionID]);
    }

    /**
     * Count total comments in a discussion specified by $where conditions.
     *
     * @param array|false $where Conditions
     * @return int Count rows.
     */
    public function getCountWhere(array|false $where = false): int
    {
        if (is_array($where)) {
            $this->SQL->where($where);
        }

        return $this->SQL
            ->select("CommentID", "count", "CountComments")
            ->from("Comment")
            ->get()
            ->firstRow()->CountComments;
    }

    /**
     * Get single comment by ID. Allows you to pick data format of return value.
     *
     * @param int $id Unique ID of the comment.
     * @param string $datasetType Format to return comment in.
     * @param array $options options to pass to the database.
     * @return array|object SQL result in format specified by $resultType.
     * @throws Exception
     */
    public function getID($id, $datasetType = DATASET_TYPE_OBJECT, $options = [])
    {
        $parentName = $this->getParentRecordField("getParentNameField");
        $parentPlaceID = $this->getParentRecordField("getPlaceIDField");
        $parentPlaceRecordType = $this->getParentRecordField("getPlaceRecordTypeField");

        $query = $this->createSql()
            ->select("c.*")
            ->select(["d.Type as DiscussionType"])
            ->select($parentName, "coalesce", "parentName")
            ->select($parentName, "coalesce", "DiscussionName") // Backwards compatibility
            ->select($parentPlaceID, "coalesce", "CategoryID")
            ->select($parentPlaceID, "coalesce", "placeRecordID")
            ->select($parentPlaceRecordType, "coalesce", "placeRecordType")
            ->from("Comment c")
            ->where("c.CommentID", $id)
            ->limit(1);

        $this->addParentCommentQuery($query);
        $this->addParentRecordTable($query);

        // Groups hooks here to join a group ID off the discussion.
        $extraSelects = \Gdn::eventManager()->fireFilter("commentModel_extraSelects", []);
        if (!empty($extraSelects)) {
            $query->select($extraSelects);
        }
        $this->joinUsersSql($query);

        $this->SQL = $query;
        $this->options($options);

        $comment = $query->get()->firstRow($datasetType);

        if ($comment) {
            $this->calculate($comment);
        }

        return $comment;
    }

    /**
     * Get the list of record column fields name.
     *
     * @param string $functionName
     * @param array|null $parentRecordType
     * @return string
     */
    public function getParentRecordField(string $functionName, array $parentRecordType = null): string
    {
        $name = [];
        if (!isset($parentRecordType)) {
            $parentRecordType = $this->getParentRecordTypes();
        }

        foreach ($this->parentHandlers as $parentType) {
            if (!in_array($parentType->getRecordType(), $parentRecordType)) {
                continue;
            }
            $name[] = $parentType->$functionName();
        }
        return implode(",", $name);
    }

    /**
     * Join the parent record table.
     *
     * @param Gdn_SQLDriver $query
     * @param array|null $parentRecordTypes
     * @return void
     */
    public function addParentRecordTable(Gdn_SQLDriver &$query, array $parentRecordTypes = null): void
    {
        if (!isset($parentRecordTypes)) {
            $parentRecordTypes = $this->getParentRecordTypes();
        }

        foreach ($this->parentHandlers as $parentType) {
            if (!in_array($parentType->getRecordType(), $parentRecordTypes)) {
                continue;
            }
            $parentType->joinParentTable($query);
        }
    }

    /**
     * Return Max Depth for current layout widget
     *
     * @param int $parentCommentID
     * @param string $layoutID;
     *
     * @return int
     */
    public function resolveCommentMaxDepth(int $parentCommentID): int
    {
        if (!FeatureFlagHelper::featureEnabled("customLayout.post")) {
            // No nesting allowed.
            return 1;
        }
        // get Base layout without extra details all we need is LayoutID.
        [$layoutID, $resolvedQuery] = $this->layoutViewModel->queryLayout(
            new LayoutQuery("comment", "comment", $parentCommentID, ["skipPageCalculation" => true])
        );

        try {
            $layout = $this->layoutModel->selectSingle(["layoutID" => $layoutID]);
        } catch (NoResultsException $ex) {
            // Default layout is applied.
            return 5;
        }

        // Now let's loop through the layout
        $maxDepth = 1;
        ArrayUtils::walkRecursiveArray($layout["layout"], function (array $value) use (&$maxDepth) {
            $hydrateKey = $value["\$hydrate"] ?? null;
            $validHydrateKeys = [
                "react.asset.postCommentThread",
                "react.asset.answerThread",
                "react.asset.eventCommentThread",
            ];
            if (in_array($hydrateKey, $validHydrateKeys)) {
                // This is it.
                $maxDepth = $value["apiParams"]["maxDepth"] ?? 5;
            }
        });

        return $maxDepth;
    }

    /**
     * Get the page of a discussion a comment should be in.
     *
     * @param array|int $commentRowOrCommentID
     * @param string $layoutID
     *
     * @return int
     * @throws NotFoundException
     */
    public function getCommentThreadPage(array|int $commentRowOrCommentID): int
    {
        $commentID = is_array($commentRowOrCommentID)
            ? $commentRowOrCommentID["commentID"] ?? $commentRowOrCommentID["CommentID"]
            : $commentRowOrCommentID;
        $perPage = Gdn::config("Vanilla.Comments.PerPage", 30);
        $maxDepth = $this->resolveCommentMaxDepth($commentID);
        $topLevelComment = $maxDepth > 1 ? $this->threadModel->resolveTopLevelParentComment($commentID) : $commentID;
        $threadOffset = $this->getCommentThreadOffset($topLevelComment, $maxDepth);
        return floor($threadOffset / $perPage) + 1;
    }

    /**
     * Gets the offset of the specified comment in its related discussion.
     *
     * @param int|array|object $commentRowOrCommentID Unique ID or a comment object for which the offset is being defined.
     * @param int $maxDepth
     * @return int The offset of the comment within the discussion thread.
     * @throws NotFoundException
     */
    public function getCommentThreadOffset(array|object|int $commentRowOrCommentID, int $maxDepth = 1): int
    {
        if (is_numeric($commentRowOrCommentID)) {
            $comment = $this->getID($commentRowOrCommentID, DATASET_TYPE_ARRAY);
            $commentID = $commentRowOrCommentID;
        } else {
            $comment = (array) $commentRowOrCommentID;
            $commentID = $comment["CommentID"];
        }

        if (!$comment) {
            throw new NotFoundException("Comment", [
                "commentID" => $commentID,
            ]);
        }

        $this->SQL
            ->select("c.CommentID", "count", "CountComments")
            ->from("Comment c")
            ->where([
                "parentRecordType" => $comment["parentRecordType"],
                "parentRecordID" => $comment["parentRecordID"],
                "CommentID <>" => $commentID,
            ]);

        if ($maxDepth > 1) {
            $this->SQL->where("parentCommentID is NULL");
        }

        $this->SQL->beginWhereGroup();

        // Figure out the where clause based on the sort.
        foreach ($this->_OrderBy as $part) {
            [$expr, $value] = $this->_WhereFromOrderBy($part, $comment, "");

            if (!isset($prevWhere)) {
                $this->SQL->where($expr, $value);
            } else {
                $this->SQL->orOp();
                $this->SQL->beginWhereGroup();
                $this->SQL->orWhere($prevWhere[0], $prevWhere[1]);
                $this->SQL->where($expr, $value);
                $this->SQL->endWhereGroup();
            }

            $prevWhere = $this->_WhereFromOrderBy($part, $comment, "==");
        }

        $this->SQL->endWhereGroup();

        return $this->SQL->get()->firstRow()->CountComments;
    }

    /**
     * Builds Where statements for GetOffset method.
     *
     * @param array $part Value from $this->_OrderBy.
     * @param object $comment
     * @param string $op Comparison operator.
     * @return array Expression and value.
     *
     * @see CommentModel::getCommentThreadOffset()
     *
     */
    protected function _WhereFromOrderBy($part, $comment, $op = "")
    {
        if (!$op || $op == "=") {
            $op = ($part[1] == "desc" ? ">" : "<") . $op;
        } elseif ($op == "==") {
            $op = "=";
        }

        $expr = $part[0] . " " . $op;
        if (preg_match("/c\.(\w*\b)/", $part[0], $matches)) {
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
     * @param array|false $settings Currently unused.
     * @return int $commentID
     * @throws ClientException
     * @throws NotFoundException
     * @throws PremoderationException
     * @since 2.0.0
     */
    public function save($formPostValues, $settings = false)
    {
        // Define the primary key in this model's table.
        $this->defineSchema();
        $textUpdated = false;
        $settings = is_array($settings) ? $settings : [];
        // Validate $CommentID and whether this is an insert
        $commentID = val("CommentID", $formPostValues);
        $commentID = is_numeric($commentID) && $commentID > 0 ? $commentID : false;
        $isInsert = $commentID === false;
        if ($isInsert) {
            $this->addInsertFields($formPostValues);
        } else {
            $this->addUpdateFields($formPostValues);
        }

        if ($isInsert || isset($formPostValues["Body"])) {
            // Apply body validation rules.
            $this->Validation->applyRule("Body", "Required");
            $this->Validation->addRule("MeAction", "function:ValidateMeAction");
            $this->Validation->applyRule("Body", "MeAction");
            $maxCommentLength = Gdn::config("Vanilla.Comment.MaxLength");
            if (is_numeric($maxCommentLength) && $maxCommentLength > 0) {
                $this->Validation->setSchemaProperty("Body", "maxPlainTextLength", $maxCommentLength);
                $this->Validation->applyRule("Body", "plainTextLength");
            }
            $minCommentLength = c("Vanilla.Comment.MinLength");
            if ($minCommentLength && is_numeric($minCommentLength)) {
                $this->Validation->setSchemaProperty("Body", "MinTextLength", $minCommentLength);
                $this->Validation->applyRule("Body", "MinTextLength");
            }
        } else {
            $this->Validation->unapplyRule("Body");
        }

        $previousComment = [];
        if ($commentID !== false) {
            // Fetch the comment's data before we save, for comparison's sake.
            $previousComment = $this->getID($commentID, DATASET_TYPE_ARRAY);
        }

        // Make sure the discussion actually exists (https://github.com/vanilla/vanilla-patches/issues/716).
        if (isset($formPostValues["DiscussionID"])) {
            $discussion = $this->discussionModel->getID($formPostValues["DiscussionID"], DATASET_TYPE_ARRAY);
            if (!$discussion) {
                throw new NotFoundException("Discussion");
            }
            $formPostValues["parentRecordType"] = "discussion";
            $formPostValues["parentRecordID"] = $formPostValues["DiscussionID"];
        }

        $parentRecordType =
            $formPostValues["parentRecordType"] ?? ($previousComment["parentRecordType"] ?? "discussion");
        $parentRecordID =
            $formPostValues["parentRecordID"] ??
            ($previousComment["parentRecordID"] ?? $previousComment["DiscussionID"]);
        $formPostValues["parentRecordType"] = $parentRecordType;

        $parentCommentID = $formPostValues["parentCommentID"] ?? ($previousComment["parentCommentID"] ?? null);
        if ($parentCommentID !== null) {
            // Make sure the parent comment exists and is correct.
            $parentComment = $this->validateParentComment($parentCommentID, $parentRecordType, $parentRecordID);
            $formPostValues["depth"] = $parentComment["depth"] + 1;
        }

        $isDiscussionComment = $parentRecordType === "discussion";

        $isValidUser = true;

        if ($isDiscussionComment) {
            // Prep and fire event
            $formPostValues["DiscussionID"] = $parentRecordID;
            $this->EventArguments["FormPostValues"] = &$formPostValues;
            $this->EventArguments["CommentID"] = $commentID;
            $this->EventArguments["IsValid"] = &$isValidUser;
            $this->EventArguments["UserModel"] = $this->userModel;
            $this->fireEvent("BeforeSaveComment");
        }

        // Validate the form posted values
        if (!$this->validate($formPostValues, $isInsert)) {
            return false;
        }
        $prevDiscussionID = false;

        // If the post is new and it validates, check for spam
        $skipSpamCheck = $settings["skipSpamCheck"] ?? false;
        if ($isInsert) {
            if (
                !$skipSpamCheck &&
                $isDiscussionComment &&
                $this->checkUserSpamming(Gdn::session()->UserID, $this->floodGate)
            ) {
                // User triggered flood control.
                // Validation error was added to our validation.
                return false;
            }
        }
        $fields = $this->Validation->schemaValidationFields();
        unset($fields[$this->PrimaryKey]);
        if (!isset($fields["InsertUserID"]) || !isset($fields["DateInserted"])) {
            $comment = $this->getID($commentID, DATASET_TYPE_ARRAY);
            $insertUserID = $comment["InsertUserID"] ?? null;
            $dateInserted = $comment["DateInserted"] ?? null;
        } else {
            $insertUserID = $fields["InsertUserID"];
            $dateInserted = $fields["DateInserted"];
        }

        $commentData = $commentID
            ? array_merge($fields, [
                "CommentID" => $commentID,
                "InsertUserID" => $insertUserID,
                "DateInserted" => $dateInserted,
            ])
            : $fields;

        if ($isDiscussionComment && !$skipSpamCheck) {
            if (FeatureFlagHelper::featureEnabled("escalations")) {
                ///
                /// Modern premoderation.
                ///
                $discussionID = $parentRecordID;
                $discussion = $discussion ?? $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
                if (!$discussion) {
                    throw new NotFoundException("Discussion");
                }
                $commentName = self::generateCommentName($discussion["Name"] ?? null);
                $premodItem = new PremoderationItem(
                    userID: $this->sessionInterface->UserID,
                    userName: $this->sessionInterface->User->Name,
                    userEmail: $this->sessionInterface->User->Email,
                    recordName: $commentName,
                    recordBody: $commentData["Body"] ?? $previousComment["Body"],
                    recordFormat: $commentData["Format"] ?? $previousComment["Format"],
                    isEdit: $commentID !== false,
                    placeRecordType: "category",
                    placeRecordID: $discussion["CategoryID"],
                    recordType: "comment",
                    recordID: $commentID === false ? null : $commentID,
                    rawRow: $fields
                );
                $this->premoderationModel()->premoderateItem($premodItem);
            } else {
                // Discussion spam checks.
                $spam = SpamModel::isSpam("Comment", $commentData, ["action" => "update"]);
                if ($spam) {
                    return SPAM;
                }

                $isValid = true;
                $invalidReturnType = false;
                $this->EventArguments["CommentData"] = $commentData;
                $this->EventArguments["IsValid"] = &$isValid;
                $this->EventArguments["InvalidReturnType"] = &$invalidReturnType;
                $this->fireEvent("AfterValidateComment");
                if (!$isValid) {
                    return $invalidReturnType;
                }
            }
        }

        if ($isInsert === false) {
            $prevDiscussionID = $previousComment["DiscussionID"] ?? false;

            // Log the save.
            if ($isDiscussionComment) {
                LogModel::logChange("Edit", "Comment", array_merge($fields, ["CommentID" => $commentID]));

                if (c("Garden.ForceInputFormatter")) {
                    $fields["Format"] = Gdn::config("Garden.InputFormatter", "");
                }
            }

            // Save the new value.
            $this->serializeRow($fields);

            $textUpdated = $previousComment["Body"] !== ($fields["Body"] ?? $previousComment["Body"]);
            $this->SQL->put($this->Name, $fields, ["CommentID" => $commentID]);
        } else {
            if (!FeatureFlagHelper::featureEnabled("escalations") && $isDiscussionComment && !$skipSpamCheck) {
                ///
                /// Legacy Premoderation
                ///

                if (!val("Format", $fields) || c("Garden.ForceInputFormatter")) {
                    $fields["Format"] = Gdn::config("Garden.InputFormatter", "");
                }

                // Check for approval
                $approvalRequired = checkRestriction("Vanilla.Approval.Require");
                if ($approvalRequired && !val("Verified", Gdn::session()->User)) {
                    $discussionModel = $this->discussionModel;
                    $discussion = $discussionModel->getID(val("DiscussionID", $fields));
                    $fields["CategoryID"] = val("CategoryID", $discussion);
                    LogModel::insert("Pending", "Comment", $fields);
                    return UNAPPROVED;
                }
            }

            // Create comment.
            $this->serializeRow($fields);
            $commentID = $this->SQL->insert($this->Name, $fields);
        }

        if (!$commentID) {
            // We failed to save. Callers will have to check the validation results.
            return false;
        }

        ///
        /// Some pre-fetch side-effects for discussion comments.
        ///
        if ($isDiscussionComment) {
            $this->EventArguments["CommentID"] = $commentID;
            $this->EventArguments["Insert"] = $isInsert;
            $this->EventArguments["CommentData"] = $commentData;
            // IsNewDiscussion is passed when the first comment for new discussions are created.
            $this->EventArguments["IsNewDiscussion"] = val("IsNewDiscussion", $formPostValues);
            $this->fireEvent("AfterSaveComment");
        }

        $event = new MachineTranslationEvent("comment", [$commentID]);
        $this->getEventManager()->dispatch($event);

        $comment = $this->getID($commentID, DATASET_TYPE_ARRAY);
        if (!$comment) {
            // The comment might have been deleted between creation and now.
            return false;
        }

        $comment["parentRecordType"] = $comment["parentRecordType"] ?? "discussion";
        $handler = $this->getParentHandler($comment["parentRecordType"]);

        ///
        /// Post fetch side-effects for comments.
        ///
        $this->threadModel->handleParentCommentInsertSideEffects(
            $comment,
            empty($previousComment) ? null : $previousComment
        );

        if ($isDiscussionComment) {
            $this->handleDiscussionCommentSideEffects(
                commentRow: $comment,
                discussionID: $parentRecordID,
                isInsert: $isInsert,
                prevDiscussionID: is_int($prevDiscussionID) ? $prevDiscussionID : null
            );
        }

        if ($isInsert) {
            $handler->handleCommentInsert($comment);
        }

        $comment["parentRecordName"] = $handler->getParentName($comment["parentRecordID"]);

        ///
        /// Now global side effects after insert.
        ///
        if (isset($fields["Body"])) {
            // Our body was updated so go update our media uploads..
            $this->calculateMediaAttachments($commentID, !$isInsert);
        }
        $this->updateUser($comment["InsertUserID"], $isInsert);

        $action = $isInsert ? CommentEvent::ACTION_INSERT : CommentEvent::ACTION_UPDATE;
        \Gdn::scheduler()->addJobDescriptor(
            new NormalJobDescriptor(DeferredResourceEventJob::class, [
                "id" => $commentID,
                "model" => CommentModel::class,
                "action" => $action,
                "textUpdated" => $textUpdated,
            ])
        );
        return $commentID;
    }

    /**
     * @param int $parentCommentID
     * @param string $parentRecordType
     * @param int $parentRecordID
     * @return array The parent comment.
     */
    private function validateParentComment(int $parentCommentID, string $parentRecordType, int $parentRecordID): array
    {
        $parentComment = $this->getID($parentCommentID, DATASET_TYPE_ARRAY);
        if (!$parentComment) {
            throw new NotFoundException("Parent Comment", ["commentID" => $parentCommentID]);
        }

        if (
            $parentComment["parentRecordType"] !== $parentRecordType ||
            $parentComment["parentRecordID"] !== $parentRecordID
        ) {
            throw new ClientException("Parent comment does not match the parent record type and ID.", 400, [
                "parentCommentID" => $parentCommentID,
                "parentRecordType" => $parentRecordType,
                "parentRecordID" => $parentRecordID,
            ]);
        }
        return $parentComment;
    }

    /**
     * After a discussion comment is created or updated, handle any necessary side effects.
     *
     * @param array $commentRow The inserted/updated DB row.
     * @param int $discussionID The discussionID that the comment is NOW.
     * @param bool $isInsert true if this was a new comment insert.
     * @param int|null $prevDiscussionID If we changed the discussionID pass that so we can do cleanup from it.
     */
    private function handleDiscussionCommentSideEffects(
        array $commentRow,
        int $discussionID,
        bool $isInsert,
        int|null $prevDiscussionID
    ): void {
        // Aggregate counts
        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        $newDiscussionID = $discussion["DiscussionID"] ?? null;
        $discussionIDChanged = $prevDiscussionID && $newDiscussionID && $newDiscussionID !== $prevDiscussionID;
        if ($isInsert && $discussion) {
            $this->forumAggregateModel()->handleCommentInsert($commentRow, $discussion);
        } elseif ($discussionIDChanged) {
            $previousDiscussion = $this->discussionModel->getID($prevDiscussionID, DATASET_TYPE_ARRAY);
            if ($previousDiscussion) {
                $this->forumAggregateModel()->handleCommentMove($commentRow, $previousDiscussion, $discussion);
            }
        }

        // More discussion metas
        // Mark the user as participated and update DateLastViewed.
        $this->SQL->replace(
            "UserDiscussion",
            ["Participated" => 1, "DateLastViewed" => $commentRow["DateInserted"]],
            ["DiscussionID" => $discussionID, "UserID" => val("InsertUserID", $commentRow)]
        );

        // ForeignID stash
        $session = Gdn::session();
        $session->setPublicStash("CommentForForeignID_" . $discussion["ForeignID"] ?? null, null);

        ///
        /// Notifications / webhooks / deferred jobs
        ///
        if ($isInsert && $discussion) {
            // Send notifications
            $notificationGenerator = Gdn::getContainer()->get(\Vanilla\Models\CommunityNotificationGenerator::class);
            $notificationGenerator->notifyNewComment($commentRow, $discussion);
        }
    }

    /**
     * Generate a comment event object, based on a database row.
     *
     * @param array $row
     * @param string $action
     * @param null $sender
     * @return CommentEvent
     * @throws ContainerException
     * @throws FormatterNotFoundException
     * @throws \Garden\Container\NotFoundException
     * @throws ValidationException
     */
    public function eventFromRow(array $row, string $action, $sender = null): ResourceEvent
    {
        $this->userModel->expandUsers($row, ["InsertUserID"]);

        $parentRecordType = $row["parentRecordType"] ?? "discussion";

        $out = $this->schema();
        if ($parentRecordType === "discussion") {
            $row = $this->addDiscussionData($row);
            $out = $out->merge(
                Schema::parse(["discussion:o" => SchemaFactory::get(PostFragmentSchema::class, "PostFragment")])
            );
        }

        $comment = $this->normalizeRow($row);
        $comment = $out->validate($comment);

        if ($sender) {
            $senderSchema = new UserFragmentSchema();
            $sender = $senderSchema->validate($sender);
        }

        $result = new CommentEvent($action, ["comment" => $comment], $sender);
        return $result;
    }

    /**
     * @return string
     */
    public static function trendingScoreCalculationSql(): string
    {
        return "COALESCE(c.Score, 0) + COALESCE(c.countChildComments, 0) * 2 + COALESCE(c.scoreChildComments, 0) / 10";
    }

    /**
     * @param array $rawCommentRow
     * @return array[]
     */
    public static function trendingScoreCalculationDebug(array $rawCommentRow): array
    {
        $score = $rawCommentRow["Score"] ?? 0;

        $plainTextTwig = <<<TWIG
({{score}} + ({{countChildComments}} * 2) + ({{scoreChildComments}} / 10)) / ({{hoursSinceCreation}} + {{trendingWindowHours}}) ^ {{trendingWindowExponent}}
TWIG;

        $mathMlTwig = <<<TWIG
<math xmlns="http://www.w3.org/1998/Math/MathML">
  <mfrac>
    <mrow>
      <mi>{{score}}</mi>
      <mo>+</mo>
      <mo>(</mo>
      <mi>{{countChildComments}}</mi>
      <mo>&#x2217;</mo>
      <mn>2</mn>
      <mo>)</mo>
      <mo>+</mo>
      <mo>(</mo>
      <mi>{{scoreChildComments}}</mi>
      <mo>/</mo>
      <mn>10</mn>
      <mo>)</mo>
    </mrow>
    <mrow>
      <mo>(</mo>
      <mi>{{hoursSinceCreation}}</mi>
      <mo>+</mo>
      <mi>{{trendingWindowHours}}</mi>
      <mo>)</mo>
      <msup>
        <mrow></mrow>
        <mi>{{trendingWindowExponent}}</mi>
      </msup>
    </mrow>
  </mfrac>
</math>
TWIG;
        $templateValues = [
            "score" => "score",
            "countChildComments" => "countChildComments",
            "scoreChildComments" => "scoreChildComments",
            "hoursSinceCreation" => "hoursSinceCreation",
            "trendingWindowHours" => "trendingWindowHours",
            "trendingWindowExponent" => "trendingWindowExponent",
        ];
        $realValues = $rawCommentRow + ["score" => $score];

        $templatePlainText = TwigStaticRenderer::renderString($plainTextTwig, $templateValues);
        $templateMathMl = TwigStaticRenderer::renderString($mathMlTwig, $templateValues);
        $equationPlainText = TwigStaticRenderer::renderString($plainTextTwig, $realValues);
        $equationMathMl = TwigStaticRenderer::renderString($mathMlTwig, $realValues);

        return [
            "plainText" => [
                "template" => $templatePlainText,
                "equation" => $equationPlainText,
            ],
            "mathMl" => [
                "template" => $templateMathMl,
                "equation" => $equationMathMl,
            ],
        ];
    }

    /**
     * Given a database row, massage the data into a more externally-useful format.
     *
     * @param array $row
     * @param array $expand Expand fields.
     *
     * @return array
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws FormatterNotFoundException
     */
    public function normalizeRow(array $row, $expand = []): array
    {
        $rawBody = $row["Body"];
        $format = $row["Format"];
        $bodyParsed = $this->formatterService->parse($rawBody, $format);
        $row["Body"] = $this->formatterService->renderHTML($bodyParsed);
        $row["image"] = $this->formatterService->parseMainImage($bodyParsed, $format);
        $row["Name"] = self::generateCommentName(
            $row["parentName"] ?? ($row["DiscussionName"] ?? $row["discussion"]["Name"])
        );
        $row["Url"] = commentUrl($row);
        $row["Attributes"] = new Attributes($row["Attributes"] ?? null);
        $row["InsertUserID"] = $row["InsertUserID"] ?? 0;
        $row["DateInserted"] = $row["DateInserted"] ?? ($row["DateUpdated"] ?? new DateTime());

        if (DebugUtils::isDebug() && isset($row[ModelUtils::SORT_TRENDING])) {
            $row[ModelUtils::SORT_TRENDING . "Debug"] = self::trendingScoreCalculationDebug($row);
        }

        if (empty($row["Score"])) {
            $row["Score"] = 0;
        }

        $scheme = new CamelCaseScheme();
        $result = $scheme->convertArrayKeys($row);

        if (isset($result["discussionID"])) {
            $result["parentRecordType"] = "discussion";
            $result["parentRecordID"] = $result["discussionID"];
        }

        if (ModelUtils::isExpandOption(ModelUtils::EXPAND_CRAWL, $expand)) {
            $result["canonicalID"] = "comment_{$result["commentID"]}";
            $result[
                "recordCollapseID"
            ] = "site{$this->ownSite->getSiteID()}_parentRecordType_{$result["parentRecordType"]}_parentRecordID{$result["parentRecordID"]}";
            $result["excerpt"] = $this->formatterService->renderExcerpt($bodyParsed, $format);
            $result["bodyPlainText"] = $this->formatterService->renderPlainText($bodyParsed, $format);
            $categoryID = $row["CategoryID"] ?? null;
            $result["scope"] =
                $categoryID !== null
                    ? $this->categoryModel->getRecordScope($row["CategoryID"])
                    : CrawlableRecordSchema::SCOPE_RESTRICTED;
            $result["score"] = $row["Score"] ?? 0;
            $siteSection = $this->siteSectionModel->getSiteSectionForAttribute("allCategories", $categoryID);
            $result["locale"] = $siteSection->getContentLocale();
            $searchService = Gdn::getContainer()->get(SearchService::class);
            /** @var SearchTypeQueryExtenderInterface $extender */
            foreach ($searchService->getExtenders() as $extender) {
                $extender->extendRecord($result, "comment");
            }
        }

        // Get the comment's parsed content's first image & get the srcset for it.
        $result["image"] = $this->formatterService->parseMainImage($rawBody, $format);

        if (
            \Vanilla\FeatureFlagHelper::featureEnabled("AISuggestions") &&
            isset($result["attributes"]["aiSuggestionID"])
        ) {
            $result["suggestion"] = $this->aiSuggestionModel->getByID($result["attributes"]["aiSuggestionID"]);
        }

        return $result;
    }

    /**
     * Generate a comment name from a discussion name. This will return 'Untitled' if passed a null value.
     *
     * @param string|null $discussionName
     * @return string
     */
    public static function generateCommentName(?string $discussionName): string
    {
        if ($discussionName) {
            $discussionName = Gdn::formatService()->renderPlainText($discussionName, "text");
        }
        return sprintf(t("Re: %s"), $discussionName ?? t("Untitled"));
    }

    /**
     * Update the attachment status of attachemnts in particular comment.
     *
     * @param int $commentID The ID of the comment.
     * @param bool $isUpdate Whether or not we are updating an existing comment.
     */
    private function calculateMediaAttachments(int $commentID, bool $isUpdate)
    {
        $commentRow = $this->getID($commentID, DATASET_TYPE_ARRAY);
        if ($commentRow) {
            if ($isUpdate) {
                $this->flagInactiveMedia($commentID, $commentRow["Body"], $commentRow["Format"]);
            }
            $this->refreshMediaAttachments($commentID, $commentRow["Body"], $commentRow["Format"]);
        }
    }

    /**
     * Update user's total comment count.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $userID Unique ID of the user to be updated.
     */
    public function updateUser($userID, $inc = false)
    {
        $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
        if (!$user) {
            $this->logger->info("Failed updating the user $userID, this user do not exists.");
            return;
        }

        if ($inc) {
            $countComments = val("CountComments", $user);
            // Increment if 100 or greater; Recalculate on 120, 140 etc.
            if ($countComments >= 100 && $countComments % 20 !== 0) {
                $this->SQL
                    ->update("User")
                    ->set("CountComments", "CountComments + 1", false)
                    ->where("UserID", $userID)
                    ->put();

                $this->userModel->updateUserCache($userID, "CountComments", $countComments + 1);
                $this->addDirtyRecord("user", $userID);
                return;
            }
        }

        $countComments = $this->SQL
            ->select("CommentID", "count", "CountComments")
            ->from("Comment")
            ->where("InsertUserID", $userID)
            ->get()
            ->value("CountComments", 0);

        // Save the count to the user table
        $this->userModel->setField($userID, "CountComments", $countComments);
    }

    /**
     * Override of parent::setField
     *
     * @param int $rowID
     * @param array|string $property
     * @param bool $value
     */
    public function setField($rowID, $property, $value = false)
    {
        if (!is_array($property)) {
            $set = [$property => $value];
        } else {
            $set = $property;
        }

        $this->Database->runWithTransaction(function () use ($rowID, $set) {
            if (isset($set["Score"])) {
                // A score was passed, let's compare it to our previous one.
                $currentPartial = $this->createSql()
                    ->select("Score")
                    ->select("parentCommentID")
                    ->from("Comment")
                    ->where("CommentID", $rowID)
                    ->get()
                    ->firstRow(DATASET_TYPE_ARRAY);
                $scoreIncrement = $set["Score"] - ($currentPartial["Score"] ?? 0);

                // If we have a parent apply it to them too.
                $parentCommentID = $currentPartial["parentCommentID"] ?? null;
                if ($parentCommentID !== null) {
                    $this->threadModel->updateParentsRecursively(
                        firstParentCommentID: $parentCommentID,
                        set: [
                            "scoreChildComments" => new Increment($scoreIncrement),
                        ]
                    );
                }
            }
            parent::setField($rowID, $set);

            $this->addDirtyRecord("comment", $rowID);
        });
    }

    /**
     * Delete a comment.
     *
     * {@inheritdoc}
     */
    public function delete($where = [], $options = [])
    {
        if (is_numeric($where)) {
            deprecated("CommentModel->delete(int)", "CommentModel->deleteID(int)");

            $result = $this->deleteID($where, $options);
            return $result;
        }

        throw new \BadMethodCallException("CommentModel->delete() is not supported.", 400);
    }

    /**
     * @param array $commentRow
     * @throws Exception
     */
    public function handleDiscussionCommentPreDeleteSideEffects(array $commentRow): void
    {
        $discussionID = $commentRow["DiscussionID"] ?? null;
        if ($discussionID === null) {
            throw new ServerException(
                "Can't handle discussion comment delete for a comment that is not part of a discussion.",
                500,
                [
                    "commentID" => $commentRow["CommentID"],
                ]
            );
        }

        $discussion = $this->discussionModel->getID($commentRow["DiscussionID"], DATASET_TYPE_ARRAY);
        if (!$discussion) {
            return;
        }

        // Decrement the UserDiscussion comment count if the user has seen this comment
        $offset = $this->getCommentThreadOffset($commentRow);
        $this->SQL
            ->update("UserDiscussion")
            ->set("CountComments", "CountComments - 1", false)
            ->where("DiscussionID", $commentRow["DiscussionID"])
            ->where("CountComments >", $offset)
            ->put();

        // These are only fired for discussion deletes because old client plugins are only aware of comments on discussions.
        $this->EventArguments["Comment"] = $commentRow;
        $this->EventArguments["Discussion"] = $discussion;
        $this->fireEvent("DeleteComment");
        $this->fireEvent("BeforeDeleteComment");
    }

    /**
     * Soft delete a comment.
     *
     * This is a hard delete that completely removes it from the database.
     * Events: DeleteComment, BeforeDeleteComment.
     *
     * @param int $id Unique ID of the comment to be deleted.
     * @param array $options Additional options for the delete.
     * @return bool Always returns true.
     */
    public function tombstoneDeleteID($id, $options = [])
    {
        Assert::integerish($id);
        Assert::isArray($options);

        $this->EventArguments["CommentID"] = $id;

        $comment = $this->getID($id, DATASET_TYPE_ARRAY);
        if (!$comment) {
            return false;
        }
        $commentInsertUserID = $comment["InsertUserID"];
        $discussionID = $comment["DiscussionID"] ?? null;
        if ($discussionID !== null) {
            $comment["parentRecordType"] = "discussion";
            $comment["parentRecordID"] = $discussionID;
            $this->handleDiscussionCommentPreDeleteSideEffects($comment);
            // Log the deletion. Change log currently only supports discussion comment deletes.
            $log = val("Log", $options, "Delete");
            LogModel::insert($log, "Comment", $comment, val("LogOptions", $options, []));
        }

        // Tombstone the comment.
        $this->SQL
            ->update(
                "Comment",
                [
                    "InsertUserID" => 0,
                    "UpdateUserID" => 0,
                    "Body" => t("This content has been removed."),
                    "format" => "Html",
                ],
                ["CommentID" => $id]
            )
            ->put();

        // After deletion
        if ($discussionID !== null) {
            // Handle aggregates
            $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
            if ($discussion) {
                $this->getEventManager()->fire("forumAggregateModel_comment", [
                    "comment" => $comment,
                    "discussion" => array_merge($discussion, ["CountComments" => $discussion["CountComments"] - 1]),
                ]);
            }
        }

        // Update the user's comment count
        $this->updateUser($commentInsertUserID);

        $dataObject = (object) $comment;
        $this->calculate($dataObject);

        $commentEvent = $this->eventFromRow(
            (array) $dataObject,
            ResourceEvent::ACTION_DELETE,
            $this->userModel->currentFragment()
        );
        $this->getEventManager()->dispatch($commentEvent);
        return true;
    }

    /**
     * Delete a comment.
     *
     * This is a hard delete that completely removes it from the database.
     * Events: DeleteComment, BeforeDeleteComment.
     *
     * @param int $id Unique ID of the comment to be deleted.
     * @param array $options Additional options for the delete.
     * @return bool Always returns true.
     */
    public function deleteID($id, $options = [])
    {
        Assert::integerish($id);
        Assert::isArray($options);

        $this->EventArguments["CommentID"] = $id;

        $comment = $this->getID($id, DATASET_TYPE_ARRAY);
        if (!$comment) {
            return false;
        }

        $discussionID = $comment["DiscussionID"] ?? null;
        if ($discussionID !== null) {
            $this->handleDiscussionCommentPreDeleteSideEffects($comment);
        }

        // Delete the comment.
        $this->SQL->delete("Comment", ["CommentID" => $id]);
        $parentRecordType = $comment["parentRecordType"] ?? "discussion";
        $handler = $this->getParentHandler($parentRecordType);
        if (isset($parentRecordType, $this->parentHandlers)) {
            $handler->handleCommentDelete($comment);
            $log = val("Log", $options, "Delete");
            LogModel::insert($log, "Comment", $comment, val("LogOptions", $options, []));
        }

        if ($options["parentCommentDelete"] ?? true) {
            $this->threadModel->handleParentCommentDeleteSideEffects($comment);
        }

        // After deletion
        if ($discussionID !== null) {
            // Handle aggregates
            $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
            if ($discussion) {
                $this->forumAggregateModel()->handleCommentDelete($comment, $discussion);
            }
        }

        // Update the user's comment count
        $this->updateUser($comment["InsertUserID"]);

        $dataObject = (object) $comment;
        $this->calculate($dataObject);

        $commentEvent = $this->eventFromRow(
            (array) $dataObject,
            ResourceEvent::ACTION_DELETE,
            $this->userModel->currentFragment()
        );
        $this->getEventManager()->dispatch($commentEvent);
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
    public function setCalculatedFields(&$data)
    {
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
    public function calculate($comment)
    {
        if (val("parentRecordType", $comment, null) === null && val("DiscussionID", $comment, null) !== null) {
            setValue("parentRecordType", $comment, "discussion");
            setValue("parentRecordID", $comment, val("DiscussionID", $comment));
        }

        // Do nothing yet.
        if ($attributes = val("Attributes", $comment)) {
            setValue("Attributes", $comment, dbdecode($attributes));
        }

        $this->EventArguments["Comment"] = $comment;
        $this->fireEvent("SetCalculatedFields");
    }

    public function where($value = null)
    {
        if ($value === null) {
            return $this->_Where;
        } elseif (!$value) {
            $this->_Where = [];
        } elseif (is_a($value, "Gdn_SQLDriver")) {
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
     *
     * @return bool Returns true if the user can edit or false otherwise.
     */
    public static function canEdit($comment, &$timeLeft = 0)
    {
        $comment = (array) $comment;
        $timeLeft &= $timeLeft ?? 0;
        $commentModel = Gdn::getContainer()->get(CommentModel::class);
        return $commentModel
            ->getParentHandler($comment["parentRecordType"])
            ->hasEditPermission($comment, throw: false, timeLeft: $timeLeft);
    }

    /**
     * @inheritDoc
     */
    public function getCrawlInfo(): array
    {
        $searchableParentTypes = [];
        foreach ($this->parentHandlers as $parentHandler) {
            if ($parentHandler->isSearchable()) {
                $searchableParentTypes[] = $parentHandler->getRecordType();
            }
        }
        $searchableParentTypes = implode(",", $searchableParentTypes);

        $r = \Vanilla\Models\LegacyModelUtils::getCrawlInfoFromPrimaryKey(
            $this,
            "/api/v2/comments?sort=-commentID&parentRecordType={$searchableParentTypes}&expand=crawl,roles",
            "commentID"
        );
        return $r;
    }

    /**
     * Return a URL for a comment. This function is in here and not functions.general so that plugins can override.
     *
     * @param object|array $comment
     * @param bool $withDomain
     * @return string
     */
    public static function commentUrl($comment, $withDomain = true)
    {
        $eventManager = \Gdn::eventManager();
        if ($eventManager->hasHandler("customCommentUrl")) {
            return $eventManager->fireFilter("customCommentUrl", "", $comment, $withDomain);
        }

        return url(self::createRawCommentPath($comment), $withDomain);
    }

    /**
     * Return a URL path for a comment. This function is in here and not functions.general so that plugins can override.
     *
     * @param object|array $comment
     *
     * @return string
     *
     * @internal Don't use unless you are the global commentUrl function.
     */
    public static function createRawCommentPath($comment)
    {
        $comment = (array) $comment;
        $parentRecordType = $comment["parentRecordType"] ?? ($comment["ParentRecordType"] ?? "discussion");
        $parentRecordType = strtolower($parentRecordType);
        $commentModel = Gdn::getContainer()->get(CommentModel::class);

        /* @var AbstractCommentParentHandler $parentRecord */
        $parentHandler = $commentModel->getParentHandler($parentRecordType);
        return $parentHandler->getCommentUrlPath($comment);
    }

    /**
     * Add a 'discussion' field to the comment that contains the discussion data.
     *
     * @param array $apiComment The row of comment data.
     * @return array
     */
    private function addDiscussionData(array $apiComment): array
    {
        $apiComment["discussion"] = $this->discussionModel->getID($apiComment["DiscussionID"], DATASET_TYPE_ARRAY);
        if (!$apiComment["discussion"]) {
            $apiComment["discussion"] = [
                "Name" => "name",
                "Url" => "url",
                "InsertUserID" => $apiComment["InsertUserID"],
            ];
        }
        $apiComment["discussion"]["Type"] = $apiComment["discussion"]["Type"] ?? "discussion";
        // Don't track the discussion body.
        $apiComment["discussion"]["Body"] = null;

        return $apiComment;
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
        $fields["recordID"] = $record["CommentID"] ?? ($record["commentID"] ?? false);
        $fields["parentRecordID"] = $record["DiscussionID"] ?? ($record["discussionID"] ?? null);

        if ($fields["userID"] && $fields["mentionedName"] && $fields["recordID"]) {
            $fields["recordType"] = $this->userMentionsModel::COMMENT;
            $fields["parentRecordType"] = $this->userMentionsModel::DISCUSSION;
            $fields["dateInserted"] = $this->userMentionsModel->getDate($record);
            $fields["status"] = $this->userMentionsModel::ACTIVE_STATUS;

            $this->userMentionsModel->insert($fields, [$this->userMentionsModel::OPT_REPLACE => true]);
        }

        return false;
    }

    /**
     * @param $query
     * @return void
     */
    public function addParentCommentQuery(&$query): void
    {
        $query
            ->select("cc.Body", "", "parentCommentBody")
            ->select("cc.Format", "", "parentCommentFormat")
            ->select("cc.InsertUserID", "", "parentCommentInsertUserID")
            ->select("cc.DateInserted", "", "parentCommentDateInserted")
            ->select("cc.DiscussionID", "", "parentDiscussionID")
            ->leftJoin("Comment cc", "cc.CommentID = c.parentCommentID");
    }

    /**
     * @inheritDoc
     */
    public function calculateAggregates(string $aggregateName, int $from, int $to): void
    {
        $this->counts($aggregateName, $from, $to, $to, [
            "CommentID" => new RangeExpression(">=", $from, "<=", $to),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function counts(string $column, $from = false, $to = false, $max = false, array $where = []): array
    {
        // All the counts concern nested comment fields. To avoid further bloating the comment model, we perform the
        // counts in the thread model.
        $result = $this->threadModel->counts($column, $from, $to, $max, $where);
        return $result;
    }

    /**
     * Query for discussionIDs for a list of comments.
     *
     * @param array $commentIDs
     * @return array
     * @throws NotFoundException
     */
    public function getDiscussionIDFromCommentIDs(array $commentIDs): array
    {
        $query = $this->permissionedCommentQuery(
            // Only discussions supported here for now.
            parentRecordTypes: ["discussion"],
            where: ["c.CommentID" => $commentIDs]
        );
        $query->select(["c.DiscussionID"]);
        $comments = $query->get()->result(DATASET_TYPE_ARRAY);
        if (empty($comments)) {
            throw new NotFoundException("Comments", ["recordIDs" => $commentIDs]);
        }
        return array_unique(array_column($comments, "DiscussionID"));
    }

    /**
     * Query for discussionIDs for a list of comments that are escalations.
     *
     * @param array $commentIDs
     * @return array
     * @throws NotFoundException
     */
    public function getDiscussionIDFromWithEscalationCommentIDs(array $commentIDs): array
    {
        $query = $this->permissionedCommentQuery(
            // Only discussions supported here for now.
            parentRecordTypes: ["discussion", "escalation"],
            where: ["c.CommentID" => $commentIDs]
        );
        $query->resetSelects();
        $query->select(["c.DiscussionID", "cb.placeRecordID"]);

        $comments = $query->get()->result(DATASET_TYPE_ARRAY);
        if (empty($comments)) {
            throw new NotFoundException("Comments", ["recordIDs" => $commentIDs]);
        }
        $discussionIDs = array_unique(array_column($comments, "DiscussionID"));
        $discussionIDs = array_filter($discussionIDs, function ($discussionID) {
            return $discussionID !== null;
        });
        $categoryIDs = array_unique(array_column($comments, "placeRecordID"));
        $categoryIDs = array_filter($categoryIDs, function ($categoryID) {
            return $categoryID !== null;
        });
        return ["discussionIDs" => $discussionIDs, "categoryIDs" => $categoryIDs];
    }

    /**
     * Query for max depth from a list of comment IDs.
     *
     * @param array $commentIDs
     * @return int
     * @throws NotFoundException
     */
    public function getMaxDepthFromCommentIDs(array $commentIDs): int
    {
        $row = $this->createSql()
            ->select("MAX(depth) as maxDepth")
            ->from("Comment")
            ->where("CommentID", $commentIDs)
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);
        if (empty($row)) {
            throw new NotFoundException("Comments", ["recordIDs" => $commentIDs]);
        }
        return $row["maxDepth"];
    }

    /**
     * Register a comment parent type.
     *
     * @param string $parentClassName
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function registerCommentParentType(string $parentClassName): void
    {
        $parent = Gdn::getContainer()->get($parentClassName);
        $this->parentHandlers[$parent->getRecordType()] = $parent;
    }

    /**
     * Get the parent record types.
     *
     * @return array
     */
    public function getParentRecordTypes(): array
    {
        return array_keys($this->parentHandlers);
    }

    /**
     * Get the category ID of a parent record.
     *
     * @param int $parentID
     * @param string $parentRecordType
     * @return int|null
     */
    public function getCategoryIDByParentRecordType(int $parentID, string $parentRecordType): ?int
    {
        return $this->getParentHandler($parentRecordType)->getCategoryID($parentID);
    }

    /**
     * Get the parent record by ID and type.
     *
     * @param int $parentID
     * @param string $parentRecordType
     * @return array|false
     * @throws NotFoundException
     */
    public function getParentRecord(int $parentID, string $parentRecordType): array|false
    {
        return $this->getParentHandler($parentRecordType)->getParentRecord($parentID);
    }

    /**
     * Get the last comment by parent record type.
     *
     * @param int $parentID
     * @param string $parentRecordType
     * @return array
     * @throws Exception
     */
    public function getLastCommentByParentRecordType(int $parentID, string $parentRecordType): array|bool
    {
        $sql = $this->createSql();
        $query = $sql
            ->select("c.*")
            ->from("Comment c")
            ->where("c.parentRecordType", $parentRecordType)
            ->where("c.parentRecordID", $parentID)
            ->orderBy("c.DateInserted", "desc")
            ->limit(1);
        return $query->get()->firstRow(DATASET_TYPE_ARRAY);
    }

    /**
     * Get a parent handler by record type.
     *
     * @param string $parentRecordType
     * @return AbstractCommentParentHandler
     * @throws NotFoundException
     */
    public function getParentHandler(string $parentRecordType): AbstractCommentParentHandler
    {
        $handler = $this->findParentHandler($parentRecordType);
        if ($handler === null) {
            throw new NotFoundException("Parent record type", ["parentRecordType" => $parentRecordType]);
        }
        return $handler;
    }

    /**
     * @param string $parentRecordType
     *
     * @return AbstractCommentParentHandler|null
     */
    public function findParentHandler(string $parentRecordType): ?AbstractCommentParentHandler
    {
        return $this->parentHandlers[$parentRecordType] ?? null;
    }

    /**
     * Fetch the parent record ID and type for a list of comment IDs.
     *
     * @param array $commentIDs
     * @return array
     * @throws Exception
     */
    public function getDistinctParents(array $commentIDs): array
    {
        $sql = $this->createSql();
        $parents = $sql
            ->select(["parentRecordID", "parentRecordType"])
            ->from("Comment")
            ->where("CommentID", $commentIDs)
            ->groupBy("parentRecordID, parentRecordType")
            ->get()
            ->resultArray();
        return $parents;
    }
}
