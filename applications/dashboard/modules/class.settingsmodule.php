<?php
/**
 * Settings module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
     * @param string $Type self::TYPE_APPLICATION or self::TYPE_PLUGIN or self::TYPE_THEME
     * @param string $Name
     * @return boolean
     */
    public static function isRemovable($Type, $Name) {

        switch ($Type) {
            case self::TYPE_APPLICATION:
                $ApplicationManager = Gdn::Factory('ApplicationManager');

                if ($IsRemovable = !array_key_exists($Name, $ApplicationManager->EnabledApplications())) {
                    $ApplicationInfo = arrayValue($Name, $ApplicationManager->AvailableApplications(), array());
                    $ApplicationFolder = arrayValue('Folder', $ApplicationInfo, '');

                    $IsRemovable = IsWritable(PATH_APPLICATIONS.DS.$ApplicationFolder);
                }
                break;
            case self::TYPE_PLUGIN:
                if ($IsRemovable = !array_key_exists($Name, Gdn::pluginManager()->EnabledPlugins())) {
                    $PluginInfo = arrayValue($Name, Gdn::pluginManager()->AvailablePlugins(), false);
                    $PluginFolder = arrayValue('Folder', $PluginInfo, false);

                    $IsRemovable = IsWritable(PATH_PLUGINS.DS.$PluginFolder);
                }
                break;
            case self::TYPE_THEME:
                // TODO
                $IsRemovable = false;
                break;
        }

        return $IsRemovable;
    }
}
