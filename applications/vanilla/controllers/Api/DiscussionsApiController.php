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
    private $discussionModel;

    /**
     * @var Schema
     */
    private $discussionSchema;

    /**
     * @var
     */
    private $discussionPostSchema;

    public function __construct(DiscussionModel $discussionModel) {
        $this->discussionModel = $discussionModel;
    }

    public function index(array $query) {
        $this->permission();
        $sch = $this->schema([
            'categoryID:i?' => 'Filter by a category.',
            'inertUserID:i?' => 'Filter by author.',
            'page:i?',
            'expand:b?' => 'Expand joined records.'
        ], 'in')->setDescription('List discussions.');
        $out = $this->schema([':a' => $this->discussionSchema()], 'out');

        $query = $sch->validate($query);
    }

    public function get($id) {
        $this->permission();

        $in = $this->idParamSchema()->setDescription('Get a discussion.');
        $out = $this->schema($this->discussionSchema(), 'out');
    }

    public function get_edit($id) {
        $this->permission();

        $in = $this->idParamSchema()->setDescription('Get a discussion for editing.');
        $out = $this->schema(Schema::parse(['discussionID', 'name', 'body', 'format'])->add($this->fullSchema()), 'out');
    }

    public function post(array $body) {
        $this->permission('!update');

        $sch = $this->schema($this->discussionPostSchema(), 'in')->setDescription('Add a discussion.');
        $out = $this->schema($this->discussionSchema(), 'out');

    }

    public function patch($id, array $body) {
        $this->permission();

        $sch = $this->discussionPostSchema('in')->setDescription('Update a discussion.');
        $out = $this->schema($this->discussionSchema(), 'out');
    }

    public function put_announce($id) {
        $this->permission();

        $sch = $this->idParamSchema()->setDescription('Announce a discussion.');
    }

    public function put_close($id) {
        $this->permission();

        $sch = $this->idParamSchema()->setDescription('Close a discussion.');
    }

    public function put_sink($id) {
        $this->permission();

        $sch = $this->idParamSchema()->setDescription('Sink a discussion.');
    }

    public function put_bookmark($id, array $body) {
        $this->permission();

        $sch = $this
            ->schema(['bookmarked:b' => 'Pass true to bookmark or false to remove bookmark.'], 'in')
            ->setDescription('Bookmark a discussion.');
        $out = $this->schema(['bookmarked:b' => 'The current bookmark value.'], 'out');

        $body = $sch->validate($body);
    }

    public function delete($id) {
        $this->permission();

        $in = $sch = $this->idParamSchema()->setDescription('Delete a discussion.');
        $out = $this->schema([], 'out');
    }

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

    public function discussionSchema($type = '') {
        if ($this->discussionSchema === null) {
            $this->discussionSchema = $this->schema($this->fullSchema(), 'Discussion');
        }
        return $this->schema($this->discussionSchema, $type);
    }

    public function discussionPostSchema($type = '') {
        if ($this->discussionPostSchema === null) {
            $this->discussionPostSchema = $this->schema(
                Schema::parse(['name', 'body', 'format', 'categoryID'])->add($this->fullSchema()),
                'DiscussionPost'
            );
        }
        return $this->schema($this->discussionPostSchema, $type);
    }

    public function idParamSchema($type = 'in') {
        return $this->schema(['id:i' => 'The discussion ID.'], $type);
    }
}
