<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Forum\Controllers\Api\DiscussionsApiIndexSchema;
use Vanilla\Widgets\Schema\WidgetBackgroundSchema;

/**
 * Abstraction layer to generate schemas for widgets.
 */
trait WidgetSchemaTrait
{
    /**
     * Get the schema for widget item options.
     *
     * @param string $fieldName
     * @param Schema|null $additionalOptions
     * @return Schema
     */
    public static function itemOptionsSchema(
        string $fieldName = "itemOptions",
        Schema $additionalOptions = null
    ): Schema {
        $schema = Schema::parse([
            "imagePlacement:s?" => [
                "enum" => ["left", "top"],
                "description" => "Describe where image will be placed on widget item.",
            ],
            "imagePlacementMobile:s?" => [
                "enum" => ["left", "top"],
                "description" => "Describe where image will be placed on widget item on mobile.",
            ],
            "box?" => Schema::parse([
                "borderType:s?" => [
                    "enum" => self::borderTypeOptions(),
                    "description" => "Describe what type of the border the widget item should have.",
                ],
                "border?" => self::borderSchema("Configure border style."),
                "background?" => new WidgetBackgroundSchema("Background options for the widget item.", false),
                "spacing?" => self::spacingSchema("Configure box internal spacing."),
            ]),
            "contentType:s?" => [
                "enum" => self::contentTypeOptions(),
                "description" => "Describe the widget item display style.",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Image Type", "Configure the image type", "Style Guide Default"),
                    new StaticFormChoices([
                        "title-description" => "None",
                        "title-description-icon" => "Icon",
                        "title-description-image" => "Image",
                        "title-background" => "Background",
                    ])
                ),
            ],
            "fg?" => [
                "type" => "string",
                "description" => "Widget item foreground color.",
            ],
            "display:?" => Schema::parse([
                "name?" => [
                    "type" => "boolean",
                    "default" => true,
                    "description" => "Whether to show widget item name.",
                ],
                "description?" => [
                    "type" => "boolean",
                    "default" => true,
                    "description" => "Whether to show widget item description.",
                ],
                "counts?" => [
                    "type" => "boolean",
                    "default" => true,
                    "description" => "Whether to show widget item counts.",
                ],
                "cta?" => [
                    "type" => "boolean",
                    "description" => "Whether to show widget item CTA.",
                ],
            ]),
            "alignment?" => [
                "enum" => ["center", "left"],
                "description" => "Widget item content alignment",
            ],
            "viewMore?" => Schema::parse([
                "labelCode?" => [
                    "type" => "string",
                    "description" => "Button text/label.",
                ],
                "buttonType:s?" => [
                    "enum" => self::buttonTypeOptions(),
                    "description" => "Button options.",
                ],
            ]),
        ]);

        if ($additionalOptions) {
            $schema = $schema->merge($additionalOptions);
        }

        return Schema::parse([
            "$fieldName?" => $schema
                ->setDescription("Configure various widget item options")
                ->setField("x-control", SchemaForm::section(new FormOptions("Item Options"))),
        ]);
    }

    /**
     * Get the schema for a border style.
     *
     * @param string|null $description
     * @return Schema
     */
    public static function borderSchema(string $description = null): Schema
    {
        $schema = Schema::parse([
            "color?" => [
                "type" => "string",
                "description" => "Border color.",
            ],
            "width?" => [
                "type" => ["number", "string"],
                "description" => "Border width.",
            ],
            "style?" => [
                "type" => "string",
                "description" => "Border style.",
            ],
            "radius?" => [
                "type" => ["number", "string"],
                "description" => "Border radius.",
            ],
        ]);
        if ($description) {
            $schema->setField("description", $description);
        }

        return $schema;
    }

    /**
     * Get the schema for spacing.
     *
     * @return Schema
     */
    public static function spacingSchema(): Schema
    {
        return Schema::parse([
            "top?" => [
                "type" => ["string", "number"],
                "description" => "Top spacing.",
            ],
            "bottom?" => [
                "type" => ["string", "number"],
                "description" => "Bottom spacing.",
            ],
            "left?" => [
                "type" => ["string", "number"],
                "description" => "Left spacing.",
            ],
            "right?" => [
                "type" => ["string", "number"],
                "description" => "Right spacing.",
            ],
            "horizontal?" => [
                "type" => ["string", "number"],
                "description" => "Horizontal spacing (left and right).",
            ],
            "vertical?" => [
                "type" => ["string", "number"],
                "description" => "Vertical spacing (top and bottom).",
            ],
            "all?" => [
                "type" => ["string", "number"],
                "description" => "All spacing (top, right, bottom, left).",
            ],
        ]);
    }

    /**
     * Get an array of the button type options.
     *
     * @return string[]
     */
    public static function buttonTypeOptions(): array
    {
        return ["standard", "primary", "transparent", "translucid", "text", "custom"];
    }

    /**
     * Get an array of the display type options.
     *
     * @return string[]
     */
    public static function contentTypeOptions(): array
    {
        return ["title-description-icon", "title-description-image", "title-background", "title-description"];
    }

    /**
     * Get sort schema.
     *
     * @return Schema
     */
    protected static function sortSchema(): Schema
    {
        return Schema::parse([
            "sort?" => [
                "type" => "string",
                "default" => "-dateLastComment",
                "x-control" => DiscussionsApiIndexSchema::getSortFormOptions(),
            ],
        ]);
    }

    /**
     * Get limit Schema
     *
     * @return Schema
     */
    public static function limitSchema(): Schema
    {
        return Schema::parse([
            "limit?" => [
                "type" => "integer",
                "description" => t("Desired number of items."),
                "minimum" => 1,
                "default" => 10,
                "x-control" => DiscussionsApiIndexSchema::getLimitFormOptions(),
            ],
        ]);
    }

    /**
     * Get limit form options.
     *
     * @param FieldMatchConditional|null $conditional
     * @return array
     */
    public static function getLimitFormOptions(FieldMatchConditional $conditional = null): array
    {
        return SchemaForm::dropDown(
            new FormOptions(t("Limit"), t("Choose how many records to display.")),
            new StaticFormChoices([
                "3" => 3,
                "5" => 5,
                "10" => 10,
            ]),
            $conditional
        );
    }
}
