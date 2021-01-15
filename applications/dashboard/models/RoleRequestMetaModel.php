<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Vanilla\Database\Operation;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentIPAddressProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Models\FormatSchema;
use Vanilla\Schema\BasicSchemaSchema;

/**
 * Handles meta information for controlling role request.
 *
 * In order for a role to support requests it must have an entry in this table for any request it wants to process.
 */
final class RoleRequestMetaModel extends \Vanilla\Models\PipelineModel {
    private const FIELD_SCHEMA = 'attributesSchema';
    /**
     * @var Schema
     */
    private $schema;

    /**
     * RoleRequestMetaModel constructor.
     *
     * @param CurrentUserFieldProcessor $userFields
     * @param CurrentIPAddressProcessor $ipFields
     */
    public function __construct(
        CurrentUserFieldProcessor $userFields,
        CurrentIPAddressProcessor $ipFields
    ) {
        parent::__construct('roleRequestMeta');
        $this->setPrimaryKey('roleID', 'type');
        $this->schema = new BasicSchemaSchema();

        $dateFields = new CurrentDateFieldProcessor();
        $dateFields->camelCase();
        $this->addPipelineProcessor($dateFields);

        $userFields->camelCase();
        $this->addPipelineProcessor($userFields);

        $ipFields->camelCase();
        $this->addPipelineProcessor($ipFields);

        $attributes = new JsonFieldProcessor([self::FIELD_SCHEMA, 'attributes']);
        $this->addPipelineProcessor($attributes);
    }

    /**
     * {@inheritDoc}
     */
    public function insert(array $set, array $options = []) {
        // Check an existing record and change it to an update.
        $current = $this->select($this->pluckPrimaryWhere($set));
        if (!empty($current)) {
            return $this->update(
                $set,
                $this->pluckPrimaryWhere($set),
                $options
            );
        }

        return parent::insert($set, $options);
    }

    /**
     * Returns the schema used to validate the attributes object.
     *
     * This is a bit confusing, but this is the schema for the meta table's attributes and not the schema for the
     * `attributesSchema` field.
     *
     * @return Schema
     */
    public function getAttributesSchema(): Schema {
        $notificationSchema = Schema::parse([
            'name:s?',
            'body:s?',
            'format?' => new FormatSchema(),
            'url:s?',
        ]);

        $r = Schema::parse([
            'notification?' => [
                'type' => 'object',
                'properties' => [
                    'approved?' => $notificationSchema,
                    'denied?' => $notificationSchema,
                    'communityManager?' => $notificationSchema,
                ]
            ],
            'link:o?' => [
                'name:s?',
                'description:s?',
            ],
            'allowReapply' => [
                'type' => 'boolean',
                'default' => 'false'
            ],
            'notifyDenied' => [
                'type' => 'boolean',
                'default' => 'false'
            ],
            'notifyCommunityManager' => [
                'type' => 'boolean',
                'default' => 'false'
            ],
        ]);
        return $r;
    }

    /**
     * {@inheritDoc}
     */
    protected function handleInnerOperation(Operation $op) {
        // Validate the attributes schema.
        if ($op->hasSetItem(self::FIELD_SCHEMA)) {
            $schema = json_decode($op->getSetItem(self::FIELD_SCHEMA), true);
            $schema['description'] = 'Meta';
            try {
                $valid = $this->schema->validate($schema);
                $op->setSetItem(self::FIELD_SCHEMA, json_encode($valid));
            } catch (ValidationException $ex) {
                $validation = new Validation();
                $validation->merge($ex->getValidation(), self::FIELD_SCHEMA);
                throw new ValidationException($validation);
            }
        }

        return parent::handleInnerOperation($op);
    }
}
