<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Pages;

use Garden\Web\Data;
use Vanilla\Web\PageDispatchController;

/**
 * Handle /dashboard/content requests.
 */
class AdminContentPageController extends PageDispatchController
{
    public function index(string $path): Data
    {
        $data = $this->usePage(\DashboardPage::class)
            ->permission(["site.manage", "community.moderate", "staff.allow"])
            ->setSeoTitle(t("Moderation Content"))
            ->setSeoRequired(false)
            ->blockRobots()
            ->render();

        return $data;
    }
}
