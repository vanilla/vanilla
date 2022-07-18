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
 * Widget representing a section that extends to the edge of the page.
 */
class SectionFullWidth extends AbstractLayoutSection
{
    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "SectionFullWidth";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        return Schema::parse([
            "children?" => new ReactChildrenSchema("The contents of the section."),
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Full Width";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "section.full-width";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/sectionIcons/fullwidth.svg";
    }
}
