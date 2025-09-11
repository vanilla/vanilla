<?php
/**
 * @author David Barbier <david.barbier@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Widgets;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\FormPickerOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Subcommunities\Models\SubcommunitySiteSection;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Web\TwigRenderTrait;
use Vanilla\Widgets\Schema\WidgetBackgroundSchema;

/**
 * Abstraction layer for the module displaying Categories.
 */
trait HomeWidgetContainerSchemaTrait
{
    use TwigRenderTrait;

    /**
     * Get the schema for the widget title.
     *
     * @param ?string $placeholder
     * @param ?bool $required
     * @param ?string $defaultValue
     *
     * @return Schema
     * @throws ContainerException
     * @throws NotFoundException
     */
    public static function widgetTitleSchema(
        string $placeholder = null,
        bool $required = false,
        string $defaultValue = null,
        bool $allowDynamic = true,
        string $defaultTitleType = null
    ): Schema {
        $title = $required ? "title:s" : "title:s?";
        $titleSchema = [
            "type" => "string",
            "description" => "Title of the widget",
            "x-control" => SchemaForm::textBox(
                new FormOptions("Title", "Set a custom title.", $placeholder ?? "Type your title here"),
                "text",
                $allowDynamic
                    ? new FieldMatchConditional(
                        "titleType",
                        Schema::parse([
                            "type" => "string",
                            "const" => "static",
                        ])
                    )
                    : null
            ),
        ];

        if ($defaultValue) {
            $titleSchema["default"] = $defaultValue;
        }

        if (!$allowDynamic) {
            return Schema::parse([
                $title => $titleSchema,
            ]);
        }

        $dynamicSchemas = \Gdn::getContainer()->get(DynamicContainerSchemaOptions::class);

        $options = $dynamicSchemas->getTitleChoices();
        // Always add "none" as the first option if the field is not required
        if (!$required) {
            $options->option("None", "none");
        }

        $options->option("Custom", "static");

        return Schema::parse([
            $required ? "titleType:s" : "titleType:s?" => [
                "type" => "string",
                "description" => "The type of title to use (contextual or static)",
                "default" => $defaultTitleType ?? ($defaultValue ? "static" : "none"),
                "x-control" => SchemaForm::radioPicker(
                    new FormOptions(
                        "Title Type",
                        "Select the kind of title",
                        "",
                        t(
                            "A contextual title will be the banner or subcommunity title depending on where which page this layout is applied."
                        )
                    ),
                    $options
                ),
            ],
            $title => $titleSchema,
        ]);
    }

    /**
     * Get the schema for the widget title.
     *
     * @param ?string $placeholder
     * @param ?bool $required
     * @param ?string $defaultValue
     *
     * @return Schema
     * @throws ContainerException
     * @throws NotFoundException
     */
    public static function widgetDescriptionSchema(
        string $placeholder = null,
        bool $required = false,
        string $defaultValue = null,
        bool $allowDynamic = true
    ): Schema {
        $description = $required ? "description:s" : "description:s?";
        $descriptionSchema = [
            "type" => "string",
            "description" => "Description of the widget.",
            "x-control" => SchemaForm::textBox(
                new FormOptions(
                    "Description",
                    "Set a custom description.",
                    $placeholder ?? "Type your description here"
                ),
                "textarea",
                $allowDynamic
                    ? new FieldMatchConditional(
                        "descriptionType",
                        Schema::parse([
                            "type" => "string",
                            "const" => "static",
                        ])
                    )
                    : null
            ),
        ];

        if ($defaultValue) {
            $descriptionSchema["default"] = $defaultValue;
        }

        if (!$allowDynamic) {
            return Schema::parse([
                $description => $descriptionSchema,
            ]);
        }

        $dynamicSchemas = \Gdn::getContainer()->get(DynamicContainerSchemaOptions::class);

        $options = $dynamicSchemas->getDescriptionChoices();

        // Always add "none" as the first option if the field is not required
        if (!$required) {
            $options->option("None", "none");
        }

        $options->option("Custom", "static");

        return Schema::parse([
            $required ? "descriptionType:s" : "descriptionType:s?" => [
                "type" => "string",
                "description" => "The type of description to use (contextual or static)",
                "default" => $defaultValue ? "static" : "none",
                "x-control" => SchemaForm::radioPicker(
                    new FormOptions("Description Type", "Select the kind of title"),
                    $options
                ),
            ],
            $description => $descriptionSchema,
        ]);
    }

    /**
     * Get schema for a widget subtitle.
     *
     * @param string $fieldName The name of the field.
     * @param ?string $placeholder
     *
     * @return Schema
     */
    public static function widgetSubtitleSchema(string $fieldName = "subtitle", string $placeholder = null): Schema
    {
        return Schema::parse([
            "{$fieldName}:s?" => [
                "type" => "string",
                "description" => "Subtitle of the widget.",
                "x-control" => SchemaForm::textBox(
                    new FormOptions("Subtitle", "Set a custom subtitle.", $placeholder ?? "Type your subtitle here")
                ),
            ],
        ]);
    }

    /**
     * Get the schema for display options with featured image and fallback image.
     *
     * @param string $fieldName The name of the field.
     *
     * @return Schema
     */
    public static function displayOptionsSchema(string $fieldName = "displayOptions"): Schema
    {
        $propertiesSchema = Schema::parse([
            "featuredImage?" => [
                "type" => "boolean",
                "default" => false,
                "x-control" => SchemaForm::toggle(
                    new FormOptions(
                        "Featured Image",
                        "Show a featured image when available.",
                        "",
                        "Post will show a featured image when available. If there's nothing to show, the branded default image will show."
                    )
                ),
            ],
            "fallbackImage?" => [
                "type" => "string",
                "description" =>
                    "By default, an SVG image using your brand color displays when there's nothing else to show. Upload your own image to customize. Recommended size: 1200px by 600px.",
                "x-control" => SchemaForm::upload(
                    new FormOptions(
                        "Fallback Image",
                        "Upload your own image to override the default SVG.",
                        "Choose Image",
                        "By default, an SVG image using your brand color displays when there's nothing else to show. Upload your own image to customize. Recommended size: 1200px by 600px."
                    ),
                    new FieldMatchConditional(
                        "displayOptions.featuredImage",
                        Schema::parse([
                            "type" => "boolean",
                            "const" => true,
                        ])
                    )
                ),
            ],
        ]);

        return Schema::parse([
            "$fieldName?" => $propertiesSchema
                ->setDescription("Configure the display options")
                ->setField("x-control", SchemaForm::section(new FormOptions("Display Options"))),
        ]);
    }

    /**
     * Get the schema for container options.
     *
     * @param string $fieldName
     * @param array|null $allowedProperties
     * @param bool $minimalProperties
     * @param array $displayTypes
     * @param bool $viewAll
     * @return Schema
     */
    public static function containerOptionsSchema(
        string $fieldName = "options",
        array $allowedProperties = null,
        bool $minimalProperties = false,
        array $displayTypes = [
            "grid" => "Grid",
            "list" => "List",
            "carousel" => "Carousel",
            "link" => "Link",
        ],
        bool $viewAll = true,
        string $visualBackgroundType = "inner",
        string $defaultBorderType = "none"
    ): Schema {
        $viewAllSchema = [];

        if ($viewAll) {
            $viewAllSchema = ["viewAll?" => self::viewAllSchema("Configure a view all link for the widget.")];
        }

        $basicPropertiesSchema = [
            "outerBackground?" => new WidgetBackgroundSchema(
                "Set a full width background for the container.",
                $visualBackgroundType === "outer"
            ),
            "innerBackground?" => new WidgetBackgroundSchema(
                "Set an inner background (inside of the margins) for the container.",
                $visualBackgroundType === "inner"
            ),
            "borderType:s" => [
                "enum" => self::borderTypeOptions(),
                "description" => "Describe what type of border the widget should have.",
                "default" => $defaultBorderType,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Border Type", "Choose widget border type", "Style Guide Default"),
                    new StaticFormChoices([
                        "none" => "None",
                        "border" => "Border",
                        "shadow" => "Shadow",
                        "separator" => "Separator",
                    ])
                ),
            ],
            "headerAlignment:s" => [
                "description" => "Configure alignment of the title, subtitle, and description.",
                "enum" => ["left", "center"],
                "default" => "left",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Header Alignment", "Configure alignment of the title, subtitle, and description."),
                    new StaticFormChoices(["left" => "Left", "center" => "Center"])
                ),
            ],
            "visualBackgroundType" => [
                "type" => "string",
                "default" => $visualBackgroundType,
                "enum" => ["inner", "outer"],
            ],
        ];
        $extendedSchema = [
            "maxColumnCount:i?" => [
                "description" => "Set the maximum number of columns for the widget.",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(
                        "Max Columns",
                        "Set the maximum number of columns for the widget.",
                        "Style Guide Default"
                    ),
                    new StaticFormChoices(["1" => 1, "2" => 2, "3" => 3, "4" => 4, "5" => 5]),
                    new FieldMatchConditional(
                        "containerOptions.displayType",
                        Schema::parse([
                            "type" => "string",
                            "enum" => ["grid", "carousel"],
                        ])
                    )
                ),
            ],
            "displayType:s?" => array_merge(
                [
                    "enum" => array_keys($displayTypes),
                    "description" => "Describe the widget display format.",
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions(
                            "Display Type",
                            "Choose the widget display type.",
                            "Style Guide Default",
                            "Selection will affect the item options available."
                        ),
                        new StaticFormChoices($displayTypes)
                    ),
                ],
                count(array_keys($displayTypes)) == 1 ? ["default" => array_key_first($displayTypes)] : []
            ),
        ] +
            $viewAllSchema + [
                "isGrid:b?" => [
                    "deprecationMessage" => "This is deprecated. Use displayType instead.",
                    "description" => "Configure if the widget should display as a grid. Defaults to false.",
                ],
                "isCarousel:b?" => [
                    "deprecationMessage" => "This is deprecated. Use displayType instead.",
                    "description" => "Configure if the widget should display in a carousel. Defaults to false.",
                ],
            ];

        if ($minimalProperties) {
            $propertiesSchema = Schema::parse($basicPropertiesSchema);
        } else {
            $propertiesSchema = Schema::parse(array_merge($basicPropertiesSchema, $extendedSchema));
        }

        if ($allowedProperties) {
            $allowedProperties[] = "visualBackgroundType";
            $propertiesSchema = Schema::parse($allowedProperties)->add($propertiesSchema);
        }

        return Schema::parse([
            "$fieldName?" => $propertiesSchema
                ->setDescription("Configure various container options")
                ->setField("x-control", SchemaForm::section(new FormOptions("Container Options"))),
        ]);
    }

    /**
     * Get the schema for a viewAll action.
     *
     * @param string|null $description
     * @return Schema
     */
    public static function viewAllSchema(string $description = null): Schema
    {
        $schema = Schema::parse([
            "showViewAll:b?" => [
                "description" => "Enable View All button.",
                "x-control" => SchemaForm::toggle(new FormOptions("View All Button", "Show View All button.")),
            ],
            "to:s?" => [
                "description" => "The URL of the view all link.",
                "x-control" => SchemaForm::textBox(
                    new FormOptions("URL", 'Set a custom url for "View All" link.'),
                    "text",
                    new FieldMatchConditional(
                        "containerOptions.viewAll.showViewAll",
                        Schema::parse([
                            "type" => "boolean",
                            "const" => true,
                        ])
                    )
                ),
            ],
            "name:s?" => [
                "description" => 'A custom name for the view all link. Default is "View All" or defined by the theme.',
                "x-control" => SchemaForm::textBox(
                    new FormOptions("Label", 'Set a custom name for "View All" link.'),
                    "text",
                    new FieldMatchConditional(
                        "containerOptions.viewAll.showViewAll",
                        Schema::parse([
                            "type" => "boolean",
                            "const" => true,
                        ])
                    )
                ),
            ],
            "position:s?" => [
                "enum" => ["top", "bottom"],
                "description" => 'Where to render the viewAll link. Default is "bottom" or defined by the theme.',
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Position", 'Choose "View All" link position.'),
                    new StaticFormChoices([
                        "top" => "Top",
                        "bottom" => "Bottom",
                    ]),
                    new FieldMatchConditional(
                        "containerOptions.viewAll.showViewAll",
                        Schema::parse([
                            "type" => "boolean",
                            "const" => true,
                        ])
                    )
                ),
            ],
        ]);
        if ($description) {
            $schema->setField("description", $description);
        }
        return $schema;
    }

    /**
     * Get an array of the border type options.
     *
     * @return string[]
     */
    public static function borderTypeOptions(): array
    {
        return ["border", "separator", "none", "shadow"];
    }

    /**
     * Get an array of the display type options.
     *
     * @return string[]
     */
    public static function displayTypeOptions(): array
    {
        return ["grid", "list", "carousel", "link"];
    }

    /**
     * Render seo content for the home widget container.
     *
     * @param array $props Array with home widget container props.
     * @param string $childHtml Child HTML content to put after the headings.
     */
    protected function renderWidgetContainerSeoContent(array $props, string $childHtml): string
    {
        $tpl = <<<TWIG
<div class="pageBox">
    {% if title|default(false) or subtitle|default(false) or description|default(false) %}
    <div class="pageHeadingBox">
        {% if title|default(false) %}<h2>{{ t(title) }}</h2>{% endif %}
        {% if subtitle|default(false) %}<h3>{{ t(subtitle) }}</h3>{% endif %}
        {% if description|default(false) %}<p>{{ t(description) }}</p>{% endif %}
    </div>
    {% endif %}
    {{ childHtml|raw }}
    {% if containerOptions.viewAll.to|default(false) %}
    <div><a href="{{ containerOptions.viewAll.to }}">{{ containerOptions.viewAll.name|default(t("View All")) }}</a></div>
    {% endif %}
</div>
TWIG;

        $result = $this->renderTwigFromString($tpl, $props + ["childHtml" => $childHtml]);
        return $result;
    }
}
