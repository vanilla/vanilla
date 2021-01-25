<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;

/**
 * API Controller for the `/reactions` resource.
 */
class ReactionsApiController extends AbstractApiController {

    /** @var ReactionModel */
    private $reactionModel;

    /**
     * ReactionsApiController constructor.
     *
     * @param ReactionModel $reactionModel
     */
    public function __construct(ReactionModel $reactionModel) {
        $this->reactionModel = $reactionModel;
    }

    /**
     * Get a schema comprised of all available fields for a reaction type.
     *
     * @return Schema
     */
    public function fullReactionTypeSchema() {
        static $schema;

        if ($schema === null) {
            $schema = $this->schema($this->reactionModel->typeSchema(), 'ReactionType');
        }

        return $schema;
    }

    /**
     * Get a single reaction type.
     *
     * @param string $urlCode
     * @return array
     */
    public function get($urlCode) {
        $this->permission('Garden.Community.Manage');

        $in = $this->schema($this->idParamSchema(), 'in')->setDescription('Get a single reaction type.');
        $out = $this->schema($this->fullReactionTypeSchema(), 'out');

        $row = $this->normalizeOutput($this->reactionByUrlCode($urlCode));
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a reaction type for editing.
     *
     * @param string $urlCode
     * @return array
     */
    public function get_edit($urlCode) {
        $this->permission('Garden.Community.Manage');

        $in = $this->schema($this->idParamSchema(), 'in')->setDescription('Get a reaction type for editing.');
        $out = $this->schema(Schema::parse([
            'urlCode', 'name', 'description', 'class', 'points', 'active'
        ])->add($this->fullReactionTypeSchema()), 'out');

        $row = $this->normalizeOutput($this->reactionByUrlCode($urlCode));
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only schema for reaction types.
     *
     * @return Schema Returns a schema object.
     */
    public function idParamSchema() {
        return $this->schema(['id:i' => 'The reaction type ID.'], 'in');
    }

    /**
     * Get a list of reaction types.
     *
     * @return array
     */
    public function index() {
        $this->permission('Garden.Community.Manage');

        $in = $this->schema([], 'in')->setDescription('Get a list of reaction types.');
        $out = $this->schema([':a' => $this->fullReactionTypeSchema()], 'out');

        $rows = array_values(ReactionModel::reactionTypes());
        foreach ($rows as &$row) {
            $row = $this->normalizeOutput($row);
        }
        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Normalize API input for use with existing models possibly expecting database field names.
     *
     * @param array $row
     * @return array
     */
    public function normalizeInput(array $row) {
        $row = ApiUtils::convertInputKeys($row);
        return $row;
    }

    /**
     * Normalize a reaction type database row for use in API responses.
     *
     * @param array $row
     * @return array
     */
    public function normalizeOutput(array $row) {
        $row = $this->reactionModel->normalizeTypeRow($row);
        return $row;
    }

    /**
     * Update a reaction type.
     *
     * @param string $urlCode
     * @param array $body
     * @return array
     */
    public function patch($urlCode, array $body) {
        $this->permission('Garden.Community.Manage');

        $this->idParamSchema();
        $in = $this->schema($this->postSchema(), 'in')->setDescription('Update a reaction type.');
        $out = $this->schema($this->fullReactionTypeSchema(), 'out');

        $body = $in->validate($body, true);

        // Make sure the reaction exists.
        $row = $this->reactionByUrlCode($urlCode);

        // Prepare to save. Flag as custom, so future updates won't wipe out changes.
        $body['urlCode'] = $row['UrlCode']; // Maintain original URL code casing.
        $body['custom'] = true;
        $data = $this->normalizeInput($body);
        $this->reactionModel->save($data);
        ReactionModel::$ReactionTypes = null; // Wipe the types cache.

        $this->validateModel($this->reactionModel);
        $row = $this->normalizeOutput($this->reactionByUrlCode($urlCode));
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a schema suitable for adding or editing a resource row.
     *
     * @return Schema
     */
    public function postSchema() {
        static $schema;

        if ($schema === null) {
            $schema = Schema::parse([
                'name', 'description', 'class', 'points', 'active'
            ])->add($this->fullReactionTypeSchema());
            $schema->setField('properties.class.enum', ['Flag', 'Negative', 'Positive']);
        }

        return $schema;
    }

    /**
     * Get a reaction type by its numeric ID.
     *
     * @param string $urlCode The URL code of a reaction type.
     * @throws NotFoundException If the reaction could not be found.
     * @return array
     */
    public function reactionByUrlCode($urlCode) {
        $row = ReactionModel::reactionTypes($urlCode);
        if (!$row) {
            throw new NotFoundException('Reaction');
        }
        return $row;
    }
}
