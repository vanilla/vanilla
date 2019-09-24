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
        $protocols = [
            'http://',
            'https://',
        ];
        // Remove http:// and https:// before calling idn_to_ascii.
        foreach($protocols as $protocol) {
            if(strpos($link, $protocol) === 0) {
                $link = str_replace($protocol, '', $link);
            }
        }
        idn_to_ascii($link, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46, $idnaInfo);
        $link = $idnaInfo['result'];
        if ($idnaInfo['errors'] !== 0) {
            $link = null;
        }
        return $link;
    }
}
