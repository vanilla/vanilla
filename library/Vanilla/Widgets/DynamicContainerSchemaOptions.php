<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets;

use Vanilla\Forms\FormPickerOptions;
use Vanilla\Widgets\React\BannerFullWidget;

/**
 * Instance Class to hold dynamic configuration for {@link HomeWidgetContainerSchemaTrait::class} static methods.
 */
class DynamicContainerSchemaOptions
{
    public FormPickerOptions $titleChoices;
    public FormPickerOptions $descriptionChoices;
    public FormPickerOptions $imageSourceChoices;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    /** @var string|null */
    private string|null $layoutViewType = null;

    /**
     * @return DynamicContainerSchemaOptions
     */
    public static function instance(): DynamicContainerSchemaOptions
    {
        return \Gdn::getContainer()->get(self::class);
    }

    /**
     * @return string|null
     */
    public function getLayoutViewType(): ?string
    {
        return $this->layoutViewType;
    }

    /**
     * @param string|null $layoutViewType
     * @return void
     */
    public function setLayoutViewType(?string $layoutViewType): void
    {
        $this->layoutViewType = $layoutViewType;
    }

    /**
     * Add a dynamic choice to use in {@link HomeWidgetContainerSchemaTrait::widgetTitleSchema()}.
     *
     * @param string $value
     * @param string $label
     * @return void
     *
     * @deprecated Use {@link self::$titleChoices}
     */
    public function addTitleChoice(string $value, string $label): void
    {
        $this->titleChoices->option($label, $value);
    }

    /**
     * Add a dynamic choice to use in {@link HomeWidgetContainerSchemaTrait::widgetDescriptionSchema()}.
     *
     * @param string $value
     * @param string $label
     * @return void
     *
     * @deprecated Use {@link self::$descriptionChoices}
     */
    public function addDescriptionChoice(string $value, string $label): void
    {
        $this->descriptionChoices->option($label, $value);
    }

    /**
     * @return FormPickerOptions
     */
    public function getTitleChoices(): FormPickerOptions
    {
        return clone $this->titleChoices;
    }

    /**
     * @return FormPickerOptions
     */
    public function getDescriptionChoices(): FormPickerOptions
    {
        return clone $this->descriptionChoices;
    }

    /**
     * Add a dynamic choice to use in {@link BannerFullWidget::getBackgroundSchema()}.
     *
     * @param string $value
     * @param string $label
     * @return void
     * @deprecated Use {@link self::$imageSourceChoices}
     **/
    public function addImageSourceChoice(string $value, string $label): void
    {
        $this->imageSourceChoices->option($label, $value);
    }

    /**
     * Get dynamic choices to be used in {@link BannerFullWidget::getBackgroundSchema()}.
     *
     * @return FormPickerOptions
     */
    public function getImageSourceChoices(): FormPickerOptions
    {
        return clone $this->imageSourceChoices;
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->titleChoices = FormPickerOptions::create();
        $this->descriptionChoices = FormPickerOptions::create();
        $this->imageSourceChoices = FormPickerOptions::create();
    }
}
