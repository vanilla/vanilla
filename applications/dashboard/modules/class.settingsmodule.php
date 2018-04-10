<?php
/**
 * Settings module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Varies functions related to Settings
 */
class SettingsModule extends Gdn_Module {

    const TYPE_APPLICATION = 'application';

    const TYPE_PLUGIN = 'plugin';

    const TYPE_THEME = 'theme';

    /**
     * Is the application/plugin/theme removable?
     *
     * @param string $type self::TYPE_APPLICATION or self::TYPE_PLUGIN or self::TYPE_THEME
     * @param string $name
     * @return boolean
     */
    public static function isRemovable($type, $name) {

        switch ($type) {
            case self::TYPE_APPLICATION:
                $applicationManager = Gdn::factory('ApplicationManager');

                if ($isRemovable = !array_key_exists($name, $applicationManager->enabledApplications())) {
                    $applicationInfo = val($name, $applicationManager->availableApplications(), []);
                    $applicationFolder = val('Folder', $applicationInfo, '');

                    $isRemovable = isWritable(PATH_APPLICATIONS.DS.$applicationFolder);
                }
                break;
            case self::TYPE_PLUGIN:
                if ($isRemovable = !array_key_exists($name, Gdn::pluginManager()->enabledPlugins())) {
                    $pluginInfo = val($name, Gdn::pluginManager()->availablePlugins(), false);
                    $pluginFolder = val('Folder', $pluginInfo, false);

                    $isRemovable = isWritable(PATH_PLUGINS.DS.$pluginFolder);
                }
                break;
            case self::TYPE_THEME:
                // TODO
                $isRemovable = false;
                break;
        }

        return $isRemovable;
    }
}
