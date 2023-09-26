<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use CategoriesApiController;
use CategoryModel;
use Garden\Container\ContainerException;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Exception\PermissionException;
use Vanilla\ImageSrcSet\ImageSrcSetService;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\WidgetSchemaTrait;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Gdn;

/**
 * Asset representing category list for the page.
 */
class CategoryListAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HomeWidgetContainerSchemaTrait;
    use WidgetSchemaTrait;
    use HydrateAwareTrait;
    use CategoriesWidgetTrait;

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
        $this->api = $api;
        $this->categoryModel = $categoryModel;
        $this->siteSectionModel = $siteSectionModel;
        $this->imageSrcSetService = $imageSrcSetService;
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "CategoriesWidget";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Category List";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "asset.categoryList";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "";
    }

    /**
     * Get props for component
     *
     * @return array
     * @throws HttpException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function getProps(): ?array
    {
        $categoryListLayoutsEnabled = Gdn::config("Feature.layoutEditor.categoryList.Enabled");

        $validatedParams = $this->getApiSchema()->validate((array) $this->props["apiParams"]);
        $this->props["apiParams"] = array_merge((array) $this->props["apiParams"], $validatedParams);

        $parentCategoryID = $this->getHydrateParam("category.categoryID") ?? -1;
        $categoryIDs = $this->categoryModel->getCollection()->getChildIDs([$parentCategoryID]);

        // If there are no sub-categories, we do not show the asset.
        if (count($categoryIDs) == 0) {
            return null;
        }

        $params = [
            "categoryID" => $categoryIDs,
            "limit" => $this->props["apiParams"]["limit"] ?? null,
        ];
        // this check won't be needed once we permanently release custom layouts for categories
        if ($categoryListLayoutsEnabled) {
            $params["outputFormat"] = "tree";
            $params["maxDepth"] = 4;
            $params["categoryID"] = null;
            $params["parentCategoryID"] = $parentCategoryID;
        }

        // get the categories
        $categories = $this->api->index($params)->getData();

        // if we have "parentCategoryID", for tree outputFormat the API builds the tree from its children and returns parentCategory itself
        if ($categoryListLayoutsEnabled && $parentCategoryID !== -1) {
            $categories = $this->getAllChildCategories($categories);
        }

        $this->props["itemData"] = $categoryListLayoutsEnabled
            ? $this->mapCategoryToItem($categories)
            : // below code will be gone when we permanently release custom layouts for categories
            array_map(function ($category) {
                $fallbackImage = $this->props["itemOptions"]["fallbackImage"] ?? null;
                $fallbackImage = $fallbackImage ?: null;
                $imageUrl = $category["bannerUrl"] ?? $fallbackImage;
                $imageUrlSrcSet = $category["bannerUrlSrcSet"] ?? null;
                if (!$imageUrlSrcSet && $fallbackImage && $this->imageSrcSetService) {
                    $imageUrlSrcSet = $this->imageSrcSetService->getResizedSrcSet($imageUrl);
                }

                $fallbackIcon = $this->props["itemOptions"]["fallbackIcon"] ?? null;
                $fallbackIcon = $fallbackIcon ?: null;
                $iconUrl = $category["iconUrl"] ?? $fallbackIcon;
                $iconUrlSrcSet = $category["iconUrlSrcSet"] ?? null;
                if (!$iconUrlSrcSet && $fallbackIcon && $this->imageSrcSetService) {
                    $iconUrlSrcSet = $this->imageSrcSetService->getResizedSrcSet($iconUrl);
                }

                return [
                    "to" => $category["url"],
                    "iconUrl" => $iconUrl,
                    "iconUrlSrcSet" => $iconUrlSrcSet,
                    "imageUrl" => $imageUrl,
                    "imageUrlSrcSet" => $imageUrlSrcSet,
                    "name" => $category["name"],
                    "description" => $category["description"] ?? "",
                    "counts" => [
                        [
                            "labelCode" => "discussions",
                            "count" => (int) $category["countAllDiscussions"] ?? 0,
                        ],
                    ],
                ];
            }, $categories);

        return $this->props;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(),
            self::widgetDescriptionSchema(),
            self::widgetSubtitleSchema("subtitle"),
            Schema::parse([
                "apiParams" => self::getApiSchema(),
            ]),
            self::containerOptionsSchema(
                "containerOptions",
                [],
                false,
                [
                    "grid" => "Grid",
                    "list" => "List",
                ],
                false
            ),
            self::itemOptionsSchema("itemOptions", self::getFallbackImageSchema())
        );
    }

    /**
     * @inheritDoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        return $this->renderWidgetContainerSeoContent($props, $this->renderSeoLinkList($props["itemData"]));
    }

    /**
     * @inheritDoc
     */
    public static function getApiSchema(): Schema
    {
        return CategoriesWidgetTrait::getApiSchema(false, false, false, false);
    }
}
