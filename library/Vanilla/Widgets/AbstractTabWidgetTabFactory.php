<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets;

use Vanilla\Web\JsInterpop\LegacyReactModule;

/**
 * Abstract class implementing a factory of tab widgets.
 */
abstract class AbstractTabWidgetTabFactory
{
    /**
     * Get the default label for the tab.
     *
     * @return string
     */
    public function getDefaultTabLabelCode(): string
    {
        /** @var LegacyReactModule $class */
        $class = static::getWidgetClass();
        return $class::getWidgetName();
    }

    /**
     * Create a configured instance of the module.
     *
     * @return LegacyReactModule
     */
    public function getTabModule(): LegacyReactModule
    {
        /** @var LegacyReactModule $module */
        $module = \Gdn::getContainer()->get(static::getWidgetClass());
        return $module;
    }

    /**
     * Get an identifier for the tab factory.
     *
     * @return string
     */
    public function getTabPresetID(): string
    {
        $tabCode = $this->getDefaultTabLabelCode();
        $id = slugify($tabCode);
        return $id;
    }

    /**
     * Get the name of the widget that is to be created by the factory.
     *
     * @psalm-return class-string<LegacyReactModule>
     * @return string
     */
    abstract public function getWidgetClass(): string;

    /**
     * Should this appear as a default tab?
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return false;
    }
}
