<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\ContentSecurityPolicy;

/**
 * Content security policy provider interface
 */
interface ContentSecurityPolicyProviderInterface {
    /**
     * Get content security policies.
     *
     * @return Policy[]
     */
    public function getPolicies(): array;
}
