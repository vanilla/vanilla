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
     * Transform any unicode characters in the domain of a well-formed URL per IDNA encoding.
     *
     * @param string $url The domain name to convert.
     * @throws InvalidArgumentException If the host cannot be retrieved from the URL.
     * @throws InvalidargumentException If there was an error performing IDN translation of the domain.
     * @return string Returns an absolute, well-formed URL with IDN translation on the domain.
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
