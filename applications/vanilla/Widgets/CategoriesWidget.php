<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\ImageSrcSet\ImageSrcSetService;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\WidgetSchemaTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;

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

    public const FILTER_CATEGORY = "category";
    public const FILTER_PARENT_CATEGORY = "parentCategory";
    public const FILTER_SITE_SECTION = "siteSection";
    public const FILTER_CURRENT_SITE_SECTION = "currentSiteSection";
    public const FILTER_CURRENT_CATEGORY = "currentCategory";
    public const FILTER_NONE = "none";

    /** @var \CategoriesApiController */
    private $api;

    /** @var \CategoryModel */
    private $categoryModel;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var ImageSrcSetService */
    private $imageSrcSetService;

    /**
     * DI.
     *
     * @param \CategoriesApiController $api
     * @param \CategoryModel $categoryModel
     * @param SiteSectionModel $siteSectionModel
     * @param ImageSrcSetService $imageSrcSetService
     */
    public function __construct(
        \CategoriesApiController $api,
        \CategoryModel $categoryModel,
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
     */
    public function getProps(): ?array
    {
        $validatedParams = $this->getApiSchema()->validate((array) $this->props["apiParams"]);
        $this->props["apiParams"] = array_merge((array) $this->props["apiParams"], $validatedParams);

        $filter = $this->props["apiParams"]["filter"];
        $categoryIDs = null;

        switch ($filter) {
            case self::FILTER_CATEGORY:
                $categoryIDs = count($this->props["apiParams"]["categoryID"])
                    ? $this->props["apiParams"]["categoryID"]
                    : null;
                break;
            case self::FILTER_PARENT_CATEGORY:
                $categoryIDs = $this->categoryModel
                    ->getCollection()
                    ->getChildIDs([$this->props["apiParams"]["parentCategoryID"] ?? -1]);
                break;
            case self::FILTER_SITE_SECTION:
                $siteSection = $this->siteSectionModel->getByID($this->props["apiParams"]["siteSectionID"] ?? "");
                $siteSectionCategoryID = $siteSection ? $siteSection->getCategoryID() : -1;
                $categoryIDs = $this->categoryModel->getCollection()->getChildIDs([$siteSectionCategoryID]);
                break;
            case self::FILTER_CURRENT_CATEGORY:
                $parentCategoryID = $this->getHydrateParam("category.categoryID") ?? -1;
                $categoryIDs =
                    $parentCategoryID === -1
                        ? null
                        : $this->categoryModel->getCollection()->getChildIDs([$parentCategoryID]);
                break;
            case self::FILTER_CURRENT_SITE_SECTION:
                $siteSectionID = $this->getHydrateParam("siteSection.sectionID");
                $siteSection = $this->siteSectionModel->getByID($siteSectionID);
                $siteSectionCategoryID = $siteSection->getCategoryID();
                $categoryIDs =
                    $siteSectionCategoryID === -1
                        ? null
                        : $this->categoryModel->getCollection()->getChildIDs([$siteSectionCategoryID]);
                break;
            case self::FILTER_NONE:
                break;
        }

        //get the categories
        $categories = $this->api
            ->index([
                "categoryID" => $categoryIDs,
                "limit" => $this->props["apiParams"]["limit"] ?? null,
                "followed" => $this->props["apiParams"]["followed"] ?? null,
                "featured" => $this->props["apiParams"]["featured"] ?? null,
            ])
            ->getData();

        $this->props["itemData"] = array_map(function ($category) {
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
            self::containerOptionsSchema("containerOptions"),
            self::itemOptionsSchema("itemOptions", self::getFallbackImageSchema())
        );
    }
}
