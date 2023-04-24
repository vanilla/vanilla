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
use Vanilla\Http\InternalClient;
use Vanilla\InjectableInterface;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\React\DefaultSectionTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;
use Vanilla\Widgets\WidgetSchemaTrait;

/**
 * Widget to display featured content in custom layouts
 */
class FeaturedCollectionsWidget implements ReactWidgetInterface, CombinedPropsWidgetInterface, InjectableInterface
{
    use DefaultSectionTrait;
    use CombinedPropsWidgetTrait;
    use HomeWidgetContainerSchemaTrait;
    use WidgetSchemaTrait;

    /** @var CollectionsApiController */
    private $api;

    /**
     * DI.
     *
     * @param CollectionsApiController $api
     */
    public function setDependencies(CollectionsApiController $api)
    {
        $this->api = $api;
    }

    /**
     * @inheritDoc
     */
    public static function getComponentName(): string
    {
        return "FeaturedCollectionsWidget";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "Featured Collections";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string
    {
        return "featuredcollections";
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
}
