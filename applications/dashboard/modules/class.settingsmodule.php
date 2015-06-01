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
    public static function IsRemovable($Type, $Name) {

        switch ($Type) {
            case self::TYPE_APPLICATION:
                $ApplicationManager = Gdn::Factory('ApplicationManager');

                if ($IsRemovable = !array_key_exists($Name, $ApplicationManager->EnabledApplications())) {
                    $ApplicationInfo = ArrayValue($Name, $ApplicationManager->AvailableApplications(), array());
                    $ApplicationFolder = ArrayValue('Folder', $ApplicationInfo, '');

                    $IsRemovable = IsWritable(PATH_APPLICATIONS.DS.$ApplicationFolder);
                }
                break;
            case self::TYPE_PLUGIN:
                if ($IsRemovable = !array_key_exists($Name, Gdn::PluginManager()->EnabledPlugins())) {
                    $PluginInfo = ArrayValue($Name, Gdn::PluginManager()->AvailablePlugins(), FALSE);
                    $PluginFolder = ArrayValue('Folder', $PluginInfo, FALSE);

                    $IsRemovable = IsWritable(PATH_PLUGINS.DS.$PluginFolder);
                }
                break;
            case self::TYPE_THEME:
                // TODO
                $IsRemovable = FALSE;
                break;
        }

        return $IsRemovable;
    }

}
