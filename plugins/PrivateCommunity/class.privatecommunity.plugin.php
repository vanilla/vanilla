<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
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

class PrivateCommunityPlugin extends Gdn_Plugin {

    public function RoleController_AfterRolesInfo_Handler($Sender) {
        if (!Gdn::Session()->CheckPermission('Garden.Settings.Manage'))
            return;

        $Private = C('Garden.PrivateCommunity');
        echo '<div style="padding: 10px 0;">';
        $Style = array('style' => 'background: #ff0; padding: 2px 4px; margin: 0 10px 2px 0; display: inline-block;');
        if ($Private) {
            echo Wrap('Your community is currently <strong>PRIVATE</strong>.', 'span', $Style);
            echo Wrap(Anchor('Switch to PUBLIC', 'settings/privatecommunity/on/'.Gdn::Session()->TransientKey(), 'SmallButton').'(Everyone will see inside your community)', 'div');
        } else {
            echo Wrap('Your community is currently <strong>PUBLIC</strong>.', 'span', $Style);
            echo Wrap(Anchor('Switch to PRIVATE', 'settings/privatecommunity/off/'.Gdn::Session()->TransientKey(), 'SmallButton').'(Only members will see inside your community)', 'div');
        }
        echo '</div>';
    }

    public function SettingsController_PrivateCommunity_Create($Sender) {
        $Session = Gdn::Session();
        $Switch = GetValue(0, $Sender->RequestArgs);
        $TransientKey = GetValue(1, $Sender->RequestArgs);
        if (
            in_array($Switch, array('on', 'off'))
            && $Session->ValidateTransientKey($TransientKey)
            && $Session->CheckPermission('Garden.Settings.Manage')
        ) {
            SaveToConfig('Garden.PrivateCommunity', $Switch == 'on' ? FALSE : TRUE);
        }
        Redirect('dashboard/role');
    }

    public function Setup() {
        // No setup required
    }
}
