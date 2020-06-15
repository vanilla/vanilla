<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\ContentSecurityPolicy;

/**
 * Vanilla whitelist content security policy provider
 */
class VanillaWhitelistContentSecurityPolicyProvider implements ContentSecurityPolicyProviderInterface {

    const VANILLA_WHITELIST = [
        'https://www.google.com'
    ];

    /**
     * @inheritdoc
     */
    public function getPolicies(): array {
        $policies[] = new Policy(Policy::SCRIPT_SRC, implode(' ', self::VANILLA_WHITELIST));

        return $policies;
    }
}
