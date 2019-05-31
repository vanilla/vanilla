<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent;

/**
 * Interface for a class that generates embeds.
 */
interface EmbedCreatorInterface {
    /**
     * Creates an embed from a given URL.
     * This is a potentially very slow/expensive operation.
     * Only ever perform this on writes, or in a queue.
     *
     * @param string $url
     * @return AbstractEmbed
     */
    public function createEmbedForUrl(string $url): AbstractEmbed;

    /**
     * Create an embed class from already fetched data.
     * Implementations should be fast and capable of running in loop on every page load.
     *
     * @param array $data
     * @return AbstractEmbed
     */
    public function createEmbedFromData(array $data): AbstractEmbed;
}
