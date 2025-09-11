<?php
/**
 * Manages installation of Dashboard.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Vanilla\Web\TwigRenderTrait;

/**
 * Handles /setup endpoint.
 */
class SetupController extends DashboardController
{
    use TwigRenderTrait;

    /**
     * The summary of all settings available.
     *
     * The menu items displayed here are collected from each application's
     * application controller and all plugin's definitions.
     *
     * @since 2.0.0
     * @access public
     */
    public function index(): void
    {
        $this->ApplicationFolder = "dashboard";
        $this->MasterView = "setup";
        // Fatal error if Garden has already been installed.
        $installed = c("Garden.Installed");
        if ($installed) {
            throw new Gdn_UserException("Vanilla is installed!", 409);
        }

        echo $this->renderTwig("@dashboard/setup/install-prompt.twig", []);
    }
}
