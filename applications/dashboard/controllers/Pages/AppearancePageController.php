<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Pages;

use DashboardPage;
use Garden\Web\Data;
use Vanilla\Web\ContentSecurityPolicyMiddleware;
use Vanilla\Web\PageDispatchController;

/**
 * Controller covering the `/appearance/*` pages.
 */
class AppearancePageController extends PageDispatchController {

    /**
     * /appearance/*
     *
     * @param string $path Allow any path.
     *
     * @return Data
     */
    public function index(string $path = ""): Data {
        $data = $this->usePage(DashboardPage::class)
            ->permission(["settings.manage", "community.moderate"])
            ->setSeoRequired(false)
            ->blockRobots()
            ->render();

        // To load monaco.
        $data->setMeta(ContentSecurityPolicyMiddleware::SCRIPT_BYPASS, true);
        return $data;
    }
}
