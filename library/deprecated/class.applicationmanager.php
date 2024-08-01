<?php
/**
 * Application Manager
 *
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

use Garden\Web\Exception\NotFoundException;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Models\AddonModel;

/**
 * Manages available applications, enabling and disabling them.
 *
 * @deprecated 3.0 Use Vanilla\AddonManager.
 */
class Gdn_ApplicationManager
{
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
    public function __construct(AddonManager $addonManager = null)
    {
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
    public function availableApplications()
    {
        if (!is_array($this->availableApplications)) {
            $applications = [];
            $addons = $this->addonManager->lookupAllByType(Addon::TYPE_ADDON);

            foreach ($addons as $addon) {
                /* @var Addon $addon */
                if ($addon->getInfoValue("oldType") !== "application") {
                    continue;
                }

                $info = $this->calcOldInfoArray($addon);
                $applications[$info["Index"]] = $info;
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
    public function enabledApplications()
    {
        if (!is_array($this->enabledApplications)) {
            $applications = [];
            $addons = $this->addonManager->getEnabled();

            foreach ($addons as $addon) {
                /* @var Addon $addon */
                if ($addon->getInfoValue("oldType") !== "application") {
                    continue;
                }

                $info = $this->calcOldInfoArray($addon);
                $applications[$info["Index"]] = $info;
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
    private function calcOldInfoArray(Addon $addon)
    {
        $info = Gdn_pluginManager::calcOldInfoArray($addon);
        $directories = explode("/", $addon->getSubdir());
        $info["Folder"] = $directories[count($directories) - 1];

        return $info;
    }

    /**
     * Check to see if an application is enabled.
     *
     * @param string $applicationName The name of the application to check.
     * @return bool Returns true if the application is enabled, otherwise false.
     */
    public function checkApplication($applicationName)
    {
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
    public function getApplicationInfo($applicationName, $key = null)
    {
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
    public function availableVisibleApplications()
    {
        $availableApplications = $this->availableApplications();
        foreach ($availableApplications as $applicationName => $info) {
            if (!val("AllowEnable", $info, true) || !val("AllowDisable", $info, true)) {
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
    public function enabledVisibleApplications()
    {
        $availableApplications = $this->availableApplications();
        $enabledApplications = $this->enabledApplications();
        foreach ($availableApplications as $applicationName => $info) {
            if (array_key_exists($applicationName, $enabledApplications)) {
                if (!val("AllowEnable", $info, true) || !val("AllowDisable", $info, true)) {
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
    public function enabledApplicationFolders()
    {
        deprecated("Gdn_ApplicationManager->enabledApplicationFolders()");

        $addons = $this->addonManager->getEnabled();
        $applications = array_filter($addons, Addon::makeFilterCallback(["oldType" => "application"]));

        $result = ["dashboard"];
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
    public function checkRequirements($applicationName)
    {
        $availableApplications = $this->availableApplications();
        $requiredApplications = val("RequiredApplications", val($applicationName, $availableApplications, []), false);
        $enabledApplications = $this->enabledApplications();
        checkRequirements($applicationName, $requiredApplications, $enabledApplications, "application");
    }

    /**
     * Enable an application.
     *
     * @param string $addonKey The name of the application to enable.
     */
    public function enableApplication(string $addonKey)
    {
        $addonModel = \Gdn::getContainer()->get(AddonModel::class);
        $addon = $this->addonManager->lookupAddon($addonKey);
        if ($addon === null) {
            throw new NotFoundException("Application", [
                "addonKey" => $addonKey,
            ]);
        }

        $addonModel->enable($addon);
    }

    /**
     * Disable an application.
     *
     * @param string $addonKey The name of the application to disable.
     * @throws \Exception Throws an exception if the application can't be disabled.
     */
    public function disableApplication($addonKey)
    {
        $addonModel = \Gdn::getContainer()->get(AddonModel::class);
        $addon = $this->addonManager->lookupAddon($addonKey);
        if ($addon === null) {
            throw new NotFoundException("Application", [
                "addonKey" => $addonKey,
            ]);
        }

        $addonModel->disable($addon);
    }

    /**
     * Check whether or not an application is enabled.
     *
     * @param string $name The name of the application.
     * @return bool Whether or not the application is enabled.
     * @since 2.2
     * @deprecated
     */
    public function isEnabled($name)
    {
        deprecated("Gdn_ApplicationManager->isEnabled()", "AddonManager->isEnabled()");
        return $this->addonManager->isEnabled($name, Addon::TYPE_ADDON);
    }

    /**
     * Define the permissions for an application.
     *
     * @param string $applicationName The name of the application.
     */
    public function registerPermissions($applicationName)
    {
        $addon = $this->addonManager->lookupAddon($applicationName);

        if ($permissions = $addon->getInfoValue("registerPermissions")) {
            Gdn::permissionModel()->define($permissions);
        }
    }
}
