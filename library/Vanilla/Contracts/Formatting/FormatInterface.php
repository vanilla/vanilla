<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Formatting;

use Vanilla\Formatting\Attachment;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Formatting\Heading;

/**
 * An interface for rendering, filtering, and parsing user content.
 */
interface FormatInterface {

    /**
     * Render a safe, sanitized, HTML version of some content.
     *
     * @param string $content The content to render.
     *
     * @return string
     */
    public function renderHTML(string $content): string;

    /**
     * Render a safe, sanitized, short version of some content.
     *
     * @param string $content The content to render.
     *
     * @return string
     */
    public function renderExcerpt(string $content): string;

    /**
     * Render a plain text version of some content.
     *
     * @param string $content The content to render.
     *
     * @return string
     */
    public function renderPlainText(string $content): string;

    /**
     * Render a version of the content suitable to be quoted in other content.
     *
     * @param string $content The raw content to render.
     *
     * @return string
     */
    public function renderQuote(string $content): string;

    /**
     * Format a particular string.
     *
     * @param string $content The content to render.
     * @return string
     *
     * @throws FormattingException If the post content wasn't valid and couldn't be filtered.
     */
    public function filter(string $content): string;

    /**
     * Parse a list of attachments from some contents.
     *
     * @param string $content The raw content to parse.
     *
     * @return Attachment[]
     */
    public function parseAttachments(string $content): array;

    /**
     * Parse out a list of headings from the post contents.
     *
     * @param string $content The raw content to parse.
     *
     * @return Heading[]
     */
    public function parseHeadings(string $content): array;

    /**
     * Parse out a list of usernames mentioned in the post contents.
     *
     * @param string $content The raw content to parse.
     *
     * @return string[] A list of usernames.
     */
    public function parseMentions(string $content): array;
}
