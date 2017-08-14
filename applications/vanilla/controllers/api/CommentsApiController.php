<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\Utility\CapitalCaseScheme;

/**
 * API Controller for the `/comments` resource.
 */
class CommentsApiController extends AbstractApiController {

    /** @var CapitalCaseScheme */
    private $caseScheme;

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
    public function __construct(CommentModel $commentModel, DiscussionModel $discussionModel, UserModel $userModel) {
        $this->commentModel = $commentModel;
        $this->discussionModel = $discussionModel;
        $this->userModel = $userModel;

        $this->caseScheme = new CapitalCaseScheme();
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
                Schema::parse(['body', 'format', 'discussionID'])->add($this->fullSchema()),
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
            'format:s' => 'The output format of the comment.',
            'dateInserted:dt' => 'When the comment was created.',
            'insertUserID:i' => 'The user that created the comment.',
            'insertUser?' => $this->getUserFragmentSchema(),
            'url:s?' => 'The full URL to the comment.'
        ]);
    }

    /**
     * Get a comment.
     *
     * @param int $id The ID of the comment.
     * @return array
     */
    public function get($id) {
        $this->permission();

        $in = $this->idParamSchema()->setDescription('Get a comment.');
        $out = $this->schema($this->commentSchema(), 'out');

        $comment = $this->commentByID($id);
        if ($comment['InsertUserID'] !== $this->getSession()->UserID) {
            $discussion = $this->discussionByID($comment['DiscussionID']);
            $this->discussionModel->categoryPermission('Vanilla.Discussions.View', $discussion['CategoryID']);
        }

        $this->prepareRow($comment);
        $this->userModel->expandUsers($comment, ['InsertUserID']);
        $result = $out->validate($comment);
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
        $out = $this->schema(Schema::parse(['commentID', 'discussionID', 'body', 'format'])->add($this->fullSchema()), 'out');

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
     * @return mixed
     */
    public function index(array $query) {
        $this->permission();

        $in = $this->schema([
            'discussionID:i?' => 'The discussion ID.',
            'page:i?' => [
                'description' => 'Page number.',
                'default' => 1,
                'minimum' => 1,
                'maximum' => $this->discussionModel->getMaxPages()
            ],
            'limit:i?' => [
                'description' => 'The number of items per page.',
                'default' => $this->commentModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 100
            ],
            'insertUserID:i?' => 'Filter by author.',
            'after:dt?' => 'Limit to comments after this date.',
            'expand:b?' => [
                'description' => 'Expand associated records.',
                'default' => false
            ]
        ], 'in')->requireOneOf(['discussionID', 'insertUserID'])->setDescription('List comments.');
        $out = $this->schema([':a' => $this->commentSchema()], 'out');

        $query = $in->validate($query);

        $after = isset($query['after']) ? $query['after'] : null;
        if ($after instanceof DateTimeImmutable) {
            $after = $after->format(DateTime::ATOM);
        }

        // Lookup by discussion or by user?
        if (array_key_exists('discussionID', $query)) {
            $discussion = $this->discussionByID($query['discussionID']);
            list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);
            $this->discussionModel->categoryPermission('Vanilla.Discussions.View', $discussion['CategoryID']);

            // Build up the where clause.
            $where = ['DiscussionID' => $query['discussionID'], 'joinUsers' => false];

            if (isset($query['insertUserID'])) {
                $where['InsertUserID'] = $query['insertUserID'];
            }

            if ($after !== null) {
                $where['DateInserted >'] = $after;
            }

            $rows = $this->commentModel->getWhere(
                $where,
                'DateInserted',
                'asc',
                $limit,
                $offset
            )->resultArray();
        } else {
            $rows = $this->commentModel->getByUser2(
                $query['insertUserID'],
                $query['limit'],
                $query['offset'],
                false,
                $after,
                'asc'
            )->resultArray();
        }

        if ($query['expand']) {
            $this->userModel->expandUsers($rows, ['InsertUserID']);
        }
        foreach ($rows as &$currentRow) {
            $this->prepareRow($currentRow);
        }

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Prepare data for output.
     *
     * @param array $row
     */
    public function prepareRow(array &$row) {
        $this->formatField($row, 'Body', $row['Format']);
        $row['Url'] = commentUrl($row);

        if (!is_array($row['Attributes'])) {
            $attributes = dbdecode($row['Attributes']);
            $row['Attributes'] = is_array($attributes) ? $attributes : [];
        }
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

        $in = $this->commentPostSchema('in')->setDescription('Update a comment.');
        $out = $this->commentSchema('out');

        $body = $in->validate($body, true);
        $commentData = $this->caseScheme->convertArrayKeys($body);
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
        $row = $this->commentByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID']);
        $this->prepareRow($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Add a comment.
     *
     * @param array $body The request body.
     * @throws ServerException if the comment could not be created.
     * @return Data
     */
    public function post(array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->commentPostSchema('in')->setDescription('Add a comment.');
        $out = $this->commentSchema('out');

        $body = $in->validate($body);
        $commentData = $this->caseScheme->convertArrayKeys($body);
        $discussion = $this->discussionByID($commentData['DiscussionID']);
        $this->discussionModel->categoryPermission('Vanilla.Comments.Add', $discussion['CategoryID']);
        $id = $this->commentModel->save($commentData);
        if (!$id) {
            throw new ServerException('Unable to insert comment.', 500);
        }
        $row = $this->commentByID($id);
        $this->prepareRow($row);
        $this->userModel->expandUsers($row, ['InsertUserID']);

        $result = $out->validate($row);
        return new Data($result, 201);
    }
}
