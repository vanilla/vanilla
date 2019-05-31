<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent;

use Garden\Container;

/**
 * Manage scraping embed data and generating markup.
 */
class EmbedService implements EmbedCreatorInterface {

    /** @var int High embed prioritization. */
    const PRIORITY_HIGH = 100;

    /** @var int Normal embed prioritization. */
    const PRIORITY_NORMAL = 50;

    /** @var int Low embed prioritization. */
    const PRIORITY_LOW = 25;

    /** @var EmbedCache Caching interface. */
    private $cache;

    /** @var AbstractEmbedFactory */
    private $fallbackFactory;

    /** @var array */
    private $registeredFactories = [];

    /**
     * EmbedManager constructor.
     *
     * @param EmbedCache $cache
     */
    public function __construct(EmbedCache $cache) {
        $this->cache = $cache;
    }

    /**
     * Add a new embed type.
     *
     * @param AbstractEmbedFactory $embedFactory
     * @param int $priority
     * @return $this
     */
    public function registerFactory(AbstractEmbedFactory $embedFactory, int $priority = self::PRIORITY_NORMAL) {
        $this->registeredFactories[] = [
            'priority' => $priority,
            'factory' => $embedFactory
        ];
        uasort($this->registeredFactories, function (array $valA, array $valB) {
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
     * Use the embed factories to create the embed.
     * Implements URL based caching.
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        // Check the cache first.
        $cachedEmbed = $this->cache->getCachedEmbed($url);
        if ($cachedEmbed !== null) {
            return $cachedEmbed;
        }

        $factory = $this->getFactoryForUrl($url);
        $embed = $factory->createEmbedForUrl($url);
        $this->cache->cacheEmbed($embed);
        return $embed;
    }

    /**
     * Use the embed factories to create the embed.
     * @inheritdoc
     */
    public function createEmbedFromData(array $data): AbstractEmbed {
        // Fallback in case we have bad data (will fallback to fallback embed).
        $url = $data['url'] ?? null;
        $factory = $this->getFactoryForUrl($url);
        return $factory->createEmbedFromData($url);
    }

    /**
     * Iterate through all registered factories to find the one that can handle the given URL.
     *
     * @param string $url
     * @return AbstractEmbedFactory
     */
    private function getFactoryForUrl(string $url): AbstractEmbedFactory {
        foreach ($this->registeredFactories as $registered) {
            /** @var AbstractEmbedFactory $factory */
            $factory = $registered['factory'];
            if ($factory->canHandleUrl($url)) {
                return $factory;
            }
        }

        return $this->fallbackFactory;
    }

    /**
     * Set the defaultEmbed.
     *
     * @param AbstractEmbedFactory $fallbackFactory
     *
     * @return $this
     */
    public function setFallbackEmbed(AbstractEmbedFactory $fallbackFactory) {
        $this->fallbackFactory = $fallbackFactory;
        return $this;
    }

    /**
     * Get the fallback embed type.
     *
     * @return AbstractEmbedFactory Returns the fallbackFactory.
     */
    public function getFallbackFactory() {
        return $this->fallbackFactory;
    }
}
