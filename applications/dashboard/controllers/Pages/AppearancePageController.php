<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Pages;

use DashboardPage;
use Garden\Web\Data;
use Gdn;
use Vanilla\Theme\ThemePreloadProvider;
use Vanilla\Web\ContentSecurityPolicyMiddleware;
use Vanilla\Web\JsInterpop\ReduxActionPreloadTrait;
use Vanilla\Web\PageDispatchController;

/**
 * Controller covering the `/appearance/*` pages.
 */
class AppearancePageController extends PageDispatchController
{
    use ReduxActionPreloadTrait;

    /**
     * /appearance/*
     *
     * @param string $path Allow any path.
     * @return Data
     */
    public function index(string $path = ""): Data
    {
        $data = $this->usePage(DashboardPage::class)
            ->permission(["site.manage", "community.moderate"])
            ->setSeoTitle(t("Appearance"))
            ->setSeoRequired(false)
            ->blockRobots()
            ->render();

        // To load monaco.
        $data->setMeta(ContentSecurityPolicyMiddleware::SCRIPT_BYPASS, true);
        return $data;
    }
}
