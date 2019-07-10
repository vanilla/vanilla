<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent;

/**
 * Base embed made to be used as a fallback.
 * This matches everything by default so you should not use it with a normal registration.
 * @see EmbedService::setFallbackFactory()
 */
abstract class FallbackEmbedFactory extends AbstractEmbedFactory {
    protected $canHandleEmptyPaths = true;

    /**
     * No supported doamins. This is a fallback.
     * @inheritdoc
     */
    protected function getSupportedDomains(): array {
        return [self::WILDCARD_DOMAIN];
    }

    /**
     * No supported doamins. This is a fallback.
     * @inheritdoc
     */
    protected function getSupportedPathRegex(string $domain): string {
        return '/.+/';
    }
}
