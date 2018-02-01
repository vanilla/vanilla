<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Models;

use Garden\Container\Container;
use Garden\EventManager;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Gdn_Configuration;
use PermissionModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Addon;
use Vanilla\AddonManager;

/**
 * Handles addon maintenance within the application.
 *
 * This class is meant to take over the dashboard functionality of the {@link \Gdn_ApplicationManager}, {@link \Gdn_PluginManager},
 * {@link \Gdn_ThemeManager}, and {@link \LocaleModel}. This changes some functionality.
 *
 * - The {@link AddonModel} will enable the requirements of all addons, not just plugins.
 * - The {@link AddonModel} will run the structure file on setup if it hasn't been included. Before, applications where
 *   required to include their structure files.
 * - TODO: The {@link AddonModel} doesn't currently respect restrictions on themes and locales. This should be addressed later.
 */
class AddonModel implements LoggerAwareInterface {
    use LoggerAwareTrait;

    /**
     * @var AddonManager
     */
    private $addonManager;

    /**
     * @var Gdn_Configuration
     */
    private $config;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var EventManager
     */
    private $events;

    /**
     * AddonModel constructor.
     *
     * @param AddonManager $addonManager The addon manager dependency.
     * @param EventManager $events The event manager dependency.
     * @param Gdn_Configuration $config The config dependency.
     * @param Container $container The container dependency.
     */
    public function __construct(
        AddonManager $addonManager,
        EventManager $events,
        Gdn_Configuration $config,
        Container $container
    ) {
        $this->addonManager = $addonManager;
        $this->events = $events;
        $this->config = $config;
        $this->container = $container;
    }

    /**
     * Enable an addon.
     *
     * If the addon has requirements then this might result in the addon's requirements being enabled too.
     *
     * @param Addon $addon The addon to enable.
     * @param array $options Additional options.
     *
     * - **themeType**: Specify "mobile" for the mobile theme.
     * @return Returns an array of all of the addons that were enabled.
     */
    public function enable(Addon $addon, array $options = []) {
        $this->validateEnable($addon, $options);

        // Enable this addon's requirements.
        $requirements = $this->addonManager->lookupRequirements($addon, AddonManager::REQ_DISABLED);
        $requiredAddons = [];
        foreach ($requirements as $addonKey => $row) {
            $requiredAddons[] = $requiredAddon = $this->addonManager->lookupAddon($addonKey);
            $this->enableInternal($requiredAddon, $options);
        }

        $this->enableInternal($addon, $options);

        return array_merge([$addon], array_reverse($requiredAddons));
    }

    /**
     * Validate whether or not an addon can be enabled.
     *
     * @param Addon $addon The addon to enable.
     * @param array $options Additional options for the enable.
     * @throws ValidationException Throws an exception if the addon cannot be enabled.
     */
    public function validateEnable(Addon $addon, array $options = []) {
        $options += ['force' => false];
        $validation = new Validation();

        if (!$options['force'] && $this->isEnabledConfig($addon, $options)) {
            $validation->addError(
                'enabled',
                'The {addonName} {addonType} is already enabled.',
                ['addonName' => $addon->getName(), 'addonType' => $addon->getType()]
            );
            throw new ValidationException($validation);
        }

        try {
            $this->addonManager->checkRequirements($addon, true);
        } catch (\Exception $ex) {
            $validation->addError('requirements', $ex->getMessage(), $ex->getCode());
        }

        try {
            $this->addonManager->checkConflicts($addon, true);
        } catch (\Exception $ex) {
            $validation->addError('conflicts', $ex->getMessage(), $ex->getCode());
        }

        if (!$validation->isValid()) {
            throw new ValidationException($validation);
        }

        try {
            $addon->test(true);
        } catch (\Exception $ex) {
            $validation->addError('test', $ex->getMessage());
            throw new ValidationException($validation);
        }
    }

    /**
     * Determine whether or not an addon is enabled in the config.
     *
     * @param Addon $addon The addon the check.
     * @param array $options Additional options for the check.
     * @return bool Returns **true** if the addon is enabled or **false** otherwise.
     */
    private function isEnabledConfig(Addon $addon, array $options = []) {
        $enabled = false;
        switch ($addon->getType()) {
            case Addon::TYPE_ADDON:
                if ($addon->getInfoValue('oldType') === 'application') {
                    $enabled = $this->config->get('EnabledApplications.'.$addon->getRawKey());
                } else {
                    $enabled = $this->config->get('EnabledPlugins.'.$addon->getRawKey());
                }
                break;
            case Addon::TYPE_LOCALE:
                $enabled = $this->config->get('EnabledLocales.'.$addon->getRawKey());
                break;
            case Addon::TYPE_THEME:
                $options += ['themeType' => ''];

                $enabled = $this->config->get($this->getThemeConfigKey($options['themeType'])) === $addon->getKey();
                break;
        }
        return !empty($enabled);
    }

    /**
     * Enable a single addon.
     *
     * This method should be called after the addon has been enabled and all of its requirements have been enabled.
     *
     * @param Addon $addon The addon to enable.
     * @param array $options Additional options for the enable.
     */
    private function enableInternal(Addon $addon, array $options) {
        $wasEnabled = $this->isEnabledConfig($addon, $options);

        $this->addonManager->startAddon($addon);

        // Load bootstrap file.
        if ($bootstrap = $addon->getSpecial('bootstrap')) {
            include_once $addon->path($bootstrap, Addon::PATH_FULL);
        }

        $this->runSetup($addon);
        $this->enableInConfig($addon, true, $options);
        if ($pluginClass = $addon->getPluginClass()) {
            $this->events->bindClass($pluginClass, $addon->getPriority());

            // Fire some main events on the plugin.
            if (!$wasEnabled) {
                $this->callBootstrapEvents($pluginClass);
            }
        }

        if (!$wasEnabled) {
            $this->logger->info(
                'The {addonKey} {addonType} was enabled.',
                ['event' => 'addon_enabled', 'addonKey' => $addon->getKey(), 'addonType' => $addon->getType()]
            );
        }
    }

    /**
     * Call the bootstrap style events on a plugin.
     *
     * This method is used when a plugin is enabled so that it can do what it can initialize after the bootstrap has happened.
     *
     * @param string $pluginClass The name of the plugin class.
     */
    private function callBootstrapEvents($pluginClass) {
        $instance = $this->container->get($pluginClass);

        $this->events->fireClass($instance, 'container_init', $this->container);
    }

    /**
     * Run an addon's setup method or structure.
     *
     * @param Addon $addon The addon to run.
     */
    private function runSetup(Addon $addon) {
        // Look for a setup method.
        $called = $this->callPluginMethod($addon, 'setup');

        // @TODO This if is a kludge because Vanilla's core applications are inconsistent.
        // Once the InstallModel is in use this code can be cleaned up by manual structure inclusion in addons.
        if (($structure = $addon->getSpecial('structure')) && (!$called || !in_array($addon->path($structure, Addon::PATH_FULL), get_included_files())) || $addon->getKey() === 'dashboard') {
            $this->logger->info(
                "Executing structure for {addonKey}.",
                ['event' => 'addon_structure', 'addonKey' => $addon->getKey(), 'structureType' => 'file']
            );

            $this->includeFile($addon->path($structure, Addon::PATH_FULL));
        }

        // Register permissions.
        $this->registerPermissions($addon);
    }

    /**
     * Call a method on an addon's plugin if it exists.
     *
     * @param Addon $addon The addon that owns the plugin.
     * @param string $method The name of the method to call.
     * @return bool Returns **true** if the method was called or **false** otherwise.
     */
    private function callPluginMethod(Addon $addon, $method) {
        if (($pluginClass = $addon->getPluginClass()) && method_exists($pluginClass, $method)) {
            $plugin = $this->container->get($pluginClass);

            $this->logger->info(
                "Calling {addonMethod} on {addonClass} for {addonKey}.",
                ['event' => 'addon_method', 'addonMethod' => $method, 'addonClass' => $pluginClass, 'addonKey' => $addon->getKey()]
            );

            $this->container->call([$plugin, $method]);

            return true;
        }
        return false;
    }

    /**
     * Include an file with optional dependency injection.
     *
     * If the included file returns a callable then that callable will be called through the dependency injection container.
     *
     * @param string $path The full path of the file.
     */
    private function includeFile($path) {
        // Legacy structure files require global variables.

        /* @var \Gdn_Database $Database */
        $Database = $this->container->get(\Gdn_Database::class);
        $SQL = $Database->sql();
        $Structure = $Database->structure();

        $r = require $path;
        if (is_callable($r)) {
            $this->container->call($r);
        }
    }

    /**
     * Register an addon's permissions.
     *
     * @param Addon $addon The addon to register.
     */
    private function registerPermissions(Addon $addon) {
        if ($permissions = $addon->getInfoValue('registerPermissions')) {
            $this->logger->info(
                "Defining permissions for {addonKey}.",
                ['event' => 'addon_permissions', 'addonKey' => $addon->getKey(), 'permissions' => $permissions]
            );
            $permissionModel = $this->container->get(PermissionModel::class);
            $permissionModel->define($permissions);
        }
    }

    /**
     * Enable or disable an addon in the config.
     *
     * @param Addon $addon The addon to enable/disable.
     * @param bool $enabled Whether or not the addon is enabled.
     * @param array $options Additional options for the operation.
     */
    private function enableInConfig(Addon $addon, $enabled, array $options = []) {
        $options += ['forceConfig' => true];

        if (!$options['forceConfig'] && $this->isEnabledConfig($addon, $options) === $enabled) {
            return;
        }

        switch ($addon->getType()) {
            case Addon::TYPE_ADDON:
                if ($addon->getInfoValue('oldType') === 'application') {
                    $this->config->saveToConfig(
                        'EnabledApplications.'.$addon->getRawKey(),
                        $enabled ? trim(basename($addon->getSubdir()), '/') : null,
                        ['RemoveEmpty' => true]
                    );
                } else {
                    $this->config->saveToConfig('EnabledPlugins.'.$addon->getRawKey(), $enabled);
                }
                break;
            case Addon::TYPE_LOCALE:
                $this->config->saveToConfig(
                    'EnabledLocales.'.$addon->getRawKey(),
                    $enabled ? $addon->getInfoValue('locale') : null,
                    ['RemoveEmpty' => true]
                );
                break;
            case Addon::TYPE_THEME:
                $options += ['themeType' => ''];
                $configKey = $this->getThemeConfigKey($options['themeType']);

                $this->config->saveToConfig(
                    $configKey,
                    $enabled ? $addon->getKey() : null,
                    ['RemoveEmpty' => true]
                );
                break;
        }
    }

    /**
     * Disable an addon.
     *
     * @param Addon $addon The addon to disable.
     * @param array $options Additional options on the disable.
     * @throws ValidationException Throws an exception if the addon cannot be disabled.
     */
    public function disable(Addon $addon, array $options = []) {
        $wasEnabled = $this->isEnabledConfig($addon, $options);

        // 1. Validate the disable.
        try {
            $this->addonManager->checkDependents($addon, true);
        } catch (\Exception $ex) {
            $validation = new Validation();
            $validation->addError('dependents', $ex->getMessage());
            throw new ValidationException($validation);
        }

        // 2. Perform necessary hook action.
        $this->callPluginMethod($addon, 'onDisable');

        // 3. Disable it.
        $this->enableInConfig($addon, false, $options);
        if ($addon->getPluginClass()) {
            $this->events->unbindClass($addon->getPluginClass());
        }
        $this->addonManager->stopAddon($addon);

        // 4. Log the disable.
        if ($wasEnabled) {
            $this->logger->info(
                'The {addonKey} {addonType} was disabled.',
                ['event' => 'addon_disabled', 'addonKey' => $addon->getKey(), 'addonType' => $addon->getType()]
            );
        }

//        $this->EventArguments['AddonName'] = $pluginName;
//        $this->fireEvent('AddonDisabled');
    }

    /**
     * Run the database structure updates on an addon.
     *
     * This method was adapted from {@link \UpdateModel::runStructure()}.
     *
     * @param Addon $addon The addon to run.
     */
    public function runStructure(Addon $addon) {
        // Look for a file.
        if ($structure = $addon->getSpecial('structure')) {
            $this->logger->info(
                "Executing structure for {addonKey}.",
                ['event' => 'addon_structure', 'addonKey' => $addon->getKey(), 'structureType' => 'file']
            );

            $this->includeFile($addon->path($structure));
        }

        // Look for a structure method on the addon.
        $this->callPluginMethod($addon, 'structure');

        // Register permissions.
        $this->registerPermissions($addon);
    }

    /**
     * Signal completion after a structure update.
     */
    public function onAfterStructure() {
        $this->events->fire('updateModel_afterStructure', $this);
    }

    /**
     * Get the addonManager.
     *
     * @return AddonManager Returns the addonManager.
     */
    public function getAddonManager() {
        return $this->addonManager;
    }

    public function splitID($addonID) {
        return Addon::splitGlobalKey($addonID);
    }

    /**
     * Lookup addons based on filter criteria.
     *
     * @param array $where The filter.
     * @return Addon[] Returns an array of addons.
     */
    public function getWhere(array $where = []) {
        $where += [
            'enabled' => null,
            'type' => null,
            'addonID' => null,
            'hidden' => false,
            'deprecated' => null,
            'themeType' => 'desktop'
        ];

        $am = $this->getAddonManager();

        // Do a bit of optimization depending on the filter.
        if ($where['addonID']) {
            list($key, $type) = Addon::splitGlobalKey($where['addonID']);
            $addons = [$am->lookupByType($key, $type)];
        } elseif ($where['enabled'] && $where['type'] === Addon::TYPE_THEME) {
            $addons = [$am->lookupTheme($this->getThemeKey($where['themeType']))];
        } elseif (!empty($where['enabled'])) {
            $addons = $am->getEnabled();
            // Add the theme again because getEnabled() doesn't currently work with the mobile theme.
            if ($theme = $am->lookupTheme($this->getThemeKey($where['themeType']))) {
                $addons[$theme->getType().'/'.$theme->getKey()] = $theme;
            }

            $addons = array_values($addons);
        } elseif (!empty($where['type'])) {
            $addons = array_values($am->lookupAllByType($where['type']));
        } else {
            $addons = array_merge(
                array_values($am->lookupAllByType(Addon::TYPE_ADDON)),
                array_values($am->lookupAllByType(Addon::TYPE_THEME)),
                array_values($am->lookupAllByType(Addon::TYPE_LOCALE))
            );
        }

        $addons = array_filter($addons, function (Addon $addon = null) use ($where, $am) {
            if ($addon === null) {
                return false;
            }

            if (isset($where['type']) && $addon->getType() !== $where['type']) {
                return false;
            }
            if (isset($where['enabled'])) {
                if ($addon->getType() === Addon::TYPE_THEME && $addon->getKey() !== $this->getThemeKey($where['themeType'])) {
                    return false;
                } elseif ($addon->getType() !== Addon::TYPE_THEME && $where['enabled'] !== $am->isEnabled($addon->getKey(), $addon->getType())) {
                    return false;
                }
            }
            if (isset($where['hidden']) && $where['hidden'] !== $addon->getInfoValue('hidden', false)) {
                return false;
            }
            if (isset($where['deprecated']) && $where['deprecated'] !== $addon->getInfoValue('deprecated', false)) {
                return false;
            } elseif (!isset($where['deprecated']) && $addon->getInfoValue('deprecated') && !$am->isEnabled($addon->getKey(), $addon->getType())) {
                return false;
            }
            return true;
        });

        return $addons;
    }

    /**
     * Get the current theme key.
     *
     * @param string $type They type of theme, one of **desktop** or **mobile**.
     * @return string Returns an addon key for a theme.
     */
    public function getThemeKey($type = '') {
        if ($type === 'mobile') {
            $r = $this->config->get('Garden.MobileTheme', AddonManager::DEFAULT_MOBILE_THEME);
        } else {
            $r = $this->config->get('Garden.Theme', AddonManager::DEFAULT_DESKTOP_THEME);
        }
        return $r;
    }

    /**
     * Get the config key for a given theme.
     *
     * @param string $type The type of theme, either **dekstop** or **mobile**.
     * @return string Returns a config key.
     */
    private function getThemeConfigKey($type = '') {
        if (strcasecmp($type, 'desktop') === 0) {
            $type = '';
        }
        $r = 'Garden.'.ucfirst($type).'Theme';
        return $r;
    }
}
