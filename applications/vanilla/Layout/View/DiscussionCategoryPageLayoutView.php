<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use CategoryModel;
use Garden\Schema\Schema;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Forum\Widgets\CategoryFollowAsset;
use Vanilla\Forum\Widgets\CategoryListAsset;
use Vanilla\Forum\Widgets\DiscussionListAsset;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Layout\View\AbstractCustomLayoutView;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Web\PageHeadInterface;

/**
 * View type for categories
 */
class DiscussionCategoryPageLayoutView extends AbstractCustomLayoutView
{
    use HydrateAwareTrait;

    private BreadcrumbModel $breadcrumbModel;

    private SiteSectionModel $siteSectionModel;

    /**
     * Constructor.
     */
    public function __construct(BreadcrumbModel $breadcrumbModel, SiteSectionModel $siteSectionModel)
    {
        $this->breadcrumbModel = $breadcrumbModel;
        $this->siteSectionModel = $siteSectionModel;
        $this->registerAssetClass(CategoryListAsset::class);
        $this->registerAssetClass(DiscussionListAsset::class);
        $this->registerAssetClass(CategoryFollowAsset::class);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Discussion Categories Page";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return "discussionCategoryPage";
    }

    /**
     * @inheritDoc
     */
    public function getTemplateID(): string
    {
        return "discussionCategoryPage";
    }

    /**
     * @inheritDoc
     */
    public function getParamInputSchema(): Schema
    {
        return DiscussionListAsset::getDiscussionListSchema();
    }

    /**
     * @inheritDoc
     */
    public function getParamResolvedSchema(): Schema
    {
        return Schema::parse(["layoutViewType:s"]);
    }

    /**
     * @inheritDoc
     */
    public function resolveParams(array $paramInput, ?PageHeadInterface $pageHead = null): array
    {
        $category = $paramInput["category"];
        $this->setSiteSection($category, $paramInput["siteSection"] ?? []);
        $pageHead->setSeoTitle($category["name"], false);

        if (!empty($category["description"])) {
            $pageHead->setSeoDescription($category["description"]);
        }

        $crumbs = $this->breadcrumbModel->getForRecord(new ForumCategoryRecordType($category["categoryID"]));
        $pageHead->setSeoBreadcrumbs($crumbs);

        $url = isset($paramInput["page"]) ? $category["url"] . "/p{$paramInput["page"]}" : $category["url"];
        $pageHead->setCanonicalUrl($url);

        return $paramInput + ["layoutViewType" => $this->getType()];
    }

    public function setSiteSection(array &$category, array $siteSection)
    {
        if (isset($siteSection["attributes"]["allCategories"])) {
            if (in_array($category["categoryID"], $siteSection["attributes"]["allCategories"])) {
                $category["url"] = CategoryModel::createRawCategoryUrl($category["categoryID"]);
            } else {
                // If this category is not in the given site section, redirect to a site section that contains it.
                $allSiteSections = $this->siteSectionModel->getAll();
                foreach ($allSiteSections as $section) {
                    $categories = $section->getAttributes()["allCategories"] ?? [];
                    if (in_array($category["categoryID"], $categories)) {
                        $categoryUrl = CategoryModel::createRawCategoryUrl($category["categoryID"]);
                        redirectTo($categoryUrl);
                    }
                }
            }
        }
    }
}
