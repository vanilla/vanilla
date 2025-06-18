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
     * FieldMatchConditional constructor.
     *
     * @param string $field
     * @param Schema $schema
     * @param bool $invert
     */
    public function __construct(protected string $field, protected Schema $schema, protected bool $invert = false)
    {
    }

    /**
     * Get all choices.
     *
     * @return array
     */
    public function getCondition(): array
    {
        $res =
            [
                "field" => $this->field,
            ] + $this->schema->getSchemaArray();
        if ($this->invert) {
            $res["invert"] = true;
        }
        return $res;
    }
}
