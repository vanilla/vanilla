<?php
/**
 * DashboardHooks class.
 *
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Garden\Container\Container;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Container\Reference;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ResponseException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\AddonManager;
use Vanilla\Contracts;
use Vanilla\Dashboard\Events\UserSpoofEvent;
use Vanilla\Dashboard\Modules\CommunityLeadersModule;
use Vanilla\Exception\PermissionException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Models\CommunityManagement\EscalationModel;
use Vanilla\Logging\AuditLogger;
use Vanilla\Utility\ContainerUtils;
use Vanilla\Web\SystemTokenUtils;
use Vanilla\Widgets\WidgetService;

/**
 * Event handlers for the Dashboard application.
 */
class DashboardHooks extends Gdn_Plugin implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var string */
    private string $mobileThemeKey;

    /** @var string */
    private string $desktopThemeKey;

    /** @var null|bool */
    private $spoofEnabled = null;

    /**
     * Constructor for DI.
     */
    public function __construct(
        private AddonManager $addonManager,
        private SessionModel $sessionModel,
        private Contracts\ConfigurationInterface $config
    ) {
        // DO NOT inject other things here.
        // This set of event handlers is always loaded super early and anything you inject here has potential to be polluted
        // in the container.
        parent::__construct();
        $this->mobileThemeKey = $config->get("Garden.MobileTheme");
        $this->desktopThemeKey = $config->get("Garden.Theme");
    }

    /**
     * Add emoji config to a controller's JavaScript definitions object.
     *
     * @param Gdn_Controller $controller
     * @throws ContainerException
     * @throws NotFoundException
     */
    private function addEmojiDefinitions(Gdn_Controller $controller)
    {
        // Fetched lazily and not in the constructor to allow addons to manipulate it.
        $emoji = \Gdn::getContainer()->get(Emoji::class);
        if ($emoji->isEnabled() === false) {
            return;
        }

        $controller->addDefinition("emoji", $emoji->getWebConfig());
    }

    /**
     * Install the formatter to the container.
     *
     * @param Container $dic The container to initialize.
     */
    public function container_init_handler(Container $dic)
    {
        $dic->rule("HeadModule")
            ->setShared(true)
            ->addAlias("Head")
            ->rule("MenuModule")
            ->setShared(true)
            ->addAlias("Menu")
            ->rule("Gdn_Dispatcher")
            ->addCall("passProperty", ["Menu", new Reference("MenuModule")])
            ->rule(\Vanilla\Menu\CounterModel::class)
            ->addCall("addProvider", [new Reference(ActivityCounterProvider::class)])
            ->addCall("addProvider", [new Reference(RoleCounterProvider::class)])
            ->rule(WidgetService::class)
            ->addCall("registerWidget", [CommunityLeadersModule::class]);

        $mf = \Vanilla\Models\ModelFactory::fromContainer($dic);
        $mf->addModel("user", UserModel::class, "u");

        $privateIPs = \Gdn::config("Garden.Privacy.IPs");

        if (in_array($privateIPs, ["full", "partial"])) {
            ContainerUtils::addCall($dic, \Gdn_Request::class, "anonymizeIP", [$privateIPs === "full"]);
            // This is a kludge, but given this is a privacy setting, let's ensure newed up IPs are used properly.
            $_SERVER["HTTP_CLIENT_IP"] = $_SERVER["HTTP_X_FORWARDED_FOR"] = $_SERVER[
                "REMOTE_ADDR"
            ] = \Gdn::request()->getIP();
        }
    }

    /**
     * Fire before every page render.
     *
     * @param Gdn_Controller $sender
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function base_render_before($sender)
    {
        if (!\Gdn::config("Garden.Installed", false)) {
            // Don't run any of this if we aren't installed.
            return;
        }
        $session = Gdn::session();

        if (
            $sender->MasterView == "admin" &&
            ($sender->isRenderingMasterView() || $sender->deliveryType() === DELIVERY_TYPE_VIEW)
        ) {
            if (val("Form", $sender)) {
                $sender->Form->setStyles("bootstrap");
            }

            $sender->CssClass = htmlspecialchars($sender->CssClass);
            $sections = Gdn_Theme::section(null, "get");
            if (is_array($sections)) {
                foreach ($sections as $section) {
                    $sender->CssClass .= " Section-" . $section;
                }
            }

            // Get our plugin nav items.
            $navAdapter = new NestedCollectionAdapter(DashboardNavModule::getDashboardNav());
            $sender->EventArguments["SideMenu"] = $navAdapter;
            $sender->fireEvent("GetAppSettingsMenuItems");

            $sender->removeJsFile("jquery.popup.js");
            $sender->addJsFile("vendors/jquery.checkall.min.js", "dashboard");
            $sender->addJsFile("buttongroup.js", "dashboard");
            $sender->addJsFile("dashboard.js", "dashboard");
            $sender->addJsFile("jquery.expander.js");
            $sender->addJsFile("settings.js", "dashboard");
            $sender->addJsFile("vendors/tether.min.js", "dashboard");
            $sender->addJsFile("vendors/bootstrap/util.js", "dashboard");
            $sender->addJsFile("vendors/drop.min.js", "dashboard");
            $sender->addJsFile("vendors/moment.min.js", "dashboard");
            $sender->addJsFile("vendors/daterangepicker.js", "dashboard");
            $sender->addJsFile("vendors/bootstrap/tooltip.js", "dashboard");
            $sender->addJsFile("vendors/clipboard.min.js", "dashboard");
            $sender->addJsFile("vendors/bootstrap/dropdown.js", "dashboard");
            $sender->addJsFile("vendors/bootstrap/collapse.js", "dashboard");
            $sender->addJsFile("vendors/bootstrap/modal.js", "dashboard");
            $sender->addJsFile("vendors/icheck.min.js", "dashboard");
            $sender->addJsFile("jquery.tablejenga.js", "dashboard");
            $sender->addJsFile("vendors/prettify/prettify.js", "dashboard");
            $sender->addJsFile("vendors/ace/ace.js", "dashboard");
            $sender->addJsFile("vendors/ace/ext-searchbox.js", "dashboard");
            $sender->addCssFile("vendors/tomorrow.css", "dashboard");
        }

        if ($session->isValid()) {
            $confirmed = val("Confirmed", Gdn::session()->User, true);
            if (UserModel::requireConfirmEmail() && !$confirmed) {
                $message = formatString(
                    t(
                        "You need to confirm your email address.",
                        'You need to confirm your email address. Click <a href="{/entry/emailconfirmrequest,url}">here</a> to resend the confirmation email.'
                    )
                );
                $sender->informMessage($message, "");
            }
        }

        // Add Message Modules (if necessary)
        $location = $sender->Application . "/" . substr($sender->ControllerName, 0, -10) . "/" . $sender->RequestMethod;
        $exceptions = ["all"];

        if (in_array($sender->MasterView, ["", "default"])) {
            $exceptions[] = "[NonAdmin]";
        }

        // SignIn popup is a special case
        $signInOnly = $sender->deliveryType() == DELIVERY_TYPE_VIEW && $location == "Dashboard/entry/signin";
        if ($signInOnly) {
            $exceptions = [];
        }

        // All registration pages should display "Register" messages.
        $location =
            strpos(strtolower($location), "dashboard/entry/register") === 0 ? "dashboard/entry/register" : $location;
        $messageModel = new MessageModel();
        $layoutViewTypes = $messageModel->getActiveLayoutViewTypes();
        $locationMap = array_change_key_case($messageModel->getLocationMap(), CASE_LOWER);
        $location = $locationMap[strtolower($location)] ?? $location;

        if (
            $sender->MasterView != "admin" &&
            !$sender->data("_NoMessages") &&
            ((val("MessagesLoaded", $sender) != "1" &&
                $sender->MasterView != "empty" &&
                arrayInArray($exceptions, $layoutViewTypes, false)) ||
                inArrayI($location, $layoutViewTypes))
        ) {
            $messageData = $messageModel->getMessagesForLocation(
                $location,
                $exceptions,
                $sender->data("Category.CategoryID")
            );
            foreach ($messageData as $message) {
                $message["CssClass"] = ucfirst($message["Type"]) . "Message";
                $messageModule = new MessageModule($sender, $message);
                if ($signInOnly) {
                    // Insert special messages even in SignIn popup
                    echo $messageModule;
                } elseif ($sender->isRenderingMasterView()) {
                    $sender->addModule($messageModule);
                }
            }
            $sender->MessagesLoaded = "1"; // Fixes a bug where render gets called more than once and messages are loaded/displayed redundantly.
        }

        // Allow forum embedding
        if ($embed = c("Garden.Embed.Allow")) {
            // Record the remote url where the forum is being embedded.
            $remoteUrl = c("Garden.Embed.RemoteUrl");
            if ($remoteUrl) {
                $sender->addDefinition("RemoteUrl", $remoteUrl);
            }
            if ($remoteUrlFormat = c("Garden.Embed.RemoteUrlFormat")) {
                $sender->addDefinition("RemoteUrlFormat", $remoteUrlFormat);
            }

            // Force embedding?
            if (!isSearchEngine() && strtolower($sender->ControllerName) != "entry") {
                if (isMobile()) {
                    $forceEmbedForum = c("Garden.Embed.ForceMobile") ? "1" : "0";
                } else {
                    $forceEmbedForum = c("Garden.Embed.ForceForum") ? "1" : "0";
                }

                $sender->addDefinition("ForceEmbedForum", $forceEmbedForum);
                $sender->addDefinition("ForceEmbedDashboard", c("Garden.Embed.ForceDashboard") ? "1" : "0");
            }

            $sender->addDefinition("Path", Gdn::request()->path());

            $get = Gdn::request()->get();
            $sender->addDefinition("Query", http_build_query($get));
            // $Sender->addDefinition('MasterView', $Sender->MasterView);
            $sender->addDefinition("InDashboard", $sender->MasterView == "admin" ? "1" : "0");

            if (FeatureFlagHelper::featureEnabled("newEmbedSystem")) {
                // New embed system assets go through webpack.
            } elseif ($embed === 2) {
                $sender->addJsFile("vanilla.embed.local.js");
            } else {
                $sender->addJsFile("embed_local.js");
            }
        } else {
            $sender->setHeader("X-Frame-Options", "SAMEORIGIN");
        }

        // Allow return to mobile site
        $forceNoMobile = val("X-UA-Device-Force", $_COOKIE);
        if ($forceNoMobile === "desktop") {
            $sender->addAsset(
                "Foot",
                wrap(anchor(t("Back to Mobile Site"), "/profile/nomobile/1", "js-hijack"), "div"),
                "MobileLink"
            );
        }

        // Allow global translation of TagHint
        if (c("Tagging.Discussions.Enabled")) {
            $sender->addDefinition("TaggingAdd", Gdn::session()->checkPermission("Vanilla.Tagging.Add"));
            $sender->addDefinition("TaggingSearchUrl", Gdn::request()->url("tags/search"));
            $sender->addDefinition("MaxTagsAllowed", c("Vanilla.Tagging.Max", 5));
            $sender->addDefinition("TagHint", t("TagHint", "Start to type..."));
        }

        // Add symbols.
        if ($sender->deliveryMethod() === DELIVERY_METHOD_XHTML) {
            $sender->addAsset("Symbols", $sender->fetchView("symbols", "", "Dashboard"));
        }

        // Add emoji.
        $this->addEmojiDefinitions($sender);
    }

    /**
     * Checks if the user is previewing a theme and, if so, updates the default master view.
     *
     * @param Gdn_Controller $sender
     * @throws Gdn_UserException
     */
    public function base_beforeFetchMaster_handler($sender)
    {
        $session = Gdn::session();
        if (!$session->isValid()) {
            return;
        }
        if (isMobile()) {
            $theme = htmlspecialchars($session->getPreference("PreviewMobileThemeFolder", ""));
        } else {
            $theme = htmlspecialchars($session->getPreference("PreviewThemeFolder", ""));
        }
        $isDefaultMaster = $sender->MasterView == "default" || $sender->MasterView == "";
        if ($theme != "" && $isDefaultMaster) {
            $themeHtmlFile = paths(PATH_THEMES, $theme, "views", "default.master.tpl");
            $themeAddonHtmlFile = paths(PATH_ADDONS_THEMES, $theme, "views", "default.master.tpl");
            if (file_exists($themeHtmlFile)) {
                $sender->EventArguments["MasterViewPath"] = $themeHtmlFile;
            } elseif (file_exists($themeAddonHtmlFile)) {
                $sender->EventArguments["MasterViewPath"] = $themeAddonHtmlFile;
            } else {
                // for default theme
                $sender->EventArguments["MasterViewPath"] = $sender->fetchViewLocation(
                    "default.master",
                    "",
                    "dashboard"
                );
            }
        }
    }

    /**
     * Setup dashboard navigation.
     *
     * @param $sender
     */
    public function dashboardNavModule_init_handler($sender)
    {
        /** @var DashboardNavModule $nav */
        $nav = $sender;

        $session = Gdn::session();

        $statusModel = \Gdn::getContainer()->get(DiscussionStatusModel::class);
        $reportModel = \Gdn::getContainer()->get(\Vanilla\Forum\Models\CommunityManagement\ReportModel::class);
        $escalationModel = \Gdn::getContainer()->get(EscalationModel::class);

        $sort = -1; // Ensure these nav items come before any plugin nav items.

        $triageCount = $statusModel->getUnresolvedCount(limit: 1000, cached: false);
        if ($triageCount >= 1000) {
            $triageCount = "{$triageCount}+";
        }

        $reportCount = $reportModel->countVisibleReports(
            [
                "status" => \Vanilla\Forum\Models\CommunityManagement\ReportModel::STATUS_NEW,
            ],
            limit: 1000
        );
        if ($reportCount >= 1000) {
            $reportCount = "{$reportCount}+";
        }

        $escalationCount = $escalationModel->queryEscalationsCount(
            [
                "status" => [EscalationModel::STATUS_OPEN, EscalationModel::STATUS_IN_PROGRESS],
            ],
            limit: 1000
        );
        if ($escalationCount >= 1000) {
            $escalationCount = "{$escalationCount}+";
        }

        $escalationsEnabled = FeatureFlagHelper::featureEnabled("escalations");

        $nav->addGroupToSection("Moderation", t("Posts"), "content")
            ->addLinkToSectionIf(
                $this->config->get("triage.enabled") && $session->checkPermission(["staff.allow"], false),
                "Moderation",
                t("Triage"),
                "/dashboard/content/triage",
                "content.triage",
                "",
                $sort,
                ["badge" => $triageCount]
            )
            ->addLinkToSectionIf(
                $this->config->get("Feature.CommunityManagementBeta.Enabled") &&
                    $session->checkPermission(["community.moderate", "posts.moderate"], false),
                "Moderation",
                t("Reports"),
                "/dashboard/content/reports",
                "content.reports",
                "",
                $sort,
                ["badge" => $reportCount]
            )
            ->addLinkToSectionIf(
                $this->config->get("Feature.CommunityManagementBeta.Enabled") &&
                    $session->checkPermission(["community.moderate", "posts.moderate"], false),
                "Moderation",
                t("Escalations"),
                "/dashboard/content/escalations",
                "content.escalations",
                "",
                $sort,
                ["badge" => $escalationCount]
            );

        if ($escalationsEnabled) {
            $nav->addGroupToSection("Moderation", t("Activity & Registration"), "content-other");
        }

        $nav->addLinkToSectionIf(
            $session->checkPermission(["community.moderate"], false),
            "Moderation",
            t("Spam Queue"),
            "/dashboard/log/spam",
            $escalationsEnabled ? "content-other.spam-queue" : "content.spam-queue",
            "",
            $sort
        )
            ->addLinkToSectionIf(
                $session->checkPermission(["community.moderate"], false),
                "Moderation",
                t("Moderation Queue"),
                "/dashboard/log/moderation",
                $escalationsEnabled ? "content-other.moderation-queue" : "content.moderation-queue",
                "",
                $sort,
                ["popinRel" => "/dashboard/log/count/moderate"],
                false
            )
            ->addLinkToSectionIf(
                $session->checkPermission(["community.moderate"], false),
                "Moderation",
                t("Change Log"),
                "/dashboard/log/edits",
                "settings.change-log",
                "",
                1 // Always last
            );

        $nav->addGroupToSection("Moderation", t("Users"), "users")
            ->addLinkToSectionIf(
                $session->checkPermission("Garden.Users.Approve"),
                "Moderation",
                t("Applicants"),
                "/dashboard/user/applicants",
                "users.applicants",
                "",
                $sort,
                ["popinRel" => "/dashboard/user/applicantcount"],
                false
            )
            ->addLinkToSectionIf(
                $session->checkPermission(["Garden.Users.Add", "Garden.Users.Edit", "Garden.Users.Delete"], false),
                "Moderation",
                t("Manage Users"),
                "/dashboard/user",
                "users.members",
                "",
                $sort
            )
            ->addLinkToSectionIf(
                \Vanilla\FeatureFlagHelper::featureEnabled(ManageController::FEATURE_ROLE_APPLICATIONS) &&
                    $session->checkPermission("Garden.Community.Manage"),
                "Moderation",
                t("Role Applicants"),
                "/manage/requests/role-applications",
                "users.role-applications",
                "",
                $sort
            );

        $nav->addGroupToSection("Moderation", t("Settings"), "settings")
            ->addLinkToSectionIf(
                "Garden.Community.Manage",
                "Moderation",
                t("Messages"),
                "/dashboard/message",
                "settings.messages",
                "",
                $sort
            )
            ->addLinkToSectionIf(
                "community.moderate",
                "Moderation",
                t("Content Settings"),
                "/dashboard/content/settings",
                "settings.content",
                "",
                $sort
            )
            ->addLinkToSectionIf(
                "community.moderate",
                "Moderation",
                t("Premoderation Settings"),
                "/dashboard/content/premoderation",
                "settings.premoderation",
                "",
                $sort
            )
            ->addLinkToSectionIf(
                Gdn::config("Feature.CommunityManagementBeta.Enabled") &&
                    Gdn::config("Feature.escalations.Enabled") &&
                    Gdn::config("Feature.AutomationRules.Enabled") &&
                    $session->checkPermission(["community.moderate"], false),
                "Moderation",
                t("Escalation Rules"),
                "/dashboard/content/escalation-rules",
                "settings.escalation-rules",
                "",
                $sort
            )
            ->addLinkToSectionIf(
                "Garden.Settings.Manage",
                "Moderation",
                t("Ban Rules"),
                "/dashboard/settings/bans",
                "settings.bans",
                "",
                $sort
            );

        $nav->addGroup(t("Membership"), "users", "", ["after" => "appearance"])
            ->addLinkIf(
                $session->checkPermission(["Garden.Settings.Manage", "Garden.Roles.Manage"], false),
                t("Roles & Permissions"),
                "/dashboard/role",
                "users.roles",
                "",
                $sort
            )
            ->addLinkIf(
                "Garden.Settings.Manage",
                t("Registration"),
                "/dashboard/settings/registration",
                "users.registration",
                "",
                $sort
            )
            ->addLinkIf(
                "Garden.Settings.Manage",
                t("User Profile"),
                "/dashboard/settings/profile",
                "users.profile",
                "",
                $sort
            )
            ->addLinkIf(
                "Garden.Settings.Manage",
                t("User Preferences"),
                "/dashboard/settings/preferences",
                "users.preferences",
                "",
                $sort
            )
            ->addLinkIf(
                "Garden.Settings.Manage" && Gdn::config("Feature.SuggestedContent.Enabled"),
                t("Interests & Suggested Content"),
                "/settings/interests",
                "users.interests",
                "",
                $sort
            )
            ->addLinkIf(
                "Garden.Community.Manage",
                t("Avatars"),
                "/dashboard/settings/avatars",
                "users.avatars",
                "",
                $sort
            );
        if (\Gdn::config("Feature.AutomationRules.Enabled")) {
            $nav->addGroupIf("Garden.Settings.Manage", t("Automation"), "automation", "", [
                "after" => "appearance",
            ])
                ->addLinkIf(
                    "Garden.Settings.Manage",
                    t("Automation Rules"),
                    "/settings/automation-rules",
                    "automation.automation-rules"
                )
                ->addLinkIf(
                    "Garden.Settings.Manage",
                    t("Automation Rules History"),
                    "/settings/automation-rules/history",
                    "automation.automation-rules-history"
                );
        }
        $nav->addGroup(t("Emails"), "email", "", ["after" => "users"])
            ->addLinkIf(
                "Garden.Community.Manage",
                t("Email Settings"),
                "/dashboard/settings/email",
                "email.settings",
                "",
                $sort
            )
            ->addLinkIf(
                !Gdn::config("Garden.Email.Disabled") &&
                    $session->checkPermission(["Garden.Community.Manage", "Garden.Settings.Manage"]),
                t("Digest Settings"),
                "/dashboard/settings/digest",
                "email.digest",
                "",
                $sort
            )
            ->addGroup(t("Discussions"), "forum", "", ["after" => "email"])
            ->addLinkIf("Garden.Settings.Manage", t("Tagging"), "settings/tagging", "forum.tagging", $sort)
            ->addLinkIf(
                Gdn::config("Feature.AISuggestions.Enabled") &&
                    Gdn::config("Feature.aiFeatures.Enabled") &&
                    $session->checkPermission("Garden.Settings.Manage"),
                t("AI Suggested Answers"),
                "/settings/ai-suggestions",
                "forum.ai-suggestions",
                "",
                $sort
            )
            ->addGroup(t("Reputation"), "reputation", "", ["after" => "forum"])
            ->addGroup(t("Connections"), "connect", "", ["after" => "reputation"])
            ->addLinkIf(
                "Garden.Settings.Manage",
                t("Social Connect", "Social Media"),
                "/social/manage",
                "connect.social",
                "",
                $sort
            )
            ->addGroup(t("Addons"), "add-ons", "", ["after" => "connect"])
            ->addLinkIf(
                "Garden.Settings.Manage",
                t("Plugins"),
                "/dashboard/settings/plugins",
                "add-ons.plugins",
                "",
                $sort
            )
            ->addLinkIf(
                "Garden.Settings.Manage",
                t("Applications"),
                "/dashboard/settings/applications",
                "add-ons.applications",
                "",
                $sort
            )
            ->addLinkIf("Garden.Settings.Manage", t("Labs"), "/settings/labs", "add-ons.labs", "", $sort, [
                "badge" => "New",
            ])
            ->addLinkIf(
                Gdn::config("Garden.ExternalSearch.Enabled", false) &&
                    $session->checkPermission("Garden.Settings.Manage"),
                t("External Search"),
                "/dashboard/settings/external-search",
                "add-ons.externalSearch",
                "",
                $sort
            )
            ->addGroup(t("Technical"), "site-settings", "", ["after" => "reputation"])
            ->addLinkIf(
                "Garden.Settings.Manage",
                t("Language Settings"),
                "/settings/language",
                "site-settings.languages",
                "",
                $sort
            )
            ->addLinkIf(
                "Garden.Settings.Manage",
                t("Security"),
                "/dashboard/settings/security",
                "site-settings.security",
                "",
                $sort
            )
            ->addLinkIf(
                "Garden.Settings.Manage",
                t("Audit Log"),
                "/dashboard/settings/audit-logs",
                "site-settings.audit-log",
                "",
                $sort,
                ["badge" => "New"]
            )
            ->addLinkIf("Garden.Settings.Manage", t("Routes"), "/dashboard/routes", "site-settings.routes", "", $sort)
            ->addGroup("API Integrations", "api", "", ["after" => "site-settings"])
            ->addGroupIf("Garden.Settings.Manage", t("Forum Data"), "forum-data", "", ["after" => "site-settings"])
            ->addLinkIf(
                \Vanilla\FeatureFlagHelper::featureEnabled("Import") && $session->checkPermission("Garden.Import"),
                t("Import"),
                "/dashboard/import",
                "forum-data.import",
                "",
                $sort
            );
    }

    /**
     * Aggressively prompt users to upgrade PHP version.
     *
     * @param SettingsController $sender
     */
    public function settingsController_render_before($sender)
    {
        if (!inSection("Dashboard") || $sender->isRenderingMasterView()) {
            return;
        }
        // Set this in your config to dismiss our upgrade warnings. Not recommended.
        $warning = c("Vanilla.WarnedMeToUpgrade");
        if ($warning && version_compare(ENVIRONMENT_PHP_NEXT_VERSION, $warning) <= 0) {
            return;
        }

        $phpVersion = phpversion();
        if (version_compare($phpVersion, ENVIRONMENT_PHP_NEXT_VERSION) < 0) {
            $versionStr = htmlspecialchars($phpVersion);

            $upgradeMessage = [
                "Content" =>
                    "We recommend using at least PHP " .
                    ENVIRONMENT_PHP_NEXT_VERSION .
                    ". Support for PHP " .
                    $versionStr .
                    " may be dropped in upcoming releases.",
                "AssetTarget" => "Content",
                "CssClass" => "WarningMessage",
            ];
            $messageModule = new MessageModule($sender, $upgradeMessage);
            $sender->addModule($messageModule);
        }

        $mysqlVersion = gdn::sql()->version();
        if (version_compare($mysqlVersion, "5.6") < 0) {
            $upgradeMessage = [
                "Content" =>
                    "We recommend using at least <b>MySQL 5.7</b> or <b>MariaDB 10.2</b>. Version " .
                    htmlspecialchars($mysqlVersion) .
                    " will not support all upcoming Vanilla features.",
                "AssetTarget" => "Content",
                "CssClass" => "InfoMessage",
            ];
            $messageModule = new MessageModule($sender, $upgradeMessage);
            $sender->addModule($messageModule);
        }
    }

    /**
     * List all tags and allow searching
     *
     * @param SettingsController $sender
     * @param null $search
     * @param null $type
     * @param null $page
     * @throws Gdn_UserException
     */
    public function settingsController_tagging_create($sender, $search = null, $type = null, $page = null)
    {
        $sender->permission("Garden.Settings.Manage");

        $sender->title("Tagging");
        $sender->setHighlightRoute("settings/tagging");
        $sQL = Gdn::sql();

        /** @var Gdn_Form $form */
        $form = $sender->Form;

        if ($form->authenticatedPostBack()) {
            $formValue = (bool) $form->getFormValue("Tagging.Discussions.Enabled");
            saveToConfig("Tagging.Discussions.Enabled", $formValue);
        }

        [$offset, $limit] = offsetLimit($page, 100);
        $sender->setData("_Limit", $limit);

        if ($search) {
            $sQL->like("FullName", $search, "right");
        }

        $queryType = $type;

        if (strtolower($type) == "all" || $search || $type === null) {
            $queryType = false;
            $type = "";
        }

        // This type doesn't actually exist, but it will represent the blank types in the column.
        if (strtolower($type) == "tags") {
            $queryType = "";
        }

        if (!$search && $queryType !== false) {
            $sQL->where("Type", $queryType);
        }

        // Get all tag types
        $tagModel = TagModel::instance();
        $tagTypes = $tagModel->getTagTypes();
        $tagTypes = array_change_key_case($tagTypes, CASE_LOWER);

        // Store type for view
        $tagType = !empty($type) ? $type : "All";
        $sender->setData("_TagType", $tagType);

        // Store tag types
        $sender->setData("_TagTypes", $tagTypes);

        // Determine if new tags can be added for the current type.
        $canAddTags = !empty($tagTypes[$type]["addtag"]) && $tagTypes[$type]["addtag"] ? 1 : 0;
        $canAddTags &= checkPermission("Vanilla.Tagging.Add");
        $sender->setData("_CanAddTags", $canAddTags);

        $data = $sQL
            ->select("t.*")
            ->from("Tag t")
            ->orderBy("t.CountDiscussions", "desc")
            ->limit($limit, $offset)
            ->get()
            ->resultArray();

        $sender->setData("Tags", $data);

        if ($search) {
            $sQL->like("FullName", $search, "right");
        }

        // Make sure search uses its own search type, so results appear in their own tab.
        $sender->Form->Action = url("/settings/tagging/?type=" . $tagType);

        // Search results pagination will mess up a bit, so don't provide a type in the count.
        $recordCountWhere = ["Type" => $queryType];
        if ($queryType === false) {
            $recordCountWhere = [];
        }
        if ($search) {
            $recordCountWhere = [];
        }

        $sender->setData("RecordCount", $sQL->getCount("Tag", $recordCountWhere));
        $sender->render("tagging");
    }

    /**
     * Add the tags endpoint to the settingsController
     *
     * @param SettingsController $sender
     * @param string $action
     * @throws Gdn_UserException
     */
    public function settingsController_tags_create($sender, $action)
    {
        $sender->permission("Garden.Settings.Manage");

        switch ($action) {
            case "delete":
                $tagID = val(1, $sender->RequestArgs);
                $tagModel = new TagModel();
                $tag = $tagModel->getID($tagID, DATASET_TYPE_ARRAY);
                $allowedTypes = Gdn::config("Tagging.Discussions.AllowedTypes", [""]);
                if (!in_array($tag["Type"] ?? "", $allowedTypes)) {
                    $sender->informMessage(formatString(t("You cannot delete a reserved tag.")));
                    $sender->render("blank", "utility", "dashboard");
                    break;
                }
                if ($sender->Form->authenticatedPostBack()) {
                    // Delete tag & tag relations.
                    $sQL = Gdn::sql();
                    $sQL->delete("TagDiscussion", ["TagID" => $tagID]);
                    $sQL->delete("Tag", ["TagID" => $tagID]);
                    $tag["Name"] = htmlspecialchars($tag["Name"]);
                    $tag["FullName"] = htmlspecialchars($tag["FullName"]);
                    $sender->informMessage(formatString(t("<b>{Name}</b> deleted."), $tag));
                    $sender->jsonTarget("#Tag_{$tag["TagID"]}", null, "Remove");
                }

                $sender->render("blank", "utility", "dashboard");
                break;
            case "edit":
                $sender->setHighlightRoute("settings/tagging");
                $sender->title(t("Edit Tag"));
                $tagID = val(1, $sender->RequestArgs);

                // Set the model on the form.
                $tagModel = new TagModel();
                $sender->Form->setModel($tagModel);
                $tag = $tagModel->getID($tagID);
                $sender->Form->setData($tag);

                // Make sure the form knows which item we are editing.
                $sender->Form->addHidden("TagID", $tagID);

                if ($sender->Form->authenticatedPostBack()) {
                    // Make sure the tag is valid
                    $tagData = $sender->Form->getFormValue("Name");
                    if (!TagModel::validateTag($tagData)) {
                        $sender->Form->addError("@" . t("ValidateTag", "Tags cannot contain commas or underscores."));
                    }

                    // Make sure that the tag name is not already in use.
                    if ($tagModel->getWhere(["TagID <>" => $tagID, "Name" => $tagData])->numRows() > 0) {
                        $sender->setData("MergeTagVisible", true);
                        if (!$sender->Form->getFormValue("MergeTag")) {
                            $sender->Form->addError("The specified tag name is already in use.");
                        }
                    }

                    if (trim(str_replace(" ", "-", $tagData)) !== TagModel::tagSlug($tagData)) {
                        $sender->Form->addError(
                            "@" . t("ValidateTag", "The Url Slug may only contain alphanumeric characters and hyphens.")
                        );
                    }

                    if ($sender->Form->save()) {
                        $sender->informMessage(t("Your changes have been saved."));
                        $sender->setRedirectTo("/settings/tagging");
                    }
                }

                $sender->render("tags");
                break;
            case "add":
            default:
                $sender->permission("Vanilla.Tagging.Add");
                $sender->setHighlightRoute("settings/tagging");
                $sender->title("Add Tag");

                // Set the model on the form.
                $tagModel = new TagModel();
                $sender->Form->setModel($tagModel);

                // Add types if allowed to add tags for it, and not '' or 'tags', which
                // are the same.
                $tagType = Gdn::request()->get("type");
                if (strtolower($tagType) != "tags" && $tagModel->canAddTagForType($tagType)) {
                    $tagType = strtolower($tagType) === "all" ? "" : $tagType;
                    $sender->Form->addHidden("Type", $tagType, true);
                }

                if ($sender->Form->authenticatedPostBack()) {
                    // Make sure the tag is valid
                    $tagName = $sender->Form->getFormValue("Name");
                    if (!TagModel::validateTag($tagName)) {
                        $sender->Form->addError("@" . t("ValidateTag", "Tags cannot contain commas or underscores."));
                    }

                    $tagType = $tagType ?? ($sender->Form->getFormValue("Type") ?? "");
                    if (!$tagModel->canAddTagForType($tagType)) {
                        $sender->Form->addError(
                            "@" . t("ValidateTagType", "That type does not accept manually adding new tags.")
                        );
                    }

                    // Make sure that the tag name is not already in use.
                    if ($tagModel->getWhere(["Name" => $tagName])->numRows() > 0) {
                        $sender->Form->addError("The specified tag name is already in use.");
                    }

                    if (trim(str_replace(" ", "-", $tagName)) !== TagModel::tagSlug($tagName)) {
                        $sender->Form->addError(
                            "@" . t("ValidateTag", "The Url Slug may only contain alphanumeric characters and hyphens.")
                        );
                    }

                    $saved = $sender->Form->save();
                    if ($saved) {
                        $sender->informMessage(t("Your changes have been saved."));
                        $sender->setRedirectTo("/settings/tagging");
                    }
                }

                $sender->render("tags");
                break;
        }
    }

    /**
     * Add the tag endpoint to the discussionController
     *
     * @param DiscussionController $sender
     * @param int $discussionID
     * @throws Exception
     *
     */
    public function discussionController_tag_create($sender, $discussionID, $origin)
    {
        if (!c("Tagging.Discussions.Enabled")) {
            throw new Exception("Not found", 404);
        }

        if (!filter_var($discussionID, FILTER_VALIDATE_INT)) {
            throw notFoundException("Discussion");
        }

        $discussion = DiscussionModel::instance()->getID($discussionID, DATASET_TYPE_ARRAY);
        if (!$discussion) {
            throw notFoundException("Discussion");
        }

        $hasPermission = Gdn::session()->checkPermission("Vanilla.Tagging.Add");
        if (!$hasPermission && $discussion["InsertUserID"] !== GDN::session()->UserID) {
            throw permissionException("Vanilla.Tagging.Add");
        }
        $sender->title("Add Tags");

        if ($sender->Form->authenticatedPostBack()) {
            $rawFormTags = $sender->Form->getFormValue("Tags");
            $formTags = TagModel::splitTags($rawFormTags);

            if (!$formTags) {
                $sender->Form->addError("@" . t("No tags provided."));
            } else {
                // If we're associating with categories
                $categoryID = -1;
                if (c("Vanilla.Tagging.CategorySearch", false)) {
                    $categoryID = val("CategoryID", $discussion, -1);
                }

                // Save the tags to the db.
                TagModel::instance()->saveDiscussion($discussionID, $formTags, ["", "Tag"], $categoryID);

                $sender->informMessage(t("The tags have been added to the discussion."));
            }
        }

        $sender->render("tag", "discussion", "vanilla");
    }

    /**
     * Set P3P header because IE won't allow cookies thru the iFrame without it.
     *
     * This must be done in the Dispatcher because of PrivateCommunity.
     * That precludes using Controller->SetHeader.
     * This is done so comment & forum embedding can work in old IE.
     *
     * @param Gdn_Dispatcher $sender
     * @throws ResponseException
     */
    public function gdn_dispatcher_appStartup_handler($sender)
    {
        safeHeader('P3P: CP="CAO PSA OUR"', true);

        if ($sso = Gdn::request()->get("sso")) {
            saveToConfig("Garden.Registration.SendConnectEmail", false, false);

            $deliveryMethod = $sender->getDeliveryMethod(Gdn::request());
            $isApi = $deliveryMethod === DELIVERY_METHOD_JSON;

            $currentUserID = Gdn::session()->UserID;
            $userID = Gdn::userModel()->sso($sso);

            if (!$userID) {
                Gdn::userModel()->Validation->reset();
            }

            if ($userID !== $currentUserID) {
                Gdn::session()->start($userID, !$isApi, !$isApi);
            }
            if ($isApi) {
                Gdn::session()->validateTransientKey(true);
            }

            if ($userID != $currentUserID) {
                Gdn::userModel()->fireEvent("AfterSignIn");
            }

            // Make sure we don't leak the sso query param through referrer headers.
            safeHeader("Referrer-Policy: strict-origin");
        }
    }

    /**
     * Check if we have a valid token associated with the request.
     * The checkAccessToken was previously done in gdn_dispatcher_appStartup_handler hook.
     * It was changed to have the access token auth happen as close as possible to standard auth.
     * It's necessary to do it via events until Vanilla overhauls its authentication workflow.
     */
    public function gdn_auth_startAuthenticator_handler()
    {
        $this->checkAccessToken();
    }

    /**
     * Check to see if a user is banned.
     *
     * @throws Exception if the user is banned.
     */
    public function base_afterSignIn_handler()
    {
        if (!Gdn::session()->isValid()) {
            if (
                $ban = Gdn::session()
                    ->getPermissions()
                    ->getBan()
            ) {
                throw new ClientException($ban["msg"], 401, $ban);
            } else {
                if (
                    !Gdn::session()
                        ->getPermissions()
                        ->has("Garden.SignIn.Allow")
                ) {
                    throw new PermissionException("Garden.SignIn.Allow");
                } else {
                    throw new ClientException("The session could not be started", 401);
                }
            }
        }
    }

    /**
     * Check the access token.
     */
    private function checkAccessToken()
    {
        $pattern = "/^\/?(?:[^\/]+\/)?api/";
        if (!preg_match($pattern, Gdn::request()->getPath())) {
            return;
        }

        $m = [];
        $authHeader = \Gdn::request()->getHeader("Authorization");
        $hasAuthHeader = !empty($authHeader) && preg_match("`^Bearer\s+(.*)`i", $authHeader, $m);
        $hasTokenParam = !empty($_GET["access_token"]);
        if (!$hasAuthHeader && !$hasTokenParam) {
            return;
        }

        $token = empty($_GET["access_token"]) ? $m[1] ?? "" : $_GET["access_token"];
        $token = trim($token);

        if (str_starts_with(haystack: $token, needle: "vnla_sys.")) {
            $systemTokenUtils = \Gdn::getContainer()->get(SystemTokenUtils::class);
            $service = $systemTokenUtils->authenticateDynamicSystemTokenService($token);

            $systemUserID = \Gdn::userModel()->getSystemUserID();

            $this->logger->info("Service \"{$service}\" made a request as system.", [
                \Vanilla\Logger::FIELD_CHANNEL => \Vanilla\Logger::CHANNEL_SYSTEM,
                "from_service" => $service,
                "tags" => ["accessToken"],
                "event" => "accessToken_auth",
            ]);

            \Gdn::session()->start($systemUserID, false, false);
            \Gdn::session()->validateTransientKey(true);
            // Someone tried to use a dynamic system token.
        } elseif ($token) {
            $model = new AccessTokenModel();

            try {
                $authRow = $model->verify($token, true);

                Gdn::session()->start($authRow["UserID"], false, false);
                Gdn::session()->validateTransientKey(true);

                $username = Gdn::session()->User->Name ?? "unknown";
                $tokenName = $authRow["Attributes"]["name"] ?? "id {$authRow["AccessTokenID"]}";
                $this->logger->info("User $username used access token $tokenName", [
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
                    "userID" => $authRow["UserID"],
                    "userName" => $username,
                    "tokenID" => $authRow["AccessTokenID"],
                    "tokenName" => $tokenName,
                    "tags" => ["accessToken"],
                    "event" => "accessToken_auth",
                ]);
            } catch (\Exception $ex) {
                // Add a psuedo-WWW-Authenticate header. We want the response to know, but don't want to kill everything.
                $msg = $ex->getMessage();
                safeHeader("X-WWW-Authenticate: error=\"invalid_token\", error_description=\"$msg\"");
            }
        }
    }

    /**
     * @param Gdn_Dispatcher $sender
     */
    public function gdn_dispatcher_sendHeaders_handler($sender)
    {
        $csrfToken = Gdn::request()->post(
            Gdn_Session::CSRF_NAME,
            Gdn::request()->get(
                Gdn_Session::CSRF_NAME,
                Gdn::request()->getValueFrom(Gdn_Request::INPUT_SERVER, "HTTP_X_CSRF_TOKEN")
            )
        );

        if ($csrfToken && Gdn::session()->isValid() && !Gdn::session()->validateTransientKey($csrfToken)) {
            safeHeader("X-CSRF-Token: " . Gdn::session()->transientKey());
        }
    }

    /**
     * Method for plugins that want a friendly /sso method to hook into.
     *
     * @param RootController $sender
     * @param string $target The url to redirect to after sso.
     * @throws ResponseException
     */
    public function rootController_sso_create($sender, $target = "")
    {
        if (!$target) {
            $target = $sender->Request->get("redirect");
            if (!$target) {
                $target = "/";
            }
        }

        // Get the default authentication provider.
        $defaultProvider = Gdn_AuthenticationProviderModel::getDefault();
        $sender->EventArguments["Target"] = $target;
        $sender->EventArguments["DefaultProvider"] = $defaultProvider;
        $handled = false;
        $sender->EventArguments["Handled"] = &$handled;

        $sender->fireEvent("SSO");

        // If an event handler didn't handle the signin then just redirect to the target.
        if (!$handled) {
            redirectTo($target);
        }
    }

    /**
     * Clear user navigation preferences if we can't find the explicit method on the controller.
     *
     * @param Gdn_Controller $sender
     * @param array $args Event arguments. We can expect a 'PathArgs' key here.
     */
    public function gdn_dispatcher_methodNotFound_handler($sender, $args)
    {
        // If PathArgs is empty, the user hit the root, and we assume they want the index.
        // If not, they got redirected to the root because their controller method was not
        // found. We should clear the user prefs in that case.
        if (!empty($args["PathArgs"])) {
            if (Gdn::session()->isValid()) {
                $uri = Gdn::request()->getRequestArguments("server")["REQUEST_URI"] ?? "";
                try {
                    $userModel = new UserModel();
                    $userModel->clearSectionNavigationPreference($uri);
                } catch (Exception $ex) {
                    // Nothing
                }
            }
        }
    }

    /**
     *
     *
     * @param SiteNavModule $sender
     */
    public function siteNavModule_init_handler($sender)
    {
        // GLOBALS

        // Add a link to the community home.
        $sender->addLinkToGlobals(t("Community Home"), "/", "main.home", "", -100, ["icon" => "home"], false);
        $sender->addGroupToGlobals("", "etc", "", 100);
        $sender->addLinkToGlobalsIf(
            Gdn::session()->isValid() && isMobile(),
            t("Full Site"),
            "/profile/nomobile",
            "etc.nomobile",
            "js-hijack",
            100,
            ["icon" => "resize-full"]
        );
        $sender->addLinkToGlobalsIf(Gdn::session()->isValid(), t("Sign Out"), signOutUrl(), "etc.signout", "", 100, [
            "icon" => "signout",
        ]);
        $sender->addLinkToGlobalsIf(!Gdn::session()->isValid(), t("Sign In"), signinUrl(), "etc.signin", "", 100, [
            "icon" => "signin",
        ]);

        // DEFAULTS

        if (!Gdn::session()->isValid()) {
            return;
        }

        $sender
            ->addLinkIf(Gdn::session()->isValid(), t("Profile"), "/profile", "main.profile", "profile", 10, [
                "icon" => "user",
            ])
            ->addLinkIf("Garden.Activity.View", t("Activity"), "/activity", "main.activity", "activity", 10, [
                "icon" => "time",
            ]);

        // Add the moderation items.
        $sender->addGroup(t("Moderation"), "moderation", "moderation", 90);
        if (Gdn::session()->checkPermission("Garden.Users.Approve")) {
            $roleModel = new RoleModel();
            $applicant_count = (int) $roleModel->getApplicantCount();
            if ($applicant_count > 0 || true) {
                $sender->addLink(
                    t("Applicants"),
                    "/user/applicants",
                    "moderation.applicants",
                    "applicants",
                    [],
                    ["icon" => "user", "badge" => $applicant_count]
                );
            }
        }
        $sender
            ->addLinkIf(
                "Garden.Moderation.Manage",
                t("Spam Queue"),
                "/log/spam",
                "moderation.spam",
                "spam",
                [],
                ["icon" => "spam"]
            )
            ->addLinkIf(
                "Garden.Settings.Manage",
                t("Dashboard"),
                "/settings",
                "etc.dashboard",
                "dashboard",
                [],
                ["icon" => "dashboard"]
            );

        $user = Gdn::controller()->data("Profile");
        $user_id = val("UserID", $user);

        //EDIT PROFILE SECTION

        // Users can edit their own profiles and moderators can edit any profile.
        $sender
            ->addLinkToSectionIf(
                hasEditProfile($user_id),
                "EditProfile",
                t("Profile"),
                userUrl($user, "", "edit"),
                "main.editprofile",
                "",
                [],
                ["icon" => "edit"]
            )
            ->addLinkToSectionIf(
                "Garden.Users.Edit",
                "EditProfile",
                t("Edit Account"),
                "/user/edit/" . $user_id,
                "main.editaccount",
                "Popup",
                [],
                ["icon" => "cog"]
            )
            ->addLinkToSection("EditProfile", t("Back to Profile"), userUrl($user), "main.profile", "", 100, [
                "icon" => "arrow-left",
            ]);

        //PROFILE SECTION

        $sender
            ->addLinkToSectionIf(
                c("Garden.Profile.ShowActivities", true),
                "Profile",
                t("Activity"),
                userUrl($user, "", "activity"),
                "main.activity",
                "",
                [],
                ["icon" => "time"]
            )
            ->addLinkToSectionIf(
                Gdn::controller()->data("Profile.UserID") == Gdn::session()->UserID,
                "Profile",
                t("Notifications"),
                userUrl($user, "", "notifications"),
                "main.notifications",
                "",
                [],
                ["icon" => "globe", "badge" => Gdn::controller()->data("Profile.CountNotifications")]
            )
            // Show the invitations if we're using the invite registration method.
            ->addLinkToSectionIf(
                strcasecmp(c("Garden.Registration.Method"), "invitation") === 0,
                "Profile",
                t("Invitations"),
                userUrl($user, "", "invitations"),
                "main.invitations",
                "",
                [],
                ["icon" => "ticket"]
            )
            // Users can edit their own profiles and moderators can edit any profile.
            ->addLinkToSectionIf(
                hasEditProfile($user_id),
                "Profile",
                t("Edit Profile"),
                userUrl($user, "", "edit"),
                "Profile",
                "main.editprofile",
                "",
                [],
                ["icon" => "edit"]
            );
    }

    /**
     * After executing /settings/utility/update check if any role permissions have been changed, if not reset all the permissions on the roles.
     *
     * @param $sender
     */
    public function updateModel_afterStructure_handler($sender)
    {
        // Only setup default permissions if no role permissions are set.
        $hasPermissions = Gdn::sql()
            ->getWhere("Permission", ["RoleID >" => 0], "", "", 1)
            ->firstRow(DATASET_TYPE_ARRAY);
        if (!$hasPermissions) {
            PermissionModel::resetAllRoles();
        }
    }

    /**
     * Copy a file locally so that it can be manipulated by php.
     *
     * @param Gdn_Upload $sender The upload object doing the manipulation.
     * @param array $args Arguments useful for copying the file.
     * @throws Exception Throws an exception if there was a problem copying the file for local use.
     */
    public function gdn_upload_copyLocal_handler($sender, $args)
    {
        $parsed = $args["Parsed"];
        if ($parsed["Type"] !== "static" || $parsed["Domain"] !== "v") {
            return;
        }
        // Sanitize $parsed['Name'] to prevent path traversal.
        $parsed["Name"] = str_replace("..", "", $parsed["Name"]);
        $remotePath = PATH_ROOT . "/" . $parsed["Name"];

        // Make sure we are copying a file from uploads.
        if (strpos($remotePath, PATH_UPLOADS) !== 0 || strpos($remotePath, PATH_UPLOADS . "/import/" === 0)) {
            throw new \Exception("Can only copy from the uploads folder.", 403);
        }

        // Since this is just a temp file we don't want to nest it in a bunch of subfolders.
        $localPath = paths(PATH_UPLOADS, "tmp-static", str_replace("/", "-", $parsed["Name"]));

        // Make sure the destination path exists
        if (!file_exists(dirname($localPath))) {
            mkdir(dirname($localPath), 0777, true);
        }

        // Copy
        copy($remotePath, $localPath);

        $args["Path"] = $localPath;
    }

    /**
     * Keep text fields their lengths when altering tables.
     *
     * @param \Gdn_DatabaseStructure $structure
     */
    public function gdn_mySQLStructure_beforeSet(\Gdn_DatabaseStructure $structure): void
    {
        if (!\Vanilla\FeatureFlagHelper::featureEnabled(\Vanilla\Utility\SqlUtils::FEATURE_ALTER_TEXT_FIELD_LENGTHS)) {
            \Vanilla\Utility\SqlUtils::keepTextFieldLengths($structure);
        }
    }

    /**
     * Check the actual value of $spoofEnabled. If there is a config for the previous Spoof plugin, use it, otherwise default to true.
     *
     * @return bool
     */
    private function checkSpoofEnabled(): bool
    {
        $this->spoofEnabled = $this->config->get("EnabledPlugins.Spoof", true);
        return $this->spoofEnabled;
    }

    /**
     * Validates the current user's permissions & transientkey and then spoofs
     * the userid passed as the first arg and redirects to profile.
     *
     * @param UserController $sender
     * @throws ResponseException
     */
    public function userController_autoSpoof_create(UserController $sender): void
    {
        if (!$this->checkSpoofEnabled()) {
            return;
        }
        $spoofUserID = getValue("0", $sender->RequestArgs);
        $user = $sender->userModel->getId(intval($spoofUserID));
        $transientKey = getValue("1", $sender->RequestArgs);

        // Validate the transient key && permissions
        if (
            Gdn::session()->validateTransientKey($transientKey) &&
            Gdn::session()->checkPermission("Garden.Settings.Manage") &&
            $user->Admin < 2
        ) {
            $spoofedByUser = Gdn::session()->User;
            $session = Gdn::session()->Session;
            $attributes = $session["Attributes"] ?? [];
            $context = array_merge(
                ["spoofedByUserID" => $spoofedByUser->UserID, "spoofedByUserName" => $spoofedByUser->Name],
                $attributes
            );

            $spoofEvent = new UserSpoofEvent($user->UserID, $user->Name, $context);
            AuditLogger::log($spoofEvent);

            Gdn::session()->start($spoofUserID, attributes: $spoofEvent->getAuditContext());
        }
        if (!isset($this->_DeliveryType) || $this->_DeliveryType !== DELIVERY_TYPE_ALL) {
            $sender->setRedirectTo("profile");
            $sender->render("blank", "utility", "dashboard");
        } else {
            redirectTo("profile");
        }
    }

    /**
     * Adds a "Spoof" link to the user management list.
     *
     * @param UserController $sender
     */
    public function userController_userListOptions_handler(UserController $sender): void
    {
        if (!$this->checkSpoofEnabled()) {
            return;
        }
        if (!Gdn::session()->checkPermission("Garden.Settings.Manage")) {
            return;
        }

        $user = getValue("User", $sender->EventArguments);
        if ($user && $user->Admin < 2) {
            $attr = [
                "aria-label" => t("Spoof"),
                "title" => t("Spoof"),
                "data-follow-link" => "true",
            ];
            $class = "js-modal-confirm btn btn-icon";
            echo anchor(
                dashboardSymbol("spoof"),
                "/user/autospoof/" . $user->UserID . "/" . Gdn::session()->transientKey(),
                $class,
                $attr
            );
        }
    }

    /**
     * Adds a "Spoof" link to the site management list.
     * NOTE: There doesn't seem to be a `manageController_siteListOptions` event.
     *
     * @param ManageController $sender
     */
    public function manageController_siteListOptions_handler(ManageController $sender): void
    {
        if (!$this->checkSpoofEnabled()) {
            return;
        }
        if (!Gdn::session()->checkPermission("Garden.Settings.Manage")) {
            return;
        }

        $site = getValue("Site", $sender->EventArguments);
        if ($site) {
            echo anchor(
                t("Spoof"),
                "/user/autospoof/" . $site->InsertUserID . "/" . Gdn::session()->transientKey(),
                "PopConfirm SmallButton"
            );
        }
    }

    /**
     * Add items to the profile dashboard.
     * NOTE: The event `afterAddSideMenu` only seems to be fired when viewing one's own profile and this handler
     * has a specific check that the profile must be for a different user. So it doesn't seem to do anything.
     *
     * @param ProfileController $sender
     */
    public function profileController_afterAddSideMenu_handler(ProfileController $sender): void
    {
        if (!$this->checkSpoofEnabled()) {
            return;
        }
        if (!Gdn::session()->checkPermission("Garden.Settings.Manage")) {
            return;
        }

        $sideMenu = $sender->EventArguments["SideMenu"];
        $viewingUserID = Gdn::session()->UserID;

        if ($sender->User->UserID != $viewingUserID) {
            $sideMenu->addLink(
                "Options",
                t("Spoof User"),
                "/user/autospoof/" . $sender->User->UserID . "/" . Gdn::session()->transientKey(),
                "",
                ["class" => "PopConfirm"]
            );
        }
    }

    /**
     * Format Spoof information.
     *
     * @param LogModel $sender
     * @param array $args
     */
    public function logModel_formatContent_handler(LogModel $sender, array $args): void
    {
        $log = $args["Log"];
        $data = $log["Data"];
        if ($log["Operation"] == "Spoof") {
            $args["Result"] =
                "Spoofed in User ID <b>" .
                $sender->formatKey("SpoofUserName", $log) .
                "</b>(" .
                $sender->formatKey("SpoofUserID", $log) .
                ") as <b>" .
                $sender->formatKey("userSpoofedName", $data) .
                "</b>(" .
                $sender->formatKey("userSpoofedId", $data) .
                ")";
        }
    }
}
