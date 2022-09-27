<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forms;

use Garden\Schema\Schema;

/**
 * Form element schemas.
 */
class SchemaForm
{
    const DROPDOWN_TYPE = "dropDown";
    const RADIO_TYPE = "radio";
    const CHECKBOX_TYPE = "checkBox";
    const TEXT_TYPE = "textBox";
    const TOGGLE_TYPE = "toggle";
    const DRAG_AND_DROP_TYPE = "dragAndDrop";
    const CODE_EDITOR_TYPE = "codeBox";
    const COLOR_TYPE = "color";
    const UPLOAD = "upload";

    /**
     * Create a "section" of the form on an object type.
     *
     * @param FormOptions $options
     *
     * @return array
     */
    public static function section(FormOptions $options): array
    {
        return [
            "label" => $options->getLabel(),
            "description" => $options->getDescription(),
        ];
    }

    /**
     * Drop down form element schema.
     *
     * @param FormOptions $options
     * @param FormChoicesInterface $choices
     * @param FieldMatchConditional|null $conditions
     * @param boolean $multiple
     * @return array
     */
    public static function dropDown(
        FormOptions $options,
        FormChoicesInterface $choices,
        FieldMatchConditional $conditions = null,
        $multiple = false
    ): array {
        $result = [
            "description" => $options->getDescription(),
            "label" => $options->getLabel(),
            "inputType" => self::DROPDOWN_TYPE,
            "placeholder" => $options->getPlaceHolder(),
            "choices" => $choices->getChoices(),
            "multiple" => $multiple,
        ];

        if ($conditions) {
            $result["conditions"] = [$conditions->getCondition()];
        }

        return $result;
    }

    /**
     * Text box form element schema.
     *
     * @param FormOptions $options
     * @param string $type "text", "number" or "textarea".
     * @param FieldMatchConditional|null $conditions
     * @return array
     */
    public static function textBox(
        FormOptions $options,
        string $type = "text",
        FieldMatchConditional $conditions = null
    ): array {
        $result = [
            "description" => $options->getDescription(),
            "label" => $options->getLabel(),
            "inputType" => self::TEXT_TYPE,
            "placeholder" => $options->getPlaceHolder(),
            "type" => $type,
        ];

        if ($conditions) {
            $result["conditions"] = [$conditions->getCondition()];
        }

        return $result;
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
        string $language = "text/html",
        ?string $jsonSchemaUri = null
    ): array {
        return [
            "description" => $options->getDescription(),
            "label" => $options->getLabel(),
            "inputType" => self::CODE_EDITOR_TYPE,
            "placeholder" => $options->getPlaceHolder(),
            "language" => $language,
            "jsonSchemaUri" => $jsonSchemaUri,
        ];
    }

    /**
     * Toggle form element schema.
     *
     * @param FormOptions $options
     * @param FieldMatchConditional|null $conditions
     * @return array
     */
    public static function toggle(FormOptions $options, FieldMatchConditional $conditions = null)
    {
        $result = [
            "description" => $options->getDescription(),
            "label" => $options->getLabel(),
            "inputType" => self::TOGGLE_TYPE,
            "tooltip" => $options->getTooltip(),
        ];

        if ($conditions) {
            $result["conditions"] = [$conditions->getCondition()];
        }

        return $result;
    }

    /**
     * Checkbox form element schema.
     *
     * @param FormOptions $options
     * @param FieldMatchConditional|null $conditions
     * @param string|null $labelType
     * @return array
     */
    public static function checkBox(FormOptions $options, FieldMatchConditional $conditions = null, $labelType = null)
    {
        $result = [
            "description" => $options->getDescription(),
            "label" => $options->getLabel(),
            "inputType" => self::CHECKBOX_TYPE,
            "labelType" => $labelType,
        ];

        if ($conditions) {
            $result["conditions"] = [$conditions->getCondition()];
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
    public static function radio(
        FormOptions $options,
        FormChoicesInterface $choices,
        FieldMatchConditional $conditions = null
    ) {
        $result = [
            "description" => $options->getDescription(),
            "label" => $options->getLabel(),
            "inputType" => self::RADIO_TYPE,
            "choices" => $choices->getChoices(),
        ];

        if ($conditions) {
            $result["conditions"] = [$conditions->getCondition()];
        }

        return $result;
    }

    /**
     * Used for rendering custom react form controls.
     *
     * @param FormOptions $options
     * @param Schema $itemSchema Schema representing a single item in the drag and drop.
     *
     * @return array
     */
    public static function dragAndDrop(FormOptions $options, Schema $itemSchema): array
    {
        return [
            "inputType" => self::DRAG_AND_DROP_TYPE,
            "description" => $options->getDescription(),
            "label" => $options->getLabel(),
            "itemSchema" => $itemSchema,
            "fullSize" => true,
        ];
    }

    /**
     * Used for rendering react color picker.
     *
     * @param FormOptions $options
     * @param FieldMatchConditional|null $conditions
     * @param string|null $defaultBackground
     *
     * @return array
     */
    public static function color(
        FormOptions $options,
        FieldMatchConditional $conditions = null,
        $defaultBackground = null
    ): array {
        $result = [
            "description" => $options->getDescription(),
            "label" => $options->getLabel(),
            "inputType" => self::COLOR_TYPE,
            "placeholder" => $options->getPlaceHolder(),
        ];

        if ($defaultBackground) {
            $result["defaultBackground"] = $defaultBackground;
        }

        return $result;
    }

    /**
     * Used upload inputs.
     *
     * @param FormOptions $options
     * @param ?FieldMatchConditional $conditions
     * @return array
     */
    public static function upload(FormOptions $options, FieldMatchConditional $conditions = null): array
    {
        $result = [
            "description" => $options->getDescription(),
            "label" => $options->getLabel(),
            "inputType" => self::UPLOAD,
            "placeholder" => $options->getPlaceHolder(),
            "tooltip" => $options->getTooltip(),
        ];

        if ($conditions) {
            $result["conditions"] = [$conditions->getCondition()];
        }

        return $result;
    }
}
