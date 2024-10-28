<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\SwaggerUI\Addon;

use Garden\EventHandlersInterface;
use SettingsController;

/**
 * Handles the swagger UI menu options.
 */
class SwaggerUIEventHandlers implements EventHandlersInterface
{
    /**
     * Add the APIv2 menu item.
     *
     * @param \DashboardNavModule $nav The menu to add the module to.
     */
    public function dashboardNavModule_init_handler(\DashboardNavModule $nav)
    {
        $nav->addLinkToSectionIf(
            \gdn::session()->checkPermission("site.manage"),
            "settings",
            t("API"),
            "/settings/api-docs",
            "api.api-docs",
            "nav-api-docs",
            ["after" => "security"],
            ["badge" => "v2"]
        );
    }

    /**
     * The main swagger page.
     *
     * @param SettingsController $sender The page controller.
     */
    public function settingsController_swagger_create(SettingsController $sender)
    {
        $sender->permission("site.manage");
        redirectTo("/settings/api-docs" . "?" . http_build_query(\Gdn::request()->getQuery()));
    }
}
