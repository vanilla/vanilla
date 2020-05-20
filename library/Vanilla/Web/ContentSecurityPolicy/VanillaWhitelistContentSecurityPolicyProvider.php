<?php


namespace Vanilla\Web\ContentSecurityPolicy;


class VanillaWhitelistContentSecurityPolicyProvider implements ContentSecurityPolicyProviderInterface {

    const VANILLA_WHITELIST = [
        'https://www.google.com/recaptcha/api.js'
    ];

    /**
     * @inheritdoc
     */
    public function getPolicies(): array {
        $policies[] = new Policy(Policy::SCRIPT_SRC, implode(' ', self::VANILLA_WHITELIST));

        return $policies;
    }
}
