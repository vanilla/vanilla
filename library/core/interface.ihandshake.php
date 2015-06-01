<?php
/**
 * Handshake interface
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0.10
 */

/**
 * A template for handshake-aware authenticator classes.
 */
interface Gdn_IHandshake {

    /**
     * Get the handshake data, such as temporary foreign user identity info
     *
     * In VanillaConnect and ProxyConnect, this function retrieves the temporary handshake data
     * stored in the authenticator's cookie. This information is used as a parameter when calling
     * the Get____FromHandshake() methods decribed below.
     */
    public function GetHandshake();

    /**
     * Fetches the remote user key from the parsed handshake package
     *
     * @param mixed $Handshake
     */
    public function GetUserKeyFromHandshake($Handshake);

    public function GetUserNameFromHandshake($Handshake);

    public function GetProviderKeyFromHandshake($Handshake);

    public function GetTokenKeyFromHandshake($Handshake);

    public function GetUserEmailFromHandshake($Handshake);

    public function Finalize($UserKey, $UserID, $ConsumerKey, $TokenKey, $Payload);

    public function GetHandshakeMode();
}
