<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent;

use Garden\Http\HttpClient;

/**
 * Base embed factory class.
 *
 * Responsibilities
 * - Matching URLs.
 * - Gathering additional information through I/O.
 * - Create an AbstractEmbed instance.
 */
abstract class AbstractEmbedFactory implements EmbedCreatorInterface {

    /**
     * @var bool Set this flag if you want the embed to be able empty paths.
     *      Eg. http://test.com with no path on the end.
     */
    protected $canHandleEmptyPaths = false;

    /**
     * Determine if factory can handle a particular URL.
     * Default implementation uses getSupportedDomains and getSupportedPathRegex
     *
     * @param string $url
     * @return bool
     */
    public function canHandleUrl(string $url): bool {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            // Not even a URL.
            return false;
        }

        $pieces = parse_url($url);

        // We only allow limited URL schemes.
        $scheme = $pieces['scheme'] ?? '';
        $schemeMatches = in_array($scheme, ['http', 'https']);

        // Validate we have domain. We allow all subdomains here.
        $domain = $pieces['host'] ?? '';
        $domainMatches = false;
        foreach ($this->getSupportedDomains() as $supportedDomain) {
            if ($domain === $supportedDomain || stringEndsWith($domain, ".{$supportedDomain}")) {
                $domainMatches = true;
                break;
            }
        }

        // Check our URL path.
        $path = $pieces['path'] ?? null;
        if ($path === null) {
            $pathMatches = $this->canHandleEmptyPaths;
        } else {
            $pathMatches = (bool) preg_match($this->getSupportedPathRegex($domain), $path);
        }

        return $schemeMatches && $pathMatches && $domainMatches;
    }

    /**
     * Get an array of supported domains for the site.
     *
     * @return array
     */
    abstract protected function getSupportedDomains(): array;

    /**
     * Get a regex to match the path of the site against.
     *
     * @param string $domain The current domain we are matching on.
     *
     * @return string
     */
    abstract protected function getSupportedPathRegex(string $domain): string;
}
