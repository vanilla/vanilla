<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Gdn;
use Vanilla\Community\BaseDiscussionWidgetModule;
use Vanilla\Forum\Layout\View\DiscussionListLayoutView;
use Vanilla\Forum\Widgets\DiscussionsWidgetSchemaTrait;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\WidgetSchemaTrait;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;

/**
 * Asset representing discussion list for the page.
 */
class DiscussionListAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HomeWidgetContainerSchemaTrait, WidgetSchemaTrait, DiscussionsWidgetSchemaTrait, HydrateAwareTrait;

    /** @var BaseDiscussionWidgetModule */
    private $baseDiscussionWidget;

    /**
     * DI.
     *
     * @param BaseDiscussionWidgetModule $baseDiscussionWidget
     */
    public function __construct(BaseDiscussionWidgetModule $baseDiscussionWidget)
    {
        $this->baseDiscussionWidget = $baseDiscussionWidget;
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "DiscussionsWidget";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Discussion List";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "asset.discussionList";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "";
    }

    /**
     * @inheritdoc
     */
    public function getProps(): ?array
    {
        $props = $this->props;
        $hydrateParams = $this->getHydrateParams();
        $props["apiParams"]["siteSectionID"] =
            $hydrateParams["siteSection"]["sectionID"] ?? $props["apiParams"]["siteSectionID"];

        $desiredHydrateParams = array_keys(DiscussionListLayoutView::paramInputSchema()->getField("properties"));

        $apiParams = array_merge(
            $props["apiParams"],
            ArrayUtils::pluck($this->getHydrateParams(), $desiredHydrateParams)
        );

        // pinOrder is defined by the sort order
        $sortOrder = $apiParams["sort"];
        $pinIsMixed = $sortOrder == "-score" || $sortOrder == "dateInserted";
        $apiParams["pinOrder"] = $pinIsMixed ? "mixed" : "first";
        $apiParams["page"] = $apiParams["page"] ?? 1;

        $props["apiParams"] = $apiParams;
        $props["isAsset"] = true;

        $props = $this->baseDiscussionWidget->getProps($props);

        //at this point we should have some defaults
        if (!$props) {
            return null;
        }

        // Set this again.
        $props["isAsset"] = true;
        $props["defaultSort"] = $this->props["apiParams"]["sort"];
        $props["noCheckboxes"] = false;
        $categoryFollowing = Gdn::config()->get("Vanilla.EnableCategoryFollowing", 0);
        $props["categoryFollowEnabled"] = $categoryFollowing && $categoryFollowing !== "0";
        return $props;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        $apiSchema = BaseDiscussionWidgetModule::getApiSchema();

        $apiSchema = $apiSchema->merge(
            SchemaUtils::composeSchemas(
                self::followedCategorySchema(),
                static::categorySchema(),
                self::siteSectionIDSchema(),
                Schema::parse([
                    "sort?" => [
                        "type" => "string",
                        "default" => "Recently Commented",
                        "x-control" => SchemaForm::dropDown(
                            new FormOptions(
                                t("Default Sort Order"),
                                t("Choose the order records are sorted by default.")
                            ),
                            new StaticFormChoices([
                                "-dateLastComment" => t("Recently Commented"),
                                "-dateInserted" => t("Recently Created"),
                                "-score" => t("Top"),
                                "-hot" => t("Trending"),
                                "dateInserted" => t("Oldest"),
                            ])
                        ),
                    ],
                ]),
                self::getSlotTypeSchema(),
                self::limitSchema()
            )
        );

        $schema = SchemaUtils::composeSchemas(
            self::widgetTitleSchema(),
            self::widgetSubtitleSchema("subtitle"),
            self::widgetDescriptionSchema(),
            Schema::parse([
                "apiParams" => $apiSchema,
            ]),
            self::optionsSchema(),
            self::containerOptionsSchema("containerOptions")
        );

        return $schema;
    }

    /**
     * @inheritDoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        return $this->baseDiscussionWidget->renderSeoHtml($props);
    }
}
