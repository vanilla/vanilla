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
class HttpStrictTransportSecurityModel {
    const HSTS_HEADER = 'Strict-Transport-Security';
    const MAX_AGE = 'max-age';
    const INCLUDE_SUBDOMAINS = 'includeSubDomains';
    const PRELOAD = 'preload';

    const MAX_AGE_KEY = 'Garden.Security.Hsts.MaxAge';
    const INCLUDE_SUBDOMAINS_KEY = 'Garden.Security.Hsts.IncludeSubDomains';
    const PRELOAD_KEY = 'Garden.Security.Hsts.Preload';

    const DEFAULT_TTL = 604800; // 1 week
    const DEFAULT_INCLUDE_SUBDOMAINS = false;
    const DEFAULT_PRELOAD = false;

    /**
     * @var ConfigurationInterface
     */
    private $config;

    /**
     * HttpStrictTransportSecurityModel constructor.
     * @param ConfigurationInterface $config
     */
    public function __construct(ConfigurationInterface $config) {
        $this->config = $config;
    }

    /**
     * Compose content hsts header string according to site configuration
     *
     * @return string
     */
    public function getHsts(): string {
        $hsts[] = self::MAX_AGE.'='.$this->getMaxAge();
        if ($this->includeSubDomains()) {
            $hsts[] = self::INCLUDE_SUBDOMAINS;
        }
        if ($this->preload()) {
            $hsts[] = self::PRELOAD;
        }

        return implode('; ', $hsts);
    }

    /**
     * Get max-age HSTS directive value.
     *
     * @return int
     */
    private function getMaxAge(): int {
        return $this->config->get(self::MAX_AGE_KEY, self::DEFAULT_TTL);
    }

    /**
     * Check if HSTS includeSubDomains directive is enabled.
     *
     * @return bool
     */
    private function includeSubDomains(): bool {
        return $this->config->get(self::INCLUDE_SUBDOMAINS_KEY, self::DEFAULT_INCLUDE_SUBDOMAINS);
    }

    /**
     * Check if HSTS preload directive is enabled.
     *
     * @return bool
     */
    private function preload(): bool {
        return $this->config->get(self::PRELOAD_KEY, self::DEFAULT_PRELOAD);
    }
}
