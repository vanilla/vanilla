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


    public $configs = array('Plugins.Gravatar.UseVanillicon' => 'Whether to use Vanillicon as the default image for users without a Gravatar avatar.',
                            'Plugins.Gravatar.DefaultAvatar' => 'Url of the default avatar image. Must have Plugins.Gravatar.UseVanillicon set to false or the Vanillicon plugin disabled.');

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function profileController_afterAddSideMenu_handler($Sender, $Args) {
        $Sender->User->Photo = GravatarPlugin::getUserPhoto($Sender->User, c('Garden.Profile.MaxWidth', 200));
    }

    /**
     * Checks if a given email has an associated Gravatar image.
     *
     * @param $email The email to check.
     * @return bool Whether the email has a Gravatar associated with it
     */
    public static function gravatarExists($email) {
        $https = Gdn::request()->scheme() == 'https';
        $protocol = $https ? 'https://secure.' : 'http://www.';

        // We set the default Gravatar image to 404. If we hit a 404, we know there is no Gravatar associated with the email.
        $url = $protocol.'gravatar.com/avatar/'
            .md5(strtolower($email))
            .'?d=404';

        $pr = new ProxyRequest();
        $response = $pr->request(array(
            'URL' => $url
        ));

        if ($pr->ResponseStatus == '404') {
            // No Gravatar.
            return false;
        }
        return true;
    }

    /**
     * Return the Gravatar image or if it does not exist, return the Vanillicon or default Gravatar image.
     *
     * @param $user The user whose photo to retrieve.
     * @param int $size The size of the photo to retrieve.
     * @return string The url of the user photo.
     */
    public static function getUserPhoto($user, $size = 50) {
        $userModel = new UserModel();
        $id = val('UserID', $user);

        // Let's get the cached user, if there is one.
        $cachedUser = $userModel->getUserFromCache($id, 'userid');
        $user = $cachedUser ?: $user;

        if (!val('Photo', $user)) {
            $email = val('Email', $user);
            $https = Gdn::request()->scheme() == 'https';
            $protocol = $https ? 'https://secure.' : 'http://www.';
            $url = $protocol.'gravatar.com/avatar/'
                .md5(strtolower($email))
                .'?amp;size='.$size;

            if (c('Plugins.Gravatar.UseVanillicon', true) && class_exists('VanilliconPlugin')) {

                // v2 Vanillicon uses svg, which is not at all supported by Gravatar,
                // so we can't use Gravatar's default('d') param here.
                if (c('Plugins.Vanillicon.Type') == 'v2') {
                    if (!self::gravatarExists($email)) {
                        // Explicitly set the avatar to the Vanillicon svg.
                        $url = VanilliconPlugin::vanilliconUrl($user);
                    }
                } else {
                    // v1 Vanillicon.
                    $default = VanilliconPlugin::vanilliconUrl($user);
                    $url .= '&d='.$default;
                }
            } else {
                $default = urlencode(Asset(c('Plugins.Gravatar.DefaultAvatar', 'plugins/Gravatar/default.png'), true));
                $url .= '&d='.$default;
            }

            $userModel->updateUserCache(val('UserID', $user), 'Photo', $url);
            return $url;
        }
        return val('Photo', $user);
    }
}

if (!function_exists('UserPhotoDefaultUrl')) {
    /**
     *
     *
     * @param $user The user to return the photo URL for.
     * @return string The user's photo url.
     */
    function userPhotoDefaultUrl($user) {
        return GravatarPlugin::getUserPhoto($user, c('Garden.Thumbnail.Width', 50));
    }
}
