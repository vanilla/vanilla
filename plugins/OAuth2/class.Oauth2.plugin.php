<?php

$PluginInfo['OAuth2'] = array(
    'Name' => 'OAuth2 SSO',
    'ClassName' => "OAuth2Plugin",
    'Description' => 'Connect to an authentication provider to allow users to log on using SSO.',
    'Version' => '1.0.0',
    'RequiredApplications' => array('Vanilla' => '2.0'),
    'SettingsUrl' => '/settings/dashboard',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true
);

class OAuth2Plugin extends Gdn_OAuth2 implements Gdn_IPlugin {

    protected $settingsView = "settings/Oauth2";

    public function __construct() {
        $this
            ->setProviderKey('0Auth2');
    }

    /**
     * Wrapper function for writing a generic settings controller.
     *
     * @param SettingsController $sender.
     * @param SettingsController $args.
     */
    public function settingsController_settings_create($sender, $args) {
        $this->settingsController_oAuth2_create($sender, $args);
    }
}
