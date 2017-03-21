<?php
use Garden\Schema\Schema;

/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

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

    /**
     * DiscussionsApiController constructor.
     *
     * @param DiscussionModel $discussionModel
     */
    public function __construct(DiscussionModel $discussionModel) {
        $this->discussionModel = $discussionModel;
    }

    /**
     * List discussions.
     *
     * @param array $query The query string.
     */
    public function index(array $query) {
        $this->permission();
        $in = $this->schema([
            'categoryID:i?' => 'Filter by a category.',
            'inertUserID:i?' => 'Filter by author.',
            'page:i?',
            'expand:b?' => 'Expand joined records.'
        ], 'in')->setDescription('List discussions.');
        $out = $this->schema([':a' => $this->discussionSchema()], 'out');

        $query = $in->validate($query);
    }

    /**
     * Get a discussion.
     *
     * @param int $id The ID of the discussion.
     */
    public function get($id) {
        $this->permission();

        $in = $this->idParamSchema()->setDescription('Get a discussion.');
        $out = $this->schema($this->discussionSchema(), 'out');
    }

    /**
     * Get a discussion for editing.
     *
     * @param int $id The ID of the discussion.
     */
    public function get_edit($id) {
        $this->permission();

        $in = $this->idParamSchema()->setDescription('Get a discussion for editing.');
        $out = $this->schema(Schema::parse(['discussionID', 'name', 'body', 'format'])->add($this->fullSchema()), 'out');
    }

    /**
     * Add a discussion.
     *
     * @param array $body The request body.
     */
    public function post(array $body) {
        $this->permission();

        $in = $this->schema($this->discussionPostSchema(), 'in')->setDescription('Add a discussion.');
        $out = $this->schema($this->discussionSchema(), 'out');
    }

    /**
     * Update a discussion.
     *
     * @param int $id The ID of the discussion.
     * @param array $body The request body.
     */
    public function patch($id, array $body) {
        $this->permission();

        $in = $this->discussionPostSchema('in')->setDescription('Update a discussion.');
        $out = $this->schema($this->discussionSchema(), 'out');
    }

    /**
     * Announce a discussion.
     *
     * @param int $id The ID of the discussion.
     */
    public function put_announce($id) {
        $this->permission();

        $in = $this->idParamSchema()->setDescription('Announce a discussion.');
    }

    /**
     * Close a discussion.
     *
     * @param int $id The ID of the discussion.
     */
    public function put_close($id) {
        $this->permission();

        $in = $this->idParamSchema()->setDescription('Close a discussion.');
    }

    /**
     * Sink a discussion.
     *
     * @param int $id The ID of the discussion.
     */
    public function put_sink($id) {
        $this->permission();

        $in = $this->idParamSchema()->setDescription('Sink a discussion.');
    }

    /**
     * Bookmark a discussion.
     *
     * @param int $id The ID of the discussion.
     * @param array $body The request body.
     */
    public function put_bookmark($id, array $body) {
        $this->permission();

        $in = $this
            ->schema(['bookmarked:b' => 'Pass true to bookmark or false to remove bookmark.'], 'in')
            ->setDescription('Bookmark a discussion.');
        $out = $this->schema(['bookmarked:b' => 'The current bookmark value.'], 'out');

        $body = $in->validate($body);
    }

    /**
     * Delete a discussion.
     *
     * @param int $id The ID of the discussion.
     */
    public function delete($id) {
        $this->permission();

        $in = $this->idParamSchema()->setDescription('Delete a discussion.');
        $out = $this->schema([], 'out');
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
            'format:s' => 'The output format of the discussion.',
            'categoryID:i' => 'The category the discussion is in.',
            'dateInserted:dt' => 'When the discussion was created.',
            'insertUserID:i' => 'The user that created the discussion.',
            'insertUser?' => $this->getUserFragmentSchema()
        ]);
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
}
