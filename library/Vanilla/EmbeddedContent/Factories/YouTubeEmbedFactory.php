<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Garden\Http\HttpClient;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\YouTubeEmbed;

/**
 * Factory for YouTubeEmbed.
 */
class YouTubeEmbedFactory extends AbstractEmbedFactory {

    const SHORT_DOMAIN = "youtu.be";

    const PRIMARY_DOMAINS = ["youtube.com", "m.youtube.com", "www.youtube.com"];

    const OEMBED_URL_BASE = "https://www.youtube.com/oembed";

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
        $domains = self::PRIMARY_DOMAINS;
        $domains[] = self::SHORT_DOMAIN;
        return $domains;
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedPathRegex(string $domain): string {
        if ($domain === self::SHORT_DOMAIN) {
            return "`^/?[\w-]{11}$`";
        } else {
            return "`^/?watch$`";
        }
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
        //     "type": "video",
        //     "provider_url": "https://www.youtube.com/",
        //     "thumbnail_url": "https://i.ytimg.com/vi/hCeNC1sfEMM/hqdefault.jpg",
        //     "author_name": "2ndJerma",
        //     "author_url": "https://www.youtube.com/channel/UCL7DDQWP6x7wy0O6L5ZIgxg",
        //     "version": "1.0",
        //     "provider_name": "YouTube",
        //     "html": "<iframe width=\"480\" height=\"270\" src=\"https://www.youtube.com/embed/hCeNC1sfEMM?feature=oembed\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>",
        //     "thumbnail_height": 360,
        //     "thumbnail_width": 480,
        //     "title": "The Best of 2018",
        //     "width": 480,
        //     "height": 270
        // }

        $parameters = [];
        parse_str(parse_url($url, PHP_URL_QUERY) ?? "", $parameters);

        $start = $this->startTime($url);

        $data = [
            "embedType" => YouTubeEmbed::TYPE,
            "url" => $url,
            "name" => $response["title"] ?? null,
            "height" => $response["height"] ?? null,
            "width" => $response["width"] ?? null,
            "photoUrl" => $response["thumbnail_url"] ?? null,
            "videoID" => $videoID,
            "listID" => $parameters["list"] ?? null,
            "showRelated" => ($parameters["rel"] ?? false) ? true : false,
            "start" => $start,
        ];

        return new YouTubeEmbed($data);
    }

    /**
     * Get a YouTube URL's time value and convert it to seconds (e.g. 2m8s to 128).
     *
     * @param string $url
     * @return int|null
     */
    private function startTime(string $url): ?int {
        $parameters = [];
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        parse_str(parse_url($url, PHP_URL_QUERY) ?? "", $parameters);

        if (!is_string($fragment) && empty($parameters)) {
            return null;
        }

        if (preg_match("/t=(?P<start>\d+)/", $fragment, $timeParts)) {
            return (int)$timeParts["start"];
        }

        if (preg_match("/^(?:(?P<ticks>\d+)|(?:(?P<minutes>\d*)m)?(?:(?P<seconds>\d*)s)?)$/", $parameters["t"] ?? "", $timeParts)) {
            if (array_key_exists("ticks", $timeParts) && $timeParts["ticks"] !== "") {
                return $timeParts["ticks"];
            } else {
                $minutes = $timeParts["minutes"] ? (int)$timeParts["minutes"] : 0;
                $seconds = $timeParts["seconds"] ? (int)$timeParts["seconds"] : 0;
                return ($minutes * 60) + $seconds;
            }
        }

        return null;
    }

    /**
     * Given a YouTube video URL, extract its video ID.
     *
     * @param string $url
     * @return string|null
     */
    private function videoIDFromUrl(string $url): ?string {
        $host = parse_url($url, PHP_URL_HOST);

        if ($host === self::SHORT_DOMAIN) {
            $path = parse_url($url, PHP_URL_PATH) ?? "";
            return preg_match("`^/?(?<videoID>[\w-]{11})`", $path, $matches) ? $matches["videoID"] : null;
        } else {
            $parameters = [];
            parse_str(parse_url($url, PHP_URL_QUERY) ?? "", $parameters);
            return $parameters["v"] ?? null;
        }

        return null;
    }
}
