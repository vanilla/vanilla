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
 * Embed data object for Twitch.
 */
class TwitchEmbed extends AbstractEmbed {

    const TYPE = "twitch";

    /**
     * Generate an embed frame URL from a type and ID.
     *
     * @param string $id
     * @param string $type
     * @return string|null
     */
    private function frameSourceFromID(string $id, string $type): ?string {
        if ($type === "clip") {
            return "https://clips.twitch.tv/embed?clip=".urlencode($id);
        } else {
            $query = http_build_query([$type => $id]);
            return "https://player.twitch.tv/?" . $query;
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
            $data["twitchID"] = $this->urlToID($embedUrl);
        }
        $data = EmbedUtils::ensureDimensions($data);
        return $data;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize() {
        $data = parent::jsonSerialize();
        if (array_key_exists("twitchID", $data)) {
            list($type, $id) = explode(":", $data["twitchID"]);
            $data["frameSrc"] = $this->frameSourceFromID($id, $type);
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
            "time:s?",
            "twitchID:s",
        ]);
    }

    /**
     * Given an embed URL, convert it to a type:id string.
     *
     * @param string $url
     * @return string|null
     */
    private function urlToID(string $url): ?string {
        $host = parse_url($url, PHP_URL_HOST);
        $query = [];
        parse_str(parse_url($url, PHP_URL_QUERY) ?? "", $query);

        switch ($host) {
            case "clips.twitch.tv":
                return "clip:{$query["clip"]}" ?? null;
            case "player.twitch.tv":
                if (array_key_exists("video", $query)) {
                    return "video:{$query['video']}";
                } elseif (array_key_exists("collection", $query)) {
                    return "collection:{$query['collection']}";
                } elseif (array_key_exists("channel", $query)) {
                    return "channel:{$query['channel']}";
                }
                // Nothing we can identify? Fall through to the default.
            default:
                return null;
        }
    }
}
