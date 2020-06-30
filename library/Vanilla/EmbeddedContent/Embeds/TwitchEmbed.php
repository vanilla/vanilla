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

    /** @var string */
    private $host;

    /**
     * Generate an embed frame URL from a type and ID.
     *
     * @param string $id
     * @param string $type
     * @return string|null
     */
    private function frameSourceFromID(string $id, string $type): ?string {
        $params = [];
        if (!empty($this->host)) {
            $params['parent'] = $this->host;
        }
        if ($type === "clip") {
            $params['clip'] = $id;
            return "https://clips.twitch.tv/embed?".http_build_query($params);
        } else {
            $params[$type] = $id;
            return "https://player.twitch.tv/?" . http_build_query($params);
        }
    }

    /**
     * Sets the hostname from the request.
     *
     * @param string $host
     */
    public function setHost(string $host): void {
        $this->host = $host;
    }

    /**
     * Get the hostname from the request.
     *
     * @return string|null
     */
    public function getHost(): ?string {
        return $this->host;
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
