<?php
/**
 * Gravatar Plugin.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Gravatar
 */

// Define the plugin:
$PluginInfo['Gravatar'] = array(
    'Name' => 'Gravatar',
    'Description' => 'Implements Gravatar avatars for all users who have not uploaded their own custom profile picture & icon.',
    'Version' => '1.5',
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
     * Generate a Gravatar image URL based on the provided email address.
     *
     * @link http://en.gravatar.com/site/implement/images/ Gravatar Image Requests
     * @param string $email Email address for the user, used to generate the avatar ID.
     * @param int $size Target image size.
     * @return string A formatted Gravatar image URL.
     */
    public static function generateUrl($email, $size = 80) {
        $avatarID = md5(strtolower($email));

        // Figure out our base URLs.  Gravatar doesn't support SVGs, so we're stuck with using Vanillicon v1.
        if (Gdn::request()->scheme() === 'https') {
            $baseUrl = 'https://secure.gravatar.com/avatar';
            $vanilliconBaseUrl = 'https://vanillicon.com';
        } else {
            $baseUrl = 'http://www.gravatar.com/avatar';
            $vanilliconBaseUrl = 'http://vanillicon.com';
        }

        if (c('Plugins.Gravatar.UseVanillicon', true)) {
            // Version 1 of Vanillicon only supports three sizes.  Figure out which one is best for this image.
            if ($size <= 50) {
                $vanilliconSize = 50;
            } elseif ($size <= 100) {
                $vanilliconSize = 100;
            } else {
                $vanilliconSize = 200;
            }

            $default = "{$vanilliconBaseUrl}/{$avatarID}_{$vanilliconSize}.png";
        } else {
            $configuredDefaultAvatar = c('Plugins.Gravatar.DefaultAvatar', c('Garden.DefaultAvatar'));
            if ($configuredDefaultAvatar) {
                $default = Gdn_Upload::parse($configuredDefaultAvatar);
            } else {
                $default = asset(
                    $size <= 50 ? 'plugins/Gravatar/default.png' : 'plugins/Gravatar/default_250.png',
                    true
                );
            }
        }

        $query = [
            'default' => $default,
            'rating' => c('Plugins.Gravatar.Rating', 'g'),
            'size' => $size
        ];

        return $baseUrl."/{$avatarID}/?".http_build_query($query);
    }

    /**
     * Set the Gravatar image on the user's profile.
     *
     * @param ProfileController $sender Reference to the current profile controller instance.
     * @param array $args Additional parameters for the current event.
     */
    public function profileController_afterAddSideMenu_handler($sender, $args) {
        if (!$sender->User->Photo) {
            $sender->User->Photo = self::generateUrl(
                val('Email', $sender->User),
                c('Garden.Profile.MaxWidth', 200)
            );
        }
    }
}

if (!function_exists('UserPhotoDefaultUrl')) {
    /**
     * Calculate the user's default photo url.
     *
     * @param array|object $user The user to examine.
     * @return string Gravatar image URL.
     */
    function userPhotoDefaultUrl($user) {
        return GravatarPlugin::generateUrl(
            val('Email', $user),
            c('Garden.Thumbnail.Width', 50)
        );
    }
}
