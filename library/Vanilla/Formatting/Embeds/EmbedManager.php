<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Embeds;

use Exception;
use Gdn_Cache;
use InvalidArgumentException;
use Vanilla\Attributes;
use Vanilla\PageScraper;

/**
 * Manage scraping embed data and generating markup.
 */
class EmbedManager {

    /** Page info caching expiry (24 hours). */
    const CACHE_EXPIRY = 24 * 60 * 60;

    /** @var int High embed prioritization. */
    const PRIORITY_HIGH = 100;

    /** @var int Normal embed prioritization. */
    const PRIORITY_NORMAL = 50;

    /** @var int Low embed prioritization. */
    const PRIORITY_LOW = 25;

    /** @var Gdn_Cache Caching interface. */
    private $cache;

    /** @var Embed The default embed type. */
    private $defaultEmbed;

    /** @var bool Allow network requests (e.g. HTTP)? */
    private $networkEnabled = true;

    /** @var array Available embed types. */
    private $embeds = [];

    /**
     * EmbedManager constructor.
     *
     * @param Gdn_Cache $cache
     * @param ImageEmbed $imageEmbed
     */
    public function __construct(Gdn_Cache $cache) {
        $this->cache = $cache;
    }

    /**
     * Add a new embed type.
     *
     * @param Embed $embed
     * @param int $priority
     * @return $this
     */
    public function addEmbed(Embed $embed, int $priority = null) {
        $priority = $priority ?: self::PRIORITY_NORMAL;
        $type = $embed->getType();
        $this->embeds[$type] = [
            'priority' => $priority,
            'embed' => $embed
        ];
        uasort($this->embeds, function(array $valA, array $valB) {
            return $valB['priority'] <=> $valA['priority'];
        });
        return $this;
    }

    /**
     * Add all of the built in embeds and defaults. This is primarily used for simpler bootstrapping.
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function addCoreEmbeds() {
        $dic = \Gdn::getContainer();
        $this->setDefaultEmbed($dic->get(LinkEmbed::class))
            ->addEmbed($dic->get(QuoteEmbed::class))
            ->addEmbed($dic->get(TwitterEmbed::class))
            ->addEmbed($dic->get(YouTubeEmbed::class))
            ->addEmbed($dic->get(VimeoEmbed::class))
            ->addEmbed($dic->get(InstagramEmbed::class))
            ->addEmbed($dic->get(SoundCloudEmbed::class))
            ->addEmbed($dic->get(ImgurEmbed::class))
            ->addEmbed($dic->get(TwitchEmbed::class))
            ->addEmbed($dic->get(GettyEmbed::class))
            ->addEmbed($dic->get(GiphyEmbed::class))
            ->addEmbed($dic->get(WistiaEmbed::class))
            ->addEmbed($dic->get(CodePenEmbed::class))
            ->addEmbed($dic->get(FileEmbed::class))
            ->addEmbed($dic->get(ImageEmbed::class), self::PRIORITY_LOW);
    }

    /**
     * Get the default embed type.
     *
     * @return Embed|null Returns the defaultEmbed.
     */
    public function getDefaultEmbed() {
        return $this->defaultEmbed;
    }

    /**
     * Is the provided domain associated with the embed type?
     *
     * @param string $url The target URL.
     * @return Embed
     * @throws InvalidArgumentException if the URL is not valid.
     * @throws Exception if a default embed type is needed, but hasn't been configured.
     */
    private function getEmbedByUrl(string $url): Embed {
        $domain = parse_url($url, PHP_URL_HOST);

        if (!$domain) {
            throw new InvalidArgumentException('Invalid URL.');
        }

        reset($this->embeds);
        foreach ($this->embeds as $type => $registeredEmbed) {
            /** @var Embed $testEmbed */
            $testEmbed = $registeredEmbed['embed'];
            if ($testEmbed->canHandle($domain, $url)) {
                $embed = $testEmbed;
                break;
            }
        }

        // No specific embed so use the default web scrape embed.
        if (!isset($embed) && ($embed = $this->getDefaultEmbed()) === null) {
            throw new Exception('Unable to locate an handler for the URL.');
        }

        return $embed;
    }

    /**
     * Get all valid embed types.
     *
     * @return array
     */
    public function getTypes() {
        $types = array_keys($this->embeds);
        if ($default = $this->getDefaultEmbed()) {
            $types[] = $default->getType();
        }
        return $types;
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

        if (empty($data['attributes'])) {
            $data['attributes'] = new Attributes();
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
            $embed = $this->embeds[$type]['embed'];
        } elseif (($defaultEmbed = $this->getDefaultEmbed()) && $type === $defaultEmbed->getType()) {
            $embed = $defaultEmbed;
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
     * @param Embed $defaultEmbed
     * @return $this
     */
    public function setDefaultEmbed(Embed $defaultEmbed) {
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
