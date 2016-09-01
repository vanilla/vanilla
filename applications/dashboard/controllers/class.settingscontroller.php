<?php
/**
 * Managing core Dashboard settings.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
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
    public function applications($Filter = '', $ApplicationName = '', $TransientKey = '') {
        $this->permission('Garden.Settings.Manage');

        // Page setup
        $this->addJsFile('addons.js');
        $this->addJsFile('applications.js');
        $this->title(t('Applications'));
        $this->setHighlightRoute('dashboard/settings/applications');

        // Validate & set parameters
        $Session = Gdn::session();
        if ($ApplicationName && !$Session->validateTransientKey($TransientKey)) {
            $ApplicationName = '';
        }
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
                try {
                    $ApplicationManager->disableApplication($ApplicationName);
                } catch (Exception $e) {
                    $this->Form->addError(strip_tags($e->getMessage()));
                }
            } else {
                try {
                    $ApplicationManager->checkRequirements($ApplicationName);
                } catch (Exception $e) {
                    $this->Form->addError(strip_tags($e->getMessage()));
                }
                if ($this->Form->errorCount() == 0) {
                    $Validation = new Gdn_Validation();
                    $ApplicationManager->registerPermissions($ApplicationName, $Validation);
                    $ApplicationManager->enableApplication($ApplicationName, $Validation);
                    $this->Form->setValidationResults($Validation->results());
                }

            }
            if ($this->Form->errorCount() == 0) {
                redirect('settings/applications/'.$this->Filter);
            }
        }
        $this->render();
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
        $BanModel = $this->_BanModel;
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
            $thumbnailSize = c('Garden.Thumbnail.Size', 40);
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
            if ($tmpAvatar = $upload->validateUpload('DefaultAvatar', false)) {
                // New upload
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
                    $thumbnailSize = c('Garden.Thumbnail.Size', 40);

                    // Update crop properties.
                    $basename = changeBasename($avatar, "p%s");
                    $source = $upload->copyLocal($basename);
                    $crop = new CropImageModule($this, $this->Form, $thumbnailSize, $thumbnailSize, $source);
                    $crop->saveButton = false;
                    $crop->setSize($thumbnailSize, $thumbnailSize);
                    $crop->setExistingCropUrl(Gdn_UploadImage::url(changeBasename($avatar, "n%s")));
                    $crop->setSourceImageUrl(Gdn_UploadImage::url(changeBasename($avatar, "p%s")));
                    $this->setData('crop', $crop);
                }
            }
            $this->informMessage(t("Your settings have been saved."));
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
                c('Garden.Profile.MaxHeight', 1000),
                c('Garden.Profile.MaxWidth', 250),
                array('SaveGif' => c('Garden.Thumbnail.SaveGif'))
            );

            $thumbnailSize = c('Garden.Thumbnail.Size', 40);
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
        $this->permission('Garden.Community.Manage');
        $this->setHighlightRoute('dashboard/settings/banner');
        $this->title(t('Banner'));

        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->setField(array(
            'Garden.HomepageTitle' => c('Garden.Title'),
            'Garden.Title',
            'Garden.Description'
        ));

        // Set the model on the form.
        $this->Form->setModel($ConfigurationModel);

        // Get the current logo.
        $Logo = c('Garden.Logo');
        if ($Logo) {
            $Logo = ltrim($Logo, '/');
            // Fix the logo path.
            if (stringBeginsWith($Logo, 'uploads/')) {
                $Logo = substr($Logo, strlen('uploads/'));
            }
            $this->setData('Logo', $Logo);
        }

        // Get the current mobile logo.
        $MobileLogo = c('Garden.MobileLogo');
        if ($MobileLogo) {
            $MobileLogo = ltrim($MobileLogo, '/');
            // Fix the logo path.
            if (stringBeginsWith($MobileLogo, 'uploads/')) {
                $MobileLogo = substr($MobileLogo, strlen('uploads/'));
            }
            $this->setData('MobileLogo', $MobileLogo);
        }


        // Get the current favicon.
        $Favicon = c('Garden.FavIcon');
        $this->setData('Favicon', $Favicon);

        $ShareImage = c('Garden.ShareImage');
        $this->setData('ShareImage', $ShareImage);

        // If seeing the form for the first time...
        if (!$this->Form->authenticatedPostBack()) {
            // Apply the config settings to the form.
            $this->Form->setData($ConfigurationModel->Data);
        } else {
            $SaveData = array();
            if ($this->Form->save() !== false) {
                $Upload = new Gdn_Upload();
                try {
                    // Validate the upload
                    $TmpImage = $Upload->validateUpload('Logo', false);
                    if ($TmpImage) {
                        // Generate the target image name
                        $TargetImage = $Upload->generateTargetName(PATH_UPLOADS);
                        $ImageBaseName = pathinfo($TargetImage, PATHINFO_BASENAME);

                        // Delete any previously uploaded images.
                        if ($Logo) {
                            $Upload->delete($Logo);
                        }

                        // Save the uploaded image
                        $Parts = $Upload->SaveAs(
                            $TmpImage,
                            $ImageBaseName
                        );
                        $ImageBaseName = $Parts['SaveName'];
                        $SaveData['Garden.Logo'] = $ImageBaseName;
                        $this->setData('Logo', $ImageBaseName);
                    }

                    $TmpMobileImage = $Upload->validateUpload('MobileLogo', false);
                    if ($TmpMobileImage) {
                        // Generate the target image name
                        $TargetImage = $Upload->generateTargetName(PATH_UPLOADS);
                        $ImageBaseName = pathinfo($TargetImage, PATHINFO_BASENAME);

                        // Delete any previously uploaded images.
                        if ($MobileLogo) {
                            $Upload->delete($MobileLogo);
                        }

                        // Save the uploaded image
                        $Parts = $Upload->saveAs(
                            $TmpMobileImage,
                            $ImageBaseName
                        );
                        $ImageBaseName = $Parts['SaveName'];
                        $SaveData['Garden.MobileLogo'] = $ImageBaseName;
                        $this->setData('MobileLogo', $ImageBaseName);
                    }

                    $ImgUpload = new Gdn_UploadImage();
                    $TmpFavicon = $ImgUpload->validateUpload('Favicon', false);
                    if ($TmpFavicon) {
                        $ICOName = 'favicon_'.substr(md5(microtime()), 16).'.ico';

                        if ($Favicon) {
                            $Upload->delete($Favicon);
                        }

                        // Resize the to a png.
                        $Parts = $ImgUpload->SaveImageAs($TmpFavicon, $ICOName, 16, 16, array('OutputType' => 'ico', 'Crop' => true));
                        $SaveData['Garden.FavIcon'] = $Parts['SaveName'];
                        $this->setData('Favicon', $Parts['SaveName']);
                    }

                    $TmpShareImage = $Upload->ValidateUpload('ShareImage', false);
                    if ($TmpShareImage) {
                        $TargetImage = $Upload->GenerateTargetName(PATH_UPLOADS, false);
                        $ImageBaseName = pathinfo($TargetImage, PATHINFO_BASENAME);

                        if ($ShareImage) {
                            $Upload->delete($ShareImage);
                        }

                        $Parts = $Upload->SaveAs($TmpShareImage, $ImageBaseName);
                        $SaveData['Garden.ShareImage'] = $Parts['SaveName'];
                        $this->setData('ShareImage', $Parts['SaveName']);

                    }
                } catch (Exception $ex) {
                    $this->Form->addError($ex);
                }
                // If there were no errors, save the path to the logo in the config
                if ($this->Form->errorCount() == 0) {
                    saveToConfig($SaveData);

                }

                $this->informMessage(t("Your settings have been saved."));
            }
        }

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
        $this->addJsFile('bans.js');

        list($Offset, $Limit) = offsetLimit($Page, 20);

        $BanModel = new BanModel();
        $this->_BanModel = $BanModel;

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
        $this->setHighlightRoute('dashboard/settings/emailstyles');
        $this->addJsFile('email.js');
        // Get the current logo.
        $image = c('Garden.EmailTemplate.Image');
        if ($image) {
            $image = ltrim($image, '/');
            $this->setData('EmailImage', Gdn_UploadImage::url($image));
        }
        $this->Form = new Gdn_Form();
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(array(
            'Garden.EmailTemplate.TextColor',
            'Garden.EmailTemplate.BackgroundColor',
            'Garden.EmailTemplate.ContainerBackgroundColor',
            'Garden.EmailTemplate.ButtonTextColor',
            'Garden.EmailTemplate.ButtonBackgroundColor'
        ));
        // Set the model on the form.
        $this->Form->setModel($configurationModel);
        // If seeing the form for the first time...
        if ($this->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $this->Form->setData($configurationModel->Data);
        } else {
            if ($this->Form->save() !== false) {
                $this->informMessage(t("Your settings have been saved."));
            }
        }
        $this->render();
    }

    /**
     * Sets up a new Gdn_Email object with a test email.
     *
     * @param string $textColor The hex color code of the text.
     * @param string $backGroundColor The hex color code of the background color.
     * @param string $containerBackgroundColor The hex color code of the container background color.
     * @param string $buttonTextColor The hex color code of the link color.
     * @param string $buttonBackgroundColor The hex color code of the button background.
     * @return Gdn_Email The email object with the test colors set.
     */
    public function getTestEmail($textColor = '', $backGroundColor = '', $containerBackgroundColor = '', $buttonTextColor = '', $buttonBackgroundColor = '') {
        $emailer = new Gdn_Email();
        $email = $emailer->getEmailTemplate();
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
     * Echoes out a test email with the colors in the post request.
     *
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function emailPreview() {
        $request = Gdn::request();
//        if (!$request->isAuthenticatedPostBack(true)) {
//            throw new Exception('Requires POST', 405);
//        }

        $textColor = $request->post('textColor', '');
        $backGroundColor = $request->post('backgroundColor', '');
        $containerBackGroundColor = $request->post('containerBackgroundColor', '');
        $buttonTextColor = $request->post('buttonTextColor', '');
        $buttonBackgroundColor = $request->post('buttonBackgroundColor', '');

        echo $this->getTestEmail($textColor, $backGroundColor, $containerBackGroundColor, $buttonTextColor, $buttonBackgroundColor)->getEmailTemplate()->toString();
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
                if ($emailer->send()) {
                    $this->informMessage(t("The email has been sent."));
                } else {
                    $this->Form->addError(t('Error sending email. Please review the addresses and try again.'));
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
                    $newToggle = wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/setemailformat/text', 'Hijack', array('onclick' => 'emailStyles.hideSettings();')), 'span', array('class' => "toggle-wrap toggle-wrap-on ActivateSlider ActivateSlider-Active"));
                } else {
                    $newToggle = wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/setemailformat/html', 'Hijack', array('onclick' => 'emailStyles.showSettings();')), 'span', array('class' => "toggle-wrap toggle-wrap-off ActivateSlider ActivateSlider-Inactive"));
                }
                $this->jsonTarget("#plaintext-toggle", $newToggle);
            }
        }
        $this->render('Blank', 'Utility');
    }

    /**
     * Remove the email image from config & delete it.
     */
    public function removeEmailImage() {
        if (Gdn::request()->isAuthenticatedPostBack(true) && Gdn::session()->checkPermission('Garden.Community.Manage')) {
            $image = c('Garden.EmailTemplate.Image', '');
            RemoveFromConfig('Garden.EmailTemplate.Image');
            $upload = new Gdn_Upload();
            $upload->delete($image);
            $this->informMessage(sprintf(t('%s deleted.'), t('Logo')));
        }

        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * Endpoint for retrieving email image url.
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
     * Form for adding an email image.
     * Exposes the Garden.EmailTemplate.Image setting.
     * Garden.EmailTemplate.Image must be an upload.
     *
     * Saves the image based on 2 config settings:
     * Garden.EmailTemplate.ImageMaxWidth (default 400px) and
     * Garden.EmailTemplate.ImageMaxHeight (default 300px)
     *
     * @throws Gdn_UserException
     */
    public function emailImage() {
        if (!Gdn::session()->checkPermission('Garden.Community.Manage')) {
            throw permissionException();
        }
        $this->addJsFile('email.js');
        $this->setHighlightRoute('dashboard/settings/email');
        $image = c('Garden.EmailTemplate.Image');
        $this->Form = new Gdn_Form();
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        // Set the model on the form.
        $this->Form->setModel($configurationModel);
        if ($this->Form->authenticatedPostBack() !== false) {
            try {
                $upload = new Gdn_UploadImage();
                // Validate the upload
                $tmpImage = $upload->validateUpload('EmailImage', false);
                if ($tmpImage) {
                    // Generate the target image name
                    $targetImage = $upload->generateTargetName(PATH_UPLOADS);
                    $imageBaseName = pathinfo($targetImage, PATHINFO_BASENAME);
                    // Delete any previously uploaded images.
                    if ($image) {
                        $upload->delete($image);
                    }
                    // Save the uploaded image
                    $parts = $upload->saveImageAs(
                        $tmpImage,
                        $imageBaseName,
                        c('Garden.EmailTemplate.ImageMaxWidth', 400),
                        c('Garden.EmailTemplate.ImageMaxHeight', 300)
                    );

                    $imageBaseName = $parts['SaveName'];
                    saveToConfig('Garden.EmailTemplate.Image', $imageBaseName);
                    $this->setData('EmailImage', Gdn_UploadImage::url($imageBaseName));
                } else {
                    $this->Form->addError(t('There\'s been an error uploading the image. Your email logo can uploaded in one of the following filetypes: gif, jpg, png'));
                }
            } catch (Exception $ex) {
                $this->Form->addError($ex);
            }
        }
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
        $this->addJsFile('settings.js');
        $this->title(t('Dashboard'));

        $this->RequiredAdminPermissions[] = 'Garden.Settings.View';
        $this->RequiredAdminPermissions[] = 'Garden.Settings.Manage';
        $this->RequiredAdminPermissions[] = 'Garden.Community.Manage';
        $this->RequiredAdminPermissions[] = 'Garden.Users.Add';
        $this->RequiredAdminPermissions[] = 'Garden.Users.Edit';
        $this->RequiredAdminPermissions[] = 'Garden.Users.Delete';
        $this->RequiredAdminPermissions[] = 'Garden.Users.Approve';
        $this->fireEvent('DefineAdminPermissions');
        $this->permission($this->RequiredAdminPermissions, false);
        $this->setHighlightRoute('dashboard/settings');

        $UserModel = Gdn::userModel();

        // Get recently active users
        $this->ActiveUserData = $UserModel->getActiveUsers(5);

        // Check for updates
        $this->addUpdateCheck();

        // Fire an event so other applications can add some data to be displayed
        $this->fireEvent('DashboardData');

        Gdn_Theme::section('DashboardHome');
        $this->render();
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
    public function locales($Op = null, $LocaleKey = null, $TransientKey = null) {
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
        if (($TransientKey && Gdn::session()->validateTransientKey($TransientKey)) || $this->Form->authenticatedPostBack()) {
            if ($Op) {
                $Refresh = false;
                switch (strtolower($Op)) {
                    case 'enable':
                        $Locale = val($LocaleKey, $AvailableLocales);
                        if (!is_array($Locale)) {
                            $this->Form->addError('@'.sprintf(t('The %s locale pack does not exist.'), htmlspecialchars($LocaleKey)), 'LocaleKey');
                        } elseif (!isset($Locale['Locale'])) {
                            $this->Form->addError('ValidateRequired', 'Locale');
                        } else {
                            saveToConfig("EnabledLocales.$LocaleKey", $Locale['Locale']);
                            $EnabledLocales[$LocaleKey] = $Locale['Locale'];
                            $Refresh = true;
                        }
                        break;
                    case 'disable':
                        RemoveFromConfig("EnabledLocales.$LocaleKey");
                        unset($EnabledLocales[$LocaleKey]);
                        $Refresh = true;
                        break;
                }

                // Set default locale field if just doing enable/disable
                $this->Form->setValue('Locale', Gdn_Locale::canonicalize(c('Garden.Locale', 'en')));
            } elseif ($this->Form->authenticatedPostBack()) {
                // Save the default locale.
                saveToConfig('Garden.Locale', $this->Form->getFormValue('Locale'));
                $Refresh = true;
                $this->informMessage(t("Your changes have been saved."));
            }

            if ($Refresh) {
                Gdn::locale()->refresh();
                redirect('/settings/locales');
            }
        } elseif (!$this->Form->isPostBack()) {
            $this->Form->setValue('Locale', Gdn_Locale::canonicalize(c('Garden.Locale', 'en')));
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

    /**
     * Manage list of plugins.
     *
     * @since 2.0.0
     * @access public
     * @param string $Filter 'enabled', 'disabled', or 'all' (default)
     * @param string $PluginName Unique ID of plugin to be modified.
     * @param string $TransientKey Security token.
     */
    public function plugins($Filter = '', $PluginName = '', $TransientKey = '') {
        $this->permission('Garden.Settings.Manage');

        // Page setup
        $this->addJsFile('addons.js');
        $this->title(t('Plugins'));
        $this->setHighlightRoute('dashboard/settings/plugins');

        // Validate and set properties
        $Session = Gdn::session();
        if ($PluginName && !$Session->validateTransientKey($TransientKey)) {
            $PluginName = '';
        }

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
            try {
                $this->EventArguments['PluginName'] = $PluginName;
                if (array_key_exists($PluginName, $this->EnabledPlugins) === true) {
                    Gdn::pluginManager()->disablePlugin($PluginName);
                    Gdn_LibraryMap::clearCache();
                    $this->fireEvent('AfterDisablePlugin');
                } else {
                    $Validation = new Gdn_Validation();
                    if (!Gdn::pluginManager()->enablePlugin($PluginName, $Validation)) {
                        $this->Form->setValidationResults($Validation->results());
                    } else {
                        Gdn_LibraryMap::ClearCache();
                    }

                    $this->EventArguments['Validation'] = $Validation;
                    $this->fireEvent('AfterEnablePlugin');
                }
            } catch (Exception $e) {
                $this->Form->addError($e);
            }
            if ($this->Form->errorCount() == 0) {
                redirect('/settings/plugins/'.$this->Filter);
            }
        }
        $this->render();
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
        $this->setData('EnabledThemeFolder', val('Folder', $ThemeInfo));
        $this->setData('EnabledTheme', Gdn::themeManager()->enabledThemeInfo());
        $this->setData('EnabledThemeName', val('Name', $ThemeInfo, val('Index', $ThemeInfo)));

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

                Gdn::session()->setPreference(array('PreviewThemeName' => '', 'PreviewThemeFolder' => '')); // Clear out the preview
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
     */
    public function previewTheme($ThemeName = '') {
        $this->permission('Garden.Settings.Manage');
        $ThemeInfo = Gdn::themeManager()->getThemeInfo($ThemeName);

        $PreviewThemeName = $ThemeName;
        $PreviewThemeFolder = val('Folder', $ThemeInfo);
        $IsMobile = val('IsMobile', $ThemeInfo);

        // If we failed to get the requested theme, cancel preview
        if ($ThemeInfo === false) {
            $PreviewThemeName = '';
            $PreviewThemeFolder = '';
        }

        Gdn::session()->setPreference(array(
            'PreviewThemeName' => $PreviewThemeName,
            'PreviewThemeFolder' => $PreviewThemeFolder,
            'PreviewIsMobile' => $IsMobile
        ));

        redirect('/');
    }

    /**
     * Closes current theme preview.
     *
     * @since 2.0.0
     * @access public
     */
    public function cancelPreview() {
        $Session = Gdn::session();
        $IsMobile = $Session->User->Preferences['PreviewIsMobile'];
        $Session->setPreference(array('PreviewThemeName' => '', 'PreviewThemeFolder' => '', 'PreviewIsMobile' => ''));

        if ($IsMobile) {
            redirect('settings/mobilethemes');
        } else {
            redirect('settings/themes');
        }
    }

    /**
     * Remove the logo from config & delete it.
     *
     * @since 2.1
     * @param string $TransientKey Security token.
     */
    public function removeFavicon($TransientKey = '') {
        $Session = Gdn::session();
        if ($Session->validateTransientKey($TransientKey) && $Session->checkPermission('Garden.Community.Manage')) {
            $Favicon = c('Garden.FavIcon', '');
            RemoveFromConfig('Garden.FavIcon');
            $Upload = new Gdn_Upload();
            $Upload->delete($Favicon);
        }

        redirect('/settings/banner');
    }

    /**
     * Remove the share image from config & delete it.
     *
     * @since 2.1
     * @param string $TransientKey Security token.
     */
    public function removeShareImage($TransientKey = '') {
        $this->permission('Garden.Community.Manage');

        if (Gdn::request()->isAuthenticatedPostBack()) {
            $ShareImage = c('Garden.ShareImage', '');
            removeFromConfig('Garden.ShareImage');
            $Upload = new Gdn_Upload();
            $Upload->delete($ShareImage);
        }

        $this->RedirectUrl = '/settings/banner';
        $this->render('Blank', 'Utility');
    }


    /**
     * Remove the logo from config & delete it.
     *
     * @since 2.0.0
     * @access public
     * @param string $TransientKey Security token.
     */
    public function removeLogo($TransientKey = '') {
        $Session = Gdn::session();
        if ($Session->validateTransientKey($TransientKey) && $Session->checkPermission('Garden.Community.Manage')) {
            $Logo = c('Garden.Logo', '');
            RemoveFromConfig('Garden.Logo');
            safeUnlink(PATH_ROOT."/$Logo");
        }

        redirect('/settings/banner');
    }

    /**
     * Remove the mobile logo from config & delete it.
     *
     * @since 2.0.0
     * @access public
     * @param string $TransientKey Security token.
     */
    public function removeMobileLogo($TransientKey = '') {
        $Session = Gdn::session();
        if ($Session->validateTransientKey($TransientKey) && $Session->checkPermission('Garden.Community.Manage')) {
            $MobileLogo = c('Garden.MobileLogo', '');
            RemoveFromConfig('Garden.MobileLogo');
            safeUnlink(PATH_ROOT."/$MobileLogo");
        }

        redirect('/settings/banner');
    }


    /**
     * Remove the default avatar from config & delete it.
     *
     * @since 2.0.0
     * @access public
     * @param string $transientKey Security token.
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
        $this->render();
    }

    /**
     *
     *
     * @param string $Tutorial
     */
    public function tutorials($Tutorial = '') {
        $this->setData('Title', t('Help &amp; Tutorials'));
        $this->setHighlightRoute('dashboard/settings/tutorials');
        $this->setData('CurrentTutorial', $Tutorial);
        Gdn_Theme::section('Tutorials');
        $this->render();
    }
}
