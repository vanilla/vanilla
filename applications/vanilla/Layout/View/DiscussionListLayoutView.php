<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use Garden\Schema\Schema;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Forum\Widgets\DiscussionListAsset;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Layout\View\AbstractCustomLayoutView;
use Vanilla\Layout\View\LegacyLayoutViewInterface;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Web\PageHeadInterface;

/**
 * Legacy view type for discussion list
 */
class DiscussionListLayoutView extends AbstractCustomLayoutView implements LegacyLayoutViewInterface
{
    use HydrateAwareTrait;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->registerAssetClass(DiscussionListAsset::class);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Discussions Page";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return "discussionList";
    }

    /**
     * @inheritDoc
     */
    public function getLegacyType(): string
    {
        return "Vanilla/Discussions/Index";
    }

    /**
     * @inheritDoc
     */
    public function getTemplateID(): string
    {
        return "discussionList";
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
        $resolvedParams = parent::resolveParams($paramInput, $pageHead);
        $pageHead->setSeoTitle(t("Discussions"), false);
        $pageHead->setSeoDescription(
            \Gdn::formatService()->renderPlainText(c("Garden.Description", ""), HtmlFormat::FORMAT_KEY)
        );
        $crumbs = [new Breadcrumb(t("Home"), "/"), new Breadcrumb(t("Discussions"), "/discussions")];

        $pageHead->setSeoBreadcrumbs($crumbs);

        $url = isset($paramInput["page"]) ? "discussions/p{$paramInput["page"]}" : "/discussions";
        $pageHead->setCanonicalUrl($url);

        return $resolvedParams + ["layoutViewType" => $this->getType()];
    }
}
