<?php
/**
 * Comment model
 *
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0
 */

use Garden\Events\ResourceEvent;
use Garden\Events\EventFromRowInterface;
use Garden\Schema\Schema;
use Garden\Utils\ContextException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\SimpleCache\CacheInterface;
use Vanilla\Attributes;
use Vanilla\Community\Events\CommentQueryEvent;
use Vanilla\Community\Schemas\PostFragmentSchema;
use Vanilla\Dashboard\AiSuggestionModel;
use Vanilla\Dashboard\Models\PremoderationModel;
use Vanilla\Dashboard\Models\AiSuggestionSourceService;
use Vanilla\Dashboard\Models\UserMentionsInterface;
use Vanilla\Dashboard\Models\UserMentionsModel;
use Vanilla\Database\CallbackWhereExpression;
use Vanilla\Events\LegacyDirtyRecordTrait;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\FormatFieldTrait;
use Vanilla\Formatting\UpdateMediaTrait;
use Vanilla\Forum\Jobs\DeferredResourceEventJob;
use Vanilla\Forum\Models\CommunityManagement\EscalationModel;
use Vanilla\Forum\Models\ForumAggregateModel;
use Vanilla\ImageSrcSet\ImageSrcSetService;
use Vanilla\ImageSrcSet\MainImageSchema;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Models\Model;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Permissions;
use Vanilla\Premoderation\PremoderationItem;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\SchemaFactory;
use Vanilla\Community\Events\CommentEvent;
use Vanilla\Contracts\Formatting\FormatFieldInterface;
use Vanilla\Site\OwnSite;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\ModelUtils;
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
    protected $_OrderBy = [["c.DateInserted", ""]];

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

    private ReactionModel $reactionModel;
    public int $LastCommentCount = 0;

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
            ->column("parentRecordType", "varchar(10)", true)
            ->column("parentRecordID", "int", true)
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
            ->column("Attributes", "text", true)
            ->set();

        // Indexes

        $structure
            ->table("Comment")

            ->createIndexIfNotExists("IX_Comment_DateInserted_parentRecordType_parentRecordID", [
                "DateInserted",
                "parentRecordType",
                "parentRecordID",
            ])
            ->createIndexIfNotExists("IX_Comment_parentRecordType_parentRecordID_DateInserted", [
                "parentRecordType",
                "parentRecordID",
                "DateInserted",
            ])
            ->createIndexIfNotExists("IX_Comment_InsertUserID_parentRecordType_parentRecordID", [
                "InsertUserID",
                "parentRecordType",
                "parentRecordID",
            ])
            ->createIndexIfNotExists("IX_Comment_Score", ["Score"])

            // Legacy indexes
            ->tryRenameIndex("IX_Comment_1", "IX_Comment_DiscussionID_DateInserted")
            ->createIndexIfNotExists("IX_Comment_DateInserted", ["DateInserted"])
            ->createIndexIfNotExists("IX_Comment_InsertUserID_DiscussionID", ["InsertUserID", "DiscussionID"])
            ->createIndexIfNotExists("IX_Comment_DiscussionID_DateInserted", ["DiscussionID", "DateInserted"]);

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
     * @return EscalationModel
     */
    private function escalationModel(): EscalationModel
    {
        return Gdn::getContainer()->get(EscalationModel::class);
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
     * Create the base comment query with parent records joined.
     *
     * @param bool $joinUsers Whether or not to join in insertUser/updateUser information.
     * @param string[] $parentRecordTypes The types of parent records to join.
     */
    public function commentQuery(bool $joinUsers = true, array $parentRecordTypes = ["discussion", "escalation"])
    {
        Assert::notEmpty($parentRecordTypes, "You must specify parentRecordTypes.");
        $this->SQL->select("c.*")->from("Comment c");

        $parentRecordNameFields = [];
        $categoryIDFields = [];
        $permissionWheres = [];

        $hasPermissionBypass = $this->sessionInterface
            ->getPermissions()
            ->hasAny(["community.moderate", "site.manage", Permissions::PERMISSION_SYSTEM]);

        if (in_array("discussion", $parentRecordTypes)) {
            $this->SQL
                ->select(["d.Type as DiscussionType"])
                ->leftJoin(
                    "Discussion d",
                    "coalesce(c.parentRecordType, 'discussion') = 'discussion' AND coalesce(c.parentRecordID, c.DiscussionID) = d.DiscussionID"
                );
            $categoryIDFields[] = "d.CategoryID";
            $parentRecordNameFields[] = "d.Name";
            if (!$hasPermissionBypass) {
                $permissionWheres[] = new CallbackWhereExpression(function (Gdn_SQLDriver $sql) {
                    $this->discussionModel->applyDiscussionCategoryPermissionsWhere($sql);
                });
            } else {
                $permissionWheres[] = "d.CategoryID IS NOT NULL";
            }
        }

        if (in_array("escalation", $parentRecordTypes)) {
            $this->SQL->leftJoin(
                "escalation e",
                "c.parentRecordType = 'escalation' AND c.parentRecordID = e.EscalationID"
            );
            $categoryIDFields[] = "e.placeRecordID";
            $parentRecordNameFields[] = "e.name";
            if (!$hasPermissionBypass) {
                $escCategoryIDs = $this->categoryModel->getCategoryIDsWithPermissionForUser(
                    userID: Gdn::session()->UserID,
                    permission: "Vanilla.Posts.Moderate"
                );
                $permissionWheres[] = [
                    "e.placeRecordType" => "category",
                    "e.placeRecordID" => $escCategoryIDs,
                ];
            } else {
                $permissionWheres[] = "e.placeRecordID IS NOT NULL";
            }
        }

        $this->SQL
            ->select(implode(",", $parentRecordNameFields), "coalesce", "parentRecordName")
            ->select(implode(",", $parentRecordNameFields), "coalesce", "DiscussionName") // Backwards compatibility
            ->select(implode(",", $categoryIDFields), "coalesce", "CategoryID")
            ->select(implode(",", $categoryIDFields), "coalesce", "placeRecordID")
            ->select("'category' as placeRecordType");

        // We only want records that joined on in some type of way.

        $this->SQL->beginWhereGroup();
        foreach ($permissionWheres as $i => $permissionWhere) {
            if ($i > 0) {
                $this->SQL->orOp();
            }
            $this->SQL
                ->beginWhereGroup()
                ->where($permissionWhere)
                ->endWhereGroup();
        }
        $this->SQL->endWhereGroup();

        if (in_array("discussion", $parentRecordTypes)) {
            // Groups hooks here to join a group ID off the discussion.
            $extraSelects = \Gdn::eventManager()->fireFilter("commentModel_extraSelects", []);
            if (!empty($extraSelects)) {
                $this->SQL->select($extraSelects);
            }
        }

        if ($joinUsers) {
            $this->SQL
                ->select("iu.Name", "", "InsertName")
                ->select("iu.Photo", "", "InsertPhoto")
                ->select("iu.Email", "", "InsertEmail")
                ->join("User iu", "c.InsertUserID = iu.UserID", "left")
                ->select("uu.Name", "", "UpdateName")
                ->select("uu.Photo", "", "UpdatePhoto")
                ->select("uu.Email", "", "UpdateEmail")
                ->join("User uu", "c.UpdateUserID = uu.UserID", "left");
        }
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
     * Select from the comment table, filling in default options where appropriate.
     *
     * @param array $where The where clause.
     * @param string|array $orderFields The columns to order by.
     * @param string $orderDirection The direction to order by.
     * @param int $limit The database limit.
     * @param int $offset The database offset.
     * @param string $alias A named alias for the Comment table.
     * @return Gdn_SQLDriver Returns SQL driver filled in with the select settings.
     */
    private function select(
        $where = [],
        $orderFields = "",
        $orderDirection = "asc",
        $limit = 0,
        $offset = 0,
        $alias = null
    ) {
        // Setup a clean copy of the SQL object.
        $sql = clone $this->SQL;
        $sql->reset();

        // Build up the basic query, accounting for a potential table name alias.
        $from = $this->Name;
        if ($alias) {
            $from .= " {$alias}";
        }
        $sql->select("CommentID")
            ->from($from)
            ->where($where);

        // Apply a limit.
        $limit = $limit ?: $this->getDefaultLimit();
        $sql->limit($limit, $offset);

        // Determine which sort fields to apply.
        if ($orderFields) {
            $sql->orderBy($orderFields, $orderDirection);
        } else {
            // Fallback to the configured sort fields on the object.
            foreach ($this->_OrderBy as $defaultOrder) {
                [$field, $dir] = $defaultOrder;
                // Reset any potential table prefixes, if we have an alias.
                if ($alias) {
                    $parts = explode(".", $field);
                    $field = $parts[count($parts) === 1 ? 0 : 1];
                    $field = "{$alias}.{$field}";
                }
                $sql->orderBy($field, $dir);
            }
            unset($parts, $field, $dir, $defaultOrder);
        }

        return $sql;
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
        $eventManager = $this->getEventManager();
        $eventManager->dispatch(new CommentQueryEvent($this->SQL));
        $where = $this->stripWherePrefixes($where);
        [$where, $options] = $this->splitWhere($where, ["joinUsers" => true, "joinDiscussions" => false]);

        // Build up an inner select of comments to force late-loading.
        $innerSelect = $this->select($where, $orderFields, $orderDirection, $limit, $offset, "c3");

        // Add the inner select's parameters to the outer select.
        $this->SQL->mergeParameters($innerSelect);

        $innerSelectSql = $innerSelect->getSelect();
        $result = $this->SQL
            ->select("c.*")
            ->from($this->Name . " c")
            ->join("($innerSelectSql) c2", "c.CommentID = c2.CommentID")
            ->get();

        if ($options["joinUsers"]) {
            $this->userModel->joinUsers($result, ["InsertUserID", "UpdateUserID"]);
        }

        if ($options["joinDiscussions"]) {
            $this->discussionModel->joinDiscussionData($result, "DiscussionID", $options["joinDiscussions"]);
        }
        $this->setCalculatedFields($result);

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
    public function getByDiscussion($discussionID, $limit, $offset = 0, array $where = [])
    {
        $this->commentQuery(true, ["discussion"]);
        $this->EventArguments["DiscussionID"] = &$discussionID;
        $this->EventArguments["Limit"] = &$limit;
        $this->EventArguments["Offset"] = &$offset;
        $this->EventArguments["Where"] = &$where;
        $this->fireEvent("BeforeGet");
        $eventManager = $this->getEventManager();
        $eventManager->dispatch(new CommentQueryEvent($this->SQL));

        // Use a subquery to force late-loading of comments. This optimizes pagination.
        $sql2 = clone $this->SQL;
        $sql2->reset();

        // Using a subquery isn't compatible with Vanilla's named parameter implementation. Manually escape conditions.
        $where = array_merge($where, ["c.DiscussionID" => $discussionID]);
        foreach ($where as $field => &$value) {
            if (filter_var($value, FILTER_VALIDATE_INT)) {
                continue;
            }
            $value = Gdn::database()
                ->connection()
                ->quote($value);
        }

        $sql2
            ->select("CommentID")
            ->from("Comment c")
            ->where($where, null, true, false)
            ->limit($limit, $offset);
        $this->orderBy($sql2);
        $select = $sql2->getSelect();

        $px = $this->SQL->Database->DatabasePrefix;
        $this->SQL->Database->DatabasePrefix = "";

        $this->SQL->join("($select) c2", "c.CommentID = c2.CommentID");
        $this->SQL->Database->DatabasePrefix = $px;

        $this->where($this->SQL);

        $result = $this->SQL->get();

        $this->userModel->joinUsers($result, ["InsertUserID", "UpdateUserID"]);

        $this->setCalculatedFields($result);

        $this->EventArguments["Comments"] = &$result;
        $this->fireEvent("AfterGet");

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
     * @return object SQL results.
     */
    public function getByUser($userID, $limit, $offset = 0)
    {
        // Get category permissions
        $perms = DiscussionModel::categoryPermissions();

        // Build main query
        $this->commentQuery(
            // Only discussions supported here for now.
            parentRecordTypes: ["discussion"]
        );
        $this->fireEvent("BeforeGet");
        $eventManager = $this->getEventManager();
        $eventManager->dispatch(new CommentQueryEvent($this->SQL));
        $this->SQL
            ->select("d.Name", "", "DiscussionName")
            ->where("c.InsertUserID", $userID)
            ->orderBy("c.CommentID", "desc")
            ->limit($limit, $offset);

        // Verify permissions (restricting by category if necessary)
        if ($perms !== true) {
            $this->SQL->join("Category ca", "d.CategoryID = ca.CategoryID", "left")->whereIn("d.CategoryID", $perms);
        }

        //$this->orderBy($this->SQL);

        $data = $this->SQL->get();
        $this->userModel->joinUsers($data, ["InsertUserID", "UpdateUserID"]);

        return $data;
    }

    /**
     *
     * Get comments for a user. This is an optimized version of CommentModel->getByUser().
     *
     * @since 2.1
     * @access public
     *
     * @param int $userID Which user to get comments for.
     * @param int $limit Max number to get.
     * @param int $offset Number to skip.
     * @param int|bool $lastCommentID A hint for quicker paging.
     * @param string|null $after Only pull comments following this date.
     * @param string $order Order comments ascending (asc) or descending (desc) by ID.
     * @param string $permission Permission to filter categories by.
     * @return Gdn_DataSet SQL results.
     */
    public function getByUser2(
        $userID,
        $limit,
        $offset,
        $lastCommentID = false,
        $after = null,
        $order = "desc",
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

        // The point of this query is to select from one comment table, but filter and sort on another.
        // This puts the paging into an index scan rather than a table scan.
        $this->SQL
            ->select("c2.*")
            ->select("d.Name", "", "DiscussionName")
            ->select("d.CategoryID")
            ->from("Comment c")
            ->join("Comment c2", "c.CommentID = c2.CommentID")
            ->join("Discussion d", "c2.DiscussionID = d.DiscussionID")
            ->where("c.InsertUserID", $userID)
            ->orderBy("c.CommentID", $order);

        if ($after) {
            $this->SQL->where("c.DateInserted >", $after);
        }

        if ($lastCommentID) {
            // The last comment id from the last page was given and can be used as a hint to speed up the query.
            $this->SQL->where("c.CommentID <", $lastCommentID)->limit($limit);
        } else {
            $this->SQL->limit($limit, $offset);
        }
        $this->fireEvent("BeforeGetByUser");
        $eventManager = $this->getEventManager();
        $eventManager->dispatch(new CommentQueryEvent($this->SQL));
        $data = $this->SQL->get();

        $result = &$data->result();
        $this->LastCommentCount = $data->numRows();
        if (count($result) > 0) {
            $this->LastCommentID = $result[count($result) - 1]->CommentID;
        } else {
            $this->LastCommentID = null;
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

        $this->userModel->joinUsers($data, ["InsertUserID", "UpdateUserID"]);

        $this->EventArguments["Comments"] = &$data;
        $this->fireEvent("AfterGet");

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

        $query = $this->createSelectCommentsQuery($where);
        if ($options[Model::OPT_ORDER] === "dateUpdated") {
            $options[Model::OPT_ORDER] = "sortDateUpdated";
            $query->select("c.dateUpdated, c.dateInserted", "COALESCE", "sortDateUpdated");
        } else {
            $options[Model::OPT_ORDER] = "c.{$options[Model::OPT_ORDER]}";
        }
        $this->SQL->applyModelOptions($options);

        $result = $query->get();
        $this->LastCommentCount = $result->numRows();

        $this->EventArguments["Comments"] = &$result;
        $this->fireEvent("AfterGet");

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
        $baseQuery = $this->createSelectCommentsQuery($where);
        $count = $baseQuery->getPagingCount("c.CommentID", $maxLimit);
        return $count;
    }

    /**
     * @param array $where
     * @return Gdn_SQLDriver
     */
    private function createSelectCommentsQuery(array $where): Gdn_SQLDriver
    {
        $joinDirtyRecords = $where[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;
        if (isset($where[DirtyRecordModel::DIRTY_RECORD_OPT])) {
            unset($where[DirtyRecordModel::DIRTY_RECORD_OPT]);
        }

        // All fields should be associated with a table. If there isn't one, assign it to comments.
        foreach ($where as $field => $value) {
            if (!str_contains($field, ".")) {
                $where["c.{$field}"] = $value;
                unset($where[$field]);
            }
        }

        $query = $this->SQL;
        // Apply the base of the query.
        $this->commentQuery(
            // This method is primarily used for API lookups which has its own user joining mechanism.
            joinUsers: false
        );
        $query->where($where);
        if ($joinDirtyRecords) {
            $this->applyDirtyWheres("c");
        }

        // If we have a user role where, make sure we join on that table.
        $insertUserRoleIDs = $where["uri.RoleID"] ?? null;
        if (!empty($insertUserRoleIDs)) {
            $query->join("UserRole uri", "c.InsertUserID = uri.UserID")->where("uri.RoleID", $insertUserRoleIDs);
        }

        $eventManager = $this->getEventManager();
        $eventManager->dispatch(new CommentQueryEvent($this->SQL));
        return $query;
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
     */
    public function schema(): Schema
    {
        $result = Schema::parse([
            "commentID:i" => "The ID of the comment.",
            "discussionID:i?" => "The ID of the discussion.",
            "parentRecordType:s?",
            "parentRecordID:i?",
            "discussionCollapseID:s?",
            "name:s?" => [
                "description" => "The name of the comment",
                "x-localize" => true,
            ],
            "categoryID:i?" => "The ID of the category of the comment",
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
            "score:i|n" => "Total points associated with this post.",
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
        ]);
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
     * @return mixed SQL result in format specified by $resultType.
     */
    public function getID($id, $datasetType = DATASET_TYPE_OBJECT, $options = [])
    {
        $this->options($options);

        $this->commentQuery(); // `false` suppresses FireEvent
        $comment = $this->SQL
            ->where("c.CommentID", $id)
            ->get()
            ->firstRow($datasetType);

        if ($comment) {
            $this->calculate($comment);
        }
        return $comment;
    }

    /**
     * Get single comment by ID as SQL result data.
     *
     * @param int $commentID Unique ID of the comment.
     * @param array $options
     * @return Gdn_DataSet SQL result.
     */
    public function getIDData($commentID, $options = [])
    {
        $this->commentQuery(); // FALSE supresses FireEvent
        $this->options($options);

        return $this->SQL->where("c.CommentID", $commentID)->get();
    }

    /**
     * Gets the offset of the specified comment in its related discussion.
     *
     * @param int|array|object $commentRowOrCommentID Unique ID or a comment object for which the offset is being defined.
     * @return int The offset of the comment within the discussion thread.
     */
    public function getDiscussionThreadOffset(array|object|int $commentRowOrCommentID): int
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

        $discussionID = $comment["DiscussionID"] ?? null;
        if ($discussionID === null) {
            throw new ServerException(
                "Can't get a discussion thread offset for a comment that is not part of a discussion.",
                500,
                [
                    "commentID" => $commentID,
                ]
            );
        }

        $this->SQL
            ->select("c.CommentID", "count", "CountComments")
            ->from("Comment c")
            ->where("c.DiscussionID", $comment["DiscussionID"]);

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
     * @see CommentModel::getDiscussionThreadOffset()
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
     * @since 2.0.0
     */
    public function save($formPostValues, $settings = false)
    {
        // Define the primary key in this model's table.
        $this->defineSchema();

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
            // Fetch the discussion's data before we save, for comparison's sake.
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
        $formPostValues["parentRecordType"] = $parentRecordType;

        $isDiscussionComment = $parentRecordType === "discussion";

        $isValidUser = true;

        if ($isDiscussionComment) {
            // Prep and fire event
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
                $discussionID = $fields["DiscussionID"] ?? $previousComment["DiscussionID"];
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
                $spam = SpamModel::isSpam("Comment", $commentData);
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

        $comment = $this->getID($commentID, DATASET_TYPE_ARRAY);
        if (!$comment) {
            // The comment might have been deleted between creation and now.
            return false;
        }

        ///
        /// Post fetch side-effects for discussion comments.
        ///
        if ($isDiscussionComment) {
            $this->handleDiscussionCommentSideEffects(
                commentRow: $comment,
                discussionID: $comment["DiscussionID"],
                isInsert: $isInsert,
                prevDiscussionID: is_int($prevDiscussionID) ? $prevDiscussionID : null
            );
        }

        if ($isInsert && $comment["parentRecordType"] === "escalation") {
            $this->escalationModel()->handleCommentInsert($comment);
        }

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
            ])
        );
        return $commentID;
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
     * @param array|object|null $sender
     * @return CommentEvent
     */
    public function eventFromRow(array $row, string $action, $sender = null): ResourceEvent
    {
        $this->userModel->expandUsers($row, ["InsertUserID"]);

        $parentRecordType = $row["parentRecordType"];

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
     * Given a database row, massage the data into a more externally-useful format.
     *
     * @param array $row
     * @param array|string|bool $expand Expand fields.
     *
     * @return array
     */
    public function normalizeRow(array $row, $expand = []): array
    {
        $rawBody = $row["Body"];
        $format = $row["Format"];
        $bodyParsed = $this->formatterService->parse($rawBody, $format);
        $row["Body"] = $this->formatterService->renderHTML($bodyParsed);
        $row["image"] = $this->formatterService->parseMainImage($bodyParsed, $format);
        $row["Name"] = self::generateCommentName($row["DiscussionName"] ?? $row["discussion"]["Name"]);
        $row["Url"] = commentUrl($row);
        $row["Attributes"] = new Attributes($row["Attributes"] ?? null);
        $row["InsertUserID"] = $row["InsertUserID"] ?? 0;
        $row["DateInserted"] = $row["DateInserted"] ?? ($row["DateUpdated"] ?? new DateTime());
        $scheme = new CamelCaseScheme();
        $result = $scheme->convertArrayKeys($row);

        if (isset($result["discussionID"])) {
            $result["parentRecordType"] = "discussion";
            $result["parentRecordID"] = $result["discussionID"];
        }

        if (ModelUtils::isExpandOption(ModelUtils::EXPAND_CRAWL, $expand)) {
            $result["canonicalID"] = "comment_{$result["commentID"]}";
            $result["recordCollapseID"] = "site{$this->ownSite->getSiteID()}_discussion{$result["discussionID"]}";
            $result["excerpt"] = $this->formatterService->renderExcerpt($bodyParsed, $format);
            $result["bodyPlainText"] = $this->formatterService->renderPlainText($bodyParsed, $format);
            $result["scope"] = $this->categoryModel->getRecordScope($row["CategoryID"]);
            $result["score"] = $row["Score"] ?? 0;
            $siteSection = $this->siteSectionModel->getSiteSectionForAttribute("allCategories", $row["CategoryID"]);
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
        parent::setField($rowID, $property, $value);
        $this->addDirtyRecord("comment", $rowID);
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
    private function handleDiscussionCommentPreDeleteSideEffects(array $commentRow): void
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
        $offset = $this->getDiscussionThreadOffset($commentRow);
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
            // Log the deletion. Change log currently only supports discussion comment deletes.
            $log = val("Log", $options, "Delete");
            LogModel::insert($log, "Comment", $comment, val("LogOptions", $options, []));
        }

        if ($comment["parentRecordType"] === "escalation") {
            $this->escalationModel()->handleCommentDelete($comment);
        }

        // Delete the comment.
        $this->SQL->delete("Comment", ["CommentID" => $id]);

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
     * @param array|null $discussion The discussion row associated with this comment.
     * @return bool Returns true if the user can edit or false otherwise.
     */
    public static function canEdit($comment, &$timeLeft = 0, $discussion = null)
    {
        $comment = (array) $comment;
        // Guests can't edit.
        if (Gdn::session()->UserID === 0) {
            return false;
        }

        $categoryID = $comment["CategoryID"];
        $permissions = \Gdn::session()->getPermissions();
        $isGlobalMod = $permissions->hasAny(["site.manage", "community.moderate"]);
        $isCategoryMod = $permissions->has(
            "posts.moderate",
            $categoryID,
            Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
            CategoryModel::PERM_JUNCTION_TABLE
        );
        if ($isGlobalMod || $isCategoryMod) {
            // Mods can always edit.
            return true;
        }

        // Only attempt to fetch the discussion if we weren't provided one.
        if ($discussion === null) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID(val("DiscussionID", $comment));
        }

        // Can the current user edit all comments in this category?
        $category = CategoryModel::categories(val("CategoryID", $discussion));
        if (CategoryModel::checkPermission($category, "Vanilla.Comments.Edit")) {
            return true;
        }

        // Check if user can view the category contents.
        if (!CategoryModel::checkPermission($category, "Vanilla.Comments.Add")) {
            return false;
        }

        // Make sure only moderators can edit closed things.
        if (val("Closed", $discussion)) {
            return false;
        }

        // Non-mods can't edit if they aren't the author.
        if (Gdn::session()->UserID != val("InsertUserID", $comment)) {
            return false;
        }

        return parent::editContentTimeout($comment, $timeLeft);
    }

    /**
     * @inheritDoc
     */
    public function getCrawlInfo(): array
    {
        $r = \Vanilla\Models\LegacyModelUtils::getCrawlInfoFromPrimaryKey(
            $this,
            "/api/v2/comments?sort=-commentID&expand=crawl,roles",
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
        if (function_exists("commentUrl")) {
            // Legacy overrides.
            return commentUrl($comment, $withDomain);
        } else {
            return self::createRawCommentUrl($comment, $withDomain);
        }
    }

    /**
     * Return a URL for a comment. This function is in here and not functions.general so that plugins can override.
     *
     * @param object|array $comment
     * @param bool $withDomain
     * @return string
     *
     * @internal Don't use unless you are the global commentUrl function.
     */
    public static function createRawCommentUrl($comment, $withDomain = true)
    {
        $comment = (object) $comment;

        $parentRecordType = $comment->parentRecordType ?? "discussion";
        if ($parentRecordType === "escalation") {
            // Escalation comments.
            $escalationID = $comment->parentRecordID;
            return url("/dashboard/content/escalations/{$escalationID}?commentID={$comment->CommentID}", $withDomain);
        }

        // Discussion comments
        $eventManager = \Gdn::eventManager();
        if ($eventManager->hasHandler("customCommentUrl")) {
            return $eventManager->fireFilter("customCommentUrl", "", $comment, $withDomain);
        }

        $result = "/discussion/comment/{$comment->CommentID}#Comment_{$comment->CommentID}";
        return url($result, $withDomain);
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
}
