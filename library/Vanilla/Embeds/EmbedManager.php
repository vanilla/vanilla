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

    /** Valid image extensions. */
    const IMAGE_EXTENSIONS = ['bmp', 'gif', 'jpeg', 'jpg', 'png', 'svg', 'tif', 'tiff'];

    /** @var Gdn_Cache Caching interface. */
    private $cache;

    /** @var AbstractEmbed The default embed type. */
    private $defaultEmbed;

    /** @var bool Allow network requests (e.g. HTTP)? */
    private $networkEnabled = true;

    /** @var AbstractEmbed[] Available embed types. */
    private $embeds = [];

    /** @var ImageEmbed Generic image embed. */
    private $imageEmbed;

    /**
     * EmbedManager constructor.
     *
     * @param Gdn_Cache $cache
     * @param ImageEmbed $imageEmbed
     */
    public function __construct(Gdn_Cache $cache, ImageEmbed $imageEmbed) {
        $this->cache = $cache;
        $this->imageEmbed = $imageEmbed;
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
                if ($domain === $testDomain || stringEndsWith($domain, ".{$testDomain}")) {
                    $embed = $testEmbed;
                    break 2;
                }
            }
        }

        if (!isset($embed)) {
            if ($this->isImageUrl($url)) {
                $embed = $this->imageEmbed;
            } else {
                $embed = $this->getDefaultEmbed();
            }
        }

        return $embed;
    }

    /**
     * Is this an image URL?
     *
     * @param string $url Target URL.
     * @return bool
     */
    private function isImageUrl(string $url): bool {
        $result = false;

        $path = parse_url($url, PHP_URL_PATH);
        if ($path !== false) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $result = $extension && in_array(strtolower($extension), self::IMAGE_EXTENSIONS);
        }

        return $result;
    }

    /**
     * Should network requests be available to embed objects?
     *
     * @return bool
     */
    public function isNetworkEnabled(): bool {
        return $this->networkEnabled;
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
            $cachedData = $this->cache->get($cacheKey);
            if ($cachedData !== Gdn_Cache::CACHEOP_FAILURE) {
                $data = $cachedData;
            }
        }

        if ($data === null) {
            $embed = $this->getEmbedByUrl($url);
            $type = $embed->getType();
            $embedNetworkEnabled = $embed->isNetworkEnabled();
            $embed->setNetworkEnabled($this->networkEnabled);
            $data = $embed->matchUrl($url);
            $embed->setNetworkEnabled($embedNetworkEnabled);

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

        if (array_key_exists($type, $this->embeds)) {
            $embed = $this->embeds[$type];
        } elseif ($type === $this->imageEmbed->getType()) {
            $embed = $this->imageEmbed;
        } elseif ($type === $this->getDefaultEmbed()->getType()) {
            $embed = $this->getDefaultEmbed();
        }

        if (empty($embed)) {
            throw new InvalidArgumentException('Invalid embed type.');
        }

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

    /**
     * Set whether or not embed objects should be able to use the network to gather additional data.
     *
     * @param bool $networkEnabled Should network requests be available to embed objects?
     * @return $this
     */
    public function setNetworkEnabled(bool $networkEnabled) {
        $this->networkEnabled = $networkEnabled;
        return $this;
    }
}
