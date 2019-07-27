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
 * Embed data object for Getty Images.
 */
class GettyImagesEmbed extends AbstractEmbed {

    const LEGACY_TYPE = "getty";
    const TYPE = "gettyimages";

    /**
     * @inheritdoc
     */
    protected function getAllowedTypes(): array {
        return [
            self::TYPE,
            self::LEGACY_TYPE,
        ];
    }

    /**
     * @inheritdoc
     */
    public function normalizeData(array $data): array {
        $data = EmbedUtils::remapProperties($data, [
            "embedSignature" => "attributes.sig",
            "foreignID" => "attributes.id",
            "photoID" => "attributes.postID",
        ]);
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
            "name:s?",
            "photoUrl:s?",
            "photoID:s",
            "foreignID:s",
            "embedSignature:s",
        ]);
    }
}
