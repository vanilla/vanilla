<?php if (!defined('APPLICATION')) exit();
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
    'MobileFriendly' => TRUE
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
    public function ProfileController_AfterAddSideMenu_Handler($Sender, $Args) {
        if (!$Sender->User->Photo) {
            $Email = GetValue('Email', $Sender->User);
            $Protocol = Gdn::Request()->Scheme() == 'https' ? 'https://secure.' : 'http://www.';

            $Url = $Protocol.'gravatar.com/avatar.php?'
                .'gravatar_id='.md5(strtolower($Email))
                .'&amp;size='.C('Garden.Profile.MaxWidth', 200);

            if (C('Plugins.Gravatar.UseVanillicon', TRUE))
                $Url .= '&default='.urlencode(Gdn::Request()->Scheme().'://vanillicon.com/'.md5($Email).'_200.png');
            else
                $Url .= '&default='.urlencode(Asset(C('Plugins.Gravatar.DefaultAvatar', 'plugins/Gravatar/default_250.png'), TRUE));


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
    function UserPhotoDefaultUrl($User) {
        $Email = GetValue('Email', $User);
        $Https = Gdn::Request()->Scheme() == 'https';
        $Protocol = $Https ? 'https://secure.' : 'http://www.';

        $Url = $Protocol.'gravatar.com/avatar.php?'
            .'gravatar_id='.md5(strtolower($Email))
            .'&amp;size='.C('Garden.Thumbnail.Width', 50);

        if (C('Plugins.Gravatar.UseVanillicon', TRUE))
            $Url .= '&default='.urlencode(Gdn::Request()->Scheme().'://vanillicon.com/'.md5($Email).'.png');
        else
            $Url .= '&default='.urlencode(Asset(C('Plugins.Gravatar.DefaultAvatar', 'plugins/Gravatar/default.png'), TRUE));

        return $Url;
    }
}
