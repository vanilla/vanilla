<?php
/**
 * PrivateCommunity Plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package PrivateCommunity
 */

// Define the plugin:
$PluginInfo['PrivateCommunity'] = array(
    'Name' => 'Private Community',
    'Description' => 'Adds an option to Roles & Permissions to make all pages only visible for signed-in community members.',
    'Version' => '1.0',
    'Author' => "Mark O'Sullivan",
    'AuthorEmail' => 'mark@vanillaforums.com',
    'AuthorUrl' => 'http://markosullivan.ca',
    'SettingsUrl' => '/dashboard/role',
);

/**
 * Class PrivateCommunityPlugin
 */
class PrivateCommunityPlugin extends Gdn_Plugin {

    /**
     *
     *
     * @param $Sender
     */
    public function roleController_afterRolesInfo_handler($Sender) {
        if (!Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            return;
        }

        $Private = c('Garden.PrivateCommunity');
        echo '<div style="padding: 10px 0;">';
        $Style = array('style' => 'background: #ff0; padding: 2px 4px; margin: 0 10px 2px 0; display: inline-block;');
        if ($Private) {
            echo wrap('Your community is currently <strong>PRIVATE</strong>.', 'span', $Style);
            echo wrap(anchor('Switch to PUBLIC', 'settings/privatecommunity/on/'.Gdn::session()->transientKey(), 'SmallButton').'(Everyone will see inside your community)', 'div');
        } else {
            echo wrap('Your community is currently <strong>PUBLIC</strong>.', 'span', $Style);
            echo wrap(anchor('Switch to PRIVATE', 'settings/privatecommunity/off/'.Gdn::session()->transientKey(), 'SmallButton').'(Only members will see inside your community)', 'div');
        }
        echo '</div>';
    }

    /**
     *
     *
     * @param $Sender
     */
    public function settingsController_privateCommunity_create($Sender) {
        $Session = Gdn::session();
        $Switch = val(0, $Sender->RequestArgs);
        $TransientKey = val(1, $Sender->RequestArgs);
        if (in_array($Switch, array('on', 'off'))
            && $Session->validateTransientKey($TransientKey)
            && $Session->checkPermission('Garden.Settings.Manage')
        ) {
            saveToConfig('Garden.PrivateCommunity', $Switch == 'on' ? false : true);
        }
        redirect('dashboard/role');
    }

    /**
     * No setup.
     */
    public function setup() {
    }
}
