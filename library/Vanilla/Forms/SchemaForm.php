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
    const RADIO_PICKER_TYPE = "radioPicker";
    const CHECKBOX_TYPE = "checkBox";
    const TEXT_TYPE = "textBox";
    const RICHEDITOR_TYPE = "richeditor";
    const TOGGLE_TYPE = "toggle";
    const DRAG_AND_DROP_TYPE = "dragAndDrop";
    const CODE_EDITOR_TYPE = "codeBox";
    const COLOR_TYPE = "color";
    const UPLOAD = "upload";
    const DATE_PICKER = "datePicker";
    const TIME_DURATION = "timeDuration";

    const RADIO_PICKER = "radioPicker";

    /**
     * Create a "section" of the form on an object type.
     *
     * @param FormOptions $options
     * @param FieldMatchConditional|null $conditions,
     *
     * @return array
     */
    public static function section(FormOptions $options, ?FieldMatchConditional $conditions = null): array
    {
        $result = [
            "label" => $options->getLabel(),
            "description" => $options->getDescription(),
        ];
        if ($conditions) {
            $result["conditions"] = [$conditions->getCondition()];
        }
        return $result;
    }

    /**
     * Drop down form element schema.
     *
     * @param FormOptions $options
     * @param FormChoicesInterface $choices
     * @param FieldMatchConditional|null $conditions
     * @param bool $multiple
     * @return array
     */
    public static function dropDown(
        FormOptions $options,
        FormChoicesInterface $choices,
        FieldMatchConditional $conditions = null,
        bool $multiple = false
    ): array {
        $result = array_merge(
            [
                "description" => $options->getDescription(),
                "label" => $options->getLabel(),
                "inputType" => "select",
                "placeholder" => $options->getPlaceHolder(),
                "multiple" => $multiple,
                "tooltip" => $options->getTooltip(),
            ],
            $choices->getOptionsData()
        );

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
            "tooltip" => $options->getTooltip(),
        ];

        if ($conditions) {
            $result["conditions"] = [$conditions->getCondition()];
        }

        return $result;
    }

    /**
     * Rich text box form element schema.
     *
     * @param FormOptions $options
     * @param string $legend
     * @param FieldMatchConditional|null $conditions
     * @return array
     */
    public static function richTextBox(
        FormOptions $options,
        string $legend = "",
        FieldMatchConditional $conditions = null
    ): array {
        $result = [
            "description" => $options->getDescription(),
            "label" => $options->getLabel(),
            "inputType" => self::RICHEDITOR_TYPE,
            "placeholder" => $options->getPlaceHolder(),
            "legend" => $legend,
            "tooltip" => $options->getTooltip(),
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
        $result = $options->values() + [
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
     * @param ?array $tooltipsPerOption
     *
     * @return array
     */
    public static function radio(
        FormOptions $options,
        FormChoicesInterface $choices,
        FieldMatchConditional $conditions = null,
        array $tooltipsPerOption = null
    ) {
        $result = $options->values() + [
            "inputType" => self::RADIO_TYPE,
            "choices" => $choices->getChoices(),
            "tooltipsPerOption" => $tooltipsPerOption,
        ];

        if ($conditions) {
            $result["conditions"] = [$conditions->getCondition()];
        }

        return $result;
    }

    /**
     * Radio form element as dropdown picker.
     *
     * @param FormOptions $options
     * @param FormPickerOptions $pickerOptions
     * @param ?FieldMatchConditional $conditional
     *
     * @return array
     */
    public static function radioPicker(
        FormOptions $options,
        FormPickerOptions $pickerOptions,
        FieldMatchConditional $conditional = null
    ): array {
        $result = $options->values() + [
            "inputType" => self::RADIO_PICKER_TYPE,
            "options" => $pickerOptions->getOptions(),
            "labelType" => "standard",
        ];

        if ($conditional) {
            $result["conditions"] = [$conditional->getCondition()];
        }
        return $result;
    }

    /**
     * Render a custom react component.
     *
     * @param FormOptions $options
     * @param string $reactComponent
     * @param array|null $componentProps
     * @param FieldMatchConditional|null $conditional
     *
     * @return array
     */
    public static function custom(
        FormOptions $options,
        string $reactComponent,
        ?array $componentProps = null,
        FieldMatchConditional $conditional = null
    ): array {
        $result = $options->values() + [
            "inputType" => "custom",
            "component" => $reactComponent,
            "componentProps" => $componentProps,
        ];
        if ($conditional) {
            $result["conditions"] = [$conditional->getCondition()];
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
    public static function dragAndDrop(
        FormOptions $options,
        Schema $itemSchema,
        FormModalOptions|null $modal = null
    ): array {
        $result = $options->values() + [
            "inputType" => self::DRAG_AND_DROP_TYPE,
            "itemSchema" => $itemSchema,
        ];

        if ($modal !== null) {
            $result["asModal"] = true;
            $result["modalTitle"] = $modal->title;
            $result["modalSubmitLabel"] = $modal->submitLabel;
        } else {
            $result["fullSize"] = true;
        }
        return $result;
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

        if ($conditions) {
            $result["conditions"] = [$conditions->getCondition()];
        }

        return $result;
    }

    /**
     * Used for upload inputs.
     *
     * @param FormOptions $options
     * @param FieldMatchConditional|Array<FieldMatchConditional>|null $conditions
     * @return array
     */
    public static function upload(FormOptions $options, FieldMatchConditional|array $conditions = null): array
    {
        $result = [
            "description" => $options->getDescription(),
            "label" => $options->getLabel(),
            "inputType" => self::UPLOAD,
            "placeholder" => $options->getPlaceHolder(),
            "tooltip" => $options->getTooltip(),
        ];

        if ($conditions) {
            $conditions = is_array($conditions)
                ? array_map(fn(FieldMatchConditional $condition) => $condition->getCondition(), $conditions)
                : [$conditions->getCondition()];
            $result["conditions"] = $conditions;
        }

        return $result;
    }

    /**
     * Used for date picker inputs (rendered through react only).
     *
     * @param FormOptions $options
     * @param ?FieldMatchConditional $conditions
     * @return array
     */
    public static function datePicker(FormOptions $options, FieldMatchConditional $conditions = null): array
    {
        $result = [
            "description" => $options->getDescription(),
            "label" => $options->getLabel(),
            "inputType" => self::DATE_PICKER,
            "placeholder" => $options->getPlaceHolder(),
            "tooltip" => $options->getTooltip(),
        ];

        if ($conditions) {
            $result["conditions"] = [$conditions->getCondition()];
        }

        return $result;
    }

    /**
     * Used for time duration inputs (rendered through react only).
     *
     * @param FormOptions $options
     * @param ?FieldMatchConditional $conditions
     * @param ?array $supportedUnits
     * @return array
     */
    public static function timeDuration(
        FormOptions $options,
        FieldMatchConditional $conditions = null,
        null|array $supportedUnits = null
    ): array {
        $result = [
            "description" => $options->getDescription(),
            "label" => $options->getLabel(),
            "inputType" => self::TIME_DURATION,
            "placeholder" => $options->getPlaceHolder(),
            "tooltip" => $options->getTooltip(),
        ];

        if ($supportedUnits) {
            $result["supportedUnits"] = $supportedUnits;
        }

        if ($conditions) {
            $result["conditions"] = [$conditions->getCondition()];
        }

        return $result;
    }
}
