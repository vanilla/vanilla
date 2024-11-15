<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Dashboard\Models\InterestModel;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Http\InternalClient;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\React\DefaultSectionTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;
use Vanilla\Widgets\ToggledWidgetInterface;

class SuggestedContentWidget implements ReactWidgetInterface, CombinedPropsWidgetInterface, ToggledWidgetInterface
{
    use HomeWidgetContainerSchemaTrait;
    use CombinedPropsWidgetTrait;
    use DefaultSectionTrait;
    use DiscussionsWidgetSchemaTrait;

    /**
     * D.I.
     *
     * @param InternalClient $api
     */
    public function __construct(private InternalClient $api)
    {
    }

    /**
     * @inheritDoc
     */
    public static function getComponentName(): string
    {
        return "SuggestedContentWidget";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetIconPath(): ?string
    {
        return "/applications/dashboard/design/images/widgetIcons/suggested-content.svg";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(defaultValue: "Discover"),
            self::widgetSubtitleSchema("subtitle"),
            self::widgetDescriptionSchema(),

            self::suggestedFollowsSchema(),
            self::suggestedContentSchema(),

            self::optionsSchema(allowedProperties: ["metas?"]),
            self::displayOptionsSchema(),
            self::containerOptionsSchema("containerOptions", viewAll: false)
        );
    }

    /**
     * Common schema for suggested follows and suggested content.
     *
     * @param string $defaultTitle
     * @return Schema
     */
    private static function commonSuggestedSchema(string $defaultTitle)
    {
        return Schema::parse([
            "enabled:b" => [
                "default" => true,
                "x-control" => SchemaForm::toggle(new FormOptions(t("Enabled"))),
            ],
            "title:s?" => [
                "default" => $defaultTitle,
                "x-control" => SchemaForm::textBox(new FormOptions(t("Title"))),
            ],
            "subtitle:s?" => [
                "x-control" => SchemaForm::textBox(new FormOptions(t("Subtitle"))),
            ],
            "limit:i" => [
                "minimum" => 0,
                "maximum" => 20,
                "step" => 1,
                "default" => 3,
                "x-control" => SchemaForm::textBox(new FormOptions(t("Limit")), "number"),
            ],
        ]);
    }

    /**
     * Get schema for suggested follows configuration.
     *
     * @return Schema
     */
    private static function suggestedFollowsSchema(): Schema
    {
        $schema = self::commonSuggestedSchema("Suggested Follows")
            ->setDescription("Configure the Suggested Follows options")
            ->setField("x-control", SchemaForm::section(new FormOptions(t("Suggested Follows"))));
        self::applyFieldConditions($schema, "enabled", "suggestedFollows.");
        return Schema::parse([
            "suggestedFollows?" => $schema,
        ]);
    }

    /**
     * Get schema for suggested content configuration.
     *
     * @return Schema
     */
    private static function suggestedContentSchema(): Schema
    {
        $schema = self::commonSuggestedSchema("Suggested Content")
            ->merge(
                Schema::parse([
                    "excerptLength:i" => [
                        "minimum" => 1,
                        "step" => 1,
                        "default" => 200,
                        "x-control" => SchemaForm::textBox(new FormOptions(t("Excerpt Length")), "number"),
                    ],
                    "featuredImage:b" => [
                        "default" => false,
                        "x-control" => SchemaForm::toggle(new FormOptions(t("Featured Image"))),
                    ],
                    "fallbackImage?" => [
                        "type" => "string",
                        "x-control" => SchemaForm::upload(
                            new FormOptions(t("Fallback Image")),
                            new FieldMatchConditional(
                                "suggestedContent.featuredImage",
                                Schema::parse([
                                    "type" => "boolean",
                                    "const" => true,
                                ])
                            )
                        ),
                    ],
                ])
            )
            ->setDescription("Configure the Suggested Content options")
            ->setField("x-control", SchemaForm::section(new FormOptions(t("Suggested Content"))));
        self::applyFieldConditions($schema, "enabled", "suggestedContent.");
        return Schema::parse([
            "suggestedContent?" => $schema,
        ]);
    }

    /**
     * Applies a field match conditional to all controls which are expanded by a single toggle.
     *
     * @param Schema $schema
     * @param string $toggleField
     * @param string $prefix
     * @return Schema
     */
    private static function applyFieldConditions(Schema $schema, string $toggleField, string $prefix = ""): Schema
    {
        $properties = $schema->getField("properties");
        foreach ($properties as $name => &$property) {
            if ($name === $toggleField) {
                continue;
            }
            if (!key_exists("x-control", $property)) {
                continue;
            }
            if (!key_exists("conditions", $property["x-control"])) {
                $property["x-control"]["conditions"] = [];
            }
            $property["x-control"]["conditions"][] = (new FieldMatchConditional(
                $prefix . $toggleField,
                Schema::parse([
                    "type" => "boolean",
                    "const" => true,
                ])
            ))->getCondition();
        }
        $schema->setField("properties", $properties);
        return $schema;
    }

    /**
     * Get the schema for display options.
     *
     * @param string $fieldName The name of the field.
     *
     * @return Schema
     */
    private static function displayOptionsSchema(string $fieldName = "displayOptions"): Schema
    {
        $propertiesSchema = Schema::parse([
            "excludedCategoryIDs?" => [
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(t("Exclude Categories"), t("Select a category")),
                    new ApiFormChoices(
                        "/api/v2/categories/search?query=%s&limit=30",
                        "/api/v2/categories/%s",
                        "categoryID",
                        "name"
                    ),
                    null,
                    true
                ),
            ],
        ]);

        return Schema::parse([
            "$fieldName?" => $propertiesSchema
                ->setDescription("Configure the display options")
                ->setField("x-control", SchemaForm::section(new FormOptions(t("Display Options")))),
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "Suggested Content";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string
    {
        return "suggestedcontent";
    }

    /**
     * @inheritDoc
     */
    public function getProps(): ?array
    {
        $apiParams = [];
        if ($this->props["suggestedFollows"]["enabled"] && $this->props["suggestedFollows"]["limit"]) {
            $apiParams["suggestedFollowsLimit"] = $this->props["suggestedFollows"]["limit"];
        }
        if ($this->props["suggestedContent"]["enabled"] && $this->props["suggestedContent"]["limit"]) {
            $apiParams["suggestedContentLimit"] = $this->props["suggestedContent"]["limit"];
            $apiParams["suggestedContentExcerptLength"] = $this->props["suggestedContent"]["excerptLength"];
        }

        if (empty($apiParams)) {
            return null;
        }

        $apiParams["excludedCategoryIDs"] = $this->props["excludedCategoryIDs"] ?? [];

        $suggestedContent = $this->api->get("interests/suggested-content", $apiParams)->getBody();
        $this->props = array_merge($this->props, $suggestedContent, ["apiParams" => $apiParams]);

        return $this->props;
    }

    /**
     * @inheritDoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        // todo: return to this
        return "";
    }

    /**
     * @inheritDoc
     */
    public static function isEnabled(): bool
    {
        return InterestModel::isSuggestedContentEnabled();
    }
}
