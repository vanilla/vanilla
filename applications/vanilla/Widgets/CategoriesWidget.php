<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
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
use Vanilla\ImageSrcSet\ImageSrcSetService;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\FilterableWidgetTrait;
use Vanilla\Widgets\WidgetSchemaTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Gdn;

/**
 * Class CategoriesWidget
 */
class CategoriesWidget extends AbstractReactModule implements CombinedPropsWidgetInterface, HydrateAwareInterface
{
    use CombinedPropsWidgetTrait;
    use HomeWidgetContainerSchemaTrait;
    use WidgetSchemaTrait;
    use HydrateAwareTrait;
    use CategoriesWidgetTrait;
    use FilterableWidgetTrait;

    /** @var CategoriesApiController */
    private CategoriesApiController $api;

    /** @var CategoryModel */
    private CategoryModel $categoryModel;

    /** @var SiteSectionModel */
    private SiteSectionModel $siteSectionModel;

    /** @var ImageSrcSetService */
    private ImageSrcSetService $imageSrcSetService;

    /**
     * DI.
     *
     * @param CategoriesApiController $api
     * @param CategoryModel $categoryModel
     * @param SiteSectionModel $siteSectionModel
     * @param ImageSrcSetService $imageSrcSetService
     */
    public function __construct(
        CategoriesApiController $api,
        CategoryModel $categoryModel,
        SiteSectionModel $siteSectionModel,
        ImageSrcSetService $imageSrcSetService
    ) {
        parent::__construct();

        $this->api = $api;
        $this->categoryModel = $categoryModel;
        $this->siteSectionModel = $siteSectionModel;
        $this->imageSrcSetService = $imageSrcSetService;
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

        $filter = $this->props["apiParams"]["filter"];

        $apiParams = $this->props["apiParams"];

        $followedFilter = $apiParams["followed"] ?? null;

        $params["followed"] = $followedFilter;
        $params["expand"] = "all";

        if ($followedFilter) {
            // Forcing ourselves to be flat.
            $params["outputFormat"] = "flat";
            $params["limit"] = 500;
        } else {
            $params["outputFormat"] = "tree";
            $params["maxDepth"] = 4;
        }

        $expectedFields = [];
        switch ($filter) {
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
        $categories = $this->api->index($params)->getData();

        $this->props["itemData"] = $this->mapCategoryToItem($categories);

        return $this->props;
    }

    /**
     * @inheritDoc
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
                    )
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
        $layoutViewType = Gdn::request()->get("layoutViewType", false);
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
