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

    use StaticInitializer;

    /**
     * @var array The custom sections for the dashboard.
     */
    protected static $customSections = ['DashboardHome', 'Moderation', 'Settings', 'Analytics'];

    /**
     * @var array The section info for the dashboard's main nav.
     */
    protected static $sectionsInfo = [
        'DashboardHome' => [
            'section' => 'DashboardHome',
            'title' => 'Dashboard',
            'description' => 'Forum Overview',
            'url' => '/settings'
        ],
        'Moderation' => [
            'section' => 'Moderation',
            'title' => 'Moderation',
            'description' => 'Gate Keeping',
            'url' => '/dashboard/log/moderation',
            'permission' => 'Garden.Moderation.Manage'
        ],
        'Settings' => [
            'section' => 'Settings',
            'title' => 'Settings',
            'description' => 'Preferences & Addons',
            'url' => '/dashboard/settings/plugins'
        ]
    ];

    /**
     * @var string The view for rendering the panel
     */
    public $view = 'nav-dashboard';

    protected $defaultEvent = 'Settings';

    public function __construct() {
        parent::$customSections = self::$customSections;
        parent::__construct();
    }

    /**
     * Check user permissions, translate our translate-ables and url-ify our urls. Returns an array of the main sections
     * ready to be put into a view.
     *
     * @return array The sections to display in the main dashboard nav.
     */
    public static function getSectionsInfo() {
        self::initStatic();

        $session = Gdn::session();
        foreach (self::$sectionsInfo as $key => &$section) {
            if (val('permission', $section) && !$session->checkPermission(val('permission', $section))) {
                unset(self::$sectionsInfo[$key]);
            } else {
                $section['title'] = t($section['title']);
                $section['description'] = t($section['description']);
                $section['url'] = url($section['url']);
                $section['active'] = '';
            }
        }

        $currentSections = Gdn_Theme::section('', 'get');

        $activeSet = false;
        foreach($currentSections as $currentSection) {
            if (array_key_exists($currentSection, self::$sectionsInfo)) {
                self::$sectionsInfo[$currentSection]['active'] = 'active';
                $activeSet = true;
            }
        }

        if (!$activeSet) {
            self::$sectionsInfo['Settings']['active'] = 'active';
        }

        return self::$sectionsInfo;
    }

    /**
     * Adds a section to the sections info array to output in the dashboard.
     *
     * @param $section
     */
    public static function registerSection($section) {
        $requiredArrayKeys = ['title', 'description', 'url', 'section'];

        // Make sure we have what we need.
        foreach($requiredArrayKeys as $key) {
            if (!array_key_exists($key, $section)) {
                return;
            }
        }
        self::$sectionsInfo[$section['section']] = $section;
        self::$customSections[] = $section['section'];
    }

    public function prepare() {
        if (!inSection('Dashboard')) {
            return false;
        } else {
            return parent::prepare();
        }
    }
}
