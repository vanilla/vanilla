<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Garden\EventManager;
use Garden\StaticCacheTranslationTrait;
use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\Attachment;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Formatting\Heading;
use Vanilla\Formatting\Html\HtmlEnhancer;
use Vanilla\Formatting\Html\HtmlSanitizer;

/**
 * Format definition for HTML based formats.
 */
class HtmlFormat implements FormatInterface {

    use StaticCacheTranslationTrait;

    const FORMAT_KEY = "Html";

    /** @var HtmlSanitizer */
    private $htmlSanitizer;

    /** @var HtmlEnhancer */
    private $htmlEnhancer;

    /** @var bool */
    private $shouldCleanupLineBreaks;

    /**
     *
     * @param HtmlSanitizer $htmlSanitizer
     * @param HtmlEnhancer $htmlEnhancer
     * @param bool $shouldCleanupLineBreaks
     */
    public function __construct(
        HtmlSanitizer $htmlSanitizer,
        HtmlEnhancer $htmlEnhancer,
        bool $shouldCleanupLineBreaks = true
    ) {
        $this->htmlSanitizer = $htmlSanitizer;
        $this->htmlEnhancer = $htmlEnhancer;
        $this->shouldCleanupLineBreaks = $shouldCleanupLineBreaks;
    }

    public function renderHtml(string $value): string {
        $sanitized = $this->htmlSanitizer->filter($value);

        if ($this->shouldCleanupLineBreaks) {
            $sanitized = self::cleanupLineBreaks($sanitized);
        }

        $enhanced = $this->htmlEnhancer->enhance($sanitized);
        return $enhanced;
    }

    public function renderExcerpt(string $content, string $query = null): string {
        // TODO: Implement renderExcerpt() method.
    }

    public function renderPlainText(string $content): string {
        // TODO: Implement renderPlainText() method.
    }

    public function renderQuote(string $content): string {
        // TODO: Implement renderQuote() method.
    }

    public function filter(string $content): string {
        try {
            $this->renderHtml($content);
        } catch (\Exception $e) {
            // Rethrow as a formatting exception with exception chaining.
            throw new FormattingException($e->getMessage(), 500, $e);
        }
        return $content;
    }

    public function parseAttachments(string $content): array {
        return [];
    }

    public function parseHeadings(string $content): array {
        $rendered = $this->renderHtml($content);
        $dom = new \DOMDocument();
        $dom->loadHTML($rendered);

        $xpath = new \DOMXPath($dom);
        $domHeadings = $xpath->query('.//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]');

        /** @var Heading[] $headings */
        $headings = [];

        /** @var \DOMNode $domHeading */
        foreach ($domHeadings as $domHeading) {
            $level = str_replace('h', 0, $domHeading->nodeValue);
            $level = filter_var($level, FILTER_VALIDATE_INT);

            if (!$level) {
                continue;
            }

            $headings[] = new Heading(
                $domHeading->textContent,
                $level,
                'no_id_found'
            );
        }

        return $headings;
    }

    public function parseMentions(string $content): array {
        return [];
    }

    const BLOCK_WITH_OWN_WHITESPACE =
        "(?:table|dl|ul|ol|pre|blockquote|address|p|h[1-6]|" .
        "section|article|aside|hgroup|header|footer|nav|figure|" .
        "figcaption|details|menu|summary|li|tbody|tr|td|th|" .
        "thead|tbody|tfoot|col|colgroup|caption|dt|dd)";

    /**
     * Removes the break above and below tags that have their own natural margin.
     *
     * @param string $html An HTML string to process.
     *
     * @return string
     * @internal Marked public for internal backwards compatibility only.
     */
    public function cleanupLineBreaks(string $html): string {
        $zeroWidthWhitespaceRemoved = preg_replace(
            "/(?!<code[^>]*?>)(\015\012|\012|\015)(?![^<]*?<\/code>)/",
            "<br />",
            $html
        );
        $breakBeforeReplaced = preg_replace(
            '!(?:<br\s*/>){1,2}\s*(<' . self::BLOCK_WITH_OWN_WHITESPACE. '[^>]*>)!',
            "\n$1",
            $zeroWidthWhitespaceRemoved
        );
        $breakAfterReplaced = preg_replace(
            '!(</' . self::BLOCK_WITH_OWN_WHITESPACE . '[^>]*>)\s*(?:<br\s*/>){1,2}!',
            "$1\n",
            $breakBeforeReplaced
        );
        return $breakAfterReplaced;
    }

    /**
     * Check to see if a string has spoilers and replace them with an innocuous string.
     *
     * Good for displaying excerpts from discussions and without showing the spoiler text.
     *
     * @param string $html An HTML-formatted string.
     * @param string $replaceWith The translation code to replace spoilers with.
     *
     * @return string Returns the html with spoilers removed.
     * @internal Marked public for internal backwards compatibility only.
     */
    public function replaceSpoilersWithPlaintext(string $html, string $replaceWith = "(Spoiler)") {
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $spoilers = $xpath->query(".//*[contains(@class, 'Spoiler') or contains(@class, 'UserSpoiler')]");

        /** @var \DOMNode $spoiler */
        foreach ($spoilers as $spoiler) {
            $replacement = new \DOMNode();
            $replacement->textContent = self::t($replaceWith);
            $spoiler->parentNode->replaceChild($replacement, $spoiler);
        }

        return $dom->saveHTML();
    }
}
