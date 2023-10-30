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
        return SchemaUtils::composeSchemas(
            Schema::parse([
                "showTitle:b?" => [
                    "description" => "Whether or not the title should be displayed",
                    "default" => true,
                ],
                "title:s?" => [
                    "description" => "Banner title.",
                    "x-control" => SchemaForm::textBox(new FormOptions("Title", "Banner title.", "Dynamic Title")),
                ],
                "textColor:s?" => [
                    "description" => "Color of the text in the banner",
                    "x-control" => SchemaForm::color(
                        new FormOptions(
                            "Text Color",
                            "The color for foreground text in the banner.",
                            "Style Guide default."
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
                        ])
                    ),
                ],
                "showDescription:b" => [
                    "default" => true,
                    "x-control" => SchemaForm::toggle(
                        new FormOptions("Description", "Show a description in the banner.")
                    ),
                ],
                "description:s?" => [
                    "description" => "Banner description.",
                    "x-control" => SchemaForm::textBox(
                        new FormOptions("", "Banner description.", "Dynamic Description"),
                        "textarea",
                        new FieldMatchConditional(
                            "showDescription",
                            Schema::parse([
                                "type" => "boolean",
                                "const" => true,
                                "default" => true,
                            ])
                        )
                    ),
                ],
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
            self::getExtendedSchemaForLayout()
        );
    }

    /**
     * Get an extended schema specific to a given layout type.
     *
     * @param string|null $layoutViewType
     * @return Schema
     */
    public static function getExtendedSchemaForLayout(?string $layoutViewType = null): Schema
    {
        $siteSectionModel = Gdn::getContainer()->get(SiteSectionModel::class);
        $backgroundSchema = new WidgetBackgroundSchema("Banner Background", true, true);
        $backgroundSchemaArray = $backgroundSchema->getSchemaArray();

        // Temporarily remove the image property to reposition it at the bottom of the form.
        $image = $backgroundSchemaArray["properties"]["image"];
        unset($backgroundSchemaArray["properties"]["image"]);

        // Build the set of allowed image sources.
        $imageSourceOptions = [self::IMAGE_SOURCE_STYLEGUIDE => "Style Guide Banner"];
        if (in_array($layoutViewType, ["discussionCategoryPage", "nestedCategoryList"])) {
            $imageSourceOptions[self::IMAGE_SOURCE_CATEGORY] = "Category Banner";
        }
        if ($layoutViewType == "home" && count($siteSectionModel->getAll()) > 1) {
            $imageSourceOptions[self::IMAGE_SOURCE_SITE_SECTION] = "Subcommunity Banner";
        }
        $imageSourceOptions[self::IMAGE_SOURCE_CUSTOM] = "Custom";

        // Add imageSource prop.
        $backgroundSchemaArray["properties"]["imageSource"] = [
            "type" => "string",
            "default" => self::IMAGE_SOURCE_CUSTOM,
            "enum" => [
                self::IMAGE_SOURCE_STYLEGUIDE,
                self::IMAGE_SOURCE_CUSTOM,
                self::IMAGE_SOURCE_SITE_SECTION,
                self::IMAGE_SOURCE_CATEGORY,
            ],
            "x-control" => SchemaForm::radio(
                new FormOptions("Image Source"),
                new StaticFormChoices($imageSourceOptions)
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
        $this->props["title"] = empty($this->props["title"]) ? $siteSection->getSectionName() : $this->props["title"];
        $this->props["description"] = empty($this->props["description"])
            ? $siteSection->getSectionDescription()
            : $this->props["description"];

        return $this->props;
    }

    /**
     * Helper method to get the configured background image for this banner.
     *
     * @param SiteSectionInterface $siteSection
     * @return string|null
     */
    private function getBackgroundImage(SiteSectionInterface $siteSection): ?string
    {
        $imageSource = $this->props["background"]["imageSource"] ?? null;
        if ($imageSource === self::IMAGE_SOURCE_CUSTOM) {
            return !empty($this->props["background"]["image"]) ? $this->props["background"]["image"] : null;
        }
        if ($imageSource === self::IMAGE_SOURCE_SITE_SECTION) {
            return $siteSection->getSectionID() !== (string) DefaultSiteSection::DEFAULT_ID
                ? $siteSection->getBannerImageLink()
                : null;
        }
        if ($imageSource === self::IMAGE_SOURCE_CATEGORY) {
            return BannerImageModel::getBannerImageSlug($this->getHydrateParam("categoryID"));
        }
        if ($imageSource === self::IMAGE_SOURCE_STYLEGUIDE) {
            return $this->siteSectionModel->getDefaultSiteSection()->getBannerImageLink();
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
