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
     * @var array The section info for the dashboard's main nav.
     */
    protected static $sectionsInfo = [
        'DashboardHome' => [
            'section' => 'DashboardHome',
            'title' => 'Dashboard',
            'description' => 'Site Overview',
            'url' => '/settings'
        ],
        'Moderation' => [
            'section' => 'Moderation',
            'title' => 'Moderation',
            'description' => 'Community Management',
            'url' => '/dashboard/log/moderation',
            'permission' => 'Garden.Moderation.Manage'
        ],
        'Settings' => [
            'section' => 'Settings',
            'title' => 'Settings',
            'description' => 'Configuration & Addons',
            'url' => '/dashboard/settings/banner'
        ]
    ];

    protected static $altSectionsInfo = [
        'Tutorials' => [
            'section' => 'Tutorials',
            'title' => 'Help',
            'description' => '',
            'url' => '/dashboard/settings/gettingstarted'
        ]
    ];

    public function __construct($cssClass = '', $useCssPrefix = true) {
        self::$altSectionsInfo['Tutorials']['title'] = dashboardSymbol('question-mark');
        parent::__construct($cssClass, $useCssPrefix);
    }

    /**
     * Check user permissions, translate our translate-ables and url-ify our urls. Returns an array of the main sections
     * ready to be put into a view.
     *
     * @return array The sections to display in the main dashboard nav.
     */
    public function getSectionsInfo($alt = false) {

        if (!self::$initStaticFired) {
            self::$initStaticFired = true;
            $this->fireEvent('init');
        }

        $sections = $alt ? self::$altSectionsInfo : self::$sectionsInfo;

        $session = Gdn::session();
        foreach ($sections as $key => &$section) {
            if (val('permission', $section) && !$session->checkPermission(val('permission', $section))) {
                unset($sections[$key]);
            } else {
                $section['title'] = t($section['title']);
                $section['description'] = t($section['description']);
                $section['url'] = url($section['url']);
                $section['active'] = $this->isActiveSection($section['section']) ? 'active' : '';
            }
        }
        return $sections;
    }

    public function isActiveSection($section) {

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

    public function toString() {
        if (!self::$initStaticFired) {
            self::$initStaticFired = true;
            $this->fireEvent('init');
        }
        $this->fireAs(get_called_class())->fireEvent('render');
        return parent::toString();
    }
}
