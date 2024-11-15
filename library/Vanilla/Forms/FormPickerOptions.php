<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forms;

/**
 * Class description options for various types of form pickers.
 */
class FormPickerOptions implements FormChoicesInterface
{
    private array $options = [];

    private function __construct()
    {
    }

    public static function create(): FormPickerOptions
    {
        return new FormPickerOptions();
    }

    /**
     * Add an option.
     *
     * @param string $label
     * @param string|int $value
     * @param string|null $description
     * @param string|null $tooltip
     * @return $this
     */
    public function option(
        string $label,
        string|int $value,
        string|null $description = null,
        string|null $tooltip = null
    ): FormPickerOptions {
        $this->options[] = [
            "label" => $label,
            "value" => $value,
            "description" => $description,
            "tooltip" => $tooltip,
        ];
        return $this;
    }

    /**
     * @return array<array{label: string, value: string, description: string|null, tooltip: string|null}>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @inheritDoc
     */
    public function getChoices(): array
    {
        $options = [];
        $tooltipsPerOption = [];
        $notesPerOption = [];

        foreach ($this->options as $option) {
            $value = $option["value"];
            $options[$value] = $option["label"];

            if ($tooltip = $option["tooltip"] ?? null) {
                $tooltipsPerOption[$value] = $tooltip;
            }

            if ($note = $option["description"] ?? null) {
                $notesPerOption[$value] = $note;
            }
        }

        return [
            "staticOptions" => $options,
            "tooltipsPerOption" => $tooltipsPerOption,
            "notesPerOption" => $notesPerOption,
        ];
    }
}
