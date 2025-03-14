<?php
/**
 * Managing core Dashboard settings.
 *
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */
use Vanilla\Addon;
use Vanilla\FeatureFlagHelper;
use Vanilla\Models\AddonModel;
use Vanilla\Theme\ThemeServiceHelper;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Web\HttpStrictTransportSecurityModel as HstsModel;
use Vanilla\Theme\FsThemeProvider;
use Vanilla\Widgets\WidgetService;
use Vanilla\Dashboard\Controllers\Api\ChurnExportApiController;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Garden\Web\Exception\ClientException;

/**
 * Handles /settings endpoint.
 */
class SettingsController extends DashboardController
{
    const DEFAULT_AVATAR_FOLDER = "defaultavatar";
    const CONFIG_CSP_DOMAINS = "ContentSecurityPolicy.ScriptSrc.AllowedDomains";
    const CONFIG_CSP_STRICT_DYNAMIC = "ContentSecurityPolicy.StrictDynamic";
    const CONFIG_TRUSTED_DOMAINS = "Garden.TrustedDomains";
    const DEFAULT_PASSWORD_LENGTH = 8;

    /** @var array Models to automatically instantiate. */
    public $Uses = ["Form", "Database"];

    /** @var string */
    public $ModuleSortContainer = "Dashboard";

    /** @var Gdn_Form */
    public $Form;

    /** @var array List of permissions that should all have access to main dashboard. */
    public $RequiredAdminPermissions = [];

    /** @var BanModel The ban model. */
    private $_BanModel;

    /**
     * @var \Vanilla\Models\AddonModel
     */
    private $addonModel;

    /** @var WidgetService */
    private $widgetService;

    /** @var LocaleModel */
    private LocaleModel $localeModel;

    /**
     * SettingsController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->addonModel = \Gdn::getContainer()->get(AddonModel::class);
        $this->widgetService = \Gdn::getContainer()->get(WidgetService::class);
        $this->localeModel = Gdn::getContainer()->get(LocaleModel::class);
    }

    /**
     * Render the labs page.
     */
    public function labs()
    {
        $this->permission("Garden.Settings.Manage");
        $this->render("labs");
    }

    /**
     * Settings page for external search.
     */
    public function externalSearch()
    {
        $this->permission(["Garden.Settings.Manage"], false);
        if (Gdn::config("Garden.ExternalSearch.Enabled", false)) {
            $this->setHighlightRoute("dashboard/settings/external-search");
            $this->title(t("External Search"));
            $this->render("external-search");
        } else {
            throw notFoundException("Page");
        }
    }

    /**
     * Render the language settings page.
     */
    public function language()
    {
        $this->permission("Garden.Settings.Manage");
        $this->render("language");
    }

    /**
     * Render the post type settings page.
     */
    public function postTypes()
    {
        $this->permission("Garden.Settings.Manage");
        $this->setHighlightRoute("/settings/post-types");
        $this->title(t("Post Types and Post Fields"));
        if (FeatureFlagHelper::featureEnabled("customLayout.createPost")) {
            $this->render("post-types");
        } else {
            $this->renderException(notFoundException());
        }
    }

    /**
     * Render the reports page.
     */
    public function reports()
    {
        $this->permission("Garden.Settings.Manage");
        $this->render("reports");
    }

    /**
     * Highlight menu path. Automatically run on every use.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize()
    {
        parent::initialize();
        if ($this->Menu) {
            $this->Menu->highlightRoute("/dashboard/settings");
        }
        Gdn_Theme::section("Settings");

        // Many dashboard pages display a pretty style flash when deferring all scripts.
        // Disable deferred scripts on these pages until this is resolved.
        // This is also less signficant here because these pages are not indexed by search engines.
        $this->useDeferredLegacyScripts = false;
    }

    /**
     * Application management screen.
     *
     * @since 2.0.0
     * @access public
     * @param string $filter 'enabled', 'disabled', or 'all' (default)
     * @param string $applicationName Unique ID of app to be modified.
     * @param string $TransientKey Security token.
     */
    public function applications($filter = "", $applicationName = "")
    {
        $this->permission("Garden.Settings.Manage");

        // Page setup
        $this->addJsFile("addons.js");
        $this->addJsFile("applications.js");
        $this->title(t("Applications"));
        $this->setHighlightRoute("dashboard/settings/applications");

        // Verify addon cache integrity?
        if ($this->verifyAddonCache()) {
            $this->addJsFile("addoncache.js");
            $this->addDefinition("VerifyCache", "addon");
        }

        if (!in_array($filter, ["enabled", "disabled"])) {
            $filter = "all";
        }
        $this->Filter = $filter;

        $applicationManager = Gdn::applicationManager();
        $this->AvailableApplications = $applicationManager->availableVisibleApplications();
        $this->EnabledApplications = $applicationManager->enabledVisibleApplications();

        if ($applicationName != "") {
            $addon = Gdn::addonManager()->lookupAddon($applicationName);
            if (!$addon) {
                throw notFoundException("Application");
            }
            if (Gdn::addonManager()->isEnabled($applicationName, Addon::TYPE_ADDON)) {
                $this->disableApplication($applicationName, $filter);
            } else {
                $this->enableApplication($applicationName, $filter);
            }
        } else {
            $this->render();
        }
    }

    public function disableApplication($addonName, $filter)
    {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }

        $this->permission("Garden.Settings.Manage");
        $applicationManager = Gdn::applicationManager();

        $action = "none";
        if ($filter == "enabled") {
            $action = "SlideUp";
        }

        $addon = Gdn::addonManager()->lookupAddon($addonName);
        try {
            $applicationManager->disableApplication($addonName);
            $this->informMessage(sprintf(t("%s Disabled."), val("name", $addon->getInfo(), t("Application"))));
        } catch (Exception $e) {
            $this->Form->addError(strip_tags($e->getMessage()));
        }

        $this->handleAddonToggle($addonName, $addon->getInfo(), "applications", false, $filter, $action);
        $this->render("blank", "utility", "dashboard");
    }

    public function enableApplication($addonName, $filter)
    {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }

        $this->permission("Garden.Settings.Manage");
        $applicationManager = Gdn::applicationManager();

        $action = "none";
        if ($filter == "disabled") {
            $action = "SlideUp";
        }

        $addon = Gdn::addonManager()->lookupAddon($addonName);

        try {
            $applicationManager->checkRequirements($addonName);
            $this->informMessage(sprintf(t("%s Enabled."), val("name", $addon->getInfo(), t("Application"))));
        } catch (Exception $e) {
            $this->Form->addError(strip_tags($e->getMessage()));
        }
        if ($this->Form->errorCount() == 0) {
            $validation = new Gdn_Validation();
            $applicationManager->registerPermissions($addonName, $validation);
            $applicationManager->enableApplication($addonName, $validation);
            $this->Form->setValidationResults($validation->results());
        }

        $this->handleAddonToggle($addonName, $addon->getInfo(), "applications", true, $filter, $action);
        $this->render("blank", "utility", "dashboard");
    }

    private function handleAddonToggle($addonName, $addonInfo, $type, $isEnabled, $filter = "", $action = "")
    {
        require_once $this->fetchViewLocation("helper_functions");

        if ($this->Form->errorCount() > 0) {
            $this->informMessage($this->Form->errors());
        } else {
            if ($action === "SlideUp") {
                $this->jsonTarget("#" . Gdn_Format::url($addonName) . "-addon", "", "SlideUp");
            } else {
                ob_start();
                writeAddonMedia($addonName, $addonInfo, $isEnabled, $type, $filter);
                $row = ob_get_clean();
                $this->jsonTarget("#" . Gdn_Format::url($addonName) . "-addon", $row, "ReplaceWith");
            }
        }
    }

    /**
     * Gets the ban model and instantiates it if it doesn't exist.
     *
     * @return BanModel
     */
    public function getBanModel()
    {
        if ($this->_BanModel === null) {
            $banModel = new BanModel();
            $this->_BanModel = $banModel;
        }
        return $this->_BanModel;
    }

    /**
     * Application management screen.
     *
     * @since 2.0.0
     * @access protected
     * @param array $ban Data about the ban.
     *    Valid keys are BanType and BanValue. BanValue is what is to be banned.
     *    Valid values for BanType are email, ipaddress or name.
     */
    private static function legacyUserPageBanFilter($ban)
    {
        $banModel = \Gdn::getContainer()->get(BanModel::class);
        $banWhere = $banModel->banWhere($ban, "u.");
        foreach ($banWhere as $name => $value) {
            if (!in_array($name, ["u.Admin", "u.Deleted"])) {
                return ["Filter" => "$name $value"];
            }
        }
        return [];
    }

    /**
     * @param array $banRow
     * @return string
     */
    public static function banRuleUsersUrl(array $banRow): string
    {
        if (FeatureFlagHelper::featureEnabled("NewUserManagement")) {
            $baseUrl = "/dashboard/user";

            $query = [
                "banned" => "true",
            ];
            $banType = strtolower($banRow["BanType"]);
            switch ($banType) {
                case "email":
                case "name":
                    $query["Keywords"] = $banRow["BanValue"];
                    break;
                case "ipaddress":
                    $query["ipAddresses"][] = $banRow["BanValue"];
                    break;
            }
        } else {
            $baseUrl = "/dashboard/user/banned";
            $query = self::legacyUserPageBanFilter($banRow);
        }

        return url($baseUrl . "?" . http_build_query($query), true);
    }

    /**
     * Settings page for managing avatar settings.
     *
     * Displays the current avatar and exposes the following config settings:
     * Garden.Thumbnail.Size
     * Garden.Profile.MaxWidth
     * Garden.Profile.MaxHeight
     */
    public function avatars()
    {
        $this->permission("Garden.Community.Manage");
        $this->setHighlightRoute("dashboard/settings/avatars");
        $this->addJsFile("avatars.js");
        $this->title(t("Avatars"));

        $validation = new Gdn_Validation();
        $validation->applyRule("Garden.Thumbnail.Size", "Integer", t("Thumbnail size must be an integer."));
        $validation->applyRule("Garden.Profile.MaxWidth", "Integer", t("Max avatar width must be an integer."));
        $validation->applyRule("Garden.Profile.MaxHeight", "Integer", t("Max avatar height must be an integer."));

        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(["Garden.Thumbnail.Size", "Garden.Profile.MaxWidth", "Garden.Profile.MaxHeight"]);
        $this->Form->setModel($configurationModel);
        $this->setData("avatar", UserModel::getDefaultAvatarUrl());

        $this->fireEvent("AvatarSettings");

        if (!$this->Form->authenticatedPostBack(true)) {
            $this->Form->setData($configurationModel->Data);
        } else {
            if ($this->Form->save() !== false) {
                $this->informMessage(t("Your settings have been saved."));
            }
        }
        $this->render();
    }

    /**
     * @return void
     */
    public function auditLogs()
    {
        $this->permission("site.manage");
        $this->setHighlightRoute("dashboard/settings/audit-logs");
        $this->title(t("Audit Logs"));
        $this->render();
    }

    /**
     * Reload the panel navigation. Updates the panel navigation (the content of the div with the
     * class '.js-panel-nav') in the page with the navigation for one or more sections in the
     * Dashboard Nav Module. For instance, I could replace the panel nav with the moderation section nav
     * by calling reloadPanelNavigation('Moderation').
     *
     * @param String|array $sections The section or sections to update the panel nav with.
     * @param String $activeUrl The highlight url for the panel nav.
     * @throws Exception
     */
    public function reloadPanelNavigation($sections = "", $activeUrl = "")
    {
        $dashboardNavModule = DashboardNavModule::getDashboardNav();

        // Coerce into an array
        if ($sections !== "" && gettype($sections) === "string") {
            $sections = [$sections];
        }

        if ($sections !== "") {
            $dashboardNavModule->setCurrentSections($sections);
        }

        if ($activeUrl !== "") {
            $dashboardNavModule->setHighlightRoute($activeUrl);
        }

        // Get our plugin nav items the new way.
        $dashboardNavModule->fireEvent("init");

        // Get our plugin nav items the old way.
        $navAdapter = new NestedCollectionAdapter($dashboardNavModule);
        $this->EventArguments["SideMenu"] = $navAdapter;
        $this->fireEvent("GetAppSettingsMenuItems");

        $this->jsonTarget(".js-panel-nav", $dashboardNavModule->toString());
    }

    /**
     * Handles the setting of the Garden.Profile.EditPhotos config and updates the edit photos toggle.
     *
     * @param $allow Expects either 'true' or 'false'.
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function allowEditPhotos($allow)
    {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }
        if (!Gdn::session()->checkPermission("Garden.Settings.Manage")) {
            throw new Exception('You don\'t have permisison to do that.', 401);
        }

        $allow = strtolower($allow);
        saveToConfig("Garden.Profile.EditPhotos", $allow === "true");
        if ($allow === "true") {
            $newToggle = wrap(
                anchor(
                    '<div class="toggle-well"></div><div class="toggle-slider"></div>',
                    "/dashboard/settings/alloweditphotos/false",
                    "Hijack"
                ),
                "span",
                ["class" => "toggle-wrap toggle-wrap-on"]
            );
            $this->informMessage(t("Editing photos allowed."));
        } else {
            $newToggle = wrap(
                anchor(
                    '<div class="toggle-well"></div><div class="toggle-slider"></div>',
                    "/dashboard/settings/alloweditphotos/true",
                    "Hijack"
                ),
                "span",
                ["class" => "toggle-wrap toggle-wrap-off"]
            );
            $this->informMessage(t("Editing photos not allowed."));
        }
        $this->jsonTarget("#editphotos-toggle", $newToggle);

        $this->render("Blank", "Utility");
    }

    /**
     * Test whether a path is a relative path to the proper uploads directory.
     *
     * @param string $avatar The path to the avatar image to test (most often Garden.DefaultAvatar)
     * @return bool Whether the avatar has been uploaded from the dashboard.
     */
    public function isUploadedDefaultAvatar($avatar)
    {
        return strpos($avatar, self::DEFAULT_AVATAR_FOLDER . "/") !== false;
    }

    /**
     * Settings page for uploading, deleting and cropping the default avatar.
     *
     * @throws Exception
     */
    public function defaultAvatar()
    {
        $this->permission("Garden.Community.Manage");
        $this->setHighlightRoute("dashboard/settings/avatars");
        $this->title(t("Default Avatar"));
        $this->addJsFile("avatars.js");

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $this->Form->setModel($configurationModel);

        // If the avatar is a svg file, don't show crop module.
        $avatar = c("Garden.DefaultAvatar");
        $ext = !empty($avatar) ? strtolower(pathinfo($avatar, PATHINFO_EXTENSION)) : "";
        $isSvg = Gdn_UploadSvg::isSvg("DefaultAvatar");
        $isImageUploaded = !empty($_FILES["DefaultAvatar"]);

        /*
         * Don't show crop module on the following conditions as we are not able to crop them:
         * 1) if the current avatar is an svg file
         * 2) if the current avatar is not a svg file but the uploaded file is a svg file
         * 3) if the current avatar is not a svg file but the uploaded file is a svg file
         */
        if (
            (($isImageUploaded && !$isSvg && $ext !== "svg") || (!$isImageUploaded && $ext !== "svg")) &&
            $avatar &&
            $this->isUploadedDefaultAvatar($avatar)
        ) {
            //Get the image source so we can manipulate it in the crop module.
            $upload = new Gdn_UploadImage();
            $thumbnailSize = c("Garden.Thumbnail.Size");
            $basename = changeBasename($avatar, "p%s");
            $source = $upload->copyLocal($basename);

            //Set up cropping.
            $crop = new CropImageModule($this, $this->Form, $thumbnailSize, $thumbnailSize, $source);
            $crop->saveButton = false;
            $crop->setExistingCropUrl(Gdn_UploadImage::url(changeBasename($avatar, "n%s")));
            $crop->setSourceImageUrl(Gdn_UploadImage::url(changeBasename($avatar, "p%s")));
            $this->setData("crop", $crop);
        } else {
            $userImage = $avatar
                ? (isUrl($avatar)
                    ? $avatar
                    : Gdn_UploadImage::url(changeBasename($avatar, "p%s")))
                : UserModel::getDefaultAvatarUrl();
            $this->setData("avatar", $userImage);
        }

        if (!$this->Form->authenticatedPostBack()) {
            $this->Form->setData($configurationModel->Data);
        } elseif ($this->Form->save() !== false) {
            $upload = $isSvg ? new Gdn_UploadSvg() : new Gdn_UploadImage();
            $newAvatar = false;
            $newUpload = false;
            if ($tmpAvatar = $upload->validateUpload("DefaultAvatar", false)) {
                // New upload
                $newUpload = true;
                $thumbOptions = ["Crop" => true, "SaveGif" => c("Garden.Thumbnail.SaveGif")];
                $newAvatar = $this->saveDefaultAvatars($tmpAvatar, $thumbOptions, $isSvg);
            } elseif ($avatar && $crop && $crop->isCropped()) {
                // New thumbnail
                $tmpAvatar = $source;
                $thumbOptions = [
                    "Crop" => true,
                    "SourceX" => $crop->getCropXValue(),
                    "SourceY" => $crop->getCropYValue(),
                    "SourceWidth" => $crop->getCropWidth(),
                    "SourceHeight" => $crop->getCropHeight(),
                ];
                $newAvatar = $this->saveDefaultAvatars($tmpAvatar, $thumbOptions);
            }
            if ($this->Form->errorCount() == 0) {
                if ($newAvatar) {
                    $this->deleteDefaultAvatars($avatar);
                    $avatar = c("Garden.DefaultAvatar");
                    if (!$isSvg) {
                        $thumbnailSize = c("Garden.Thumbnail.Size");

                        // Update crop properties.
                        $basename = changeBasename($avatar, "p%s");
                        $source = $upload->copyLocal($basename);
                        $crop = new CropImageModule($this, $this->Form, $thumbnailSize, $thumbnailSize, $source);
                        $crop->saveButton = false;
                        $crop->setSize($thumbnailSize, $thumbnailSize);
                        $crop->setExistingCropUrl(Gdn_UploadImage::url(changeBasename($avatar, "n%s")));
                        $crop->setSourceImageUrl(Gdn_UploadImage::url(changeBasename($avatar, "p%s")));
                        $this->setData("crop", $crop);
                    } else {
                        $this->setData(
                            "avatar",
                            isurl($avatar) ? $avatar : Gdn_UploadImage::url(changeBasename($avatar, "p%s"))
                        );
                    }
                    // New uploads stay on the page to allow cropping. Otherwise, redirect to avatar settings page.
                    if (!$newUpload) {
                        redirectTo("/dashboard/settings/avatars");
                    }
                }
                $this->informMessage(t("Your settings have been saved."));
            }
        }
        $this->render();
    }

    /**
     * Saves the default avatar to /uploads in three formats:
     *   The default image, which is not resized or cropped.
     *   p* : The profile-sized image, which is constrained by Garden.Profile.MaxWidth and Garden.Profile.MaxHeight.
     *   n* : The thumbnail-sized image, which is constrained and cropped according to Garden.Thumbnail.Size.
     *
     * @param string $source The path to the local copy of the image.
     * @param array $thumbOptions The options to save the thumbnail-sized avatar with.
     * @param bool $isSvg Whether the image is SVG.
     * @return bool Whether the saves were successful.
     */
    private function saveDefaultAvatars($source, $thumbOptions, $isSvg = false)
    {
        try {
            $upload = $isSvg ? new Gdn_UploadSvg() : new Gdn_UploadImage();
            $ext = $isSvg ? "svg" : "jpg";
            // Generate the target image name
            $targetImage = $upload->generateTargetName(PATH_UPLOADS, $ext);
            $imageBaseName = pathinfo($targetImage, PATHINFO_BASENAME);

            if ($isSvg) {
                $parts = $upload->saveAs($source, self::DEFAULT_AVATAR_FOLDER . "/" . $imageBaseName, [], true);
                $upload->saveAs($source, self::DEFAULT_AVATAR_FOLDER . "/p$imageBaseName", [], true);
                $upload->saveAs($source, self::DEFAULT_AVATAR_FOLDER . "/n$imageBaseName");
            } else {
                // Save the full size image.
                $parts = Gdn_UploadImage::saveImageAs($source, self::DEFAULT_AVATAR_FOLDER . "/" . $imageBaseName);

                // Save the profile size image.
                Gdn_UploadImage::saveImageAs(
                    $source,
                    self::DEFAULT_AVATAR_FOLDER . "/p$imageBaseName",
                    c("Garden.Profile.MaxHeight"),
                    c("Garden.Profile.MaxWidth"),
                    ["SaveGif" => c("Garden.Thumbnail.SaveGif")]
                );

                $thumbnailSize = c("Garden.Thumbnail.Size");
                // Save the thumbnail size image.
                Gdn_UploadImage::saveImageAs(
                    $source,
                    self::DEFAULT_AVATAR_FOLDER . "/n$imageBaseName",
                    $thumbnailSize,
                    $thumbnailSize,
                    $thumbOptions
                );
            }
        } catch (Exception $ex) {
            $this->Form->addError($ex);
            return false;
        }

        $imageBaseName = $parts["SaveName"];
        saveToConfig("Garden.DefaultAvatar", $imageBaseName);
        return true;
    }

    /**
     * Branding management screen.
     *
     * @since 2.0.0
     * @access public
     */
    public function branding()
    {
        $this->permission(["Garden.Community.Manage", "Garden.Settings.Manage"], false);
        redirectTo("appearance/branding", 302);
    }

    /**
     * Settings page for user profile redirection.
     */
    public function profile()
    {
        $this->permission(["Garden.Settings.Manage"], false);
        $this->setHighlightRoute("dashboard/settings/profile");
        $this->title(t("User Profile"));
        $this->render();
    }

    /**
     * User preferences page.
     */
    public function preferences()
    {
        $this->permission(["Garden.Settings.Manage"], false);
        $this->setHighlightRoute("dashboard/settings/preferences");
        $this->title(t("User Preferences"));
        $this->render();
    }

    /**
     * Manage user bans (add, edit, delete, list).
     *
     * @since 2.0.18
     * @access public
     * @param string $action Add, edit, delete, or none.
     * @param string $search Term to filter ban list by.
     * @param int|string $page Page number.
     * @param int|string $iD Ban ID we're editing or deleting.
     */
    public function bans($action = "", $search = "", $page = "", $iD = "")
    {
        Gdn_Theme::section("Moderation", "set");
        $this->permission("site.manage");

        // Page setup
        $this->title(t("Ban Rules"));

        [$offset, $limit] = offsetLimit($page, 20);

        $banModel = $this->getBanModel();

        switch (strtolower($action)) {
            case "add":
            case "edit":
                $this->Form->setModel($banModel);
                $this->setData("Title", sprintf(t(ucfirst($action) . " %s"), t("Ban Rule")));

                if ($this->Form->authenticatedPostBack()) {
                    if ($iD) {
                        $this->Form->setFormValue("BanID", $iD);
                    }

                    // Trim the ban value to avoid obvious mismatches.
                    $banValue = trim($this->Form->getFormValue("BanValue"));
                    $this->Form->setFormValue("BanValue", $banValue);

                    // We won't let you HAL 9000 the entire crew.
                    $crazyBans = ["*", "*@*", "*.*", "*.*.*", "*.*.*.*"];
                    if (in_array($banValue, $crazyBans)) {
                        $this->Form->addError("I'm sorry Dave, I'm afraid I can't do that.");
                    }

                    try {
                        // Save the ban.
                        $newID = $this->Form->save();
                        $this->jsonTarget("", "", "Refresh");
                    } catch (Exception $ex) {
                        $this->Form->addError($ex);
                    }
                } else {
                    if ($iD) {
                        $this->Form->setData($banModel->getID($iD));
                    }
                }

                $banTypes = [
                    "IPAddress" => t("IP Address"),
                    "Email" => t("Email"),
                    "Name" => t("Name"),
                ];

                $eventManager = Gdn::getContainer()->get(\Garden\EventManager::class);
                $banTypes = $eventManager->fireFilter("settingsController_listBanTypes", $banTypes);

                $this->setData("_BanTypes", $banTypes);

                $this->View = "Ban";
                break;
            case "delete":
                if ($this->Form->authenticatedPostBack()) {
                    $banModel->delete(["BanID" => $iD]);
                    $this->View = "BanDelete";
                    $this->jsonTarget("", "", "Refresh");
                }
                break;
            case "find":
                $this->findBanRule($search);
                break;
            default:
                $bans = $banModel->getWhere([], "BanType, BanValue", "asc", $limit, $offset)->resultArray();
                $this->setData("Bans", $bans);
                break;
        }
        $this->render();
    }

    /**
     * Layout management screen.
     *
     * @since 2.0.0
     * @access public
     */
    public function layout()
    {
        $this->permission("Garden.Settings.Manage");
        redirectTo("/appearance/layouts", 302);
    }

    /**
     *
     * @deprecated
     * @throws Exception
     */
    public function configuration()
    {
        deprecated("settingsController->configuration()");
    }

    /**
     * Security settings management screen.
     *
     * @since 2.4
     * @access public
     */
    public function security()
    {
        $this->permission("Garden.Settings.Manage");
        $this->setHighlightRoute("dashboard/settings/security");
        $this->title(t("Security"));

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            self::CONFIG_TRUSTED_DOMAINS,
            self::CONFIG_CSP_DOMAINS,
            self::CONFIG_CSP_STRICT_DYNAMIC,
            "Garden.Format.WarnLeaving",
            HstsModel::MAX_AGE_KEY,
            HstsModel::INCLUDE_SUBDOMAINS_KEY,
            HstsModel::PRELOAD_KEY,
            "Garden.Password.MinLength",
            "Garden.Cookie.PersistExpiry",
            "Garden.SignIn.Attempts",
            "Garden.SignIn.LockoutTime",
            "Garden.Privacy.IPs",
        ]);

        // Set the model on the form.
        $this->Form->setModel($configurationModel);
        $configurationModel->Data["hasExportPermission"] =
            Gdn::session()
                ->getPermissions()
                ->has("Garden.Exports.Manage") && !Gdn::session()->isSpoofedInUser();

        try {
            $churnExportController = \Gdn::getContainer()->get(ChurnExportApiController::class);
            $exportAvailable = $churnExportController->get_exportAvailable();
            if ($exportAvailable["exportAvailable"]) {
                $exportStatus = $exportAvailable["exportStatus"] ?? "Pending";
                $configurationModel->Data["exportAvailableForDownload"] = $exportStatus === "completed";
                $configurationModel->Data["exportExpirationDate"] = $exportAvailable["exportExpiry"] ?? null;
            }
        } catch (ClientException $e) {
            $logger = Gdn::getContainer()->get(LoggerInterface::class);
            $logger->log(LogLevel::INFO, $e->getMessage());
        }

        // If seeing the form for the first time...
        if ($this->Form->authenticatedPostBack() === false) {
            // Format trusted domains as a string
            $trustedDomains = val(self::CONFIG_TRUSTED_DOMAINS, $configurationModel->Data);
            if (is_array($trustedDomains)) {
                $trustedDomains = implode("\n", $trustedDomains);
            }
            $configurationModel->Data[self::CONFIG_TRUSTED_DOMAINS] = $trustedDomains;
        } else {
            $this->Form->setFormValue(
                self::CONFIG_CSP_STRICT_DYNAMIC,
                (bool) $this->Form->getValue(self::CONFIG_CSP_STRICT_DYNAMIC)
            );
            $configurationModel->Validation->applyRule("Garden.Password.MinLength", "Integer");
            if ($this->Form->getValue("Garden.Password.MinLength") < self::DEFAULT_PASSWORD_LENGTH) {
                $this->Form->addError(
                    sprintf(
                        "Password minimum length value should be greater than or equal %d.",
                        self::DEFAULT_PASSWORD_LENGTH
                    ),
                    "Garden.Password.MinLength"
                );
                $this->Form->setFormValue("Garden.Password.MinLength", self::DEFAULT_PASSWORD_LENGTH);
            } else {
                $this->Form->setFormValue(
                    "Garden.Password.MinLength",
                    $this->Form->getValue("Garden.Password.MinLength")
                );
            }

            if (
                $this->Form->getValue("Garden.SignIn.Attempts") < 0 ||
                $this->Form->getValue("Garden.SignIn.Attempts") == ""
            ) {
                $this->Form->setFormValue("Garden.SignIn.Attempts", 0);
            }

            if (
                $this->Form->getValue("Garden.SignIn.LockoutTime") < 0 ||
                $this->Form->getValue("Garden.SignIn.LockoutTime") == ""
            ) {
                $this->Form->setFormValue("Garden.SignIn.LockoutTime", 0);
            }
            // Format the trusted domains as an array based on newlines & spaces
            $trustedDomains = $this->Form->getValue(self::CONFIG_TRUSTED_DOMAINS);
            $trustedDomains = ArrayUtils::explodeTrim("\n", $trustedDomains);
            $trustedDomains = array_unique(array_filter($trustedDomains));
            $trustedDomains = implode("\n", $trustedDomains);
            $this->Form->setFormValue(self::CONFIG_TRUSTED_DOMAINS, $trustedDomains);

            // Join CSP domains with newlines.
            $cspDomains = $this->Form->getValue("ContentSecurityPolicy.ScriptSrc.AllowedDomains", "");
            $cspDomains = explode("\n", $cspDomains);
            $cspDomains = array_unique(array_filter($cspDomains));
            $this->Form->setFormValue("ContentSecurityPolicy.ScriptSrc.AllowedDomains", $cspDomains);

            $this->Form->setFormValue(
                "Garden.Format.DisableUrlEmbeds",
                $this->Form->getValue("Garden.Format.DisableUrlEmbeds") !== "1"
            );

            $this->Form->setFormValue(
                HstsModel::INCLUDE_SUBDOMAINS_KEY,
                $this->Form->getValue(HstsModel::INCLUDE_SUBDOMAINS_KEY) === "1"
            );
            $this->Form->setFormValue(HstsModel::PRELOAD_KEY, $this->Form->getValue(HstsModel::PRELOAD_KEY) === "1");

            $this->Form->setFormValue(HstsModel::MAX_AGE_KEY, (int) $this->Form->getValue(HstsModel::MAX_AGE_KEY));

            if ($this->Form->save() !== false) {
                $this->informMessage(t("Your settings have been saved."));
            }

            // Reformat array as string so it displays properly in the form
            $this->Form->setFormValue(self::CONFIG_TRUSTED_DOMAINS, $trustedDomains);
        }
        if (!empty($configurationModel->Data)) {
            // Apply the config settings to the form.
            $this->Form->setData($configurationModel->Data);
        }

        $this->render();
    }

    /**
     * Backwards compatibility.
     *
     * @deprecated 2.4 Legacy redirect. Use SettingsController::layout instead.
     */
    public function homepage()
    {
        redirectTo("/settings/layout");
    }

    /**
     * Backwards compatibility.
     *
     * @deprecated 2.4 Legacy redirect. Use SettingsController::branding instead.
     */
    public function banner()
    {
        redirectTo("/appearance/branding");
    }

    /**
     * Manage automation rules settings page.
     *
     * @return void
     */
    public function automationRules()
    {
        $this->permission("Garden.Settings.Manage");
        $this->setHighlightRoute("dashboard/settings/automation-rules");
        $this->title(t("Automation Rules Settings"));
        if (Gdn::config("Feature.AutomationRules.Enabled")) {
            $this->render("automation-rules");
        } else {
            $this->renderException(notFoundException());
        }
    }

    /**
     * Outgoing Email management screen.
     *
     * @since 2.0.0
     * @access public
     */
    public function email()
    {
        $this->permission("Garden.Settings.Manage");
        $this->setHighlightRoute("dashboard/settings/email");
        $this->title(t("Email Settings"));
        $this->render();
    }

    /**
     * Manage email digest settings
     */

    public function digest()
    {
        $this->permission(["Garden.Settings.Manage", "Garden.Community.Manage"]);
        $this->setHighlightRoute("dashboard/settings/digest");
        $this->title(t("Digest Settings"));
        $emailDigestEnabled = !Gdn::config("Garden.Email.Disabled");
        if ($emailDigestEnabled) {
            $this->render();
        } else {
            redirectTo("home/index");
        }
    }

    /**
     * Manage AI Suggested Answers settings
     */
    public function aiSuggestions()
    {
        $this->permission("Garden.Settings.Manage");
        $this->setHighlightRoute("settings/ai-suggestions");
        $this->title(t("AI Suggested Answers"));
        if (Gdn::config("Feature.AISuggestions.Enabled")) {
            $this->render("ai-suggestions");
        } else {
            $this->renderException(notFoundException());
        }
    }

    /**
     * Manage Interests and Suggested Content settings
     */
    public function interests()
    {
        $this->permission("Garden.Settings.Manage");
        $this->setHighlightRoute("settings/interests");
        $this->title(t("Interests & Suggested Content"));
        if (Gdn::config("Feature.SuggestedContent.Enabled")) {
            $this->render("interests");
        } else {
            $this->renderException(notFoundException());
        }
    }

    /**
     * Manages the Tagging.Discussions.Enabled setting.
     *
     * @param String $value Either 'true' or 'false', whether to enable tagging.
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function enableTagging($value)
    {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }
        $value = strtolower($value);
        if (Gdn::session()->checkPermission("Garden.Community.Manage")) {
            saveToConfig("Tagging.Discussions.Enabled", $value === "true");
            if ($value === "true") {
                $newToggle = wrap(
                    anchor(
                        '<div class="toggle-well"></div><div class="toggle-slider"></div>',
                        "/dashboard/settings/enabletagging/false",
                        "Hijack"
                    ),
                    "span",
                    ["class" => "toggle-wrap toggle-wrap-on"]
                );
                $this->jsonTarget(".js-foggy", "foggyOff", "Trigger");
                $this->informMessage(sprintf(t("%s enabled."), t("Tagging")));
            } else {
                $newToggle = wrap(
                    anchor(
                        '<div class="toggle-well"></div><div class="toggle-slider"></div>',
                        "/dashboard/settings/enabletagging/true",
                        "Hijack"
                    ),
                    "span",
                    ["class" => "toggle-wrap toggle-wrap-off"]
                );
                $this->jsonTarget(".js-foggy", "foggyOn", "Trigger");
                $this->informMessage(sprintf(t("%s disabled."), t("Tagging")));
            }
            $this->jsonTarget("#enable-tagging-toggle", $newToggle);
        }

        if (Gdn::config("Tagging.Discussions.Enabled", false)) {
            $this->widgetService->registerWidget(TagModule::class);
        } else {
            $this->widgetService->unregisterWidget(TagModule::class);
        }

        $this->render("blank", "utility");
    }

    /**
     * Manages the Garden.PrivateCommunity setting.
     *
     * @param String $enabled Either 'true' or 'false', whether to enable a private community.
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function privateCommunity($enabled)
    {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }
        $enabled = strtolower($enabled);
        if (Gdn::session()->checkPermission("Garden.Community.Manage")) {
            saveToConfig("Garden.PrivateCommunity", $enabled === "true");
            if ($enabled === "true") {
                $newToggle = wrap(
                    anchor(
                        '<div class="toggle-well"></div><div class="toggle-slider"></div>',
                        "/dashboard/settings/privatecommunity/false",
                        "Hijack"
                    ),
                    "span",
                    ["class" => "toggle-wrap toggle-wrap-on"]
                );
                $this->informMessage(sprintf(t("%s enabled."), t("Private Communities")));
            } else {
                $newToggle = wrap(
                    anchor(
                        '<div class="toggle-well"></div><div class="toggle-slider"></div>',
                        "/dashboard/settings/privatecommunity/true",
                        "Hijack"
                    ),
                    "span",
                    ["class" => "toggle-wrap toggle-wrap-off"]
                );
                $this->informMessage(sprintf(t("%s disabled."), t("Private Communities")));
            }
            $this->jsonTarget("#private-community-toggle", $newToggle);
        }
        $this->render("blank", "utility");
    }

    /**
     * Main dashboard.
     *
     * You can override this method with a method in your plugin named
     * SettingsController_Index_Create. You can hook into it with methods named
     * SettingsController_Index_Before and SettingsController_Index_After.
     *
     * @since 2.0.0
     * @access public
     */
    public function index()
    {
        // Confirm that the user has at least one of the many admin preferences.
        $this->permission(
            [
                "Garden.Settings.View",
                "Garden.Settings.Manage",
                "Garden.Community.Manage",
                "Garden.Moderation.Manage",
                "Garden.Users.Add",
                "Garden.Users.Edit",
                "Garden.Users.Delete",
                "Garden.Users.Approve",
            ],
            false
        );

        // Resolve our default landing page redirection based on permissions.
        if (
            !Gdn::session()->checkPermission(
                ["Garden.Settings.View", "Garden.Settings.Manage", "Garden.Community.Manage"],
                false
            )
        ) {
            // We don't have permission to see the dashboard/home.
            redirectTo(DashboardNavModule::getDashboardNav()->getUrlForSection("Moderation"));
        }

        // Still here?
        redirectTo("/dashboard/role");
    }

    public function home()
    {
        $this->addJsFile("settings.js");
        $this->title(t("Dashboard"));

        $this->RequiredAdminPermissions = ["Garden.Settings.View", "Garden.Settings.Manage", "Garden.Community.Manage"];

        $this->fireEvent("DefineAdminPermissions");
        $this->permission($this->RequiredAdminPermissions, false);
        $this->setHighlightRoute("dashboard/settings");

        $userModel = Gdn::userModel();

        // Get recently active users
        $this->ActiveUserData = $userModel->getActiveUsers(5);

        // Check for updates
        $this->addUpdateCheck();

        $this->addDefinition("ExpandText", t("more"));
        $this->addDefinition("CollapseText", t("less"));

        // Fire an event so other applications can add some data to be displayed
        $this->fireEvent("DashboardData");

        Gdn_Theme::section("DashboardHome");
        $this->setData("IsWidePage", true);
        $this->CssClass .= " dashboard";

        $this->render("index");
    }

    /**
     * Adds information to the definition list that causes the app to "phone
     * home" and see if there are upgrades available.
     *
     * Currently added to the dashboard only. Nothing renders with this method.
     * It is public so it can be added by plugins.
     */
    public function addUpdateCheck()
    {
        if (c("Garden.NoUpdateCheck")) {
            return;
        }

        // Check to see if the application needs to phone-home for updates. Doing
        // this here because this method is always called when admin pages are
        // loaded regardless of the application loading them.
        $updateCheckDate = Gdn::config("Garden.UpdateCheckDate", "");
        if (
            $updateCheckDate == "" || // was not previous defined
            !isTimestamp($updateCheckDate) || // was not a valid timestamp
            $updateCheckDate < strtotime("-1 day") // was not done within the last day
        ) {
            $updateData = [];

            // Grab all of the available addons & versions.
            foreach ([Addon::TYPE_ADDON, Addon::TYPE_THEME] as $type) {
                $addons = Gdn::addonManager()->lookupAllByType($type);
                /* @var Addon $addon */
                foreach ($addons as $addon) {
                    $updateData[] = [
                        "Name" => $addon->getRawKey(),
                        "Version" => $addon->getVersion(),
                        "Type" => $addon->getInfoValue("oldType", $type),
                    ];
                }
            }

            // Dump the entire set of information into the definition list. The client will ping the server for updates.
            $this->addDefinition("UpdateChecks", $updateData);
        }
    }

    /**
     * Manage list of locales.
     *
     * @since 2.0.0
     * @access public
     * @param string $Op 'enable' or 'disable'
     * @param string $LocaleKey Unique ID of locale to be modified.
     * @param string $TransientKey Security token.
     */
    public function locales($Op = null, $LocaleKey = null)
    {
        $this->permission("Garden.Settings.Manage");

        $this->title(t("Locales"));
        $this->setHighlightRoute("/settings/locales");
        $this->addJsFile("addons.js");

        // Get the available locale packs.
        $AvailableLocales = $this->localeModel->availableLocalePacks();

        // Get the enabled locale packs.
        $EnabledLocales = $this->localeModel->enabledLocalePacks();

        // Check to enable/disable a locale.
        if ($this->Form->authenticatedPostBack() && !$Op) {
            // Save the default locale.
            saveToConfig("Garden.Locale", $this->Form->getFormValue("Locale"));
            $this->informMessage(t("Your changes have been saved."));

            Gdn::locale()->refresh();
            redirectTo("/settings/locales");
        } else {
            $this->Form->setValue("Locale", Gdn_Locale::canonicalize(c("Garden.Locale", "en")));
        }

        if ($Op) {
            switch (strtolower($Op)) {
                case "enable":
                    $this->enableLocale($LocaleKey, val($LocaleKey, $AvailableLocales), $EnabledLocales);
                    break;
                case "disable":
                    $this->disableLocale($LocaleKey, val($LocaleKey, $AvailableLocales), $EnabledLocales);
            }
        }

        // Check for the default locale warning.
        $DefaultLocale = Gdn_Locale::canonicalize(c("Garden.Locale"));
        if ($DefaultLocale !== "en") {
            $LocaleFound = false;
            $MatchingLocales = [];
            foreach ($AvailableLocales as $Key => $LocaleInfo) {
                $Locale = val("Locale", $LocaleInfo);
                if ($Locale == $DefaultLocale) {
                    $MatchingLocales[] = val("Name", $LocaleInfo, $Key);
                }

                if (val($Key, $EnabledLocales) == $DefaultLocale) {
                    $LocaleFound = true;
                }
            }
            $this->setData("DefaultLocale", $DefaultLocale);
            $this->setData("DefaultLocaleWarning", !$LocaleFound);
            $this->setData("MatchingLocalePacks", htmlspecialchars(implode(", ", $MatchingLocales)));
        }

        // Remove all hidden locales, unless they are enabled.
        $AvailableLocales = array_filter($AvailableLocales, function ($locale) use ($EnabledLocales) {
            return !val("Hidden", $locale) || isset($EnabledLocales[val("Index", $locale)]);
        });
        static::sortAddons($AvailableLocales);

        $this->setData("AvailableLocales", $AvailableLocales);
        $this->setData("EnabledLocales", $EnabledLocales);
        $this->setData("Locales", $this->localeModel->availableLocales());
        $this->render();
    }

    public function enableLocale($addonName, $addonInfo)
    {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }
        $this->permission("Garden.Settings.Manage");

        if (!is_array($addonInfo)) {
            $this->Form->addError(
                "@" . sprintf(t("The %s locale pack does not exist."), htmlspecialchars($addonName)),
                "LocaleKey"
            );
        } elseif (!isset($addonInfo["Locale"])) {
            $this->Form->addError("ValidateRequired", "Locale");
        } else {
            Gdn::config()->saveToConfig("EnabledLocales.$addonName", $addonInfo["Locale"]);
            $this->localeModel->enableLocale($addonInfo["Locale"]);
            $this->informMessage(sprintf(t("%s Enabled."), val("Name", $addonInfo, t("Locale"))));
        }

        $this->handleAddonToggle($addonName, $addonInfo, "locales", true);
        $this->render("blank", "utility", "dashboard");
    }

    public function disableLocale($addonName, $addonInfo)
    {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }
        $this->permission("Garden.Settings.Manage");

        removeFromConfig("EnabledLocales.$addonName");
        $this->informMessage(sprintf(t("%s Disabled."), val("Name", $addonInfo, t("Locale"))));

        $this->handleAddonToggle($addonName, $addonInfo, "locales", false);
        $this->render("blank", "utility", "dashboard");
    }

    /**
     * Manage list of plugins.
     *
     * @since 2.0.0
     * @access public
     * @param string $filter 'enabled', 'disabled', or 'all' (default)
     * @param string $pluginName Unique ID of plugin to be modified.
     * @param string $TransientKey Security token.
     */
    public function plugins($filter = "", $pluginName = "")
    {
        $this->permission("Garden.Settings.Manage");

        // Page setup
        $this->addJsFile("addons.js");
        $this->title(t("Plugins"));
        $this->setHighlightRoute("dashboard/settings/plugins");

        // Verify addon cache integrity?
        if ($this->verifyAddonCache()) {
            $this->addJsFile("addoncache.js");
            $this->addDefinition("VerifyCache", "addon");
        }

        if (!in_array($filter, ["enabled", "disabled"])) {
            $filter = "all";
        }
        $this->Filter = $filter;

        // Retrieve all available plugins from the plugins directory
        $this->EnabledPlugins = Gdn::pluginManager()->enabledPlugins();
        $this->AvailablePlugins = Gdn::pluginManager()->availablePlugins();

        if ($pluginName != "") {
            if (in_array(strtolower($pluginName), array_map("strtolower", array_keys($this->EnabledPlugins)))) {
                $this->disablePlugin($pluginName, $filter);
            } else {
                $this->enablePlugin($pluginName, $filter);
            }
        } else {
            self::sortAddons($this->EnabledPlugins);
            self::sortAddons($this->AvailablePlugins);
            $this->render();
        }
    }

    public function disablePlugin($pluginName, $filter = "all")
    {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }

        $this->permission("Garden.Settings.Manage");

        $action = "none";
        if ($filter == "enabled") {
            $action = "SlideUp";
        }

        $addon = Gdn::addonManager()->lookupAddon($pluginName);

        try {
            Gdn::pluginManager()->disablePlugin($pluginName);
            Gdn_LibraryMap::clearCache();
            $this->informMessage(sprintf(t("%s Disabled."), val("name", $addon->getInfo(), t("Plugin"))));
            $this->EventArguments["PluginName"] = $pluginName;
            $this->fireEvent("AfterDisablePlugin");
        } catch (Exception $e) {
            $this->Form->addError($e);
        }

        $this->handleAddonToggle($pluginName, $addon->getInfo(), "plugins", false, $filter, $action);
        $this->reloadPanelNavigation("Settings", "/dashboard/settings/plugins");
        $this->render("blank", "utility", "dashboard");
    }

    /**
     * Enable a plugin.
     *
     * @param string $pluginName The key of the plugin.
     * @param string $filter
     */
    public function enablePlugin($pluginName, $filter = "all")
    {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }

        $this->permission("Garden.Settings.Manage");

        $action = "none";
        if ($filter == "disabled") {
            $action = "SlideUp";
        }

        $addon = Gdn::addonManager()->lookupAddon($pluginName);
        $requirementsEnabled = [];
        try {
            $this->addonModel->validateEnable($addon);

            $validation = new Gdn_Validation();
            $result = Gdn::pluginManager()->enablePlugin($pluginName, $validation);
            if (!$result) {
                $this->Form->setValidationResults($validation->results());
            } else {
                Gdn_LibraryMap::clearCache();

                if (is_array($result) && array_key_exists("RequirementsEnabled", $result)) {
                    if (is_array($result["RequirementsEnabled"]) && count($result["RequirementsEnabled"]) > 0) {
                        $requirementsEnabled = $result["RequirementsEnabled"];
                        $requirementNames = [];
                        foreach ($requirementsEnabled as $requiredAddon) {
                            $requirementNames[] = val("name", $requiredAddon->getInfo(), t("Plugin"));
                        }
                        $this->informMessage(
                            sprintf(t("Required addons enabled: %s"), implode(", ", $requirementNames))
                        );
                    }
                }

                $this->informMessage(sprintf(t("%s Enabled."), val("name", $addon->getInfo(), t("Plugin"))));
            }
            $this->EventArguments["PluginName"] = $pluginName;
            $this->EventArguments["Validation"] = $validation;
            $this->fireEvent("AfterEnablePlugin");
        } catch (Exception $e) {
            $this->Form->addError($e);
        }

        $this->handleAddonToggle($pluginName, $addon->getInfo(), "plugins", true, $filter, $action);
        if (count($requirementsEnabled) > 0) {
            foreach ($requirementsEnabled as $requiredAddon) {
                /** @var $requiredAddon Addon */
                $this->handleAddonToggle(
                    $requiredAddon->getKey(),
                    $requiredAddon->getInfo(),
                    "plugins",
                    true,
                    $filter,
                    $action
                );
            }
        }

        $this->reloadPanelNavigation("Settings", "/dashboard/settings/plugins");
        $this->render("blank", "utility", "dashboard");
    }

    /**
     * Configuration of registration settings.
     *
     * Events: BeforeRegistrationUpdate
     *
     * @since 2.0.0
     * @access public
     * @param string $redirectUrl Where to send user after registration.
     */
    public function registration($redirectUrl = "")
    {
        $this->permission("Garden.Settings.Manage");
        $this->setHighlightRoute("dashboard/settings/registration");

        $this->addJsFile("registration.js");
        $this->title(t("Registration"));

        // Load roles with sign-in permission
        $roleModel = new RoleModel();
        $this->RoleData = $roleModel->getByPermission("Garden.SignIn.Allow");
        $this->setData("_Roles", array_column($this->RoleData->resultArray(), "Name", "RoleID"));

        // Get currently selected InvitationOptions
        $this->ExistingRoleInvitations = Gdn::config("Garden.Registration.InviteRoles");
        if (is_array($this->ExistingRoleInvitations) === false) {
            $this->ExistingRoleInvitations = [];
        }

        // Get the currently selected Expiration Length
        $this->InviteExpiration = Gdn::config("Garden.Registration.InviteExpiration", "");

        // Get target
        $this->InviteTarget = Gdn::config("Garden.Registration.InviteTarget", "");

        // Registration methods.
        $this->RegistrationMethods = [
            // 'Closed' => "Registration is closed.",
            "Basic" => "New users fill out a simple form and are granted access immediately.",
            "Approval" => "New users are reviewed and approved by an administrator (that's you!).",
            "Invitation" => "Existing members send invitations to new members.",
            "Connect" => "New users are only registered through SSO plugins.",
        ];

        // Options for how many invitations a role can send out per month.
        $this->InvitationOptions = [
            "0" => t("None"),
            "1" => "1",
            "2" => "2",
            "5" => "5",
            "-1" => t("Unlimited"),
        ];

        // Options for when invitations should expire.
        $this->InviteExpirationOptions = [
            "1 week" => t("1 week after being sent"),
            "2 weeks" => t("2 weeks after being sent"),
            "1 month" => t("1 month after being sent"),
            "FALSE" => t("never"),
        ];

        // Replace 'Captcha' with 'Basic' if needed
        if (c("Garden.Registration.Method") == "Captcha") {
            saveToConfig("Garden.Registration.Method", "Basic");
        }

        // Create a model to save configuration settings
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);

        $registrationOptions = [
            "Garden.Registration.Method" => "Basic",
            "Garden.Registration.InviteExpiration",
            "Garden.Registration.InviteTarget",
            "Garden.Registration.ConfirmEmail",
            "Garden.Registration.SSOConfirmEmail",
        ];
        $configurationModel->setField($registrationOptions);

        $roleModel = new RoleModel();
        $unconfirmedCount = $roleModel->getByType(RoleModel::TYPE_UNCONFIRMED)->count();
        $this->setData("ConfirmationSupported", $unconfirmedCount > 0);
        unset($roleModel, $unconfirmedCount);

        $this->EventArguments["Validation"] = &$validation;
        $this->EventArguments["Configuration"] = &$configurationModel;
        $this->fireEvent("Registration");

        // Set the model on the forms.
        $this->Form->setModel($configurationModel);

        if ($this->Form->authenticatedPostBack() === false) {
            $this->Form->setData($configurationModel->Data);
        } else {
            // Define some validation rules for the fields being saved
            $configurationModel->Validation->applyRule("Garden.Registration.Method", "Required");

            // Define the Garden.Registration.RoleInvitations setting based on the postback values
            $invitationRoleIDs = (array) $this->Form->getValue("InvitationRoleID");
            $invitationCounts = (array) $this->Form->getValue("InvitationCount");
            $this->ExistingRoleInvitations = array_combine($invitationRoleIDs, $invitationCounts);
            $configurationModel->forceSetting("Garden.Registration.InviteRoles", $this->ExistingRoleInvitations);

            if (
                $this->data("ConfirmationSupported") === false &&
                ($this->Form->getValue("Garden.Registration.ConfirmEmail") ||
                    $this->Form->getValue("Garden.Registration.SSOConfirmEmail"))
            ) {
                $this->Form->addError('A role with default type "unconfirmed" is required to use email confirmation.');
            }

            // Event hook
            $this->EventArguments["ConfigurationModel"] = &$configurationModel;
            $this->fireEvent("BeforeRegistrationUpdate");

            // Save!
            if ($this->Form->save() !== false) {
                // Get the updated InviteTarget
                $this->InviteTarget = Gdn::config("Garden.Registration.InviteTarget", "");

                // Get the updated Expiration Length
                $this->InviteExpiration = Gdn::config("Garden.Registration.InviteExpiration", "");
                $this->informMessage(t("Your settings have been saved."));
                if ($redirectUrl != "") {
                    $this->setRedirectTo($redirectUrl, false);
                }
            }
        }

        $this->render();
    }

    /**
     * Sort list of addons for display.
     *
     * @since 2.0.0
     * @access public
     * @param array $array Addon data (e.g. $PluginInfo).
     * @param bool $filter Whether to exclude hidden addons (defaults to TRUE).
     */
    public static function sortAddons(&$array, $filter = true)
    {
        // Make sure every addon has a name.
        foreach ($array as $key => $value) {
            if ($filter && val("Hidden", $value)) {
                unset($array[$key]);
                continue;
            }

            $name = val("Name", $value, $key);
            setValue("Name", $array[$key], $name);
        }
        uasort($array, ["SettingsController", "CompareAddonName"]);
    }

    /**
     * Compare addon names for uasort.
     *
     * @since 2.0.0
     * @access public
     * @see self::sortAddons()
     * @param array $a First addon data.
     * @param array $b Second addon data.
     * @return int Result of strcasecmp.
     */
    public static function compareAddonName($a, $b)
    {
        return strcasecmp(val("EnName", $a, val("Name", $a)), val("EnName", $b, val("Name", $b)));
    }

    /**
     * Test and addon to see if there are any fatal errors during install.
     *
     * @since 2.0.0
     * @access public
     * @param string $addonType
     * @param string $addonName
     * @param string $transientKey Security token.
     */
    public function testAddon($addonType = "", $addonName = "", $transientKey = "")
    {
        $this->permission("Garden.Settings.Manage");

        if (!in_array($addonType, ["Plugin", "Application", "Theme", "Locale"])) {
            $addonType = "Plugin";
        }

        $session = Gdn::session();
        $addonName = $session->validateTransientKey($transientKey) ? $addonName : "";
        if ($addonType == "Locale") {
            $addonManager = $this->localeModel;
            $testMethod = "TestLocale";
        } else {
            $addonManagerName = $addonType . "Manager";
            $testMethod = "Test" . $addonType;
            $addonManager = Gdn::factory($addonManagerName);
        }
        if ($addonName != "") {
            $validation = new Gdn_Validation();

            try {
                $addonManager->$testMethod($addonName, $validation);
            } catch (Exception $ex) {
                if (debug()) {
                    throw $ex;
                } else {
                    echo $ex->getMessage();
                    return;
                }
            }
        }

        ob_clean();
        echo "Success";
    }

    /**
     * Manage options for a theme.
     *
     * @since 2.0.0
     * @access public
     * @todo Why is this in a giant try/catch block?
     */
    public function themeOptions()
    {
        $this->permission("Garden.Settings.Manage");

        try {
            $this->addJsFile("addons.js");
            $this->setHighlightRoute("dashboard/settings/themeoptions");

            $themeManager = Gdn::themeManager();
            $this->setData("ThemeInfo", $themeManager->enabledThemeInfo());

            if ($this->Form->authenticatedPostBack()) {
                // Save the styles to the config.
                $styleKey = $this->Form->getFormValue("StyleKey");

                $configSaveData = [
                    "Garden.ThemeOptions.Styles.Key" => $styleKey,
                    "Garden.ThemeOptions.Styles.Value" => $this->data("ThemeInfo.Options.Styles.$styleKey.Basename"),
                ];

                // Save the text to the locale.
                $translations = [];
                foreach ($this->data("ThemeInfo.Options.Text", []) as $key => $default) {
                    $value = $this->Form->getFormValue($this->Form->escapeFieldName("Text_" . $key));
                    $configSaveData["ThemeOption.{$key}"] = $value;
                    //$this->Form->setFormValue('Text_'.$Key, $Value);
                }

                saveToConfig($configSaveData);
                $this->informMessage(t("Your changes have been saved."));
            }

            $this->setData("ThemeOptions", c("Garden.ThemeOptions"));
            $styleKey = $this->data("ThemeOptions.Styles.Key");

            if (!$this->Form->isPostBack()) {
                foreach ($this->data("ThemeInfo.Options.Text", []) as $key => $options) {
                    $default = val("Default", $options, "");
                    $value = c("ThemeOption.{$key}", "#DEFAULT#");
                    if ($value === "#DEFAULT#") {
                        $value = $default;
                    }

                    $this->Form->setValue($this->Form->escapeFieldName("Text_" . $key), $value);
                }
            }

            $this->setData("ThemeFolder", $themeManager->enabledTheme());
            $this->title(t("Theme Options"));
            $this->Form->addHidden("StyleKey", $styleKey);
        } catch (Exception $ex) {
            $this->Form->addError($ex);
        }

        $this->render();
    }

    /**
     * Manage options for a mobile theme.
     *
     * @since 2.0.0
     * @access public
     * @todo Why is this in a giant try/catch block?
     */
    public function mobileThemeOptions()
    {
        $this->permission("Garden.Settings.Manage");

        try {
            $this->addJsFile("addons.js");
            $this->setHighlightRoute("dashboard/settings/mobilethemeoptions");

            $themeManager = Gdn::themeManager();
            $enabledThemeName = $themeManager->mobileTheme();
            $enabledThemeInfo = $themeManager->getThemeInfo($enabledThemeName);

            $this->setData("ThemeInfo", $enabledThemeInfo);

            if ($this->Form->authenticatedPostBack()) {
                // Save the styles to the config.
                $styleKey = $this->Form->getFormValue("StyleKey");

                $configSaveData = [
                    "Garden.MobileThemeOptions.Styles.Key" => $styleKey,
                    "Garden.MobileThemeOptions.Styles.Value" => $this->data(
                        "ThemeInfo.Options.Styles.$styleKey.Basename"
                    ),
                ];

                // Save the text to the locale.
                $translations = [];
                foreach ($this->data("ThemeInfo.Options.Text", []) as $key => $default) {
                    $value = $this->Form->getFormValue($this->Form->escapeFieldName("Text_" . $key));
                    $configSaveData["ThemeOption.{$key}"] = $value;
                    //$this->Form->setFormValue('Text_'.$Key, $Value);
                }

                saveToConfig($configSaveData);
                $this->fireEvent["AfterSaveThemeOptions"];

                $this->informMessage(t("Your changes have been saved."));
            }

            $this->setData("ThemeOptions", c("Garden.MobileThemeOptions"));
            $styleKey = $this->data("ThemeOptions.Styles.Key");

            if (!$this->Form->authenticatedPostBack()) {
                foreach ($this->data("ThemeInfo.Options.Text", []) as $key => $options) {
                    $default = val("Default", $options, "");
                    $value = c("ThemeOption.{$key}", "#DEFAULT#");
                    if ($value === "#DEFAULT#") {
                        $value = $default;
                    }

                    $this->Form->setFormValue($this->Form->escapeFieldName("Text_" . $key), $value);
                }
            }

            $this->setData("ThemeFolder", $enabledThemeName);
            $this->title(t("Mobile Theme Options"));
            $this->Form->addHidden("StyleKey", $styleKey);
        } catch (Exception $ex) {
            $this->Form->addError($ex);
        }

        $this->render("themeoptions");
    }

    /**
     * Themes management screen.
     *
     * @since 2.0.0
     * @access public
     * @param string $themeName Unique ID.
     * @param string $transientKey Security token.
     */
    public function themes($themeName = "", $transientKey = "")
    {
        $this->addJsFile("addons.js");
        $this->setData("Title", t("Themes"));

        // Verify addon cache integrity?
        if ($this->verifyAddonCache()) {
            $this->addJsFile("addoncache.js");
            $this->addDefinition("VerifyCache", "theme");
        }

        $this->permission("Garden.Settings.Manage");
        $this->setHighlightRoute("dashboard/settings/themes");

        $themeKey = Gdn::themeManager()->getEnabledDesktopThemeKey();

        // Check to see if the resolved theme is the same as the one set in config. If not, we couldn't
        // find the theme and are using the default theme instead. Show an error.
        $enabledThemeKey = c("Garden.Theme", Gdn_ThemeManager::DEFAULT_DESKTOP_THEME);
        if ($themeName === "" && $themeKey !== $enabledThemeKey) {
            $message = t("The theme with key %s could not be found and will not be started.");
            $this->Form->addError(sprintf($message, $enabledThemeKey));
        }

        $currentTheme = $this->themeInfoToMediaItem($themeKey, true);
        $this->setData("CurrentTheme", $currentTheme);

        $themes = Gdn::themeManager()->availableThemes();
        uasort($themes, ["SettingsController", "_NameSort"]);

        // Remove themes that are archived
        $remove = [];
        foreach ($themes as $index => $theme) {
            $archived = val("Archived", $theme);
            if ($archived) {
                $remove[] = $index;
            }

            // Remove mobile themes, as they have own page.
            if (isset($theme["IsMobile"]) && $theme["IsMobile"]) {
                unset($themes[$index]);
            }
        }
        foreach ($remove as $index) {
            unset($themes[$index]);
        }
        $this->setData("AvailableThemes", $themes);

        if ($themeName != "" && Gdn::session()->validateTransientKey($transientKey)) {
            try {
                $themeInfo = Gdn::themeManager()->getThemeInfo($themeName);
                if ($themeInfo === false) {
                    throw new Exception(sprintf(t("Could not find a theme identified by '%s'"), $themeName));
                }

                Gdn::session()->setPreference(["PreviewThemeName" => "", "PreviewThemeFolder" => ""]); // Clear out the preview
                Gdn::themeManager()->enableTheme($themeName);
                $this->EventArguments["ThemeName"] = $themeName;
                $this->EventArguments["ThemeInfo"] = $themeInfo;
                $this->fireEvent("AfterEnableTheme");
            } catch (Exception $ex) {
                $this->Form->addError($ex);
            }

            if ($this->Form->errorCount() == 0) {
                redirectTo("/settings/themes");
            }
        }
        $this->render();
    }

    public function themeInfo($themeName)
    {
        $this->permission("Garden.Settings.Manage");
        $themeMedia = $this->themeInfoToMediaItem($themeName);
        $this->setData("Theme", $themeMedia);
        $this->render();
    }

    /**
     * Compiles theme info data into a media module.
     *
     * @param string $themeKey The theme key from the themeinfo array.
     * @param bool $isCurrent Whether the theme is the current theme (if so, adds a little current-theme flag when rendering).
     * @return MediaItemModule A media item representing the theme.
     * @throws Exception
     */
    private function themeInfoToMediaItem($themeKey, $isCurrent = false)
    {
        $themeInfo = Gdn::themeManager()->getThemeInfo($themeKey);

        if (!$themeInfo) {
            throw new Exception(sprintf(t("Theme with key %s not found."), $themeKey));
        }
        $options = val("Options", $themeInfo, []);
        $iconUrl = val(
            "IconUrl",
            $themeInfo,
            val("ScreenshotUrl", $themeInfo, "applications/dashboard/design/images/theme-placeholder.svg")
        );
        $themeName = val("Name", $themeInfo, val("Index", $themeInfo, $themeKey));
        $themeUrl = val("ThemeUrl", $themeInfo, "");
        $description = val("Description", $themeInfo, "");
        $version = val("Version", $themeInfo, "");
        $newVersion = val("NewVersion", $themeInfo, "");
        $attr = [];

        if ($isCurrent) {
            $attr["class"] = "media-callout-grey-bg";
        }

        $media = new MediaItemModule($themeName, $themeUrl, $description, "div", $attr);
        $media->setView("media-callout");
        $media->addOption("has-options", !empty($options));
        $media->addOption("has-upgrade", $newVersion != "" && version_compare($newVersion, $version, ">"));
        $media->addOption("new-version", val("NewVersion", $themeInfo, ""));
        $media->setImage($iconUrl);

        if ($isCurrent) {
            $media->addOption("is-current", $isCurrent);
        }

        // Meta

        // Add author meta
        $author = val("Author", $themeInfo, "");
        $authorUrl = val("AuthorUrl", $themeInfo, "");
        $media->addMetaIf(
            $author != "",
            '<span class="media-meta author">' .
                sprintf("Created by %s", $authorUrl != "" ? anchor($author, $authorUrl) : $author) .
                "</span>"
        );

        // Add version meta
        $version = val("Version", $themeInfo, "");
        $media->addMetaIf(
            $version != "",
            '<span class="media-meta version">' . sprintf(t("Version %s"), $version) . "</span>"
        );

        // Add requirements meta
        $requirements = val("RequiredApplications", $themeInfo, []);
        $required = [];
        $requiredString = "";

        if (!empty($requirements)) {
            foreach ($requirements as $requirement => $versionInfo) {
                $required[] = printf(t('%1$s Version %2$s'), $requirement, $versionInfo);
            }
        }

        if (!empty($required)) {
            $requiredString .=
                '<span class="media-meta requirements">' . t("Requires: ") . implode(", ", $required) . "</span>";
        }
        $media->addMetaIf($requiredString != "", $requiredString);
        return $media;
    }

    /**
     * Mobile Themes management screen.
     *
     * @since 2.2.10.3
     * @access public
     * @param string $ThemeName Unique ID.
     * @param string $TransientKey Security token.
     */
    public function mobileThemes($ThemeName = "", $TransientKey = "")
    {
        $IsMobile = true;

        $this->addJsFile("addons.js");
        $this->addJsFile("addons.js");
        $this->setData("Title", t("Mobile Themes"));

        $this->permission("Garden.Settings.Manage");
        $this->setHighlightRoute("dashboard/settings/themes");

        // Get currently enabled theme.
        $themeKey = Gdn::themeManager()->getEnabledMobileThemeKey();
        $ThemeInfo = Gdn::themeManager()->getThemeInfo($themeKey);

        // Check to see if the resolved theme is the same as the one set in config. If not, we couldn't
        // find the theme and are using the default theme instead. Show an error.
        $enabledThemeKey = c("Garden.MobileTheme", Gdn_ThemeManager::DEFAULT_MOBILE_THEME);
        if ($ThemeName === "" && $themeKey !== $enabledThemeKey) {
            $message = t("The theme with key %s could not be found and will not be started.");
            $this->Form->addError(sprintf($message, $enabledThemeKey));
        }

        $this->setData("EnabledThemeInfo", $ThemeInfo);
        $this->setData("EnabledThemeFolder", val("Folder", $ThemeInfo));
        $this->setData("EnabledTheme", $ThemeInfo);
        $this->setData("EnabledThemeScreenshotUrl", val("ScreenshotUrl", $ThemeInfo));
        $this->setData("EnabledThemeName", val("Name", $ThemeInfo, val("Index", $ThemeInfo)));

        // Get all themes.
        $Themes = Gdn::themeManager()->availableThemes();

        // Filter themes.
        foreach ($Themes as $ThemeKey => $ThemeData) {
            $isMobile = $ThemeData["IsMobile"] ?? false;
            $isArchived = $ThemeData["Archived"] ?? false;
            $isResponsive = $ThemeData["IsResponsive"] ?? false;

            // Only show mobile themes.
            if (!$isMobile && !$isResponsive) {
                unset($Themes[$ThemeKey]);
            }

            // Remove themes that are archived
            if ($isArchived) {
                unset($Themes[$ThemeKey]);
            }
        }

        uasort($Themes, ["SettingsController", "_NameSort"]);
        $this->setData("AvailableThemes", $Themes);

        // Process self-post.
        if ($ThemeName != "" && Gdn::session()->validateTransientKey($TransientKey)) {
            try {
                $ThemeInfo = Gdn::themeManager()->getThemeInfo($ThemeName);
                if ($ThemeInfo === false) {
                    throw new Exception(sprintf(t("Could not find a theme identified by '%s'"), $ThemeName));
                }

                Gdn::session()->setPreference(["PreviewMobileThemeName" => "", "PreviewMobileThemeFolder" => ""]); // Clear out the preview
                Gdn::themeManager()->enableTheme($ThemeName, $IsMobile);
                $this->EventArguments["ThemeName"] = $ThemeName;
                $this->EventArguments["ThemeInfo"] = $ThemeInfo;
                $this->fireEvent("AfterEnableTheme");
            } catch (Exception $Ex) {
                $this->Form->addError($Ex);
            }

            $AsyncRequest = $this->deliveryType() === DELIVERY_TYPE_VIEW ? true : false;

            if ($this->Form->errorCount() == 0) {
                if ($AsyncRequest) {
                    echo "Success";
                    $this->render("Blank", "Utility", "Dashboard");
                    exit();
                } else {
                    redirectTo("/settings/mobilethemes");
                }
            } else {
                if ($AsyncRequest) {
                    echo $this->Form->errorString();
                    $this->render("Blank", "Utility", "Dashboard");
                    exit();
                }
            }
        }

        $this->render();
    }

    /**
     * Finds the bans rules affecting a given user. Valid arguments include either the user ID or the username.
     *
     * @param int|string $userIdentifier Either the username or user ID.
     */
    private function findBanRule($userIdentifier)
    {
        $userModel = new UserModel();

        if (is_numeric($userIdentifier)) {
            $user = $userModel->getID($userIdentifier);
        } else {
            $user = $userModel->getByUsername($userIdentifier);
        }

        if ($user === false) {
            $this->setData("Title", sprintf(t("Ban rules matching %s"), htmlspecialchars($userIdentifier)));
            $emptyMessageTitle = sprintf(t("User does not exist"));
            $this->setData("EmptyMessageTitle", $emptyMessageTitle);
            $emptyMessageBody = sprintf(t("Cannot find the user identified by %s."), htmlspecialchars($userIdentifier));
            $this->setData("EmptyMessageBody", $emptyMessageBody);
            return;
        }

        $matchingBans = [];

        if ($user) {
            $userID = val("UserID", $user);
            $userIPs = $userModel->getIPs($userID);

            // Check auto bans
            $banRules = BanModel::allBans();
            foreach ($banRules as $banRule) {
                // Convert ban to regex.
                $parts = explode("*", str_replace("%", "*", $banRule["BanValue"]));
                $parts = array_map("preg_quote", $parts);
                $regex = "`^" . implode(".*", $parts) . '$`i';

                switch ($banRule["BanType"]) {
                    case "IPAddress":
                        foreach ($userIPs as $ip) {
                            if (preg_match($regex, $ip)) {
                                $matchingBans[] = $banRule;
                            }
                        }
                        break;
                    case "Email":
                    case "Name":
                        if (preg_match($regex, val($banRule["BanType"], $user))) {
                            $matchingBans[] = $banRule;
                        }
                }
            }
        }

        // Join ban's insert username.
        foreach ($matchingBans as &$banRule) {
            $banRule["InsertName"] = val("Name", $userModel->getID(val("InsertUserID", $banRule)));
        }

        $name = val("Name", $user);
        $this->setHighlightRoute("dashboard/settings/bans");
        $this->setData("Title", sprintf(t("Ban rules matching %s"), htmlspecialchars($name)));
        $this->setData("Bans", $matchingBans);
        $emptyMessage = sprintf(t("There are no existing ban rules affecting user %s."), htmlspecialchars($name));
        $this->setData("EmptyMessageBody", $emptyMessage);
    }

    protected static function _nameSort($a, $b)
    {
        return strcasecmp(val("Name", $a), val("Name", $b));
    }

    /**
     * Show a preview of a theme.
     *
     * @since 2.0.0
     * @access public
     * @param string $themeName Unique ID.
     * @param string $transientKey
     */
    public function previewTheme($themeName = "", $transientKey = "")
    {
        $this->permission("Garden.Settings.Manage");

        if (Gdn::session()->validateTransientKey($transientKey)) {
            /** @var ThemeServiceHelper $themeHelper */
            $themeHelper = Gdn::getContainer()->get(ThemeServiceHelper::class);
            /** @var FsThemeProvider $themeProvider */
            $themeProvider = Gdn::getContainer()->get(FsThemeProvider::class);
            $theme = $themeProvider->getTheme($themeName);
            $themeHelper->setSessionPreviewTheme($theme);

            redirectTo("/");
        } else {
            redirectTo("settings/themes");
        }
    }

    /**
     * Closes theme preview.
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $previewThemeFolder
     * @param string $transientKey
     */
    public function cancelPreview($previewThemeFolder = "", $transientKey = "")
    {
        $this->permission("Garden.Settings.Manage");
        $isMobile = false;

        if (Gdn::session()->validateTransientKey($transientKey)) {
            /** @var ThemeServiceHelper $themeHelper */
            $themeHelper = Gdn::getContainer()->get(ThemeServiceHelper::class);
            $themeHelper->cancelSessionPreviewTheme();
        }

        if ($isMobile) {
            redirectTo("settings/mobilethemes");
        } else {
            redirectTo("settings/themes");
        }
    }

    /**
     * Remove the default avatar from config & delete it.
     *
     * @since 2.0.0
     * @access public
     */
    public function removeDefaultAvatar()
    {
        if (
            Gdn::request()->isAuthenticatedPostBack(true) &&
            Gdn::session()->checkPermission("Garden.Community.Manage")
        ) {
            $avatar = c("Garden.DefaultAvatar", "");
            $this->deleteDefaultAvatars($avatar);
            removeFromConfig("Garden.DefaultAvatar");
            $this->informMessage(sprintf(t("%s deleted."), t("Avatar")));
        }
        $this->render("blank", "utility", "dashboard");
    }

    /**
     * Deletes uploaded default avatars.
     *
     * @param string $avatar The avatar to delete.
     */
    private function deleteDefaultAvatars($avatar = "")
    {
        if ($avatar && $this->isUploadedDefaultAvatar($avatar)) {
            $upload = new Gdn_Upload();
            $upload->delete(self::DEFAULT_AVATAR_FOLDER . "/" . basename($avatar));
            $upload->delete(self::DEFAULT_AVATAR_FOLDER . "/" . basename(changeBasename($avatar, "p%s")));
            $upload->delete(self::DEFAULT_AVATAR_FOLDER . "/" . basename(changeBasename($avatar, "n%s")));
        }
    }

    /**
     * Prompts new admins how to get started using new install.
     *
     * @since 2.0.0
     * @access public
     */
    public function gettingStarted()
    {
        $this->permission("Garden.Settings.Manage");

        $this->setData("Title", t("Getting Started"));
        $this->setHighlightRoute("dashboard/settings/gettingstarted");

        Gdn_Theme::section("Tutorials");
        $this->setData("IsWidePage", true);
        $this->render();
    }

    /**
     *
     *
     * @param string $tutorial
     */
    public function tutorials($tutorial = "")
    {
        $this->permission("Garden.Settings.Manage");
        $this->setData("Title", t("Help &amp; Tutorials"));
        $this->setHighlightRoute("dashboard/settings/tutorials");
        $this->setData("CurrentTutorial", $tutorial);
        Gdn_Theme::section("Tutorials");
        $this->setData("IsWidePage", true);
        $this->render();
    }

    /**
     * Can we attempt to verify the addon cache's integrity?
     *
     * @return bool
     */
    private function verifyAddonCache()
    {
        return !c("Cache.Addons.DisableEndpoints");
    }
}
