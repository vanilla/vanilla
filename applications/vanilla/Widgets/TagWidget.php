<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\LegacyReactModule;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

/**
 * Class TagWidget
 */
class TagWidget extends LegacyReactModule
{
    use HomeWidgetContainerSchemaTrait;

    /**
     * DI.
     */
    public function __construct(private \TagsApiController $apiClient)
    {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Tag Cloud";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetGroup(): string
    {
        return "Community";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "tag";
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "TagWidget";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/tagcloud.svg";
    }

    /**
     * Get the schema for itemOptions.
     *
     * @return Schema
     */
    private static function getItemSchema(): Schema
    {
        return Schema::parse([
            "itemOptions?" => [
                "description" => "Configure various item options",
                "type" => "object",
                "properties" => [
                    "tagPreset:s" => [
                        "description" => "Collections of styles predefined for tags.",
                        "enum" => ["STANDARD", "PRIMARY", "GREYSCALE", "COLORED"],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get the schema for limit.
     *
     * @return Schema
     */
    private static function getLimitSchema(): Schema
    {
        return Schema::parse([
            "limit" => [
                "type" => "integer",
                "description" => t("Desired number of items."),
                "minimum" => 1,
                "maximum" => 100,
                "step" => 1,
                "default" => 10,
                "x-control" => SchemaForm::textBox(
                    new FormOptions(
                        t("Limit"),
                        t("Choose how many records to display."),
                        "",
                        t("Up to a maximum of 100 items may be displayed.")
                    ),
                    "number"
                ),
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        $schema = SchemaUtils::composeSchemas(
            self::widgetTitleSchema(),
            self::widgetSubtitleSchema("subtitle"),
            self::widgetDescriptionSchema(),
            \TagModule::getWidgetSchema(),
            self::getLimitSchema(),
            self::containerOptionsSchema("containerOptions", [
                "outerBackground?",
                "innerBackground?",
                "borderType?",
                "headerAlignment?",
            ]),
            self::getItemSchema()
        );

        return $schema;
    }

    /**
     * @inheritdoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        $result = $this->renderWidgetContainerSeoContent($props, $this->renderSeoLinkList($props["tags"]));
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getProps(): ?array
    {
        $limit = $this->props["limit"] ?? 20;
        $tags = $this->apiClient->index([
            "sort" => "-countDiscussions",
            "excludeNoCountDiscussion" => true,
            "type" => [""],
            "limit" => $limit,
        ]);

        if (count($tags) === 0) {
            return null;
        }

        return array_merge($this->props, [
            "tags" => $tags,
        ]);
    }
}
