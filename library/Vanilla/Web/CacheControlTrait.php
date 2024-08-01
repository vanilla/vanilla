<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

/**
 * Mixin methods related to HTTP cache control
 */
trait CacheControlTrait
{
    //region Public Static Methods
    /**
     * A convenience method for sending cache control headers directly to the response with the `header()` function.
     *
     * @param string $cacheControl The value of the cache control header.
     */
    public static function sendCacheControlHeaders(string $cacheControl)
    {
        safeHeader("Cache-Control: $cacheControl");
        foreach (static::getHttp10Headers($cacheControl) as $key => $value) {
            safeHeader("$key: $value");
        }
        if ($cacheControl === CacheControlConstantsInterface::NO_CACHE) {
            safeHeader("Vary: " . CacheControlConstantsInterface::VARY_COOKIE);
        }
    }

    /**
     * Translate a Cache-Control header into HTTP/1.0 Expires and Pragma headers.
     *
     * @param string $cacheControl A valid Cache-Control header value.
     * @return array
     */
    public static function getHttp10Headers(string $cacheControl): array
    {
        $result = [];

        if (preg_match("`max-age=(\d+)`", $cacheControl, $m)) {
            if ($m[1] === "0") {
                $result["Expires"] = "Sat, 01 Jan 2000 00:00:00 GMT";
                $result["Pragma"] = "no-cache";
            } else {
                $result["Expires"] = gmdate("D, d M Y H:i:s T", time() + $m[1]);
            }
        }

        return $result;
    }
    //endregion
}
