<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

$PluginInfo['oauth2'] = array(
    'Name' => 'OAuth 2.0',
    'ClassName' => "OAuth2Plugin",
    'Description' => 'Connect forum sign on to a basic OAuth 2.0 provider.',
    'Version' => '1.0.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'SettingsUrl' => '/settings/oauth2',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true,
    'Group' => 'SSO',
    'Author' => "Patrick Kelly",
    'AuthorEmail' => 'patrick.k@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
);

/**
 * Class OAuth2Plugin
 *
 * Expose the functionality of the core class Gdn_OAuth2 to create SSO workflows.
 */

class OAuth2Plugin extends Gdn_OAuth2 {
    /**
     * @var string Sets the settings view in the dashboard.
     */
    protected $settingsView = 'settings/oauth2';


    /**
     * Set the key for saving OAuth settings in GDN_UserAuthenticationProvider
     */
    public function __construct() {
        $this->setProviderKey('oauth2');
    }
}
