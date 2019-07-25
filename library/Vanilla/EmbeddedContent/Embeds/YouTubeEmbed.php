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
 * Embed data object for YouTube.
 */
class YouTubeEmbed extends AbstractEmbed {

    const TYPE = "youtube";

    /**
     * Generate a valid frame source URL, based on the provided data array.
     *
     * @param array $data
     * @return string|null
     */
    private function frameSource(array $data): ?string {
        $listID = $data["listID"] ?? null;
        $start = $data["start"] ?? null;
        $videoID = $data["videoID"] ?? null;
        $rel = $data["rel"] ?? null;

        if ($listID !== null) {
            $params = "feature=oembed&autoplay=1&list={$listID}";
            if ($videoID !== null) {
                return "https://www.youtube.com/embed/{$videoID}?{$params}";
            } else {
                return "https://www.youtube.com/embed/videoseries?{$params}";
            }
        } elseif ($videoID !== null) {
            $params = "feature=oembed&autoplay=1";

            if ($rel !== null) {
                $params .= "&rel=" . (int)$rel;
            }

            if ($start) {
                $params .= "&start={$start}";
            }

            return "https://www.youtube.com/embed/{$videoID}?{$params}";
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
        if (is_string($embedUrl)) {
            $data["videoID"] = $this->urlToID($embedUrl);
        }
        $data = EmbedUtils::ensureDimensions($data);
        return $data;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize() {
        $data = parent::jsonSerialize();
        if (array_key_exists("videoID", $data) || array_key_exists("listID", $data)) {
            $data["frameSrc"] = $this->frameSource($data);
        }
        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema {
        return Schema::parse([
            "height:i",
            "width:i",
            "photoUrl:s?",
            "videoID:s?",
            "listID:s?",
            "showRelated:b?",
            "start:i?",
        ])->requireOneOf(["listID", "videoID"]);
    }

    /**
     * Given an embed URL, return the video ID.
     *
     * @param string $url
     * @return string|null
     */
    private function urlToID(string $url): ?string {
        $path = parse_url($url, PHP_URL_PATH) ?? "";
        $query = [];
        parse_str(parse_url($url, PHP_URL_QUERY) ?? "", $query);

        if (!preg_match("`/embed/(?P<target>videoseries|[\w-]{11})$`", $path, $pathMatches)) {
            return false;
        }

        if ($pathMatches["target"] === "videoseries") {
            return null;
        } else {
            return $pathMatches["target"];
        }
    }
}
