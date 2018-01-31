<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

/**
 * Class SsoUtils
 */
class SsoUtils {
    /**
     * Create a CSRF token to verify on a subsequent request.
     *
     * @return A CSRF token.
     */
    public static function createCSRFToken() {
        return Gdn::session()->generateCSRFToken();
    }

    /**
     * Verify a CSRF token.
     * This function will stash the token (using the context) so that it works with postbacks.
     *
     * @param string $context Context defining where this function is used.
     * @param $suppliedCSRFToken
     * @throws Gdn_UserException If the CSRF token is invalid/expired.
     */
    public static function verifyCSRFToken($context, $suppliedCSRFToken) {
        if (empty($suppliedCSRFToken)) {
            throw new Gdn_UserException(t('Invalid CSRF token supplied.'), 403);
        }

        $storedCSRFData = null;
        $isCSRFDataValid = false;
        try {
            $storedCSRFData = Gdn::session()->consumeCSRFToken($suppliedCSRFToken);
            // Stash the token in case we post back!
            Gdn::session()->stash("{$context}CSRF", $storedCSRFData);
            $isCSRFDataValid = true;
        } catch (Exception $e){}

        if (!$storedCSRFData) {
            $storedCSRFData = Gdn::session()->stash("{$context}CSRF", '', false);
            $isCSRFDataValid = Gdn::session()->isCSRFDataValid($suppliedCSRFToken, $storedCSRFData);
        }

        if (!$isCSRFDataValid) {
            throw new Gdn_UserException(t('Invalid/Expired CSRF token.'), 400);
        }
    }
}
