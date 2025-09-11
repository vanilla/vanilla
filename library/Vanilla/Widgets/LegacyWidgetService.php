<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets;

use Vanilla\Contracts\Addons\WidgetInterface;
use Vanilla\Layout\LayoutHydrator;
use Webmozart\Assert\Assert;

/**
 * Class WidgetService
 *
 * @package Vanilla\Models
 *
 * @deprecated Used for legacy widgets with pockets. You probably want {@link LayoutHydrator} instead.
 */
class LegacyWidgetService
{
    /** @var LegacyWidgetFactory[] */
    public $widgetFactories = [];

    /**
     * Register widgets.
     *
     * @param string $widgetClass
     */
    public function registerWidget(string $widgetClass)
    {
        Assert::classExists($widgetClass);
        Assert::isAOf($widgetClass, WidgetInterface::class);

        /** @var WidgetInterface $widgetClass */
        $this->widgetFactories[$widgetClass::getWidgetID()] = new LegacyWidgetFactory($widgetClass);
    }

    /**
     * Delete a widget.
     *
     * @param string $widgetID
     */
    public function unregisterWidget(string $widgetID)
    {
        if ($this->widgetFactories[$widgetID]) {
            unset($this->widgetFactories[$widgetID]);
        }
    }

    /**
     * Get a widget by name.
     *
     * @param string|null $widgetID
     * @return LegacyWidgetFactory
     */
    public function getFactoryByID(?string $widgetID): ?LegacyWidgetFactory
    {
        if (!$widgetID) {
            return null;
        }
        $factory = $this->widgetFactories[$widgetID] ?? null;
        if (!$factory) {
            return null;
        }

        return $factory;
    }

    /**
     * Get all registered widgets.
     *
     * @return LegacyWidgetFactory[]
     */
    public function getFactories(): array
    {
        $widgets = array_values($this->widgetFactories);
        usort($widgets, function (LegacyWidgetFactory $a, LegacyWidgetFactory $b) {
            return $a->getName() <=> $b->getName();
        });
        return $widgets;
    }
}
