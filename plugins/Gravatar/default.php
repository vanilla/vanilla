<?php
/**
 * Gravatar Plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Gravatar
 */

// Define the plugin:
$PluginInfo['Gravatar'] = array(
    'Name' => 'Gravatar',
    'Description' => 'Implements Gravatar avatars for all users who have not uploaded their own custom profile picture & icon.',
    'Version' => '1.4.3',
    'Author' => "Mark O'Sullivan",
    'AuthorEmail' => 'mark@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.com',
    'MobileFriendly' => true
);

// 1.1 Fixes - Used GetValue to retrieve array props instead of direct references
// 1.2 Fixes - Make Gravatar work with the mobile theme
// 1.3 Fixes - Changed UserBuilder override to also accept an array of user info
// 1.4 Change - Lets you chain Vanillicon as the default by setting Plugins.Gravatar.UseVanillicon in config.

/**
 * Class GravatarPlugin
 */
class GravatarPlugin extends Gdn_Plugin {

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function profileController_afterAddSideMenu_handler($Sender, $Args) {
        if (!$Sender->User->Photo) {
            $Email = val('Email', $Sender->User);
            $Protocol = Gdn::request()->scheme() == 'https' ? 'https://secure.' : 'http://www.';

            $Url = $Protocol.'gravatar.com/avatar.php?'
                .'gravatar_id='.md5(strtolower($Email))
                .'&amp;size='.c('Garden.Profile.MaxWidth', 200);

            if (c('Plugins.Gravatar.UseVanillicon', true)) {
                $Url .= '&default='.urlencode(Gdn::request()->scheme().'://vanillicon.com/'.md5($Email).'_200.png');
            } else {
                $Url .= '&default='.urlencode(asset(c('Plugins.Gravatar.DefaultAvatar', 'plugins/Gravatar/default_250.png'), true));
            }


            $Sender->User->Photo = $Url;
        }
    }
}

if (!function_exists('UserPhotoDefaultUrl')) {
    /**
     *
     *
     * @param $User
     * @return string
     */
    function userPhotoDefaultUrl($User) {
        $Email = val('Email', $User);
        $Https = Gdn::request()->scheme() == 'https';
        $Protocol = $Https ? 'https://secure.' : 'http://www.';

        $Url = $Protocol.'gravatar.com/avatar.php?'
            .'gravatar_id='.md5(strtolower($Email))
            .'&amp;size='.c('Garden.Thumbnail.Width', 50);

        if (c('Plugins.Gravatar.UseVanillicon', true)) {
            $Url .= '&default='.urlencode(Gdn::request()->scheme().'://vanillicon.com/'.md5($Email).'.png');
        } else {
            $Url .= '&default='.urlencode(Asset(c('Plugins.Gravatar.DefaultAvatar', 'plugins/Gravatar/default.png'), true));
        }

        return $Url;
    }
}
