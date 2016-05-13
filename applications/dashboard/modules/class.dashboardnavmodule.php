<?php
/**
 * Dashboard nav module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Module for a list of links.
 */
class DashboardNavModule extends SiteNavModule {

    protected $customSections = ['Dashboard', 'Moderation', 'Settings', 'Analytics'];

    protected $customSectionsInfo = [
        'dashboard' => [
            'title' => 'Dashboard',
            'description' => 'Forum Overview'
        ],
        'moderation' => [
            'title' => 'Moderation',
            'description' => 'Gate Keeping'
        ],
        'settings' => [
            'title' => 'Settings',
            'description' => 'Preferences & Addons'
        ],
        'analytics' => [
            'title' => 'Analytics',
            'description' => 'Eye Candy For Your Boss'
        ]
    ];

    protected $defaultEvent = 'Settings';

    public function __construct() {
        parent::__construct();
    }

    public function prepare() {
        if (!inSection('Dashboard')) {
            return;
        } else {
            return parent::prepare();
        }
    }
}
