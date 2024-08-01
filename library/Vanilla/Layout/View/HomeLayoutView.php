<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\View;

use Garden\Schema\Schema;
use Gdn;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Web\PageHeadInterface;

/**
 * View type for a homepage.
 */
class HomeLayoutView extends AbstractCustomLayoutView
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return "Homepage";
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return "home";
    }

    /**
     * @inheritdoc
     */
    public function getTemplateID(): string
    {
        return "home";
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
    public function resolveParams(array $paramInput, ?PageHeadInterface $pageHead = null): array
    {
        $resolvedParams = parent::resolveParams($paramInput, $pageHead);

        $pageHead->setSeoTitle(t("Home"), false);

        $crumbs = [new Breadcrumb(t("Home"), "/")];

        $pageHead->setSeoBreadcrumbs($crumbs);
        return $resolvedParams;
    }
}
