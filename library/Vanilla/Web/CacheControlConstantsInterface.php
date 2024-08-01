<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

/**
 * Defines constant values related to HTTP Cache Control
 */
interface CacheControlConstantsInterface
{
    /** @var string The name of the cache control header. */
    public const HEADER_CACHE_CONTROL = "Cache-Control";
    /** @var string Maximum cache age. */
    public const MAX_CACHE = "public, max-age=31536000";
    /** @var string Standard Cache-Control header for content that should not be cached. */
    public const NO_CACHE = "private, no-cache, max-age=0, must-revalidate";
    /** @var string Standard Cache-Control header string for public, cacheable content. */
    public const PUBLIC_CACHE = "public, max-age=120";
    /** @var string Standard vary header when using public cache control based on session. */
    public const VARY_COOKIE = "Accept-Encoding, Cookie";
    /** @var string Disable auto-vary for sessioned users. */
    public const META_NO_VARY = "noVary";
}
