<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Debugger
 */

/**
 * Class DebuggerPlugin
 */
class DebuggerPlugin extends Gdn_Plugin {

    /**
     * Install the debugger database.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Add CSS file to all pages.
     *
     * @param $sender
     * @param $args
     */
    public function assetModel_styleCss_handler($sender, $args) {
        $sender->addCssFile('debugger.css', 'plugins/Debugger');
    }

    /**
     * Add Debugger info to frontend.
     *
     * @param $Sender
     */
    public function base_afterBody_handler($Sender) {
        $Session = Gdn::session();
        if (!debug() || !$Session->checkPermission('Plugins.Debugger.View') || $Sender->MasterView == 'admin') {
            return;
        }

        require $Sender->fetchViewLocation('Debug', '', 'plugins/Debugger');
    }

    /**
     * Add Debugger info to dashboard after content asset.
     *
     * @param $sender
     * @param $args
     */
    public function base_afterRenderAsset_handler($sender, $args) {
        if (val('AssetName', $args) == 'Content' && $sender->MasterView == 'admin') {
            $session = Gdn::session();
            if (!debug() || !$session->checkPermission('Plugins.Debugger.View')) {
                return;
            }
            require $sender->fetchViewLocation('Debug', '', 'plugins/Debugger');
        }
    }

    /**
     * Register the debug database that captures the queries.
     *
     * This event happens as early as possible so that all queries can be captured.
     *
     * @param Gdn_PluginManager $sender The {@link Gdn_PluginManager} firing the event.
     */
    public function gdn_pluginManager_afterStart_handler($sender) {
        $tmp = Gdn::factoryOverwrite(true);
        Gdn::factoryInstall(Gdn::AliasDatabase, 'Gdn_DatabaseDebug', __DIR__.DS.'class.databasedebug.php', Gdn::FactorySingleton, ['Database']);
        Gdn::factoryOverwrite($tmp);
        unset($tmp);
    }

    /**
     * Build HTML.
     *
     * @param $data
     * @param string $indent
     * @return string
     */
    public static function formatData($data, $indent = '') {
        $result = '';
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($key === null)
                    $key = 'NULL';
                $result .= "$indent<b>$key</b>: ";

                if ($value === null) {
                    $result .= "NULL\n";
                } elseif (is_numeric($value) || is_string($value) || is_bool($value) || is_null($value)) {
                    $result .= htmlspecialchars(var_export($value, true))."\n";
                } else {
                    if (is_a($value, 'Gdn_DataSet'))
                        $result .= "DataSet";

                    $result .=
                        "\n"
                        .self::formatData($value, $indent.'   ');
                }
            }
        } elseif (is_a($data, 'Gdn_DataSet')) {
            $data = $data->result();
            if (count($data) == 0)
                return $result.'EMPTY<br />';

            $fields = array_keys((array)reset($data));
            $result .= $indent.'<b>Count</b>: '.count($data)."\n"
                .$indent.'<b>Fields</b>: '.htmlspecialchars(implode(", ", $fields))."\n";
            return $result;
        } elseif (is_a($data, 'stdClass')) {
            $data = (array)$data;
            return self::formatData($data, $indent);
        } elseif (is_object($data)) {
            $result .= $indent.get_class($data);
        } else {
            return trim(var_export($data, true));
        }
        return $result;
    }

    /**
     *
     *
     * @param $sender
     */
    public function pluginController_debugger_create($sender) {
        $sender->render();
    }

    /**
     *
     */
    public function setup() {
    }

    /**
     *
     */
    public function onDisable() {
        saveToConfig('Debug', FALSE, ['RemoveEmpty' => TRUE]);
    }
}
