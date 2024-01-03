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
        $validatedParams = $this->getApiSchema()->validate((array) $this->props["apiParams"]);
        $this->props["apiParams"] = array_merge((array) $this->props["apiParams"], $validatedParams);

        $parentCategoryID = $this->getHydrateParam("category.categoryID") ?? -1;
        $categoryIDs = $this->categoryModel->getCategoryDescendantIDs($parentCategoryID);

        // If there are no sub-categories, we do not show the asset.
        if (count($categoryIDs) == 0) {
            return null;
        }

        $params = [
            "outputFormat" => "tree",
            "maxDepth" => 4,
            "expand" => "all",
            "parentCategoryID" => $parentCategoryID,
        ];

        // get the categories
        $categories = $this->api->index($params)->getData();

        $this->props["itemData"] = $this->mapCategoryToItem($categories);
        $this->props["isAsset"] = true;

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
            self::optionsSchema(),
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
