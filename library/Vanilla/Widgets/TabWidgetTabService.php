<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets;

/**
 * Service for collecting registrations of TabWidgetTabFactory.
 */
class TabWidgetTabService
{
    /** @var AbstractTabWidgetTabFactory[] */
    private $tabFactories = [];

    /**
     * Register a tab factory.
     *
     * @param AbstractTabWidgetTabFactory $tabFactory
     */
    public function registerTabFactory(AbstractTabWidgetTabFactory $tabFactory): void
    {
        $this->tabFactories[$tabFactory->getTabPresetID()] = $tabFactory;
    }

    /**
     * Get all tab factories.
     *
     * @return AbstractTabWidgetTabFactory[]
     */
    public function getTabFactories(): array
    {
        return $this->tabFactories;
    }

    /**
     * Get all tab factories.
     *
     * @return AbstractTabWidgetTabFactory[]
     */
    public function getDefaultFactories(): array
    {
        $defaults = [];
        foreach ($this->getTabFactories() as $key => $factory) {
            if ($factory->isDefault()) {
                $defaults[$key] = $factory;
            }
        }
        return $defaults;
    }

    /**
     * Locate a tab factory by it's widget name.
     *
     * @param string $tabPresetID
     *
     * @return AbstractTabWidgetTabFactory|null
     */
    public function findTabFactory(string $tabPresetID): ?AbstractTabWidgetTabFactory
    {
        return $this->tabFactories[$tabPresetID] ?? null;
    }
}
