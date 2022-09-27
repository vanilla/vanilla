<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forms;

use Garden\Schema\Schema;

/**
 * Class FieldMatchConditional
 *
 * @package Vanilla\Forms
 */
class FieldMatchConditional implements FormFieldMatchInterface
{
    /**
     * @var string $field
     */
    private $field;

    /**
     * @var Schema $schema
     */
    private $schema;

    /**
     * FieldMatchConditional constructor.
     *
     * @param string $field
     * @param Schema $schema
     */
    public function __construct(string $field, Schema $schema)
    {
        $this->field = $field;
        $this->schema = $schema;
    }

    /**
     * Get all choices.
     *
     * @return array
     */
    public function getCondition(): array
    {
        return [
            "field" => $this->field,
        ] + $this->schema->getSchemaArray();
    }
}
