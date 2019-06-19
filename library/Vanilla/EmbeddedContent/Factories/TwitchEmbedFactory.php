<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Garden\Http\HttpClient;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\TwitchEmbed;

/**
 * Factory for TwitchEmbed.
 */
class TwitchEmbedFactory extends AbstractEmbedFactory {

    const SUPPORTED_DOMAINS = ["clips.twitch.tv", "www.twitch.tv"];

    const OEMBED_URL_BASE = "https://api.twitch.tv/v5/oembed";

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
        return self::SUPPORTED_DOMAINS;
    }

    /**
     * We pass along to the oembed service. If it can't parse the URL, then we definitely can't.
     * @inheritdoc
     */
    protected function getSupportedPathRegex(string $domain): string {
        return "/.+/";
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
        // {
        //     "version": 1,
        //     "type": "video",
        //     "twitch_type": "clip",
        //     "title": "Lights! Camera! Action!",
        //     "author_name": "Jerma985",
        //     "author_url": "https://www.twitch.tv/jerma985",
        //     "curator_name": "funkengines",
        //     "curator_url": "https://www.twitch.tv/funkengines",
        //     "provider_name": "Twitch",
        //     "provider_url": "https://www.twitch.tv/",
        //     "thumbnail_url": "https://clips-media-assets2.twitch.tv/AT-cm%7C267415465-preview.jpg",
        //     "video_length": 32,
        //     "created_at": "2018-07-07T01:15:04Z",
        //     "game": "Dark Souls",
        //     "html": "<iframe src=\"https://clips.twitch.tv/embed?clip=KnottyOddFishShazBotstix&autoplay=false\" width=\"620\" height=\"351\" frameborder=\"0\" scrolling=\"no\" allowfullscreen></iframe>",
        //     "width": 620,
        //     "height": 351,
        //     "request_url": "https://www.twitch.tv/jerma985/clip/KnottyOddFishShazBotstix?filter=clips&range=all&sort=time",
        //     "author_thumbnail_url": "https://static-cdn.jtvnw.net/jtv_user_pictures/jerma985-profile_image-447425e773e6fd5c-150x150.jpeg",
        //     "author_id": "23936415",
        //     "view_count": 329483,
        //     "twitch_content_id": "267415465"
        //   }

        $data = [
            "embedType" => TwitchEmbed::TYPE,
            "url" => $response["request_url"] ?? null,
            "name" => $response["title"] ?? "",
            "height" => $response["height"] ?? null,
            "width" => $response["width"] ?? null,
            "twitchID" => $response["twitch_content_id"] ?? null,
        ];

        return new TwitchEmbed($data);
    }
}
