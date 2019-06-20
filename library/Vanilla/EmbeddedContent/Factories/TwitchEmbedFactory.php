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

    const CLIPS_DOMAIN = "clips.twitch.tv";
    const PRIMARY_DOMAINS = ["www.twitch.tv", "twitch.tv"];

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
        return self::PRIMARY_DOMAINS + [self::CLIPS_DOMAIN];
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedPathRegex(string $domain): string {
        if ($domain === self::CLIPS_DOMAIN) {
            return "`^/(?!embed)[^/]+`";
        } else {
            return "`^/(?!directory|downloads|jobs|turbo)`";
        }
    }

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        $twitchID = $this->idFromUrl($url);

        $response = $this->httpClient->get(
            self::OEMBED_URL_BASE,
            ["url" => $url]
        );

        // Example Response JSON
        // {
        //     "version": 1,
        //     "type": "video",
        //     "twitch_type": "vod",
        //     "title": "Movie Magic",
        //     "author_name": "Jerma985",
        //     "author_url": "https://www.twitch.tv/jerma985",
        //     "provider_name": "Twitch",
        //     "provider_url": "https://www.twitch.tv/",
        //     "thumbnail_url": "https://static-cdn.jtvnw.net/s3_vods/aa1bb413e849cf63b446_jerma985_34594404336_1230815694/thumb/thumb0-640x360.jpg",
        //     "video_length": 19593,
        //     "created_at": "2019-06-19T21:22:59Z",
        //     "game": "The Movies",
        //     "html": "<iframe src=\"https://player.twitch.tv/?%21branding=&amp;autoplay=false&amp;video=v441409883\" width=\"500\" height=\"281\" frameborder=\"0\" scrolling=\"no\" allowfullscreen></iframe>",
        //     "width": 500,
        //     "height": 281,
        //     "request_url": "https://www.twitch.tv/videos/441409883"
        //   }

        $query = parse_url($url, PHP_URL_QUERY);
        $parameters = [];
        parse_str($query ?? "", $parameters);

        $data = [
            "embedType" => TwitchEmbed::TYPE,
            "url" => $url,
            "name" => $response["title"] ?? null,
            "height" => $response["height"] ?? null,
            "width" => $response["width"] ?? null,
            "photoUrl" => $response["thumbnail_url"] ?? null,
            "twitchID" => $twitchID,
            "time" => $parameters["time"] ?? $parameters["t"] ?? null,
        ];

        return new TwitchEmbed($data);
    }

    /**
     * Given a Twitch URL, generate a unique ID.
     *
     * @param string $url
     * @return string|null
     */
    private function idFromUrl(string $url): ?string {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if ($host === false || $host === null || $path === false || $path === null) {
            return null;
        }

        if ($host === "clips.twitch.tv" || preg_match("`/(?<channel>[^/]+)/clip/(?<clipID>[^/]+)`", $path, $clipsMatch)) {
            return "clip:{$clipsMatch['clipID']}";
        } elseif (preg_match("`/(?<type>videos|collections)/(?<id>[^/]+)`", $path, $videosMatch)) {
            $type = $videosMatch["type"] === "videos" ? "video" : "collection";
            return "{$type}:{$videosMatch['id']}";
        } elseif (preg_match("`/(?<channel>[^/]+)`", $path, $channelMatch)) {
            return "channel:{$channelMatch['channel']}";
        }

        return null;
    }
}
