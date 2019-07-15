<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Schema\Schema;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\EmbedUtils;
use Vanilla\Web\Asset\AssetPreloader;
use Vanilla\Web\Asset\ExternalAsset;

/**
 * Embed data object for Instagram.
 */
class InstagramEmbed extends AbstractEmbed {

    const JS_SCRIPT = "https://platform.instagram.com/en_US/embeds.js";
    const TYPE = "instagram";

    /**
     * Override to set a value in the PreloadAssetModel.
     * @inheritdoc
     */
    public function __construct(array $data) {
        parent::__construct($data);

        EmbedUtils::getPreloadModel()->addScript(
            new ExternalAsset(self::JS_SCRIPT),
            AssetPreloader::REL_PRELOAD,
            'instagram-embed-script-asset'
        );
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
        $data = EmbedUtils::remapProperties($data, [
            "version" => "attributes.versionNumber",
        ]);
        $permaLink = $data["attributes"]["permaLink"] ?? null;
        if (is_string($permaLink)) {
            $data["postID"] = $this->urlToID($permaLink);
        }
        $data = EmbedUtils::ensureDimensions($data);
        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema {
        return Schema::parse([
            "height:i?",
            "width:i?",
            "photoUrl:s?",
            "postID:s",
            "version:i",
        ]);
    }

    /**
     * Given an embed URL, return the post ID.
     *
     * @param string $url
     * @return string|null
     */
    private function urlToID(string $url): ?string {
        return preg_match("`/?p/(?<postID>[\w-]+)`", parse_url($url, PHP_URL_PATH) ?? "", $matches) ? $matches["postID"] : null;
    }
}
