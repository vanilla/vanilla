<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Widgets;

/**
 * Interface for a widget which requires an additional check before it can be used.
 */
interface ToggledWidgetInterface
{
    /**
     * Returns true if the widget can be used.
     *
     * @return bool
     */
    public static function isEnabled(): bool;
}
