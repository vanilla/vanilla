<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\Utility\CapitalCaseScheme;

/**
 * API Controller for the `/discussions` resource.
 */
class DiscussionsApiController extends AbstractApiController {

    /** @var CapitalCaseScheme */
    private $caseScheme;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var Schema */
    private $discussionSchema;

    /** @var Schema */
    private $discussionPostSchema;

    /** @var Schema */
    private $idParamSchema;

    /** @var UserModel */
    private $userModel;

    /**
     * DiscussionsApiController constructor.
     *
     * @param DiscussionModel $discussionModel
     * @param UserModel $userModel
     */
    public function __construct(DiscussionModel $discussionModel, UserModel $userModel) {
        $this->discussionModel = $discussionModel;
        $this->userModel = $userModel;

        $this->caseScheme = new CapitalCaseScheme();
    }

    /**
     * Delete a discussion.
     *
     * @param int $id The ID of the discussion.
     */
    public function delete($id) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamSchema()->setDescription('Delete a discussion.');
        $out = $this->schema([], 'out');

        $row = $this->discussionByID($id);
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->discussionModel->categoryPermission('Vanilla.Discussions.Delete', $row['CategoryID']);
        }
        $this->discussionModel->deleteID($id);
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
     * Get a discussion schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function discussionPostSchema($type = '') {
        if ($this->discussionPostSchema === null) {
            $this->discussionPostSchema = $this->schema(
                Schema::parse(['name', 'body', 'format', 'categoryID'])->add($this->fullSchema()),
                'DiscussionPost'
            );
        }
        return $this->schema($this->discussionPostSchema, $type);
    }

    /**
     * Get the full discussion schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function discussionSchema($type = '') {
        if ($this->discussionSchema === null) {
            $this->discussionSchema = $this->schema($this->fullSchema(), 'Discussion');
        }
        return $this->schema($this->discussionSchema, $type);
    }

    /**
     * Get a schema instance comprised of all available discussion fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullSchema() {
        return Schema::parse([
            'discussionID:i' => 'The ID of the discussion.',
            'name:s' => 'The title of the discussion.',
            'body:s' => 'The body of the discussion.',
            'categoryID:i' => 'The category the discussion is in.',
            'dateInserted:dt' => 'When the discussion was created.',
            'insertUserID:i' => 'The user that created the discussion.',
            'insertUser?' => $this->getUserFragmentSchema(),
            'bookmarked:b' => 'Whether or no the discussion is bookmarked by the current user.',
            'announce:b' => 'Whether or not the discussion has been announced (pinned).',
            'closed:b' => 'Whether the discussion is closed or open.',
            'sink:b' => 'Whether or not the discussion has been sunk.',
            'countComments:i' => 'The number of comments on the discussion.',
            'url:s?' => 'The full URL to the discussion.'
        ]);
    }

    /**
     * Get a discussion.
     *
     * @param int $id The ID of the discussion.
     * @throws NotFoundException if the discussion could not be found.
     * @return array
     */
    public function get($id) {
        $this->permission();

        $in = $this->idParamSchema()->setDescription('Get a discussion.');
        $out = $this->schema($this->discussionSchema(), 'out');

        $row = $this->discussionByID($id);
        if (!$row) {
            throw new NotFoundException('Discussion');
        }

        $this->discussionModel->categoryPermission('Vanilla.Discussions.View', $row['CategoryID']);

        $this->prepareRow($row);
        $this->userModel->expandUsers($row, ['InsertUserID']);

        $result = $out->validate($row);
        return $result;
    }

    public function prepareRow(&$row) {
        $row['Announce'] = (bool)$row['Announce'];
        $row['Bookmarked'] = (bool)$row['Bookmarked'];
        $row['Url'] = discussionUrl($row);
        $this->formatField($row, 'Body', $row['Format']);

        if (!is_array($row['Attributes'])) {
            $attributes = dbdecode($row['Attributes']);
            $row['Attributes'] = is_array($attributes) ? $attributes : [];
        }
    }

    /**
     * Get a discussion for editing.
     *
     * @param int $id The ID of the discussion.
     * @throws NotFoundException if the discussion could not be found.
     * @return array
     */
    public function get_edit($id) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamSchema()->setDescription('Get a discussion for editing.');
        $out = $this->schema(Schema::parse(['discussionID', 'name', 'body', 'format', 'categoryID'])->add($this->fullSchema()), 'out');

        $row = $this->discussionByID($id);
        $row['Url'] = discussionUrl($row);

        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->discussionModel->categoryPermission('Vanilla.Discussions.Edit', $row['CategoryID']);
        }

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only discussion record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema($type = 'in') {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse(['id:i' => 'The discussion ID.']),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * List discussions.
     *
     * @param array $query The query string.
     * @return array
     */
    public function index(array $query) {
        $this->permission();

        $in = $this->schema([
            'categoryID:i?' => 'Filter by a category.',
            'page:i?' => [
                'description' => 'Page number.',
                'default' => 1,
                'minimum' => 1,
                'maximum' => $this->discussionModel->getMaxPages()
            ],
            'limit:i?' => [
                'description' => 'The number of items per page.',
                'default' => $this->discussionModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 100
            ],
            'insertUserID:i?' => 'Filter by author.',
            'expand:b?' => 'Expand associated records.'
        ], 'in')->setDescription('List discussions.');
        $out = $this->schema([':a' => $this->discussionSchema()], 'out');

        $query = $this->filterValues($query);
        $query = $in->validate($query);
        $where = array_intersect_key($query, array_flip(['categoryID', 'insertUserID']));
        $where['Announce'] = 'all';
        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        if (array_key_exists('categoryID', $where)) {
            $this->discussionModel->categoryPermission('Vanilla.Discussions.View', $where['categoryID']);
        }
        $rows = $this->discussionModel->getWhereRecent($where, $limit, $offset)->resultArray();
        if (!empty($query['expand'])) {
            $this->userModel->expandUsers($rows, ['InsertUserID']);
        }
        foreach ($rows as &$currentRow) {
            $this->prepareRow($currentRow);
        }

        $result = $out->validate($rows, true);
        return $result;
    }

    /**
     * Update a discussion.
     *
     * @param int $id The ID of the discussion.
     * @param array $body The request body.
     * @throws NotFoundException if unable to find the discussion.
     * @return array
     */
    public function patch($id, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->discussionPostSchema('in')->setDescription('Update a discussion.');
        $out = $this->schema($this->discussionSchema(), 'out');

        $body = $in->validate($body, true);

        $row = $this->discussionByID($id);
        $discussionData = $this->caseScheme->convertArrayKeys($body);
        $discussionData['DiscussionID'] = $id;
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->discussionModel->categoryPermission('Vanilla.Discussions.Edit', $row['CategoryID']);
        }
        if (array_key_exists('CategoryID', $discussionData) && $row['CategoryID'] !== $discussionData['CategoryID']) {
            $this->discussionModel->categoryPermission('Vanilla.Discussions.Add', $discussionData['CategoryID']);
        }

        $this->discussionModel->save($discussionData);

        $result = $this->discussionByID($id);
        $this->prepareRow($result);
        return $out->validate($result);
    }

    /**
     * Add a discussion.
     *
     * @param array $body The request body.
     * @throws ServerException if the discussion could not be created.
     * @return Data
     */
    public function post(array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema($this->discussionPostSchema(), 'in')->setDescription('Add a discussion.');
        $out = $this->schema($this->discussionSchema(), 'out');

        $body = $in->validate($body);
        $this->discussionModel->categoryPermission('Vanilla.Discussions.Add', $body['categoryID']);

        $discussionData = $this->caseScheme->convertArrayKeys($body);
        $id = $this->discussionModel->save($discussionData);

        if (!$id) {
            throw new ServerException('Unable to insert discussion.', 500);
        }

        $row = $this->discussionByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID']);
        $this->prepareRow($row);
        $result = $out->validate($row);
        return new Data($result, 201);
    }

    /**
     * Announce a discussion.
     *
     * @param int $id The ID of the discussion.
     * @param array $body The request body.
     * @throws NotFoundException if unable to find the discussion.
     * @return array
     */
    public function put_announce($id, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this
            ->schema(['announce:b' => 'Pass true to announce or false to unannounce.'], 'in')
            ->setDescription('Announce a discussion.');
        $out = $this->schema(['announce:b' => 'The current announce value.'], 'out');

        $row = $this->discussionByID($id);
        $this->discussionModel->categoryPermission('Vanilla.Discussions.Announce', $row['CategoryID']);

        $body = $in->validate($body);
        $announce = intval($body['announce']);
        $this->discussionModel->setField($row['DiscussionID'], 'Announce', $announce);

        $result = $this->discussionByID($id);
        return $out->validate($result);
    }

    /**
     * Bookmark a discussion.
     *
     * @param int $id The ID of the discussion.
     * @param array $body The request body.
     * @return array
     */
    public function put_bookmark($id, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this
            ->schema(['bookmarked:b' => 'Pass true to bookmark or false to remove bookmark.'], 'in')
            ->setDescription('Bookmark a discussion.');
        $out = $this->schema(['bookmarked:b' => 'The current bookmark value.'], 'out');

        $body = $in->validate($body);
        $row = $this->discussionByID($id);
        $bookmarked = intval($body['bookmarked']);
        $this->discussionModel->categoryPermission('Vanilla.Discussions.View', $row['CategoryID']);
        $this->discussionModel->bookmark($id, $this->getSession()->UserID, $bookmarked);

        $result = $this->discussionByID($id);
        return $out->validate($result);
    }

    /**
     * Close a discussion.
     *
     * @param int $id The ID of the discussion.
     * @param array $body The request body.
     * @return array
     */
    public function put_close($id, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this
            ->schema(['closed:b' => 'Pass true to close or false to open.'], 'in')->setDescription('Close a discussion.');
        $out = $this->schema(['closed:b' => 'The current close value.'], 'out');

        $row = $this->discussionByID($id);
        $this->discussionModel->categoryPermission('Vanilla.Discussions.Close', $row['CategoryID']);

        $body = $in->validate($body);
        $closed = intval($body['closed']);
        $this->discussionModel->setField($row['DiscussionID'], 'Closed', $closed);

        $result = $this->discussionByID($id);
        return $out->validate($result);
    }

    /**
     * Sink a discussion.
     *
     * @param int $id The ID of the discussion.
     * @param array $body The request body.
     * @return array
     */
    public function put_sink($id, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this
            ->schema(['sink:b' => 'Pass true to sink or false to unsink.'], 'in')->setDescription('Sink a discussion.');
        $out = $this->schema(['sink:b' => 'The current sink value.'], 'out');

        $row = $this->discussionByID($id);
        $this->discussionModel->categoryPermission('Vanilla.Discussions.Sink', $row['CategoryID']);

        $body = $in->validate($body);
        $sink = intval($body['sink']);
        $this->discussionModel->setField($row['DiscussionID'], 'Sink', $sink);

        $result = $this->discussionByID($id);
        $this->userModel->expandUsers($result, ['InsertUserID']);
        return $out->validate($result);
    }
}
