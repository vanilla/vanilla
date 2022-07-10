<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\DateFilterSchema;
use Vanilla\ApiUtils;
use Vanilla\Exception\PermissionException;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Search\SearchOptions;
use Vanilla\Search\SearchResultItem;
use Vanilla\Search\SearchService;
use Garden\Web\Exception\ClientException;
use Vanilla\Utility\ModelUtils;

/**
 * API Controller for the `/comments` resource.
 */
class CommentsApiController extends AbstractApiController {

    use CommunitySearchSchemaTrait;
    use \Vanilla\Formatting\FormatCompatTrait;

    /** @var CommentModel */
    private $commentModel;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var Schema */
    private $commentSchema;

    /** @var Schema */
    private $commentPostSchema;

    /** @var Schema */
    private $idParamSchema;

    /** @var UserModel */
    private $userModel;

    /**
     * CommentsApiController constructor.
     *
     * @param CommentModel $commentModel
     * @param DiscussionModel $discussionModel
     * @param UserModel $userModel
     */
    public function __construct(
        CommentModel $commentModel,
        DiscussionModel $discussionModel,
        UserModel $userModel
    ) {
        $this->commentModel = $commentModel;
        $this->discussionModel = $discussionModel;
        $this->userModel = $userModel;
    }

    /**
     * Get a comment by its numeric ID.
     *
     * @param int $id The comment ID.
     * @throws NotFoundException if the comment could not be found.
     * @return array
     */
    public function commentByID($id) {
        $row = $this->commentModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Comment');
        }
        return $row;
    }

    /**
     * Get a comment schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function commentPostSchema($type = '') {
        if ($this->commentPostSchema === null) {
            $this->commentPostSchema = $this->schema(
                Schema::parse([
                    'body',
                    'format' => new \Vanilla\Models\FormatSchema(),
                    'discussionID'
                ])->add($this->fullSchema()),
                'CommentPost'
            );
        }
        return $this->schema($this->commentPostSchema, $type);
    }

    /**
     * Get the full comment schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function commentSchema($type = '') {
        if ($this->commentSchema === null) {
            $this->commentSchema = $this->schema($this->fullSchema(), 'Comment');
        }
        return $this->schema($this->commentSchema, $type);
    }

    /**
     * Delete a comment.
     *
     * @param int $id The ID of the comment.
     */
    public function delete(int $id) {
        $this->permission('Garden.SignIn.Allow');

        // Throws if the user can't delete.
        $this->commentModel->checkCanDelete($id);

        $this->commentModel->deleteID($id);
    }

    /**
     * Get a discussion by its numeric ID.
     *
     * @param int $id The discussion ID.
     * @throws NotFoundException if the discussion could not be found.
     * @return array
     */
    public function discussionByID($id) {
        $row = $this->discussionModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Discussion');
        }
        return $row;
    }

    /**
     * Get a schema instance comprised of all available comment fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullSchema() {
        $result = $this->commentModel->schema();
        return $result;
    }

    /**
     * Get a comment.
     *
     * @param int $id The ID of the comment.
     * @param array $query The request query.
     * @return array
     */
    public function get(int $id, array $query) {
        $query['id'] = $id;
        $this->permission();

        $in = $this->schema($this->idParamSchema(), ['CommentGet', 'in'])->setDescription('Get a comment.');

        $query = $in->validate($query);
        $commentSchema = CrawlableRecordSchema::applyExpandedSchema($this->commentSchema(), 'comment', $query['expand']);
        $out = $this->schema($commentSchema, 'out');

        $comment = $this->commentByID($id);
        if (isset($comment['DiscussionID'])) {
            $this->getEventManager()->fireFilter('commentsApiController_getFilters', $this, $comment['DiscussionID'], $query);
        }
        if ($comment['InsertUserID'] !== $this->getSession()->UserID) {
            $discussion = $this->discussionByID($comment['DiscussionID']);
            $this->discussionModel->categoryPermission('Vanilla.Discussions.View', $discussion['CategoryID']);
        }

        $this->userModel->expandUsers($comment, $this->resolveExpandFields($query, ['insertUser' => 'InsertUserID']));
        $comment = $this->normalizeOutput($comment, $query['expand']);
        $result = $out->validate($comment);

        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter('commentsApiController_getOutput', $result, $this, $in, $query, $comment);

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
     * @throws \Garden\Schema\ValidationException If the output schema is configured incorrectly.
     */
    public function get_quote($id) {
        $this->permission();

        $this->idParamSchema();
        $in = $this->schema([], 'in')->setDescription('Get a comments embed data.');
        $out = $this->schema($this->quoteSchema(), 'out');

        $comment = $this->commentByID($id);
        $this->getEventManager()->fireFilter('commentsApiController_getFilters', $this, $comment['DiscussionID'], []);

        if ($comment['InsertUserID'] !== $this->getSession()->UserID) {
            $discussion = $this->discussionByID($comment['DiscussionID']);
            $this->discussionModel->categoryPermission('Vanilla.Discussions.View', $discussion['CategoryID']);
        }

        $comment['Url'] = commentUrl($comment);
        $isRich = strcasecmp($comment['Format'], RichFormat::FORMAT_KEY) === 0;
        $comment['bodyRaw'] = $isRich ? json_decode($comment['Body'], true) : $comment['Body'];

        $this->userModel->expandUsers($comment, ['InsertUserID']);
        $result = $out->validate($comment);
        return $result;
    }

    /**
     * Get the schema for comment quote data.
     *
     * @return Schema
     */
    private function quoteSchema(): Schema {
        return Schema::parse([
            'commentID:i' => 'The ID of the comment.',
            'bodyRaw:s|a' => 'The raw body of the comment. This can be an array of rich operations or a string for other formats',
            'dateInserted:dt' => 'When the comment was created.',
            'dateUpdated:dt|n' => 'When the comment was last updated.',
            'insertUser' => $this->getUserFragmentSchema(),
            'url:s' => 'The full URL to the comment.',
            'format' => new \Vanilla\Models\FormatSchema(true),
        ]);
    }

    /**
     * Get a comment for editing.
     *
     * @param int $id The ID of the comment.
     * @return array
     */
    public function get_edit($id) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamSchema()->setDescription('Get a comment for editing.');
        $out = $this->schema(Schema::parse([
            'commentID',
            'discussionID',
            'body',
            'format' => new \Vanilla\Models\FormatSchema(true),
        ])
            ->add($this->fullSchema()), 'out')
            ->addFilter('', [\Vanilla\Formatting\Formats\RichFormat::class, 'editBodyFilter']);

        $comment = $this->commentByID($id);
        $comment['Url'] = commentUrl($comment);
        $this->getEventManager()->fireFilter('commentsApiController_getFilters', $this, $comment['DiscussionID'], []);

        if ($comment['InsertUserID'] !== $this->getSession()->UserID) {
            $discussion = $this->discussionByID($comment['DiscussionID']);
            $this->discussionModel->categoryPermission('Vanilla.Comments.Edit', $discussion['CategoryID']);
        }

        $result = $out->validate($comment);
        $this->applyFormatCompatibility($result, 'body', 'format');
        return $result;
    }

    /**
     * Get an ID-only comment record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema($type = 'in') {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse([
                    'id:i' => 'The comment ID.',
                    'expand' => ApiUtils::getExpandDefinition(['-insertUser']),
                ]),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * List comments.
     *
     * @param array $query The query string.
     * @return Data
     */
    public function index(array $query) {
        $this->permission();

        $in = $this->schema([
            'commentID?' => \Vanilla\Schema\RangeExpression::createSchema([':int'])->setField('x-filter', ['field' => 'CommentID']),
            'dateInserted?' => new DateFilterSchema([
                'description' => 'When the comment was created.',
                'x-filter' => [
                    'field' => 'c.DateInserted',
                    'processor' => [DateFilterSchema::class, 'dateFilterField'],
                ],
            ]),
            'dateUpdated?' => new DateFilterSchema([
                'description' => 'When the comment was updated.',
                'x-filter' => [
                    'field' => 'c.DateUpdated',
                    'processor' => [DateFilterSchema::class, 'dateFilterField'],
                ],
            ]),
            'discussionID:i?' => [
                'description' => 'The discussion ID.',
                'x-filter' => [
                    'field' => 'DiscussionID',
                    'processor' => function ($name, $value) {
                        $discussion = $this->discussionByID($value);
                        $this->discussionModel->categoryPermission('Vanilla.Discussions.View', $discussion['CategoryID']);
                        return [$name => $value];
                    },
                ],
            ],
            'dirtyRecords:b?',
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => $this->commentModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => ApiUtils::getMaxLimit(),
            ],
            'sort:s?' => [
                'enum' => ApiUtils::sortEnum('dateInserted', 'commentID', 'dateUpdated'),
                'default' => 'dateInserted',
            ],
            'insertUserID:i?' => [
                'description' => 'Filter by author.',
                'x-filter' => [
                    'field' => 'InsertUserID',
                ],
            ],
            'expand?' => ApiUtils::getExpandDefinition(['insertUser', '-body'])
        ], ['CommentIndex', 'in'])->requireOneOf(['commentID', 'discussionID', 'insertUserID'])->setDescription('List comments.');

        $query = $in->validate($query);
        $commentSchema = CrawlableRecordSchema::applyExpandedSchema($this->commentSchema(), 'comment', $query['expand']);
        $out = $this->schema([':a' => $commentSchema], 'out');

        if (isset($query['discussionID'])) {
            $this->getEventManager()->fireFilter('commentsApiController_getFilters', $this, $query['discussionID'], $query);
        }

        $where = ApiUtils::queryToFilters($in, $query);

        $joinDirtyRecords = $query[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;
        if ($joinDirtyRecords) {
            $where[DirtyRecordModel::DIRTY_RECORD_OPT] = $joinDirtyRecords;
        }

        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);

        [$orderField, $orderDirection] = \Vanilla\Models\LegacyModelUtils::orderFieldDirection($query['sort']);
        $rows = $this->commentModel->lookup($where, true, $limit, $offset, $orderDirection, $orderField)->resultArray();
        $hasMore = $this->commentModel->LastCommentCount >= $limit;

        // Expand associated rows.
        $this->userModel->expandUsers(
            $rows,
            $this->resolveExpandFields($query, ['insertUser' => 'InsertUserID'])
        );

        foreach ($rows as &$currentRow) {
            $currentRow = $this->normalizeOutput($currentRow, $query['expand']);
        }

        $result = $out->validate($rows);

        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter('commentsApiController_indexOutput', $result, $this, $in, $query, $rows);

        if (isset($where['DiscussionID']) && count($where) === 1) {
            $discussion = $this->discussionByID($where['DiscussionID']);
            $paging = ApiUtils::numberedPagerInfo($discussion['CountComments'], '/api/v2/comments', $query, $in);
        } else {
            $paging = ApiUtils::morePagerInfo($hasMore, '/api/v2/comments', $query, $in);
        }

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @param array|string|bool $expand
     *
     * @return array Return a Schema record.
     */
    public function normalizeOutput(array $dbRecord, $expand = []) {
        $normalizedRow = $this->commentModel->normalizeRow($dbRecord, $expand);
        $normalizedRow['type'] = 'comment';
        $normalizedRow['recordID'] = 'commentID';
        // Allow addons to hook into the normalization process.
        $options = [
            'expand' => $expand,
        ];
        $result = $this->getEventManager()->fireFilter(
            'commentsApiController_normalizeOutput',
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
     */
    public function patch($id, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamSchema('in');
        $in = $this->commentPostSchema('in')->setDescription('Update a comment.');
        $out = $this->commentSchema('out');

        $body = $in->validate($body, true);
        $commentData = ApiUtils::convertInputKeys($body);
        $commentData['CommentID'] = $id;
        $row = $this->commentByID($id);
        $canEdit = CommentModel::canEdit($row);
        if (!$canEdit) {
            throw new ClientException('Editing comments is not allowed.');
        }
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $discussion = $this->discussionByID($row['DiscussionID']);
            $this->discussionModel->categoryPermission('Vanilla.Comments.Edit', $discussion['CategoryID']);
        }
        if (array_key_exists('DiscussionID', $commentData) && $row['DiscussionID'] !== $commentData['DiscussionID']) {
            $discussion = $this->discussionByID($commentData['DiscussionID']);
            $this->discussionModel->categoryPermission('Vanilla.Comments.Add', $discussion['CategoryID']);
        }
        // Body is a required field in CommentModel::save.
        if (!array_key_exists('Body', $commentData)) {
            $commentData['Body'] = $row['Body'];
        }
        $saveResult = $this->commentModel->save($commentData);
        $this->validateModel($this->commentModel);
        ModelUtils::validateSaveResultPremoderation($saveResult, 'comment');

        $row = $this->commentByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID']);
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
    public function post(array $body) {
        $this->permission('Garden.SignIn.Allow');
        $in = $this->commentPostSchema('in')->setDescription('Add a comment.');
        $out = $this->commentSchema('out');

        $body = $in->validate($body);
        $commentData = ApiUtils::convertInputKeys($body);
        $discussion = $this->discussionByID($commentData['DiscussionID']);
        $this->discussionModel->categoryPermission('Vanilla.Comments.Add', $discussion['CategoryID']);
        $session = $this->getSession();
        $sessionUser = $session->UserID;
        $isAdmin = $session->checkRankedPermission('Garden.Moderation.Manage');
        $canView = $this->discussionModel->canView($discussion, $sessionUser);
        if (!$canView && !$isAdmin) {
            throw permissionException('Vanilla.Discussions.View');
        }
        // Only users with 'Moderation.Manage' perms should be able to add a comment to a closed discussion.
        if ($discussion['Closed'] && !$isAdmin) {
            throw new Gdn_UserException(t('This discussion has been closed.'));
        }
        $id = $this->commentModel->save($commentData);
        $this->validateModel($this->commentModel);
        ModelUtils::validateSaveResultPremoderation($id, 'comment');
        if (!$id) {
            throw new ServerException('Unable to insert comment.', 500);
        }
        $this->commentModel->save2($id, true);
        $row = $this->commentByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID']);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Search comments.
     *
     * @param array $query
     * @return Data
     */
    public function get_search(array $query) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this
            ->schema([
                'categoryID:i?' => 'The numeric ID of a category.',
            ], 'in')
            ->merge($this->searchSchema())
            ->setDescription('Search comments.');
        $query = $in->validate($query);

        $searchQuery = [
            'recordTypes' => ['comment'],
        ];
        if (array_key_exists('categoryID', $query)) {
            $searchQuery['categoryID'] = $query['categoryID'];
        }
        [$offset, $limit] = offsetLimit(
            "p{$query['page']}",
            $query['limit']
        );

        $results = $this->getSearchService()->search($searchQuery, new SearchOptions($offset, $limit));
        $commentIDs = [];
        /** @var SearchResultItem $result */
        foreach ($results as $result) {
            $commentIDs[] = $result->getRecordID();
        }

        // Hit the comments API back for formatting.
        return $this->index([
            'commentID' => $commentIDs,
            'expand' => $query['expand'] ?? null,
            'limit' => $query['limit'] ?? null,
        ]);
    }
}
