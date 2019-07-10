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
 * Embed data object for Wistia.
 */
class WistiaEmbed extends AbstractEmbed {

    const TYPE = "wistia";

    /**
     * Generate a valid frame source URL, based on the provided data array.
     *
     * @param array $data
     * @return string|null
     */
    private function frameSource(array $data): ?string {
        return array_key_exists("videoID", $data) ? "https://fast.wistia.net/embed/iframe/{$data['videoID']}?autoPlay=1" : null;
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
            "videoID:s",
        ]);
    }

    /**
     * Given an embed URL, return the video ID.
     *
     * @param string $url
     * @return string|null
     */
    private function urlToID(string $url): ?string {
        return preg_match("`/?embed/iframe/(?<videoID>[\w-]+)`", parse_url($url, PHP_URL_PATH) ?? "", $matches) ? $matches["videoID"] : null;
    }
}
