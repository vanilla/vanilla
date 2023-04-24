<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Formatting;

use Vanilla\Formatting\Attachment;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Formatting\ParsedFormat;

/**
 * An interface for rendering, filtering, and parsing user content.
 *
 * @template T extends ParsedFormat
 */
interface FormatInterface
{
    /**
     * Render a safe, sanitized, HTML version of some content.
     *
     * @param string|T $content The content to render.
     *
     * @return string
     */
    public function renderHTML($content): string;

    /**
     * Render a safe, sanitized, HTML version of some content.
     *
     * @param ?array $context The context related to rendered content.
     *
     * @return FormatInterface
     */
    public function setContext(?array $context): FormatInterface;

    /**
     * Render a safe, sanitized, short version of some content.
     *
     * @param string|T $content The content to render.
     *
     * @return string
     */
    public function renderExcerpt($content): string;

    /**
     * Render a plain text version of some content.
     *
     * @param string|T $content The content to render.
     *
     * @return string
     */
    public function renderPlainText($content): string;

    /**
     * Calculate the length of content with formatting and metadata removed.
     *
     * @param string|T $content
     * @return int
     */
    public function getPlainTextLength($content): int;

    /**
     * Render a version of the content   suitable to be quoted in other content.
     *
     * @param string|T $content The raw content to render.
     *
     * @return string
     */
    public function renderQuote($content): string;

    /**
     * Format a particular string.
     *
     * @param string|T $content The content to render.
     * @return string
     *
     * @throws FormattingException If the post content wasn't valid and couldn't be filtered.
     */
    public function filter($content): string;

    /**
     * Generate an intermediary parsed format that we can use pass into other formatting methods.
     * This can help optimize cases where we are processing the same content in multiple ways.
     *
     * @param string $content
     *
     * @return T
     */
    public function parse(string $content);

    /**
     * Parse a list of attachments from some contents.
     *
     * @param string|T $content The raw content to parse.
     *
     * @return Attachment[]
     */
    public function parseAttachments($content): array;

    /**
     * Parse out a list of headings from the post contents.
     *
     * @param string|T $content The raw content to parse.
     *
     * @return Heading[]
     */
    public function parseHeadings($content): array;

    /**
     * Parse images out of the post contents.
     *
     * @param string|T $content
     *
     * @return string[]
     */
    public function parseImageUrls($content): array;

    /**
     * Parse image data from post content.
     *
     * @param string|T $content
     *
     * @return array
     */
    public function parseImages($content): array;

    /**
     * Parse out a list of usernames mentioned in the post contents.
     *
     * @param string|T $content The raw content to parse.
     *
     * @return string[] A list of usernames.
     */
    public function parseMentions($content, bool $skipTaggedContent = true): array;

    /**
     * @param bool $extendContent
     */
    public function setAllowExtendedContent(bool $extendContent): void;

    /**
     * Anonymize the username from body by replacing the username with the replacement value.
     *
     * @param string $username
     * @param string $body
     * @return string
     */
    public function removeUserPII(string $username, string $body): string;

    /**
     * Parse out every user mention from a post.
     *
     * @param string|T $body
     * @return array Username mentioned in the post.
     */
    public function parseAllMentions($body): array;
}
