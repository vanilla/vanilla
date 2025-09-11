<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use CategoryModel;
use Garden\Schema\Schema;
use Gdn;
use Vanilla\Community\BaseDiscussionWidgetModule;
use Vanilla\Forum\Controllers\Api\DiscussionsApiIndexSchema;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\Fragments\PostItemFragmentMeta;
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
    use HomeWidgetContainerSchemaTrait;
    use WidgetSchemaTrait;
    use DiscussionsWidgetSchemaTrait;
    use HydrateAwareTrait;

    /** @var BaseDiscussionWidgetModule */
    private BaseDiscussionWidgetModule $baseDiscussionWidget;

    private CategoryModel $categoryModel;

    /**
     * DI.
     *
     * @param BaseDiscussionWidgetModule $baseDiscussionWidget
     * @param CategoryModel $categoryModel
     */
    public function __construct(BaseDiscussionWidgetModule $baseDiscussionWidget, CategoryModel $categoryModel)
    {
        $this->baseDiscussionWidget = $baseDiscussionWidget;
        $this->categoryModel = $categoryModel;
    }

    /**
     * @inheritdoc
     */
    public static function getFragmentClasses(): array
    {
        return [PostItemFragmentMeta::class];
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

        $layoutViewType = $hydrateParams["layoutViewType"] ?? ($props["layoutViewType"] ?? null);
        $inputSchema = self::getDiscussionListSchema();
        $desiredHydrateParams = array_keys($inputSchema->getField("properties"));

        $apiParams = array_merge(
            $props["apiParams"],
            ArrayUtils::pluck($this->getHydrateParams(), $desiredHydrateParams)
        );

        if ($layoutViewType === "discussionCategoryPage") {
            $categoryFollowing = false;
            $apiParams["layoutViewType"] = $layoutViewType;
            $apiParams["includeChildCategories"] = false;
            $apiParams["categoryID"] = $hydrateParams["categoryID"] ?? ($apiParams["categoryID"] ?? null);
            $category = $this->categoryModel->getID($apiParams["categoryID"]);
            $apiParams["excludeHiddenCategories"] = false;
            // this bit is needed for FE
            $apiParams["categoryUrlCode"] = $category->UrlCode;
            if ($category->DisplayAs !== CategoryModel::DISPLAY_DISCUSSIONS) {
                // Don't load discussion asset if this is a category page and the category doesn't allow discussions.
                return null;
            }
        }

        // pinOrder is defined by the sort order
        $sortOrder = $apiParams["sort"];
        $pinIsMixed = $sortOrder == "-score" || $sortOrder == "dateInserted";
        $apiParams["pinOrder"] = $pinIsMixed ? "mixed" : "first";
        $apiParams["page"] = $apiParams["page"] ?? 1;

        $props["apiParams"] = $apiParams;
        $props["isAsset"] = true;

        $assetProps = $this->baseDiscussionWidget->getProps($props);
        // If the user doesn't have permissions
        if ($assetProps === null) {
            return null;
        }
        $props = array_replace($props, $assetProps);

        //at this point we should have some defaults
        if (!$props) {
            return null;
        }

        // Set this again.
        $props["isAsset"] = true;
        $props["defaultSort"] = $this->props["apiParams"]["sort"];
        $props["noCheckboxes"] = false;
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
     * @inheritdoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        return $this->baseDiscussionWidget->renderSeoHtml($props);
    }

    /**
     * Statically expose input schema.
     *
     * @return Schema
     */
    public static function getDiscussionListSchema(): Schema
    {
        $mainSchema = new DiscussionsApiIndexSchema(30);
        $schema = Schema::parse([
            "type?",
            "sort?",
            "followed?",
            "suggested?",
            "page?",
            "tagID?",
            "internalStatusID?",
            "statusID?",
            "hasComments?",
        ])->add($mainSchema->withNoDefaults());
        return $schema;
    }
}
