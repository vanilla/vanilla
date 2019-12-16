<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\ContentSecurityPolicy;

use Psr\Log\LoggerInterface;
use Vanilla\Contracts\Web\UASnifferInterface;

/**
 * Content security policies model.
 */
class ContentSecurityPolicyModel {
    const CONTENT_SECURITY_POLICY = 'Content-Security-Policy';

    const X_FRAME_OPTIONS = 'X-Frame-Options';

    /** @var array List of providers. */
    private $providers = [];

    /** @var string Nonce value to embed for all inlined scripts */
    private $nonce;

    /** @var UASnifferInterface */
    private $isIE11;

    /** @var LoggerInterface */
    private $logger;

    /**
     * ContentSecurityPolicyModel constructor.
     *
     * @param UASnifferInterface $ieDetector
     * @param LoggerInterface $logger
     */
    public function __construct(UASnifferInterface $ieDetector, LoggerInterface $logger) {
        $this->isIE11 = $ieDetector->isIE11();
        $this->logger = $logger;
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
            if ($filter === 'all' || $directive === $filter) {
                if (array_key_exists($directive, $directives)) {
                    $directives[$directive] .= ' ' . $policy->getArgument();
                } else {
                    $directives[$directive] = $directive . ' ' . $policy->getArgument();
                }
            }
        }
        return implode('; ', $directives);
    }

    /**
     * Get an x-frame options header for backwards compatibility.
     *
     * @return string
     */
    public function getXFrameString(): ?string {
        $policies = $this->getPolicies();

        $ancestorArguments = [];
        foreach ($policies as $policy) {
            if ($policy->getDirective() === Policy::FRAME_ANCESTORS) {
                $ancestors = explode(' ', $policy->getArgument());
                foreach ($ancestors as $ancestor) {
                    $ancestorArguments []= $ancestor;
                }
            }
        }

        if (count($ancestorArguments) <= 1 && $ancestorArguments[0] === Policy::FRAME_ANCESTORS_SELF) {
            return Policy::X_FRAME_SAMEORIGIN;
        }

        // Get all of the policy domains that aren't 'self'.
        $ancestorArguments = array_filter($ancestorArguments, function ($arg) {
            return $arg !== Policy::FRAME_ANCESTORS_SELF;
        });
        $ancestorArguments = array_values($ancestorArguments);

        // If we have just one, we can support ALLOW_FROM.
        // See https://tools.ietf.org/html/rfc7034#section-2.3.2.3
        if (count($ancestorArguments) <= 1) {
            return Policy::X_FRAME_ALLOW_FROM . ' ' . $ancestorArguments[0];
        }

        // All other supported browsers support Content-Security-Policy
        // And do not support multiple ALLOW_FROM headers.
        if (!$this->isIE11) {
            return null;
        }

        // We have IE11 & a CSP that is not possible to express as an X-Frame-Origin.
        // This makes users in that browser susceptible to clickjacking.
        // We can't actually return a header here without potentially breaking an embed.
        // And the "workaround" described in RFC7034 isn't really feasible.
        // @see https://tools.ietf.org/html/rfc7034#section-2.1
        //
        // Because of the circumstances we are just going ot log a warning here.

        $message = <<<WARNING
Potential Clickjacking vulnerability. Site's trusted domains require evaluation.

Served browser does not respect Content-Security-Policy and multiple domains cannot be encoded in an X-Frame-Options header.
WARNING;

        trigger_error($message, E_USER_WARNING);
        $this->logger->warning($message);
        return null;
    }
}
