<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Web\JsInterpop\AbstractReactModule;

/**
 * Module for rendering multiple modules for tabs.
 *
 * Other modules must have a factory extending TabWidgetTabFactory and be registered with the TabWidgetTabService.
 */
class TabWidgetModule extends AbstractReactModule
{
    /** @var TabWidgetTabService */
    private $tabService;

    /** @var array */
    private $tabConfiguration;

    /** @var int */
    private $limit = 5;

    /** @var int */
    private $defaultTabIndex = 0;

    /**
     * DI.
     *
     * @param TabWidgetTabService $tabService
     */
    public function __construct(TabWidgetTabService $tabService)
    {
        parent::__construct();
        $this->tabService = $tabService;
    }

    /**
     * @inheritDoc
     */
    public function getProps(): ?array
    {
        $tabConfiguration = $this->tabConfiguration ?? self::getDefaultOptions();
        $tabs = [];
        foreach ($tabConfiguration as $tab) {
            if ($tab["isHidden"] ?? false) {
                continue;
            }
            $tabComponent = $tab["tabPresetID"] ?? "";
            $factory = $this->tabService->findTabFactory($tabComponent);
            if ($factory === null) {
                trigger_error("Could not find tab factory for component '$tabComponent'", E_USER_NOTICE);
                continue;
            }

            $tabLabel = $tab["label"] ?? $factory->getDefaultTabLabelCode();
            try {
                $tabWidget = $factory->getTabModule();
                if ($tabWidget instanceof LimitableWidgetInterface) {
                    $tabWidget->setLimit($this->limit);
                }
                $componentName = $tabWidget->getComponentName();
                $props = $tabWidget->getProps();
                if ($props === null) {
                    // User is unable to render this tab.
                    continue;
                }

                $tabs[] = [
                    "label" => $tabLabel,
                    "componentName" => $componentName,
                    "componentProps" => $props,
                ];
            } catch (\Exception $e) {
                // Still try to render the other tabs.
                trigger_error(formatException($e), E_USER_WARNING);
            }
        }

        if (count($tabs) === 0) {
            return null;
        }

        return [
            "tabs" => $tabs,
            "defaultTabIndex" => $this->defaultTabIndex,
        ];
    }

    /**
     * Apply the tab configuration.
     *
     * @param array $tabs
     */
    public function setTabConfiguration(array $tabs): void
    {
        $this->tabConfiguration = $tabs;
    }

    /**
     * Set which tab (in order) is active by default.
     *
     * @param int $defaultTabIndex
     */
    public function setDefaultTabIndex(int $defaultTabIndex): void
    {
        $this->defaultTabIndex = $defaultTabIndex;
    }

    /**
     * Apply preset ids to generate a tab configuration.
     *
     * @param string[] $presetIDs
     */
    public function setTabPresetIDs(array $presetIDs): void
    {
        $options = self::getTabOptions();
        $configuration = [];
        foreach ($presetIDs as $presetID) {
            $matchedOption = $options[$presetID] ?? null;
            if ($matchedOption !== null) {
                $configuration[] = $matchedOption;
            }
        }
        $this->setTabConfiguration($configuration);
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @inheritDoc
     */
    public static function getComponentName(): string
    {
        return "TabWidget";
    }

    /**
     * @return Schema
     */
    public static function getWidgetSchema(): Schema
    {
        $choices = self::getTabChoices();
        $itemSchema = Schema::parse([
            "tabPresetID" => [
                "type" => "string",
                "enum" => array_keys($choices),
                "x-control" => SchemaForm::dropDown(new FormOptions("Content"), new StaticFormChoices($choices)),
            ],
            "label" => [
                "type" => "string",
                "x-control" => SchemaForm::textBox(new FormOptions("Label")),
            ],
            "isHidden:b?",
        ]);

        return Schema::parse([
            "limit:i" => [
                "default" => 5,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Max Items", "Maximum number of items to display in each widget."),
                    new StaticFormChoices([
                        "3" => 3,
                        "5" => 5,
                        "10" => 10,
                    ])
                ),
            ],
            "tabConfiguration" => [
                "type" => "array",
                "items" => $itemSchema->getSchemaArray(),
                "x-control" => SchemaForm::dragAndDrop(
                    new FormOptions(
                        "Edit Tabs",
                        "Select the content you want to add in your Tabs," .
                            " rename if you want to and, drag & drop the order in which the tabs should appear. "
                    ),
                    $itemSchema
                ),
                "default" => array_values(self::getDefaultOptions()),
            ],
        ]);
    }

    /**
     * Get an array of tab options.
     *
     * @return array
     */
    private static function getTabOptions(): array
    {
        // Cheating a little bit here, but it can't be helped. Widget schemas have to be static.
        /** @var TabWidgetTabService $tabService */
        $tabService = \Gdn::getContainer()->get(TabWidgetTabService::class);

        $allFactories = $tabService->getTabFactories();
        $options = array_map([self::class, "transformFactoryToOption"], $allFactories);
        return $options;
    }

    /**
     * Get an array of tab choices.
     *
     * @return array
     */
    private static function getTabChoices(): array
    {
        // Cheating a little bit here, but it can't be helped. Widget schemas have to be static.
        /** @var TabWidgetTabService $tabService */
        $tabService = \Gdn::getContainer()->get(TabWidgetTabService::class);

        $allFactories = $tabService->getTabFactories();
        $choices = [];
        foreach ($allFactories as $factory) {
            $choices[$factory->getTabPresetID()] = $factory->getDefaultTabLabelCode();
        }
        return $choices;
    }

    /**
     * Get an array of default tab options.
     *
     * @return array
     */
    private static function getDefaultOptions(): array
    {
        // Cheating a little bit here, but it can't be helped. Widget schemas have to be static.
        /** @var TabWidgetTabService $tabService */
        $tabService = \Gdn::getContainer()->get(TabWidgetTabService::class);

        $defaultFactories = $tabService->getDefaultFactories();
        $options = array_map([self::class, "transformFactoryToOption"], $defaultFactories);
        return $options;
    }

    /**
     * Transform a tab factory into an option.
     *
     * @param AbstractTabWidgetTabFactory $factory
     *
     * @return array
     */
    private static function transformFactoryToOption(AbstractTabWidgetTabFactory $factory): array
    {
        return [
            "label" => $factory->getDefaultTabLabelCode(),
            "tabPresetID" => $factory->getTabPresetID(),
        ];
    }

    /**
     * @return string
     */
    public static function getWidgetName(): string
    {
        return "Tabs Widget";
    }
}
