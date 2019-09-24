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
     * @return mixed Returns the ASCII domain name or null on failure.
     */
    public static function domainAsAscii(string $link): ?string {
        $parsedLink = parse_url($link, PHP_URL_HOST);
        idn_to_ascii($parsedLink, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46, $idnaInfo);
        if ($idnaInfo['result'] !== $parsedLink) {
            $link = $idnaInfo['result'];
            if ($idnaInfo['errors'] !== 0) {
                $link = null;
            }
        }
        return $link;
    }
}
