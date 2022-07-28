<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Schema\Schema;
use Vanilla\EmbeddedContent\AbstractEmbed;

/**
 * Embed data object for Kaltura.
 */
class KalturaEmbed extends AbstractEmbed
{
    const TYPE = "kaltura";

    /**
     * @inheritdoc
     */
    protected function getAllowedTypes(): array
    {
        return [self::TYPE];
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema
    {
        return Schema::parse(["height:i", "width:i", "photoUrl:s?", "frameSrc:s"]);
    }
}
