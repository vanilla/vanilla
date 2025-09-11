<?php
/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Controllers\Api\CollectionsApiController;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\ReactWidget;
use Vanilla\Widgets\WidgetSchemaTrait;

/**
 * Widget to display featured content in custom layouts
 */
class FeaturedCollectionsWidget extends ReactWidget
{
    use HomeWidgetContainerSchemaTrait;
    use WidgetSchemaTrait;

    /**
     * DI.
     */
    public function __construct(private CollectionsApiController $api)
    {
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "FeaturedCollectionsWidget";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Featured Collections";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "featuredcollections";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/featuredcollections.svg";
    }

    /**
     * API Params
     *
     * @return Schema
     */
    public static function getApiSchema(): Schema
    {
        return Schema::parse([
            "collectionID" => [
                "type" => "integer",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Add Collection", "Choose a collection to display"),
                    new ApiFormChoices("/api/v2/collections", "/api/v2/collections/%s", "collectionID", "name")
                ),
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(),
            self::widgetSubtitleSchema("subtitle"),
            self::widgetDescriptionSchema(),
            Schema::parse(["apiParams" => self::getApiSchema()]),
            self::displayOptionsSchema(),
            self::containerOptionsSchema("containerOptions")
        );
    }

    /**
     * Get props from component
     *
     * @param array|null $params
     * @return array|null
     */
    public function getProps(?array $params = null): ?array
    {
        $apiParams = $this->props["apiParams"];
        $collection = $this->api->get_content($apiParams["collectionID"], "en");
        $this->props["collection"] = $collection;
        return $this->props;
    }

    /**
     * @inheritdoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        $result = $this->renderWidgetContainerSeoContent(
            $props,
            $this->renderSeoLinkList(array_column($props["collection"]["records"], "record"))
        );
        return $result;
    }
}
