<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Api;

use DashboardNavModule;
use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Vanilla\Exception\PermissionException;
use Vanilla\Web\Controller;

/**
 * Contains information useful for building the dashboard.
 */
class DashboardApiController extends Controller {

    /** @var \Gdn_Locale */
    private $locale;

    /** @var EventManager */
    private $eventManager;

    /**
     * Constructor.
     *
     * @param \Gdn_Locale $locale
     * @param EventManager $eventManager
     */
    public function __construct(\Gdn_Locale $locale, EventManager $eventManager) {
        $this->locale = $locale;
        $this->eventManager = $eventManager;
    }


    /**
     * Get the HTML for the legacy dashboard menu.
     *
     * @param RequestInterface $request The request.
     * @param array $query Query parameters.
     *
     * @return array
     */
    public function get_menuLegacy(RequestInterface $request, array $query = []) {
        $this->checkDashboardPermission();
        $this->locale->set($query['locale']);

        $in = Schema::parse([
            "activeUrl:s",
            "section:s?" => [
                "enum" => ["moderation", "settings"],
            ],
            "locale:s",
        ]);

        $params = $in->validate($query);
        $section = ucfirst($query['section'] ?? 'settings');

        $activeUrl = $params['activeUrl'];
        if (filter_var($activeUrl, FILTER_VALIDATE_URL)) {
            $activeUrlPath = parse_url($activeUrl, PHP_URL_PATH);
            $toReplace = $request->getAssetRoot();
            $activeUrlPath = str_replace($toReplace, '', $activeUrlPath);
        } else {
            $activeUrlPath = $activeUrl;
        }

        $dashboardNavModule = new DashboardNavModule();
        $dashboardNavModule->setCurrentSections([$section]);
        $dashboardNavModule->setHighlightRoute($activeUrlPath);

        // Getting menus will call any necessary events.
        $dashboardNavModule->getMenus();

        $result = $dashboardNavModule->toString();
        return [
            'html' => $result,
        ];
    }

    /**
     * Get the menus in the dashboard.
     *
     * @return array Returns the menus.
     */
    public function index_menus() {
        $this->checkDashboardPermission();

        $in = $this->schema([], 'in')->setDescription('List the dashboard menus.');
        $out = $this->schema([
            ':a' => [
                'name:s' => 'The title of the menu.',
                'key:s' => 'The ID of the menu.',
                'description:s' => 'The menu description.',
                'url:s?' =>  'The URL to the menu if it doesn\'t have a submenu.',
                'groups:a' => [
                    'name:s' => 'The title of the group.',
                    'key:s' => 'The key of the group.',
                    'links:a' => [
                        'name:s' => 'The title of the link.',
                        'key:s' => 'The key of the link.',
                        'url:s' => 'The URL of the link.',
                        'react:b' => 'Whether or not the link represents a React component.',
                        'badge?' => [
                            'type' => 'object',
                            'description' => 'Information about a badge to display beside the link.',
                            'properties' => [
                                'type:s' => [
                                    'description' => 'The type of badge.',
                                    'enum' => ['view', 'text'],
                                ],
                                'url:s?' => 'The URL of a view.',
                                'text:s' => 'Literal text for the badge.',
                            ],
                        ],
                    ],
                ],
            ],
        ], 'out');

        $module = new DashboardNavModule();
        $result = $module->getMenus();

        $result = $out->validate($result);

        return $result;
    }

    /**
     * This is the array of permissions from the module.
     * We just want to make sure the user has at least one of the permissions although if they don't then the menus would be empty.
     *
     * @throws PermissionException If the user doesn't have enough permission.
     */
    private function checkDashboardPermission() {
        $this->permission([
            'Garden.Settings.Manage',
            'Garden.Community.Manage',
            'Garden.Moderation.Manage',
            'Garden.Settings.View',
            'Moderation.ModerationQueue.Manage',
            'Garden.Users.Add',
            'Garden.Users.Edit',
            'Garden.Users.Delete',
            'Garden.Users.Approve',
        ]);
    }
}
