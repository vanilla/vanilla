<?php

use Garden\Web\Data;
use Vanilla\Utility\Timers;
use Vanilla\Web\PageDispatchController;

/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

class VanillaStaffPageController extends PageDispatchController
{
    protected $assetSection = "admin-new";

    public function index(string $path): Data
    {
        Timers::instance()->setShouldRecordProfile(false);
        $page = $this->usePage(\DashboardPage::class)
            ->setSeoTitle("Developer - Recorded Profiles")
            ->setSeoRequired(false)
            ->permission("admin.only");

        return $page->render();
    }
}
