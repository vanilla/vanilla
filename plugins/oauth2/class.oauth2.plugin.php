<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
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
        $providers = Gdn_AuthenticationProviderModel::getWhereStatic(['AuthenticationSchemeAlias' => 'oauth2']);
        $providerKeys = array_column($providers, 'AuthenticationKey');
        $this->setProviderKey($providerKeys);
        $this->settingsView = 'plugins/settings/oauth2';
    }
}
