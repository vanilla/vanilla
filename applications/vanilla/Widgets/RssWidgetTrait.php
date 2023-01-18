<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Vanilla\Dashboard\Models\RemoteResourceModel;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Metadata\Parser\RSSFeedParser;
use Vanilla\Logging\ErrorLogger;

/**
 * Trait for fetching items for RSS feeds.
 */
trait RssWidgetTrait
{
    /**
     * @var RSSFeedParser
     */
    private $feedParser;
    /**
     * @var FormatService
     */
    private $formatService;

    /**
     * @var RemoteResourceModel
     */
    private $remoteResourceModel;

    /**
     * @var \Gdn_Cache
     */
    private $cache;

    /**
     * RSSModule constructor.
     *
     * @param RSSFeedParser $feedParser
     * @param FormatService $formatService
     * @param RemoteResourceModel $remoteResourceModel
     * @param \Gdn_Cache $cache
     */
    public function setDependencies(
        RSSFeedParser $feedParser,
        FormatService $formatService,
        RemoteResourceModel $remoteResourceModel,
        \Gdn_Cache $cache
    ) {
        $this->feedParser = $feedParser;
        $this->formatService = $formatService;
        $this->remoteResourceModel = $remoteResourceModel;
        $this->cache = $cache;
    }

    /**
     * Get RSS feed items from a configured url.
     *
     * @param string $feedUrl The feed url.
     * @param string|null $fallbackImageUrl A fallback image to use if the feed doesn't provide one.
     *
     * @return array|null
     */
    protected function getRssFeedItems(string $feedUrl, ?string $fallbackImageUrl): ?array
    {
        $results = $this->loadParsedXMLData($feedUrl);
        if (!$results) {
            return null;
        }
        $resultsItems = $results["item"] ?? [];
        $fallbackImageUrl =
            $fallbackImageUrl && $fallbackImageUrl !== ""
                ? $fallbackImageUrl
                : $results["channel"]["image"]["url"] ?? null;

        $feedItems = [];
        foreach ($resultsItems as $item) {
            $feedItems[] = $this->mapRSSFeedToItem($item, $fallbackImageUrl);
        }
        return $feedItems;
    }

    /**
     * Get the cache key based on the url.
     *
     * @param string $feedUrl
     *
     * @return string
     */
    private function getCacheKey(string $feedUrl): string
    {
        return sprintf("rss.module.%s.parse.content", md5($feedUrl));
    }

    /**
     * Get parsed  xml data from the cache.
     *
     * @param string $feedUrl
     *
     * @return array|null
     */
    private function getFromCache(string $feedUrl): ?array
    {
        $key = $this->getCacheKey($feedUrl);
        $parsedContent = $this->cache->get($key);

        return $parsedContent !== \Gdn_Cache::CACHEOP_FAILURE ? $parsedContent : null;
    }

    /**
     * Load parsed xml data.
     *
     * @param string $feedUrl
     *
     * @return array|null
     */
    private function loadParsedXMLData(string $feedUrl): ?array
    {
        $callback = function ($content) use ($feedUrl) {
            $results = [];
            if (!empty($content)) {
                $rssFeedDOM = new \DOMDocument();
                libxml_use_internal_errors(true);
                $loaded = $rssFeedDOM->loadXML($content);
                $errors = libxml_get_errors();
                libxml_use_internal_errors(false);
                libxml_clear_errors();
                if (!$loaded) {
                    ErrorLogger::warning(
                        "RSS feed couldn't be parsed successfully",
                        ["rss_feed"],
                        [
                            "url" => $feedUrl,
                            "errors" => $errors,
                        ]
                    );
                } else {
                    try {
                        $results = $this->feedParser->parse($rssFeedDOM);
                    } catch (\Exception $exception) {
                        ErrorLogger::warning(
                            "RSS feed couldn't be parsed successfully",
                            ["rss_feed"],
                            [
                                "url" => $feedUrl,
                                "message" => $exception->getMessage(),
                            ]
                        );
                    }
                }
            }
            return !empty($results) ? json_encode($results) : "";
        };
        try {
            $headers = [
                "Accept" => [
                    "application/rss+xml",
                    "application/rdf+xml",
                    "application/atom+xml",
                    "application/xml",
                    "text/xml",
                ],
            ];
            $rssFeedContent = $this->remoteResourceModel->getByUrl($feedUrl, $headers, $callback);
        } catch (\Exception $exception) {
            //if debug is not enabled error out silently
            if (debug()) {
                throw new \Gdn_UserException("The url provided doesnt give back a valid rss feed.");
            }
        }

        if (!$rssFeedContent) {
            return null;
        }

        return json_decode($rssFeedContent, true);
    }

    /**
     * Map RSS feed data into a widget item.
     *
     * @param array $result
     * @param ?string $fallbackUrl
     * @return array
     */
    private function mapRSSFeedToItem(array $result, ?string $fallbackUrl)
    {
        $image = $result["img"] ?? null;
        $imageValue = $image["src"] ?? null;
        $enclosure = $result["enclosure"] ?? null;
        $isEnclosureImage = $enclosure && substr($enclosure["type"], 0, 6) === "image/";
        $enclosureValue = $isEnclosureImage ? $enclosure["url"] : null;
        $imageUrl = $imageValue ?: $enclosureValue;

        return [
            "to" => $result["link"],
            "name" => $result["title"],
            "imageUrl" => $imageUrl ?: $fallbackUrl,
            "description" => $this->formatService->renderPlainText(
                $result["description"] ?? $result["title"],
                HtmlFormat::FORMAT_KEY
            ),
        ];
    }
}
