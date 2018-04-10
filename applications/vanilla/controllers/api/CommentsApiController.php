<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\DateFilterSchema;
use Vanilla\ApiUtils;

/**
 * API Controller for the `/comments` resource.
 */
class CommentsApiController extends AbstractApiController {

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
                    'format:s' => 'The input format of the comment.',
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
    public function delete($id) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamSchema()->setDescription('Delete a comment.');
        $out = $this->schema([], 'out');

        $comment = $this->commentByID($id);
        if ($comment['InsertUserID'] !== $this->getSession()->UserID) {
            $discussion = $this->discussionByID($comment['CommentID']);
            $this->discussionModel->categoryPermission('Vanilla.Comments.Delete', $discussion['CategoryID']);
        }
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
        return Schema::parse([
            'commentID:i' => 'The ID of the comment.',
            'discussionID:i' => 'The ID of the discussion.',
            'body:s' => 'The body of the comment.',
            'dateInserted:dt' => 'When the comment was created.',
            'dateUpdated:dt|n' => 'When the comment was last updated.',
            'insertUserID:i' => 'The user that created the comment.',
            'score:i|n' => 'Total points associated with this post.',
            'insertUser?' => $this->getUserFragmentSchema(),
            'url:s?' => 'The full URL to the comment.'
        ]);
    }

    /**
     * Get a comment.
     *
     * @param int $id The ID of the comment.
     * @param array $query The request query.
     * @return array
     */
    public function get($id, array $query) {
        $this->permission();

        $this->idParamSchema();
        $in = $this->schema([], ['CommentGet', 'in'])->setDescription('Get a comment.');
        $out = $this->schema($this->commentSchema(), 'out');

        $query = $in->validate($query);

        $comment = $this->commentByID($id);
        if ($comment['InsertUserID'] !== $this->getSession()->UserID) {
            $discussion = $this->discussionByID($comment['DiscussionID']);
            $this->discussionModel->categoryPermission('Vanilla.Discussions.View', $discussion['CategoryID']);
        }

        $this->userModel->expandUsers($comment, ['InsertUserID'], ['expand' => true]);
        $comment = $this->normalizeOutput($comment);
        $result = $out->validate($comment);

        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter('commentsApiController_getOutput', $result, $this, $in, $query, $comment);

        return $result;
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
            'format:s' => 'The input format of the comment.',
        ])->add($this->fullSchema()), 'out');

        $comment = $this->commentByID($id);
        $comment['Url'] = commentUrl($comment);
        if ($comment['InsertUserID'] !== $this->getSession()->UserID) {
            $discussion = $this->discussionByID($comment['DiscussionID']);
            $this->discussionModel->categoryPermission('Vanilla.Comments.Edit', $discussion['CategoryID']);
        }

        $result = $out->validate($comment);
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
                Schema::parse(['id:i' => 'The comment ID.']),
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
                    'processor' => function($name, $value) {
                        $discussion = $this->discussionByID($value);
                        $this->discussionModel->categoryPermission('Vanilla.Discussions.View', $discussion['CategoryID']);
                        return [$name => $value];
                    },
                ],
            ],
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
                'maximum' => $this->discussionModel->getMaxPages()
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => $this->commentModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 100
            ],
            'insertUserID:i?' => [
                'description' => 'Filter by author.',
                'x-filter' => [
                    'field' => 'InsertUserID',
                ],
            ],
            'expand?' => ApiUtils::getExpandDefinition(['insertUser'])
        ], ['CommentIndex', 'in'])->requireOneOf(['discussionID', 'insertUserID'])->setDescription('List comments.');
        $out = $this->schema([':a' => $this->commentSchema()], 'out');

        $query = $in->validate($query);
        $where = ApiUtils::queryToFilters($in, $query);

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        $rows = $this->commentModel->lookup($where, true, $limit, $offset, 'asc')->resultArray();
        $hasMore = $this->commentModel->LastCommentCount >= $limit;

        // Expand associated rows.
        $this->userModel->expandUsers(
            $rows,
            $this->resolveExpandFields($query, ['insertUser' => 'InsertUserID']),
            ['expand' => $query['expand']]
        );

        foreach ($rows as &$currentRow) {
            $currentRow = $this->normalizeOutput($currentRow);
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
     * @return array Return a Schema record.
     */
    public function normalizeOutput(array $dbRecord) {
        $this->formatField($dbRecord, 'Body', $dbRecord['Format']);
        $dbRecord['Url'] = commentUrl($dbRecord);

        if (!is_array($dbRecord['Attributes'])) {
            $attributes = dbdecode($dbRecord['Attributes']);
            $dbRecord['Attributes'] = is_array($attributes) ? $attributes : [];
        }

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);

        // Allow addons to hook into the normalization process.
        $options = [];
        $result = $this->getEventManager()->fireFilter('commentsApiController_normalizeOutput', $schemaRecord, $this, $options);

        return $result;
    }

    /**
     * Update a comment.
     *
     * @param int $id The ID of the comment.
     * @param array $body The request body.
     * @return array
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
        $this->commentModel->save($commentData);
        $this->validateModel($this->commentModel);
        $row = $this->commentByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID'], ['expand' => true]);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Add a comment.
     *
     * @param array $body The request body.
     * @throws ServerException if the comment could not be created.
     * @return array
     */
    public function post(array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->commentPostSchema('in')->setDescription('Add a comment.');
        $out = $this->commentSchema('out');

        $body = $in->validate($body);
        $commentData = ApiUtils::convertInputKeys($body);
        $discussion = $this->discussionByID($commentData['DiscussionID']);
        $this->discussionModel->categoryPermission('Vanilla.Comments.Add', $discussion['CategoryID']);
        $id = $this->commentModel->save($commentData);
        $this->validateModel($this->commentModel);
        if (!$id) {
            throw new ServerException('Unable to insert comment.', 500);
        }
        $row = $this->commentByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID'], ['expand' => true]);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }
}
