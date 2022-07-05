<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Vanilla\Layout\Section\SectionFullWidth;
use Vanilla\Layout\Section\SectionOneColumn;
use Vanilla\Layout\Section\SectionThreeColumns;
use Vanilla\Layout\Section\SectionTwoColumns;

/**
 * All placement in all sections.
 */
trait AllSectionTrait
{
    /**
     * Allows all sections.
     *
     */
    public static function getAllowedSectionIDs(): array
    {
        return [
            SectionOneColumn::getWidgetID(),
            SectionTwoColumns::getWidgetID(),
            SectionThreeColumns::getWidgetID(),
            SectionFullWidth::getWidgetID(),
        ];
    }
}
