<?php
/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Gdn;
use Garden\Schema\Schema;
use Vanilla\ImageSrcSet\ImageSrcSetService;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Layout\Section\SectionFullWidth;
use Vanilla\Site\SiteSectionModel;
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
        return Schema::parse([
            "showTitle:b?" => [
                "description" => "Whether or not the title should be displayed",
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
                "x-control" => SchemaForm::toggle(new FormOptions("Description", "Show a description in the banner.")),
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
                "x-control" => SchemaForm::toggle(new FormOptions("Search Bar", "Show a search bar in the banner.")),
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
            "background?" => (new WidgetBackgroundSchema("Banner Background", true, true))->setField(
                "properties.useOverlay",
                [
                    "type" => "boolean",
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions("Color Overlay"),
                        new FieldMatchConditional(
                            "background.image",
                            Schema::parse(["type" => "string", "minLength" => 1])
                        )
                    ),
                ]
            ),
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

        $this->props["background"]["image"] = empty($this->props["background"]["image"])
            ? $siteSection->getBannerImageLink()
            : $this->props["background"]["image"];
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
     * @inheritDoc
     */
    public static function getComponentName(): string
    {
        return "BannerWidget";
    }
}
