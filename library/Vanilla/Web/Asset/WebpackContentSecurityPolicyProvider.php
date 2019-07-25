<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Asset;

use Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyProviderInterface;
use Vanilla\Web\ContentSecurityPolicy\Policy;

/**
 * Default content security policy provider.
 */
class WebpackContentSecurityPolicyProvider implements ContentSecurityPolicyProviderInterface {

    /**
     * @var WebpackAssetProvider
     */
    private $webpack;

    /**
     * WebpackContentSecurityPolicyProvider constructor.
     * @param WebpackAssetProvider $webpack
     */
    public function __construct(WebpackAssetProvider $webpack) {
        $this->webpack = $webpack;
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
        $scriptSrcPolicies = [];
        if ($this->webpack->isHotReloadEnabled()) {
            $scriptSrcPolicies[] = new Policy(Policy::SCRIPT_SRC, '\'unsafe-eval\'');
            $scriptSrcPolicies[] = new Policy(Policy::SCRIPT_SRC, $this->webpack->getHotReloadSocketAddress());
        }

        return $scriptSrcPolicies;
    }
}
