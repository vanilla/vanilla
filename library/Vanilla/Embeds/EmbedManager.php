<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Embeds;

use Exception;
use Gdn_Cache;
use InvalidArgumentException;

/**
 * Manage scraping embed data and generating markup.
 */
class EmbedManager {

    /** Page info caching expiry (24 hours). */
    const CACHE_EXPIRY = 24 * 60 * 60;

    /** Default scrape type. */
    const TYPE_DEFAULT = 'site';

    /** @var Gdn_Cache Caching interface. */
    private $cache;

    /** @var AbstractEmbed The default embed type. */
    private $defaultEmbed;

    /** @var AbstractEmbed[] Available embed types. */
    private $embeds = [];

    /**
     * EmbedManager constructor.
     *
     * @param Gdn_Cache $cache
     */
    public function __construct(Gdn_Cache $cache) {
        $this->cache = $cache;
    }

    /**
     * Add a new embed type.
     *
     * @param AbstractEmbed $embed
     * @return $this
     */
    public function addEmbed(AbstractEmbed $embed) {
        $type = $embed->getType();
        $this->embeds[$type] = $embed;
        return $this;
    }

    /**
     * Get the default embed type.
     *
     * @return AbstractEmbed Returns the defaultEmbed.
     * @throws Exception if no default embed type has been configured.
     */
    public function getDefaultEmbed(): AbstractEmbed {
        if ($this->defaultEmbed === null) {
            throw new Exception('Default embed type not configured.');
        }
        return $this->defaultEmbed;
    }

    /**
     * Is the provided domain associated with the embed type?
     *
     * @param string $url The target URL.
     * @return AbstractEmbed
     * @throws InvalidArgumentException if the URL is not valid.
     * @throws Exception if a default embed type is needed, but hasn't been configured.
     */
    private function getEmbedByUrl(string $url): AbstractEmbed {
        $domain = parse_url($url, PHP_URL_HOST);

        if (!$domain) {
            throw new InvalidArgumentException('Invalid URL.');
        }

        // No specific embed so use the default web scrape embed.
        foreach ($this->embeds as $testEmbed) {
            foreach ($testEmbed->getDomains() as $testDomain) {
                if ($domain === $testDomain || stringEndsWith($testDomain, ".{$testDomain}")) {
                    $embed = $testEmbed;
                    break 2;
                }
            }
        }

        if (!isset($embed)) {
            $embed = $this->getDefaultEmbed();
        }

        return $embed;
    }

    /**
     * Return structured data from a URL.
     *
     * @param string $url Embed URL.
     * @param bool $forceRefresh Should the cache be disregarded?
     * @return array
     * @throws InvalidArgumentException if the URL is not valid.
     * @throws Exception if a default embed type is needed, but hasn't been configured.
     */
    public function matchUrl(string $url, bool $forceRefresh = false): array {
        $cacheKey = 'EmbedManager.'.md5($url);
        $data = null;

        // If not forcing a refresh, attempt to load page data from the cache.
        if (!$forceRefresh) {
            $data = $this->cache->get($cacheKey);
            if ($data === Gdn_Cache::CACHEOP_FAILURE) {
                unset($data);
            }
        }

        if (!isset($data)) {
            $embed = $this->getEmbedByUrl($url);
            $type = $embed->getType();
            $data = $embed->matchUrl($url);

            $defaults = [
                'name' => null,
                'body' => null,
                'photoUrl' => null,
                'height' => null,
                'width' => null,
                'attributes' => []
            ];
            $data = is_array($data) ? array_merge($defaults, $data) : $defaults;
            $data['url'] = $url;
            $data['type'] = $type;
        }

        if ($data) {
            $this->cache->store($cacheKey, $data, [Gdn_Cache::FEATURE_EXPIRY => self::CACHE_EXPIRY]);
        }

        return $data;
    }

    /**
     * Given structured data, generate markup for an embed.
     *
     * @param array $data
     * @return string
     * @throws Exception if a default embed type is needed, but hasn't been configured.
     */
    public function renderData(array $data): string {
        $type = $data['type'] ?? null;
        if (!array_key_exists($type, $this->embeds) && $type !== $this->getDefaultEmbed()->getType()) {
            throw new InvalidArgumentException('Invalid embed type.');
        }
        $embed = $this->embeds[$type] ?? $this->getDefaultEmbed();

        $result = $embed->renderData($data);
        return $result;
    }

    /**
     * Set the defaultEmbed.
     *
     * @param AbstractEmbed $defaultEmbed
     * @return $this
     */
    public function setDefaultEmbed(AbstractEmbed $defaultEmbed) {
        $this->defaultEmbed = $defaultEmbed;
        return $this;
    }
}
