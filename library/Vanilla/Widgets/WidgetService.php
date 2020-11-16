<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets;

use Vanilla\Contracts\Addons\WidgetInterface;
use Webmozart\Assert\Assert;

/**
 * Class WidgetService
 *
 * @package Vanilla\Models
 */
class WidgetService {

    /** @var WidgetFactory[] */
    public $widgetFactories = [];

    /**
     * Register widgets.
     *
     * @param string $widgetClass
     */
    public function registerWidget(string $widgetClass) {
        Assert::classExists($widgetClass);
        Assert::isAOf($widgetClass, WidgetInterface::class);

        /** @var WidgetInterface $widgetClass */
        $this->widgetFactories[$widgetClass::getWidgetID()] = new WidgetFactory($widgetClass);
    }

    /**
     * Delete a widget.
     *
     * @param string $widgetID
     */
    public function unregisterWidget(string $widgetID) {
        if ($this->widgetFactories[$widgetID]) {
            unset($this->widgetFactories[$widgetID]);
        }
    }

    /**
     * Get a widget by name.
     *
     * @param string|null $widgetID
     * @return WidgetFactory
     */
    public function getFactoryByID(?string $widgetID): ?WidgetFactory {
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
     * @return WidgetFactory[]
     */
    public function getFactories(): array {
        $widgets = array_values($this->widgetFactories);
        usort($widgets, function (WidgetFactory $a, WidgetFactory $b) {
            return $a->getName() <=> $b->getName();
        });
        return $widgets;
    }
}
