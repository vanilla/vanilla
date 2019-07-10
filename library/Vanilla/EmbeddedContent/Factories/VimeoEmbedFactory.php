<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Garden\Http\HttpClient;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\VimeoEmbed;

/**
 * Factory for VimeoEmbed.
 */
class VimeoEmbedFactory extends AbstractEmbedFactory {

    const DOMAINS = ["vimeo.com"];

    const OEMBED_URL_BASE = "https://vimeo.com/api/oembed.json";

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
        return "`^/?\d+(\?|$)`";
    }

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        $response = $this->httpClient->get(
            self::OEMBED_URL_BASE,
            ["url" => $url]
        );

        // Example Response JSON
        // phpcs:disable Generic.Files.LineLength
        // {
        //     "type": "video",
        //     "version": "1.0",
        //     "provider_name": "Vimeo",
        //     "provider_url": "https://vimeo.com/",
        //     "title": "Glycol",
        //     "author_name": "Diego Diapolo",
        //     "author_url": "https://vimeo.com/diegodiapolo",
        //     "is_plus": "0",
        //     "account_type": "basic",
        //     "html": "<iframe src=\"https://player.vimeo.com/video/207028770?app_id=122963\" width=\"640\" height=\"300\" frameborder=\"0\" title=\"Glycol\" allow=\"autoplay; fullscreen\" allowfullscreen></iframe>",
        //     "width": 640,
        //     "height": 300,
        //     "duration": 70,
        //     "description": "Glycol decomposes in contact with the air in about ten days, in water or soil in just a couple of weeks. Plastic spaces and living things are made of it, habitating in an artificial world created by humans, a world of perpetual motion and random algorithms. It is aesthetic, mathematical and physical. Glycol is an experiment I did to communicate with you.\n\nAll by Diego Diapolo\n\nmore at \nwww.diegodiapolo.com\nwww.instagram.com/diegodiapolo",
        //     "thumbnail_url": "https://i.vimeocdn.com/video/740788474_640.jpg",
        //     "thumbnail_width": 640,
        //     "thumbnail_height": 300,
        //     "thumbnail_url_with_play_button": "https://i.vimeocdn.com/filter/overlay?src0=https%3A%2F%2Fi.vimeocdn.com%2Fvideo%2F740788474_640.jpg&src1=http%3A%2F%2Ff.vimeocdn.com%2Fp%2Fimages%2Fcrawler_play.png",
        //     "upload_date": "2017-03-05 17:33:52",
        //     "video_id": 207028770,
        //     "uri": "/videos/207028770"
        // }
        // phpcs:enable Generic.Files.LineLength

        $data = [
            "embedType" => VimeoEmbed::TYPE,
            "url" => $url,
            "name" => $response["title"] ?? null,
            "height" => $response["height"] ?? null,
            "width" => $response["width"] ?? null,
            "photoUrl" => $response["thumbnail_url"] ?? null,
            "videoID" => $response["video_id"] ?? null,
        ];

        return new VimeoEmbed($data);
    }
}
