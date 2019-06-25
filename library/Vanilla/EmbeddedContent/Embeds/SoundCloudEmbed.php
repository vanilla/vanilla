<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Schema\Schema;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\EmbedUtils;

/**
 * Embed data object for SoundCloud.
 */
class SoundCloudEmbed extends AbstractEmbed {

    const API_HOST = "api.soundcloud.com";

    const TYPE = "soundcloud";

    /**
     * Given an API URL, get an associative ID representing the type and resource ID.
     *
     * @param string $url
     * @return array|null
     */
    private function apiUrlToID(string $url): array {
        if (parse_url($url, PHP_URL_HOST) !== self::API_HOST) {
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
    protected function getAllowedTypes(): array {
        return [self::TYPE];
    }

    /**
     * @inheritdoc
     */
    public function normalizeData(array $data): array {
        $embedUrl = $data["attributes"]["embedUrl"] ?? null;
        $postID = $data["attributes"]["postID"] ?? null;
        if (is_string($embedUrl) && is_string($postID)) {
            $data = array_merge($this->urlToID($embedUrl, $postID), $data);
        }
        $data = EmbedUtils::ensureDimensions($data);
        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema {
        return Schema::parse([
            "userID:s?",
            "photoUrl:s?",
            "playlistID:s?",
            "trackID:s?",
            "showArtwork" => [
                "default" => true,
                "type" => "boolean",
            ],
            "useVisualPlayer" => [
                "default" => true,
                "type" => "boolean",
            ],
        ])->requireOneOf(["userID", "playlistID", "trackID"]);
    }

    /**
     * Given an embed URL, return an associative array representing the resource type and ID.
     *
     * @param string $url
     * @param string $postID
     * @return array
     */
    private function urlToID(string $url, string $postID): array {
        $parameters = [];
        parse_str(parse_url($url, PHP_URL_QUERY) ?? "", $parameters);

        if (!array_key_exists("url", $parameters) || parse_url($parameters["url"], PHP_URL_HOST) !== self::API_HOST) {
            return [];
        }

        $path = parse_url($parameters["url"], PHP_URL_PATH) ?? "";
        if (preg_match("`/?playlists/`", $path)) {
            return ["playlistID" => $postID];
        } elseif (preg_match("`/?tracks/`", $path)) {
            return ["trackID" => $postID];
        } elseif (preg_match("`/?users/`", $path)) {
            return ["userID" => $postID];
        } else {
            return [];
        }
    }
}
