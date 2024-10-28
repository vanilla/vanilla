<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Api;

use DashboardNavModule;
use Vanilla\Web\Controller;

/**
 * Contains information useful for building the dashboard.
 */
class DashboardApiController extends Controller
{
    /**
     * Get the menus in the dashboard.
     *
     * @return array Returns the menus.
     */
    public function index_menus()
    {
        // This is the array of permissions from the module.
        // We just want to make sure the user has at least one of the permissions although if they don't then the menus would be empty.
        $this->permission([
            "Garden.Settings.Manage",
            "Garden.Community.Manage",
            "Garden.Moderation.Manage",
            "posts.moderate",
            "Garden.Settings.View",
            "Garden.Users.Add",
            "Garden.Users.Edit",
            "Garden.Users.Delete",
            "Garden.Users.Approve",
            "Analytics.Data.View",
            "Analytics.Dashboards.Manage",
        ]);

        $in = $this->schema([], "in")->setDescription("List the dashboard menus.");
        $out = $this->schema(
            [
                ":a" => [
                    "name:s" => "The title of the menu.",
                    "id:s" => "The ID of the menu.",
                    "description:s" => "The menu description.",
                    "url:s?" => 'The URL to the menu if it doesn\'t have a submenu.',
                    "children:a" => [
                        "name:s?" => "The title of the group.",
                        "id:s?" => "The key of the group.",
                        "children:a" => [
                            "name:s" => "The title of the link.",
                            "id:s" => "The key of the link.",
                            "parentID:s" => "The key of the parent.",
                            "url:s" => "The URL of the link.",
                            "react:b" => "Whether or not the link represents a React component.",
                            "badge?" => [
                                "type" => "object",
                                "description" => "Information about a badge to display beside the link.",
                                "properties" => [
                                    "type:s" => [
                                        "description" => "The type of badge.",
                                        "enum" => ["view", "text"],
                                    ],
                                    "url:s?" => "The URL of a view.",
                                    "text:s" => "Literal text for the badge.",
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            "out"
        );

        $module = new DashboardNavModule();
        $result = $module->getMenus();

        $result = $out->validate($result);

        return $result;
    }
}
