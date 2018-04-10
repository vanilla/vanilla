<?php
/**
 * Gravatar Plugin.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Gravatar
 */

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
            $configuredDefaultAvatar = c('Garden.DefaultAvatar', false);
            if ($configuredDefaultAvatar) {
                $defaultParsed = Gdn_Upload::parse($configuredDefaultAvatar);
                $default = val('Url', $defaultParsed);
            } else {
                $default = asset('applications/dashboard/design/images/defaulticon.png', true);
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
     * Gravatar settings page.
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_gravatar_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');

        $cf = new ConfigurationModule($sender);
        $cf->initialize([
            'Plugins.Gravatar.UseVanillicon' => [
                'LabelCode' => 'Enable Vanillicon icons as your default avatars',
                'Control' => 'toggle'
            ]
        ]);

        $sender->setData('Title', t('Gravatar Settings'));
        $cf->renderAll();
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
                c('Garden.Profile.MaxWidth')
            );
        }
    }

    /**
     * Overrides allowing admins to set the default avatar when Plugins.Gravatar.UseVanillicon
     * is set, since it has no effect. Adds messages to the top of avatar settings page and to the help panel asset.
     *
     * @param SettingsController $sender
     */
    public function settingsController_avatarSettings_handler($sender) {
        $message = '';
        $help = t('Users with a Gravatar account will by default get their Gravatar avatar.');

        $useVanillicon = c('Plugins.Gravatar.UseVanillicon', false);

        if ($useVanillicon) {
            $message = t('You\'re using Vanillicon avatars as your default avatars.');
            $message .= ' '.t('To set a custom default avatar, disable Vanillicon from your Gravatar settings.');
            $help .= ' '.t('Users without a Gravatar account will get a Vanillicon avatar.');
            $sender->setData('canSetDefaultAvatar', false);
        } else {
            $help .= ' '.t('Users without a Gravatar account will get the default avatar.');
        }

        if (Gdn::addonManager()->isEnabled('vanillicon', \Vanilla\Addon::TYPE_ADDON) && !$useVanillicon) {
            // Gravatar overrides Vanillicon
            $message = t('The Gravatar plugin overrides the Vanillicon plugin.');
            $message .= ' '.t('To use both Vanillicon and Gravatar, enable Vanillicon from your Gravatar settings.');
        }

        if ($message) {
            $messages = $sender->data('messages', []);
            $messages = array_merge($messages, [$message]);
            $sender->setData('messages', $messages);
        }

        helpAsset(t('How are my users\' default avatars set?'), $help);
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
            c('Garden.Thumbnail.Size')
        );
    }
}
