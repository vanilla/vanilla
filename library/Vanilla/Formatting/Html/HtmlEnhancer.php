<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html;

use Garden\EventManager;

/**
 * Implementation of Vanilla's "magic" HTML processing.
 */
class HtmlEnhancer {

    /** @var EventManager */
    private $eventManager;

    /** @var \Emoji */
    private $emojiParser;

    /**
     * DI.
     *
     * @param EventManager $eventManager
     * @param \Emoji $emojiParser
     */
    public function __construct(EventManager $eventManager, \Emoji $emojiParser) {
        $this->eventManager = $eventManager;
        $this->emojiParser = $emojiParser;
    }


    /**
     * Enhance an HTML Vanilla's "magic" HTML processing.
     *
     * Runs an HTML string through our custom links, mentions, emoji and spoilers formatters.
     * Any thing done here is AFTER security filtering and must be extremely careful.
     * This should always be done LAST, after any other input formatters.
     *
     * @param string $sanitizedHtml An already HTML string.
     * @param bool $doMentions Whether or not to format mentions.
     * @param bool $doEmbeds Whether or not links should be converted into legacy embeds.
     *
     * @return string The formatted HTML string.
     *
     * @internal This method is only public so it can be used for backwards compat in Gdn_Format.
     */
    public function enhance(string $sanitizedHtml, bool $doMentions = true, bool $doEmbeds = true) {
        // Do event first so it doesn't have to deal with the other formatters.
        $eventsHandledHtml = $this->eventManager->fireFilter('format_filterHtml', $sanitizedHtml);

        // Embed & auto-links.
        $sanitizedHtml = \Gdn_Format::links($eventsHandledHtml, true, $doEmbeds);

        // Mentions.
        if ($doMentions) {
            $sanitizedHtml = \Gdn_Format::mentions($sanitizedHtml);
        }

        // Emoji.
        $sanitizedHtml = $this->emojiParser->translateToHtml($sanitizedHtml);

        return $sanitizedHtml;
    }
}
