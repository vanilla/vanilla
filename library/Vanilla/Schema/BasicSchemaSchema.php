<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Schema;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;

/**
 * A schema JSON validator.
 *
 * Use this schema if you want an endpoint to handle schemas itself. This class will validate a stripped down schema
 * array.
 */
class BasicSchemaSchema extends Schema {
    /**
     * @var Schema
     */
    private $propertySchema;

    /**
     * BasicSchemaSchema constructor.
     */
    public function __construct() {
        $schema = [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'enum' => ['object'],
                ],
                'properties' => [
                    'type' => 'object',
                ],
                'required' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
            ],
            'required' => [
                'type',
                'properties',
            ],
        ];

        parent::__construct($schema);

        $propertySchema = [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'type' => ['string', 'array'],
                    'enum' => [
                        'string', ['string', 'null'],
                        'number', ['number', 'null'],
                        'integer', ['integer', 'null'],
                        'boolean', ['boolean', 'null'],
                    ],
                ],
                'description' => [
                    'type' => 'string',
                ],
                'x-label' => [
                    'type' => 'string',
                ],
                'x-control' => [
                    'type' => 'string',
                    'enum' => ['textbox', 'textarea', 'checkbox'],
                ],
                'default' => [
                    'type' => ['string', 'number', 'integer', 'boolean', 'null'],
                ],
                'enum' => [
                    'type' => 'array',
                    'items' => [
                        'type' => ['string', 'number', 'integer', 'boolean'],
                    ],
                ],
                'minLength' => [
                    'type' => 'integer',
                    'minimum' => 0,
                ],
                'maxLength' => [
                    'type' => 'integer',
                    'minimum' => 1,
                ],
                'minimum' => [
                    'type' => 'number',
                ],
                'maximum' => [
                    'type' => 'number',
                ],
            ],
            'required' => [
                'type',
            ],
        ];

        $this->propertySchema = new Schema($propertySchema);
    }

    /**
     * {@inheritDoc}
     */
    public function validate($data, $sparse = false) {
        $valid = parent::validate($data, $sparse);

        // Garden schema 1.x doesn't support the additionalProperties attribute so fake it here.
        $validation = $this->createValidation();
        foreach ($valid['properties'] as $name => &$property) {
            try {
                $property = $this->propertySchema->validate($property);
            } catch (ValidationException $ex) {
                $validation->merge($ex->getValidation(), "properties.$name");
            }
        }
        if ($validation->getErrorCount() > 0) {
            throw new ValidationException($validation);
        }
        return $valid;
    }
}
