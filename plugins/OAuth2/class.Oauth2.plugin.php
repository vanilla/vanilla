<?php

$PluginInfo['oauth2'] = array(
    'Name' => 'OAuth2 SSO',
    'ClassName' => "OAuth2Plugin",
    'Description' => 'Connect to an authentication provider to allow users to log on using SSO.',
    'Version' => '1.0.0',
    'RequiredApplications' => array('Vanilla' => '2.0'),
    'SettingsUrl' => '/settings/oauth2',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true
);

class OAuth2Plugin extends Gdn_OAuth2 {

    protected $settingsView = 'settings/oauth2';

    public function __construct() {
        $this
            ->setProviderKey('oauth2');
    }
}
