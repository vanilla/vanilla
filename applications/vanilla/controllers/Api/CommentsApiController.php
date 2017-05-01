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
     * @return array
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

        return [];
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

        $this->massageRow($comment);
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
        $out = $this->schema(Schema::parse(['commentID', 'body', 'format', 'url'])->add($this->fullSchema()), 'out');

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
            'discussionID:i' => 'The discussion ID.',
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
            'after:dt?' => 'Get only comments after this date.',
            'expand:b?' => 'Expand associated records.'
        ], 'in')->setDescription('List comments.');
        $out = $this->schema([':a' => $this->commentSchema()], 'out');

        $query = $in->validate($query);
        $discussion = $this->discussionByID($query['discussionID']);
        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);
        $this->discussionModel->categoryPermission('Vanilla.Discussions.View', $discussion['CategoryID']);

        // Build up the where clause.
        $where = ['discussionID' => $query['discussionID'], 'joinUsers' => false];

        if (isset($query['after'])) {
            $where['dateInserted >'] = $query['after'];
        }

        $rows = $this->commentModel->getWhere(
            $where,
            'DateInserted',
            'asc',
            $limit,
            $offset
        )->resultArray();

        if ($query['expand']) {
            $this->userModel->expandUsers($rows, ['InsertUserID']);
        }
        foreach ($rows as &$currentRow) {
            $this->massageRow($currentRow);
        }

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Prepare data for output.
     *
     * @param array $row
     */
    public function massageRow(array &$row) {
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

        $body = $in->validate($body);
        $data = $this->caseScheme->convertArrayKeys($body);
        $data['CommentID'] = $id;
        $row = $this->commentByID($id);
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $discussion = $this->discussionByID($row['DiscussionID']);
            $this->discussionModel->categoryPermission('Vanilla.Comments.Edit', $discussion['CategoryID']);
        }
        if ($row['DiscussionID'] !== $data['DiscussionID']) {
            $discussion = $this->discussionByID($data['DiscussionID']);
            $this->discussionModel->categoryPermission('Vanilla.Comments.Add', $discussion['CategoryID']);
        }
        $this->commentModel->save($data);
        $row = $this->commentByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID']);
        $this->massageRow($row);

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
        $data = $this->caseScheme->convertArrayKeys($body);
        $discussion = $this->discussionByID($data['DiscussionID']);
        $this->discussionModel->categoryPermission('Vanilla.Comments.Add', $discussion['CategoryID']);
        $id = $this->commentModel->save($data);
        if (!$id) {
            throw new ServerException('Unable to insert comment.', 500);
        }
        $row = $this->commentByID($id);
        $this->massageRow($row);
        $this->userModel->expandUsers($row, ['InsertUserID']);

        $result = $out->validate($row);
        return new Data($result, 201);
    }
}
