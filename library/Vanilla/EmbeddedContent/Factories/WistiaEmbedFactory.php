<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Garden\Http\HttpClient;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\WistiaEmbed;

/**
 * Factory for WistiaEmbed.
 */
class WistiaEmbedFactory extends AbstractEmbedFactory {

    const DOMAINS = ["wistia.com", "wi.st"];

    const OEMBED_URL_BASE = "https://fast.wistia.com/oembed.json";

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
        return "`^/?(medias|embed)/`";
    }

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        $videoID = $this->videoIDFromUrl($url);

        $response = $this->httpClient->get(
            self::OEMBED_URL_BASE,
            ["url" => $url]
        );

        // Example Response JSON
        // {
        //     "version": "1.0",
        //     "type": "video",
        //     "html": "<iframe src=\"https://fast.wistia.net/embed/iframe/0k5h1g1chs\" title=\"Lenny Delivers a Video - oEmbed glitch\" allowtransparency=\"true\" frameborder=\"0\" scrolling=\"no\" class=\"wistia_embed\" name=\"wistia_embed\" allowfullscreen mozallowfullscreen webkitallowfullscreen oallowfullscreen msallowfullscreen width=\"960\" height=\"540\"></iframe>\n<script src=\"https://fast.wistia.net/assets/external/E-v1.js\" async></script>",
        //     "width": 960,
        //     "height": 540,
        //     "provider_name": "Wistia, Inc.",
        //     "provider_url": "https://wistia.com",
        //     "title": "Lenny Delivers a Video - oEmbed glitch",
        //     "thumbnail_url": "https://embed-ssl.wistia.com/deliveries/99f3aefb8d55eef2d16583886f610ebedd1c6734.jpg?image_crop_resized=960x540",
        //     "thumbnail_width": 960,
        //     "thumbnail_height": 540,
        //     "player_color": "54bbff",
        //     "duration": 40.264
        // }

        $data = [
            "embedType" => WistiaEmbed::TYPE,
            "url" => $url,
            "name" => $response["title"] ?? null,
            "height" => $response["height"] ?? null,
            "width" => $response["width"] ?? null,
            "photoUrl" => $response["thumbnail_url"] ?? null,
            "videoID" => $videoID,
        ];

        return new WistiaEmbed($data);
    }

    /**
     * Given a Wistia video URL, extract its video ID.
     *
     * @param string $url
     * @return string|null
     */
    private function videoIDFromUrl(string $url): ?string {
        return preg_match("`^/?(medias|embed)/(?<videoID>[\w-]+)`", parse_url($url, PHP_URL_PATH) ?? "", $matches) ? $matches["videoID"] : null;
    }
}
