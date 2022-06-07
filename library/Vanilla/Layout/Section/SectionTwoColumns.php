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
 * Widget representing a 2 column layout.
 */
class SectionTwoColumns extends AbstractLayoutSection
{
    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "SectionTwoColumns";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        return Schema::parse([
            "isInverted?" => [
                "type" => "boolean",
                "default" => false,
                "description" => 'If "true", places the secondary column to the left.',
            ],
            "mainTop?" => new ReactChildrenSchema(),
            "mainBottom?" => new ReactChildrenSchema(),
            "secondaryTop?" => new ReactChildrenSchema(),
            "secondaryBottom?" => new ReactChildrenSchema(),
            "breadcrumbs?" => new ReactChildrenSchema(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "2 Columns";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "section.2-columns";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/sectionIcons/2columnu.svg";
    }
}
