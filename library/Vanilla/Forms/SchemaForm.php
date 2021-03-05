<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forms;

/**
 * Form element schemas.
 */
class SchemaForm {

    const DROPDOWN_TYPE = 'dropDown';
    const RADIO_TYPE = 'radio';
    const TEXT_TYPE = 'textBox';
    const TOGGLE_TYPE = 'toggle';

    /**
     * Create a "section" of the form on an object type.
     *
     * @param FormOptions $options
     *
     * @return array
     */
    public static function section(FormOptions $options): array {
        return [
            'label' => $options->getLabel(),
            'description' => $options->getDescription(),
        ];
    }

    /**
     * Drop down form element schema.
     *
     * @param FormOptions $options
     * @param FormChoicesInterface $choices
     * @param FieldMatchConditional|null $conditions
     * @return array
     */
    public static function dropDown(
        FormOptions $options,
        FormChoicesInterface $choices,
        FieldMatchConditional $conditions = null
    ) {

        $result = [
            'description' => $options->getDescription(),
            'label' => $options->getLabel(),
            'inputType' => self::DROPDOWN_TYPE,
            'placeholder' => $options->getPlaceHolder(),
            'choices' => $choices->getChoices(),
        ];

        if ($conditions) {
            $result['conditions'] =  [$conditions->getConditions()];
        }

        return $result;
    }

    /**
     * Text box form element schema.
     *
     * @param FormOptions $options
     * @param string $type "text", "number" or "textarea".
     *
     * @return array
     */
    public static function textBox(FormOptions $options, string $type = 'text') {
        return [
            'description' => $options->getDescription(),
            'label' => $options->getLabel(),
            'inputType' => self::TEXT_TYPE,
            'placeholder' => $options->getPlaceHolder(),
            'type' => $type,
        ];
    }

    /**
     * Toggle form element schema.
     *
     * @param FormOptions $options
     * @return array
     */
    public static function toggle(FormOptions $options) {
        return [
            'description' => $options->getDescription(),
            'label' => $options->getLabel(),
            'inputType' => self::TOGGLE_TYPE,
        ];
    }

    /**
     * Radio form element schema.
     *
     * @param FormOptions $options
     * @param FormChoicesInterface $choices
     * @param ?FieldMatchConditional $conditions
     *
     * @return array
     */
    public static function radio(FormOptions $options, FormChoicesInterface $choices, FieldMatchConditional $conditions = null) {
        $result = [
            'description' => $options->getDescription(),
            'label' => $options->getLabel(),
            'inputType' => self::RADIO_TYPE,
            'choices' => $choices->getChoices(),
        ];

        if ($conditions) {
            $result['conditions'] = [$conditions->getConditions()];
        }
        return $result;
    }
}
