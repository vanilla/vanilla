<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent;

/**
 * Interface for filtering embeds.
 */
interface EmbedFilterInterface {

    /**
     * Whether or not the filter can handle a particular embed type.
     *
     * @param string $embedType
     * @return bool
     */
    public function canHandleEmbedType(string $embedType): bool;

    /**
     * Filter some embed data.
     * This is used for sanitizing embeds on the way in/out of the site.
     *
     * @param AbstractEmbed $embed
     * @return AbstractEmbed
     */
    public function filterEmbed(AbstractEmbed $embed): AbstractEmbed;
}
