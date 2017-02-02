<?php
/**
 * Managing core Dashboard settings.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */
use Vanilla\Addon;

/**
 * Handles /settings endpoint.
 */
class SettingsController extends DashboardController {

    const DEFAULT_AVATAR_FOLDER = 'defaultavatar';

    /** @var array Models to automatically instantiate. */
    public $Uses = array('Form', 'Database');

    /** @var string */
    public $ModuleSortContainer = 'Dashboard';

    /** @var Gdn_Form */
    public $Form;

    /** @var array List of permissions that should all have access to main dashboard. */
    public $RequiredAdminPermissions = array();

    /** @var BanModel The ban model. */
    private $_BanModel;

    /**
     * Highlight menu path. Automatically run on every use.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        parent::initialize();
        Gdn_Theme::section('Dashboard');
        if ($this->Menu) {
            $this->Menu->highlightRoute('/dashboard/settings');
        }
    }

    /**
     * Handle the tracking of a page tick.
     */
    public function analyticsTick() {
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->deliveryType(DELIVERY_TYPE_DATA);

        Gdn::statistics()->tick();
        Gdn::statistics()->fireEvent("AnalyticsTick");
        $this->render();
    }

    /**
     * Application management screen.
     *
     * @since 2.0.0
     * @access public
     * @param string $Filter 'enabled', 'disabled', or 'all' (default)
     * @param string $ApplicationName Unique ID of app to be modified.
     * @param string $TransientKey Security token.
     */
    public function applications($Filter = '', $ApplicationName = '') {
        $this->permission('Garden.Settings.Manage');

        // Page setup
        $this->addJsFile('addons.js');
        $this->addJsFile('applications.js');
        $this->title(t('Applications'));
        $this->setHighlightRoute('dashboard/settings/applications');

        if (!in_array($Filter, array('enabled', 'disabled'))) {
            $Filter = 'all';
        }
        $this->Filter = $Filter;

        $ApplicationManager = Gdn::applicationManager();
        $this->AvailableApplications = $ApplicationManager->availableVisibleApplications();
        $this->EnabledApplications = $ApplicationManager->enabledVisibleApplications();

        if ($ApplicationName != '') {
            $addon = Gdn::addonManager()->lookupAddon($ApplicationName);
            if (!$addon) {
                throw notFoundException('Application');
            }
            if (Gdn::addonManager()->isEnabled($ApplicationName, Addon::TYPE_ADDON)) {
                $this->disableApplication($ApplicationName, $Filter);
            } else {
                $this->enableApplication($ApplicationName, $Filter);
            }
        } else {
            $this->render();
        }
    }

    public function disableApplication($addonName, $filter) {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }

        $this->permission('Garden.Settings.Manage');
        $applicationManager = Gdn::applicationManager();

        $action = 'none';
        if ($filter == 'enabled') {
            $action = 'SlideUp';
        }

        $addon = Gdn::addonManager()->lookupAddon($addonName);
        try {
            $applicationManager->disableApplication($addonName);
            $this->informMessage(sprintf(t('%s Disabled.'), val('name', $addon->getInfo(), t('Application'))));
        } catch (Exception $e) {
            $this->Form->addError(strip_tags($e->getMessage()));
        }

        $this->handleAddonToggle($addonName, $addon->getInfo(), 'applications', false, $filter, $action);
    }

    public function enableApplication($addonName, $filter) {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }

        $this->permission('Garden.Settings.Manage');
        $applicationManager = Gdn::applicationManager();

        $action = 'none';
        if ($filter == 'disabled') {
            $action = 'SlideUp';
        }

        $addon = Gdn::addonManager()->lookupAddon($addonName);

        try {
            $applicationManager->checkRequirements($addonName);
            $this->informMessage(sprintf(t('%s Enabled.'), val('name', $addon->getInfo(), t('Application'))));
        } catch (Exception $e) {
            $this->Form->addError(strip_tags($e->getMessage()));
        }
        if ($this->Form->errorCount() == 0) {
            $validation = new Gdn_Validation();
            $applicationManager->registerPermissions($addonName, $validation);
            $applicationManager->enableApplication($addonName, $validation);
            $this->Form->setValidationResults($validation->results());
        }

        $this->handleAddonToggle($addonName, $addon->getInfo(), 'applications', true, $filter, $action);
    }

    private function handleAddonToggle($addonName, $addonInfo, $type, $isEnabled, $filter = '', $action = '') {
        require_once($this->fetchViewLocation('helper_functions'));

        if ($this->Form->errorCount() > 0) {
            $this->informMessage($this->Form->errors());
        } else {
            if ($action === 'SlideUp') {
                $this->jsonTarget('#'.Gdn_Format::url($addonName).'-addon', '', 'SlideUp');
            } else {
                ob_start();
                writeAddonMedia($addonName, $addonInfo, $isEnabled, $type, $filter);
                $row = ob_get_clean();
                $this->jsonTarget('#'.Gdn_Format::url($addonName).'-addon', $row, 'ReplaceWith');
            }
        }

        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * Gets the ban model and instantiates it if it doesn't exist.
     *
     * @return BanModel
     */
    public function getBanModel() {
        if ($this->_BanModel === null) {
            $BanModel = new BanModel();
            $this->_BanModel = $BanModel;
        }
        return $this->_BanModel;
    }

    /**
     * Application management screen.
     *
     * @since 2.0.0
     * @access protected
     * @param array $Ban Data about the ban.
     *    Valid keys are BanType and BanValue. BanValue is what is to be banned.
     *    Valid values for BanType are email, ipaddress or name.
     */
    protected function _banFilter($Ban) {
        $BanModel = $this->getBanModel();
        $BanWhere = $BanModel->banWhere($Ban);
        foreach ($BanWhere as $Name => $Value) {
            if (!in_array($Name, array('u.Admin', 'u.Deleted'))) {
                return "$Name $Value";
            }
        }
    }

    /**
     * Settings page for managing avatar settings.
     *
     * Displays the current avatar and exposes the following config settings:
     * Garden.Thumbnail.Size
     * Garden.Profile.MaxWidth
     * Garden.Profile.MaxHeight
     */
    public function avatars() {
        $this->permission('Garden.Community.Manage');
        $this->setHighlightRoute('dashboard/settings/avatars');
        $this->addJsFile('avatars.js');
        $this->title(t('Avatars'));

        $validation = new Gdn_Validation();
        $validation->applyRule('Garden.Thumbnail.Size', 'Integer', t('Thumbnail size must be an integer.'));
        $validation->applyRule('Garden.Profile.MaxWidth', 'Integer', t('Max avatar width must be an integer.'));
        $validation->applyRule('Garden.Profile.MaxHeight', 'Integer', t('Max avatar height must be an integer.'));

        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(array(
            'Garden.Thumbnail.Size',
            'Garden.Profile.MaxWidth',
            'Garden.Profile.MaxHeight'
        ));
        $this->Form->setModel($configurationModel);
        $this->setData('avatar', UserModel::getDefaultAvatarUrl());

        $this->fireEvent('AvatarSettings');

        if (!$this->Form->authenticatedPostBack()) {
            $this->Form->setData($configurationModel->Data);
        } else {
            if ($this->Form->save() !== false) {
                $this->informMessage(t("Your settings have been saved."));
            }
        }
        $this->render();
    }

    /**
     * Handles the setting of the Garden.Profile.EditPhotos config and updates the edit photos toggle.
     *
     * @param $allow Expects either 'true' or 'false'.
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function allowEditPhotos($allow) {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        if (!Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            throw new Exception('You don\'t have permisison to do that.', 401);
        }

        $allow = strtolower($allow);
        saveToConfig('Garden.Profile.EditPhotos', $allow === 'true');
        if ($allow === 'true') {
            $newToggle = wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/alloweditphotos/false', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-on"]);
            $this->informMessage(t('Editing photos allowed.'));
        } else {
            $newToggle = wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/alloweditphotos/true', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-off"]);
            $this->informMessage(t('Editing photos not allowed.'));
        }
        $this->jsonTarget("#editphotos-toggle", $newToggle);

        $this->render('Blank', 'Utility');
    }

    /**
     * Test whether a path is a relative path to the proper uploads directory.
     *
     * @param string $avatar The path to the avatar image to test (most often Garden.DefaultAvatar)
     * @return bool Whether the avatar has been uploaded from the dashboard.
     */
    public function isUploadedDefaultAvatar($avatar) {
        return (strpos($avatar, self::DEFAULT_AVATAR_FOLDER.'/') !== false);
    }

    /**
     * Settings page for uploading, deleting and cropping the default avatar.
     *
     * @throws Exception
     */
    public function defaultAvatar() {
        $this->permission('Garden.Community.Manage');
        $this->setHighlightRoute('dashboard/settings/avatars');
        $this->title(t('Default Avatar'));
        $this->addJsFile('avatars.js');

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $this->Form->setModel($configurationModel);

        if (($avatar = c('Garden.DefaultAvatar')) && $this->isUploadedDefaultAvatar($avatar)) {
            //Get the image source so we can manipulate it in the crop module.
            $upload = new Gdn_UploadImage();
            $thumbnailSize = c('Garden.Thumbnail.Size');
            $basename = changeBasename($avatar, "p%s");
            $source = $upload->copyLocal($basename);

            //Set up cropping.
            $crop = new CropImageModule($this, $this->Form, $thumbnailSize, $thumbnailSize, $source);
            $crop->saveButton = false;
            $crop->setExistingCropUrl(Gdn_UploadImage::url(changeBasename($avatar, "n%s")));
            $crop->setSourceImageUrl(Gdn_UploadImage::url(changeBasename($avatar, "p%s")));
            $this->setData('crop', $crop);
        } else {
            $this->setData('avatar', UserModel::getDefaultAvatarUrl());
        }

        if (!$this->Form->authenticatedPostBack()) {
            $this->Form->setData($configurationModel->Data);
        } else if ($this->Form->save() !== false) {
            $upload = new Gdn_UploadImage();
            $newAvatar = false;
            $newUpload = false;
            if ($tmpAvatar = $upload->validateUpload('DefaultAvatar', false)) {
                // New upload
                $newUpload = true;
                $thumbOptions = array('Crop' => true, 'SaveGif' => c('Garden.Thumbnail.SaveGif'));
                $newAvatar = $this->saveDefaultAvatars($tmpAvatar, $thumbOptions);
            } else if ($avatar && $crop && $crop->isCropped()) {
                // New thumbnail
                $tmpAvatar = $source;
                $thumbOptions = array('Crop' => true,
                    'SourceX' => $crop->getCropXValue(),
                    'SourceY' => $crop->getCropYValue(),
                    'SourceWidth' => $crop->getCropWidth(),
                    'SourceHeight' => $crop->getCropHeight());
                $newAvatar = $this->saveDefaultAvatars($tmpAvatar, $thumbOptions);
            }
            if ($this->Form->errorCount() == 0) {
                if ($newAvatar) {
                    $this->deleteDefaultAvatars($avatar);
                    $avatar = c('Garden.DefaultAvatar');
                    $thumbnailSize = c('Garden.Thumbnail.Size');

                    // Update crop properties.
                    $basename = changeBasename($avatar, "p%s");
                    $source = $upload->copyLocal($basename);
                    $crop = new CropImageModule($this, $this->Form, $thumbnailSize, $thumbnailSize, $source);
                    $crop->saveButton = false;
                    $crop->setSize($thumbnailSize, $thumbnailSize);
                    $crop->setExistingCropUrl(Gdn_UploadImage::url(changeBasename($avatar, "n%s")));
                    $crop->setSourceImageUrl(Gdn_UploadImage::url(changeBasename($avatar, "p%s")));
                    $this->setData('crop', $crop);

                    // New uploads stay on the page to allow cropping. Otherwise, redirect to avatar settings page.
                    if (!$newUpload) {
                        redirect('/dashboard/settings/avatars');
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
     * @return bool Whether the saves were successful.
     */
    private function saveDefaultAvatars($source, $thumbOptions) {
        try {
            $upload = new Gdn_UploadImage();
            // Generate the target image name
            $targetImage = $upload->generateTargetName(PATH_UPLOADS);
            $imageBaseName = pathinfo($targetImage, PATHINFO_BASENAME);

            // Save the full size image.
            $parts = Gdn_UploadImage::saveImageAs(
                $source,
                self::DEFAULT_AVATAR_FOLDER.'/'.$imageBaseName
            );

            // Save the profile size image.
            Gdn_UploadImage::saveImageAs(
                $source,
                self::DEFAULT_AVATAR_FOLDER."/p$imageBaseName",
                c('Garden.Profile.MaxHeight'),
                c('Garden.Profile.MaxWidth'),
                array('SaveGif' => c('Garden.Thumbnail.SaveGif'))
            );

            $thumbnailSize = c('Garden.Thumbnail.Size');
            // Save the thumbnail size image.
            Gdn_UploadImage::saveImageAs(
                $source,
                self::DEFAULT_AVATAR_FOLDER."/n$imageBaseName",
                $thumbnailSize,
                $thumbnailSize,
                $thumbOptions
            );
        } catch (Exception $ex) {
            $this->Form->addError($ex);
            return false;
        }

        $imageBaseName = $parts['SaveName'];
        saveToConfig('Garden.DefaultAvatar', $imageBaseName);
        return true;
    }

    /**
     * Banner management screen.
     *
     * @since 2.0.0
     * @access public
     */
    public function banner() {
        $this->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);
        $this->setHighlightRoute('dashboard/settings/banner');
        $this->title(t('Banner'));
        $configurationModule = new ConfigurationModule($this);
        $configurationModule->initialize([
            'Garden.HomepageTitle' => [
                'LabelCode' => t('Homepage Title'),
                'Control' => 'textbox',
                'Description' => t('The homepage title is displayed on your home page.', 'The homepage title is displayed on your home page. Pick a title that you would want to see appear in search engines.')
            ],
            'Garden.Description' => [
                'LabelCode' => t('Site Description'),
                'Control' => 'textbox',
                'Description' => t("The site description usually appears in search engines.", 'The site description usually appears in search engines. You should try having a description that is 100–150 characters long.'),
                'Options' => [
                    'Multiline' => true,
                ]
            ],
            'Garden.Title' => [
                'LabelCode' => t('Banner Title'),
                'Control' => 'textbox',
                'Description' => t("The banner title appears on your site's banner and in your browser's title bar.",
                    "The banner title appears on your site's banner and in your browser's title bar. It should be less than 20 characters. If a banner logo is uploaded, it will replace the banner title on user-facing forum pages. Also, keep in mind some themes may hide this title.")

            ],
            'Garden.Logo' => [
                'LabelCode' => t('Banner Logo'),
                'Control' => 'imageupload',
                'Description' => t('LogoDescription', 'The banner logo appears at the top of your site. Some themes may not display this logo.'),
                'Options' => [
                    'RemoveConfirmText' => sprintf(t('Are you sure you want to delete your %s?'), t('banner logo'))
                ]
            ],
            'Garden.MobileLogo' => [
                'LabelCode' => t('Mobile Banner Logo'),
                'Control' => 'imageupload',
                'Description' => t('MobileLogoDescription', 'The mobile banner logo appears at the top of your site. Some themes may not display this logo.'),
                'Options' => [
                    'RemoveConfirmText' => sprintf(t('Are you sure you want to delete your %s?'), t('mobile banner logo'))
                ]
            ],
            'Garden.FavIcon' => [
                'LabelCode' => t('Favicon'),
                'Control' => 'imageupload',
                'Size' => '16x16',
                'OutputType' => 'ico',
                'Prefix' => 'favicon_',
                'Crop' => true,
                'Description' => t('FaviconDescription', "Your site's favicon appears in your browser's title bar. It will be scaled to 16x16 pixels."),
                'Options' => [
                    'RemoveConfirmText' => sprintf(t('Are you sure you want to delete your %s?'), t('favicon'))
                ]
            ],
            'Garden.ShareImage' => [
                'LabelCode' => t('Share Image'),
                'Control' => 'imageupload',
                'Description' => t('ShareImageDescription', "When someone shares a link from your site we try and grab an image from the page. If there isn't an image on the page then we'll use this image instead. The image should be at least 50&times;50, but we recommend 200&times;200."),
                'Options' => [
                    'RemoveConfirmText' => sprintf(t('Are you sure you want to delete your %s?'), t('share image'))
                ]
            ]
        ]);
        $this->setData('ConfigurationModule', $configurationModule);
        $this->render();
    }

    /**
     * Manage user bans (add, edit, delete, list).
     *
     * @since 2.0.18
     * @access public
     * @param string $Action Add, edit, delete, or none.
     * @param string $Search Term to filter ban list by.
     * @param int $Page Page number.
     * @param int $ID Ban ID we're editing or deleting.
     */
    public function bans($Action = '', $Search = '', $Page = '', $ID = '') {
        $this->permission('Garden.Settings.Manage');

        // Page setup
        $this->title(t('Banning Options'));

        list($Offset, $Limit) = offsetLimit($Page, 20);

        $BanModel = $this->getBanModel();

        switch (strtolower($Action)) {
            case 'add':
            case 'edit':
                $this->Form->setModel($BanModel);

                if ($this->Form->authenticatedPostBack()) {
                    if ($ID) {
                        $this->Form->setFormValue('BanID', $ID);
                    }

                    // Trim the ban value to avoid obvious mismatches.
                    $banValue = trim($this->Form->getFormValue('BanValue'));
                    $this->Form->setFormValue('BanValue', $banValue);

                    // We won't let you HAL 9000 the entire crew.
                    $crazyBans = ['*', '*@*', '*.*', '*.*.*', '*.*.*.*'];
                    if (in_array($banValue, $crazyBans)) {
                        $this->Form->addError("I'm sorry Dave, I'm afraid I can't do that.");
                    }

                    try {
                        // Save the ban.
                        $NewID = $this->Form->save();
                    } catch (Exception $Ex) {
                        $this->Form->addError($Ex);
                    }
                } else {
                    if ($ID) {
                        $this->Form->setData($BanModel->getID($ID));
                    }
                }
                $this->setData('_BanTypes', array('IPAddress' => t('IP Address'), 'Email' => t('Email'), 'Name' => t('Name')));
                $this->View = 'Ban';
                break;
            case 'delete':
                if ($this->Form->authenticatedPostBack()) {
                    $BanModel->delete(array('BanID' => $ID));
                    $this->View = 'BanDelete';
                }
                break;
            default:
                $Bans = $BanModel->getWhere(array(), 'BanType, BanValue', 'asc', $Limit, $Offset)->resultArray();
                $this->setData('Bans', $Bans);
                break;
        }

        Gdn_Theme::section('Moderation');
        $this->render();
    }

    /**
     * Homepage management screen.
     *
     * @since 2.0.0
     * @access public
     */
    public function homepage() {
        $this->permission('Garden.Settings.Manage');

        // Page setup
        $this->setHighlightRoute('dashboard/settings/homepage');
        $this->title(t('Homepage'));

        $CurrentRoute = val('Destination', Gdn::router()->getRoute('DefaultController'), '');
        $this->setData('CurrentTarget', $CurrentRoute);
        if (!$this->Form->authenticatedPostBack()) {
            $this->Form->setData(array(
                'Target' => $CurrentRoute
            ));
        } else {
            $NewRoute = val('Target', $this->Form->formValues(), '');
            Gdn::router()->deleteRoute('DefaultController');
            Gdn::router()->setRoute('DefaultController', $NewRoute, 'Internal');
            $this->setData('CurrentTarget', $NewRoute);

            // Save the preferred layout setting
            saveToConfig(array(
                'Vanilla.Discussions.Layout' => val('DiscussionsLayout', $this->Form->formValues(), ''),
                'Vanilla.Categories.Layout' => val('CategoriesLayout', $this->Form->formValues(), '')
            ));

            $this->informMessage(t("Your changes were saved successfully."));
        }

        // Add warnings for layouts that have been specified by the theme.
        $themeManager = Gdn::themeManager();
        $theme = $themeManager->enabledThemeInfo();
        $layout = val('Layout', $theme);

        $warningText = t('Your theme has specified the layout selected below. Changing the layout may make your theme look broken.');
        $warningAlert = wrap($warningText, 'div', ['class' => 'alert alert-warning padded']);
        $dangerText = t('Your theme recommends the %s layout, but you\'ve selected the %s layout. This may make your theme look broken.');
        $dangerAlert = wrap($dangerText, 'div', ['class' => 'alert alert-danger padded']);

        if (val('Discussions', $layout)) {
            $dicussionsLayout = strtolower(val('Discussions', $layout));
            if ($dicussionsLayout != c('Vanilla.Discussions.Layout')) {
                $discussionsAlert = sprintf($dangerAlert, $dicussionsLayout, c('Vanilla.Discussions.Layout'));
            } else {
                $discussionsAlert = $warningAlert;
            }
            $this->setData('DiscussionsAlert', $discussionsAlert);
        }

        if (val('Categories', $layout)) {
            $categoriesLayout = strtolower(val('Categories', $layout));
            if ($categoriesLayout != c('Vanilla.Categories.Layout')) {
                $categoriesAlert = sprintf($dangerAlert, $categoriesLayout, c('Vanilla.Categories.Layout'));
            } else {
                $categoriesAlert = $warningAlert;
            }
            $this->setData('CategoriesAlert', $categoriesAlert);
        }

        $this->render();
    }

    /**
     *
     *
     * @throws Exception
     */
    public function configuration() {
        $this->permission('Garden.Settings.Manage');
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->deliveryType(DELIVERY_TYPE_DATA);

        $ConfigData = array(
            'Title' => c('Garden.Title'),
            'Domain' => c('Garden.Domain'),
            'Cookie' => c('Garden.Cookie'),
            'Theme' => c('Garden.Theme'),
            'Analytics' => array(
                'InstallationID' => c('Garden.InstallationID'),
                'InstallationSecret' => c('Garden.InstallationSecret')
            )
        );

        $Config = Gdn_Configuration::format($ConfigData, array(
            'FormatStyle' => 'Dotted',
            'WrapPHP' => false,
            'SafePHP' => false,
            'Headings' => false,
            'ByLine' => false,
        ));

        $Configuration = array();
        eval($Config);

        $this->setData('Configuration', $Configuration);

        $this->render();
    }

    /**
     * Outgoing Email management screen.
     *
     * @since 2.0.0
     * @access public
     */
    public function email() {
        $this->permission('Garden.Settings.Manage');
        $this->setHighlightRoute('dashboard/settings/email');
        $this->addJsFile('email.js');
        $this->title(t('Outgoing Email'));

        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->setField(array(
            'Garden.Email.SupportName',
            'Garden.Email.SupportAddress',
            'Garden.Email.UseSmtp',
            'Garden.Email.SmtpHost',
            'Garden.Email.SmtpUser',
            'Garden.Email.SmtpPassword',
            'Garden.Email.SmtpPort',
            'Garden.Email.SmtpSecurity',
            'Garden.Email.OmitToName'
        ));

        // Set the model on the form.
        $this->Form->setModel($ConfigurationModel);

        // If seeing the form for the first time...
        if ($this->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $this->Form->setData($ConfigurationModel->Data);
        } else {
            // Define some validation rules for the fields being saved
            $ConfigurationModel->Validation->applyRule('Garden.Email.SupportName', 'Required');
            $ConfigurationModel->Validation->applyRule('Garden.Email.SupportAddress', 'Required');
            $ConfigurationModel->Validation->applyRule('Garden.Email.SupportAddress', 'Email');

            if ($this->Form->save() !== false) {
                $this->informMessage(t("Your settings have been saved."));
            }
        }

        $this->render();
    }

    /**
     * Settings page for HTML email styling.
     *
     * Exposes config settings:
     * Garden.EmailTemplate.BackgroundColor
     * Garden.EmailTemplate.ButtonBackgroundColor
     * Garden.EmailTemplate.ButtonTextColor
     * Garden.EmailTemplate.Image
     *
     * Saves the image based on 2 config settings:
     * Garden.EmailTemplate.ImageMaxWidth (default 400px) and
     * Garden.EmailTemplate.ImageMaxHeight (default 300px)
     *
     * @throws Gdn_UserException
     */
    public function emailStyles() {
        // Set default colors
        if (!c('Garden.EmailTemplate.TextColor')) {
            saveToConfig('Garden.EmailTemplate.TextColor', EmailTemplate::DEFAULT_TEXT_COLOR, false);
        }
        if (!c('Garden.EmailTemplate.BackgroundColor')) {
            saveToConfig('Garden.EmailTemplate.BackgroundColor', EmailTemplate::DEFAULT_BACKGROUND_COLOR, false);
        }
        if (!c('Garden.EmailTemplate.ContainerBackgroundColor')) {
            saveToConfig('Garden.EmailTemplate.ContainerBackgroundColor', EmailTemplate::DEFAULT_CONTAINER_BACKGROUND_COLOR, false);
        }
        if (!c('Garden.EmailTemplate.ButtonTextColor')) {
            saveToConfig('Garden.EmailTemplate.ButtonTextColor', EmailTemplate::DEFAULT_BUTTON_TEXT_COLOR, false);
        }
        if (!c('Garden.EmailTemplate.ButtonBackgroundColor')) {
            saveToConfig('Garden.EmailTemplate.ButtonBackgroundColor', EmailTemplate::DEFAULT_BUTTON_BACKGROUND_COLOR, false);
        }

        $this->permission('Garden.Settings.Manage');
        $this->addJsFile('email.js');

        $configurationModule = new ConfigurationModule($this);
        $configurationModule->initialize([
            'Garden.EmailTemplate.Image' => [
                'Control' => 'imageupload',
                'LabelCode' => 'Email Logo',
                'Size' => c('Garden.EmailTemplate.ImageMaxWidth', '400').'x'.c('Garden.EmailTemplate.ImageMaxHeight', '300'),
                'Description' => sprintf(t('Large images will be scaled down.'),
                    c('Garden.EmailTemplate.ImageMaxWidth', 400),
                    c('Garden.EmailTemplate.ImageMaxHeight', 300)),
                'Options' => [
                    'RemoveConfirmText' => sprintf(t('Are you sure you want to delete your %s?'), t('email logo'))
                ]
            ],
            'Garden.EmailTemplate.TextColor' => [
                'Control' => 'color'
            ],
            'Garden.EmailTemplate.BackgroundColor' => [
                'Control' => 'color'
            ],
            'Garden.EmailTemplate.ContainerBackgroundColor' => [
                'Control' => 'color',
                'LabelCode' => 'Page Color'
            ],
            'Garden.EmailTemplate.ButtonTextColor' => [
                'Control' => 'color'
            ],
            'Garden.EmailTemplate.ButtonBackgroundColor' => [
                'Control' => 'color'
            ],
        ]);

        $previewButton = wrap(t('Preview'), 'span', array('class' => 'js-email-preview-button btn btn-secondary'));
        $configurationModule->controller()->setData('FormFooter', ['FormFooter' => $previewButton]);

        $this->setData('ConfigurationModule', $configurationModule);
        $this->render();
    }

    /**
     * Sets up a new Gdn_Email object with a test email.
     *
     * @param string $image The img src of the previewed image
     * @param string $textColor The hex color code of the text.
     * @param string $backGroundColor The hex color code of the background color.
     * @param string $containerBackgroundColor The hex color code of the container background color.
     * @param string $buttonTextColor The hex color code of the link color.
     * @param string $buttonBackgroundColor The hex color code of the button background.
     * @return Gdn_Email The email object with the test colors set.
     */
    public function getTestEmail($image = '', $textColor = '', $backGroundColor = '', $containerBackgroundColor = '', $buttonTextColor = '', $buttonBackgroundColor = '') {
        $emailer = new Gdn_Email();
        $email = $emailer->getEmailTemplate();

        if ($image) {
            $email->setImage($image);
        }
        if ($textColor) {
            $email->setTextColor($textColor);
        }
        if ($backGroundColor) {
            $email->setBackgroundColor($backGroundColor);
        }
        if ($backGroundColor) {
            $email->setContainerBackgroundColor($containerBackgroundColor);
        }
        if ($buttonTextColor) {
            $email->setDefaultButtonTextColor($buttonTextColor);
        }
        if ($buttonBackgroundColor) {
            $email->setDefaultButtonBackgroundColor($buttonBackgroundColor);
        }
        $message = t('Test Email Message');

        $email->setMessage($message)
            ->setTitle(t('Test Email'))
            ->setButton(externalUrl('/'), t('Check it out'));
        $emailer->setEmailTemplate($email);
        return $emailer;
    }

    /**
     * Echoes out a test email with the colors and image in the post request.
     *
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function emailPreview() {
        $request = Gdn::request();
        $image = $request->post('image', '');
        $textColor = $request->post('textColor', '');
        $backGroundColor = $request->post('backgroundColor', '');
        $containerBackGroundColor = $request->post('containerBackgroundColor', '');
        $buttonTextColor = $request->post('buttonTextColor', '');
        $buttonBackgroundColor = $request->post('buttonBackgroundColor', '');

        echo $this->getTestEmail($image, $textColor, $backGroundColor, $containerBackGroundColor, $buttonTextColor, $buttonBackgroundColor)->getEmailTemplate()->toString();
    }

    /**
     * Form for sending a test email.
     * On postback, sends a test email to the addresses specified in the form.
     *
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function emailTest() {
        if (!Gdn::session()->checkPermission('Garden.Community.Manage')) {
            throw permissionException();
        }
        $this->setHighlightRoute('dashboard/settings/email');
        $this->Form = new Gdn_Form();
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $this->Form->setModel($configurationModel);
        if ($this->Form->authenticatedPostBack() !== false) {
            $addressList = $this->Form->getFormValue('EmailTestAddresses');
            $addresses = explode(',', $addressList);
            if (sizeof($addresses) > 10) {
                $this->Form->addError(sprintf(t('Too many addresses! We\'ll send up to %s addresses at once.'), '10'));
            } else {
                $emailer = $this->getTestEmail();
                $emailer->to($addresses);
                $emailer->subject(sprintf(t('Test email from %s'), c('Garden.Title')));

                try {
                    if ($emailer->send()) {
                        $this->informMessage(t("The email has been sent."));
                    } else {
                        $this->Form->addError(t('Error sending email. Please review the addresses and try again.'));
                    }
                } catch (Exception $e) {
                    if (debug()) {
                        throw $e;
                    }
                }
            }
        }
        $this->render();
    }

    /**
     * Manages the Garden.Email.Format setting.
     *
     * @param $value Whether to send emails in plaintext.
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function setEmailFormat($value) {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $value = strtolower($value);
        if (in_array($value, Gdn_Email::$supportedFormats)) {
            if (Gdn::session()->checkPermission('Garden.Community.Manage')) {
                saveToConfig('Garden.Email.Format', $value);
                if ($value === 'html') {
                    $newToggle = wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/setemailformat/text', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-on"]);
                    $this->jsonTarget('.js-foggy', 'foggyOff', 'Trigger');
                } else {
                    $newToggle = wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/setemailformat/html', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-off"]);
                    $this->jsonTarget('.js-foggy', 'foggyOn', 'Trigger');
                }
                $this->jsonTarget("#plaintext-toggle", $newToggle);
            }
        }
        $this->render('Blank', 'Utility');
    }

    /**
     * Endpoint for retrieving current email image url.
     */
    public function emailImageUrl() {
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->deliveryType(DELIVERY_TYPE_DATA);
        $image = c('Garden.EmailTemplate.Image');
        if ($image) {
            $image = Gdn_UploadImage::url($image);
        }
        $this->setData('EmailImage', $image);
        $this->render();
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
    public function index() {

        // Confirm that the user has at least one of the many admin preferences.
        $this->permission([
            'Garden.Settings.View',
            'Garden.Settings.Manage',
            'Garden.Community.Manage',
            'Garden.Moderation.Manage',
            'Moderation.ModerationQueue.Manage',
            'Garden.Users.Add',
            'Garden.Users.Edit',
            'Garden.Users.Delete',
            'Garden.Users.Approve',
        ], false);

        // Send the user to the last section they navigated to in the dashboard.
        $section = Gdn::session()->getPreference('DashboardNav.DashboardLandingPage', 'DashboardHome');
        if ($section) {
            $sections = DashboardNavModule::getDashboardNav()->getSectionsInfo();
            $url = val('url', val($section, $sections));
            if ($url) {
                redirect($url);
            }
        }

        // Resolve our default landing page redirection based on permissions.
        if (!Gdn::session()->checkPermission([
                'Garden.Settings.View',
                'Garden.Settings.Manage',
                'Garden.Community.Manage',
            ], false)) {
            // We don't have permission to see the dashboard/home.
            redirect(DashboardNavModule::getDashboardNav()->getUrlForSection('Moderation'));
        }

        // Still here?
        redirect('dashboard/settings/home');
    }

    public function home() {
        $this->addJsFile('settings.js');
        $this->title(t('Dashboard'));

        $this->RequiredAdminPermissions = [
            'Garden.Settings.View',
            'Garden.Settings.Manage',
            'Garden.Community.Manage',
        ];

        $this->fireEvent('DefineAdminPermissions');
        $this->permission($this->RequiredAdminPermissions, false);
        $this->setHighlightRoute('dashboard/settings');

        $UserModel = Gdn::userModel();

        // Get recently active users
        $this->ActiveUserData = $UserModel->getActiveUsers(5);

        // Check for updates
        $this->addUpdateCheck();

        $this->addDefinition('ExpandText', t('more'));
        $this->addDefinition('CollapseText', t('less'));

        // Fire an event so other applications can add some data to be displayed
        $this->fireEvent('DashboardData');

        Gdn_Theme::section('DashboardHome');
        $this->setData('IsWidePage', true);

        $this->render('index');
    }

    /**
     * Adds information to the definition list that causes the app to "phone
     * home" and see if there are upgrades available.
     *
     * Currently added to the dashboard only. Nothing renders with this method.
     * It is public so it can be added by plugins.
     */
    public function addUpdateCheck() {
        if (c('Garden.NoUpdateCheck')) {
            return;
        }

        // Check to see if the application needs to phone-home for updates. Doing
        // this here because this method is always called when admin pages are
        // loaded regardless of the application loading them.
        $UpdateCheckDate = Gdn::config('Garden.UpdateCheckDate', '');
        if ($UpdateCheckDate == '' // was not previous defined
            || !IsTimestamp($UpdateCheckDate) // was not a valid timestamp
            || $UpdateCheckDate < strtotime("-1 day") // was not done within the last day
        ) {
            $UpdateData = array();

            // Grab all of the available addons & versions.
            foreach ([Addon::TYPE_ADDON, Addon::TYPE_THEME] as $type) {
                $addons = Gdn::addonManager()->lookupAllByType($type);
                /* @var Addon $addon */
                foreach ($addons as $addon) {
                    $UpdateData[] = [
                        'Name' => $addon->getRawKey(),
                        'Version' => $addon->getVersion(),
                        'Type' => $addon->getSpecial('oldType', $type)
                    ];
                }
            }

            // Dump the entire set of information into the definition list. The client will ping the server for updates.
            $this->addDefinition('UpdateChecks', $UpdateData);
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
    public function locales($Op = null, $LocaleKey = null) {
        $this->permission('Garden.Settings.Manage');

        $this->title(t('Locales'));
        $this->setHighlightRoute('dashboard/settings/locales');
        $this->addJsFile('addons.js');

        $LocaleModel = new LocaleModel();

        // Get the available locale packs.
        $AvailableLocales = $LocaleModel->availableLocalePacks();

        // Get the enabled locale packs.
        $EnabledLocales = $LocaleModel->enabledLocalePacks();

        // Check to enable/disable a locale.
        if ($this->Form->authenticatedPostBack() && !$Op) {
            // Save the default locale.
            saveToConfig('Garden.Locale', $this->Form->getFormValue('Locale'));
            $this->informMessage(t("Your changes have been saved."));

            Gdn::locale()->refresh();
            redirect('/settings/locales');
        } else {
            $this->Form->setValue('Locale', Gdn_Locale::canonicalize(c('Garden.Locale', 'en')));
        }

        if ($Op) {
            switch (strtolower($Op)) {
                case 'enable':
                    $this->enableLocale($LocaleKey, val($LocaleKey, $AvailableLocales), $EnabledLocales);
                    break;
                case 'disable':
                    $this->disableLocale($LocaleKey, val($LocaleKey, $AvailableLocales), $EnabledLocales);
            }
        }

        // Check for the default locale warning.
        $DefaultLocale = Gdn_Locale::canonicalize(c('Garden.Locale'));
        if ($DefaultLocale !== 'en') {
            $LocaleFound = false;
            $MatchingLocales = array();
            foreach ($AvailableLocales as $Key => $LocaleInfo) {
                $Locale = val('Locale', $LocaleInfo);
                if ($Locale == $DefaultLocale) {
                    $MatchingLocales[] = val('Name', $LocaleInfo, $Key);
                }

                if (val($Key, $EnabledLocales) == $DefaultLocale) {
                    $LocaleFound = true;
                }

            }
            $this->setData('DefaultLocale', $DefaultLocale);
            $this->setData('DefaultLocaleWarning', !$LocaleFound);
            $this->setData('MatchingLocalePacks', htmlspecialchars(implode(', ', $MatchingLocales)));
        }

        // Remove all hidden locales, unless they are enabled.
        $AvailableLocales = array_filter($AvailableLocales, function ($locale) use ($EnabledLocales) {
            return !val('Hidden', $locale) || isset($EnabledLocales[val('Index', $locale)]);
        });

        $this->setData('AvailableLocales', $AvailableLocales);
        $this->setData('EnabledLocales', $EnabledLocales);
        $this->setData('Locales', $LocaleModel->availableLocales());
        $this->render();
    }

    public function enableLocale($addonName, $addonInfo) {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $this->permission('Garden.Settings.Manage');

        if (!is_array($addonInfo)) {
            $this->Form->addError('@'.sprintf(t('The %s locale pack does not exist.'), htmlspecialchars($addonName)), 'LocaleKey');
        } elseif (!isset($addonInfo['Locale'])) {
            $this->Form->addError('ValidateRequired', 'Locale');
        } else {
            saveToConfig("EnabledLocales.$addonName", $addonInfo['Locale']);
            $this->informMessage(sprintf(t('%s Enabled.'), val('Name', $addonInfo, t('Locale'))));
        }

        $this->handleAddonToggle($addonName, $addonInfo, 'locales', true);

    }

    public function disableLocale($addonName, $addonInfo) {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $this->permission('Garden.Settings.Manage');

        RemoveFromConfig("EnabledLocales.$addonName");
        $this->informMessage(sprintf(t('%s Disabled.'), val('Name', $addonInfo, t('Locale'))));

        $this->handleAddonToggle($addonName, $addonInfo, 'locales', false);
    }

    /**
     * Manage list of plugins.
     *
     * @since 2.0.0
     * @access public
     * @param string $Filter 'enabled', 'disabled', or 'all' (default)
     * @param string $PluginName Unique ID of plugin to be modified.
     * @param string $TransientKey Security token.
     */
    public function plugins($Filter = '', $PluginName = '') {
        $this->permission('Garden.Settings.Manage');

        // Page setup
        $this->addJsFile('addons.js');
        $this->title(t('Plugins'));
        $this->setHighlightRoute('dashboard/settings/plugins');

        if (!in_array($Filter, array('enabled', 'disabled'))) {
            $Filter = 'all';
        }
        $this->Filter = $Filter;

        // Retrieve all available plugins from the plugins directory
        $this->EnabledPlugins = Gdn::pluginManager()->enabledPlugins();
        self::sortAddons($this->EnabledPlugins);
        $this->AvailablePlugins = Gdn::pluginManager()->availablePlugins();
        self::sortAddons($this->AvailablePlugins);

        if ($PluginName != '') {
            if (in_array(strtolower($PluginName), array_map('strtolower', array_keys($this->EnabledPlugins)))) {
                $this->disablePlugin($PluginName, $Filter);
            } else {
                $this->enablePlugin($PluginName, $Filter);
            }
        } else {
            $this->render();
        }
    }

    public function disablePlugin($pluginName, $filter = 'all') {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }

        $this->permission('Garden.Settings.Manage');

        $action = 'none';
        if ($filter == 'enabled') {
            $action = 'SlideUp';
        }

        $addon = Gdn::addonManager()->lookupAddon($pluginName);

        try {
            Gdn::pluginManager()->disablePlugin($pluginName);
            Gdn_LibraryMap::clearCache();
            $this->informMessage(sprintf(t('%s Disabled.'), val('name', $addon->getInfo(), t('Plugin'))));
            $this->EventArguments['PluginName'] = $pluginName;
            $this->fireEvent('AfterDisablePlugin');
        } catch (Exception $e) {
            $this->Form->addError($e);
        }

        $this->handleAddonToggle($pluginName, $addon->getInfo(), 'plugins', false, $filter, $action);
    }

    public function enablePlugin($pluginName, $filter = 'all') {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }

        $this->permission('Garden.Settings.Manage');

        $action = 'none';
        if ($filter == 'disabled') {
            $action = 'SlideUp';
        }

        $addon = Gdn::addonManager()->lookupAddon($pluginName);

        try {
            $validation = new Gdn_Validation();
            if (!Gdn::pluginManager()->enablePlugin($pluginName, $validation)) {
                $this->Form->setValidationResults($validation->results());
            } else {
                Gdn_LibraryMap::ClearCache();
                $this->informMessage(sprintf(t('%s Enabled.'), val('name', $addon->getInfo(), t('Plugin'))));
            }
            $this->EventArguments['PluginName'] = $pluginName;
            $this->EventArguments['Validation'] = $validation;
            $this->fireEvent('AfterEnablePlugin');
        } catch (Exception $e) {
            $this->Form->addError($e);
        }

        $this->handleAddonToggle($pluginName, $addon->getInfo(), 'plugins', true, $filter, $action);
    }

    /**
     * Configuration of registration settings.
     *
     * Events: BeforeRegistrationUpdate
     *
     * @since 2.0.0
     * @access public
     * @param string $RedirectUrl Where to send user after registration.
     */
    public function registration($RedirectUrl = '') {
        $this->permission('Garden.Settings.Manage');
        $this->setHighlightRoute('dashboard/settings/registration');

        $this->addJsFile('registration.js');
        $this->title(t('Registration'));

        // Load roles with sign-in permission
        $RoleModel = new RoleModel();
        $this->RoleData = $RoleModel->getByPermission('Garden.SignIn.Allow');
        $this->setData('_Roles', array_column($this->RoleData->resultArray(), 'Name', 'RoleID'));

        // Get currently selected InvitationOptions
        $this->ExistingRoleInvitations = Gdn::config('Garden.Registration.InviteRoles');
        if (is_array($this->ExistingRoleInvitations) === false) {
            $this->ExistingRoleInvitations = array();
        }

        // Get the currently selected Expiration Length
        $this->InviteExpiration = Gdn::config('Garden.Registration.InviteExpiration', '');

        // Registration methods.
        $this->RegistrationMethods = array(
            // 'Closed' => "Registration is closed.",
            'Basic' => "New users fill out a simple form and are granted access immediately.",
            'Approval' => "New users are reviewed and approved by an administrator (that's you!).",
            'Invitation' => "Existing members send invitations to new members.",
            'Connect' => "New users are only registered through SSO plugins."
        );

        // Options for how many invitations a role can send out per month.
        $this->InvitationOptions = array(
            '0' => t('None'),
            '1' => '1',
            '2' => '2',
            '5' => '5',
            '-1' => t('Unlimited')
        );

        // Options for when invitations should expire.
        $this->InviteExpirationOptions = array(
            '1 week' => t('1 week after being sent'),
            '2 weeks' => t('2 weeks after being sent'),
            '1 month' => t('1 month after being sent'),
            'FALSE' => t('never')
        );

        // Replace 'Captcha' with 'Basic' if needed
        if (c('Garden.Registration.Method') == 'Captcha') {
            saveToConfig('Garden.Registration.Method', 'Basic');
        }

        // Create a model to save configuration settings
        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);

        $registrationOptions = array(
            'Garden.Registration.Method' => 'Basic',
            'Garden.Registration.InviteExpiration',
            'Garden.Registration.ConfirmEmail'
        );
        $ConfigurationModel->setField($registrationOptions);

        $this->EventArguments['Validation'] = &$Validation;
        $this->EventArguments['Configuration'] = &$ConfigurationModel;
        $this->fireEvent('Registration');

        // Set the model on the forms.
        $this->Form->setModel($ConfigurationModel);

        if ($this->Form->authenticatedPostBack() === false) {
            $this->Form->setData($ConfigurationModel->Data);
        } else {
            // Define some validation rules for the fields being saved
            $ConfigurationModel->Validation->applyRule('Garden.Registration.Method', 'Required');

            // Define the Garden.Registration.RoleInvitations setting based on the postback values
            $InvitationRoleIDs = $this->Form->getValue('InvitationRoleID');
            $InvitationCounts = $this->Form->getValue('InvitationCount');
            $this->ExistingRoleInvitations = arrayCombine($InvitationRoleIDs, $InvitationCounts);
            $ConfigurationModel->forceSetting('Garden.Registration.InviteRoles', $this->ExistingRoleInvitations);

            // Event hook
            $this->EventArguments['ConfigurationModel'] = &$ConfigurationModel;
            $this->fireEvent('BeforeRegistrationUpdate');

            // Save!
            if ($this->Form->save() !== false) {
                $this->informMessage(t("Your settings have been saved."));
                if ($RedirectUrl != '') {
                    $this->RedirectUrl = $RedirectUrl;
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
     * @param array $Array Addon data (e.g. $PluginInfo).
     * @param bool $Filter Whether to exclude hidden addons (defaults to TRUE).
     */
    public static function sortAddons(&$Array, $Filter = true) {
        // Make sure every addon has a name.
        foreach ($Array as $Key => $Value) {
            if ($Filter && val('Hidden', $Value)) {
                unset($Array[$Key]);
                continue;
            }

            $Name = val('Name', $Value, $Key);
            setValue('Name', $Array[$Key], $Name);
        }
        uasort($Array, array('SettingsController', 'CompareAddonName'));
    }

    /**
     * Compare addon names for uasort.
     *
     * @since 2.0.0
     * @access public
     * @see self::SortAddons()
     * @param array $A First addon data.
     * @param array $B Second addon data.
     * @return int Result of strcasecmp.
     */
    public static function compareAddonName($A, $B) {
        return strcasecmp(val('Name', $A), val('Name', $B));
    }

    /**
     * Test and addon to see if there are any fatal errors during install.
     *
     * @since 2.0.0
     * @access public
     * @param string $AddonType
     * @param string $AddonName
     * @param string $TransientKey Security token.
     */
    public function testAddon($AddonType = '', $AddonName = '', $TransientKey = '') {
        $this->permission('Garden.Settings.Manage');

        if (!in_array($AddonType, array('Plugin', 'Application', 'Theme', 'Locale'))) {
            $AddonType = 'Plugin';
        }

        $Session = Gdn::session();
        $AddonName = $Session->validateTransientKey($TransientKey) ? $AddonName : '';
        if ($AddonType == 'Locale') {
            $AddonManager = new LocaleModel();
            $TestMethod = 'TestLocale';
        } else {
            $AddonManagerName = $AddonType.'Manager';
            $TestMethod = 'Test'.$AddonType;
            $AddonManager = Gdn::Factory($AddonManagerName);
        }
        if ($AddonName != '') {
            $Validation = new Gdn_Validation();

            try {
                $AddonManager->$TestMethod($AddonName, $Validation);
            } catch (Exception $Ex) {
                if (Debug()) {
                    throw $Ex;
                } else {
                    echo $Ex->getMessage();
                    return;
                }
            }
        }

        ob_clean();
        echo 'Success';
    }

    /**
     * Manage options for a theme.
     *
     * @since 2.0.0
     * @access public
     * @todo Why is this in a giant try/catch block?
     */
    public function themeOptions() {
        $this->permission('Garden.Settings.Manage');

        try {
            $this->addJsFile('addons.js');
            $this->setHighlightRoute('dashboard/settings/themeoptions');

            $ThemeManager = Gdn::themeManager();
            $this->setData('ThemeInfo', $ThemeManager->enabledThemeInfo());

            if ($this->Form->authenticatedPostBack()) {
                // Save the styles to the config.
                $StyleKey = $this->Form->getFormValue('StyleKey');

                $ConfigSaveData = array(
                    'Garden.ThemeOptions.Styles.Key' => $StyleKey,
                    'Garden.ThemeOptions.Styles.Value' => $this->data("ThemeInfo.Options.Styles.$StyleKey.Basename"));

                // Save the text to the locale.
                $Translations = array();
                foreach ($this->data('ThemeInfo.Options.Text', array()) as $Key => $Default) {
                    $Value = $this->Form->getFormValue($this->Form->escapeString('Text_'.$Key));
                    $ConfigSaveData["ThemeOption.{$Key}"] = $Value;
                    //$this->Form->setFormValue('Text_'.$Key, $Value);
                }

                saveToConfig($ConfigSaveData);
                $this->informMessage(t("Your changes have been saved."));
            }

            $this->setData('ThemeOptions', c('Garden.ThemeOptions'));
            $StyleKey = $this->data('ThemeOptions.Styles.Key');

            if (!$this->Form->isPostBack()) {
                foreach ($this->data('ThemeInfo.Options.Text', array()) as $Key => $Options) {
                    $Default = val('Default', $Options, '');
                    $Value = c("ThemeOption.{$Key}", '#DEFAULT#');
                    if ($Value === '#DEFAULT#') {
                        $Value = $Default;
                    }

                    $this->Form->setValue($this->Form->escapeString('Text_'.$Key), $Value);
                }
            }

            $this->setData('ThemeFolder', $ThemeManager->enabledTheme());
            $this->title(t('Theme Options'));
            $this->Form->addHidden('StyleKey', $StyleKey);
        } catch (Exception $Ex) {
            $this->Form->addError($Ex);
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
    public function mobileThemeOptions() {
        $this->permission('Garden.Settings.Manage');

        try {
            $this->addJsFile('addons.js');
            $this->setHighlightRoute('dashboard/settings/mobilethemeoptions');

            $ThemeManager = Gdn::themeManager();
            $EnabledThemeName = $ThemeManager->mobileTheme();
            $EnabledThemeInfo = $ThemeManager->getThemeInfo($EnabledThemeName);

            $this->setData('ThemeInfo', $EnabledThemeInfo);

            if ($this->Form->authenticatedPostBack()) {
                // Save the styles to the config.
                $StyleKey = $this->Form->getFormValue('StyleKey');

                $ConfigSaveData = array(
                    'Garden.MobileThemeOptions.Styles.Key' => $StyleKey,
                    'Garden.MobileThemeOptions.Styles.Value' => $this->data("ThemeInfo.Options.Styles.$StyleKey.Basename"));

                // Save the text to the locale.
                $Translations = array();
                foreach ($this->data('ThemeInfo.Options.Text', array()) as $Key => $Default) {
                    $Value = $this->Form->getFormValue($this->Form->escapeString('Text_'.$Key));
                    $ConfigSaveData["ThemeOption.{$Key}"] = $Value;
                    //$this->Form->setFormValue('Text_'.$Key, $Value);
                }

                saveToConfig($ConfigSaveData);
                $this->fireEvent['AfterSaveThemeOptions'];

                $this->informMessage(t("Your changes have been saved."));
            }

            $this->setData('ThemeOptions', c('Garden.MobileThemeOptions'));
            $StyleKey = $this->data('ThemeOptions.Styles.Key');

            if (!$this->Form->authenticatedPostBack()) {
                foreach ($this->data('ThemeInfo.Options.Text', array()) as $Key => $Options) {
                    $Default = val('Default', $Options, '');
                    $Value = c("ThemeOption.{$Key}", '#DEFAULT#');
                    if ($Value === '#DEFAULT#') {
                        $Value = $Default;
                    }

                    $this->Form->setFormValue($this->Form->escapeString('Text_'.$Key), $Value);
                }
            }

            $this->setData('ThemeFolder', $EnabledThemeName);
            $this->title(t('Mobile Theme Options'));
            $this->Form->addHidden('StyleKey', $StyleKey);
        } catch (Exception $Ex) {
            $this->Form->addError($Ex);
        }

        $this->render('themeoptions');
    }

    /**
     * Themes management screen.
     *
     * @since 2.0.0
     * @access public
     * @param string $ThemeName Unique ID.
     * @param string $TransientKey Security token.
     */
    public function themes($ThemeName = '', $TransientKey = '') {
        $this->addJsFile('addons.js');
        $this->setData('Title', t('Themes'));

        $this->permission('Garden.Settings.Manage');
        $this->setHighlightRoute('dashboard/settings/themes');

        $ThemeInfo = Gdn::themeManager()->enabledThemeInfo(true);
        $currentTheme = $this->themeInfoToMediaItem(val('Index', $ThemeInfo), true);
        $this->setData('CurrentTheme', $currentTheme);

        $Themes = Gdn::themeManager()->availableThemes();
        uasort($Themes, array('SettingsController', '_NameSort'));

        // Remove themes that are archived
        $Remove = array();
        foreach ($Themes as $Index => $Theme) {
            $Archived = val('Archived', $Theme);
            if ($Archived) {
                $Remove[] = $Index;
            }

            // Remove mobile themes, as they have own page.
            if (isset($Theme['IsMobile']) && $Theme['IsMobile']) {
                unset($Themes[$Index]);
            }
        }
        foreach ($Remove as $Index) {
            unset($Themes[$Index]);
        }
        $this->setData('AvailableThemes', $Themes);

        if ($ThemeName != '' && Gdn::session()->validateTransientKey($TransientKey)) {
            try {
                $ThemeInfo = Gdn::themeManager()->getThemeInfo($ThemeName);
                if ($ThemeInfo === false) {
                    throw new Exception(sprintf(t("Could not find a theme identified by '%s'"), $ThemeName));
                }

                Gdn::session()->setPreference(array('PreviewThemeName' => '', 'PreviewThemeFolder' => '')); // Clear out the preview
                Gdn::themeManager()->enableTheme($ThemeName);
                $this->EventArguments['ThemeName'] = $ThemeName;
                $this->EventArguments['ThemeInfo'] = $ThemeInfo;
                $this->fireEvent('AfterEnableTheme');
            } catch (Exception $Ex) {
                $this->Form->addError($Ex);
            }

            if ($this->Form->errorCount() == 0) {
                redirect('/settings/themes');
            }

        }
        $this->render();
    }

    public function themeInfo($themeName) {
        $this->permission('Garden.Settings.Manage');
        $themeMedia = $this->themeInfoToMediaItem($themeName);
        $this->setData('Theme', $themeMedia);
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
    private function themeInfoToMediaItem($themeKey, $isCurrent = false) {
        $themeInfo = Gdn::themeManager()->getThemeInfo($themeKey);

        if (!$themeInfo) {
            throw new Exception(sprintf(t('Theme with key %s not found.'), $themeKey));
        }
        $options = val('Options', $themeInfo, []);
        $iconUrl = val('IconUrl', $themeInfo, val('ScreenshotUrl', $themeInfo,
            "applications/dashboard/design/images/theme-placeholder.svg"));
        $themeName = val('Name', $themeInfo, val('Index', $themeInfo, $themeKey));
        $themeUrl = val('ThemeUrl', $themeInfo, '');
        $description = val('Description', $themeInfo, '');
        $version = val('Version', $themeInfo, '');
        $newVersion = val('NewVersion', $themeInfo, '');
        $attr = [];

        if ($isCurrent) {
            $attr['class'] = 'media-callout-grey-bg';
        }

        $media = new MediaItemModule($themeName, $themeUrl, $description, 'div', $attr);
        $media->setView('media-callout');
        $media->addOption('has-options', !empty($options));
        $media->addOption('has-upgrade', $newVersion != '' && version_compare($newVersion, $version, '>'));
        $media->addOption('new-version', val('NewVersion', $themeInfo, ''));
        $media->setImage($iconUrl);

        if ($isCurrent) {
            $media->addOption('is-current', $isCurrent);
        }

        // Meta

        // Add author meta
        $author = val('Author', $themeInfo, '');
        $authorUrl = val('AuthorUrl', $themeInfo, '');
        $media->addMetaIf($author != '', '<span class="media-meta author">'
            .sprintf('Created by %s', $authorUrl != '' ? anchor($author, $authorUrl) : $author).'</span>');

        // Add version meta
        $version = val('Version', $themeInfo, '');
        $media->addMetaIf($version != '', '<span class="media-meta version">'
            .sprintf(t('Version %s'), $version).'</span>');

        // Add requirements meta
        $requirements = val('RequiredApplications', $themeInfo, []);
        $required = [];
        $requiredString = '';

        if (!empty($requirements)) {
            foreach ($requirements as $requirement => $versionInfo) {
                $required[] = printf(t('%1$s Version %2$s'), $requirement, $versionInfo);
            }
        }

        if (!empty($required)) {
            $requiredString .= '<span class="media-meta requirements">'.t('Requires: ').implode(', ', $required).'</span>';
        }
        $media->addMetaIf($requiredString != '', $requiredString);
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
    public function mobileThemes($ThemeName = '', $TransientKey = '') {
        $IsMobile = true;

        $this->addJsFile('addons.js');
        $this->addJsFile('addons.js');
        $this->setData('Title', t('Mobile Themes'));

        $this->permission('Garden.Settings.Manage');
        $this->setHighlightRoute('dashboard/settings/themes');

        // Get currently enabled theme.
        $EnabledThemeName = Gdn::ThemeManager()->MobileTheme();
        $ThemeInfo = Gdn::themeManager()->getThemeInfo($EnabledThemeName);
        $this->setData('EnabledThemeInfo', $ThemeInfo);
        $this->setData('EnabledThemeFolder', val('Folder', $ThemeInfo));
        $this->setData('EnabledTheme', $ThemeInfo);
        $this->setData('EnabledThemeScreenshotUrl', val('ScreenshotUrl', $ThemeInfo));
        $this->setData('EnabledThemeName', val('Name', $ThemeInfo, val('Index', $ThemeInfo)));

        // Get all themes.
        $Themes = Gdn::themeManager()->availableThemes();

        // Filter themes.
        foreach ($Themes as $ThemeKey => $ThemeData) {
            // Only show mobile themes.
            if (empty($ThemeData['IsMobile'])) {
                unset($Themes[$ThemeKey]);
            }

            // Remove themes that are archived
            if (!empty($ThemeData['Archived'])) {
                unset($Themes[$ThemeKey]);
            }
        }

        uasort($Themes, array('SettingsController', '_NameSort'));
        $this->setData('AvailableThemes', $Themes);

        // Process self-post.
        if ($ThemeName != '' && Gdn::session()->validateTransientKey($TransientKey)) {
            try {
                $ThemeInfo = Gdn::themeManager()->getThemeInfo($ThemeName);
                if ($ThemeInfo === false) {
                    throw new Exception(sprintf(t("Could not find a theme identified by '%s'"), $ThemeName));
                }

                Gdn::session()->setPreference(array('PreviewMobileThemeName' => '', 'PreviewMobileThemeFolder' => '')); // Clear out the preview
                Gdn::themeManager()->enableTheme($ThemeName, $IsMobile);
                $this->EventArguments['ThemeName'] = $ThemeName;
                $this->EventArguments['ThemeInfo'] = $ThemeInfo;
                $this->fireEvent('AfterEnableTheme');
            } catch (Exception $Ex) {
                $this->Form->addError($Ex);
            }

            $AsyncRequest = ($this->deliveryType() === DELIVERY_TYPE_VIEW)
                ? true
                : false;

            if ($this->Form->errorCount() == 0) {
                if ($AsyncRequest) {
                    echo 'Success';
                    $this->render('Blank', 'Utility', 'Dashboard');
                    exit;
                } else {
                    redirect('/settings/mobilethemes');
                }
            } else {
                if ($AsyncRequest) {
                    echo $this->Form->errorString();
                    $this->render('Blank', 'Utility', 'Dashboard');
                    exit;
                }
            }
        }

        $this->render();
    }

    protected static function _nameSort($A, $B) {
        return strcasecmp(val('Name', $A), val('Name', $B));
    }

    /**
     * Show a preview of a theme.
     *
     * @since 2.0.0
     * @access public
     * @param string $ThemeName Unique ID.
     * @param string $transientKey
     */
    public function previewTheme($ThemeName = '', $transientKey = '') {
        $this->permission('Garden.Settings.Manage');

        if (Gdn::session()->validateTransientKey($transientKey)) {
            $ThemeInfo = Gdn::themeManager()->getThemeInfo($ThemeName);
            $PreviewThemeName = $ThemeName;
            $displayName = val('Name', $ThemeInfo);
            $IsMobile = val('IsMobile', $ThemeInfo);

            // If we failed to get the requested theme, cancel preview
            if ($ThemeInfo === false) {
                $PreviewThemeName = '';
            }

            if ($IsMobile) {
                Gdn::session()->setPreference(
                    ['PreviewMobileThemeFolder' => $PreviewThemeName,
                    'PreviewMobileThemeName' => $displayName]
                );
            } else {
                Gdn::session()->setPreference(
                    ['PreviewThemeFolder' => $PreviewThemeName,
                    'PreviewThemeName' => $displayName]
                );
            }

            $this->fireEvent('PreviewTheme', ['ThemeInfo' => $ThemeInfo]);

            redirect('/');
        } else {
            redirect('settings/themes');
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
    public function cancelPreview($previewThemeFolder = '', $transientKey = '') {
        $this->permission('Garden.Settings.Manage');
        $isMobile = false;

        if (Gdn::session()->validateTransientKey($transientKey)) {
            $themeInfo = Gdn::themeManager()->getThemeInfo($previewThemeFolder);
            $isMobile = val('IsMobile', $themeInfo);

            if ($isMobile) {
                Gdn::session()->setPreference(
                    ['PreviewMobileThemeFolder' => '',
                    'PreviewMobileThemeName' => '']
                );
            } else {
                Gdn::session()->setPreference(
                    ['PreviewThemeFolder' => '',
                    'PreviewThemeName' => '']
                );
            }
        }

        if ($isMobile) {
            redirect('settings/mobilethemes');
        } else {
            redirect('settings/themes');
        }
    }

    /**
     * Remove the default avatar from config & delete it.
     *
     * @since 2.0.0
     * @access public
     */
    public function removeDefaultAvatar() {
        if (Gdn::request()->isAuthenticatedPostBack(true) && Gdn::session()->checkPermission('Garden.Community.Manage')) {
            $avatar = c('Garden.DefaultAvatar', '');
            $this->deleteDefaultAvatars($avatar);
            removeFromConfig('Garden.DefaultAvatar');
            $this->informMessage(sprintf(t('%s deleted.'), t('Avatar')));
        }
        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * Deletes uploaded default avatars.
     *
     * @param string $avatar The avatar to delete.
     */
    private function deleteDefaultAvatars($avatar = '') {
        if ($avatar && $this->isUploadedDefaultAvatar($avatar)) {
            $upload = new Gdn_Upload();
            $upload->delete(self::DEFAULT_AVATAR_FOLDER.'/'.basename($avatar));
            $upload->delete(self::DEFAULT_AVATAR_FOLDER.'/'.basename(changeBasename($avatar, 'p%s')));
            $upload->delete(self::DEFAULT_AVATAR_FOLDER.'/'.basename(changeBasename($avatar, 'n%s')));
        }
    }

    /**
     * Prompts new admins how to get started using new install.
     *
     * @since 2.0.0
     * @access public
     */
    public function gettingStarted() {
        $this->permission('Garden.Settings.Manage');

        $this->setData('Title', t('Getting Started'));
        $this->setHighlightRoute('dashboard/settings/gettingstarted');

        Gdn_Theme::section('Tutorials');
        $this->setData('IsWidePage', true);
        $this->render();
    }

    /**
     *
     *
     * @param string $Tutorial
     */
    public function tutorials($Tutorial = '') {
        $this->permission('Garden.Settings.Manage');
        $this->setData('Title', t('Help &amp; Tutorials'));
        $this->setHighlightRoute('dashboard/settings/tutorials');
        $this->setData('CurrentTutorial', $Tutorial);
        Gdn_Theme::section('Tutorials');
        $this->setData('IsWidePage', true);
        $this->render();
    }
}
