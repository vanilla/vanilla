<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Schema\Schema;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\EmbeddedContentException;

/**
 * Fallback scraped link embed.
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
        $legacyPostID = $data['attributes']['postID'] ?? null;
        $postID = $data['postID'] ?? null;
        if ($postID === null && $legacyPostID !== null) {
            $data['postID'] = $legacyPostID;
        }
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
