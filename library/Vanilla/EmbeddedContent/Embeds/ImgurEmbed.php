<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Schema\Schema;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\EmbeddedContentException;
use Vanilla\EmbeddedContent\EmbedUtils;
use Vanilla\Web\Asset\AssetPreloader;
use Vanilla\Web\Asset\ExternalAsset;

/**
 * Embed data object for imgur.
 */
class ImgurEmbed extends AbstractEmbed {

    const JS_SCRIPT = "https://s.imgur.com/min/embed.js";
    const TYPE = "imgur";

    /**
     * Override to set a value in the PreloadAssetModel.
     * @inheritdoc
     */
    public function __construct(array $data) {
        parent::__construct($data);

        EmbedUtils::getPreloadModel()->addScript(
            new ExternalAsset(self::JS_SCRIPT),
            AssetPreloader::REL_PRELOAD,
            'imgur-embed-script-asset'
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
            "imgurID" => "attributes.postID",
        ]);
        if (array_key_exists("imgurID", $data) && ($data["attributes"]["isAlbum"] ?? false)) {
            $data["imgurID"] = "a/{$data['imgurID']}";
        }
        $data = EmbedUtils::ensureDimensions($data);
        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema {
        return Schema::parse([
            "height:i",
            "width:i",
            "imgurID:s",
        ]);
    }
}
