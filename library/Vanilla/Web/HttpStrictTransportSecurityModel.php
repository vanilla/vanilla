<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Vanilla\Contracts\ConfigurationInterface;

/**
 * Http Strict Transport Security model.
 */
class HttpStrictTransportSecurityModel
{
    const HSTS_HEADER = "Strict-Transport-Security";
    const MAX_AGE = "max-age";
    const INCLUDE_SUBDOMAINS = "includeSubDomains";
    const PRELOAD = "preload";

    const MAX_AGE_KEY = "Garden.Security.Hsts.MaxAge";
    const INCLUDE_SUBDOMAINS_KEY = "Garden.Security.Hsts.IncludeSubDomains";
    const PRELOAD_KEY = "Garden.Security.Hsts.Preload";

    const DEFAULT_TTL = 15768000; // 6 months
    const DEFAULT_INCLUDE_SUBDOMAINS = false;
    const DEFAULT_PRELOAD = false;

    /** @var string[]  $additionalSecurityHeaders */
    private $additionalSecurityHeaders = ["contentTypeOptions", "permittedCrossDomain", "xssProtection"];

    /**
     * @var ConfigurationInterface
     */
    private $config;

    /**
     * HttpStrictTransportSecurityModel constructor.
     * @param ConfigurationInterface $config
     */
    public function __construct(ConfigurationInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Compose content hsts header string according to site configuration
     *
     * @return string
     */
    public function getHsts(): string
    {
        $hsts[] = self::MAX_AGE . "=" . $this->getMaxAge();
        if ($this->includeSubDomains()) {
            $hsts[] = self::INCLUDE_SUBDOMAINS;
        }
        if ($this->preload()) {
            $hsts[] = self::PRELOAD;
        }

        return implode("; ", $hsts);
    }

    /**
     * Get max-age HSTS directive value.
     *
     * @return int
     */
    private function getMaxAge(): int
    {
        return $this->config->get(self::MAX_AGE_KEY, self::DEFAULT_TTL);
    }

    /**
     * Check if HSTS includeSubDomains directive is enabled.
     *
     * @return bool
     */
    private function includeSubDomains(): bool
    {
        return $this->config->get(self::INCLUDE_SUBDOMAINS_KEY, self::DEFAULT_INCLUDE_SUBDOMAINS);
    }

    /**
     * Check if HSTS preload directive is enabled.
     *
     * @return bool
     */
    private function preload(): bool
    {
        return $this->config->get(self::PRELOAD_KEY, self::DEFAULT_PRELOAD);
    }

    /**
     * Get all additional security headers.
     *
     * @return array
     */
    public function getAdditionalSecurityHeaders(): array
    {
        return $this->additionalSecurityHeaders;
    }

    /**
     * Get requested header response data by name.
     *
     * @param string $headerName the method to get the specific response header name & value
     * @return array
     */
    public function getSecurityHeaderEntry($headerName): array
    {
        return $this->{$headerName}();
    }

    /**
     * Will prevent the browser from MIME-sniffing a response away from the declared content-type.
     *
     * @return array
     */
    private function contentTypeOptions(): array
    {
        return ["X-Content-Type-Options", "nosniff"];
    }

    /**
     * An XML document that grants a web client permission to handle data across domains.
     *
     * @return array
     */
    private function permittedCrossDomain(): array
    {
        return ["X-Permitted-Cross-Domain-Policies", "master-only"];
    }

    /**
     * Header has been deprecated by modern browsers and its use can introduce additional security issues on client
     * recommended to set the header as X-XSS-Protection: 0 to disable the XSS Auditor, and not allow it to
     * take the default behavior of the browser handling the response. Use Content-Security-Policy instead.
     *
     * @return array
     */
    private function xssProtection(): array
    {
        return ["X-XSS-Protection", "0"];
    }
}
