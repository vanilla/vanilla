<?php
/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use CategoriesApiController;
use CategoryModel;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Exception\PermissionException;
use Vanilla\Http\InternalClient;
use Vanilla\ImageSrcSet\ImageSrcSetService;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\LegacyReactModule;
use Vanilla\Widgets\Fragments\CategoryItemFragmentMeta;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\FilterableWidgetTrait;
use Vanilla\Widgets\WidgetSchemaTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\DynamicContainerSchemaOptions;

/**
 * Class CategoriesWidget
 */
class CategoriesWidget extends LegacyReactModule implements CombinedPropsWidgetInterface, HydrateAwareInterface
{
    use CombinedPropsWidgetTrait;
    use HomeWidgetContainerSchemaTrait;
    use WidgetSchemaTrait;
    use HydrateAwareTrait;
    use CategoriesWidgetTrait;
    use FilterableWidgetTrait;

    /** @var InternalClient */
    private InternalClient $api;

    /** @var CategoryModel */
    private CategoryModel $categoryModel;

    /** @var SiteSectionModel */
    private SiteSectionModel $siteSectionModel;

    /** @var ImageSrcSetService */
    private ImageSrcSetService $imageSrcSetService;

    /**
     * DI.
     *
     * @param InternalClient $api
     * @param CategoryModel $categoryModel
     * @param SiteSectionModel $siteSectionModel
     * @param ImageSrcSetService $imageSrcSetService
     */
    public function __construct(
        InternalClient $api,
        CategoryModel $categoryModel,
        SiteSectionModel $siteSectionModel,
        ImageSrcSetService $imageSrcSetService
    ) {
        parent::__construct();

        $this->api = $api;
        $this->categoryModel = $categoryModel;
        $this->siteSectionModel = $siteSectionModel;
        $this->imageSrcSetService = $imageSrcSetService;
        $this->addChildComponentName("CategoryFollowWidget");
    }

    /**
     * @inheritdoc
     */
    public static function getFragmentClasses(): array
    {
        return [CategoryItemFragmentMeta::class];
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "categories";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Categories";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetGroup(): string
    {
        return "Community";
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "CategoriesWidget";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/categories.svg";
    }

    /**
     * Get props for component
     *
     * @return array
     * @throws HttpException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function getProps(): ?array
    {
        $this->props["apiParams"] = $this->props["apiParams"] ?? [];
        $this->props["apiParams"]["filter"] = $this->props["apiParams"]["filter"] ?? "none";

        $apiParams = $this->props["apiParams"];

        $followedFilter = $apiParams["followed"] ?? null;
        $featuredFilter = $apiParams["filter"] === "featured";

        $params = [];
        $params["followed"] = $followedFilter;
        $params["expand"] = "all";

        if ($followedFilter || $featuredFilter) {
            // Forcing ourselves to be flat.
            $params["outputFormat"] = "flat";
            $params["limit"] = 500;
        } else {
            $params["outputFormat"] = "tree";
            $params["maxDepth"] = 4;
        }

        $apiParams = $this->kludgeLegacyApiParams($apiParams);

        $expectedFields = [];
        switch ($apiParams["filter"]) {
            case "subcommunity":
                if ($apiParams["filterSubcommunitySubType"] == "contextual") {
                    // "Contexual" filtering
                    $params["siteSectionID"] = $this->getHydrateParam("siteSection.sectionID");
                } elseif ($apiParams["filterSubcommunitySubType"] == "set") {
                    // "Set" filtering
                    // `0` is default site section.
                    $siteSection = $this->siteSectionModel->getByID($apiParams["siteSectionID"] ?? 0);
                    $params["siteSectionID"] = $siteSection->getSectionID();
                }

                $expectedFields = ["filter", "filterSubcommunitySubType", "siteSectionID", "followed"];
                break;
            case "category":
                if ($apiParams["filterCategorySubType"] == "contextual") {
                    // "Contexual" filtering
                    $params["parentCategoryID"] =
                        $this->getHydrateParam("category.categoryID") ?? $this->categoryModel::ROOT_ID;
                } elseif ($apiParams["filterCategorySubType"] == "set") {
                    // "Set" filtering
                    $params["parentCategoryID"] = $apiParams["categoryID"] ?? null;
                }

                $expectedFields = ["filter", "filterCategorySubType", "categoryID", "followed"];
                break;
            case "featured":
                $params["categoryID"] = $apiParams["featuredCategoryID"];
                $expectedFields = ["filter", "featuredCategoryID"];
                break;
            case "none":
                $expectedFields = ["filter", "followed"];
                break;
        }
        // Cleanup
        $this->props["apiParams"] = array_intersect_key($this->props["apiParams"], array_flip($expectedFields));

        // Get the categories
        $categories = $this->api->get("/categories", $params)->getBody();

        $itemData = $this->mapCategoryToItem($categories);

        // Let's preserve the order of featured categories if we have the filter
        if (
            $featuredFilter &&
            !empty($apiParams["featuredCategoryID"]) &&
            is_array($apiParams["featuredCategoryID"]) &&
            count($apiParams["featuredCategoryID"]) > 0 &&
            count($itemData) > 0
        ) {
            $orderMap = array_flip($apiParams["featuredCategoryID"]);
            usort($itemData, fn($a, $b) => $orderMap[$a["categoryID"]] <=> $orderMap[$b["categoryID"]]);
        }

        $this->props["itemData"] = $itemData;

        return $this->props;
    }

    /**
     * Given the API params that could be from an old version of the widget, we need to convert them to the new ones.
     *
     * @param array $apiParams
     * @return array
     */
    function kludgeLegacyApiParams(array $apiParams): array
    {
        // Compatibility layer. With release 2023.022 we changed category filter values, so we need to convert the previous filter values into right ones
        // Previously the property `categoryID` indicated one or more categories that should appear in the widget.
        if (
            (!is_array($apiParams["featuredCategoryID"] ?? null) ||
                count($apiParams["featuredCategoryID"] ?? []) == 0) &&
            is_array($apiParams["categoryID"] ?? null) &&
            count($apiParams["categoryID"] ?? []) > 0
        ) {
            $apiParams["filter"] = "featured";
            $apiParams["featuredCategoryID"] = $apiParams["categoryID"];
            $apiParams["categoryID"] = null;
        }

        // `parentCategory` into `category`
        if ($apiParams["filter"] === "parentCategory" && $apiParams["parentCategoryID"] ?? null) {
            $apiParams["filter"] = "category";
            $apiParams["categoryID"] = $apiParams["parentCategoryID"];
            $apiParams["parentCategoryID"] = null;
        }
        // `siteSection` into `subcommunity` with subType `set`
        if ($apiParams["filter"] === "siteSection" && $apiParams["siteSectionID"] ?? null) {
            $apiParams["filter"] = "subcommunity";
            $apiParams["filterSubcommunitySubType"] = "set";
        }
        // `currentSiteSection` into `subcommunity` with subType `contextual`
        if ($apiParams["filter"] === "currentSiteSection" ?? null) {
            $apiParams["filter"] = "subcommunity";
            $apiParams["filterSubcommunitySubType"] = "contextual";
            $apiParams["currentSiteSection"] = null;
        }
        // `currentCategory` into `none` as we don't have equivalents for this now
        if ($apiParams["currentCategory"] ?? null) {
            $apiParams["filter"] = "none";
            $apiParams["currentCategory"] = null;
        }

        return $apiParams;
    }

    /**
     * @inheritdoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        return $this->renderWidgetContainerSeoContent($props, $this->renderSeoLinkList($props["itemData"]));
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        $filterTypeSchemaExtraOptions = self::getfilterTypeSchemaExtraOptions();

        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(),
            self::widgetDescriptionSchema(),
            self::widgetSubtitleSchema("subtitle"),
            Schema::parse([
                "apiParams?" => SchemaUtils::composeSchemas(
                    self::filterTypeSchema(
                        ["subcommunity", "category", "featured", "none"],
                        true,
                        $filterTypeSchemaExtraOptions
                    ),
                    // this bit is a kludge for widgets prior to 2023.022 release, so if the widget had `parentCategoryID`
                    // we will adjust its value to `categoryID` as it's the new filter value we use
                    Schema::parse([
                        "parentCategoryID?" => [
                            "type" => ["integer", "string", "null"],
                            "description" => "Parent Category ID",
                        ],
                    ])
                ),
            ]),
            self::containerOptionsSchema("containerOptions"),
            self::optionsSchema(),
            self::itemOptionsSchema("itemOptions", self::getFallbackImageSchema())
        );
    }

    /**
     * Return filterTypeSchemaExtraOptions depending on the current `layoutViewType`.
     *
     * @return array|false[]
     */
    private static function getfilterTypeSchemaExtraOptions(): array
    {
        // We may have a provided `layoutViewType`, or not.
        $layoutViewType = DynamicContainerSchemaOptions::instance()->getLayoutViewType();
        switch ($layoutViewType) {
            case "home":
                $filterTypeSchemaExtraOptions = [
                    "hasSubcommunitySubTypeOptions" => false,
                    "hasCategorySubTypeOptions" => false,
                ];
                break;
            case "subcommunityHome":
            case "discussionList":
            case "categoryList":
                $filterTypeSchemaExtraOptions = [
                    "hasCategorySubTypeOptions" => false,
                ];
                break;
            case "discussionCategoryPage":
            case "nestedCategoryList":
            default:
                $filterTypeSchemaExtraOptions = [];
                break;
        }
        return $filterTypeSchemaExtraOptions;
    }
}
