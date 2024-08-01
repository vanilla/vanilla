<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Vanilla\Layout\Section\SectionOneColumn;
use Vanilla\Layout\Section\SectionThreeColumns;
use Vanilla\Layout\Section\SectionThreeColumnsEven;
use Vanilla\Layout\Section\SectionTwoColumns;
use Vanilla\Layout\Section\SectionTwoColumnsEven;

/**
 * All placement in all sections except for full width.
 */
trait DefaultSectionTrait
{
    /**
     * Allows all sections except for full width.
     *
     */
    public static function getAllowedSectionIDs(): array
    {
        return [
            SectionOneColumn::getWidgetID(),
            SectionTwoColumns::getWidgetID(),
            SectionThreeColumns::getWidgetID(),
            SectionThreeColumnsEven::getWidgetID(),
            SectionTwoColumnsEven::getWidgetID(),
        ];
    }
}
