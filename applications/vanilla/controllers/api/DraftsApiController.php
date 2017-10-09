<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Utility\CapitalCaseScheme;
use Vanilla\Utility\CamelCaseScheme;

/**
 * API Controller for the `/drafts` resource.
 */
class DraftsApiController extends AbstractApiController {

    /** @var CapitalCaseScheme */
    private $caseScheme;

    /** @var DraftModel */
    private $draftModel;

    /** @var Schema */
    private $idParamSchema;

    /** @var CamelCaseScheme */
    private $translateCaseScheme;

    /**
     * DraftsApiController constructor.
     *
     * @param DraftModel $draftModel
     */
    public function __construct(DraftModel $draftModel) {
        $this->draftModel = $draftModel;
        $this->caseScheme = new CapitalCaseScheme();
        $this->translateCaseScheme = new CamelCaseScheme();
    }

    /**
     * Delete a draft.
     *
     * @param int $id The unique ID of the draft.
     */
    public function delete($id) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamSchema('in')->setDescription('Delete a draft.');
        $out = $this->schema([], 'out');

        $row = $this->draftByID($id);
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }
        $this->draftModel->deleteID($id);
    }

    /**
     * Get a draft by its unique ID.
     *
     * @param int $id
     * @throws
     * @return array
     */
    public function draftByID($id) {
        $row = $this->draftModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Draft');
        }
        return $row;
    }

    /**
     * Get a draft schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function draftPostSchema($type) {
        static $draftPostSchema;

        if (!isset($draftPostSchema)) {
            $draftPostSchema = $this->schema(
                Schema::parse(
                    ['recordType', 'parentRecordID?', 'attributes']
                )->add($this->fullSchema()),
                'DraftPost'
            );
        }

        return $this->schema($draftPostSchema, $type);
    }

    /**
     * Get a schema instance comprised of all available draft fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullSchema() {
        static $schema;

        if (!isset($schema)) {
            $schema = Schema::parse([
                'draftID:i' => 'The unique ID of the draft.',
                'recordType:s' => [
                    'description' => 'The type of record associated with this draft.',
                    'enum' => ['comment', 'discussion']
                ],
                'parentRecordID:i|n' => 'The unique ID of the intended parent to this record.',
                'attributes:o' => [
                    'description' => 'A free-form object containing all custom data for this draft.',
                    'properties' => [
                        'announce:b?' => 'If the record is a discussion, should it be announced when posted?',
                        'body:s' => 'The body content of a post draft.',
                        'closed:b?' => 'If the record is a discussion, should it be closed when posted?',
                        'categoryID:i?' => 'The category ID of a discussion.',
                        'name:s?' => 'The title of a discussion.',
                        'sink:b?' => 'If the record is a discussion, should it be sunk when posted?',
                        'tags:a?' => [
                            'description' => 'Tags to apply to a discussion.',
                            'items' => ['type' => 'string'],
                            'style' => 'form'
                        ],
                        'format:s?' => 'The format of the post.'
                    ]
                ],
                'insertUserID:i' => 'The unique ID of the user who created this draft.',
                'dateInserted:dt' => 'When the draft was created.',
                'updateUserID:i|n' => 'The unique ID of the user who updated this draft.',
                'dateUpdated:dt|n' => 'When the draft was updated.'
            ]);
        }

        return $schema;
    }

    /**
     * Get a draft.
     *
     * @param int $id The unique ID of the draft.
     * @return array
     */
    public function get($id) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamSchema('in')->setDescription('Get a draft.');
        $out = $this->schema($this->fullSchema(), 'out');

        $row = $this->draftByID($id);
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }
        $this->prepareRow($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a draft for editing.
     *
     * @param int $id The unique ID of the draft.
     * @return array
     */
    public function get_edit($id) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamSchema('in')->setDescription('Get a draft for editing.');
        $out = $this->schema(['draftID', 'parentRecordID', 'attributes'], 'out')->add($this->fullSchema());

        $row = $this->draftByID($id);
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }
        $this->prepareRow($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only draft record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema($type = 'in') {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse(['id:i' => 'The draft ID.']),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * List drafts created by the current user.
     *
     * @param array $query The query string.
     * @return array
     */
    public function index(array $query) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'recordType:s?' => [
                'description' => 'Limit the drafts to this record type.',
                'enum' => ['comment', 'discussion']
            ],
            'parentRecordID:i?' => 'Filter by the unique ID of the parent for a draft. Used with recordType.',
            'page:i?' => [
                'description' => 'Page number.',
                'default' => 1,
                'minimum' => 1
            ],
            'limit:i?' => [
                'description' => 'The number of items per page.',
                'default' => 30,
                'minimum' => 1,
                'maximum' => 100
            ]
        ], 'in')->setDescription('List drafts created by the current user.');
        $out = $this->schema([':a' => $this->fullSchema()], 'out');

        $query = $in->validate($query);

        $where = ['InsertUserID' => $this->getSession()->UserID];
        if (array_key_exists('recordType', $query)) {
            switch ($query['recordType']) {
                case 'comment':
                    if (array_key_exists('parentRecordID', $query) && !empty($query['parentRecordID'])) {
                        $where['DiscussionID'] = $query['parentRecordID'];
                    } else {
                        $where['DiscussionID >'] = 0;
                    }
                    break;
                case 'discussion':
                    $where['DiscussionID'] = null;
                    break;
            }
        }

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);
        $rows = $this->draftModel->getWhere($where, '', 'asc', $limit, $offset)->resultArray();

        foreach ($rows as &$row) {
            $this->prepareRow($row);
        }

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Update a draft.
     *
     * @param int $id The unique ID of the draft.
     * @param array $body The request body.
     * @return array
     */
    public function patch($id, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->draftPostSchema('in')->setDescription('Update a draft.');
        $out = $this->schema(['draftID', 'parentRecordID', 'attributes'], 'out')->add($this->fullSchema());

        $row = $this->draftByID($id);
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }

        $body = $in->validate($body, true);
        $recordType = !empty('DiscussionID') ? 'comment' : 'discussion';
        $draftData = $this->translateRequest($body, $recordType);
        $draftData['DraftID'] = $id;
        $this->draftModel->save($draftData);
        $this->validateModel($this->draftModel);

        $updatedRow = $this->draftByID($id);
        $this->prepareRow($updatedRow);

        $result = $out->validate($updatedRow);
        return $result;
    }

    /**
     * Create a draft.
     *
     * @param array $body The request body.
     * @return array
     */
    public function post(array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->draftPostSchema('in')->setDescription('Create a draft.');
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body);
        $draftData = $this->translateRequest($body);
        $draftID = $this->draftModel->save($draftData);
        $this->validateModel($this->draftModel);

        $row = $this->draftByID($draftID);
        $this->prepareRow($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Prepare data for output.
     *
     * @param array $row
     */
    public function prepareRow(array &$row) {
        $row = $this->translateRow($row);
    }

    /**
     * Translate a request to this endpoint into a format compatible for saving with DraftModel.
     *
     * @param array $body
     * @param string $recordType
     * @return array
     */
    private function translateRequest(array $body, $recordType = null) {
        // If the record type is not explicitly defined by the parameters, try to extract it from $body.
        if ($recordType === null && array_key_exists('recordType', $body)) {
            $recordType = $body['recordType'];
        }

        if (array_key_exists('attributes', $body)) {
            $columns = ['announce', 'body', 'categoryID', 'closed', 'format', 'name', 'sink', 'tags'];
            $attributes = array_intersect_key($body['attributes'], array_flip($columns));
            $body = array_merge($body, $attributes);
            unset($body['attributes']);
        }

        if (array_key_exists('tags', $body)) {
            if (empty($body['tags'])) {
                $body['tags'] = null;
            } elseif (is_array($body['tags'])) {
                $body['tags'] = implode(',', $body['tags']);
            }
        }

        switch ($recordType) {
            case 'comment':
                if (array_key_exists('parentRecordID', $body)) {
                    $body['DiscussionID'] = $body['parentRecordID'];
                }
                break;
            case 'discussion':
                $body['DiscussionID'] = null;
        }
        unset($body['recordType'], $body['parentRecordID']);

        $result = $this->caseScheme->convertArrayKeys($body);
        return $result;
    }

    /**
     * Translate the structure of a row from the drafts table into the format used by this endpoint.
     *
     * @param array $row
     * @return array
     */
    private function translateRow(array $row) {
        $parentRecordID = null;

        $commentAttributes = ['Body', 'DiscussionID', 'Format'];
        $discussionAttributes = ['Announce', 'Body', 'CategoryID', 'Closed', 'Format', 'Name', 'Sink', 'Tags'];
        if (array_key_exists('DiscussionID', $row) && !empty($row['DiscussionID'])) {
            $row['RecordType'] = 'comment';
            $parentRecordID = $row['DiscussionID'];
            $attributes = $commentAttributes;
        } else {
            $row['RecordType'] = 'discussion';
            $attributes = $discussionAttributes;
        }

        $row['Attributes'] = array_intersect_key($row, array_flip($attributes));
        $row['ParentRecordID'] = $parentRecordID;

        // Remove redundant attribute columns on the row.
        foreach (array_merge($commentAttributes, $discussionAttributes) as $col) {
            unset($row[$col]);
        }

        $result = $this->translateCaseScheme->convertArrayKeys($row);
        return $result;
    }
}
