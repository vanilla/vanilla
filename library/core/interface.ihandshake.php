<?php
/**
 * Handshake interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0.10
 */

/**
 * A template for handshake-aware authenticator classes.
 */
interface Gdn_IHandshake {

    /**
     * Get handshake data, such as temporary foreign user identity info.
     *
     * In VanillaConnect and ProxyConnect, this function retrieves the temporary handshake data
     * stored in the authenticator's cookie. This information is used as a parameter when calling
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
