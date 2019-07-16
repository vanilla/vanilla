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

/**
 * Embed data object for the giphy.
 */
class GiphyEmbed extends AbstractEmbed {

    const TYPE = "giphy";

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
            'giphyID' => 'attributes.postID',
        ]);
        $data = EmbedUtils::ensureDimensions($data);
        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema {
        return Schema::parse([
            'height:i',
            'width:i',
            'giphyID:s',
        ]);
    }
}
