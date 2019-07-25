<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\ContentSecurityPolicy;

/**
 * Embeds domains whitelist content security policy provider
 */
class EmbedWhitelistContentSecurityPolicyProvider implements ContentSecurityPolicyProviderInterface {
    const EMBED_WHITELIST = [
        'https://embed-cdn.gettyimages.com',
        'https://s.imgur.com',
        'https://platform.instagram.com',
        'https://platform.twitter.com',
        'https://cdn.syndication.twimg.com',
        'https://www.instagram.com/embed.js',
    ];

    /**
     * @inheritdoc
     */
    public function getPolicies(): array {
        $policies[] = new Policy(Policy::SCRIPT_SRC, implode(' ', self::EMBED_WHITELIST));

        return $policies;
    }
}
