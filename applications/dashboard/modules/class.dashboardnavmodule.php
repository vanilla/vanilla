<?php
/**
 * Dashboard nav module.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Vanilla\FeatureFlagHelper;

/**
 * Renders the dashboard nav.
 *
 * Handles the manipulation of the Dashboard sections, which are the top-level nav items appearing in the dashboard nav bar.
 * Rendering this module will render only the side nav. The section menu needs to be rendered manually using the
 * `getSectionsInfo()` function.
 */
class DashboardNavModule extends SiteNavModule
{
    /** @var string The active section if the theme section we're in doesn't match any section in the dashboard. */
    const ACTIVE_SECTION_DEFAULT = "Analytics";

    /** @var string The default section when adding items to the navigation. */
    const SECTION_DEFAULT = "Settings";

    /**  @var string The view for the panel navigation. */
    public $view = "nav-dashboard";

    /** @var DashboardNavModule The dashboard nav instance. */
    private static $dashboardNav;

    /**
     * The section info for the dashboard's main nav.
     *
     * A user must have one of the permissions in the permission list of a section in order to see that section.
     * The default landing page for the section will be the first page in the url list that the user has permission to view.
     * A section can declare itself as 'empty' which means that no panel nav will render for that section.
     *
     * @var array
     */
    private static $sectionsInfo = [
        "Moderation" => [
            "permission" => [
                "Garden.Moderation.Manage",
                "Vanilla.Posts.Manage",
                "Garden.Community.Manage",
                "Garden.Users.Add",
                "Garden.Users.Edit",
                "Garden.Users.Delete",
                "Garden.Settings.Manage",
                "Garden.Users.Approve",
            ],
            "section" => "Moderation",
            "title" => "Moderation",
            "description" => "Community Management",
            "url" => [
                "Garden.Moderation.Manage" => "/dashboard/content/reports",
                "Vanilla.Posts.Manage" => "/dashboard/content/reports",
                "staff.allow" => "/dashboard/content/triage",
                "Garden.Users.Add" => "/dashboard/user",
                "Garden.Users.Edit" => "/dashboard/user",
                "Garden.Users.Delete" => "/dashboard/user",
                "Garden.Community.Manage" => "/dashboard/message",
                "Garden.Settings.Manage" => "/dashboard/settings/bans",
                "Garden.Users.Approve" => "/dashboard/user/applicants",
            ],
        ],
        "Analytics" => [
            "permission" => ["Garden.Settings.View", "Garden.Settings.Manage", "Garden.Community.Manage"],
            "section" => "Analytics",
            "title" => "Analytics",
            "description" => "Visualize Your Community",
            "url" => "/dashboard/settings/home",
            "empty" => true,
        ],
        "Appearance" => [
            "permission" => ["Garden.Settings.Manage"],
            "section" => "Appearance",
            "title" => "Appearance",
            "description" => "Customize your community",
            "url" => "/appearance",
        ],
        "Settings" => [
            "permission" => ["Garden.Settings.Manage", "Garden.Community.Manage"],
            "section" => "Settings",
            "title" => "Settings",
            "description" => "Configuration & Addons",
            "url" => "/dashboard/role",
        ],
    ];

    /**
     * DashboardNavModule constructor.
     * @param string $cssClass The CSS class for the panel nav wrapper.
     * @param bool $useCssPrefix Whether to use CSS prefixes for the items in the panel nav.
     */
    public function __construct($cssClass = "", $useCssPrefix = true)
    {
        parent::__construct($cssClass, $useCssPrefix);
    }

    /**
     * Gets the instance of the dashboard nav module.
     *
     * @return DashboardNavModule
     */
    public static function getDashboardNav()
    {
        if (!isset(self::$dashboardNav)) {
            self::$dashboardNav = new DashboardNavModule();
        }
        return self::$dashboardNav;
    }

    /**
     * Compiles our section info and filters it according to a user's permissions. Info is properly sanitized
     * to be rendered in a view.
     *
     * @param bool $handleLandingPreference Backwards compatibility, new components should not carry this feature moving forward.
     * @return array The sections to display in the main dashboard nav.
     * @throws Exception
     */
    public function getSectionsInfo($handleLandingPreference = true)
    {
        if (!self::isInitStaticFired()) {
            self::setInitStaticFired(true);
            $this->fireEvent("init");
        }

        if ($handleLandingPreference) {
            $this->handleUserPreferencesSectionLandingPage();
        }
        $session = Gdn::session();

        $sections = self::$sectionsInfo;

        foreach ($sections as $key => &$section) {
            if (val("permission", $section) && !$session->checkPermission(val("permission", $section), false)) {
                unset($sections[$key]);
            } else {
                $section["title"] = t($section["title"]);
                $section["description"] = t($section["description"]);
                $section["active"] = $this->isActiveSection($section["section"]) ? "active" : "";
                $section["url"] = $this->getUrlForSection($key);
            }
        }
        return $sections;
    }

    /**
     * Retrieves or resolves the default url for a section link depending on the sessioned user's permissions.
     *
     * @param string $sectionKey The section to get the url for
     * @return string The url associated with the passed section key
     */
    public function getUrlForSection($sectionKey)
    {
        $section = self::$sectionsInfo[$sectionKey];
        if (is_array(val("url", $section))) {
            // In array form, the url property is stored as 'Permission' => 'url'.
            // Sometimes a section won't have a landing page common to all the permissions it houses.
            // The url gets resolved to the first url the user has permission to see.
            foreach ($section["url"] as $permission => $url) {
                if (Gdn::session()->checkPermission($permission)) {
                    // Maybe we want this to more dynamic later?
                    if ($sectionKey === "Moderation") {
                        if (c("triage.enabled") && checkPermission("staff.allow")) {
                            return "/dashboard/content/triage";
                        }

                        if (FeatureFlagHelper::featureEnabled("escalations")) {
                            return "/dashboard/content/reports";
                        } else {
                            return "/dashboard/log/moderation";
                        }
                    }
                    return $url;
                }
            }
        }

        return val("url", $section, "/");
    }

    /**
     * Checks the current theme section against the dashboard sections to find which one is active.
     * If the current theme section doesn't match one of our sections, return the default active section.
     *
     * @return string The active section.
     */
    public function getActiveSection()
    {
        $currentSections = Gdn_Theme::section("", "get");
        foreach ($currentSections as $currentSection) {
            if (array_key_exists($currentSection, self::$sectionsInfo)) {
                return $currentSection;
            }
        }
        return self::ACTIVE_SECTION_DEFAULT;
    }

    /**
     * Check to see if a section is active.
     *
     * @param string $section The section to check whether it's active.
     * @return bool Whether the section is the active section.
     */
    private function isActiveSection($section)
    {
        $allSections = [];
        foreach (self::$sectionsInfo as $sectionInfo) {
            $allSections[] = $sectionInfo["section"];
        }

        $currentSections = Gdn_Theme::section("", "get");
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
     * Handle the panel navigation collapsing preferences for the user.
     * Checks to see if any nav items have been collapsed by the user and adds data to collapse
     * those items in the nav view.
     */
    private function handleUserPreferencesNav()
    {
        if ($session = Gdn::session()) {
            $collapsed = $session->getPreference("DashboardNav.Collapsed", []);
            $section = $this->getActiveSection();
            $items = $this->getItems();
            foreach ($items as &$item) {
                if (array_key_exists(val("headerCssClass", $item), $collapsed)) {
                    $item["collapsed"] = "collapsed";
                    $item["ariaExpanded"] = "false";
                    $item["collapsedList"] = "";
                } else {
                    $item["collapsed"] = "";
                    $item["ariaExpanded"] = "true";
                    $item["collapsedList"] = "in";
                }
                if (isset($item["items"])) {
                    foreach ($item["items"] as &$subitem) {
                        $subitem["section"] = $section;
                    }
                }
            }
            $this->setItems($items);
        }
    }

    /**
     * Handle the section landing page preferences for the user. This is the page that appears when the
     * user clicks on a top-level nav item. Changes the url for the section items according to what is
     * set in user preferences.
     */
    private function handleUserPreferencesSectionLandingPage()
    {
        if ($session = Gdn::session()) {
            $landingPages = $session->getPreference("DashboardNav.SectionLandingPages", []);

            foreach (self::$sectionsInfo as $key => $section) {
                if (array_key_exists($key, $landingPages)) {
                    self::$sectionsInfo[$key]["url"] = $landingPages[$key];
                }
            }
        }
    }

    /**
     * Adds a section to the sections info array to output in the dashboard.
     *
     * @param array $section An array that contains at least the following keys: 'title', 'description', 'url', 'section'.
     */
    public function registerSection($section)
    {
        $requiredArrayKeys = ["title", "description", "url", "section"];

        // Make sure we have what we need.
        foreach ($requiredArrayKeys as $key) {
            if (!array_key_exists($key, $section)) {
                return;
            }
        }
        self::$sectionsInfo[$section["section"]] = $section;
    }

    /**
     * Clear all items for sections that have declared themselves as 'empty'.
     */
    public function handleEmpty()
    {
        $section = $this->getActiveSection();
        $section = val($section, self::$sectionsInfo);
        if (val("empty", $section) === true) {
            $this->setItems([]);
        }
    }

    /**
     * Prepares the nav for rendering.
     *
     * @return bool Whether the panel nav is cleared for rendering.
     */
    public function prepare()
    {
        $prepared = parent::prepare();
        $this->handleEmpty();
        $this->handleUserPreferencesNav();
        return $prepared;
    }

    /**
     * Render the panel nav.
     *
     * @return string The panel nav HTML.
     * @throws Exception
     */
    public function toString()
    {
        if (!self::isInitStaticFired()) {
            self::setInitStaticFired(true);
            $this->fireEvent("init");
        }

        $this->fireAs(get_called_class())->fireEvent("render");
        return parent::toString();
    }

    /**
     * Get all of the sections and menus in the dashboard.
     *
     * @return array Returns a nested array of menus.
     * @throws Exception Throws an exception if the module isn't configured properly.
     */
    public function getMenus()
    {
        static $fetched = false;

        // The init event must be fired first in order to put things in their proper order.
        if (!self::isInitStaticFired()) {
            self::setInitStaticFired(true);
            $this->fireEvent("init");
        }

        if (!$fetched) {
            $navAdapter = new NestedCollectionAdapter($this);
            $this->EventArguments["SideMenu"] = $navAdapter;
            $this->fireEvent("GetAppSettingsMenuItems");

            $this->fireAs(get_called_class())->fireEvent("render");
            $fetched = true;
        }

        $rawSections = $this->getSectionsInfo(false);
        $items = DashboardNavModule::getSectionItems();

        $menus = [];
        foreach ($rawSections as $s) {
            $menu = [
                "name" => $s["title"],
                "id" => ($sectionID = strtolower($s["section"])),
                "description" => $s["description"],
                "url" => $s["url"],
            ];

            $groups = [];
            if (isset($items[$sectionID])) {
                $rawSection = $items[$sectionID];

                foreach ($rawSection["groups"] as $g) {
                    $section = [
                        "id" => $g["key"],
                        "name" => $g["text"] ?? "",
                        "links" => [],
                    ];

                    $groups[$section["id"]] = $section;
                }

                $links = $rawSection["links"] ?? [];
                $this->sortItems($links);
                foreach ($links as $k => $l) {
                    [$groupID, $linkID] = explode(".", $k);
                    $link = [
                        "name" => strip_tags($l["text"]),
                        "id" => $linkID,
                        "parentID" => $sectionID,
                        "url" => $l["url"],
                        "react" => false,
                    ];

                    if (!empty($l["modifiers"]["badge"])) {
                        $link["badge"] = [
                            "type" => "text",
                            "text" => $l["modifiers"]["badge"],
                        ];
                    } elseif (!empty($l["modifiers"]["popinRel"])) {
                        $link["badge"] = [
                            "type" => "view",
                            "url" => $l["modifiers"]["popinRel"],
                        ];
                    }

                    $groups[$groupID]["children"][] = $link;
                }

                // Remove sections with no links.
                $groups = array_filter($groups, function ($s) {
                    return !empty($s["children"]);
                });
            }
            if (!empty($groups) || !empty($menu["url"])) {
                $menu["children"] = array_values($groups);
                $menus[] = $menu;
            }
        }

        return $menus;
    }
}
