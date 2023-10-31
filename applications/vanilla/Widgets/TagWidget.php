<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\ApiUtils;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Class TagWidget
 */
class TagWidget extends AbstractReactModule implements ReactWidgetInterface, CombinedPropsWidgetInterface
{
    use CombinedPropsWidgetTrait;
    use HomeWidgetContainerSchemaTrait;

    /** @var \TagsApiController */
    private $apiClient;

    /**
     * DI.
     *
     * @param \TagsApiController $apiClient
     */
    public function __construct(\TagsApiController $apiClient)
    {
        $this->apiClient = $apiClient;
        parent::__construct();
    }

    /**
     * @inheridoc
     */
    public static function getWidgetName(): string
    {
        return "Tag Cloud";
    }

    /**
     * @inheridoc
     */
    public static function getWidgetID(): string
    {
        return "tag";
    }

    /**
     * @inheridoc
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
     * @inheridoc
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
     * @inheritDoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        $result = $this->renderWidgetContainerSeoContent($props, $this->renderSeoLinkList($props["tags"]));
        return $result;
    }

    /**
     * @inheridoc
     */
    public function getProps(): ?array
    {
        $limit = $this->props["limit"] ?? 20;
        $tags = $this->apiClient->index([
            "sort" => "-countDiscussions",
            "excludeNoCountDiscussion" => true,
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
