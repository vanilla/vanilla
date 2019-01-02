<?php
/**
 * Handshake interface
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0.10
 */

/**
 * A template for handshake-aware Authenticator classes.
 */
interface Gdn_IHandshake {

    /**
     * Get handshake data, such as temporary foreign user identity info.
     *
     * In VanillaConnect and ProxyConnect, this function retrieves the temporary handshake data
     * stored in the Authenticator's cookie. This information is used as a parameter when calling
     * the get____FromHandshake() methods described below.
     */
    public function getHandshake();

    /**
     * Fetch the remote user key from the parsed handshake package.
     *
     * @param mixed $handshake The handshake data to check.
     */
    public function getUserKeyFromHandshake($handshake);

    public function getUserNameFromHandshake($handshake);

    public function getProviderKeyFromHandshake($handshake);

    public function getTokenKeyFromHandshake($handshake);

    public function getUserEmailFromHandshake($handshake);

    public function finalize($userKey, $userID, $consumerKey, $tokenKey, $payload);

    public function getHandshakeMode();
}
