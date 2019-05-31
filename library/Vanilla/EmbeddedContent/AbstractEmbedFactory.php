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
abstract class AbstractEmbedFactory {

    /** @var HttpClient */
    private $httpClient;

    /**
     * Dependency Injection
     *
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient) {
        $this->httpClient = $httpClient;
    }

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
        $scheme = $pieces['scheme'];
        $schemeMatches = in_array($scheme, ['http', 'https']);

        // Validate he have domain. We allow all subdomains here.
        $domain = $pieces['domain'];
        $domainMatches = false;
        foreach ($this->getSupportedDomains() as $supportedDomain) {
            if ($domain === $supportedDomain || stringEndsWith($domain, ".{$supportedDomain}")) {
                $domainMatches = true;
                break;
            }
        }

        // Check our URL path.
        $path = $pieces['path'];
        $pathMatches = preg_match($this->getSupportedPathRegex(), $path);

        return $schemeMatches && $pathMatches && $domainMatches;
    }

    /**
     * Create an embed class from a given URL.
     *
     * @param string $url
     *
     * @return AbstractEmbed
     */
    abstract public function createEmbedForUrl(string $url): AbstractEmbed;

    /**
     * Get an array of supported domains for the site.
     *
     * @return array
     */
    abstract public function getSupportedDomains(): array;

    /**
     * Get a regex to match the path of the site against.
     *
     * @return string
     */
    abstract public function getSupportedPathRegex(): string;
}
