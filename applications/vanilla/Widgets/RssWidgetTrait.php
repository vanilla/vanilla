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
        $fallbackImageUrl = $fallbackImageUrl ?? ($results["channel"]["image"]["url"] ?? null);

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
        $results = $this->getFromCache($feedUrl);
        $rssFeedContent = !$results ? $this->remoteResourceModel->getByUrl($feedUrl) : null;
        if (!$results && !$rssFeedContent) {
            return null;
        }
        if (!$results && $rssFeedContent) {
            $rssFeedDOM = new \DOMDocument();
            $loaded = $rssFeedDOM->loadXML($rssFeedContent);
            if ($loaded) {
                $results = $this->feedParser->parse($rssFeedDOM);
                $key = $this->getCacheKey($feedUrl);
                $this->cache->store($key, $results, [\Gdn_Cache::FEATURE_EXPIRY => \Gdn_Cache::APC_CACHE_DURATION]);
            }
        }

        return $results;
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
            "description" => $this->formatService->renderPlainText($result["description"], HtmlFormat::FORMAT_KEY),
        ];
    }
}
