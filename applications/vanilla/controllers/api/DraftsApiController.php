<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;

/**
 * API Controller for the `/drafts` resource.
 */
class DraftsApiController extends AbstractApiController {

    /** @var DraftModel */
    private $draftModel;

    /**
     * DraftsApiController constructor.
     *
     * @param DraftModel $draftModel
     */
    public function __construct(DraftModel $draftModel) {
        $this->draftModel = $draftModel;
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
                Schema::parse([
                    'recordType',
                    'parentRecordID?',
                    'attributes'
                ])->add($this->fullSchema()),
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
                'attributes:o' => 'A free-form object containing all custom data for this draft.',
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
        $row = $this->normalizeOutput($row);

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
        $out = $this->schema([
            'draftID',
            'parentRecordID',
            'attributes',
        ], 'out')->add($this->fullSchema());

        $row = $this->draftByID($id);
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }
        $row = $this->normalizeOutput($row);

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
        static $schema;

        if (!isset($schema)) {
            $schema = $this->schema(
                Schema::parse(['id:i' => 'The draft ID.']),
                $type
            );
        }

        return $this->schema($schema, $type);
    }

    /**
     * List drafts created by the current user.
     *
     * @param array $query The query string.
     * @return Data
     */
    public function index(array $query) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'recordType:s?' => [
                'description' => 'Filter drafts by record type.',
                'enum' => ['comment', 'discussion']
            ],
            'parentRecordID:i|n?' => [
                'description' => 'Filter by the unique ID of the parent for a draft. Used with recordType.',
                'default' => null
            ],
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
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
                    if ($query['parentRecordID'] !== null) {
                        $where['DiscussionID'] = $query['parentRecordID'];
                    } else {
                        $where['DiscussionID >'] = 0;
                    }
                    break;
                case 'discussion':
                    if ($query['parentRecordID'] !== null) {
                        $where['CategoryID'] = $query['parentRecordID'];
                    }
                    $where['DiscussionID'] = null;
                    break;
            }
        }

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);
        $rows = $this->draftModel->getWhere($where, '', 'asc', $limit, $offset)->resultArray();

        foreach ($rows as &$row) {
            $row = $this->normalizeOutput($row);
        }

        $result = $out->validate($rows);

        $paging = ApiUtils::numberedPagerInfo(
            $this->draftModel->getCount($where),
            '/api/v2/drafts',
            $query,
            $in
        );

        return new Data($result, ['paging' => $paging]);
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

        $this->idParamSchema();
        $in = $this->draftPostSchema('in')->setDescription('Update a draft.');
        $out = $this->schema(['draftID', 'parentRecordID', 'attributes'], 'out')->add($this->fullSchema());

        $row = $this->draftByID($id);
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }

        $body = $in->validate($body, true);
        $recordType = !empty($row['DiscussionID']) ? 'comment' : 'discussion';
        $draftData = $this->normalizeInput($body, $recordType);
        $draftData['DraftID'] = $id;
        $this->draftModel->save($draftData);
        $this->validateModel($this->draftModel);

        $updatedRow = $this->draftByID($id);
        $updatedRow = $this->normalizeOutput($updatedRow);

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
        $draftData = $this->normalizeInput($body);
        $draftID = $this->draftModel->save($draftData);
        $this->validateModel($this->draftModel);

        $row = $this->draftByID($draftID);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @return array Return a Schema record.
     */
    public function normalizeOutput(array $dbRecord) {
        $parentRecordID = null;

        $commentAttributes = ['Body', 'Format'];
        $discussionAttributes = ['Announce', 'Body', 'Closed', 'Format', 'Name', 'Sink', 'Tags'];
        if (array_key_exists('DiscussionID', $dbRecord) && !empty($dbRecord['DiscussionID'])) {
            $dbRecord['RecordType'] = 'comment';
            $parentRecordID = $dbRecord['DiscussionID'];
            $attributes = $commentAttributes;
        } else {
            if (array_key_exists('CategoryID', $dbRecord) && !empty($dbRecord['CategoryID'])) {
                $parentRecordID = $dbRecord['CategoryID'];
            }
            $dbRecord['RecordType'] = 'discussion';
            $attributes = $discussionAttributes;
        }

        $dbRecord['Attributes'] = array_intersect_key($dbRecord, array_flip($attributes));
        $dbRecord['ParentRecordID'] = $parentRecordID;

        // Remove redundant attribute columns on the row.
        foreach (array_merge($commentAttributes, $discussionAttributes) as $col) {
            unset($dbRecord[$col]);
        }

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        return $schemaRecord;
    }

    /**
     * Normalize a Schema record to match the database definition.
     *
     * @param array $schemaRecord Schema record.
     * @param string|null $recordType
     * @return array Return a database record.
     */
    private function normalizeInput(array $schemaRecord, $recordType = null) {
        // If the record type is not explicitly defined by the parameters, try to extract it from $body.
        if ($recordType === null && array_key_exists('recordType', $schemaRecord)) {
            $recordType = $schemaRecord['recordType'];
        }

        if (array_key_exists('attributes', $schemaRecord)) {
            $columns = ['announce', 'body', 'categoryID', 'closed', 'format', 'name', 'sink', 'tags'];
            $attributes = array_intersect_key($schemaRecord['attributes'], array_flip($columns));
            $schemaRecord = array_merge($schemaRecord, $attributes);
            unset($schemaRecord['attributes']);
        }

        if (array_key_exists('tags', $schemaRecord)) {
            if (empty($schemaRecord['tags'])) {
                $schemaRecord['tags'] = null;
            } elseif (is_array($schemaRecord['tags'])) {
                $schemaRecord['tags'] = implode(',', $schemaRecord['tags']);
            }
        }

        switch ($recordType) {
            case 'comment':
                if (array_key_exists('parentRecordID', $schemaRecord)) {
                    $schemaRecord['DiscussionID'] = $schemaRecord['parentRecordID'];
                }
                break;
            case 'discussion':
                if (array_key_exists('parentRecordID', $schemaRecord)) {
                    $schemaRecord['CategoryID'] = $schemaRecord['parentRecordID'];
                }
                $schemaRecord['DiscussionID'] = null;
        }
        unset($schemaRecord['recordType'], $schemaRecord['parentRecordID']);

        $result = ApiUtils::convertInputKeys($schemaRecord);
        return $result;
    }
}
