<?php
/**
 * PrivateCommunity Plugin.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
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
    'Icon' => 'private-community.png'
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

        ?>
        <div class="row form-group padded">
            <div class="label-wrap-wide">
                <div class="description"><?php echo t('Enable Private Communities'); ?></div>
                <div class="info"><?php echo t('Once enabled, only members will see inside your community.'); ?></div>
            </div>
            <div class="input-wrap-right">
                <span id="plaintext-toggle">
                    <?php
                    if (c('Garden.PrivateCommunity', false)) {
                        echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', 'settings/privatecommunity/on/'.Gdn::session()->TransientKey()), 'span', array('class' => "toggle-wrap toggle-wrap-on"));
                    } else {
                        echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', 'settings/privatecommunity/off/'.Gdn::session()->TransientKey()), 'span', array('class' => "toggle-wrap toggle-wrap-off"));
                    }
                    ?>
                </span>
            </div>
        </div>

        <?php
    }

    /**
     * Opt out of popup settings page on addons page
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_beforeAddonList_handler($sender, &$args) {
        if (val('PrivateCommunity', $args['AvailableAddons'])) {
            $args['AvailableAddons']['PrivateCommunity']['HasPopupFriendlySettings'] = false;
        }
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
