<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Class TagWidget
 */
class TagWidget extends AbstractReactModule implements ReactWidgetInterface, CombinedPropsWidgetInterface {

    use CombinedPropsWidgetTrait, HomeWidgetContainerSchemaTrait;

    /** @var \TagsApiController */
    private $apiClient;

    /**
     * DI.
     *
     * @param \TagsApiController $apiClient
     */
    public function __construct(\TagsApiController $apiClient) {
        $this->apiClient = $apiClient;
        parent::__construct();
    }

    /**
     * @inheridoc
     */
    public static function getWidgetName(): string {
        return "Tag";
    }

    /**
     * @inheridoc
     */
    public static function getWidgetID(): string {
        return "tag";
    }

    /**
     * @inheridoc
     */
    public function getComponentName(): string {
        return "TagWidget";
    }

    /**
     * Get the schema for itemOptions.
     *
     * @return Schema
     */
    private static function getItemSchema(): Schema {
        return Schema::parse([
            "itemOptions?" => [
                "description" => "Configure various item options",
                "type" => "object",
                "properties" => [
                    "tagPreset:s" => [
                        "description" => "Collections of styles predefined for tags.",
                        "enum" => ["STANDARD", "PRIMARY", "GREYSCALE", "COLORED"]
                    ]
                ]
            ]
        ]);
    }

    /**
     * @inheridoc
     */
    public static function getWidgetSchema(): Schema {
        $schema = SchemaUtils::composeSchemas(
            self::widgetTitleSchema(),
            self::widgetSubtitleSchema("subtitle"),
            self::widgetDescriptionSchema(),
            self::containerOptionsSchema("containerOptions", [
                'outerBackground?', 'innerBackground?', 'borderType?', 'headerAlignment?'
                ]),
            self::getItemSchema(),
            \TagModule::getWidgetSchema()
        );
        return $schema;
    }

    /**
     * @inheridoc
     */
    public function getProps(): ?array {
        $tags = $this->apiClient->index(["sort" => "countDiscussions"]);

        if (count($tags) === 0) {
            return null;
        }

        return array_merge($this->props, [
            "tags" => $tags
        ]);
    }
}
