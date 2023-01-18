<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\Community\Schemas\CategoryFragmentSchema;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\Dashboard\Models\RecordStatusLogModel;
use Vanilla\DiscussionTypeConverter;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Forum\Controllers\Api\DiscussionsApiIndexSchema;
use Vanilla\Forum\Models\DiscussionMergeModel;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Models\Model;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\SchemaFactory;
use Vanilla\Search\SearchOptions;
use Vanilla\Search\SearchResultItem;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;

/**
 * API Controller for the `/discussions` resource.
 */
class DiscussionsApiController extends AbstractApiController
{
    use CommunitySearchSchemaTrait;
    use \Vanilla\Formatting\FormatCompatTrait;

    /** @var CategoryModel */
    private $categoryModel;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var Schema */
    private $discussionSchema;

    /** @var Schema */
    private $discussionPostSchema;

    /** @var Schema */
    private $discussionPutCanonicalSchema;

    /** @var Schema */
    private $idParamSchema;

    /** @var UserModel */
    private $userModel;

    /** @var CommentModel */
    private $commentModel;

    /** @var TagModel */
    private $tagModel;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var DiscussionTypeConverter */
    private $discussionTypeConverter;

    /**
     * @var DiscussionExpandSchema
     */
    private $discussionExpandSchema;

    /**
     * @var Schema
     */
    private $discussionPatchSchema;

    /** @var RecordStatusModel */
    private $recordStatusModel;

    /** @var RecordStatusLogModel */
    private $recordStatusLogModel;

    /** @var LongRunner */
    private $longRunner;

    /** @var DiscussionStatusModel */
    private $discussionStatusModel;

    /**
     * DiscussionsApiController constructor.
     *
     * @param DiscussionModel $discussionModel
     * @param UserModel $userModel
     * @param CategoryModel $categoryModel
     * @param CommentModel $commentModel
     * @param TagModel $tagModel
     * @param SiteSectionModel $siteSectionModel
     * @param DiscussionTypeConverter $discussionTypeConverter
     * @param DiscussionExpandSchema $discussionExpandableSchema
     * @param RecordStatusModel $recordStatusModel
     * @param RecordStatusLogModel $recordStatusLogModel
     * @param LongRunner $longRunner
     * @param DiscussionStatusModel $discussionStatusModel
     */
    public function __construct(
        DiscussionModel $discussionModel,
        UserModel $userModel,
        CategoryModel $categoryModel,
        CommentModel $commentModel,
        TagModel $tagModel,
        SiteSectionModel $siteSectionModel,
        DiscussionTypeConverter $discussionTypeConverter,
        DiscussionExpandSchema $discussionExpandableSchema,
        RecordStatusModel $recordStatusModel,
        RecordStatusLogModel $recordStatusLogModel,
        LongRunner $longRunner,
        DiscussionStatusModel $discussionStatusModel
    ) {
        $this->categoryModel = $categoryModel;
        $this->discussionModel = $discussionModel;
        $this->userModel = $userModel;
        $this->commentModel = $commentModel;
        $this->tagModel = $tagModel;
        $this->siteSectionModel = $siteSectionModel;
        $this->discussionTypeConverter = $discussionTypeConverter;
        $this->discussionExpandSchema = $discussionExpandableSchema;
        $this->recordStatusModel = $recordStatusModel;
        $this->recordStatusLogModel = $recordStatusLogModel;
        $this->longRunner = $longRunner;
        $this->discussionStatusModel = $discussionStatusModel;
    }

    /**
     * Get a list of the current user's bookmarked discussions.
     *
     * @param array $query The request query.
     * @return Data
     */
    public function get_bookmarked(array $query)
    {
        $this->permission("Garden.SignIn.Allow");

        $in = $this->schema(
            [
                "page:i?" => [
                    "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                    "default" => 1,
                    "minimum" => 1,
                    "maximum" => $this->discussionModel->getMaxPages(),
                ],
                "limit:i?" => [
                    "description" => "Desired number of items per page.",
                    "default" => $this->discussionModel->getDefaultLimit(),
                    "minimum" => 1,
                    "maximum" => ApiUtils::getMaxLimit(100),
                ],
                "expand?" => ApiUtils::getExpandDefinition([
                    "insertUser",
                    "lastUser",
                    "lastPost",
                    "lastPost.body",
                    "lastPost.insertUser",
                    "reactions",
                    "status",
                ]),
            ],
            "in"
        )->setDescription('Get a list of the current user\'s bookmarked discussions.');
        $out = $this->schema([":a" => $this->discussionSchema()], "out");

        $query = $in->validate($query);
        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $rows = $this->discussionModel
            ->get($offset, $limit, [
                "w.Bookmarked" => 1,
                "w.UserID" => $this->getSession()->UserID,
            ])
            ->resultArray();

        $this->userModel->expandUsers(
            $rows,
            $this->resolveExpandFields($query, [
                "insertUser" => "InsertUserID",
                "lastUser" => "LastUserID",
                "lastPost.insertUser" => "LastUserID",
            ])
        );
        foreach ($rows as &$currentRow) {
            $currentRow = $this->normalizeOutput($currentRow, $query["expand"]);
        }
        // Expand associated rows.
        $this->discussionExpandSchema->commonExpand($rows, $query["expand"] ?? []);
        $this->expandLastCommentBody($rows, $query["expand"]);

        $result = $out->validate($rows);

        $result = $this->getEventManager()->fireFilter(
            "discussionsApiController_getOutput",
            $result,
            $this,
            $in,
            $query,
            $rows
        );

        $paging = ApiUtils::morePagerInfo($result, "/api/v2/discussions/bookmarked", $query, $in);

        return new Data($result, ["paging" => $paging]);
    }

    /**
     * Get a list of the discussion status changes.
     * @param int $id The record id
     * @param array $query The request query.
     * @return Data
     */
    public function get_statusLog(int $id, array $query)
    {
        $this->permission("session.valid");
        $in = $this->schema(
            [
                "limit:i?" => [
                    "description" => "Desired number of items per page.",
                    "default" => $this->discussionModel->getDefaultLimit(),
                    "minimum" => 1,
                    "maximum" => ApiUtils::getMaxLimit(100),
                ],
                "page:i?" => [
                    "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                    "default" => 1,
                    "minimum" => 1,
                    "maximum" => $this->discussionModel->getMaxPages(),
                ],
            ],
            "in"
        )->setDescription("Get a list status changes for the discussion");
        $out = $this->schema([":a" => $this->recordStatusLogModel->getSchema()], "out");
        $query = $in->validate($query);
        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $where = [
            "recordID" => $id,
            "recordType" => "discussion",
        ];
        $options = [
            "limit" => $limit,
            "offset" => $offset,
            "orderFields" => ["recordLogID"],
        ];
        $data = $this->recordStatusLogModel->getAllowedStatusLogs($where, $options);
        $result = $out->validate($data);

        $paging = ApiUtils::numberedPagerInfo(
            $this->recordStatusLogModel->getRecordStatusLogCount($id),
            "/api/v2/discussions/$id/status-log",
            $query,
            $in
        );

        return new Data($result, ["paging" => $paging]);
    }

    /**
     * Delete a discussion.
     *
     * @param int $id The ID of the discussion.
     *
     * @return Data
     */
    public function delete(int $id): Data
    {
        $this->permission("Garden.SignIn.Allow");

        $row = $this->discussionByID($id);
        $this->discussionModel->categoryPermission("Vanilla.Discussions.Delete", $row["CategoryID"]);
        $result = $this->longRunner->runApi(
            new LongRunnerAction(DiscussionModel::class, "deleteDiscussionIterator", [$id])
        );
        if ($result->getStatus() === 200) {
            $result->setStatus(204);
        }
        return $result;
    }

    /**
     * Delete a list of discussions.
     *
     * @param array $body The request body.
     */
    public function delete_list(array $body)
    {
        $this->permission("Vanilla.Discussions.Delete");
        $in = Schema::parse([
            "discussionIDs:a" => [
                "items" => [
                    "type" => "integer",
                ],
                "description" => "List of discussion IDs to delete.",
                "maxItems" => 50,
            ],
        ]);
        $body = $in->validate($body);

        // Make sure we filter out duplicates.
        $discussionIDs = array_unique($body["discussionIDs"]);

        // Make sure we have permission to take action on all records.
        // Note some of these IDs may not actually exist (for example if they were already deleted)
        // The long-runner method will handle these.
        $checked = $this->discussionModel->checkCategoryPermission($discussionIDs, "Vanilla.Discussions.Delete");
        if (!empty($checked["noPermissionIDs"])) {
            throw new PermissionException("Vanilla.Discussions.Delete", ["recordIDs" => $checked["noPermissionIDs"]]);
        }

        // Defer to the LongRunner for execution.
        $result = $this->longRunner->runApi(
            new LongRunnerAction(DiscussionModel::class, "deleteDiscussionsIterator", [$checked["validIDs"]])
        );
        return $result;
    }

    /**
     * Move a list of discussions.
     *
     * @param array $body The request body.
     *
     * @return Data the HTTP response.
     */
    public function patch_move(array $body): Data
    {
        // Permissions are checked per-row in the model.
        $this->permission("Vanilla.Discussions.Edit");
        $in = Schema::parse([
            "discussionIDs:a" => [
                "items" => [
                    "type" => "integer",
                ],
                "description" => "List of discussion IDs to move.",
                "maxItems" => 50,
            ],
            "categoryID:i",
            "addRedirects:b" => [
                "default" => false,
            ],
        ]);
        $body = $in->validate($body);

        $discussionIDs = $body["discussionIDs"];
        $categoryID = $body["categoryID"];

        // Check new category permission.
        $this->permission("Vanilla.Discussions.Edit", $categoryID);

        // Make sure we have permission to take action on all discussions.
        $filtered = $this->discussionModel->filterCategoryPermissions($discussionIDs, "Vanilla.Discussions.Edit");
        $missingPermissionIDs = array_diff($discussionIDs, $filtered);
        if (!empty($missingPermissionIDs)) {
            throw new PermissionException("Vanilla.Discussions.Edit", ["recordIDs" => $missingPermissionIDs]);
        }

        // Defer to the LongRunner for execution.
        $result = $this->longRunner->runApi(
            new LongRunnerAction(DiscussionModel::class, "moveDiscussionsIterator", [
                $discussionIDs,
                $categoryID,
                $body["addRedirects"],
            ])
        );
        return $result;
    }

    /**
     * Close(or Open) a list of discussions.
     *
     * @param array $body The request body.
     *
     * @return Data the HTTP response.
     */
    public function patch_close(array $body): Data
    {
        $in = Schema::parse([
            "discussionIDs:a" => [
                "items" => [
                    "type" => "integer",
                ],
                "description" => "List of discussion IDs to close(or open).",
                "maxItems" => 50,
            ],
            "closed:b" => [
                "default" => true,
            ],
        ]);
        $body = $in->validate($body);

        $discussionIDs = $body["discussionIDs"];

        // Make sure we have permission to take action on all discussions.
        $checkedPerms = $this->discussionModel->checkCategoryPermission($discussionIDs, "Vanilla.Discussions.Close");
        if (!empty($checkedPerms["noPermissionIDs"])) {
            throw new PermissionException("Vanilla.Discussions.Close", [
                "recordIDs" => $checkedPerms["noPermissionIDs"],
            ]);
        }

        // Defer to the LongRunner for execution.
        $result = $this->longRunner->runApi(
            new LongRunnerAction(DiscussionModel::class, "closeDiscussionsIterator", [
                $checkedPerms["validIDs"],
                $body["closed"],
            ])
        );
        return $result;
    }

    /**
     * Merge discussions.
     *
     * @param array $body The request's body.
     *
     * @return Data
     */
    public function patch_merge(array $body): Data
    {
        $this->permission("Vanilla.Discussions.Edit");
        $in = Schema::parse([
            "discussionIDs:a" => [
                "items" => [
                    "type" => "integer",
                ],
                "maxItems" => 50,
            ],
            "destinationDiscussionID:i",
            "addRedirects:b" => [
                "default" => false,
            ],
        ]);
        $body = $in->validate($body);
        $discussionIDs = $body["discussionIDs"];
        $destinationDiscussionID = $body["destinationDiscussionID"];
        $destinationDiscussion = $this->discussionByID($destinationDiscussionID);

        $this->permission("Vanilla.Discussions.Edit", $destinationDiscussion["CategoryID"]);

        // Make sure we have permission to take action on all discussions.
        $checked = $this->discussionModel->checkCategoryPermission($discussionIDs, "Vanilla.Discussions.Delete");
        if (!empty($checked["nonexistentIDs"])) {
            throw new NotFoundException("Discussion", ["recordIDs" => $checked["nonexistentIDs"]]);
        }
        if (!empty($checked["noPermissionIDs"])) {
            throw new PermissionException("Vanilla.Discussions.Delete", ["recordIDs" => $checked["noPermissionIDs"]]);
        }

        // Defer to the LongRunner for execution.
        $result = $this->longRunner->runApi(
            new LongRunnerAction(DiscussionMergeModel::class, "mergeDiscussionsIterator", [
                $discussionIDs,
                $destinationDiscussionID,
                $body["addRedirects"],
            ])
        );
        return $result;
    }

    /**
     * Get a discussion by its numeric ID.
     *
     * @param int $id The discussion ID.
     * @return array
     *
     * @throws NotFoundException If the discussion could not be found.
     */
    public function discussionByID(int $id): array
    {
        $row = $this->discussionModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException("Discussion", ["discussionID" => $id]);
        }
        return $row;
    }

    /**
     * Get a discussion schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function discussionPostSchema($type = "")
    {
        if ($this->discussionPostSchema === null) {
            $this->discussionPostSchema = $this->schema(
                Schema::parse([
                    "name",
                    "body",
                    "format" => new \Vanilla\Models\FormatSchema(),
                    "categoryID",
                    "closed?",
                    "sink?",
                    "pinned?",
                    "pinLocation?",
                ])
                    ->add(DiscussionExpandSchema::commonExpandSchema())
                    ->add($this->fullSchema()),
                "DiscussionPost"
            );
        }
        return $this->schema($this->discussionPostSchema, $type);
    }

    /**
     * Get a discussion schema with minimal editable fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function discussionPatchSchema($type = "")
    {
        if ($this->discussionPatchSchema === null) {
            $this->discussionPatchSchema = $this->schema(
                Schema::parse([
                    "name?",
                    "body?",
                    "format?" => new \Vanilla\Models\FormatSchema(),
                    "categoryID?",
                    "closed?",
                    "sink?",
                    "pinned?",
                    "pinLocation?",
                ])
                    ->add(DiscussionExpandSchema::commonExpandSchema())
                    ->add($this->fullSchema()),
                "DiscussionPatch"
            );
        }
        return $this->schema($this->discussionPatchSchema, $type);
    }

    /**
     * Get a discussion set canonical url schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function discussionPutCanonicalSchema($type = "")
    {
        if ($this->discussionPutCanonicalSchema === null) {
            $this->discussionPutCanonicalSchema = $this->schema(
                Schema::parse(["canonicalUrl"])->add($this->fullSchema()),
                "DiscussionPutCanonical"
            );
        }
        return $this->schema($this->discussionPutCanonicalSchema, $type);
    }

    /**
     * Get the full discussion schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function discussionSchema($type = "")
    {
        if ($this->discussionSchema === null) {
            $this->discussionSchema = $this->schema($this->fullSchema(), "Discussion");
        }
        return $this->schema($this->discussionSchema, $type);
    }

    /**
     * Get a schema instance comprised of all available discussion fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullSchema()
    {
        $result = $this->discussionModel
            ->schema()
            ->merge($this->discussionModel->userDiscussionSchema())
            ->merge(
                Schema::parse([
                    "category?" => SchemaFactory::get(CategoryFragmentSchema::class, "CategoryFragment"),
                    "recordID:i?",
                ])
            );
        return $result;
    }

    /**
     * Get a discussion.
     *
     * @param int $id The ID of the discussion.
     * @param array $query The request query.
     * @throws NotFoundException if the discussion could not be found.
     * @return array
     */
    public function get(int $id, array $query)
    {
        $this->permission();

        $this->idParamSchema();
        $in = $this->schema(
            DiscussionExpandSchema::commonExpandSchema(), // Allow addons to expand additional fields.
            ["DiscussionGet", "in"]
        )->setDescription("Get a discussion.");
        $query = $in->validate($query);
        $discussionSchema = CrawlableRecordSchema::applyExpandedSchema(
            $this->discussionSchema(),
            "discussion",
            $query["expand"]
        );

        $out = $this->schema($discussionSchema, "out");

        $this->getEventManager()->fireFilter("discussionsApiController_getFilters", $this, $id, $query);

        $row = $this->discussionByID($id);
        if (!$row) {
            throw new NotFoundException("Discussion");
        }

        $this->discussionModel->categoryPermission("Vanilla.Discussions.View", $row["CategoryID"]);

        $this->userModel->expandUsers(
            $row,
            $this->resolveExpandFields($query, ["insertUser" => "InsertUserID", "lastUser" => "LastUserID"])
        );
        $row = $this->normalizeOutput($row, $query["expand"]);

        $this->discussionExpandSchema->commonExpand($row, $query["expand"] ?? []);
        $rows = [&$row];
        $this->expandLastCommentBody($rows, $query["expand"]);
        $result = $out->validate($row);
        if ($this->isExpandField("tags", $query["expand"]) ?? false) {
            $this->tagModel->expandTags($result);
        }

        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter(
            "discussionsApiController_getOutput",
            $result,
            $this,
            $in,
            $query,
            $row,
            true
        );
        return $result;
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @param array|string|bool $expand
     * @return array Return a Schema record.
     */
    public function normalizeOutput(array $dbRecord, $expand = [])
    {
        $normalizedRow = $this->discussionModel->normalizeRow($dbRecord, $expand);

        // Fetch the crumb model lazily to prevent DI issues.
        /** @var BreadcrumbModel $breadcrumbModel */
        $breadcrumbModel = \Gdn::getContainer()->get(BreadcrumbModel::class);
        if ($this->isExpandField("breadcrumbs", $expand)) {
            $normalizedRow["breadcrumbs"] = $breadcrumbModel->getForRecord(
                new ForumCategoryRecordType($normalizedRow["categoryID"])
            );
        }

        // Allow addons to hook into the normalization process.
        $options = ["expand" => $expand];
        $result = $this->getEventManager()->fireFilter(
            "discussionsApiController_normalizeOutput",
            $normalizedRow,
            $this,
            $options
        );

        return $result;
    }

    /**
     * Get a discussion's quote data.
     *
     * @param int $id The ID of the discussion.
     *
     * @return array The discussion quote data.
     *
     * @throws NotFoundException If the record with the given ID can't be found.
     * @throws \Exception Throws an exception if no session is available.
     * @throws PermissionException Throws an exception if the user does not have the specified permission(s).
     * @throws ValidationException If the output schema is configured incorrectly.
     */
    public function get_quote(int $id)
    {
        $this->permission();

        $this->idParamSchema();
        $in = $this->schema([], "in")->setDescription("Get a discussions embed data.");
        $out = $this->schema($this->quoteSchema(), "out");

        $discussion = $this->discussionByID($id);
        $discussion["Url"] = discussionUrl($discussion);

        $this->getEventManager()->fireFilter("discussionsApiController_getFilters", $this, $id, []);

        if ($discussion["InsertUserID"] !== $this->getSession()->UserID) {
            $this->discussionModel->categoryPermission("Vanilla.Discussions.View", $discussion["CategoryID"]);
        }

        $isRich = strcasecmp($discussion["Format"], RichFormat::FORMAT_KEY) === 0;
        $discussion["bodyRaw"] = $isRich ? json_decode($discussion["Body"], true) : $discussion["Body"];
        $discussion = $this->discussionModel->fixRow($discussion);

        $this->userModel->expandUsers($discussion, ["InsertUserID"]);
        $result = $out->validate($discussion);
        return $result;
    }

    /**
     * Get the schema for discussion quote data.
     *
     * @return Schema
     */
    private function quoteSchema(): Schema
    {
        return Schema::parse([
            "discussionID:i" => "The ID of the discussion.",
            "name:s" => "The title of the discussion",
            "bodyRaw:s|a" =>
                "The raw body of the discussion. This can be an array of rich operations or a string for other formats",
            "dateInserted:dt" => "When the discussion was created.",
            "dateUpdated:dt|n" => "When the discussion was last updated.",
            "insertUser" => $this->getUserFragmentSchema(),
            "url:s" => "The full URL to the discussion.",
            "format" => new \Vanilla\Models\FormatSchema(true),
        ]);
    }

    /**
     * Get a discussion for editing.
     *
     * @param int $id The ID of the discussion.
     * @throws NotFoundException if the discussion could not be found.
     * @return array
     */
    public function get_edit(int $id)
    {
        $this->permission("Garden.SignIn.Allow");

        $this->idParamSchema()->setDescription("Get a discussion for editing.");
        $out = $this->schema(
            Schema::parse([
                "discussionID",
                "name",
                "body",
                "format" => new \Vanilla\Models\FormatSchema(true),
                "categoryID",
                "sink",
                "closed",
                "pinned",
                "pinLocation",
            ])->add($this->fullSchema()),
            ["DiscussionGetEdit", "out"]
        )->addFilter("", [\Vanilla\Formatting\Formats\RichFormat::class, "editBodyFilter"]);

        $row = $this->discussionByID($id);
        $row["Url"] = discussionUrl($row);

        $this->getEventManager()->fireFilter("discussionsApiController_getFilters", $this, $id, []);
        if ($row["InsertUserID"] !== $this->getSession()->UserID) {
            $this->discussionModel->categoryPermission("Vanilla.Discussions.Edit", $row["CategoryID"]);
        }

        $result = $out->validate($row);
        $this->applyFormatCompatibility($result, "body", "format");
        return $result;
    }

    /**
     * Get an ID-only discussion record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema($type = "in")
    {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(Schema::parse(["id:i" => "The discussion ID."]), $type);
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * List discussions.
     *
     * @param array $query The query string.
     * @return Data
     */
    public function index(array $query)
    {
        $this->permission();
        $in = $this->schema(new DiscussionsApiIndexSchema($this->discussionModel->getDefaultLimit()), [
            "DiscussionIndex",
            "in",
        ])->setDescription("List discussions.");
        $query["followed"] = $query["followed"] ?? false;
        $query["excludeHiddenCategories"] = $query["excludeHiddenCategories"] ?? false;
        $query = $in->validate($query);
        $query = $this->filterValues($query);

        $discussionSchema = CrawlableRecordSchema::applyExpandedSchema(
            $this->discussionSchema(),
            "discussion",
            $query["expand"]
        );
        $out = $this->schema([":a" => $discussionSchema], "out");

        $where = ApiUtils::queryToFilters($in, $query);
        if ($where["d.statusID"] ?? false) {
            $where["d.statusID"] = $this->recordStatusModel->validateStatusesAreActive($where["d.statusID"], false);
        }

        if ($where["d.internalStatusID"] ?? false) {
            if (\Gdn::session()->checkPermission("staff.allow")) {
                $where["d.internalStatusID"] = $this->recordStatusModel->validateStatusesAreActive(
                    $where["d.internalStatusID"],
                    true
                );
            } else {
                unset($where["d.internalStatusID"]);
            }
        }

        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $followed = $query["followed"] ?? false;
        $siteSectionID = $query["siteSectionID"] ?? "";
        $bookmarkUserID = $query["bookmarkUserID"] ?? false;
        $participatedUserID = $query["participatedUserID"] ?? false;
        $excludeHiddenCategories = $query["excludeHiddenCategories"] ?? false;

        if (!empty($this->getSession()->UserID)) {
            if (
                ($bookmarkUserID && $this->getSession()->UserID != $bookmarkUserID) ||
                ($participatedUserID && $this->getSession()->UserID != $participatedUserID)
            ) {
                $this->permission("Garden.Moderation.Manage");
            }
        }

        if (array_key_exists("d.CategoryID", $where)) {
            $includeChildCategories = $query["includeChildCategories"] ?? false;
            if ($includeChildCategories) {
                $where["d.CategoryID"] = $this->getNestedCategoriesIDs($where["d.CategoryID"], $followed);
            } else {
                $this->discussionModel->categoryPermission("Vanilla.Discussions.View", $where["d.CategoryID"]);
            }
        } elseif ($siteSectionID) {
            $siteSection = $this->siteSectionModel->getByID($query["siteSectionID"]);
            $categoryID = $siteSection ? $siteSection->getCategoryID() : null;
            if ($categoryID) {
                $where["d.CategoryID"] = $this->getNestedCategoriesIDs($categoryID, $followed);
            }
        }

        // Do we exclude hidden categories?
        if ($excludeHiddenCategories) {
            $categoriesShowingDiscussions = CategoryModel::instance()
                ->getWhere(["HideAllDiscussions" => 0])
                ->column("CategoryID");
            if (count($categoriesShowingDiscussions) > 0) {
                if (array_key_exists("d.CategoryID", $where)) {
                    // If we already have a subset of categories, filter them out.
                    $where["d.CategoryID"] = array_intersect($where["d.CategoryID"], $categoriesShowingDiscussions);
                } else {
                    // Otherwise, ensure the discussions are from categories that have `HideAllDiscussions` set to 0.
                    $where["d.CategoryID"] = $categoriesShowingDiscussions;
                }
            }
        }

        /*pull all discussion Ids based on the given Tagid/Id's and pass it on*/
        if (array_key_exists("tagID", $query)) {
            $cond = ["TagID" => $query["tagID"]];
            $discussionIDs = $this->tagModel->getTagDiscussionIDs($cond);
            if (!empty($discussionIDs)) {
                $where["d.DiscussionID"] = array_column($discussionIDs, "DiscussionID");
            }
        }

        // Allow addons to update the where clause.
        $where = $this->getEventManager()->fireFilter(
            "discussionsApiController_indexFilters",
            $where,
            $this,
            $in,
            $query
        );

        if ($followed) {
            $where["Followed"] = true;
            $query["pinOrder"] = "mixed";
        }

        $joinDirtyRecords = $query[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;
        if ($joinDirtyRecords) {
            $where[DirtyRecordModel::DIRTY_RECORD_OPT] = $joinDirtyRecords;
        }

        $count = null;
        // When using expand crawl (and crawling) we don't use numbered pagers.
        $shouldCount = !ModelUtils::isExpandOption("crawl", $query["expand"]);
        $pinned = $query["pinned"] ?? null;
        [$orderField, $orderDirection] = \Vanilla\Models\LegacyModelUtils::orderFieldDirection($query["sort"] ?? "");
        if ($bookmarkUserID) {
            $rows = $this->discussionModel
                ->getWhere($where, $orderField, $orderDirection, $limit, $offset, false, "bookmarked", $bookmarkUserID)
                ->resultArray();
            if ($shouldCount) {
                $count = $this->discussionModel->getPagingCount($where, $limit, "bookmarked", $bookmarkUserID);
            }
        } elseif ($participatedUserID) {
            $rows = $this->discussionModel
                ->getWhere(
                    $where,
                    $orderField,
                    $orderDirection,
                    $limit,
                    $offset,
                    false,
                    "participated",
                    $participatedUserID
                )
                ->resultArray();
            if ($shouldCount) {
                $count = $this->discussionModel->getPagingCount($where, $limit, "participated", $participatedUserID);
            }
        } elseif ($pinned === true) {
            $announceWhere = array_merge($where, ["d.Announce >" => "0"]);
            $rows = $this->discussionModel
                ->getAnnouncements($announceWhere, $offset, $limit, $query["sort"] ?? "")
                ->resultArray();
            if ($shouldCount) {
                $count = $this->discussionModel->getAnnouncementsPagingCount($where, $limit);
            }
        } else {
            $pinOrder = $query["pinOrder"] ?? null;
            if ($pinOrder == "first") {
                $announcements = $this->discussionModel
                    ->getAnnouncements($where, $offset, $limit, $query["sort"] ?? "")
                    ->resultArray();
                $discussions = $this->discussionModel
                    ->getWhere($where, $orderField, $orderDirection, $limit, $offset, false)
                    ->resultArray();
                $rows = array_merge($announcements, $discussions);
                if ($shouldCount) {
                    $count = $this->discussionModel->getAnnouncementsPagingCount($where, $limit);
                    $count += $this->discussionModel->getPagingCount($where, $limit);
                }
            } else {
                $where["Announce"] = "all";
                $rows = $this->discussionModel
                    ->getWhere($where, $orderField, $orderDirection, $limit, $offset, false)
                    ->resultArray();
                if ($shouldCount) {
                    $count = $this->discussionModel->getPagingCount($where, $limit);
                }
            }
        }

        // When crawling the endpoint use a more pager.
        $paging =
            $count == null
                ? ApiUtils::morePagerInfo($rows, "/api/v2/discussions", $query, $in)
                : ApiUtils::numberedPagerInfo($count, "/api/v2/discussions", $query, $in);
        $pagingObject = ["paging" => $paging];

        // Expand associated rows.
        $this->userModel->expandUsers(
            $rows,
            $this->resolveExpandFields($query, [
                "insertUser" => "InsertUserID",
                "lastUser" => "LastUserID",
                "lastPost.insertUser" => "LastUserID",
            ])
        );
        foreach ($rows as &$currentRow) {
            $currentRow = $this->normalizeOutput($currentRow, $query["expand"]);
        }
        $this->discussionExpandSchema->commonExpand($rows, $query["expand"] ?? []);
        $this->expandLastCommentBody($rows, $query["expand"]);

        $result = $out->validate($rows);
        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter(
            "discussionsApiController_getOutput",
            $result,
            $this,
            $in,
            $query,
            $rows
        );
        if ($this->isExpandField("tags", $query["expand"]) ?? false) {
            $this->tagModel->expandTags($result);
        }

        return new Data($result, $pagingObject);
    }

    // region Status Management

    /**
     * Delete a single status by its numeric ID.
     *
     * @param int $statusID
     * @return Data
     * @throws ClientException Attempting to delete a system defined status.
     * @throws ValidationException Row fails to validate against schema.
     * @throws NoResultsException Row to delete not found.
     * @throws \Garden\Web\Exception\HttpException Ban applied on permission(s) for session.
     * @throws PermissionException User does not have permission to delete discussion status.
     */
    public function delete_statuses(int $statusID): Data
    {
        $this->permission("Garden.Settings.Manage");

        $where = [
            "recordType" => "discussion",
            "statusID" => $statusID,
        ];
        $status = $this->recordStatusModel->selectSingle($where);
        $this->recordStatusModel->delete($where, [Model::OPT_LIMIT => 1]);

        $out = $this->schema($this->recordStatusModel->getSchema());
        $result = $out->validate($status);

        return new Data(null);
    }

    /**
     * Get a single status by its numeric ID.
     *
     * @param int $statusID
     * @return Data
     */
    public function get_statuses(int $statusID): Data
    {
        $this->permission();

        $in = $this->schema([]);
        $out = $this->schema($this->recordStatusModel->getSchema());

        $where = [
            "recordType" => "discussion",
            "statusID" => $statusID,
        ];
        $row = $this->recordStatusModel->selectSingle($where);

        $result = $out->validate($row);
        return new Data($result);
    }

    /**
     * Handles GET `/api/v2/discussions/statuses` API calls. Get all available statuses.
     *
     * @param array $query
     * @return Data
     */
    public function index_statuses(array $query = []): Data
    {
        $this->permission();

        $in = $this->schema(["state", "subType"])->add($this->recordStatusModel->getSchema());
        $out = $this->schema([":a" => $this->recordStatusModel->getSchema()]);

        $where = $in->validate($query, true) + ["recordType" => "discussion"];
        if (isset($where["subType"])) {
            // Mismatch between API field name and database column name
            $where["recordSubtype"] = $where["subType"];
            unset($where["subType"]);
        }

        // We only list statuses that are active (`isActive` = 1).
        $where["isActive"] = 1;

        $where = $this->getEventManager()->fireFilter("discussionsApiController_indexStatuses", $where);

        $rows = $this->recordStatusModel->select($where);

        $result = $out->validate($rows);
        return new Data($result);
    }

    /**
     * Update a single status by its numeric ID.
     *
     * @param int $statusID
     * @param array $body
     * @return Data
     * @throws ClientException Attempting to update a system defined status.
     * @throws ValidationException Row fails to validate against the schema.
     * @throws NoResultsException Status to update not found.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws PermissionException User does not have permission to update discussion status.
     */
    public function patch_statuses(int $statusID, array $body = []): Data
    {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema(
            Schema::parse(["isDefault", "name", "state", "recordSubtype", "isInternal"])->add(
                $this->recordStatusModel->getSchema()
            )
        );
        $out = $this->schema($this->recordStatusModel->getSchema());

        $body = $in->validate($body, true);

        $where = [
            "recordType" => "discussion",
            "statusID" => $statusID,
        ];
        $row = $this->recordStatusModel->selectSingle($where);
        $result = $out->validate($row);

        if (!empty($body)) {
            $this->recordStatusModel->update($body, $where, [Model::OPT_LIMIT => 1]);
            $row = $this->recordStatusModel->selectSingle($where);
            $result = $out->validate($row);
        }

        return new Data($result);
    }

    /**
     * Create a new status.
     *
     * @param array $body
     * @return Data
     * @throws ValidationException Error when validating post body against schema.
     * @throws ClientException Error during record insert.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws PermissionException User does not have permission to create discussion status.
     */
    public function post_statuses(array $body = []): Data
    {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema(
            Schema::parse(["name", "isDefault?", "state?", "recordSubtype?"])->add(
                $this->recordStatusModel->getSchema()
            )
        );
        $out = $this->schema($this->recordStatusModel->getSchema());

        $body = $in->validate($body) + ["recordType" => "discussion"];
        $statusID = intval($this->recordStatusModel->insert($body));
        $row = $this->recordStatusModel->selectSingle(["statusID" => $statusID]);

        $result = $out->validate($row);

        return new Data($result, 201);
    }

    // endregion

    /**
     * Update a discussion.
     *
     * @param int $id The ID of the discussion.
     * @param array $body The request body.
     * @param array $query The request query.
     * @return array
     * @throws ClientException If discussion editing is not allowed.
     * @throws NotFoundException If unable to find the discussion.
     */
    public function patch(int $id, array $body, array $query = [])
    {
        $this->permission("Garden.SignIn.Allow");

        $this->idParamSchema("in");
        $in = $this->discussionPatchSchema("in")->setDescription("Update a discussion.");
        $out = $this->schema($this->discussionSchema(), "out");

        $body = $in->validate($body, true);

        $row = $this->discussionByID($id);
        $canEdit = $this->discussionModel::canEdit($row);
        if (!$canEdit) {
            throw new ClientException("Editing discussions is not allowed.");
        }
        $discussionData = ApiUtils::convertInputKeys($body);
        $discussionData["DiscussionID"] = $id;
        $categoryID = $row["CategoryID"];
        if ($row["InsertUserID"] !== $this->getSession()->UserID) {
            $this->discussionModel->categoryPermission("Vanilla.Discussions.Edit", $categoryID);
        }
        if (array_key_exists("CategoryID", $discussionData) && $categoryID !== $discussionData["CategoryID"]) {
            $this->discussionModel->categoryPermission("Vanilla.Discussions.Add", $discussionData["CategoryID"]);
            $this->checkCategoryAllowsPosting($discussionData["CategoryID"]);
            $categoryID = $discussionData["CategoryID"];
        }

        $permissionCategoryID = self::getPermissionID($categoryID);

        $this->fieldPermission($body, "closed", "Vanilla.Discussions.Close", $permissionCategoryID);
        $this->fieldPermission($body, "pinned", "Vanilla.Discussions.Announce", $permissionCategoryID);
        $this->fieldPermission($body, "pinLocation", "Vanilla.Discussions.Announce", $permissionCategoryID);
        $this->fieldPermission($body, "sink", "Vanilla.Discussions.Sink", $permissionCategoryID);

        $saveResult = $this->discussionModel->save($discussionData);
        $this->validateModel($this->discussionModel);
        ModelUtils::validateSaveResultPremoderation($saveResult, "discussion");

        $result = $this->discussionByID($id);
        $result = $this->normalizeOutput($result);
        $this->discussionExpandSchema->commonExpand($result, $query["expand"] ?? []);
        return $out->validate($result);
    }

    /**
     * Get the category permission ID.
     *
     * @param int $categoryID The category ID.
     * @return int Returns the associated permission ID.
     */
    public static function getPermissionID(int $categoryID): int
    {
        $category = CategoryModel::categories($categoryID);
        if ($category) {
            return $category["PermissionCategoryID"];
        } else {
            return -1;
        }
    }

    /**
     * Add a discussion.
     *
     * @param array $body The request body.
     * @param array $query The request query.
     * @return Data
     * @throws NotFoundException If a category is not found.
     * @throws ServerException If the discussion could not be created.
     */
    public function post(array $body, array $query = []): Data
    {
        $this->permission("Garden.SignIn.Allow");

        $in = $this->discussionPostSchema("in")->setDescription("Add a discussion.");
        $out = $this->discussionSchema("out");

        $body = $in->validate($body);
        $categoryID = $body["categoryID"];
        $category = CategoryModel::categories($categoryID);
        if (!$category) {
            throw new NotFoundException("Category");
        }
        $this->checkCategoryAllowsPosting($category);

        $categoryPermissionID = self::getPermissionID($categoryID);
        $this->discussionModel->categoryPermission("Vanilla.Discussions.Add", $categoryID);
        $this->fieldPermission($body, "closed", "Vanilla.Discussions.Close", $categoryPermissionID);
        $this->fieldPermission($body, "pinned", "Vanilla.Discussions.Announce", $categoryPermissionID);
        $this->fieldPermission($body, "pinLocation", "Vanilla.Discussions.Announce", $categoryPermissionID);
        $this->fieldPermission($body, "sink", "Vanilla.Discussions.Sink", $categoryPermissionID);

        $discussionData = ApiUtils::convertInputKeys($body);
        $id = $this->discussionModel->save($discussionData);
        $this->validateModel($this->discussionModel);
        ModelUtils::validateSaveResultPremoderation($id, "discussion");

        if (!$id) {
            throw new ServerException("Unable to insert discussion.", 500);
        }

        $row = $this->discussionByID($id);
        $this->userModel->expandUsers($row, ["InsertUserID", "LastUserID"]);
        $row = $this->normalizeOutput($row);
        $this->discussionExpandSchema->commonExpand($row, $query["expand"] ?? []);
        $result = $out->validate($row);
        return new Data($result, ["status" => 201]);
    }

    /**
     * Bookmark a discussion.
     *
     * @param int $id The ID of the discussion.
     * @param array $body The request body.
     * @return array
     */
    public function put_bookmark(int $id, array $body)
    {
        $this->permission("Garden.SignIn.Allow");

        $this->idParamSchema("in");
        $in = $this->schema(
            ["bookmarked:b" => "Pass true to bookmark or false to remove bookmark."],
            "in"
        )->setDescription("Bookmark a discussion.");
        $out = $this->schema(["bookmarked:b" => "The current bookmark value."], "out");

        $body = $in->validate($body);
        $row = $this->discussionByID($id);
        $bookmarked = intval($body["bookmarked"]);
        $this->discussionModel->categoryPermission("Vanilla.Discussions.View", $row["CategoryID"]);
        $this->discussionModel->bookmark($id, $this->getSession()->UserID, $bookmarked);

        $result = $this->discussionByID($id);
        return $out->validate($result);
    }

    /**
     * Set canonical url for a discussion.
     *
     * @param int $id The ID of the discussion.
     * @param array $body The request body.
     *       Ex: ["canonicalUrl" => "https://mydomain.com/some+path/"]
     * @throws NotFoundException If unable to find the discussion.
     * @return array
     */
    public function put_canonicalUrl(int $id, array $body)
    {
        $this->permission("Garden.SignIn.Allow");

        $this->idParamSchema("in");
        $in = $this->discussionPutCanonicalSchema("in")->setDescription("Set canonical url for a discussion.");
        $out = $this->schema($this->discussionSchema(), "out");

        $body = $in->validate($body);

        $row = $this->discussionByID($id);
        $categoryID = $row["CategoryID"];
        if ($row["InsertUserID"] !== $this->getSession()->UserID) {
            $this->discussionModel->categoryPermission("Vanilla.Discussions.Edit", $categoryID);
        }

        $attributes = $row["Attributes"] ?? [];
        $attributes["CanonicalUrl"] = $body["canonicalUrl"];
        $this->discussionModel->setProperty($id, "Attributes", dbencode($attributes));

        $result = $this->discussionByID($id);
        $result = $this->normalizeOutput($result);
        return $out->validate($result);
    }

    /**
     * Remove canonical url for a discussion.
     *
     * @param int $id The ID of the discussion.
     * @throws NotFoundException If unable to find the discussion.
     * @return array
     */
    public function delete_canonicalUrl(int $id)
    {
        $this->permission("Garden.SignIn.Allow");

        $in = $this->schema(Schema::parse(["id:i" => "The discussion ID."]), "in");
        $out = $this->schema([], "out");

        $row = $this->discussionByID($id);
        $categoryID = $row["CategoryID"];
        if ($row["InsertUserID"] !== $this->getSession()->UserID) {
            $this->discussionModel->categoryPermission("Vanilla.Discussions.Edit", $categoryID);
        }
        $attributes = $row["Attributes"];
        if (!empty($attributes["CanonicalUrl"] ?? "")) {
            unset($attributes["CanonicalUrl"]);
            $this->discussionModel->setProperty($id, "Attributes", dbencode($attributes));
        }
    }

    /**
     * Search discussions.
     *
     * @param array $query
     * @return Data
     */
    public function get_search(array $query)
    {
        $this->permission();

        $in = $this->schema(
            [
                "categoryID:i?" => "The numeric ID of a category to limit search results to.",
                "followed:b?" =>
                    "Limit results to those in followed categories. Cannot be used with the categoryID parameter.",
            ],
            "in"
        )
            ->merge($this->searchSchema())
            ->setDescription("Search discussions.");
        $query = $in->validate($query);

        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);
        $searchQuery = [
            "recordTypes" => ["discussion"],
            "query" => $query["query"],
        ];

        if (array_key_exists("categoryID", $query)) {
            $searchQuery["categoryID"] = $query["categoryID"];
        } elseif ($this->getSession()->isValid() && !empty($query["followed"])) {
            $searchQuery["followedCategories"] = true;
        }

        $results = $this->getSearchService()->search($searchQuery, new SearchOptions($offset, $limit));
        $discussionIDs = [];
        /** @var SearchResultItem $result */
        foreach ($results as $result) {
            $discussionIDs[] = $result->getRecordID();
        }

        // Hit the discussion API back for formatting.
        return $this->index([
            "discussionID" => $discussionIDs,
            "expand" => $query["expand"] ?? null,
            "limit" => $query["limit"] ?? null,
        ]);
    }

    /**
     * PUT /discussions/:id/type
     *
     * Convert a discussions record type.
     *
     * @param int $id
     * @param array $body
     *
     * @return mixed
     * @throws ClientException When record not found.
     */
    public function put_type(int $id, array $body)
    {
        $this->permission("Vanilla.Discussions.Edit");

        $in = $this->schema(
            [
                "type:s" => "The type to convert the discussion to",
            ],
            "in"
        )->setDescription("Change a discussions type. ie. idea, question");
        $out = $this->schema($this->discussionSchema(), "out");
        $body = $in->validate($body);

        $from = $this->discussionModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$from) {
            throw new ClientException("Record not found.");
        }

        $fromType = strtolower($from["Type"]) ?? "";
        $toType = strtolower($body["type"]) ?? null;
        $isDiscussionType = empty($fromType);
        $noChange = $fromType === $toType || ($isDiscussionType && $toType === "discussion");

        if ($noChange) {
            $result = $this->normalizeOutput($from);
            return $out->validate($result);
        }

        $this->discussionTypeConverter->convert($from, $toType);
        $record = $this->discussionModel->getID($id, DATASET_TYPE_ARRAY);
        $result = $this->normalizeOutput($record);
        $result = $out->validate($result);
        $this->tagModel->expandTags($result);
        return $result;
    }

    /**
     * PUT /discussions/:id/status
     *
     * Update the status of the discussion.
     *
     * @param int $id discussionID
     * @param array $body body of the requerst containing statusID, and StatusNote
     *
     * @return Data
     * @throws ClientException When record not found.
     */
    public function put_status(int $id, array $body)
    {
        $this->permission("session.valid");

        $in = $this->schema([
            "statusID:i?" => "New Status ID of the discussion",
            "internalStatusID:i?" => "New Internal Status ID of the discussion",
            "statusNotes:s" => [
                "default" => "",
            ],
        ])
            ->requireOneOf(["statusID", "internalStatusID"])
            ->add(DiscussionExpandSchema::commonExpandSchema())
            ->add($this->fullSchema())
            ->setDescription("Change a status of the discussion.");

        $out = $this->schema($this->discussionSchema(), "out");
        $body = $in->validate($body);

        // Coalesce values for convenience.
        $statusID = $body["statusID"] ?? null;
        $statusNotes = $body["statusNotes"] ?? null;
        $row = $this->discussionStatusModel->updateDiscussionStatus($id, $statusID, $statusNotes);
        $row = $this->normalizeOutput($row);
        $this->discussionExpandSchema->commonExpand($row, $body["expand"] ?? []);
        $result = $out->validate($row);
        return new Data($result, ["status" => 201]);
    }

    /**
     * Expand the body of the last comment.
     *
     * @param array $rows
     * @param array|bool $expand
     */
    private function expandLastCommentBody(array &$rows, $expand): void
    {
        if (
            !$this->isExpandField("lastPost", $expand) ||
            !$this->isExpandField("lastPost.body", $expand) ||
            $this->isExpandField("-body", $expand)
        ) {
            return;
        }

        $commentIDs = [];
        foreach ($rows as $row) {
            $id = $row["lastPost"]["commentID"] ?? null;
            if (is_int($id)) {
                $commentIDs[] = $id;
            }
        }
        if (!empty($commentIDs)) {
            $comments = $this->commentModel
                ->getWhere(["commentID" => $commentIDs], "", "asc", count($commentIDs))
                ->resultArray();
            $comments = array_column($comments, null, "CommentID");
        } else {
            $comments = [];
        }

        foreach ($rows as &$row) {
            $id = $row["lastPost"]["commentID"] ?? null;
            if (isset($comments[$id])) {
                $row["lastPost"]["body"] = \Gdn::formatService()->renderHTML(
                    $comments[$id]["Body"],
                    $comments[$id]["Format"],
                    ["recordID" => $id, "recordType" => "comment"]
                );
            } elseif (isset($row["body"])) {
                $row["lastPost"]["body"] = $row["body"];
            }
        }
    }

    /**
     * Add tags to a discussion.
     *
     * @param int $id The discussion ID
     * @param array $body The tags to add.
     * @return Data
     * @throws Gdn_UserException User exception.
     * @throws NotFoundException Throws an exception if a tag isn't found.
     * @throws ValidationException Throws a validation exception.
     * @throws ClientException Throws an exception if you try to add a tag that's there already.
     * @throws \Garden\Web\Exception\HttpException Http Exception.
     * @throws PermissionException Permission Exception.
     */
    public function post_tags(int $id, array $body): Data
    {
        $this->permission("Vanilla.Tagging.Add");
        $this->canEditDiscussion($id);

        // Validate the body.
        $validatedBody = $this->tagModel->validateTagReference($body);

        // Get the tags.
        $tags = $this->tagModel->getTagsFromReferences($validatedBody);

        // Make sure each tag is of an allowed type and throw an error if not.
        $this->tagModel->checkAllowedDiscussionTagTypes($tags);

        // Add the tags to the discussion.
        $this->tagModel->addDiscussion($id, array_column($tags, "TagID"));

        // Get all the discussion tags.
        $discussionTags = $this->tagModel->getDiscussionTags($id, false);

        // Normalize and validate the tags to send back.
        $normalizedTags = $this->tagModel->normalizeOutput($discussionTags);
        $validatedTags = $this->tagModel->validateTagFragmentsOutput($normalizedTags);

        $result = new Data($validatedTags);
        return $result;
    }

    /**
     * Set the tags on a discussion.
     *
     * @param int $id The discussion ID.
     * @param array $body The tags to set.
     * @return Data
     * @throws PermissionException Permission Exception.
     * @throws NotFoundException Throws an exception if a tag isn't found.
     * @throws ValidationException Throws a validation exception.
     */
    public function put_tags(int $id, array $body): Data
    {
        $this->permission("Vanilla.Tagging.Add");
        $this->canEditDiscussion($id);

        // Validate the body.
        $validatedBody = $this->tagModel->validateTagReference($body);

        // Get the tags.
        $tags = $this->tagModel->getTagsFromReferences($validatedBody);

        // Make sure each tag is of an allowed type and throw an error if not.
        $this->tagModel->checkAllowedDiscussionTagTypes($tags);

        // Set the tags on the discussion.
        $this->tagModel->saveDiscussion($id, array_column($tags, "TagID"));

        // Get all discussion tags.
        $discussionTags = $this->tagModel->getDiscussionTags($id, false);

        // Normalize and validate the tags to send back.
        $normalizedTags = $this->tagModel->normalizeOutput($discussionTags);
        $validatedTags = $this->tagModel->validateTagFragmentsOutput($normalizedTags);

        $result = new Data($validatedTags);
        return $result;
    }

    /**
     * Check to make sure the user can edit a discussion.
     *
     * @param int $id The discussion ID to check permissions for.
     * @throws NotFoundException Throws an exception if the discussion can't be found.
     * @throws PermissionException Throws an exception if no permission.
     */
    public function canEditDiscussion(int $id): void
    {
        $row = $this->discussionByID($id);
        if ($row["InsertUserID"] !== $this->getSession()->UserID) {
            $this->discussionModel->categoryPermission("Vanilla.Discussions.Edit", $row["CategoryID"]);
        }
    }

    /**
     * Check to make sure the category is a discussion-type category. Throw an error if not.
     *
     * @param int|array $categoryOrCategoryID
     * @throws \Garden\Web\Exception\ForbiddenException Throws if the category is a non-discussion type.
     */
    public function checkCategoryAllowsPosting($categoryOrCategoryID): void
    {
        $category = is_numeric($categoryOrCategoryID)
            ? $this->categoryModel->getID($categoryOrCategoryID, DATASET_TYPE_ARRAY)
            : ArrayUtils::PascalCase($categoryOrCategoryID);
        $canPost = CategoryModel::doesCategoryAllowPosts($categoryOrCategoryID);
        if (!$canPost) {
            throw new \Garden\Web\Exception\ForbiddenException(
                sprintft(
                    "You are not allowed to post in categories with a display type of %s.",
                    t($category["DisplayAs"])
                )
            );
        }
    }

    /**
     * Get all nested categoryIDs.
     *
     * @param int|null $categoryID
     * @param bool $followed
     *
     * @return array
     */
    protected function getNestedCategoriesIDs(?int $categoryID, bool $followed): array
    {
        $categoryIDs = $this->categoryModel->getSearchCategoryIDs($categoryID, $followed, true);
        return $categoryIDs;
    }
}
