<?php
/**
 * Manages installation of Dashboard.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /setup endpoint.
 */
class SetupController extends DashboardController {

    /** @var array Models to automatically instantiate. */
    public $Uses = array('Form', 'Database');

    /**
     * Add CSS & module, set error master view. Automatically run on every use.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        $this->Head = new HeadModule($this);
        $this->addCssFile('setup.css');
        $this->addJsFile('jquery.js');
        // Make sure all errors are displayed.
        saveToConfig('Garden.Errors.MasterView', 'deverror.master.php', array('Save' => false));
    }

    /**
     * The summary of all settings available.
     *
     * The menu items displayed here are collected from each application's
     * application controller and all plugin's definitions.
     *
     * @since 2.0.0
     * @access public
     */
    public function index() {
        $this->ApplicationFolder = 'dashboard';
        $this->MasterView = 'setup';
        // Fatal error if Garden has already been installed.
        $Installed = c('Garden.Installed');
        if ($Installed) {
            $this->View = "AlreadyInstalled";
            $this->render();
            return;
        }

        if (!$this->_CheckPrerequisites()) {
            $this->View = 'prerequisites';
        } else {
            $this->View = 'configure';

            // Make sure the user has copied the htaccess file over.
            if (!file_exists(PATH_ROOT.'/.htaccess') && !$this->Form->getFormValue('SkipHtaccess')) {
                $this->setData('NoHtaccess', true);
                $this->Form->addError(t('You are missing Vanilla\'s .htaccess file.', 'You are missing Vanilla\'s <b>.htaccess</b> file. Sometimes this file isn\'t copied if you are using ftp to upload your files because this file is hidden. Make sure you\'ve copied the <b>.htaccess</b> file before continuing.'));
            }

            $ApplicationManager = new Gdn_ApplicationManager();

            // Need to go through all of the setups for each application. Garden,
            if ($this->configure() && $this->Form->isPostBack()) {
                // Get list of applications to enable during install
                // Override by creating the config and adding this setting before install begins
                $AppNames = c('Garden.Install.Applications', array('Conversations', 'Vanilla'));
                try {
                    // Step through the available applications, enabling each of them.
                    foreach ($AppNames as $AppName) {
                        $Validation = new Gdn_Validation();
                        $ApplicationManager->RegisterPermissions($AppName, $Validation);
                        $ApplicationManager->EnableApplication($AppName, $Validation);
                    }

                    Gdn::pluginManager()->start(true);
                } catch (Exception $ex) {
                    $this->Form->addError($ex);
                }
                if ($this->Form->errorCount() == 0) {
                    // Save a variable so that the application knows it has been installed.
                    // Now that the application is installed, select a more user friendly error page.
                    $Config = array('Garden.Installed' => true);
                    saveToConfig($Config);
                    $this->fireEvent('Installed');

                    PermissionModel::ResetAllRoles();

                    // Go to the dashboard
                    redirect('/settings/gettingstarted');
                }
            }
        }
        $this->render();
    }

    /**
     * Allows the configuration of basic setup information in Garden. This
     * should not be functional after the application has been set up.
     *
     * @since 2.0.0
     * @access public
     * @param string $RedirectUrl Where to send user afterward.
     */
    private function configure($RedirectUrl = '') {
        // Create a model to save configuration settings
        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->setField(array('Garden.Locale', 'Garden.Title', 'Garden.WebRoot', 'Garden.Cookie.Salt', 'Garden.Cookie.Domain', 'Database.Name', 'Database.Host', 'Database.User', 'Database.Password', 'Garden.Registration.ConfirmEmail', 'Garden.Email.SupportName'));

        // Set the models on the forms.
        $this->Form->setModel($ConfigurationModel);

        // Load the locales for the locale dropdown
        // $Locale = Gdn::locale();
        // $AvailableLocales = $Locale->GetAvailableLocaleSources();
        // $this->LocaleData = array_combine($AvailableLocales, $AvailableLocales);

        // If seeing the form for the first time...
        if (!$this->Form->isPostback()) {
            // Force the webroot using our best guesstimates
            $ConfigurationModel->Data['Database.Host'] = 'localhost';
            $this->Form->setData($ConfigurationModel->Data);
        } else {
            // Define some validation rules for the fields being saved
            $ConfigurationModel->Validation->applyRule('Database.Name', 'Required', 'You must specify the name of the database in which you want to set up Vanilla.');

            // Let's make some user-friendly custom errors for database problems
            $DatabaseHost = $this->Form->getFormValue('Database.Host', '~~Invalid~~');
            $DatabaseName = $this->Form->getFormValue('Database.Name', '~~Invalid~~');
            $DatabaseUser = $this->Form->getFormValue('Database.User', '~~Invalid~~');
            $DatabasePassword = $this->Form->getFormValue('Database.Password', '~~Invalid~~');
            $ConnectionString = GetConnectionString($DatabaseName, $DatabaseHost);
            try {
                $Connection = new PDO(
                    $ConnectionString,
                    $DatabaseUser,
                    $DatabasePassword
                );
            } catch (PDOException $Exception) {
                switch ($Exception->getCode()) {
                    case 1044:
                        $this->Form->addError(t('The database user you specified does not have permission to access the database. Have you created the database yet? The database reported: <code>%s</code>'), strip_tags($Exception->getMessage()));
                        break;
                    case 1045:
                        $this->Form->addError(t('Failed to connect to the database with the username and password you entered. Did you mistype them? The database reported: <code>%s</code>'), strip_tags($Exception->getMessage()));
                        break;
                    case 1049:
                        $this->Form->addError(t('It appears as though the database you specified does not exist yet. Have you created it yet? Did you mistype the name? The database reported: <code>%s</code>'), strip_tags($Exception->getMessage()));
                        break;
                    case 2005:
                        $this->Form->addError(t("Are you sure you've entered the correct database host name? Maybe you mistyped it? The database reported: <code>%s</code>"), strip_tags($Exception->getMessage()));
                        break;
                    default:
                        $this->Form->addError(sprintf(t('ValidateConnection'), strip_tags($Exception->getMessage())));
                        break;
                }
            }

            $ConfigurationModel->Validation->applyRule('Garden.Title', 'Required');

            $ConfigurationFormValues = $this->Form->formValues();
            if ($ConfigurationModel->validate($ConfigurationFormValues) !== true || $this->Form->errorCount() > 0) {
                // Apply the validation results to the form(s)
                $this->Form->setValidationResults($ConfigurationModel->validationResults());
            } else {
                $Host = array_shift(explode(':', Gdn::request()->requestHost()));
                $Domain = Gdn::request()->domain();

                // Set up cookies now so that the user can be signed in.
                $ExistingSalt = c('Garden.Cookie.Salt', false);
                $ConfigurationFormValues['Garden.Cookie.Salt'] = ($ExistingSalt) ? $ExistingSalt : betterRandomString(16, 'Aa0');
                $ConfigurationFormValues['Garden.Cookie.Domain'] = ''; // Don't set this to anything by default. # Tim - 2010-06-23
                // Additional default setup values.
                $ConfigurationFormValues['Garden.Registration.ConfirmEmail'] = true;
                $ConfigurationFormValues['Garden.Email.SupportName'] = $ConfigurationFormValues['Garden.Title'];

                $ConfigurationModel->save($ConfigurationFormValues, true);

                // If changing locale, redefine locale sources:
                $NewLocale = 'en-CA'; // $this->Form->getFormValue('Garden.Locale', false);
                if ($NewLocale !== false && Gdn::config('Garden.Locale') != $NewLocale) {
                    $ApplicationManager = new Gdn_ApplicationManager();
                    $Locale = Gdn::locale();
                    $Locale->set($NewLocale, $ApplicationManager->enabledApplicationFolders(), Gdn::pluginManager()->enabledPluginFolders(), true);
                }

                // Install db structure & basic data.
                $Database = Gdn::database();
                $Database->init();
                $Drop = false;
                $Explicit = false;
                try {
                    include(PATH_APPLICATIONS.DS.'dashboard'.DS.'settings'.DS.'structure.php');
                } catch (Exception $ex) {
                    $this->Form->addError($ex);
                }

                if ($this->Form->errorCount() > 0) {
                    return false;
                }

                // Create the administrative user
                $UserModel = Gdn::userModel();
                $UserModel->defineSchema();
                $UsernameError = t('UsernameError', 'Username can only contain letters, numbers, underscores, and must be between 3 and 20 characters long.');
                $UserModel->Validation->applyRule('Name', 'Username', $UsernameError);
                $UserModel->Validation->applyRule('Name', 'Required', t('You must specify an admin username.'));
                $UserModel->Validation->applyRule('Password', 'Required', t('You must specify an admin password.'));
                $UserModel->Validation->applyRule('Password', 'Match');
                $UserModel->Validation->applyRule('Email', 'Email');

                if (!($AdminUserID = $UserModel->SaveAdminUser($ConfigurationFormValues))) {
                    $this->Form->setValidationResults($UserModel->validationResults());
                } else {
                    // The user has been created successfully, so sign in now.
                    saveToConfig('Garden.Installed', true, array('Save' => false));
                    Gdn::session()->start($AdminUserID, true);
                    saveToConfig('Garden.Installed', false, array('Save' => false));
                }

                if ($this->Form->errorCount() > 0) {
                    return false;
                }

                // Assign some extra settings to the configuration file if everything succeeded.
                $ApplicationInfo = array();
                include(CombinePaths(array(PATH_APPLICATIONS.DS.'dashboard'.DS.'settings'.DS.'about.php')));

                // Detect Internet connection for CDNs
                $Disconnected = !(bool)@fsockopen('ajax.googleapis.com', 80);

                saveToConfig(array(
                    'Garden.Version' => arrayValue('Version', val('Dashboard', $ApplicationInfo, array()), 'Undefined'),
                    'Garden.Cdns.Disable' => $Disconnected,
                    'Garden.CanProcessImages' => function_exists('gd_info'),
                    'EnabledPlugins.GettingStarted' => 'GettingStarted', // Make sure the getting started plugin is enabled
                    'EnabledPlugins.HtmLawed' => 'HtmLawed' // Make sure html purifier is enabled so html has a default way of being safely parsed.
                ));
            }
        }
        return $this->Form->errorCount() == 0 ? true : false;
    }

    /**
     * Check minimum requirements for Garden.
     *
     * @since 2.0.0
     * @access private
     * @return bool Whether platform passes requirement check.
     */
    private function _CheckPrerequisites() {
        // Make sure we are running at least PHP 5.1
        if (version_compare(phpversion(), ENVIRONMENT_PHP_VERSION) < 0) {
            $this->Form->addError(sprintf(t('You are running PHP version %1$s. Vanilla requires PHP %2$s or greater. You must upgrade PHP before you can continue.'), phpversion(), ENVIRONMENT_PHP_VERSION));
        }

        // Make sure PDO is available
        if (!class_exists('PDO')) {
            $this->Form->addError(t('You must have the PDO module enabled in PHP in order for Vanilla to connect to your database.'));
        }

        if (!defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
            $this->Form->addError(t('You must have the MySQL driver for PDO enabled in order for Vanilla to connect to your database.'));
        }

        // Make sure that the correct filesystem permissions are in place
        $PermissionProblem = false;

        // Make sure the appropriate folders are writeable.
        $ProblemDirectories = array();
        if (!is_readable(PATH_CONF) || !IsWritable(PATH_CONF)) {
            $ProblemDirectories[] = PATH_CONF;
        }

        if (!is_readable(PATH_UPLOADS) || !IsWritable(PATH_UPLOADS)) {
            $ProblemDirectories[] = PATH_UPLOADS;
        }

        if (!is_readable(PATH_CACHE) || !IsWritable(PATH_CACHE)) {
            $ProblemDirectories[] = PATH_CACHE;
        }

        if (count($ProblemDirectories) > 0) {
            $PermissionProblem = true;

            $PermissionError = t(
                'Some folders don\'t have correct permissions.',
                '<p>Some of your folders do not have the correct permissions.</p><p>Using your ftp client, or via command line, make sure that the following permissions are set for your vanilla installation:</p>'
            );

            $PermissionHelp = '<pre>chmod -R 777 '.implode("\nchmod -R 777 ", $ProblemDirectories).'</pre>';

            $this->Form->addError($PermissionError.$PermissionHelp);
        }

        // Make sure the config folder is writeable
        if (!$PermissionProblem) {
            $ConfigFile = Gdn::config()->DefaultPath();
            if (!file_exists($ConfigFile)) {
                file_put_contents($ConfigFile, '');
            }

            // Make sure the config file is writeable
            if (!is_readable($ConfigFile) || !IsWritable($ConfigFile)) {
                $this->Form->addError(sprintf(t('Your configuration file does not have the correct permissions. PHP needs to be able to read and write to this file: <code>%s</code>'), $ConfigFile));
                $PermissionProblem = true;
            }
        }

        // Make sure the cache folder is writeable
        if (!$PermissionProblem) {
            if (!file_exists(PATH_CACHE.'/Smarty')) {
                mkdir(PATH_CACHE.'/Smarty');
            }
            if (!file_exists(PATH_CACHE.'/Smarty/cache')) {
                mkdir(PATH_CACHE.'/Smarty/cache');
            }
            if (!file_exists(PATH_CACHE.'/Smarty/compile')) {
                mkdir(PATH_CACHE.'/Smarty/compile');
            }
        }

        return $this->Form->errorCount() == 0 ? true : false;
    }

    public function testUrlRewrites() {
        die('ok');
    }
}
