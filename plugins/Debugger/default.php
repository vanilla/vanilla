<?php
/**
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Debugger
 */

// Define the plugin:
$PluginInfo['Debugger'] = array(
    'Description' => 'The debugger plugin displays database queries, their benchmarks, and page processing benchmarks at the bottom of each screen of the application.',
    'Version' => '1.1',
    'RegisterPermissions' => array('Plugins.Debugger.View', 'Plugins.Debugger.Manage'), // Permissions that should be added to the application. These will be prefixed with "Plugins.PluginName."
    'PluginUrl' => 'http://vanillaforums.org/addons/debugger',
    'Author' => "Mark O'Sullivan",
    'AuthorEmail' => 'mark@vanillaforums.com',
    'AuthorUrl' => 'http://markosullivan.ca',
    'MobileFriendly' => true,
);

/**
 * Class DebuggerPlugin
 */
class DebuggerPlugin extends Gdn_Plugin {

    /**
     * Install the debugger database.
     */
    public function __construct() {
        parent::__construct();
        $tmp = Gdn::FactoryOverwrite(TRUE);
        Gdn::FactoryInstall(Gdn::AliasDatabase, 'Gdn_DatabaseDebug', dirname(__FILE__).DS.'class.databasedebug.php', Gdn::FactorySingleton, array('Database'));
        Gdn::FactoryOverwrite($tmp);
        unset($tmp);
    }

    /**
     * Add CSS file to all pages.
     *
     * @param $Sender
     * @param $Args
     */
    public function AssetModel_StyleCss_Handler($Sender, $Args) {
        $Sender->AddCssFile('debugger.css', 'plugins/Debugger');
    }

    /**
     * Add Debugger info to every page.
     *
     * @param $Sender
     */
    public function Base_AfterBody_Handler($Sender) {
        $Session = Gdn::session();
        if (!Debug() && !$Session->CheckPermission('Plugins.Debugger.View')) {
            return;
        }

        require $Sender->fetchViewLocation('Debug', '', 'plugins/Debugger');
    }

    /**
     * Build HTML.
     *
     * @param $Data
     * @param string $Indent
     * @return string
     */
    public static function FormatData($Data, $Indent = '') {
        $Result = '';
        if (is_array($Data)) {
            foreach ($Data as $Key => $Value) {
                if ($Key === null)
                    $Key = 'NULL';
                $Result .= "$Indent<b>$Key</b>: ";

                if ($Value === null) {
                    $Result .= "NULL\n";
                } elseif (is_numeric($Value) || is_string($Value) || is_bool($Value) || is_null($Value)) {
                    $Result .= htmlspecialchars(var_export($Value, true))."\n";
                } else {
                    if (is_a($Value, 'Gdn_DataSet'))
                        $Result .= "DataSet";

                    $Result .=
                        "\n"
                        .self::FormatData($Value, $Indent.'   ');
                }
            }
        } elseif (is_a($Data, 'Gdn_DataSet')) {
            $Data = $Data->Result();
            if (count($Data) == 0)
                return $Result.'EMPTY<br />';

            $Fields = array_keys((array)reset($Data));
            $Result .= $Indent.'<b>Count</b>: '.count($Data)."\n"
                .$Indent.'<b>Fields</b>: '.htmlspecialchars(implode(", ", $Fields))."\n";
            return $Result;
        } elseif (is_a($Data, 'stdClass')) {
            $Data = (array)$Data;
            return self::FormatData($Data, $Indent);
        } elseif (is_object($Data)) {
            $Result .= $Indent.get_class($Data);
        } else {
            return trim(var_export($Data, true));
        }
        return $Result;
    }

    /**
     *
     *
     * @param $Sender
     */
    public function PluginController_Debugger_Create($Sender) {
        $Sender->Render();
    }

    /**
     *
     */
    public function Setup() {
        saveToConfig('Debug', true);
    }

    /**
     *
     */
    public function OnDisable() {
        saveToConfig('Debug', FALSE, array('RemoveEmpty' => TRUE));
    }
}
