<?php
/**
 * Update model.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */
use Vanilla\Addon;

/**
 * Handles updating.
 */
class UpdateModel extends Gdn_Model {

    // TODO Remove when removing other deprecated functions!
    /** @var string URL to the addons site. */
    public $AddonSiteUrl = 'http://vanilla.local';

    /**
     * Run the structure for all addons.
     *
     * The structure runs the addons in priority order so that higher priority addons override lower priority ones.
     *
     * @param bool $captureOnly Run the structure or just capture the SQL changes.
     * @throws Exception Throws an exception if in debug mode and something goes wrong.
     */
    public function runStructure($captureOnly = false) {
        $addons = array_reverse(Gdn::addonManager()->getEnabled());

        // These variables are required for included structure files.
        $Database = Gdn::database();
        $SQL = $this->SQL;
        $SQL->CaptureModifications = $captureOnly;
        $Structure = Gdn::structure();
        $Structure->CaptureOnly = $captureOnly;

        /* @var Addon $addon */
        foreach ($addons as $addon) {
            // Look for a structure file.
            if ($structure = $addon->getSpecial('structure')) {
                Logger::event(
                    'addon_structure',
                    Logger::INFO,
                    "Executing structure for {addonKey}.",
                    ['addonKey' => $addon->getKey(), 'structureType' => 'file']
                );

                try {
                    include $addon->path($structure);
                } catch (\Exception $ex) {
                    if (debug()) {
                        throw $ex;
                    }
                }
            }

            // Look for a structure method on the plugin.
            if ($addon->getPluginClass()) {
                $plugin = Gdn::pluginManager()->getPluginInstance(
                    $addon->getPluginClass(),
                    Gdn_PluginManager::ACCESS_CLASSNAME
                );

                if (is_object($plugin) && method_exists($plugin, 'structure')) {
                    Logger::event(
                        'addon_structure',
                        Logger::INFO,
                        "Executing structure for {addonKey}.",
                        ['addonKey' => $addon->getKey(), 'structureType' => 'method']
                    );

                    try {
                        call_user_func([$plugin, 'structure']);
                    } catch (\Exception $ex) {
                        if (debug()) {
                            throw $ex;
                        }
                    }
                }
            }

            // Register permissions.
            $permissions = $addon->getInfoValue('registerPermissions');
            if (!empty($permissions)) {
                Logger::event(
                    'addon_permissions',
                    Logger::INFO,
                    "Defining permissions for {addonKey}.",
                    ['addonKey' => $addon->getKey(), 'permissions' => $permissions]
                );
                Gdn::permissionModel()->define($permissions);
            }
        }
        $this->fireEvent('AfterStructure');

        if ($captureOnly && property_exists($Structure->Database, 'CapturedSql')) {
            return $Structure->Database->CapturedSql;
        }
        return [];
    }
}
