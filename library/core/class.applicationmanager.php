<?php
/**
 * Application Manager
 *
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */
use Vanilla\Addon;
use Vanilla\AddonManager;

/**
 * Manages available applications, enabling and disabling them.
 */
class Gdn_ApplicationManager {

    /** @var array Available applications. Never access this directly, instead use $this->availableApplications(); */
    private $availableApplications = null;

    /** @var array Enabled applications. Never access this directly, instead use $this->enabledApplications(); */
    private $enabledApplications = null;

    /** @var array The valid paths to search for applications. */
    public $Paths = [PATH_APPLICATIONS];

    /**
     * @var AddonManager
     */
    private $addonManager;

    /**
     *
     */
    public function __construct(AddonManager $addonManager = null) {
        $this->addonManager = $addonManager;
    }

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
            $applications = [];
            $addons = $this->addonManager->lookupAllByType(Addon::TYPE_ADDON);

            foreach ($addons as $addon) {
                /* @var Addon $addon */
                if ($addon->getInfoValue('oldType') !== 'application') {
                    continue;
                }

                $info = $this->calcOldInfoArray($addon);
                $applications[$info['Index']] = $info;
            }

            $this->availableApplications = $applications;
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
            $applications = [];
            $addons = $this->addonManager->getEnabled();

            foreach ($addons as $addon) {
                /* @var Addon $addon */
                if ($addon->getInfoValue('oldType') !== 'application') {
                    continue;
                }

                $info = $this->calcOldInfoArray($addon);
                $applications[$info['Index']] = $info;
            }

            $this->enabledApplications = $applications;
        }

        return $this->enabledApplications;
    }

    /**
     * Calculate old application's info.
     *
     * @param Addon $addon
     * @return array the old information.
     */
    private function calcOldInfoArray(Addon $addon) {
        $info = Gdn_pluginManager::calcOldInfoArray($addon);
        $directories = explode(DS, $addon->getSubdir());
        $info['Folder'] = $directories[count($directories) - 1];

        return $info;
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
        $applicationInfo = val($applicationName, $this->availableApplications(), null);
        if (is_null($applicationInfo)) {
            return false;
        }

        if (!is_null($key)) {
            return getValueR($key, $applicationInfo, false);
        }

        return $applicationInfo;
    }

    /**
     * Get a list of applications that are not marked as invisible.
     *
     * @return array Returns an array of application info arrays.
     */
    public function availableVisibleApplications() {
        $availableApplications = $this->availableApplications();
        foreach ($availableApplications as $applicationName => $info) {
            if (!val('AllowEnable', $info, true) || !val('AllowDisable', $info, true)) {
                unset($availableApplications[$applicationName]);
            }
        }
        return $availableApplications;
    }

    /**
     * Get a list of applications that are enabled and not marked as invisible.
     *
     * @return array Returns an array of application info arrays.
     */
    public function enabledVisibleApplications() {
        $availableApplications = $this->availableApplications();
        $enabledApplications = $this->enabledApplications();
        foreach ($availableApplications as $applicationName => $info) {
            if (array_key_exists($applicationName, $enabledApplications)) {
                if (!val('AllowEnable', $info, true) || !val('AllowDisable', $info, true)) {
                    unset($availableApplications[$applicationName]);
                }
            } else {
                unset($availableApplications[$applicationName]);
            }
        }
        return $availableApplications;
    }

    /**
     * Get an list of enabled application folders.
     *
     * @return array Returns an array of all of the enabled application folders.
     * @deprecated
     */
    public function enabledApplicationFolders() {
        deprecated('Gdn_ApplicationManager->enabledApplicationFolders()');

        $addons = $this->addonManager->getEnabled();
        $applications = array_filter($addons, Addon::makeFilterCallback(['oldType' => 'application']));

        $result = ['dashboard'];
        /* @var Addon $application */
        foreach ($applications as $application) {
            $result[] = $application->getKey();
        }
        return array_unique($result);
    }

    /**
     * Check that the requirements for an application have been enabled.
     *
     * @param string $applicationName The name of the application to check.
     */
    public function checkRequirements($applicationName) {
        $availableApplications = $this->availableApplications();
        $requiredApplications = val(
            'RequiredApplications',
            val($applicationName, $availableApplications, []),
            false
        );
        $enabledApplications = $this->enabledApplications();
        checkRequirements($applicationName, $requiredApplications, $enabledApplications, 'application');
    }

    /**
     * Enable an application.
     *
     * @param string $applicationName The name of the application to enable.
     * @return bool Returns true if the application was enabled or false otherwise.
     */
    public function enableApplication($applicationName) {
        $this->testApplication($applicationName);
        $applicationInfo = arrayValueI($applicationName, $this->availableApplications(), []);
        $applicationName = $applicationInfo['Index'];
        $applicationFolder = val('Folder', $applicationInfo, '');

        saveToConfig('EnabledApplications'.'.'.$applicationName, $applicationFolder);
        Logger::event(
            'addon_enabled',
            Logger::NOTICE,
            'The {addonName} application was enabled.',
            ['addonName' => $applicationName]
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
        $ApplicationInfo = arrayValueI($applicationName, $this->availableApplications(), []);
        $applicationName = $ApplicationInfo['Index'];
        $ApplicationFolder = val('Folder', $ApplicationInfo, '');
        if ($ApplicationFolder == '') {
            throw new Exception(t('The application folder was not properly defined.'));
        }

        // Hook directly into the autoloader and force it to load the newly tested application
        $this->addonManager->startAddonsByKey([$applicationName], \Vanilla\Addon::TYPE_ADDON);

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
            $hooks = Gdn::getContainer()->get($hooks);

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
        $addon = $this->addonManager->lookupAddon($applicationName);
        if (!$addon) {
            throw notFoundException('Application');
        }

        $applicationName = $addon->getRawKey();

        // 1. Check to make sure that this application is allowed to be disabled
        if (!$addon->getInfoValue('allowDisable', true)) {
            throw new Exception(sprintf(t('You cannot disable the %s application.'), $applicationName));
        }

        // 2. Check to make sure that no other enabled applications rely on this one.
        try {
            $this->addonManager->checkDependents($addon, true);
        } catch (Exception $ex) {
            throw new Gdn_UserException($ex->getMessage(), $ex->getCode());
        }

        // 2. Disable it
        removeFromConfig("EnabledApplications.{$applicationName}");

        Logger::event(
            'addon_disabled',
            Logger::NOTICE,
            'The {addonName} application was disabled.',
            ['addonName' => $applicationName]
        );

        // Clear the object caches.
        $this->addonManager->stopAddonsByKey([$applicationName], \Vanilla\Addon::TYPE_ADDON);

        /** @var \Garden\EventManager $eventManager */
        $eventManager = Gdn::getContainer()->get(\Garden\EventManager::class);
        $this->addonManager->unbindAddonEvents($addon, $eventManager);
    }

    /**
     * Check whether or not an application is enabled.
     *
     * @param string $name The name of the application.
     * @return bool Whether or not the application is enabled.
     * @since 2.2
     * @deprecated
     */
    public function isEnabled($name) {
        deprecated('Gdn_ApplicationManager->isEnabled()', 'AddonManager->isEnabled()');
        return $this->addonManager->isEnabled($name, Addon::TYPE_ADDON);
    }

    /**
     * Define the permissions for an application.
     *
     * @param string $applicationName The name of the application.
     */
    public function registerPermissions($applicationName) {
        $addon = $this->addonManager->lookupAddon($applicationName);

        if ($permissions = $addon->getInfoValue('registerPermissions')) {
            Gdn::permissionModel()->define($permissions);
        }
    }
}
