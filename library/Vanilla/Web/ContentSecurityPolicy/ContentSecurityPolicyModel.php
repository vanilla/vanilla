<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\ContentSecurityPolicy;

/**
 * Content security policies model.
 */
class ContentSecurityPolicyModel {
    const CONTENT_SECURITY_POLICY = 'Content-Security-Policy';

    /** @var array List of providers. */
    private $providers = [];

    /** @var string Nonce value to embed for all inlined scripts */
    private $nonce;

    /**
     * ContentSecurityPolicyModel constructor.
     */
    public function __construct() {
        $this->nonce = md5(base64_encode(APPLICATION_VERSION.rand(1, 1000000)));
    }

    /**
     * @param ContentSecurityPolicyProviderInterface $provider
     */
    public function addProvider(ContentSecurityPolicyProviderInterface $provider) {
        $this->providers[] = $provider;
    }

    /**
     * Get all policies.
     *
     * @return Policy[]
     */
    public function getPolicies(): array {
        $policies[] = new Policy(Policy::SCRIPT_SRC, '\'nonce-'.$this->getNonce().'\'');
        foreach ($this->providers as $provider) {
            $policies = array_merge($policies, $provider->getPolicies());
        }
        return $policies;
    }

    /**
     * @return string
     */
    public function getNonce(): string {
        return $this->nonce;
    }

    /**
     * Compose content security header string from policies list
     *
     * @param string $filter CSP directive to filter out
     * @return string
     */
    public function getHeaderString(string $filter = 'all'): string {
        $directives = [];
        $policies = $this->getPolicies();
        foreach ($policies as $policy) {
            $directive = $policy->getDirective();
            if ($directive === 'all' || $directive === $filter) {
                if (array_key_exists($directive, $directives)) {
                    $directives[$directive] .= ' ' . $policy->getArgument();
                } else {
                    $directives[$directive] = $directive . ' ' . $policy->getArgument();
                }
            }
        }
        return implode('; ', $directives);
    }
}
