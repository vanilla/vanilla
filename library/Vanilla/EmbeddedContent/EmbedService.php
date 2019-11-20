<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent;

use Garden\Container;
use Garden\Schema\ValidationException;
use Vanilla\EmbeddedContent\Embeds\CodePenEmbed;
use Vanilla\EmbeddedContent\Embeds\ErrorEmbed;
use Vanilla\EmbeddedContent\Embeds\FileEmbed;
use Vanilla\EmbeddedContent\Embeds\GiphyEmbed;
use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use Vanilla\EmbeddedContent\Embeds\ImgurEmbed;
use Vanilla\EmbeddedContent\Embeds\LinkEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbedFilter;
use Vanilla\EmbeddedContent\Factories\CodePenEmbedFactory;
use Vanilla\EmbeddedContent\Factories\GiphyEmbedFactory;
use Vanilla\EmbeddedContent\Factories\ImgurEmbedFactory;
use Vanilla\EmbeddedContent\Factories\ScrapeEmbedFactory;
use Vanilla\EmbeddedContent\Factories\TwitchEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\TwitchEmbed;
use Vanilla\EmbeddedContent\Factories\YouTubeEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\YouTubeEmbed;
use Vanilla\EmbeddedContent\Factories\WistiaEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\WistiaEmbed;
use Vanilla\EmbeddedContent\Factories\VimeoEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\VimeoEmbed;
use Vanilla\EmbeddedContent\Factories\TwitterEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\TwitterEmbed;
use Vanilla\EmbeddedContent\Factories\SoundCloudEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\SoundCloudEmbed;
use Vanilla\EmbeddedContent\Factories\InstagramEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\InstagramEmbed;
use Vanilla\EmbeddedContent\Factories\GettyImagesEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\GettyImagesEmbed;
use Vanilla\Web\RequestValidator;

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

    /** @var RequestValidator */
    private $requestValidator;

    /** @var AbstractEmbedFactory */
    private $fallbackFactory;

    /** @var array */
    private $registeredFactories = [];

    /** @var array Mapping of 'embedType' => EmbedClass::class */
    private $registeredEmbeds = [];

    /** @var EmbedFilterInterface[] */
    private $registeredFilters = [];

    /**
     * EmbedManager constructor.
     *
     * @param EmbedCache $cache
     * @param RequestValidator $requestValidator
     */
    public function __construct(EmbedCache $cache, RequestValidator $requestValidator) {
        $this->cache = $cache;
        $this->requestValidator = $requestValidator;
    }

    /**
     * Register an embed data class to map to a particular string type.
     * This class will be instantiated through createEmbedFromData().
     *
     * @param EmbedFilterInterface $embedFilter An embed filter instance.
     *
     * @return $this
     */
    public function registerFilter(EmbedFilterInterface $embedFilter): EmbedService {
        $this->registeredFilters[] = $embedFilter;
        return $this;
    }

    /**
     * Register an embed data class to map to a particular string type.
     * This class will be instantiated through createEmbedFromData().
     *
     * @param string $embedClass A class constant that extends AbstractEmbed.
     * @param string $embedType The string type that matches to the class.
     *
     * @return $this
     * @throws \Exception If the class being extended isn't a correct a subclass of AbstractEmbed.
     */
    public function registerEmbed(string $embedClass, string $embedType): EmbedService {
        if (!is_subclass_of($embedClass, AbstractEmbed::class)) {
            throw new \Exception("Only classes extending " . AbstractEmbed::class . " may be registered.");
        }
        $this->registeredEmbeds[$embedType] = $embedClass;
        return $this;
    }

    /**
     * Add a new embed type.
     *
     * @param AbstractEmbedFactory $embedFactory
     * @param int $priority
     * @return $this
     */
    public function registerFactory(AbstractEmbedFactory $embedFactory, int $priority = self::PRIORITY_NORMAL) {
        if ($embedFactory instanceof FallbackEmbedFactory) {
            trigger_error("A fallback embed was registerred as a normal embed. See EmbedService::setFallbackFactory", E_USER_WARNING);
        }
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
     * @throws \Exception If there is some incorrect class registration.
     */
    public function addCoreEmbeds() {
        $dic = \Gdn::getContainer();
        $this
            // Getty Images
            ->registerFactory($dic->get(GettyImagesEmbedFactory::class))
            ->registerEmbed(GettyImagesEmbed::class, GettyImagesEmbed::TYPE)
            ->registerEmbed(GettyImagesEmbed::class, GettyImagesEmbed::LEGACY_TYPE)
            // Giphy
            ->registerFactory($dic->get(GiphyEmbedFactory::class))
            ->registerEmbed(GiphyEmbed::class, GiphyEmbed::TYPE)
            // Imgur
            ->registerFactory($dic->get(ImgurEmbedFactory::class))
            ->registerEmbed(ImgurEmbed::class, ImgurEmbed::TYPE)
            // Instagram
            ->registerFactory($dic->get(InstagramEmbedFactory::class))
            ->registerEmbed(InstagramEmbed::class, InstagramEmbed::TYPE)
            // CodePen
            ->registerFactory($dic->get(CodePenEmbedFactory::class))
            ->registerEmbed(CodePenEmbed::class, CodePenEmbed::TYPE)
            // SoundCloud
            ->registerFactory($dic->get(SoundCloudEmbedFactory::class))
            ->registerEmbed(SoundCloudEmbed::class, SoundCloudEmbed::TYPE)
            // Twitch
            ->registerFactory($dic->get(TwitchEmbedFactory::class))
            ->registerEmbed(TwitchEmbed::class, TwitchEmbed::TYPE)
            // Twitter
            ->registerFactory($dic->get(TwitterEmbedFactory::class))
            ->registerEmbed(TwitterEmbed::class, TwitterEmbed::TYPE)
            // Vimeo
            ->registerFactory($dic->get(VimeoEmbedFactory::class))
            ->registerEmbed(VimeoEmbed::class, VimeoEmbed::TYPE)
            // Wistia
            ->registerFactory($dic->get(WistiaEmbedFactory::class))
            ->registerEmbed(WistiaEmbed::class, WistiaEmbed::TYPE)
            // YouTube
            ->registerFactory($dic->get(YouTubeEmbedFactory::class))
            ->registerEmbed(YouTubeEmbed::class, YouTubeEmbed::TYPE)
            // Scrape-able Embeds
            ->setFallbackFactory($dic->get(ScrapeEmbedFactory::class))
            ->registerEmbed(ImageEmbed::class, ImageEmbed::TYPE)
            ->registerEmbed(LinkEmbed::class, LinkEmbed::TYPE)
            // Files - No factory for the file embed. Only comes from media endpoint.
            ->registerEmbed(FileEmbed::class, FileEmbed::TYPE)
            // Internal Vanilla quote embed.
            ->registerEmbed(QuoteEmbed::class, QuoteEmbed::TYPE)
            ->registerFilter($dic->get(QuoteEmbedFilter::class))
        ;
    }

    /**
     * Filter some embed data with on of the registered filterers.
     *
     * @param array $data The data to filter.
     *
     * @return array The filtered data.
     */
    public function filterEmbedData(array $data): array {
        $type = $data['embedType'] ?? $data['type'] ?? null;

        if (!$type) {
            trigger_error(
                "Attempted to filter embed data, but a type could not be found\n" . json_encode($data, JSON_PRETTY_PRINT),
                E_USER_NOTICE
            );
        }

        // Construct the embed.
        $embed = $this->createEmbedFromData($data);
        $embed = $this->filterEmbed($embed);
        return $embed->jsonSerialize();
    }

    /**
     * Filter an embed. This should always happen after creation.
     *
     * @param AbstractEmbed $embed
     * @return AbstractEmbed
     */
    private function filterEmbed(AbstractEmbed $embed): AbstractEmbed {
        $type = $embed->getData()['embedType'];
        foreach ($this->registeredFilters as $filter) {
            if ($filter->canHandleEmbedType($type)) {
                $embed = $filter->filterEmbed($embed);
            }
        }
        return $embed;
    }

    /**
     * Use the embed factories to create the embed.
     * Implements URL based caching.
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url, bool $force = false): AbstractEmbed {
        // Ensure that this function is never called during a GET request.
        // This function makes some potentially very expensive calls
        // It can also be used to force the site into an infinite loop (eg. GET page hits the scraper which hits the same page again).
        // @see https://github.com/vanilla/dev-inter-ops/issues/23
        // We've had some situations where the site gets in an infinite loop requesting itself.
        $this->requestValidator->blockRequestType('GET', __METHOD__ . ' may not be called during a GET request.');

        // Check the cache first.
        if (!$force) {
            $cachedEmbed = $this->cache->getCachedEmbed($url);
            if ($cachedEmbed !== null) {
                return $cachedEmbed;
            }
        }

        $factory = $this->getFactoryForUrl($url);
        $embed = $factory->createEmbedForUrl($url);
        $embed = $this->filterEmbed($embed);
        $this->cache->cacheEmbed($embed);
        return $embed;
    }


    /**
     * Create an embed class from already fetched data.
     * Implementations should be fast and capable of running in loop on every page load.
     *
     * @param array $data
     * @return AbstractEmbed
     */
    public function createEmbedFromData(array $data): AbstractEmbed {
        // Fallback in case we have bad data (will fallback to fallback embed).
        $type = $data['embedType'] ?? $data['type'] ?? null;
        try {
            $embedClass = $this->registeredEmbeds[$type] ?? null;
            if ($embedClass === null) {
                return new ErrorEmbed(new \Exception("Embed class for type $type not found."), $data);
            }
            $embed = new $embedClass($data);
            $embed = $this->filterEmbed($embed);
            return $embed;
        } catch (ValidationException $e) {
            trigger_error(
                "Validation error while instantiating embed type $type with class $embedClass and data \n"
                . json_encode($data, JSON_PRETTY_PRINT) . "\n"
                . json_encode($e->jsonSerialize(), JSON_PRETTY_PRINT),
                E_USER_WARNING
            );
            return new ErrorEmbed($e, $data);
        }
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
     * @return $this
     */
    public function setFallbackFactory(AbstractEmbedFactory $fallbackFactory) {
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
