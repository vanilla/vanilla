<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
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
 * API Controller for the `/discussions` resource.
 */
class DiscussionsApiController extends AbstractApiController {

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
    public function __construct(
        DiscussionModel $discussionModel,
        UserModel $userModel
    ) {
        $this->discussionModel = $discussionModel;
        $this->userModel = $userModel;
    }

    /**
     * Get a list of the current user's bookmarked discussions.
     *
     * @param array $query The request query.
     * @return Data
     */
    public function get_bookmarked(array $query) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
                'maximum' => $this->discussionModel->getMaxPages()
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => $this->discussionModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 100
            ],
            'expand?' => ApiUtils::getExpandDefinition(['insertUser', 'lastUser', 'lastPost'])
        ], 'in')->setDescription('Get a list of the current user\'s bookmarked discussions.');
        $out = $this->schema([':a' => $this->discussionSchema()], 'out');

        $query = $in->validate($query);
        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        $rows = $this->discussionModel->get($offset, $limit, [
            'w.Bookmarked' => 1,
            'w.UserID' => $this->getSession()->UserID
        ])->resultArray();

        // Expand associated rows.
        $this->userModel->expandUsers(
            $rows,
            $this->resolveExpandFields($query, ['insertUser' => 'InsertUserID', 'lastUser' => 'LastUserID']),
            ['expand' => $query['expand']]
        );

        foreach ($rows as &$currentRow) {
            $currentRow = $this->normalizeOutput($currentRow);
        }

        $result = $out->validate($rows);

        $paging = ApiUtils::morePagerInfo($result, '/api/v2/discussions/bookmarked', $query, $in);

        return new Data($result, ['paging' => $paging]);
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
                Schema::parse([
                    'name',
                    'body',
                    'format:s' => 'The input format of the discussion.',
                    'categoryID',
                    'closed?',
                    'sink?',
                    'pinned?',
                    'pinLocation?',
                ])->add($this->fullSchema()),
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
            'type:s|n' => [
                //'enum' => [] // Let's find a way to fill that properly.
                'description' => 'The type of this discussion if any.',
            ],
            'name:s' => 'The title of the discussion.',
            'body:s' => 'The body of the discussion.',
            'categoryID:i' => 'The category the discussion is in.',
            'dateInserted:dt' => 'When the discussion was created.',
            'dateUpdated:dt|n' => 'When the discussion was last updated.',
            'insertUserID:i' => 'The user that created the discussion.',
            'insertUser?' => $this->getUserFragmentSchema(),
            'lastUser?' => $this->getUserFragmentSchema(),
            'pinned:b?' => 'Whether or not the discussion has been pinned.',
            'pinLocation:s|n' => [
                'enum' => ['category', 'recent'],
                'description' => 'The location for the discussion, if pinned. "category" are pinned to their own category. "recent" are pinned to the recent discussions list, as well as their own category.'
            ],
            'closed:b' => 'Whether the discussion is closed or open.',
            'sink:b' => 'Whether or not the discussion has been sunk.',
            'countComments:i' => 'The number of comments on the discussion.',
            'countViews:i' => 'The number of views on the discussion.',
            'score:i|n' => 'Total points associated with this post.',
            'url:s?' => 'The full URL to the discussion.',
            'lastPost?' => $this->getPostFragmentSchema(),
            'bookmarked:b' => 'Whether or not the discussion is bookmarked by the current user.',
            'unread:b' => 'Whether or not the discussion should have an unread indicator.',
            'countUnread:i?' => 'The number of unread comments.',
        ]);
    }

    /**
     * Get a discussion.
     *
     * @param int $id The ID of the discussion.
     * @param array $query The request query.
     * @throws NotFoundException if the discussion could not be found.
     * @return array
     */
    public function get($id, array $query) {
        $this->permission();

        $this->idParamSchema();
        $in = $this->schema([], ['DiscussionGet', 'in'])->setDescription('Get a discussion.');
        $out = $this->schema($this->discussionSchema(), 'out');

        $query = $in->validate($query);

        $row = $this->discussionByID($id);
        if (!$row) {
            throw new NotFoundException('Discussion');
        }

        $this->discussionModel->categoryPermission('Vanilla.Discussions.View', $row['CategoryID']);

        $this->userModel->expandUsers($row, ['InsertUserID', 'LastUserID'], ['expand' => true]);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);

        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter('discussionsApiController_getOutput', $result, $this, $in, $query, $row);
        return $result;
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @param array|bool $expand
     * @return array Return a Schema record.
     */
    public function normalizeOutput(array $dbRecord, $expand = []) {
        $dbRecord['Announce'] = (bool)$dbRecord['Announce'];
        $dbRecord['Bookmarked'] = (bool)$dbRecord['Bookmarked'];
        $dbRecord['Url'] = discussionUrl($dbRecord);
        $this->formatField($dbRecord, 'Body', $dbRecord['Format']);

        if (!is_array($dbRecord['Attributes'])) {
            $attributes = dbdecode($dbRecord['Attributes']);
            $dbRecord['Attributes'] = is_array($attributes) ? $attributes : [];
        }

        if ($this->getSession()->User) {
            $dbRecord['unread'] = $dbRecord['CountUnreadComments'] !== 0
                && ($dbRecord['CountUnreadComments'] !== true || dateCompare(val('DateFirstVisit', $this->getSession()->User), $dbRecord['DateInserted']) <= 0);
            if ($dbRecord['CountUnreadComments'] !== true && $dbRecord['CountUnreadComments'] > 0) {
                $dbRecord['countUnread'] = $dbRecord['CountUnreadComments'];
            }
        } else {
            $dbRecord['unread'] = false;
        }

        if ($this->isExpandField('lastPost', $expand)) {
            $lastPost = [
                'discussionID' => $dbRecord['DiscussionID'],
                'dateInserted' => $dbRecord['DateLastComment'],
                'insertUser' => $dbRecord['LastUser']
            ];
            if ($dbRecord['LastCommentID']) {
                $lastPost['CommentID'] = $dbRecord['LastCommentID'];
                $lastPost['name'] = sprintft('Re: %s', $dbRecord['Name']);
                $lastPost['url'] = commentUrl($lastPost, true);
            } else {
                $lastPost['name'] = $dbRecord['Name'];
                $lastPost['url'] = $dbRecord['Url'];
            }

            $dbRecord['lastPost'] = $lastPost;
        }

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        $schemaRecord['type'] = isset($schemaRecord['type']) ? lcfirst($schemaRecord['type']) : null;

        // Allow addons to hook into the normalization process.
        $options = ['expand' => $expand];
        $result = $this->getEventManager()->fireFilter('discussionsApiController_normalizeOutput', $schemaRecord, $this, $options);

        return $result;
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

        $this->idParamSchema()->setDescription('Get a discussion for editing.');
        $out = $this->schema(
            Schema::parse([
                'discussionID',
                'name',
                'body',
                'format:s' => 'The input format of the discussion.',
                'categoryID',
                'sink',
                'closed',
                'pinned',
                'pinLocation',
            ])->add($this->fullSchema()
        ), ['DiscussionGetEdit', 'out']);

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
     * @return Data
     */
    public function index(array $query) {
        $this->permission();

        $in = $this->schema([
            'categoryID:i?' => [
                'description' => 'Filter by a category.',
                'x-filter' => [
                    'field' => 'd.CategoryID'
                ],
            ],
            'dateInserted?' => new DateFilterSchema([
                'description' => 'When the discussion was created.',
                'x-filter' => [
                    'field' => 'd.DateInserted',
                    'processor' => [DateFilterSchema::class, 'dateFilterField'],
                ],
            ]),
            'dateUpdated?' => new DateFilterSchema([
                'description' => 'When the discussion was updated.',
                'x-filter' => [
                    'field' => 'd.DateUpdated',
                    'processor' => [DateFilterSchema::class, 'dateFilterField'],
                ],
            ]),
            'type:s?' => [
                'description' => 'Filter by discussion type.',
                'x-filter' => [
                    'field' => 'd.Type'
                ],
            ],
            'followed:b' => [
                'default' => false,
                'description' => 'Only fetch discussions from followed categories. Pinned discussions are mixed in.'
            ],
            'pinned:b?' => 'Whether or not to include pinned discussions. If true, only return pinned discussions. Cannot be used with the pinOrder parameter.',
            'pinOrder:s?' => [
                'default' => 'first',
                'description' => 'If including pinned posts, in what order should they be integrated? When "first", discussions pinned to a specific category will only be affected if the discussion\'s category is passed as the categoryID parameter. Cannot be used with the pinned parameter.',
                'enum' => ['first', 'mixed'],
            ],
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
                'maximum' => $this->discussionModel->getMaxPages()
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => $this->discussionModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 100
            ],
            'insertUserID:i?' => [
                'description' => 'Filter by author.',
                'x-filter' => [
                    'field' => 'd.InsertUserID',
                ],
            ],
            'expand?' => ApiUtils::getExpandDefinition(['insertUser', 'lastUser', 'lastPost'])
        ], ['DiscussionIndex', 'in'])->setDescription('List discussions.');
        $out = $this->schema([':a' => $this->discussionSchema()], 'out');

        $query = $this->filterValues($query);
        $query = $in->validate($query);

        $where = ApiUtils::queryToFilters($in, $query);

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        if (array_key_exists('categoryID', $where)) {
            $this->discussionModel->categoryPermission('Vanilla.Discussions.View', $where['categoryID']);
        }

        // Allow addons to update the where clause.
        $where = $this->getEventManager()->fireFilter('discussionsApiController_indexFilters', $where, $this, $in, $query);

        if ($query['followed']) {
            $where['Followed'] = true;
            $query['pinOrder'] = 'mixed';
        }

        $pinned = array_key_exists('pinned', $query) ? $query['pinned'] : null;
        if ($pinned === true) {
            $announceWhere = array_merge($where, ['d.Announce >' => '0']);
            $rows = $this->discussionModel->getAnnouncements($announceWhere, $offset, $limit)->resultArray();
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

        // Expand associated rows.
        $this->userModel->expandUsers(
            $rows,
            $this->resolveExpandFields($query, ['insertUser' => 'InsertUserID', 'lastUser' => 'LastUserID']),
            ['expand' => $query['expand']]
        );

        foreach ($rows as &$currentRow) {
            $currentRow = $this->normalizeOutput($currentRow, $query['expand']);
        }

        $result = $out->validate($rows, true);

        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter('discussionsApiController_indexOutput', $result, $this, $in, $query, $rows);

        $whereCount = count($where);
        $isWhereOptimized = (isset($where['d.CategoryID']) && ($whereCount === 1 || ($whereCount === 2 && isset($where['Announce']))));
        if ($whereCount === 0 || $isWhereOptimized) {
            $paging = ApiUtils::numberedPagerInfo($this->discussionModel->getCount($where), '/api/v2/discussions', $query, $in);
        } else {
            $paging = ApiUtils::morePagerInfo($rows, '/api/v2/discussions', $query, $in);
        }

        return new Data($result, ['paging' => $paging]);
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
        $discussionData = ApiUtils::convertInputKeys($body);
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
        $result = $this->normalizeOutput($result);
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

        $in = $this->discussionPostSchema('in')->setDescription('Add a discussion.');
        $out = $this->discussionSchema('out');

        $body = $in->validate($body);
        $categoryID = $body['categoryID'];
        $this->discussionModel->categoryPermission('Vanilla.Discussions.Add', $categoryID);
        $this->fieldPermission($body, 'closed', 'Vanilla.Discussions.Close', $categoryID);
        $this->fieldPermission($body, 'pinned', 'Vanilla.Discussions.Announce', $categoryID);
        $this->fieldPermission($body, 'sink', 'Vanilla.Discussions.Sink', $categoryID);

        $discussionData = ApiUtils::convertInputKeys($body);
        $id = $this->discussionModel->save($discussionData);
        $this->validateModel($this->discussionModel);

        if (!$id) {
            throw new ServerException('Unable to insert discussion.', 500);
        }

        $row = $this->discussionByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID', 'LastUserID']);
        $row = $this->normalizeOutput($row);
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
