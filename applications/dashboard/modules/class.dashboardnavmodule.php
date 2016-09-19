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

    const ACTIVE_SECTION_DEFAULT = 'Settings';

    public $view = 'nav-dashboard';

    /**
     * @var DashboardNavModule The dashboard nav instance.
     */
    protected static $dashboardNav;

    /**
     * @var array The section info for the dashboard's main nav.
     */
    protected static $sectionsInfo = [
        'DashboardHome' => [
            'permission' => [
                'Garden.Settings.View',
                'Garden.Settings.Manage',
                'Garden.Community.Manage',
            ],
            'section' => 'DashboardHome',
            'title' => 'Dashboard',
            'description' => 'Site Overview',
            'url' => '/dashboard/settings/home',
            'empty' => true
        ],
        'Moderation' => [
            'permission' => [
                'Garden.Moderation.Manage',
                'Moderation.ModerationQueue.Manage',
                'Garden.Community.Manage',
                'Garden.Users.Add',
                'Garden.Users.Edit',
                'Garden.Users.Delete',
                'Garden.Settings.Manage',
                'Garden.Users.Approve',
            ],
            'section' => 'Moderation',
            'title' => 'Moderation',
            'description' => 'Community Management',
            'url' => [
                'Garden.Moderation.Manage' => 'dashboard/log/moderation',
                'Moderation.ModerationQueue.Manage' => 'dashboard/log/moderation',
                'Garden.Community.Manage' => '/dashboard/message',
                'Garden.Users.Add' => 'dashboard/user',
                'Garden.Users.Edit' => 'dashboard/user',
                'Garden.Users.Delete' => 'dashboard/user',
                'Garden.Settings.Manage' => '/dashboard/settings/bans',
                'Garden.Users.Approve' => '/dashboard/user/applicants',
            ],
        ],
        'Settings' => [
            'permission' => [
                'Garden.Settings.Manage',
                'Garden.Community.Manage',
            ],
            'section' => 'Settings',
            'title' => 'Settings',
            'description' => 'Configuration & Addons',
            'url' => '/dashboard/settings/banner'
        ]
    ];

    protected static $altSectionsInfo = [
//        'Tutorials' => [
//            'section' => 'Tutorials',
//            'title' => 'Help',
//            'description' => '',
//            'url' => '/dashboard/settings/gettingstarted'
//        ]
    ];

    public function __construct($cssClass = '', $useCssPrefix = true) {
//        self::$altSectionsInfo['Tutorials']['title'] = dashboardSymbol('question-mark');
        parent::__construct($cssClass, $useCssPrefix);
    }

    /**
     * @return DashboardNavModule
     */
    public static function getDashboardNav() {
        if (!isset(self::$dashboardNav)) {
            self::$dashboardNav = new DashboardNavModule();
        }
        return self::$dashboardNav;
    }

    /**
     * Check user permissions, translate our translate-ables. Returns an array of the main sections
     * ready to be put into a view.
     *
     * @return array The sections to display in the main dashboard nav.
     */
    public function getSectionsInfo($alt = false) {


        if (!self::$initStaticFired) {
            self::$initStaticFired = true;
            $this->fireEvent('init');
        }

        $this->handleUserPreferencesSectionLandingPage();

        $sections = $alt ? self::$altSectionsInfo : self::$sectionsInfo;

        $session = Gdn::session();

        foreach ($sections as $key => &$section) {
            if (val('permission', $section) && !$session->checkPermission(val('permission', $section), false)) {
                unset($sections[$key]);
            } else {
                $section['title'] = t($section['title']);
                $section['description'] = t($section['description']);
                $section['active'] = $this->isActiveSection($section['section']) ? 'active' : '';
                $section['url'] = $this->getUrlForSection($key);
            }
        }
        return $sections;
    }

    /**
     * Retrieves or resolves the default url for a section link depending on the sessioned user's permissions.
     *
     * @param $sectionKey The section to get the url for
     * @return string The url associated with the passed section key
     */
    public function getUrlForSection($sectionKey) {
        $section = self::$sectionsInfo[$sectionKey];
        if (is_array(val('url', $section))) {
            // In array form, the url property is stored as 'Permission' => 'url'.
            // Sometimes a section won't have a landing page common to all the permissions it houses.
            // The url gets resolved to the first url the user has permission to see.
            foreach($section['url'] as $permission => $url) {
                if (Gdn::session()->checkPermission($permission)) {
                    return $url;
                }
            }
        }

        return val('url', $section, '/');
    }

    private function getActiveSection() {
        $allSections = array_merge(self::$sectionsInfo, self::$altSectionsInfo);
        $currentSections = Gdn_Theme::section('', 'get');
        foreach ($currentSections as $currentSection) {
            if (array_key_exists($currentSection, $allSections )) {
                return $currentSection;
            }
        }
        return self::ACTIVE_SECTION_DEFAULT;
    }

    private function isActiveSection($section) {

        $allSectionsInfo = array_merge(self::$sectionsInfo, self::$altSectionsInfo);
        $allSections = [];
        foreach ($allSectionsInfo as $sectionInfo) {
            $allSections[] = $sectionInfo['section'];
        }

        $currentSections = Gdn_Theme::section('', 'get');
        $found = false;

        foreach ($currentSections as $currentSection) {
            if ($currentSection == $section) {
                return true;
            }
            if (in_array($currentSection, $allSections)) {
                $found = true;
            }
        }

        // We're active if the section is 'Settings' and the $currentSection doesn't exist in allsections
        if (!$found && $section == self::ACTIVE_SECTION_DEFAULT) {
            return true;
        }

        return false;
    }

    private function handleUserPreferencesNav() {
        if ($session = Gdn::session()) {
            $collapsed = $session->getPreference('DashboardNav.Collapsed', []);
            $section = $this->getActiveSection();

            foreach($this->items as &$item) {
                if (array_key_exists(val('headerCssClass', $item), $collapsed)) {
                    $item['collapsed'] = 'collapsed';
                    $item['ariaExpanded'] = 'false';
                    $item['collapsedList'] = '';
                } else {
                    $item['collapsed'] = '';
                    $item['ariaExpanded'] = 'true';
                    $item['collapsedList'] = 'in';
                }
                if (isset($item['items'])) {
                    foreach($item['items'] as &$subitem) {
                        $subitem['section'] = $section;
                    }
                }
            }
        }
    }

    private function handleUserPreferencesSectionLandingPage() {
        if ($session = Gdn::session()) {
            $landingPages = $session->getPreference('DashboardNav.SectionLandingPages');

            foreach (self::$sectionsInfo as $key => $section) {
                if (array_key_exists($key, $landingPages)) {
                    self::$sectionsInfo[$key]['url'] = $landingPages[$key];
                }
            }
        }
    }

    /**
     * Adds a section to the sections info array to output in the dashboard.
     *
     * @param $section
     */
    public function registerSection($section) {
        $requiredArrayKeys = ['title', 'description', 'url', 'section'];

        // Make sure we have what we need.
        foreach ($requiredArrayKeys as $key) {
            if (!array_key_exists($key, $section)) {
                return;
            }
        }
        self::$sectionsInfo[$section['section']] = $section;
    }

    public function handleEmpty() {
        $section = $this->getActiveSection();
        $section = val($section, self::$sectionsInfo);
        if (val('empty', $section) === true) {
            $this->items = [];
        }
    }

    public function prepare() {
        parent::prepare();
        $this->handleEmpty();
        $this->handleUserPreferencesNav();
        return true;
    }

    public function toString() {
        if (!self::$initStaticFired) {
            self::$initStaticFired = true;
            $this->fireEvent('init');
        }
        $this->fireAs(get_called_class())->fireEvent('render');
        return parent::toString();
    }
}
