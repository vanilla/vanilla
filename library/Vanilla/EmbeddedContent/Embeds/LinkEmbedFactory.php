<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Http\HttpClient;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\PageScraper;

/**
 * Factory for LinkEmbed
 */
class LinkEmbedFactory extends AbstractEmbedFactory {

    /** @var PageScraper */
    private $pageScraper;

    /**
     * DI
     *
     * @param PageScraper $pageScraper
     */
    public function __construct(PageScraper $pageScraper) {
        $this->pageScraper = $pageScraper;
    }


    /**
     * No supported doamins. This is a fallback.
     * @inheritdoc
     */
    protected function getSupportedDomains(): array {
        return [];
    }

    /**
     * No supported doamins. This is a fallback.
     * @inheritdoc
     */
    protected function getSupportedPathRegex(): string {
        return "/$^/";
    }

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     * @throws \Exception If the scrape fails.
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        $scraped = $this->pageScraper->pageInfo($url);

        $result = [];
        $images = $scraped['Images'] ?? [];
        $result['name'] = $scraped['Title'] ?? null;
        $result['body'] = $scraped['Description'] ?? null;
        $result['photoUrl'] = !empty($images) ? $images[0] : null;
        return new LinkEmbed($result);
    }

    /**
     * @inheritdoc
     */
    public function createEmbedFromData(array $data): AbstractEmbed {
        return new LinkEmbed($data);
    }
}
