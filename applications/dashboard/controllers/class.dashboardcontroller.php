<?php
/**
 * Master application controller for Dashboard, extended by most others.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Vanilla\Dashboard\Events\DashboardAccessEvent;
use Vanilla\Dashboard\Events\DashboardApiAccessEvent;
use Vanilla\Logging\AuditLogger;

/**
 * Root class for the Dashboard's controllers.
 */
class DashboardController extends Gdn_Controller
{
    /** @var bool Custom theming is not allowed in the dashboard. */
    protected $allowCustomTheming = false;

    protected bool $auditLogEnabled = true;

    /**
     * Set PageName.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct()
    {
        parent::__construct();
        $this->_PageName = "dashboard";
    }

    /**
     * Include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize()
    {
        $this->Head = new HeadModule($this);
        $this->addJsFile("jquery.js");
        $this->addJsFile("jquery.form.js");
        $this->addJsFile("jquery.popin.js");
        $this->addJsFile("jquery.popup.js");
        $this->addJsFile("jquery.gardenhandleajaxform.js");
        $this->addJsFile("magnific-popup.min.js");
        $this->addJsFile("jquery.autosize.min.js");
        $this->addJsFile("global.js");

        if (in_array($this->ControllerName, ["profilecontroller", "activitycontroller"])) {
            $this->addCssFile("style.css");
            $this->addCssFile("vanillicon.css", "static");
        } else {
            $this->addCssFile("admin.css");
            $this->addCssFile("magnific-popup.css", "dashboard");
        }

        $this->MasterView = "admin";
        Gdn_Theme::section("Dashboard");

        parent::initialize();
    }

    /**
     * Override for audit logging.
     *
     * @param $view
     * @param $controllerName
     * @param $applicationFolder
     * @param $assetName
     */
    public function xRender(
        $view = "",
        $controllerName = false,
        $applicationFolder = false,
        $assetName = "Content"
    ): void {
        $this->trackAuditLog();
        parent::xRender($view, $controllerName, $applicationFolder, $assetName);
    }

    /**
     * Track the current request and parent request if available as an audit log.
     *
     * @return void
     */
    public function trackAuditLog(): void
    {
        if (!$this->auditLogEnabled) {
            return;
        }

        $request = \Gdn::request();

        if ($request->getMethod() !== "GET") {
            // We only handle GET requests here.
            // Post requests that actually change things will be audit logged through explicit access.
            return;
        }

        // This is a full request.
        if ($this->isRenderingMasterView()) {
            // This handles full page loads and injects a tag into the pages so we can differentiate between
            // full page requests and modals.
            $accessEvent = new DashboardAccessEvent();
            $this->addDefinition("auditLog", $accessEvent->asPageMeta());
            AuditLogger::log($accessEvent);
            return;
        }

        // Try to construct a parent audit log event.
        $apiEvent = DashboardApiAccessEvent::tryFromHeaders($request);
        if ($apiEvent !== null) {
            AuditLogger::log($apiEvent);
        }
    }

    /**
     * Sets a user's preference for dashboard panel nav collapsing. Collapsed groups are stored in an
     * list, by their 'data-key' attribute on the nav-header <a> element.
     *
     * @throws Gdn_UserException
     */
    public function userPreferenceCollapse()
    {
        if (Gdn::request()->isAuthenticatedPostBack(true)) {
            $key = Gdn::request()->getValue("key");
            $collapsed = Gdn::request()->getValue("collapsed");

            if ($key && $collapsed) {
                $collapsed = $collapsed === "true";
                $session = Gdn::session();
                $collapsedGroups = $session->getPreference("DashboardNav.Collapsed");
                if (!$collapsedGroups) {
                    $collapsedGroups = [];
                }

                if ($collapsed) {
                    $collapsedGroups[$key] = $key;
                } elseif (isset($collapsedGroups[$key])) {
                    unset($collapsedGroups[$key]);
                }

                $session->setPreference("DashboardNav.Collapsed", $collapsedGroups);
            }

            $this->render("blank", "utility", "dashboard");
        }
    }

    /**
     * Sets a user's preference for the landing page for each top-level nav item. Stored in a list as
     * SectionName->url pairs, where SectionName is the 'data-section' attribute on the panel nav link.
     *
     * @throws Gdn_UserException
     */
    public function userPreferenceSectionLandingPage()
    {
        if (Gdn::request()->isAuthenticatedPostBack(true)) {
            $url = Gdn::request()->getValue("url");
            $section = Gdn::request()->getValue("section");

            if ($url && $section) {
                $session = Gdn::session();
                $landingPages = $session->getPreference("DashboardNav.SectionLandingPages");
                if (!$landingPages) {
                    $landingPages = [];
                }

                $landingPages[$section] = $url;
                $session->setPreference("DashboardNav.SectionLandingPages", $landingPages);
            }
            $this->render("blank", "utility", "dashboard");
        }
    }

    /**
     * Saves the name of the section that a user has last navigated to serve as the landing page for whenever they
     * navigate to the dashboard.
     *
     * @throws Gdn_UserException
     */
    public function userPreferenceDashboardLandingPage()
    {
        if (Gdn::request()->isAuthenticatedPostBack(true)) {
            $section = Gdn::request()->getValue("section");
            if ($section && array_key_exists($section, DashboardNavModule::getDashboardNav()->getSectionsInfo())) {
                $session = Gdn::session();
                $session->setPreference("DashboardNav.DashboardLandingPage", $section);
            }

            $this->render("blank", "utility", "dashboard");
        }
    }

    /**
     * @param string $currentUrl
     */
    public function setHighlightRoute($currentUrl = "")
    {
        if ($currentUrl) {
            DashboardNavModule::getDashboardNav()->setHighlightRoute($currentUrl);
        }
    }

    /**
     * @param string $currentUrl
     */
    public function addSideMenu($currentUrl = "")
    {
        deprecated("addSideMenu", "setHighlightRoute");
        $this->setHighlightRoute($currentUrl);
    }
}
