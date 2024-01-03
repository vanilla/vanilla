<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use Garden\Schema\Schema;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Forum\Widgets\CategoryFollowAsset;
use Vanilla\Forum\Widgets\CategoryListAsset;
use Vanilla\Forum\Widgets\DiscussionListAsset;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Layout\View\AbstractCustomLayoutView;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Web\PageHeadInterface;

/**
 * View type for categories
 */
class NestedCategoryListPageLayoutView extends AbstractCustomLayoutView
{
    use HydrateAwareTrait;

    private BreadcrumbModel $breadcrumbModel;

    /**
     * Constructor.
     */
    public function __construct(BreadcrumbModel $breadcrumbModel)
    {
        $this->breadcrumbModel = $breadcrumbModel;

        $this->registerAssetClass(CategoryListAsset::class);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Nested Categories Page";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return "nestedCategoryList";
    }

    /**
     * @inheritDoc
     */
    public function getTemplateID(): string
    {
        return "nestedCategoryList";
    }

    /**
     * @inheritDoc
     */
    public function getParamInputSchema(): Schema
    {
        return Schema::parse([]);
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
}
