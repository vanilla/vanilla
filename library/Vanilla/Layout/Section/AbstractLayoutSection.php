<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Section;

use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Interface representing a layout section.
 */
abstract class AbstractLayoutSection implements ReactWidgetInterface, CombinedPropsWidgetInterface
{
    use CombinedPropsWidgetTrait;

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "";
    }

    /**
     * We don't care about this here.
     *
     * @return array
     */
    public static function getAllowedSectionIDs(): array
    {
        return [];
    }
}
