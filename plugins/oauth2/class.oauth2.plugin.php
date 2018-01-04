<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Class OAuth2Plugin
 *
 * Expose the functionality of the core class Gdn_OAuth2 to create SSO workflows.
 */

class OAuth2Plugin extends Gdn_OAuth2 {
    /**
     * Set the key for saving OAuth settings in GDN_UserAuthenticationProvider
     */
    public function __construct() {
        $this->setProviderKey('oauth2');
        $this->settingsView = 'plugins/settings/oauth2';
    }
}
