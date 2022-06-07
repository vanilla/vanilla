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
     * @return Schema
     */
    public static function itemOptionsSchema(string $fieldName = "itemOptions", array $allowedProperties = null): Schema
    {
        $schema = Schema::parse([
            "imagePlacement:s?" => [
                "enum" => ["left", "top"],
                "description" => "Describe where image will be placed on widget item.",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Image Placement", "Describe the image placement in the widget item."),
                    new StaticFormChoices(["left" => "Left", "top" => "Top"])
                ),
            ],
            "imagePlacementMobile:s?" => [
                "enum" => ["left", "top"],
                "description" => "Describe where image will be placed on widget item on mobile.",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(
                        "Image Placement On Mobile",
                        "Describe the image placement in the widget item on smaller views."
                    ),
                    new StaticFormChoices(["left" => "Left", "top" => "Top"])
                ),
            ],
            "box?" => Schema::parse([
                "borderType:s?" => [
                    "enum" => self::borderTypeOptions(),
                    "description" => "Describe what type of the border the widget item should have.",
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions("Border Type", "Choose widget item border type."),
                        new StaticFormChoices([
                            "border" => "Border",
                            "separator" => "Separator",
                            "none" => "None",
                            "shadow" => "Shadow",
                        ])
                    ),
                ],
                "border?" => self::borderSchema("Configure border style."),
                "background?" => new WidgetBackgroundSchema("Background options for the widget item."),
                "spacing?" => self::spacingSchema("Configure box internal spacing."),
            ]),
            "contentType:s?" => [
                "enum" => self::contentTypeOptions(),
                "description" => "Describe the widget item display style.",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Content Type", "Describe the widget item display style."),
                    new StaticFormChoices([
                        "title-description-icon" => "Icon",
                        "title-description-image" => "Image",
                        "title-background" => "Background",
                        "title-description" => "Text",
                    ])
                ),
            ],
            "fg?" => [
                "type" => "string",
                "description" => "Widget item foreground color.",
                "x-control" => SchemaForm::color(new FormOptions("Text color", "Pick a text color.")),
            ],
            "display:?" => Schema::parse([
                "name?" => [
                    "type" => "boolean",
                    "default" => true,
                    "description" => "Whether to show widget item name.",
                    "x-control" => SchemaForm::toggle(new FormOptions("Name", "Whether to show widget item name.")),
                ],
                "description?" => [
                    "type" => "boolean",
                    "default" => true,
                    "description" => "Whether to show widget item description.",
                    "x-control" => SchemaForm::toggle(
                        new FormOptions("Description", "Whether to show widget item description.")
                    ),
                ],
                "counts?" => [
                    "type" => "boolean",
                    "default" => true,
                    "description" => "Whether to show widget item counts.",
                    "x-control" => SchemaForm::toggle(new FormOptions("Counts", "Whether to show widget item counts.")),
                ],
                "cta?" => [
                    "type" => "boolean",
                    "description" => "Whether to show widget item CTA.",
                    "x-control" => SchemaForm::toggle(
                        new FormOptions("Call to action button", "Whether to show widget item CTA.")
                    ),
                ],
            ]),
            "alignment?" => [
                "enum" => ["center", "left"],
                "description" => "Widget item content alignment",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Content alignment", "Describe the widget item content alignment."),
                    new StaticFormChoices([
                        "center" => "Center",
                        "left" => "Left",
                    ])
                ),
            ],
            "viewMore?" => Schema::parse([
                "labelCode?" => [
                    "type" => "string",
                    "description" => "Button text/label.",
                    "x-control" => SchemaForm::textBox(
                        new FormOptions("Label", 'Specify the "View More" button text/label.')
                    ),
                ],
                "buttonType:s?" => [
                    "enum" => self::buttonTypeOptions(),
                    "description" => "Button options.",
                ],
            ]),
        ]);

        if ($allowedProperties) {
            $schema = Schema::parse($allowedProperties)->add($schema);
        }

        return Schema::parse([
            "$fieldName?" => $schema->setDescription("Configure various widget item options"),
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
                "x-control" => SchemaForm::color(new FormOptions("Border color", "Pick a border color.")),
            ],
            "width?" => [
                "type" => ["number", "string"],
                "description" => "Border width.",
                "x-control" => SchemaForm::textBox(new FormOptions("Border width", "Specify a border width.")),
            ],
            "style?" => [
                "type" => "string",
                "description" => "Border style.",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Border style.", "Specify a border style"),
                    new StaticFormChoices([
                        "solid" => "Solid",
                        "dotted" => "Dotted",
                        "double" => "Double",
                        "dashed" => "Dashed",
                        "wavy" => "Wavy",
                    ])
                ),
            ],
            "radius?" => [
                "type" => ["number", "string"],
                "description" => "Border radius.",
                "x-control" => SchemaForm::textBox(new FormOptions("Border radius", "Specify a border radius.")),
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
                "x-control" => SchemaForm::textBox(new FormOptions("Horizontal spacing")),
            ],
            "vertical?" => [
                "type" => ["string", "number"],
                "description" => "Vertical spacing (top and bottom).",
                "x-control" => SchemaForm::textBox(new FormOptions("Vertical spacing")),
            ],
            "all?" => [
                "type" => ["string", "number"],
                "description" => "All spacing (top, right, bottom, left).",
                "x-control" => SchemaForm::textBox(new FormOptions("Horizontal and vertical spacing")),
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
