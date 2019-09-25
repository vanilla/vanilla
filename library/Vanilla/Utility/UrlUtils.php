<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use InvalidArgumentException;

/**
 * A collection of url utilities.
 */
class UrlUtils {
    /**
     * This function converts domain names to IDNA ASCII form.
     *
     * @param string $url The domain name to convert.
     * @return mixed Returns the ASCII domain name or null on failure.
     */
    public static function domainAsAscii(string $url): ?string {
        $parsedLink = parse_url($url);
        if (!array_key_exists('host', $parsedLink)) {
            $parsedLink = parse_url('http://'.$url);
            if (!array_key_exists('host', $parsedLink)) {
                throw new InvalidArgumentException('Url Invalid.');
            }
        }
        $parsedLink['host'] = idn_to_ascii($parsedLink['host'], IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46, $idnaInfo);
        if ($idnaInfo['errors'] !== 0) {
            throw new InvalidArgumentException('Domain Invalid.');
        }
        $buildUrl = http_build_url($parsedLink);
        return $buildUrl;
    }
}
