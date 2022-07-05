<?php
/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Garden\Schema\Schema;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Layout\Section\SectionFullWidth;

/**
 * Class BannerContentWidget
 */
class BannerContentWidget extends BannerFullWidget
{
    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "Content Banner";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string
    {
        return "app.content-banner";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/contentbanner.svg";
    }

    /**
     * Only allow placement in a full width section.
     */
    public static function getAllowedSectionIDs(): array
    {
        return [SectionFullWidth::getWidgetID()];
    }

    /**
     * @inheritDoc
     */
    public static function getComponentName(): string
    {
        return "BannerContentWidget";
    }

    public static function getWidgetSchema(): Schema
    {
        $schema = parent::getWidgetSchema()->getSchemaArray();
        unset($schema["properties"]["showSearch"]);
        unset($schema["properties"]["searchPlacement"]);
        $schema["properties"]["showTitle"]["default"] = false;
        $schema["properties"]["showTitle"]["x-control"] = SchemaForm::toggle(new FormOptions("Title"));
        $schema["properties"]["title"]["label"] = "";
        $showTitleConditions = [
            (new FieldMatchConditional(
                "showTitle",
                Schema::parse([
                    "type" => "boolean",
                    "const" => true,
                    "default" => false,
                ])
            ))->getCondition(),
        ];
        $schema["properties"]["title"]["x-control"]["conditions"] = $showTitleConditions;
        $schema["properties"]["textColor"]["x-control"]["conditions"] = $showTitleConditions;
        $schema["properties"]["alignment"]["x-control"]["conditions"] = $showTitleConditions;
        $schema["properties"]["showDescription"]["default"] = false;
        $schema["properties"]["description"]["x-control"]["conditions"] = [
            (new FieldMatchConditional(
                "showDescription",
                Schema::parse([
                    "type" => "boolean",
                    "const" => true,
                    "default" => false,
                ])
            ))->getCondition(),
        ];
        return new Schema($schema);
    }
}
