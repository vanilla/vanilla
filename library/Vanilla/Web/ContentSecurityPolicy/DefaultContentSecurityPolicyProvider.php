<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\ContentSecurityPolicy;

/**
 * Default content security policy provider.
 */
class DefaultContentSecurityPolicyProvider implements ContentSecurityPolicyProviderInterface {

    /**
     * @var \Gdn_Configuration
     */
    private $config;

    /**
     * DefaultContentSecurityPolicyProvider constructor.
     * @param \Gdn_Configuration $config
     */
    public function __construct(\Gdn_Configuration $config) {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function getPolicies(): array {
        $policies = [];
        $policies = array_merge($policies, $this->getScriptSources());

        return $policies;
    }

    /**
     * @return Policy[]
     */
    private function getScriptSources(): array {
        $scriptSrcPolicies[] = new Policy(Policy::SCRIPT_SRC, '\'self\'');
        if ($whitelist = $this->config->get('ContentSecurityPolicy.ScriptSrc.AllowedDomains', false)) {
            $scriptSrcPolicies[] = new Policy(Policy::SCRIPT_SRC, implode(' ', $whitelist));
        }

        return $scriptSrcPolicies;
    }
}
