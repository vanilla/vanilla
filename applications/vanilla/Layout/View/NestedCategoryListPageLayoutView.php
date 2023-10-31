<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use Garden\Schema\Schema;
use Vanilla\Forum\Widgets\CategoryFollowAsset;
use Vanilla\Forum\Widgets\CategoryListAsset;
use Vanilla\Forum\Widgets\DiscussionListAsset;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Layout\View\AbstractCustomLayoutView;
use Vanilla\Web\PageHeadInterface;

/**
 * View type for categories
 */
class NestedCategoryListPageLayoutView extends AbstractCustomLayoutView
{
    use HydrateAwareTrait;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->registerAssetClass(CategoryListAsset::class);
        $this->registerAssetClass(CategoryFollowAsset::class);
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
    public function getLayoutID(): string
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
        return $paramInput + ["layoutViewType" => $this->getType()];
    }
}
