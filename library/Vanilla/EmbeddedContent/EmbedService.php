<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent;

use Exception;
use Gdn_Cache;
use InvalidArgumentException;
use Vanilla\Attributes;
use Vanilla\Formatting\Embeds;
use Garden\Container;

/**
 * Manage scraping embed data and generating markup.
 */
class EmbedService {

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

    /** @var Embeds\Embed */
    private $fallbackEmbed;

    /** @var array */
    private $registeredEmbeds = [];

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
     * @param Embeds\Embed $embed
     * @param int $priority
     * @return $this
     */
    public function addEmbed(Embeds\Embed $embed, int $priority = null) {
        $priority = $priority ?: self::PRIORITY_NORMAL;
        $type = $embed->getType();
        $this->registeredEmbeds[$type] = [
            'priority' => $priority,
            'embed' => $embed
        ];
        uasort($this->registeredEmbeds, function (array $valA, array $valB) {
            return $valB['priority'] <=> $valA['priority'];
        });
        return $this;
    }

    /**
     * Add all of the built in embeds and defaults. This is primarily used for simpler bootstrapping.
     *
     * @throws Container\ContainerException If there is an issue initializing the container.
     */
    public function addCoreEmbeds() {
        $dic = \Gdn::getContainer();
//        $this->setFallbackEmbed($dic->get(Embeds\LinkEmbed::class))
//            ->addEmbed($dic->get(Embeds\QuoteEmbed::class))
//            ->addEmbed($dic->get(Embeds\TwitterEmbed::class))
//            ->addEmbed($dic->get(Embeds\YouTubeEmbed::class))
//            ->addEmbed($dic->get(Embeds\VimeoEmbed::class))
//            ->addEmbed($dic->get(Embeds\InstagramEmbed::class))
//            ->addEmbed($dic->get(Embeds\SoundCloudEmbed::class))
//            ->addEmbed($dic->get(Embeds\ImgurEmbed::class))
//            ->addEmbed($dic->get(Embeds\TwitchEmbed::class))
//            ->addEmbed($dic->get(Embeds\GettyEmbed::class))
//            ->addEmbed($dic->get(Embeds\GiphyEmbed::class))
//            ->addEmbed($dic->get(Embeds\WistiaEmbed::class))
//            ->addEmbed($dic->get(Embeds\CodePenEmbed::class))
//            ->addEmbed($dic->get(Embeds\FileEmbed::class))
//            ->addEmbed($dic->get(Embeds\ImageEmbed::class), self::PRIORITY_LOW);
    }

    /**
     * Is the provided domain associated with the embed type?
     *
     * @param string $url The target URL.
     * @return Embeds\Embed
     *
     * @throws InvalidArgumentException If the URL is not valid.
     * @throws Exception If a default embed type is needed, but hasn't been configured.
     */
    private function getEmbedByUrl(string $url): Embeds\Embed {
        $domain = parse_url($url, PHP_URL_HOST);

        if (!$domain) {
            throw new InvalidArgumentException('Invalid URL.');
        }

        reset($this->registeredEmbeds);
        foreach ($this->registeredEmbeds as $type => $registeredEmbed) {
            /** @var Embeds\Embed $testEmbed */
            $testEmbed = $registeredEmbed['embed'];
            if ($testEmbed->canHandle($domain, $url)) {
                $embed = $testEmbed;
                break;
            }
        }

        // No specific embed so use the default web scrape embed.
        if (!isset($embed) && ($embed = $this->getFallbackEmbed()) === null) {
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
        $types = array_keys($this->registeredEmbeds);
        if ($default = $this->getFallbackEmbed()) {
            $types[] = $default->getType();
        }
        return $types;
    }

    /**
     * Return structured data from a URL.
     *
     * @param string $url Embed URL.
     * @param bool $forceRefresh Should the cache be disregarded?
     * @return array
     *
     * @throws InvalidArgumentException If the URL is not valid.
     * @throws Exception If a default embed type is needed, but hasn't been configured.
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
     * @throws Exception If a default embed type is needed, but hasn't been configured.
     */
    public function renderData(array $data): string {
        $type = $data['type'] ?? null;

        if (array_key_exists($type, $this->registeredEmbeds)) {
            $embed = $this->registeredEmbeds[$type]['embed'];
        } elseif (($fallbackEmbed = $this->getFallbackEmbed()) && $type === $fallbackEmbed->getType()) {
            $embed = $fallbackEmbed;
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
     * @param Embeds\Embed $fallbackEmbed
     *
     * @return $this
     */
    public function setFallbackEmbed(Embeds\Embed $fallbackEmbed) {
        $this->fallbackEmbed = $fallbackEmbed;
        return $this;
    }

    /**
     * Get the fallback embed type.
     *
     * @return Embeds\Embed|null Returns the fallbackEmbed.
     */
    public function getFallbackEmbed() {
        return $this->fallbackEmbed;
    }
}
