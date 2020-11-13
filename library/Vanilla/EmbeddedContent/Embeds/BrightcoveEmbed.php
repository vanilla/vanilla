<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Schema\Schema;
use Vanilla\EmbeddedContent\AbstractEmbed;

/**
 * Embed data object for Brightcove.
 */
class BrightcoveEmbed extends AbstractEmbed {

    /** @var string TYPE */
    const TYPE = 'brightcove';

    /**
     * @inheritdoc
     */
    protected function getAllowedTypes(): array {
        return [self::TYPE];
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema {
        return Schema::parse([
            "account:s",
            "playerID:s",
            "playerEmbed:s",
            "videoID:s"
        ]);
    }
}
