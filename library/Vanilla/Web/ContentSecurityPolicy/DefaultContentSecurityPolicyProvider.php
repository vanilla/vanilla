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

        if ($this->config->get("Garden.Embed.Allow")) {
            $policies = array_merge($policies, $this->getFrameAncestors());
        }

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

    /**
    * @return Policy[]
    */
    private function getFrameAncestors(): array {
        $scriptSrcPolicies[] = new Policy(Policy::FRAME_ANCESTORS, '\'self\'');
        $whitelist = $this->config->get('Garden.TrustedDomains', false);
        $trusteddDomains = is_string($whitelist) ? array_filter(explode("\n", $whitelist)) : [];
        if (count($trusteddDomains) > 0) {
            $scriptSrcPolicies[] = new Policy(Policy::FRAME_ANCESTORS, implode(' ', $trusteddDomains));
        } else {
            $remoteUrl = $this->config->get("Garden.Embed.RemoteUrl", false);
            $remoteDomain = is_string($remoteUrl) ? parse_url($remoteUrl, PHP_URL_HOST) : false;
            if (is_string($remoteDomain)) {
                $scriptSrcPolicies[] = new Policy(Policy::FRAME_ANCESTORS, $remoteDomain);
            }
        }
        return $scriptSrcPolicies;
    }
}
