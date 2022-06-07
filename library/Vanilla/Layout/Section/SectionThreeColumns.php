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
 * Widget representing a 3 column layout.
 */
class SectionThreeColumns extends AbstractLayoutSection
{
    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "SectionThreeColumns";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        return Schema::parse([
            "leftTop?" => new ReactChildrenSchema(),
            "leftBottom?" => new ReactChildrenSchema(),
            "middleTop?" => new ReactChildrenSchema(),
            "middleBottom?" => new ReactChildrenSchema(),
            "rightTop?" => new ReactChildrenSchema(),
            "rightBottom?" => new ReactChildrenSchema(),
            "breadcrumbs?" => new ReactChildrenSchema(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "3 Columns";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "section.3-columns";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/sectionIcons/3column.svg";
    }
}
