<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forms;

/**
 * Class StaticFormChoices
 *
 * @package Vanilla\Forms
 */
class StaticFormChoices implements FormChoicesInterface
{
    /**
     * @var array $choices
     */
    private $choices;

    /**
     * StaticFormChoices constructor.
     *
     * @param array $choices
     */
    public function __construct(array $choices = [])
    {
        $this->choices = $choices;
    }

    /**
     * @return array
     */
    public function getChoices(): array
    {
        return ["staticOptions" => $this->choices];
    }

    /**
     * Get all choices.
     *
     * @return array
     */
    public function getOptionsData(): array
    {
        $options = [];
        foreach ($this->choices as $key => $value) {
            $options[] = [
                "value" => $key,
                "label" => $value,
            ];
        }

        if (empty($options)) {
            return [];
        }

        return ["options" => $options];
    }
}
