<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\InjectableInterface;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\React\DefaultSectionTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Class RSSWidget
 */
class RSSWidget implements ReactWidgetInterface, CombinedPropsWidgetInterface, InjectableInterface
{
    use CombinedPropsWidgetTrait;
    use HomeWidgetContainerSchemaTrait;
    use RssWidgetTrait;
    use DefaultSectionTrait;

    /**
     * @inheridoc
     */
    public static function getWidgetName(): string
    {
        return "RSS Feed";
    }

    /**
     * @inheridoc
     */
    public static function getWidgetID(): string
    {
        return "rss";
    }

    /**
     * @inheridoc
     */
    public static function getComponentName(): string
    {
        return "RSSWidget";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/rssfeed.svg";
    }

    /**
     * Get props for component
     *
     * @return array
     */
    public function getProps(): ?array
    {
        $itemData = $this->getRssFeedItems(
            $this->props["apiParams"]["feedUrl"],
            $this->props["apiParams"]["fallbackImageUrl"] ?? null
        );
        if (empty($itemData)) {
            //if there is no valid content then we should prevent the widget from rendering and not throwing errors.
            $this->props = null;
        } else {
            $limit = $this->props["apiParams"]["limit"] ?? null;
            $this->props["itemData"] = count($itemData) > $limit ? array_slice($itemData, 0, $limit) : $itemData;
        }

        return $this->props;
    }

    /**
     * @inheridoc
     */
    public static function getWidgetSchema(): Schema
    {
        $schema = SchemaUtils::composeSchemas(
            self::widgetTitleSchema(),
            self::widgetSubtitleSchema("subtitle"),
            self::widgetDescriptionSchema(),
            Schema::parse([
                "apiParams" => Schema::parse([
                    "feedUrl:s" => [
                        "x-control" => SchemaForm::textBox(new FormOptions("RSS Feed URL", "RSS Feed URL.")),
                    ],
                    "fallbackImageUrl:s?" => [
                        "x-control" => SchemaForm::upload(
                            new FormOptions(
                                "Fallback Image",
                                "Render this image instead if feed entry does not have one.",
                                "",
                                "By default, an SVG image using your brand color displays when there’s nothing else to show. Upload your own image to customize. Recommended size: 1200px by 600px."
                            )
                        ),
                    ],
                    "limit:i?" => [
                        "default" => 10,
                        "x-control" => SchemaForm::dropDown(
                            new FormOptions("Limit", "Maximum amount of items to display."),
                            new StaticFormChoices(array_combine(range(1, 10), range(1, 10)))
                        ),
                    ],
                ]),
            ]),
            self::containerOptionsSchema("containerOptions")
        );

        return $schema;
    }
}
