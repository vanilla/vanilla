<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
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
     * Get a list of the current user's bookmarked discussions.
     *
     * @param array $query The request query.
     * @return array
     */
    public function get_bookmarked(array $query) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
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
            'expand:b?' => 'Expand associated records.'
        ], 'in');
        $out = $this->schema([':a' => $this->discussionSchema()], 'out');

        $query = $in->validate($query);
        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        $rows = $this->discussionModel->get($offset, $limit, [
            'w.Bookmarked' => 1,
            'w.UserID' => $this->getSession()->UserID
        ])->resultArray();
        if (!empty($query['expand'])) {
            $this->userModel->expandUsers($rows, ['InsertUserID']);
        }
        foreach ($rows as &$currentRow) {
            $this->prepareRow($currentRow);
        }

        $result = $out->validate($rows);
        return $result;
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
                Schema::parse(
                    ['name', 'body', 'format', 'categoryID', 'closed?', 'sink?', 'pinned?', 'pinLocation?'])->add($this->fullSchema()),
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
            'pinned:b?' => 'Whether or not the discussion has been pinned.',
            'pinLocation:s|n' => [
                'enum' => ['category', 'recent'],
                'description' => 'The location for the discussion, if pinned. "category" are pinned to their own category. "recent" are pinned to the recent discussions list, as well as their own category.'
            ],
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
        $out = $this->schema(Schema::parse(['discussionID', 'name', 'body', 'format', 'categoryID', 'sink', 'closed', 'pinned', 'pinLocation'])->add($this->fullSchema()), 'out');

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
            'pinned:b?' => 'Whether or not to include pinned discussions. If true, only return pinned discussions. Cannot be used with the pinOrder parameter.',
            'pinOrder:s?' => [
                'default' => 'first',
                'description' => 'If including pinned posts, in what order should they be integrated? When "first", discussions pinned to a specific category will only be affected if the discussion\'s category is passed as the categoryID parameter. Cannot be used with the pinned parameter.',
                'enum' => ['first', 'mixed'],
            ],
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
        if (array_key_exists('categoryID', $where)) {
            $where['d.CategoryID'] = $where['categoryID'];
        }

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        if (array_key_exists('categoryID', $where)) {
            $this->discussionModel->categoryPermission('Vanilla.Discussions.View', $where['categoryID']);
        }

        $pinned = array_key_exists('pinned', $query) ? $query['pinned'] : null;
        if ($pinned === true) {
            $announceWhere = array_merge($where, ['d.Announce >' => '0']);
            $rows = $this->discussionModel->getAnnouncements($announceWhere, $offset, $limit)->resultArray();
        } elseif ($pinned === false) {
            $rows = $this->discussionModel->getWhereRecent($where, $limit, $offset, false)->resultArray();
        } else {
            $pinOrder = array_key_exists('pinOrder', $query) ? $query['pinOrder'] : null;
            if ($pinOrder == 'first') {
                $announcements = $this->discussionModel->getAnnouncements($where, $offset, $limit)->resultArray();
                $discussions = $this->discussionModel->getWhereRecent($where, $limit, $offset, false)->resultArray();
                $rows = array_merge($announcements, $discussions);
            } else {
                $where['Announce'] = 'all';
                $rows = $this->discussionModel->getWhereRecent($where, $limit, $offset, false)->resultArray();
            }
        }

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

        $this->idParamSchema('in');
        $in = $this->discussionPostSchema('in')->setDescription('Update a discussion.');
        $out = $this->schema($this->discussionSchema(), 'out');

        $body = $in->validate($body, true);

        $row = $this->discussionByID($id);
        $discussionData = $this->caseScheme->convertArrayKeys($body);
        $discussionData['DiscussionID'] = $id;
        $categoryID = $row['CategoryID'];
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->discussionModel->categoryPermission('Vanilla.Discussions.Edit', $categoryID);
        }
        if (array_key_exists('CategoryID', $discussionData) && $categoryID !== $discussionData['CategoryID']) {
            $this->discussionModel->categoryPermission('Vanilla.Discussions.Add', $discussionData['CategoryID']);
            $categoryID = $discussionData['CategoryID'];
        }

        $this->fieldPermission($body, 'closed', 'Vanilla.Discussions.Close', $categoryID);
        $this->fieldPermission($body, 'pinned', 'Vanilla.Discussions.Announce', $categoryID);
        $this->fieldPermission($body, 'sink', 'Vanilla.Discussions.Sink', $categoryID);

        $this->discussionModel->save($discussionData);
        $this->validateModel($this->discussionModel);

        $result = $this->discussionByID($id);
        $this->prepareRow($result);
        return $out->validate($result);
    }

    /**
     * Add a discussion.
     *
     * @param array $body The request body.
     * @throws ServerException if the discussion could not be created.
     * @return array
     */
    public function post(array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema($this->discussionPostSchema(), 'in')->setDescription('Add a discussion.');
        $out = $this->schema($this->discussionSchema(), 'out');

        $body = $in->validate($body);
        $categoryID = $body['categoryID'];
        $this->discussionModel->categoryPermission('Vanilla.Discussions.Add', $categoryID);
        $this->fieldPermission($body, 'closed', 'Vanilla.Discussions.Close', $categoryID);
        $this->fieldPermission($body, 'pinned', 'Vanilla.Discussions.Announce', $categoryID);
        $this->fieldPermission($body, 'sink', 'Vanilla.Discussions.Sink', $categoryID);

        $discussionData = $this->caseScheme->convertArrayKeys($body);
        $id = $this->discussionModel->save($discussionData);
        $this->validateModel($this->discussionModel);

        if (!$id) {
            throw new ServerException('Unable to insert discussion.', 500);
        }

        $row = $this->discussionByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID']);
        $this->prepareRow($row);
        $result = $out->validate($row);
        return $result;
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

        $this->idParamSchema('in');
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
}
