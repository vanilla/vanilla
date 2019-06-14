<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Http\HttpClient;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\FallbackEmbedFactory;
use Vanilla\PageScraper;

/**
 * This factory handles all types of scrape results.
 *
 * It actually matches on any URL so is meant to be used exclusively as a fallback.
 */
class ScrapeEmbedFactory extends FallbackEmbedFactory {

    /** @var PageScraper */
    private $pageScraper;

    /** @var HttpClient */
    private $httpClient;

    /**
     * DI
     *
     * @param PageScraper $pageScraper
     * @param HttpClient $httpClient
     */
    public function __construct(PageScraper $pageScraper, HttpClient $httpClient) {
        $this->pageScraper = $pageScraper;
        $this->httpClient = $httpClient;
    }

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     * @throws \Exception If the scrape fails.
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        $contentType = $this->getContentType($url);
        $isImage = $contentType && substr($contentType, 0, 6) === 'image/';

        if ($isImage) {
            return $this->scrapeImage($url);
        } else {
            return $this->scrapeHtml($url);
        }
    }

    /**
     * Scrape an image URL.
     *
     * @param string $url
     * @return ImageEmbed
     *
     * @throws \Garden\Schema\ValidationException If there's not enough / incorrect data to make an embed.
     */
    private function scrapeImage(string $url): ImageEmbed {
        // Dimensions
        $result = getimagesize($url);
        $height = null;
        $width = null;
        if (is_array($result) && count($result) >= 2) {
            [$width, $height] = $result;
        }
        $data = [
            'url' => $url,
            'embedType' => ImageEmbed::TYPE,
            'name' => t('Untitled Image'),
            'height' => $height,
            'width' => $width,
        ];

        return new ImageEmbed($data);
    }

    /**
     * Scrape an HTML page.
     *
     * @param string $url
     * @return LinkEmbed
     *
     * @throws \Garden\Schema\ValidationException If there's not enough / incorrect data to make an embed.
     * @throws \Exception If the scrape fails.
     */
    private function scrapeHtml(string $url): LinkEmbed {
        $scraped = $this->pageScraper->pageInfo($url);

        $images = $scraped['Images'] ?? [];
        $data = [
            'embedType' => LinkEmbed::TYPE,
            'url' => $url,
            'name' =>  $scraped['Title'] ?? null,
            'body' => $scraped['Description'] ?? null,
            'photoUrl' => !empty($images) ? $images[0] : null,
        ];
        return new LinkEmbed($data);
    }

    /**
     * Get the content type for a given URL.
     *
     * @param string $url
     * @return string|null
     */
    private function getContentType(string $url): ?string {
        // Get information about the request with a HEAD request.
        $response = $this->httpClient->head($url);

        // Let's do some super inconsistent validation of what file types are allowed.
        $contentType = $response->getHeaderLines('content-type');
        // Actually an array, we want the first item.
        $contentType = reset($contentType);
        return $contentType;
    }
}
