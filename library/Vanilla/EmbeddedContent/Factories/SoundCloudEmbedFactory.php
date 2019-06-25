<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Garden\Http\HttpClient;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\SoundCloudEmbed;
use Vanilla\Utility\HtmlParserTrait;

/**
 * Factory for SoundCloudEmbed.
 */
class SoundCloudEmbedFactory extends AbstractEmbedFactory {

    use HtmlParserTrait;

    const DOMAINS = ["soundcloud.com"];

    const OEMBED_URL_BASE = "https://soundcloud.com/oembed";

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
     * Given an API URL, get an associative ID representing the type and resource ID.
     *
     * @param string $url
     * @return array|null
     */
    private function apiUrlToID(string $url): array {
        if (parse_url($url, PHP_URL_HOST) !== SoundCloudEmbed::API_HOST) {
            return [];
        }

        $path = parse_url($url, PHP_URL_PATH) ?? "";
        if (preg_match("`/?playlists/(?<playlistID>\d+)`", $path, $playlistMatches)) {
            return ["playlistID" => $playlistMatches["playlistID"]];
        } elseif (preg_match("`/?tracks/(?<trackID>\d+)`", $path, $playlistMatches)) {
            return ["trackID" => $playlistMatches["trackID"]];
        } elseif (preg_match("`/?users/(?<userID>\d+)`", $path, $playlistMatches)) {
            return ["userID" => $playlistMatches["userID"]];
        } else {
            return [];
        }
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
        // Rely on oEmbed to do it all.
        return "`.*`";
    }

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        $response = $this->httpClient->get(
            self::OEMBED_URL_BASE,
            [
                "format" => "json",
                "url" => $url,
            ]
        );

        // Example Response JSON
        // {
        //     "version": 1,
        //     "type": "rich",
        //     "provider_name": "SoundCloud",
        //     "provider_url": "http://soundcloud.com",
        //     "height": 400,
        //     "width": "100%",
        //     "title": "Old Town Road (Remix) [feat. Billy Ray Cyrus] by Lil Nas X",
        //     "description": null,
        //     "thumbnail_url": "http://i1.sndcdn.com/artworks-7PqTQwTM5TmY-0-t500x500.jpg",
        //     "html": "<iframe width=\"100%\" height=\"400\" scrolling=\"no\" frameborder=\"no\" src=\"https://w.soundcloud.com/player/?visual=true&url=https%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F600964365&show_artwork=true\"></iframe>",
        //     "author_name": "Lil Nas X",
        //     "author_url": "https://soundcloud.com/secret-service-862007284"
        // }

        $frameAttributes = $this->parseSimpleAttrs($response["html"] ?? "", "iframe") ?? [];
        $config = $this->urlToConfig($frameAttributes["src"]);

        $data = [
            "embedType" => SoundCloudEmbed::TYPE,
            "url" => $url,
            "name" => $response["title"] ?? null,
        ];
        $data = array_merge($data, $config);

        return new SoundCloudEmbed($data);
    }

    /**
     * Given an embed URL, return a config array for the content.
     *
     * @param string $url
     * @return array
     */
    private function urlToConfig(string $url): array {
        $config = [];

        $parameters = [];
        parse_str(parse_url($url, PHP_URL_QUERY) ?? "", $parameters);

        $config["useVisualPlayer"] = $parameters["visual"] ?? true;
        $config["showArtwork"] = $parameters["show_artwork"] ?? true;

        if ($apiUrl = $parameters["url"] ?? null) {
            $config = array_merge($config, $this->apiUrlToID($apiUrl));
        }

        return $config;
    }
}
