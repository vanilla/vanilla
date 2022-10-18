<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Vanilla\Widgets\TabWidgetModule;

/**
 * Class TabsWidget
 */
class TabsWidget extends TabWidgetModule
{
    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "tabs";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Tabs";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/tabs.svg";
    }
}
