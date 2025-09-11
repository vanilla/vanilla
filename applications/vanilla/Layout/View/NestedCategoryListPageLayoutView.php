<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use CategoryModel;
use Garden\Schema\Schema;
use Vanilla\Exception\PermissionException;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Forum\Widgets\CategoryListAsset;
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
     * @inheritdoc
     */
    public function getName(): string
    {
        return "Nested Categories Page";
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return "nestedCategoryList";
    }

    /**
     * @inheritdoc
     */
    public function getTemplateID(): string
    {
        return "nestedCategoryList";
    }

    /**
     * @inheritdoc
     */
    public function getParamInputSchema(): Schema
    {
        return Schema::parse([]);
    }

    /**
     * @inheritdoc
     */
    public function getParamResolvedSchema(): Schema
    {
        return Schema::parse(["layoutViewType:s"]);
    }

    /**
     * @inheritdoc
     */
    public function resolveParams(array $paramInput, ?PageHeadInterface $pageHead = null): array
    {
        $category = $paramInput["category"];
        $pageHead->setSeoTitle($category["name"], false);
        if (!CategoryModel::checkPermission($category["categoryID"], "Vanilla.Discussions.View")) {
            throw new PermissionException("Vanilla.Discussions.View");
        }
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
