<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

/**
 * A collection of url utilities.
 */
class UrlUtils {
    /**
     * This function converts domain names to IDNA ASCII form.
     *
     * @param string $link The domain name to convert.
     * @return mixed Returns the encoded domain name. or false on failure.
     */
    public static function punyEncode(string $link) {
        idn_to_ascii($link, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46, $idnaInfo);
        $link = $idnaInfo['result'];
        if ($idnaInfo['errors'] !== 0) {
            $link = false;
        }
        return $link;
    }
}
