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
    const CODE_EDITOR_TYPE = 'codeBox';

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
    ): array {

        $result = [
            'description' => $options->getDescription(),
            'label' => $options->getLabel(),
            'inputType' => self::DROPDOWN_TYPE,
            'placeholder' => $options->getPlaceHolder(),
            'choices' => $choices->getChoices(),
        ];

        if ($conditions) {
            $result['conditions'] = [$conditions->getCondition()];
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
    public static function textBox(FormOptions $options, string $type = 'text'): array {
        return [
            'description' => $options->getDescription(),
            'label' => $options->getLabel(),
            'inputType' => self::TEXT_TYPE,
            'placeholder' => $options->getPlaceHolder(),
            'type' => $type,
        ];
    }

    /**
     * Code box form element schema.
     *
     * @param FormOptions $options
     * @param string $language "text/html", "application/json".
     * @param string $jsonSchemaUri
     *
     * @return array
     */
    public static function codeBox(
        FormOptions $options,
        string $language = 'text/html',
        ?string $jsonSchemaUri = null
    ): array {
        return [
            'description' => $options->getDescription(),
            'label' => $options->getLabel(),
            'inputType' => self::CODE_EDITOR_TYPE,
            'placeholder' => $options->getPlaceHolder(),
            'language' => $language,
            'jsonSchemaUri' => $jsonSchemaUri,
        ];
    }

    /**
     * Toggle form element schema.
     *
     * @param FormOptions $options
     * @param FieldMatchConditional|null $conditions
     * @return array
     */
    public static function toggle(FormOptions $options, FieldMatchConditional $conditions = null) {
        $result = [
            'description' => $options->getDescription(),
            'label' => $options->getLabel(),
            'inputType' => self::TOGGLE_TYPE,
        ];

        if ($conditions) {
            $result['conditions'] = [$conditions->getCondition()];
        }

        return $result;
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
            $result['conditions'] = [$conditions->getCondition()];
        }

        return $result;
    }
}
