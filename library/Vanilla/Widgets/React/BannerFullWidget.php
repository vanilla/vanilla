<?php
/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Gdn;
use Garden\Schema\Schema;
use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Dashboard\Models\BannerImageModel;
use Vanilla\ImageSrcSet\ImageSrcSetService;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Layout\Section\SectionFullWidth;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\DynamicContainerSchemaOptions;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\Schema\WidgetBackgroundSchema;

/**
 * Class BannerFullWidget
 */
class BannerFullWidget implements ReactWidgetInterface, CombinedPropsWidgetInterface, HydrateAwareInterface
{
    use CombinedPropsWidgetTrait;
    use HomeWidgetContainerSchemaTrait;
    use HydrateAwareTrait;

    const IMAGE_SOURCE_STYLEGUIDE = "styleGuide";
    const IMAGE_SOURCE_SITE_SECTION = "siteSection";
    const IMAGE_SOURCE_CATEGORY = "category";
    const IMAGE_SOURCE_CUSTOM = "custom";

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var ConfigurationInterface */
    private $config;

    /** @var ImageSrcSetService */
    private $imageSrcSetService;

    /**
     * DI.
     *
     * @param SiteSectionModel $siteSectionModel
     * @param ConfigurationInterface $config
     */
    public function __construct(SiteSectionModel $siteSectionModel, ConfigurationInterface $config)
    {
        $this->siteSectionModel = $siteSectionModel;
        $this->config = $config;
        $this->imageSrcSetService = Gdn::getContainer()->get(ImageSrcSetService::class);
        $this->addChildComponentName("Banner");
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "Banner";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string
    {
        return "app-banner";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/banner.svg";
    }

    /**
     * Only allow placement in a full width section.
     */
    public static function getAllowedSectionIDs(): array
    {
        return [SectionFullWidth::getWidgetID()];
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema
    {
        $dynamicSchemas = \Gdn::getContainer()->get(DynamicContainerSchemaOptions::class);

        $titleFormChoices = $dynamicSchemas->getTitleChoices();
        $descriptionFormChoices = $dynamicSchemas->getDescriptionChoices();

        $titleFormChoices["static"] = t("Custom");
        $titleFormChoices = ["none" => t("None")] + $titleFormChoices;
        $descriptionFormChoices["static"] = t("Custom");
        $descriptionFormChoices = ["none" => t("None")] + $descriptionFormChoices;

        $titleSchema = Schema::parse([
            "showTitle:b?" => [
                "type" => "boolean",
                "description" => "Whether or not the title should be displayed",
                "default" => true,
            ],
            "titleType:s" => [
                "type" => "string",
                "description" => "The type of title to use (contextual or static)",
                "default" => "siteSection/name",
                "x-control" => SchemaForm::radio(
                    new FormOptions(
                        "Title Type",
                        "Select the kind of title",
                        "",
                        t("A contextual title depending on where this layout is applied.")
                    ),
                    new StaticFormChoices($titleFormChoices)
                ),
            ],
            "title:s?" => [
                "description" => "Banner title.",
                "default" => [
                    "\$hydrate" => "param",
                    "ref" => "siteSection/name",
                ],
                "x-control" => SchemaForm::textBox(
                    new FormOptions("Title", "Banner title."),
                    "text",
                    new FieldMatchConditional(
                        "titleType",
                        Schema::parse([
                            "type" => "string",
                            "const" => "static",
                        ])
                    )
                ),
            ],
            "textColor:s?" => [
                "description" => "Color of the text in the banner",
                "x-control" => SchemaForm::color(
                    new FormOptions(
                        "Text Color",
                        "The color for foreground text in the banner.",
                        "Style Guide default."
                    ),
                    new FieldMatchConditional(
                        "titleType",
                        Schema::parse([
                            "type" => "string",
                            "not" => [
                                "enum" => ["none"],
                            ],
                        ])
                    )
                ),
            ],
            "alignment:s?" => [
                "description" => "Alignment of items in the banner",
                "enum" => ["center", "left"],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Alignment", "Alignment of items in the banner", "Style Guide Default."),
                    new StaticFormChoices([
                        "center" => "Center Aligned",
                        "left" => "Left Aligned",
                    ]),
                    new FieldMatchConditional(
                        "titleType",
                        Schema::parse([
                            "type" => "string",
                            "not" => [
                                "enum" => ["none"],
                            ],
                        ])
                    )
                ),
            ],
        ]);

        $descriptionSchema = Schema::parse([
            "showDescription:b?" => [
                "default" => true,
            ],
            "descriptionType:s" => [
                "type" => "string",
                "description" => "The type of description to use (contextual or static)",
                "default" => "siteSection/description",
                "x-control" => SchemaForm::radio(
                    new FormOptions(
                        "Description Type",
                        "Select the kind of description",
                        "",
                        t("A contextual description depending on where this layout is applied.")
                    ),
                    new StaticFormChoices($descriptionFormChoices)
                ),
            ],
            "description:s?" => [
                "description" => "Banner description.",
                "x-control" => SchemaForm::textBox(
                    new FormOptions("", "Banner description.", "Dynamic Description"),
                    "textarea",
                    new FieldMatchConditional(
                        "descriptionType",
                        Schema::parse([
                            "type" => "string",
                            "const" => "static",
                        ])
                    )
                ),
            ],
        ]);

        return SchemaUtils::composeSchemas(
            $titleSchema,
            $descriptionSchema,
            Schema::parse([
                "showSearch:b" => [
                    "default" => true,
                    "x-control" => SchemaForm::toggle(
                        new FormOptions("Search Bar", "Show a search bar in the banner.")
                    ),
                ],
                "searchPlacement:s?" => [
                    "enum" => ["middle", "bottom"],
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions("Placement", "Where is the searchbar placed.", "Style Guide Default"),
                        new StaticFormChoices([
                            "middle" => "Middle",
                            "bottom" => "Bottom",
                        ]),
                        new FieldMatchConditional(
                            "showSearch",
                            Schema::parse([
                                "type" => "boolean",
                                "const" => true,
                                "default" => true,
                            ])
                        )
                    ),
                ],
            ]),
            self::getBackgroundSchema()
        );
    }

    /**
     * Get an extended schema specific to a given layout type.
     *
     * @return Schema
     */
    public static function getBackgroundSchema(): Schema
    {
        $dynamicSchemaOptions = \Gdn::getContainer()->get(DynamicContainerSchemaOptions::class);
        $backgroundSchema = new WidgetBackgroundSchema("Banner Background", true, true);
        $backgroundSchemaArray = $backgroundSchema->getSchemaArray();

        // Temporarily remove the image property to reposition it at the bottom of the form.
        $image = $backgroundSchemaArray["properties"]["image"];
        unset($backgroundSchemaArray["properties"]["image"]);

        // Add imageSource prop.
        $backgroundSchemaArray["properties"]["imageSource"] = [
            "type" => "string",
            "default" => self::IMAGE_SOURCE_STYLEGUIDE,
            "enum" => [
                self::IMAGE_SOURCE_STYLEGUIDE,
                self::IMAGE_SOURCE_CUSTOM,
                self::IMAGE_SOURCE_SITE_SECTION,
                self::IMAGE_SOURCE_CATEGORY,
            ],
            "x-control" => SchemaForm::radio(
                new FormOptions("Image Source"),
                new StaticFormChoices($dynamicSchemaOptions->getImageSourceChoices())
            ),
        ];

        // Restore image property and make it display only when imageSource=custom
        $backgroundSchemaArray["properties"]["image"] = $image;
        $backgroundSchemaArray["properties"]["image"]["x-control"]["conditions"] = [
            (new FieldMatchConditional(
                "background.imageSource",
                Schema::parse([
                    "type" => "string",
                    "const" => self::IMAGE_SOURCE_CUSTOM,
                ])
            ))->getCondition(),
        ];

        // Add useOverlay prop.
        $backgroundSchemaArray["properties"]["useOverlay"] = [
            "type" => "boolean",
            "default" => true,
            "x-control" => SchemaForm::checkBox(
                new FormOptions("Color Overlay"),
                new FieldMatchConditional("background.image", Schema::parse(["type" => "string", "minLength" => 1]))
            ),
        ];

        return Schema::parse([
            "background?" => $backgroundSchemaArray,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getProps(): ?array
    {
        $siteSectionID = $this->getHydrateParam("siteSection.sectionID");
        $siteSection = !empty($siteSectionID) ? $this->siteSectionModel->getByID($siteSectionID) : null;
        $siteSection = $siteSection ?? $this->siteSectionModel->getDefaultSiteSection();

        $this->props["background"]["image"] = $this->getBackgroundImage($siteSection);
        $this->props["background"]["imageUrlSrcSet"] = $this->imageSrcSetService->getResizedSrcSet(
            $this->props["background"]["image"]
        );

        // The background image source has not been set, possibly from being last edited before the prop existed
        if (!isset($this->props["background"]["imageSource"])) {
            // A background image was not previously defined, the image source is styleguide
            if (empty($this->props["background"]["image"])) {
                $this->props["background"]["imageSource"] = self::IMAGE_SOURCE_STYLEGUIDE;
            }
            // A background image was previously defined, the image source is custom
            else {
                $this->props["background"]["imageSource"] = self::IMAGE_SOURCE_CUSTOM;
            }
        }

        if ($this->props["title"] === "") {
            $this->props["title"] = $siteSection->getSectionName();
        }

        if (isset($this->props["title"]["\$hydrate"]) && isset($this->props["title"]["ref"])) {
            $this->props["title"] = $this->getHydrateParam(str_replace("/", ".", $this->props["title"]["ref"]));
        }

        if ($this->props["description"] === "") {
            $this->props["description"] = $siteSection->getSectionDescription();
        }

        if (isset($this->props["description"]["\$hydrate"]) && isset($this->props["description"]["ref"])) {
            $this->props["description"] = $this->getHydrateParam(
                str_replace("/", ".", $this->props["description"]["ref"])
            );
        }

        $this->props["showTitle"] = isset($this->props["titleType"])
            ? $this->props["titleType"] !== "none"
            : $this->props["showTitle"] ?? false;
        $this->props["showDescription"] = isset($this->props["descriptionType"])
            ? $this->props["descriptionType"] !== "none"
            : $this->props["showDescription"] ?? false;

        return $this->props;
    }

    /**
     * Helper method to get the configured background image for this banner.
     *
     * @param SiteSectionInterface $siteSection
     * @return string|null
     */
    public function getBackgroundImage(SiteSectionInterface $siteSection): ?string
    {
        $imageSource = $this->props["background"]["imageSource"] ?? null;
        if ($imageSource === self::IMAGE_SOURCE_CUSTOM) {
            return !empty($this->props["background"]["image"]) ? $this->props["background"]["image"] : null;
        }
        if ($imageSource === self::IMAGE_SOURCE_SITE_SECTION) {
            return $siteSection->getSectionID() !== DefaultSiteSection::DEFAULT_ID
                ? $siteSection->getBannerImageLink()
                : null;
        }
        if ($imageSource === self::IMAGE_SOURCE_CATEGORY) {
            return BannerImageModel::getBannerImageSlug($this->getHydrateParam("categoryID"));
        }
        if ($imageSource === self::IMAGE_SOURCE_STYLEGUIDE) {
            return null;
        }

        return empty($this->props["background"]["image"])
            ? $siteSection->getBannerImageLink()
            : $this->props["background"]["image"];
    }

    /**
     * @inheritDoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        $tpl = <<<TWIG
{% if showTitle|default(false) and title|default(false) %}
<h1>{{ title }}</h1>
{% endif %}
{% if showDescription|default(false) and description|default(false) %}
<p>{{ description }}</p>
{% endif %}
TWIG;
        $result = trim($this->renderTwigFromString($tpl, $props));
        return $result;
    }

    /**
     * @inheritDoc
     */
    public static function getComponentName(): string
    {
        return "BannerWidget";
    }
}
