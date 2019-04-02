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

    /** @var array List of providers. */
    private $providers = [];

    /**
     * @param ContentSecurityPolicyProviderInterface $provider
     */
    public function addProvider(ContentSecurityPolicyProviderInterface $provider) {
        $this->providers[] = $provider;
    }

    /**
     * Get all policies grouped by directive type.
     *
     * @return Policy[]
     */
    public function getDirectives(): array {
        $counters = [];
        foreach ($this->providers as $provider) {
            $counters = array_merge($counters, $provider->getPolicies());
        }
        return $counters;
    }
}
