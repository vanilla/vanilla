<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets;

/**
 * Interfacing something that can have an item limit.
 */
interface LimitableWidgetInterface
{
    /**
     * Apply a limit to the number of items.
     *
     * @param int $limit
     */
    public function setLimit(int $limit);
}
