<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\View;

use Garden\Schema\Schema;
use Vanilla\Layout\Asset\BreadcrumbAsset;
use Vanilla\Web\PageHeadInterface;

/**
 * View type for a homepage.
 */
class HomeLayoutView extends AbstractCustomLayoutView {

    /**
     * DI.
     *
     */
    public function __construct() {
        $this->registerAssetClass(BreadcrumbAsset::class, false, false);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string {
        return "Homepage";
    }

    /**
     * @inheritdoc
     */
    public function getType(): string {
        return "home";
    }

    /**
     * @inheritdoc
     */
    public function getLayoutID(): string {
        return "home";
    }

    /**
     * @inheritdoc
     */
    public function getParamInputSchema(): Schema {
        return Schema::parse([]);
    }

    /**
     * @inheritdoc
     */
    public function resolveParams(array $paramInput, ?PageHeadInterface $pageHead = null): array {
        $resolvedParams = parent::resolveParams($paramInput, $pageHead);
        $title = $pageHead->getSeoTitle();
        $pageHead->setSeoTitle("Home - " . $title, false);
        return $resolvedParams;
    }
}
