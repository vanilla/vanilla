<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Pages;

use CategoryModel;
use Exception;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\HttpException;
use Vanilla\Exception\PermissionException;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Forum\Layout\View\CategoryListLayoutView;
use Vanilla\Layout\LayoutPage;
use Vanilla\Utility\StringUtils;
use Vanilla\Web\PageDispatchController;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Site\SiteSectionModel;

/**
 * Controller for the custom layout category discussion list page
 */
class CategoryListPageController extends PageDispatchController
{
    /** @var CategoryListLayoutView */
    private CategoryListLayoutView $categoryListLayoutView;

    /** @var SiteSectionModel */
    private SiteSectionModel $siteSectionModel;

    /** @var CategoryModel */
    private CategoryModel $categoryModel;

    /**
     * @param CategoryListLayoutView $categoryListLayoutView
     * @param SiteSectionModel $siteSectionModel
     * @param CategoryModel $categoryModel
     */
    public function __construct(
        CategoryListLayoutView $categoryListLayoutView,
        SiteSectionModel $siteSectionModel,
        CategoryModel $categoryModel
    ) {
        $this->categoryListLayoutView = $categoryListLayoutView;
        $this->siteSectionModel = $siteSectionModel;
        $this->categoryModel = $categoryModel;
    }

    /**
     * Categories root.
     *
     * @param array $query
     * @return Data
     * @throws Exception Base exception class for all possible exceptions.
     */
    public function index(array $query): Data
    {
        $schema = $this->categoryListLayoutView->getParamInputSchema();
        $query = $schema->validate($query);

        $siteSection = $this->siteSectionModel->getCurrentSiteSection();
        $query["locale"] = $siteSection->getContentLocale();

        $layoutQuery = new LayoutQuery("categoryList", "siteSection", $siteSection->getSectionID(), $query);

        $seoDescription = \Gdn::formatService()->renderPlainText(c("Garden.Description", ""), HtmlFormat::FORMAT_KEY);

        return $this->assembleRenderData($layoutQuery, t("Categories"), $seoDescription);
    }

    /**
     * Specific Category.
     *
     * @param string $path
     * @param array $query
     * @return Data
     * @throws ClientException
     * @throws ContainerException
     * @throws HttpException
     * @throws NotFoundException
     * @throws \Garden\Web\Exception\NotFoundException|ValidationException
     */
    public function get(string $path, array $query): Data
    {
        // Get the category slug from the path.
        $categorySlug = StringUtils::parseUrlCodeFromPath($path);

        if (!$categorySlug) {
            throw new \Garden\Web\Exception\NotFoundException("Category");
        }
        $pageNumber = StringUtils::parsePageNumberFromPath($path);

        $schema = $this->categoryListLayoutView->getParamInputSchema();
        $query = $schema->validate($query);
        $query["categoryID"] = $categorySlug;
        $query["page"] = (string) $pageNumber;
        $layoutQuery = new LayoutQuery("categoryList", "category", $categorySlug, $query);
        return $this->assembleRenderData($layoutQuery);
    }

    /**
     * Assemble Render Data.
     *
     * @throws PermissionException
     * @throws HttpException
     * @throws ContainerException
     * @throws NotFoundException
     */
    private function assembleRenderData(LayoutQuery $layoutQuery, string $seoTitle = "", string $seoDescription = "")
    {
        return $this->usePage(LayoutPage::class)
            ->permission("discussions.view")
            ->setSeoRequired(false)
            ->preloadLayout($layoutQuery)
            ->render();
    }
}
