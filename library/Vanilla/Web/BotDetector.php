<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\RequestInterface;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Vanilla\Cache\CacheCacheAdapter;
use Vanilla\Utility\DebugUtils;

/**
 * Class for detection of bots.
 */
class BotDetector
{
    private \Psr\SimpleCache\CacheInterface $cache;
    private CrawlerDetect $crawlerDetector;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->crawlerDetector = new CrawlerDetect();
        $this->cache = new CacheCacheAdapter(new \Gdn_Dirtycache());
    }

    /**
     * Check if the current request is a from a bot or not.
     *
     * Notably because of caching this is really only consistent from authenticated users or on POST/PATCH/PUT/DELETE requests.
     *
     * @param RequestInterface $request The request to check.
     *
     * @return bool
     */
    public function isBot(RequestInterface $request): bool
    {
        $knownBotHeader = $request->getHeader("X-Known-Bot");
        if ($knownBotHeader === "1" || $knownBotHeader === "True") {
            return true;
        }

        $userAgent = $request->getHeader("User-Agent");
        if (str_contains($userAgent, "garden-http") && DebugUtils::isTestMode()) {
            // Bail out internal-client in tests unless a user agent is explicitly set.
            return false;
        }

        $cached = $this->cache->get($userAgent, null);

        if ($cached !== null) {
            return $cached;
        }

        $isBot = $this->crawlerDetector->isCrawler($userAgent);
        $this->cache->set($userAgent, $isBot);
        return $isBot;
    }
}
