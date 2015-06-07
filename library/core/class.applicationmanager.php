<?php
/**
 * Application Manager
 *
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Manages available applications, enabling and disabling them.
 */
class Gdn_ApplicationManager {

    /** @var array Available applications. Never access this directly, instead use $this->AvailableApplications(); */
    private $availableApplications = null;

    /** @var array Enabled applications. Never access this directly, instead use $this->EnabledApplications(); */
    private $enabledApplications = null;

    /** @var array The valid paths to search for applications. */
    public $Paths = array(PATH_APPLICATIONS);

    /**
     * Get a list of the available applications.
     *
     * Looks through the root Garden directory for valid applications and
     * returns them as an associative array of "Application Name" =>
     * "Application Info Array". It also adds a "Folder" definition to the
     * Application Info Array for each application.
     */
    public function availableApplications() {
        if (!is_array($this->availableApplications)) {
            $ApplicationInfo = array();

            $AppFolders = Gdn_FileSystem::folders(PATH_APPLICATIONS); // Get an array of all application folders
            // Now look for about files within them.
            $ApplicationAboutFiles = Gdn_FileSystem::findAll(PATH_APPLICATIONS, 'settings'.DS.'about.php', $AppFolders);
            // Include them all right here and fill the application info array
            $ApplicationCount = count($ApplicationAboutFiles);
            for ($i = 0; $i < $ApplicationCount; ++$i) {
                include($ApplicationAboutFiles[$i]);

                // Define the folder name for the newly added item
                foreach ($ApplicationInfo as $ApplicationName => $Info) {
                    if (array_key_exists('Folder', $ApplicationInfo[$ApplicationName]) === false) {
                        $Folder = substr($ApplicationAboutFiles[$i], strlen(PATH_APPLICATIONS));
                        if (substr($Folder, 0, 1) == DS) {
                            $Folder = substr($Folder, 1);
                        }

                        $Folder = substr($Folder, 0, strpos($Folder, DS));
                        $ApplicationInfo[$ApplicationName]['Folder'] = $Folder;
                    }
                }
            }
            // Add all of the indexes to the applications.
            foreach ($ApplicationInfo as $Index => &$Info) {
                $Info['Index'] = $Index;
            }

            $this->availableApplications = $ApplicationInfo;
        }

        return $this->availableApplications;
    }

    /**
     * Gets an array of all of the enabled applications.
     *
     * @return array
     */
    public function enabledApplications() {
        if (!is_array($this->enabledApplications)) {
            $EnabledApplications = Gdn::config('EnabledApplications', array('Dashboard' => 'dashboard'));
            // Add some information about the applications to the array.
            foreach ($EnabledApplications as $Name => $Folder) {
                $EnabledApplications[$Name] = array('Folder' => $Folder);
                //$EnabledApplications[$Name]['Version'] = Gdn::Config($Name.'.Version', '');
                $EnabledApplications[$Name]['Version'] = '';
                $EnabledApplications[$Name]['Index'] = $Name;
                // Get the application version from it's about file.
                $AboutPath = PATH_APPLICATIONS.'/'.strtolower($Name).'/settings/about.php';
                if (file_exists($AboutPath)) {
                    $ApplicationInfo = array();
                    include $AboutPath;
                    $EnabledApplications[$Name]['Version'] = GetValueR("$Name.Version", $ApplicationInfo, '');
                }
            }
            $this->enabledApplications = $EnabledApplications;
        }

        return $this->enabledApplications;
    }

    /**
     * Check to see if an application is enabled.
     *
     * @param string $applicationName The name of the application to check.
     * @return bool Returns true if the application is enabled, otherwise false.
     */
    public function checkApplication($applicationName) {
        if (array_key_exists($applicationName, $this->enabledApplications())) {
            return true;
        }

        return false;
    }

    /**
     * Get the information about an application.
     *
     * @param string $applicationName The name of the application to lookup.
     * @param string $key The key of a field in the application info to return.
     * @return bool|mixed Returns the application's info, a specific value, or false if the application cannot be found.
     */
    public function getApplicationInfo($applicationName, $key = null) {
        $ApplicationInfo = val($applicationName, $this->availableApplications(), null);
        if (is_null($ApplicationInfo)) {
            return false;
        }

        if (!is_null($key)) {
            return GetValueR($key, $ApplicationInfo, false);
        }

        return $ApplicationInfo;
    }

    /**
     * Get a list of applications that are not marked as invisible.
     *
     * @return array Returns an array of application info arrays.
     */
    public function availableVisibleApplications() {
        $AvailableApplications = $this->availableApplications();
        foreach ($AvailableApplications as $ApplicationName => $Info) {
            if (!val('AllowEnable', $Info, true) || !val('AllowDisable', $Info, true)) {
                unset($AvailableApplications[$ApplicationName]);
            }
        }
        return $AvailableApplications;
    }

    /**
     * Get a list of applications that are enabled and not marked as invisible.
     *
     * @return array Returns an array of application info arrays.
     */
    public function enabledVisibleApplications() {
        $AvailableApplications = $this->availableApplications();
        $EnabledApplications = $this->enabledApplications();
        foreach ($AvailableApplications as $ApplicationName => $Info) {
            if (array_key_exists($ApplicationName, $EnabledApplications)) {
                if (!val('AllowEnable', $Info, true) || !val('AllowDisable', $Info, true)) {
                    unset($AvailableApplications[$ApplicationName]);
                }
            } else {
                unset($AvailableApplications[$ApplicationName]);
            }
        }
        return $AvailableApplications;
    }

    /**
     * Get an list of enabled application folders.
     *
     * @return array Returns an array of all of the enabled application folders.
     */
    public function enabledApplicationFolders() {
        $EnabledApplications = c('EnabledApplications', array());
        $EnabledApplications['Dashboard'] = 'dashboard';
        return array_values($EnabledApplications);
    }

    /**
     * Check that the requirements for an application have been enabled.
     *
     * @param string $applicationName The name of the application to check.
     */
    public function checkRequirements($applicationName) {
        $AvailableApplications = $this->availableApplications();
        $RequiredApplications = val(
            'RequiredApplications',
            val($applicationName, $AvailableApplications, array()),
            false
        );
        $EnabledApplications = $this->enabledApplications();
        checkRequirements($applicationName, $RequiredApplications, $EnabledApplications, 'application');
    }

    /**
     * Enable an application.
     *
     * @param string $applicationName The name of the application to enable.
     * @return bool Returns true if the application was enabled or false otherwise.
     */
    public function enableApplication($applicationName) {
        $this->testApplication($applicationName);
        $ApplicationInfo = ArrayValueI($applicationName, $this->availableApplications(), array());
        $applicationName = $ApplicationInfo['Index'];
        $ApplicationFolder = val('Folder', $ApplicationInfo, '');

        SaveToConfig('EnabledApplications'.'.'.$applicationName, $ApplicationFolder);
        Logger::event(
            'addon_enabled',
            Logger::NOTICE,
            'The {addonName} application was enabled.',
            array('addonName' => $applicationName)
        );

        // Redefine the locale manager's settings $Locale->Set($CurrentLocale, $EnabledApps, $EnabledPlugins, true);
        $Locale = Gdn::locale();
        $Locale->set(
            $Locale->current(),
            $this->enabledApplicationFolders(),
            Gdn::pluginManager()->enabledPluginFolders(),
            true
        );

        $this->EventArguments['AddonName'] = $applicationName;
        Gdn::pluginManager()->callEventHandlers($this, 'ApplicationManager', 'AddonEnabled');

        return true;
    }

    /**
     * Test if an application can be enabled.
     *
     * @param string $applicationName The name of the application to test.
     * @return bool Returns true if the application can be enabled or false otherwise.
     * @throws Exception Throws an exception if the application is not in the correct format.
     */
    public function testApplication($applicationName) {
        // Add the application to the $EnabledApplications array in conf/applications.php
        $ApplicationInfo = arrayValueI($applicationName, $this->availableApplications(), array());
        $applicationName = $ApplicationInfo['Index'];
        $ApplicationFolder = val('Folder', $ApplicationInfo, '');
        if ($ApplicationFolder == '') {
            throw new Exception(t('The application folder was not properly defined.'));
        }

        // Hook directly into the autoloader and force it to load the newly tested application
        Gdn_Autoloader::attachApplication($ApplicationFolder);

        // Call the application's setup method
        $hooks = $applicationName.'Hooks';
        if (!class_exists($hooks)) {
            $hooksPath = PATH_APPLICATIONS.DS.$ApplicationFolder.'/settings/class.hooks.php';
            if (file_exists($hooksPath)) {
                include_once $hooksPath;
            }
        }
        if (class_exists($hooks)) {
            /* @var Gdn_IPlugin $hooks The hooks object should be a plugin. */
            $hooks = new $hooks();

            if (method_exists($hooks, 'setup')) {
                $hooks->setup();
            }
        }

        return true;
    }

    /**
     * Disable an application.
     *
     * @param string $applicationName The name of the application to disable.
     * @throws \Exception Throws an exception if the application can't be disabled.
     */
    public function disableApplication($applicationName) {
        // 1. Check to make sure that this application is allowed to be disabled
        $ApplicationInfo = (array)arrayValueI($applicationName, $this->availableApplications(), array());
        $applicationName = $ApplicationInfo['Index'];
        if (!val('AllowDisable', $ApplicationInfo, true)) {
            throw new Exception(sprintf(t('You cannot disable the %s application.'), $applicationName));
        }

        // 2. Check to make sure that no other enabled applications rely on this one
        foreach ($this->enabledApplications() as $CheckingName => $CheckingInfo) {
            $RequiredApplications = val('RequiredApplications', $CheckingInfo, false);
            if (is_array($RequiredApplications) && array_key_exists($applicationName, $RequiredApplications) === true) {
                throw new Exception(
                    sprintf(
                        t('You cannot disable the %1$s application because the %2$s application requires it in order to function.'),
                        $applicationName,
                        $CheckingName
                    )
                );
            }
        }

        // 2. Disable it
        removeFromConfig("EnabledApplications.{$applicationName}");

        Logger::event(
            'addon_disabled',
            Logger::NOTICE,
            'The {addonName} application was disabled.',
            array('addonName' => $applicationName)
        );

        // Clear the object caches.
        Gdn_Autoloader::smartFree(Gdn_Autoloader::CONTEXT_APPLICATION, $ApplicationInfo);

        // Redefine the locale manager's settings $Locale->Set($CurrentLocale, $EnabledApps, $EnabledPlugins, true);
        $Locale = Gdn::locale();
        $Locale->set(
            $Locale->current(),
            $this->enabledApplicationFolders(),
            Gdn::pluginManager()->enabledPluginFolders(),
            true
        );

        $this->EventArguments['AddonName'] = $applicationName;
        Gdn::pluginManager()->callEventHandlers($this, 'ApplicationManager', 'AddonDisabled');
    }

    /**
     * Check whether or not an application is enabled.
     *
     * @param string $Name The name of the application.
     * @return bool Whether or not the application is enabled.
     * @since 2.2
     */
    public function isEnabled($Name) {
        $Enabled = $this->enabledApplications();
        return isset($Enabled[$Name]) && $Enabled[$Name];
    }

    /**
     * Define the permissions for an application.
     *
     * @param string $applicationName The name of the application.
     */
    public function registerPermissions($applicationName) {
        $ApplicationInfo = val($applicationName, $this->availableApplications(), array());
        $PermissionName = val('RegisterPermissions', $ApplicationInfo, false);
        if ($PermissionName != false) {
            $PermissionModel = Gdn::permissionModel();
            $PermissionModel->define($PermissionName);
        }
    }
}
