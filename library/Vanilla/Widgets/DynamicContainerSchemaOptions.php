<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets;

/**
 * Instance Class to hold dynamic configuration for {@link HomeWidgetContainerSchemaTrait::class} static methods.
 */
class DynamicContainerSchemaOptions
{
    /** @var array<string, string> */
    private array $titleChoices = [];

    /** @var array<string, string> */
    private array $descriptionChoices = [];

    /**
     * Add a dynamic choice to use in {@link HomeWidgetContainerSchemaTrait::widgetTitleSchema()}.
     *
     * @param string $value
     * @param string $label
     * @return void
     */
    public function addTitleChoice(string $value, string $label): void
    {
        $this->titleChoices[$value] = $label;
    }

    /**
     * Add a dynamic choice to use in {@link HomeWidgetContainerSchemaTrait::widgetDescriptionSchema()}.
     *
     * @param string $value
     * @param string $label
     * @return void
     */
    public function addDescriptionChoice(string $value, string $label): void
    {
        $this->descriptionChoices[$value] = $label;
    }

    /**
     * @return array<string, string>
     */
    public function getTitleChoices(): array
    {
        return $this->titleChoices;
    }

    /**
     * @return array<string, string>
     */
    public function getDescriptionChoices(): array
    {
        return $this->descriptionChoices;
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->titleChoices = [];
        $this->descriptionChoices = [];
    }
}
