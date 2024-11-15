<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use Garden\Schema\Schema;
use Gdn;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Forum\Widgets\CategoryFollowAsset;
use Vanilla\Forum\Widgets\CategoryListAsset;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Layout\View\AbstractCustomLayoutView;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Web\PageHeadInterface;

/**
 * View type for categories
 */
class CategoryListLayoutView extends AbstractCustomLayoutView
{
    use HydrateAwareTrait;

    private BreadcrumbModel $breadcrumbModel;

    /**
     * Constructor.
     *
     * @param BreadcrumbModel $breadcrumbModel
     */
    public function __construct(BreadcrumbModel $breadcrumbModel)
    {
        $this->breadcrumbModel = $breadcrumbModel;
        $this->registerAssetClass(CategoryListAsset::class);
        $this->registerAssetClass(CategoryFollowAsset::class);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Categories List";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return "categoryList";
    }

    /**
     * @inheritDoc
     */
    public function getTemplateID(): string
    {
        return "categoryList";
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
        $pageHead->setSeoTitle(t("Categories"), false);

        $seoDescription = Gdn::config()->get("Garden.Description", "");
        $seoDescription = $seoDescription != "" ? t("Categories") . " - " . $seoDescription : "";

        $pageHead->setSeoDescription(\Gdn::formatService()->renderPlainText($seoDescription, HtmlFormat::FORMAT_KEY));

        $url = isset($paramInput["page"])
            ? $paramInput["category"]["url"] . "/p{$paramInput["page"]}"
            : $paramInput["category"]["url"];
        $pageHead->setCanonicalUrl($url);
        $crumbs = $this->breadcrumbModel->getForRecord(
            new ForumCategoryRecordType($paramInput["category"]["categoryID"])
        );
        $pageHead->setSeoBreadcrumbs($crumbs);

        return $paramInput + ["layoutViewType" => $this->getType()];
    }
}
