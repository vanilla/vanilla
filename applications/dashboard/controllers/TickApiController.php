<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Gdn_Statistics as Statistics;
use Vanilla\Analytics\EventProviderService;
use Vanilla\Utility\UrlUtils;
use Vanilla\Web\BotDetector;

/**
 * API Controller for site analytics.
 */
class TickApiController extends AbstractApiController
{
    private Statistics $statistics;
    private EventProviderService $eventProviderService;
    private BotDetector $botDetector;

    /**
     * DI.
     */
    public function __construct(
        Gdn_Statistics $statistics,
        EventProviderService $eventProviderService,
        BotDetector $botDetector
    ) {
        $this->statistics = $statistics;
        $this->eventProviderService = $eventProviderService;
        $this->botDetector = $botDetector;
    }

    /**
     * Ensure params are always lowercase and not duplicated they
     * are tracked and can be queried consistently by analytics
     *
     * @param string $url
     * @return string
     */
    public function sanitizeParams(string $url): string
    {
        // Parse the referrer to grab the params
        $parsedUrl = parse_url($url);

        // If there are any params
        if ($parsedUrl && is_array($parsedUrl) && array_key_exists("query", $parsedUrl)) {
            parse_str($parsedUrl["query"], $params);
            $fixedParams = [];
            // Iterate through the params and change the key case
            foreach ($params as $key => $value) {
                $fixedParams[strtolower($key)] = $value;
            }
            // Rebuild the url
            $parsedUrl["query"] = http_build_query($fixedParams);

            return http_build_url($parsedUrl);
        }
        // If there are no params, we can just pass the original string back
        return $url;
    }

    /**
     * Collect an analytics tick.
     *
     * @param array $body
     * @param RequestInterface $request
     *
     * @return Data
     * @throws Exception
     */
    public function post(array $body, RequestInterface $request): Data
    {
        if ($this->botDetector->isBot($request)) {
            // Don't do anything if the request is from a bot.
            // We don't want to track bots.
            return new Data(["didTrack" => false, "message" => "Bot request"]);
        }

        $this->statistics->tick();
        $this->statistics->fireEvent("AnalyticsTick");

        if (key_exists("url", $body)) {
            $body["url"] = $this->sanitizeParams($body["url"]);
        }

        // Convert the `referrer` url to punycode.
        if (key_exists("referrer", $body)) {
            // Transform Unicode characters
            $body["referrer"] = UrlUtils::domainAsAscii($body["referrer"]);
            $body["referrer"] = $this->sanitizeParams($body["referrer"]);
        }

        $this->eventProviderService->handleRequest($body);
        return new Data(["didTrack" => true]);
    }
}
