<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Section;

use Garden\Schema\Schema;
use Vanilla\Widgets\Schema\ReactChildrenSchema;

/**
 * Widget representing a single column section.
 */
class SectionOneColumn extends AbstractLayoutSection
{
    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "SectionOneColumn";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        return Schema::parse([
            "children?" => new ReactChildrenSchema("The contents of the section."),
            "isNarrow:b?" => [
                "default" => false,
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "1 Column";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "section.1-column";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/sectionIcons/1column.svg";
    }
}
