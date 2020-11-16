<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forms;

/**
 * Class FieldMatchConditional
 *
 * @package Vanilla\Forms
 */
class FieldMatchConditional implements FormFieldMatchInterface {

    /**
     * @var string $fieldName
     */
    private $fieldName;

    /**
     * @var array $values
     */
    private $values;

    /**
     * FieldMatchConditional constructor.
     *
     * @param string $field
     * @param array $values
     */
    public function __construct(
        string $field = '',
        array $values = []
    ) {
        $this->fieldName = $field;
        $this->values = $values;
    }

    /**
     * Get all choices.
     *
     * @return array
     */
    public function getConditions(): array {
        return [

                'fieldName' => $this->fieldName,
                'values' => $this->values,
        ];
    }
}
