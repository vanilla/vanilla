<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\DateFilterSchema;
use Vanilla\ApiUtils;
use Vanilla\Events\BeforeCommentPostEvent;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Forum\Models\CommentDeleteModel;
use Vanilla\Forum\Models\CommentThreadModel;
use Vanilla\Forum\Models\CommentThreadStructureOptions;
use Vanilla\Forum\Models\CommunityManagement\EscalationModel;
use Vanilla\Layout\LayoutModel;
use Vanilla\Layout\LayoutViewModel;
use Vanilla\Models\ContentDraftModel;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Models\LegacyModelUtils;
use Vanilla\Models\Model;
use Vanilla\Permissions;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Schema\RangeExpression;
use Vanilla\Search\SearchOptions;
use Vanilla\Search\SearchResultItem;
use Garden\Web\Exception\ClientException;
use Vanilla\Utility\ModelUtils;
use Garden\Web\Pagination;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\APIExpandMiddleware;

/**
 * API Controller for the `/comments` resource.
 */
class CommentsApiController extends AbstractApiController
{
    use CommunitySearchSchemaTrait;
    use \Vanilla\Formatting\FormatCompatTrait;

    /** @var Schema */
    private $commentSchema;

    /** @var Schema */
    private $idParamSchema;

    /**
     * DI.
     */
    public function __construct(
        private CommentModel $commentModel,
        private CommentThreadModel $threadModel,
        private DiscussionModel $discussionModel,
        private UserModel $userModel,
        private ReactionModel $reactionModel,
        private \Vanilla\Forum\Models\CommunityManagement\ReportModel $reportModel,
        private EscalationModel $escalationModel,
        private ConfigurationInterface $config,
        private LayoutViewModel $layoutViewModel,
        private LayoutModel $layoutModel,
        private ContentDraftModel $contentDraftModel,
        private LongRunner $longRunner,
        private CategoryModel $categoryModel
    ) {
    }

    /**
     * Get a comment by its numeric ID.
     *
     * @param int $id The comment ID.
     * @return array
     * @throws NotFoundException if the comment could not be found.
     */
    public function commentByID($id)
    {
        $row = $this->commentModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException("Comment");
        }
        return $row;
    }

    /**
     * Get a comment schema with minimal add/edit fields.
     *
     * @return Schema Returns a schema object.
     */
    private function commentPostSchema(bool $isPatch): Schema
    {
        $fields = [
            "body",
            "format" => new \Vanilla\Models\FormatSchema(),
            "discussionID?",
            "parentRecordType",
            "parentRecordID",
            "draftID?",
            "insertUserID:i?",
        ];

        if (!$isPatch) {
            $fields[] = "parentCommentID:i?";
        }
        $schema = Schema::parse($fields)
            ->add($this->fullSchema())
            ->addFilter("", function (array $row): array {
                if (isset($row["discussionID"])) {
                    $row["parentRecordType"] = "discussion";
                    $row["parentRecordID"] = $row["discussionID"];
                }
                return $row;
            })
            ->addValidator("insertUserID", function () {
                $this->permission("Site.Manage");
            });
        return $schema;
    }

    /**
     * Get the full comment schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function commentSchema($type = "")
    {
        if ($this->commentSchema === null) {
            $this->commentSchema = $this->schema($this->fullSchema(), "Comment");
        }
        return $this->schema($this->commentSchema, $type);
    }

    /**
     * Delete a comment.
     *
     * @param int $id The ID of the comment.
     * @throws PermissionException
     * @throws NotFoundException
     */
    public function delete(int $id)
    {
        $this->permission("Garden.SignIn.Allow");

        $comment = $this->commentByID($id);
        $parentHandler = $this->commentModel->getParentHandler($comment["parentRecordType"]);
        $parentHandler->hasViewPermission($comment["parentRecordID"], throw: true);
        $parentHandler->hasDeletePermission($comment);

        $this->commentModel->deleteID($id);
    }

    /**
     * Delete a list of comments.
     *
     * @param array $body The request body.
     * @return Data
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function delete_list(array $body)
    {
        $this->permission("Vanilla.Comments.Delete");
        $in = Schema::parse([
            "commentIDs:a" => [
                "items" => [
                    "type" => "integer",
                ],
                "description" => "List of comments IDs to delete.",
                "maxItems" => 50,
            ],
            "deleteMethod" => [
                "type" => "string",
                "enum" => ["tombstone", "full"],
            ],
        ]);
        $body = $in->validate($body);

        // Make sure we filter out duplicates.
        $commentIDs = array_unique($body["commentIDs"]);

        // Make sure we have permission to take action on all records.
        // Note some of these IDs may not actually exist (for example if they were already deleted)
        // The long-runner method will handle these.
        $result = $this->commentModel->getDiscussionIDFromWithEscalationCommentIDs($commentIDs);
        $discussionIDs = $result["discussionIDs"];
        $categoryIDs = $result["categoryIDs"];
        if (count($discussionIDs) === 0 && count($categoryIDs) === 0) {
            throw new NotFoundException("Comments", ["recordIDs" => $commentIDs]);
        }
        if (count($discussionIDs) > 0) {
            $checked = $this->discussionModel->checkCategoryPermission($discussionIDs, "Vanilla.Comments.Delete");
            if (!empty($checked["noPermissionIDs"])) {
                throw new PermissionException("Vanilla.Comments.Delete", ["recordIDs" => $checked["noPermissionIDs"]]);
            }
            if (count($checked["validIDs"]) !== count($discussionIDs)) {
                $comments = $this->commentModel
                    ->getWhere(["CommentID" => $commentIDs, "DiscussionID" => $checked["validIDs"]])
                    ->resultArray();
                $commentIDs = array_column($comments, "CommentID");
            }
        }
        $noPermissionIDs = [];
        if (count($categoryIDs) > 0) {
            foreach ($categoryIDs as $categoryID) {
                if (!CategoryModel::checkPermission($categoryID, "Vanilla.Comments.Delete")) {
                    $noPermissionIDs[] = $categoryID;
                }
            }
            $categoryIDs = array_diff($categoryIDs, $noPermissionIDs);
            if ($categoryIDs > 0) {
                $query = $this->commentModel->permissionedCommentQuery(
                    // Only discussions supported here for now.
                    parentRecordTypes: ["discussion", "escalation"],
                    where: ["c.CommentID" => $commentIDs, "e.placeRecordID" => $categoryIDs]
                );
                $query->resetSelects();
                $query->select(["c.CommentID"]);
                $comments = $query->get()->result(DATASET_TYPE_ARRAY);
                $commentIDs = array_merge($commentIDs, array_column($comments, "CommentID"));
            }
        }

        // Defer to the LongRunner for execution.
        $result = $this->longRunner->runApi(
            new LongRunnerAction(CommentDeleteModel::class, "deleteCommentsIterator", [
                $commentIDs,
                $body["deleteMethod"],
            ])
        );
        return $result;
    }

    /**
     * Get a discussion by its numeric ID.
     *
     * @param int $id The discussion ID.
     * @return array
     * @throws NotFoundException if the discussion could not be found.
     */
    public function discussionByID($id)
    {
        $row = $this->discussionModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException("Discussion");
        }
        return $row;
    }

    /**
     * Get a schema instance comprised of all available comment fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullSchema()
    {
        $result = $this->commentModel->schema();
        return $result;
    }

    /**
     * Get a comment.
     *
     * @param int $id The ID of the comment.
     * @param array $query The request query.
     * @return array
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
     * @throws HttpException
     */
    public function get(int $id, array $query)
    {
        $query["id"] = $id;
        $this->permission();

        $in = $this->schema($this->idParamSchema(), ["CommentGet", "in"])->setDescription("Get a comment.");

        $query = $in->validate($query);
        $commentSchema = CrawlableRecordSchema::applyExpandedSchema(
            $this->commentSchema(),
            "comment",
            $query["expand"]
        );
        $out = $this->schema($commentSchema, "out");
        $out = $this->getEventManager()->fireFilter("commentsApiController_getOutSchema", $out);

        $comment = $this->commentByID($id);
        if (isset($comment["DiscussionID"])) {
            $this->getEventManager()->fireFilter(
                "commentsApiController_getFilters",
                $this,
                $comment["DiscussionID"],
                $query
            );
        }

        $this->commentModel->hasViewPermission($comment["parentRecordType"], $comment["parentRecordID"]);

        $this->userModel->expandUsers($comment, $this->resolveExpandFields($query, ["insertUser" => "InsertUserID"]));

        if (ModelUtils::isExpandOption("attachments", $query["expand"] ?? [])) {
            $attachmentModel = AttachmentModel::instance();
            $attachmentModel->joinAttachments($comment);
        }
        $comment = $this->normalizeOutput($comment, $query["expand"]);

        if (ModelUtils::isExpandOption("category", $query["expand"])) {
            $this->categoryModel->expandCategories($comment);
        }

        $quoteParent = $query["quoteParent"] ?? true;
        $parentID = $comment["parentCommentID"] ?? null;
        if ($quoteParent && $parentID) {
            $quote = $this->threadModel->renderParentCommentAsQuote($comment);
            $comment["body"] = $quote . $comment["body"];
        }

        if (ModelUtils::isExpandOption("reactions", $query["expand"])) {
            $this->reactionModel->expandCommentReactions($comment);
        }
        $permissions = $this->getSession()->getPermissions();
        $hasReportViewPermission = $permissions->hasAny(["posts.moderate", "community.moderate"]);
        if (ModelUtils::isExpandOption("reportMeta", $query["expand"]) && $hasReportViewPermission) {
            $this->reportModel->expandReportMeta($comment, "comment");
        }
        $result = $out->validate($comment);

        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter(
            "commentsApiController_getOutput",
            $result,
            $this,
            $in,
            $query,
            $comment
        );

        return $result;
    }

    /**
     * Get a comments quote data.
     *
     * @param int $id The ID of the comment.
     *
     * @return array The comment quote data.
     *
     * @throws NotFoundException If the record with the given ID can't be found.
     * @throws \Exception Throws an exception if no session is available.
     * @throws \Vanilla\Exception\PermissionException Throws an exception if the user does not have the specified permission(s).
     * @throws ValidationException If the output schema is configured incorrectly.
     */
    public function get_quote($id)
    {
        $this->permission();

        $this->idParamSchema();
        $out = $this->schema($this->quoteSchema(), "out");

        $comment = $this->commentByID($id);
        $this->getEventManager()->fireFilter("commentsApiController_getFilters", $this, $comment["DiscussionID"], []);

        if ($comment["InsertUserID"] !== $this->getSession()->UserID) {
            $discussion = $this->discussionByID($comment["DiscussionID"]);
            $this->discussionModel->categoryPermission("Vanilla.Discussions.View", $discussion["CategoryID"]);
        }

        $comment["Url"] = commentUrl($comment);
        $isRich = strcasecmp($comment["Format"], RichFormat::FORMAT_KEY) === 0;
        $comment["bodyRaw"] = $isRich ? json_decode($comment["Body"], true) : $comment["Body"];
        $comment["bodyRaw"] = Gdn::formatService()->renderPlainText($comment["bodyRaw"], "text");

        $this->userModel->expandUsers($comment, ["InsertUserID"]);
        $result = $out->validate($comment);
        return $result;
    }

    /**
     * Get the schema for comment quote data.
     *
     * @return Schema
     */
    private function quoteSchema(): Schema
    {
        return Schema::parse([
            "commentID:i" => "The ID of the comment.",
            "bodyRaw:s|a" =>
                "The raw body of the comment. This can be an array of rich operations or a string for other formats",
            "dateInserted:dt" => "When the comment was created.",
            "dateUpdated:dt|n" => "When the comment was last updated.",
            "insertUser" => $this->getUserFragmentSchema(),
            "url:s" => "The full URL to the comment.",
            "format" => new \Vanilla\Models\FormatSchema(true),
        ]);
    }

    /**
     * Get a comment for editing.
     *
     * @param int $id The ID of the comment.
     * @return array
     * @throws HttpException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function get_edit($id)
    {
        $this->permission("Garden.SignIn.Allow");

        $in = $this->idParamSchema()->setDescription("Get a comment for editing.");
        $out = $this->schema(
            Schema::parse([
                "commentID",
                "discussionID?",
                "body",
                "format" => new \Vanilla\Models\FormatSchema(true),
            ])->add($this->fullSchema()),
            "out"
        )->addFilter("", [\Vanilla\Formatting\Formats\RichFormat::class, "editBodyFilter"]);

        $comment = $this->commentByID($id);
        $comment["Url"] = commentUrl($comment);
        $this->getEventManager()->fireFilter("commentsApiController_getFilters", $this, $comment["DiscussionID"], []);

        $event = new BeforeCommentPostEvent($comment["parentRecordType"], $comment["parentRecordID"]);
        $this->getEventManager()->dispatch($event);
        $this->commentModel->getParentHandler($comment["parentRecordType"])->hasEditPermission($comment, throw: true);

        $result = $out->validate($comment);
        $this->applyFormatCompatibility($result, "body", "format");
        return $result;
    }

    /**
     * Get an ID-only comment record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema($type = "in")
    {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse([
                    "id:i" => "The comment ID.",
                    "expand" => ApiUtils::getExpandDefinition([
                        "-insertUser",
                        "attachments",
                        "reactions",
                        "reportMeta",
                        "countReports",
                        "warnings",
                        "category",
                    ]),
                    "quoteParent:b?" => [
                        "description" => "Include the parent comment in the quote data.",
                    ],
                ]),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * @return Schema
     */
    private function expandDefinition(): Schema
    {
        return ApiUtils::getExpandDefinition([
            "insertUser",
            "-insertUser",
            "-body",
            "attachments",
            "reactions",
            "reportMeta",
            "countReports",
            "warnings",
        ]);
    }

    /**
     * GET /api/v2/comments/thread
     *
     * @param array $query
     * @return Data
     * @throws ClientException
     * @throws HttpException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function get_thread(array $query): Data
    {
        $this->permission();

        $in = Schema::parse([
            "parentRecordType:s" => [
                "enum" => $this->commentModel->getParentRecordTypes(),
                "x-filter" => true,
            ],
            "parentRecordID:i" => [
                "x-filter" => true,
            ],
            "parentCommentID:i?" => [
                "x-filter" => true,
            ],
            "focusCommentID:i?",
            "collapseChildDepth:i?",
            "collapseChildLimit:i?",
            "sort:s" => [
                "enum" => ApiUtils::sortEnum("dateInserted", "score", ModelUtils::SORT_TRENDING),
                "default" => "dateInserted",
            ],
            "page:i" => [
                "min" => 0,
                "default" => 1,
            ],
            "limit:i" => [
                "min" => 1,
                "max" => 100,
                "default" => 50,
            ],
            "expand?" => $this->expandDefinition(),
        ]);

        $query = $in->validate($query);
        $this->getEventManager()->fireFilter("commentsApiController_beforePermissions", $query);

        $this->commentModel->hasViewPermission($query["parentRecordType"], $query["parentRecordID"], throw: true);

        $where = ApiUtils::queryToFilters($in, $query);
        $parentCommentID = $where["parentCommentID"] ?? null;

        if ($parentCommentID === null) {
            unset($where["parentCommentID"]);
            $where["parentCommentID IS NULL"] = null;
        }

        [$offset, $limit] = ApiUtils::offsetLimit($query);

        [$order, $direction] = LegacyModelUtils::orderFieldDirection($query["sort"]);

        $extraSelects = [];
        if ($order === ModelUtils::SORT_TRENDING) {
            $slotType = $this->commentModel->getAutoSlotType($query["parentRecordType"], $query["parentRecordID"]);
            $where[] = ModelUtils::slotTypeWhereExpression("DateInserted", $slotType);
            $extraSelects = ModelUtils::getTrendingSelects(
                "DateInserted",
                "COALESCE(Score, 0) + COALESCE(countChildComments, 0) * 2 + COALESCE(scoreChildComments, 0) / 10",
                $slotType
            );
        }

        $threadStructure = $this->threadModel->selectCommentThreadStructure($where, [
            Model::OPT_OFFSET => $offset,
            Model::OPT_LIMIT => $limit,
            Model::OPT_ORDER => $order,
            Model::OPT_DIRECTION => $direction,
            Model::OPT_SELECT => $extraSelects,
            CommentThreadModel::OPT_THREAD_STRUCTURE => new CommentThreadStructureOptions(
                collapseChildDepth: $query["collapseChildDepth"] ?? 3,
                collapseChildLimit: $query["collapseChildLimit"] ?? 3,
                focusCommentID: $query["focusCommentID"] ?? null
            ),
        ]);

        $metaExpands = Gdn::request()->getMeta("expand");
        $requestQuery = $query;
        if (is_array($metaExpands) && count($metaExpands)) {
            $requestQuery["expand"] = array_merge($query["expand"] ?? [], $metaExpands);
        }

        $threadStructure->applyApiUrlsToHoles(\Gdn::request()->getSimpleUrl("/api/v2/comments/thread"), $requestQuery);

        $preloadIDs = $threadStructure->getPreloadCommentIDs();
        $preloadComments = $this->index([
            "commentID" => $preloadIDs,
            "expand" => $query["expand"] ?? null,
            "limit" => $preloadIDs ? count($preloadIDs) : $query["limit"],
            "sort" => $query["sort"],
            "parentRecordType" => $query["parentRecordType"],
            "parentRecordID" => $query["parentRecordID"],
            "quoteParent" => false,
        ])->getData();

        $preloadComments = array_column($preloadComments, null, "commentID");

        $pagingCount = $this->threadModel->selectPagingCount(where: $where, options: [Model::OPT_LIMIT => 10000]);
        $paging = ApiUtils::numberedPagerInfo($pagingCount, "/api/v2/comments/thread", $query, $in);

        return new Data(
            [
                "threadStructure" => $threadStructure,
                "commentsByID" => $preloadComments,
            ],
            ["paging" => $paging, APIExpandMiddleware::META_EXTRA_ITERABLES => ["commentsByID"]]
        );
    }

    /**
     * List comments.
     *
     * @param array $query The query string.
     * @return Data
     * @throws HttpException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function index(array $query)
    {
        $this->permission();

        $in = $this->schema(
            [
                "commentID?" => \Vanilla\Schema\RangeExpression::createSchema([":int"])->setField("x-filter", [
                    "field" => "CommentID",
                ]),
                "dateInserted?" => new DateFilterSchema([
                    "description" => "When the comment was created.",
                    "x-filter" => [
                        "field" => "c.DateInserted",
                        "processor" => [DateFilterSchema::class, "dateFilterField"],
                    ],
                ]),
                "dateUpdated?" => new DateFilterSchema([
                    "description" => "When the comment was updated.",
                    "x-filter" => [
                        "field" => "c.DateUpdated",
                        "processor" => [DateFilterSchema::class, "dateFilterField"],
                    ],
                ]),
                "discussionID:i?" => [
                    "description" => "The discussion ID.",
                    "x-filter" => [
                        "field" => "DiscussionID",
                    ],
                ],
                "categoryID:i?" => RangeExpression::createSchema([":int"])
                    ->setDescription("Filter by a range of category IDs.")
                    ->setField("x-filter", [
                        "field" => "d.CategoryID",
                        "processor" => function ($name, $value) {
                            foreach ($value as $categoryID) {
                                $this->discussionModel->categoryPermission("Vanilla.Discussions.View", $categoryID);
                            }
                            return [$name => $value];
                        },
                    ]),
                "parentRecordType:s?" => [
                    "enum" => $this->commentModel->getParentRecordTypes(),
                    "x-filter" => true,
                ],
                "parentRecordID:i?" => [
                    "x-filter" => true,
                ],
                "dirtyRecords:b?",
                "page:i?" => [
                    "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                    "default" => 1,
                    "minimum" => 1,
                ],
                "sort:s?" => [
                    "enum" => ApiUtils::sortEnum(
                        "dateInserted",
                        "commentID",
                        "dateUpdated",
                        "score",
                        ModelUtils::SORT_TRENDING
                    ),
                    "default" => "dateInserted",
                ],
                "limit:i?" => [
                    "description" => "Desired number of items per page.",
                    "default" => $this->commentModel->getDefaultLimit(),
                    "minimum" => 1,
                    "maximum" => ApiUtils::getMaxLimit(),
                ],
                "insertUserID:i?" => [
                    "description" => "Filter by author.",
                    "x-filter" => [
                        "field" => "InsertUserID",
                    ],
                ],
                "insertUserRoleID?" => [
                    "type" => "array",
                    "items" => [
                        "type" => "integer",
                    ],
                    "style" => "form",
                    "x-filter" => [
                        "field" => "uri.RoleID",
                    ],
                ],
                "expand?" => $this->expandDefinition(),
                "quoteParent:b?" => [
                    "description" => "Include the parent comment in the quote data.",
                ],
            ],
            ["CommentIndex", "in"]
        )
            ->addValidator("insertUserRoleID", function ($data, $field) {
                RoleModel::roleViewValidator($data, $field);
            })
            ->addValidator("", SchemaUtils::fieldRequirement("parentRecordID", "parentRecordType"))
            ->setDescription("List comments.");

        $query = $in->validate($query);
        $commentSchema = CrawlableRecordSchema::applyExpandedSchema(
            $this->commentSchema(),
            "comment",
            $query["expand"]
        );
        $commentSchema = $this->getEventManager()->fireFilter("commentsApiController_getOutSchema", $commentSchema);

        $out = $this->schema([":a" => $commentSchema], "out");

        if (isset($query["discussionID"])) {
            $query["parentRecordType"] = "discussion";
            $query["parentRecordID"] = $query["discussionID"];
        }

        $this->getEventManager()->fireFilter("commentsApiController_beforePermissions", $query);

        if (isset($query["parentRecordID"])) {
            $this->commentModel->hasViewPermission($query["parentRecordType"], $query["parentRecordID"], throw: true);
        }

        $where = ApiUtils::queryToFilters($in, $query);

        // Allow addons to update the where clause.
        $where = $this->getEventManager()->fireFilter("commentsApiController_indexFilters", $where, $this, $in, $query);

        $joinDirtyRecords = $query[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;
        if ($joinDirtyRecords) {
            $where[DirtyRecordModel::DIRTY_RECORD_OPT] = $joinDirtyRecords;
        }

        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        [$orderField, $orderDirection] = \Vanilla\Models\LegacyModelUtils::orderFieldDirection($query["sort"]);

        $extraSelects = [];
        if ($orderField === ModelUtils::SORT_TRENDING) {
            if (!isset($query["parentRecordType"]) || !isset($query["parentRecordID"])) {
                throw new ClientException("Trending sort requires a parentRecordType and parentRecordID.", 400);
            }
            // Determine a slotType
            $autoSlotType = $this->commentModel->getAutoSlotType($query["parentRecordType"], $query["parentRecordID"]);
            $where[] = ModelUtils::slotTypeWhereExpression("c.DateInserted", $autoSlotType);

            $extraSelects = ModelUtils::getTrendingSelects(
                dateField: "c.DateInserted",
                scoreCalculation: CommentModel::trendingScoreCalculationSql(),
                slotType: $autoSlotType
            );
        }

        $rows = $this->commentModel
            ->selectComments($where, [
                Model::OPT_LIMIT => $limit,
                Model::OPT_OFFSET => $offset,
                Model::OPT_DIRECTION => $orderDirection,
                Model::OPT_ORDER => $orderField,
                Model::OPT_SELECT => $extraSelects,
            ])
            ->resultArray();
        $hasMore = count($rows) >= $limit;

        // Expand associated rows.
        $this->userModel->expandUsers($rows, $this->resolveExpandFields($query, ["insertUser" => "InsertUserID"]));
        if (ModelUtils::isExpandOption("attachments", $query["expand"] ?? [])) {
            $attachmentModel = AttachmentModel::instance();
            $attachmentModel->joinAttachments($rows);
        }

        $quoteParent = $query["quoteParent"] ?? true;

        foreach ($rows as &$currentRow) {
            $currentRow = $this->normalizeOutput($currentRow, $query["expand"]);

            // Render the parent as a quote.
            $parentCommentID = $currentRow["parentCommentID"] ?? false;
            if ($quoteParent && $parentCommentID) {
                $quote = $this->threadModel->renderParentCommentAsQuote($currentRow);
                $currentRow["body"] = $quote . $currentRow["body"];
            }
        }

        if (ModelUtils::isExpandOption("reactions", $query["expand"])) {
            $this->reactionModel->expandCommentReactions($rows);
        }

        $permissions = $this->getSession()->getPermissions();
        $hasReportViewPermission = $permissions->hasAny(["posts.moderate", "community.moderate"]);
        if (ModelUtils::isExpandOption("reportMeta", $query["expand"]) && $hasReportViewPermission) {
            $this->reportModel->expandReportMeta($rows, "comment");
        }

        $result = $out->validate($rows);

        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter(
            "commentsApiController_indexOutput",
            $result,
            $this,
            $in,
            $query,
            $rows
        );

        $originalWhere = $where;
        $parentRecordType = $where["parentRecordType"] ?? null;
        $parentRecordID = $where["parentRecordID"] ?? null;
        $whereWithOutDiscussionID = $where;
        unset($where["DiscussionID"]);

        $count = null;
        if (count($whereWithOutDiscussionID) === 2 && $parentRecordType !== null && $parentRecordID !== null) {
            // We are querying just off of parent record. Let's use the aggregate count.
            $count = match ($parentRecordType) {
                "discussion" => $this->discussionByID($parentRecordID)["CountComments"] ?? null,
                "escalation" => $this->escalationModel->selectSingle()["countComments"] ?? null,
                default => null,
            };
        }

        if (!isset($where["CommentID"]) && $count === null) {
            // We aren't querying directly on commentID and we don't have a count from the parent record.
            // Let's do a paging count
            $count = $this->commentModel->selectPagingCount($originalWhere, 10000);
        }

        if ($count !== null) {
            // We have a numbered pager.
            $paging = ApiUtils::numberedPagerInfo($count, "/api/v2/comments", $query, $in);
        } else {
            $paging = ApiUtils::morePagerInfo($hasMore, "/api/v2/comments", $query, $in);
        }

        $pagingObject = Pagination::tryCursorPagination($paging, $query, $result, "commentID");
        return new Data($result, $pagingObject);
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @param array|string|bool $expand
     *
     * @return array Return a Schema record.
     */
    public function normalizeOutput(array $dbRecord, $expand = [])
    {
        $normalizedRow = $this->commentModel->normalizeRow($dbRecord, $expand);

        $normalizedRow["type"] = "comment";
        $normalizedRow["recordID"] = "commentID";
        // Allow addons to hook into the normalization process.
        $options = [
            "expand" => $expand,
        ];
        $result = $this->getEventManager()->fireFilter(
            "commentsApiController_normalizeOutput",
            $normalizedRow,
            $this,
            $options
        );
        return $result;
    }

    /**
     * Update a comment.
     *
     * @param int $id The ID of the comment.
     * @param array $body The request body.
     * @return array
     * @throws ClientException If comment editing is not allowed.
     * @throws HttpException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function patch(int $id, array $body): array
    {
        $this->permission("Garden.SignIn.Allow");

        $this->idParamSchema("in");
        $in = $this->commentPostSchema(isPatch: true)->setDescription("Update a comment.");
        $out = $this->commentSchema("out");

        $body = $in->validate($body, true);
        $commentData = ApiUtils::convertInputKeys($body);
        $commentData["CommentID"] = $id;
        $row = $this->commentByID($id);

        $event = new BeforeCommentPostEvent($row["parentRecordType"], $row["parentRecordID"]);
        $this->getEventManager()->dispatch($event);

        $this->commentModel->getParentHandler($row["parentRecordType"])->hasEditPermission($row, throw: true);

        // If we are moving a comment betweend discussions we need to be able to also add comments to the new discussion.
        if (array_key_exists("DiscussionID", $commentData) && $row["DiscussionID"] !== $commentData["DiscussionID"]) {
            $discussion = $this->discussionByID($commentData["DiscussionID"]);
            $this->discussionModel->categoryPermission("Vanilla.Comments.Add", $discussion["CategoryID"]);
        }
        // Body is a required field in CommentModel::save.
        if (!array_key_exists("Body", $commentData)) {
            $commentData["Body"] = $row["Body"];
        }
        $saveResult = $this->commentModel->save($commentData);
        $this->validateModel($this->commentModel);
        ModelUtils::validateSaveResultPremoderation($saveResult, "comment");

        $row = $this->commentByID($id);
        $this->userModel->expandUsers($row, ["InsertUserID"]);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Add a comment.
     *
     * @param array $body The request body.
     * @return array
     * @throws Exception If the user cannot view the discussion.
     * @throws ServerException If the comment could not be created.
     */
    public function post(array $body)
    {
        $this->permission("Garden.SignIn.Allow");
        $in = $this->commentPostSchema(isPatch: false)->setDescription("Add a comment.");
        $out = $this->commentSchema("out");

        $body = $in->validate($body);
        $parentRecordType = $body["parentRecordType"];
        $parentRecordID = $body["parentRecordID"];
        $event = new BeforeCommentPostEvent($parentRecordType, $parentRecordID);
        $this->getEventManager()->dispatch($event);

        $this->commentModel->getParentHandler($parentRecordType)->hasAddPermission($parentRecordID, throw: true);

        if (isset($body["parentCommentID"])) {
            if (!FeatureFlagHelper::featureEnabled("customLayout.post")) {
                throw new ClientException("Parent comments are not allowed without custom discussion threads.", 400);
            }

            // Validate that the parent comment exists.
            $parentComment = $this->threadModel->selectThreadCommentFragment($body["parentCommentID"]);

            // Validate that we are in the same thread.
            if (
                $parentComment["parentRecordType"] !== $parentRecordType ||
                $parentComment["parentRecordID"] !== $parentRecordID
            ) {
                throw new ClientException("Parent comment is from a different thread.", 400, [
                    "expected" => [
                        "parentRecordType" => $parentComment["parentRecordType"],
                        "parentRecordID" => $parentComment["parentRecordID"],
                    ],
                    "actual" => [
                        "parentRecordType" => $parentRecordType,
                        "parentRecordID" => $parentRecordID,
                    ],
                ]);
            }

            // Validate that we didn't exceed our maximum depth
            $maxDepth = $this->commentModel->resolveCommentMaxDepth($body["parentCommentID"]);
            if ($parentComment["depth"] + 1 > $maxDepth) {
                throw new ClientException("Comment exceeds maximum depth.", 400, [
                    "maxDepth" => $maxDepth,
                    "actualDepth" => $parentComment["depth"] + 1,
                ]);
            }
        }

        $commentData = ApiUtils::convertInputKeys(
            $body,
            excludedKeys: ["parentRecordType", "parentRecordID", "parentCommentID"]
        );

        $id = $this->commentModel->save($commentData);
        $this->validateModel($this->commentModel);

        // Comments drafts should be deleted after the comment is made
        if ($draftID = $body["draftID"] ?? null) {
            $this->contentDraftModel->deleteDraftWithPermissionCheck($draftID);
        }

        ModelUtils::validateSaveResultPremoderation($id, "comment");
        if (!$id) {
            throw new ServerException("Unable to read inserted comment.", 500);
        }
        $row = $this->commentByID($id);
        $this->userModel->expandUsers($row, ["InsertUserID"]);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Search comments.
     *
     * @param array $query
     * @return Data
     * @throws HttpException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function get_search(array $query)
    {
        $this->permission("Garden.SignIn.Allow");

        $in = $this->schema(
            [
                "categoryID:i?" => "The numeric ID of a category.",
            ],
            "in"
        )
            ->merge($this->searchSchema())
            ->setDescription("Search comments.");
        $query = $in->validate($query);

        $searchQuery = [
            "recordTypes" => ["comment"],
        ];
        if (array_key_exists("categoryID", $query)) {
            $searchQuery["categoryID"] = $query["categoryID"];
        }
        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $results = $this->getSearchService()->search($searchQuery, new SearchOptions($offset, $limit));
        $commentIDs = [];
        /** @var SearchResultItem $result */
        foreach ($results as $result) {
            $commentIDs[] = $result->getRecordID();
        }

        // Hit the comments API back for formatting.
        return $this->index([
            "commentID" => $commentIDs,
            "expand" => $query["expand"] ?? null,
            "limit" => $query["limit"] ?? null,
        ]);
    }

    /**
     * Respond to /api/v2/comments/:id/reactions
     *
     * @param int $id The comment ID.
     * @param array $query The request query.
     * @return array
     * @throws HttpException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function get_reactions(int $id, array $query)
    {
        $this->permission();

        $this->idParamSchema();
        $in = $this->schema([
            "type:s|n" => [
                "default" => null,
                "description" => "Filter to a specific reaction type by using its URL code.",
            ],
            "page:i?" => [
                "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
                "maximum" => 100,
            ],
            "limit:i?" => [
                "description" => "Desired number of items per page.",
                "default" => $this->reactionModel->getDefaultLimit(),
                "minimum" => 1,
                "maximum" => 100,
            ],
        ])->setDescription("Get reactions to a comment.");
        $out = $this->schema(
            [":a" => $this->reactionModel->getReactionLogFragment($this->getUserFragmentSchema())],
            "out"
        );

        $comment = $this->commentByID($id);
        if (!$this->commentModel->hasViewPermission($comment["parentRecordType"], $comment["parentRecordID"])) {
            throw new PermissionException("discussions.view");
        }

        $query = $in->validate($query);
        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);
        $comment += ["recordType" => "Comment", "recordID" => $comment["CommentID"]];
        $rows = $this->reactionModel->getRecordReactions($comment, true, $query["type"], $offset, $limit);

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * React to a comment with /api/v2/comments/:id/reactions
     *
     * @param int $id The comment ID.
     * @param array $body The request query.
     * @return array
     * @throws Gdn_UserException
     * @throws HttpException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function post_reactions(int $id, array $body)
    {
        $this->permission("Garden.SignIn.Allow");

        $in = $this->schema(
            [
                "reactionType:s" => "URL code of a reaction type.",
            ],
            "in"
        )->setDescription("React to a comment.");
        $out = $this->schema($this->reactionModel->getReactionSummaryFragment(), "out");

        $comment = $this->commentByID($id);
        $this->commentModel->hasViewPermission($comment["parentRecordType"], $comment["parentRecordID"], throw: true);
        $body = $in->validate($body);
        ReactionModel::checkReactionPermissions($body["reactionType"]);
        $this->reactionModel->react("Comment", $id, $body["reactionType"], null, false, ReactionModel::FORCE_ADD);

        // Refresh the comment to grab its updated attributes.
        $comment = $this->commentByID($id);
        $rows = $this->reactionModel->getRecordSummary($comment);

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Delete a comment reaction with /api/v2/comments/:id/reactions/:userID
     *
     * @param int $id The comment ID.
     * @param int|null $userID
     * @return void
     * @throws Gdn_UserException
     * @throws HttpException
     * @throws NotFoundException
     * @throws PermissionException
     */
    public function delete_reactions(int $id, int $userID = null): void
    {
        $this->permission("Garden.SignIn.Allow");

        $in = $this->schema(
            $this->idParamSchema()->merge(Schema::parse(["userID:i" => "The target user ID."])),
            "in"
        )->setDescription('Remove a user\'s reaction.');
        $out = $this->schema([], "out");

        $this->commentByID($id);

        if ($userID === null) {
            $userID = $this->getSession()->UserID;
        } elseif ($userID !== $this->getSession()->UserID) {
            $this->permission("Garden.Moderation.Manage");
        }

        $reaction = $this->reactionModel->getUserReaction($userID, "Comment", $id);
        if ($reaction) {
            $urlCode = $reaction["UrlCode"];
            ReactionModel::checkReactionPermissions($urlCode);
            $this->reactionModel->react("Comment", $id, $urlCode, $userID, false, ReactionModel::FORCE_REMOVE);
        } else {
            new NotFoundException("Reaction");
        }
    }
}
