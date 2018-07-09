<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla;

/**
 * Class ArrayAccessTrait.
 * Implementation of the ArrayAccess functions.
 *
 * When using this object as an array its properties are referenced.
 */
trait TokenSigningTrait {

    public $secret;
    //protected static $tokenIdentifier = "token";
    /**
     * Get the secret.
     *
     * @return mixed Returns the secret.
     */
    public function getSecret() {
        return $this->secret;
    }

    /**
     * Set the secret.
     *
     * @param mixed $secret
     * @return $this
     */
    public function setSecret($secret) {
        $this->secret = $secret;
        return $this;
    }

    /**
     * Base64 Encode a string, but make it suitable to be passed in a url.
     *
     * @param string $str The string to encode.
     * @return string The encoded string.
     */
    protected static function base64urlEncode($str) {
        return trim(strtr(base64_encode($str), '+/', '-_'), '=');
    }

    /**
     * Decode a string that was encoded using base64UrlEncode().
     *
     * @param string $str The encoded string.
     * @return string The decoded string.
     */
    protected static function base64urlDecode($str) {
        return base64_decode(strtr($str, '-_', '+/'));
    }

    /**
     * Generate a random token.
     *
     * This token will go into the database, but must be signed before being given to the client.
     *
     * @return string Returns a 32 character token string.
     */
    public function randomToken() {
        $token = self::base64urlEncode(openssl_random_pseudo_bytes(24));
        return $token;
    }

    /**
     * Verify a token's signature.
     *
     * @param string $accessToken The full access token.
     * @oaran string $tokenIdentifier the model that is calling verify signature.
     * @param bool $throw Whether or not to throw an exception on a verification error.
     * @return bool Returns **true** if the token's expiry date and signature is valid or **false** otherwise.
     * @throws \Exception Throws an exception if the token is invalid and {@link $throw} is **true**.
     */
    public function verifyTokenSignature($accessToken, $tokenIdentifier, $throw = false) {
        $parts = explode('.', $accessToken);

        if (empty($accessToken)) {
            return $this->tokenError('Missing'. $tokenIdentifier, 401, $throw);
        }

        if (count($parts) !== 4) {
            return $this->tokenError($tokenIdentifier.' missing parts.', 401, $throw);
        }

        list($version, $token, $expireStr, $sig) = $parts;

        $expires = $this->decodeDate($expireStr);
        if ($expires === null) {
            return $this->tokenError('Your '.$tokenIdentifier.' has an invalid expiry date.', 401, $throw);
        } elseif ($expires < time()) {
            return $this->tokenError('Your '.$tokenIdentifier.' has expired.', 401, $throw);
        }

        $checkToken = $this->signToken($token, $expires);
        if (!hash_equals($checkToken, $accessToken)) {
            return $this->tokenError('Your '.$tokenIdentifier.' has an invalid signature.', 401, $throw);
        }

        return true;
    }

    /**
     * Sign a token generated with {@link randomToken()}.
     *
     * @param string $token The token to sign.
     * @param mixed $expires The expiry date of the token.
     * @return string
     */
    public function signToken($token, $expires = '2 months') {
        if (empty($this->getSecret())) {
            // This means something has been misconfigured. Throw a noisy error.
            throw new \Exception("No secret to sign tokens with.", 500);
        }

        $str = 'va.'.$token.'.'.$this->encodeDate($expires);
        $sig = self::base64urlEncode(hash_hmac('sha256', $str, $this->secret, true));

        // Use a substring of the signature because we don't want the tokens to be too massive.
        // The signature is only the first line of defence. The database is the final verification.
        $result = $str.'.'.substr($sig, 0, 7);
        return $result;
    }

    /**
     * Optionally throw an exception.
     *
     * @param string $message The error message.
     * @param int $code The error code.
     * @param bool $throw Whether or not to throw an exception.
     * @return bool Returns **false**.
     * @throws Exception Throws an exception if {@link $throw} is true.
     */
    private function tokenError($message, $code = 401, $throw = false) {
        if ($throw) {
            throw new \Exception($message, $code);
        }
        return false;
    }

    /**
     * Serialize a token entry for direct insertion to the database.
     *
     * @param array &$row The row to encode.
     */
    protected function encodeRow(&$row) {
        if (is_object($row) && !$row instanceof ArrayAccess) {
            $row = (array)$row;
        }

        foreach (['Scope', 'Attributes'] as $field) {
            if (isset($row[$field]) && is_array($row[$field])) {
                $row[$field] = empty($row[$field]) ? null : json_encode($row[$field], JSON_UNESCAPED_SLASHES);
            }
        }
    }

    /**
     * Unserialize a row from the database for API consumption.
     *
     * @param array &$row The row to decode.
     */
    protected function decodeRow(&$row) {
        $isObject = false;
        if (is_object($row) && !$row instanceof ArrayAccess) {
            $isObject = true;
            $row = (array)$row;
        }

        $row['InsertIPAddress'] = ipDecode($row['InsertIPAddress']);

        foreach (['Scope', 'Attributes'] as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $row[$field] = json_decode($row[$field], true);
            }
        }

        if ($isObject) {
            $row = (object)$row;
        }
    }

    /**
     * Force a value into a timestamp.
     *
     * @param mixed $dt A timestamp or date string.
     * @return false|int
     */
    private function toTimestamp($dt) {
        if (is_numeric($dt)) {
            return (int)$dt;
        } elseif ($ts = strtotime($dt)) {
            return $ts;
        }
        return null;
    }

    /**
     * Base 64 encode a date.
     *
     * @param mixed $dt A timestamp or date string.
     * @return string Returns the encoded date.
     */
    private function encodeDate($dt) {
        $timestamp = $this->toTimestamp($dt);
        $result = self::base64urlEncode(pack('I', $timestamp));
        return $result;
    }

    /**
     * Base 64 decode a date.
     *
     * @param string $str An encoded date.
     * @return int Returns a timestamp.
     */
    private function decodeDate($str) {
        $arr = unpack('I*', self::base64urlDecode($str));
        if (empty($arr[1]) || !is_int($arr[1])) {
            return null;
        }
        return $arr[1];
    }

    /**
     * Generate and sign a token.
     *
     * @param string $expires When the token expires.
     * @return string
     */
    public function randomSignedToken($expires = '2 months') {
        return $this->signToken($this->randomToken(), $expires);
    }

    /**
     * Trim the expiry date and signature off of a token.
     *
     * @param string $accessToken The access token to trim.
     */
    public function trim($accessToken) {
        if (strpos($accessToken, '.') !== false) {
            list($_, $token) = explode('.', $accessToken);
            return $token;
        }
        return $accessToken;
    }


}
