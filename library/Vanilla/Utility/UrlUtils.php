<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

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
                // @codeCoverageIgnoreStart
                throw new InvalidArgumentException('Url Invalid.');
                // @codeCoverageIgnoreEnd
            }
        }
        $parsedLink['host'] = idn_to_ascii($parsedLink['host'], IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46, $idnaInfo);
        if ($idnaInfo['errors'] !== 0) {
            throw new InvalidArgumentException('Domain Invalid.');
        }
        $buildUrl = http_build_url($parsedLink);
        return $buildUrl;
    }

    /**
     * Generate a new URI by replacing querystring elements from an existing URI.
     *
     * @param UriInterface $uri The base URI.
     * @param array $replace The querystring replacement.
     * @return UriInterface
     */
    public static function replaceQuery(UriInterface $uri, array $replace): UriInterface {
        parse_str($uri->getQuery(), $query);
        $query = array_replace($query, $replace);
        $result = $uri->withQuery(http_build_query($query));
        return $result;
    }

    /**
     * URL encode a decoded path.
     *
     * @param string $path
     * @return string
     */
    public static function encodePath(string $path): string {
        $parts = explode('/', $path);
        $parts = array_map('rawurlencode', $parts);

        $r = implode('/', $parts);
        return $r;
    }

    /**
     * URL decode an encoded path.
     *
     * @param string $path
     * @return string
     */
    public static function decodePath(string $path): string {
        $parts = explode('/', $path);
        $parts = array_map('rawurldecode', $parts);

        $r = implode('/', $parts);
        return $r;
    }
}
