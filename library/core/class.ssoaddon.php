<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Sub-class of plugins for SSO authentication.
 */
abstract class SSOAddon extends Gdn_Plugin {

    /**
     * Gets the AuthenticationScheme to match the db's 'AuthenticationSchemeAlias' column.
     *
     * @return string
     */
    abstract protected function getAuthenticationSchemeAlias(): string;

    /**
     * Sets the value of 'IsDefault' to 0 when the plugin is disabled.
     */
    public function onDisable() {
        /** @var Gdn_AuthenticationProviderModel $authenticationProvider */
        $authenticationProvider = Gdn::getContainer()->get(Gdn_AuthenticationProviderModel::class);
        $authenticationProvider->update(['IsDefault' => 0], ['AuthenticationSchemeAlias' => $this->getAuthenticationSchemeAlias()]);
    }
}
