<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Garden\Http\HttpClient;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\TwitterEmbed;

/**
 * Factory for TwitterEmbed.
 */
class TwitterEmbedFactory extends AbstractEmbedFactory {

    const DOMAINS = ["twitter.com"];

    /** @var HttpClient */
    private $httpClient;

    /**
     * DI.
     *
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient) {
        $this->httpClient = $httpClient;
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedDomains(): array {
        return self::DOMAINS;
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedPathRegex(string $domain): string {
        return "`^/?(?:[^\/]+)/status(es)?/[\d]+`";
    }

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        $data = [
            "embedType" => TwitterEmbed::TYPE,
            "url" => $url,
            "statusID" => $this->statusIDFromUrl($url),
        ];

        return new TwitterEmbed($data);
    }

    /**
     * Given a Twitter status URL, return the status ID.
     *
     * @param string $url
     * @return int|null
     */
    private function statusIDFromUrl(string $url): ?int {
        if (!preg_match("`^/?(?:[^\/]+)/status(es)?/(?<statusID>[\d]+)`", parse_url($url, PHP_URL_PATH) ?? "", $matches)) {
            return null;
        }

        return (int)$matches["statusID"];
    }
}
