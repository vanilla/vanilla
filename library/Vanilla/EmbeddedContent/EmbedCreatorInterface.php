<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent;

/**
 * Interface for a class that generates embeds.
 */
interface EmbedCreatorInterface
{
    /**
     * Creates an embed from a given URL.
     * This is a potentially very slow/expensive operation.
     * Only ever perform this on writes, or in a queue.
     *
     * @param string $url
     * @return AbstractEmbed
     * @throws \Garden\Schema\ValidationException Creation of embed classes use Garden\Schema.
     */
    public function createEmbedForUrl(string $url): AbstractEmbed;
}
