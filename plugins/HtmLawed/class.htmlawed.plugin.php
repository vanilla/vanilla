<?php
/**
 * HtmLawed Plugin.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package HtmLawed
 */

$PluginInfo['HtmLawed'] = [
    'Description' => 'This addon is deprecated and can be removed. Its functionality is now in core.',
    'Version' => '1.5',
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.com/profile/todd',
    'Hidden' => true
];

/**
 * Class HTMLawedPlugin
 */
class HtmLawedPlugin extends Gdn_Plugin {

    /**
     * She's dead, Jim.
     *
     * @param $sender
     */
    public function gdn_dispatcher_appStartup_handler($sender) {
        Gdn::pluginManager()->disablePlugin('HtmLawed');
    }

}