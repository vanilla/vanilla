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
class UrlUtils
{
    /**
     * Transform any unicode characters in the domain of a well-formed URL per IDNA encoding.
     * Note that the terminology used here for "domain" is more accurately represented as the URL's authority,
     * i.e. <code>userinfo[at]host:port</code>
     *
     * @link https://en.wikipedia.org/wiki/Uniform_Resource_Identifier#Syntax URI syntax
     * @link https://en.wikipedia.org/wiki/IDN_homograph_attack IDN homograph attack
     * @link http://homoglyphs.net Homoglyph generator
     * @link https://www.charset.org/punycode Punycode converter
     *
     * @param string $url The URL to convert.
     * @throws InvalidArgumentException If the host cannot be retrieved from the URL.
     * @throws InvalidArgumentException Error performing IDN translation of any component within authority.
     * @return string Returns an absolute, well-formed URL with IDN translation on the authority.
     */
    public static function domainAsAscii(string $url): ?string
    {
        $parsedLink = parse_url($url);
        if ($parsedLink === false) {
            throw new InvalidArgumentException("Url Invalid.");
        }
        if (!array_key_exists("host", $parsedLink)) {
            $parsedLink = parse_url("http://" . $url);
            if ($parsedLink === false || !array_key_exists("host", $parsedLink)) {
                throw new InvalidArgumentException("Url Invalid.");
            }
        }
        if (empty(trim(urldecode($parsedLink["host"])))) {
            throw new InvalidArgumentException("Url Invalid.");
        }

        // Include the userinfo portion of the authority in the IDN to ASCII translation
        // to avoid obscuring content in the URL intended potentially to mislead observers
        // as to what the URL's host actually is based on the position of the userinfo
        // before the host within the URL.
        // See: https://datatracker.ietf.org/doc/html/rfc3986#section-3.2
        foreach (["host" => "Domain", "user" => "Username", "pass" => "Password"] as $index => $name) {
            if (!empty($parsedLink[$index])) {
                $idnaInfo = [];
                $parsedLink[$index] = idn_to_ascii(
                    $parsedLink[$index],
                    IDNA_NONTRANSITIONAL_TO_ASCII,
                    INTL_IDNA_VARIANT_UTS46,
                    $idnaInfo
                );
                if ($idnaInfo["errors"] !== 0) {
                    throw new InvalidArgumentException("{$name} Invalid.");
                }
            }
        }

        $buildUrl = http_build_url($parsedLink);
        return $buildUrl;
    }

    /**
     * Verify if domain string is Ascii.
     *
     * @param string $domain
     * @return bool
     */
    public static function isAsciiDomain(string $domain): bool
    {
        // detect if any character falls out of the Ascii chars list.
        return preg_match('/[^\x20-\x7e]/', $domain) == 0;
    }

    /**
     * Generate a new URI by replacing querystring elements from an existing URI.
     *
     * @param UriInterface $uri The base URI.
     * @param array $replace The querystring replacement.
     * @return UriInterface
     */
    public static function replaceQuery(UriInterface $uri, array $replace): UriInterface
    {
        parse_str($uri->getQuery(), $query);
        $query = array_replace($query, $replace);
        $result = $uri->withQuery(http_build_query($query));
        return $result;
    }

    /**
     * Concatenate a query string to a URL with the proper '?' or '&' character.
     *
     * @param string $uri
     * @param string|array $query
     * @return string
     */
    public static function concatQuery(string $uri, $query): string
    {
        if (empty($query)) {
            return $uri;
        } elseif (is_array($query)) {
            $query = http_build_query($query);
        }
        $sep = strpos($uri, "?") === false ? "?" : "&";
        return $uri . $sep . $query;
    }

    /**
     * URL encode a decoded path.
     *
     * @param string $path
     * @return string
     */
    public static function encodePath(string $path): string
    {
        $parts = explode("/", $path);
        $parts = array_map("rawurlencode", $parts);

        $r = implode("/", $parts);
        return $r;
    }

    /**
     * URL decode an encoded path.
     *
     * @param string $path
     * @return string
     */
    public static function decodePath(string $path): string
    {
        $parts = explode("/", $path);
        $parts = array_map("rawurldecode", $parts);

        $r = implode("/", $parts);
        return $r;
    }

    /**
     * Make sure that a URL has appropriately encoded characters.
     *
     * Many browser can handle unicode characters in their URLs. However, such characters are not actually valid characters
     * and will fail validation in many cases. This method takes improperly encoded characters and encodes them.
     *
     * @param UriInterface $url The URL to process.
     * @return UriInterface Returns a URL string with characters appropriately encoded.
     */
    public static function normalizeEncoding(UriInterface $url): UriInterface
    {
        $path = $url->getPath();
        $str = preg_replace_callback(
            "`[^%a-zA-Z0-9./_-]+`",
            function ($s): string {
                return rawurlencode($s[0]);
            },
            $path
        );
        $result = $url->withPath($str);
        return $result;
    }

    /**
     * Get the full url of a source path relative to a base url.
     *
     * Takes a source (or href) path (i.e. an image src from an HTML page), and an
     * associated URL (i.e. the page that the image appears on), and returns the
     * absolute URL for the source path (including host & protocol).
     *
     * @param string $href The source path to make absolute (if not absolute already).
     * @param string $url The full url to the page containing the src reference.
     * @return string|null Absolute source path or null if not possible.
     */
    public static function ensureAbsoluteUrl(string $href, string $url): ?string
    {
        $parsedUrl = parse_url($url);
        $parsedHref = parse_url($href);

        if ($parsedUrl === false || $parsedHref === false) {
            return null;
        }

        // If the href already has a protocol, then it's an absolute URL and can be used as-is.
        if (isset($parsedHref["scheme"])) {
            // We only consider http and https protocols, return null if we have neither of those.
            return in_array($parsedHref["scheme"], ["http", "https"], true) ? $href : null;
        }

        // The HREF must provide a host or path. If it has neither, return null.
        if (empty($parsedHref["host"]) && empty($parsedHref["path"])) {
            return null;
        }

        // Combine into one parsed array.
        $parts = $parsedHref + $parsedUrl + ["path" => null];

        // Make sure final URL array has a host and a valid protocol.
        if (empty($parts["host"]) || !in_array($parts["scheme"] ?? null, ["http", "https"], true)) {
            return null;
        }

        // Build the path relative to the current `$url`.
        // If the href path starts with `/`, then we have a host-relative path, so we can skip building the path.
        if (isset($parsedHref["path"]) && !str_starts_with($parsedHref["path"], "/")) {
            $urlPathParts = self::pathToArray($parsedUrl["path"] ?? "");
            $hrefPathParts = self::pathToArray($parsedHref["path"]);

            // If there is a trailing slash, this pops off resulting empty string from the end of the array.
            // If not, this pops of the basename of the path which should not be included in the final URL.
            array_pop($urlPathParts);

            foreach ($hrefPathParts as $part) {
                if ($part === ".") {
                    // Skip for current directory marker.
                    continue;
                }

                if ($part === "..") {
                    // Pop off path segment for upper directory marker.
                    array_pop($urlPathParts);
                    continue;
                }

                $urlPathParts[] = $part;
            }

            $parts["path"] = "/" . implode("/", $urlPathParts);
        }

        return self::buildUrl($parts);
    }

    /**
     * Helper method which takes a http path and normalizes backslashes to forward slashes,
     * trims leading slashes, and the converts the path to an array.
     *
     * @param string $path
     * @return array
     */
    private static function pathToArray(string $path): array
    {
        $path = str_replace("\\", "/", $path);
        $path = ltrim($path, "/");
        return explode("/", $path);
    }

    /**
     * Builds a URL string out of the output of `parse_url`.
     *
     * @param array $parsedUrl
     * @return string
     */
    public static function buildUrl(array $parsedUrl): string
    {
        $scheme = isset($parsedUrl["scheme"]) ? $parsedUrl["scheme"] . "://" : "";
        $host = $parsedUrl["host"] ?? "";
        $port = isset($parsedUrl["port"]) ? ":" . $parsedUrl["port"] : "";
        $user = $parsedUrl["user"] ?? "";
        $pass = isset($parsedUrl["pass"]) ? ":" . $parsedUrl["pass"] : "";
        $pass = $user || $pass ? "$pass@" : "";
        $path = $parsedUrl["path"] ?? "";
        $query = isset($parsedUrl["query"]) ? "?" . $parsedUrl["query"] : "";
        $fragment = isset($parsedUrl["fragment"]) ? "#" . $parsedUrl["fragment"] : "";

        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}
