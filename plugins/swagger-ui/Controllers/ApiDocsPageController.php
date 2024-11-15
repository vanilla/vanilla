<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\SwaggerUI\Controllers;

use Vanilla\Web\PageDispatchController;

/**
 * /settings/api-docs
 */
class ApiDocsPageController extends PageDispatchController
{
    /**
     * @return \Garden\Web\Data
     */
    public function index()
    {
        $data = $this->usePage(\DashboardPage::class)
            ->permission(["site.manage"])
            ->setSeoTitle(t("Vanilla APIv2"))
            ->setSeoRequired(false)
            ->blockRobots()
            ->render();

        return $data;
    }
}
