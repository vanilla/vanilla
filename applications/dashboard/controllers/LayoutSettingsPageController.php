<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers;

use Garden\Web\Data;
use Vanilla\Web\ContentSecurityPolicyMiddleware;
use Vanilla\Web\PageDispatchController;

/**
 * Controller for /layout-settings.
 */
class LayoutSettingsPageController extends PageDispatchController
{
    protected $assetSection = "admin";

    /**
     * /layout-settings/editor
     *
     * @return Data
     */
    public function get_playground(): Data
    {
        $data = $this->useSimplePage("Layout Editor")
            ->permission("site.manage")
            ->blockRobots()
            ->render();

        // To load monaco.
        $data->setMeta(ContentSecurityPolicyMiddleware::SCRIPT_BYPASS, true);
        return $data;
    }
}
