<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Garden\Container\Container;
use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\Exception\FormatterNotFoundException;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Formatting\Formats\NotFoundFormat;

/**
 * Simple service for calling out to formatters registered in FormatFactory.
 */
class FormatService {

    /** @var array */
    private $formats = [];

    /**
     * Render a safe, sanitized, HTML version of some content.
     *
     * @param string $content The content to render.
     * @param string $format The format of the content.
     *
     * @return string
     */
    public function renderHTML(string $content, string $format): string {
        return $this
            ->getFormatter($format)
            ->renderHTML($content);
    }

    /**
     * Format a particular string.
     *
     * @param string $content The content to render.
     * @param string $format The format of the content.
     *
     * @return string
     *
     * @throws FormattingException If the post content wasn't valid and couldn't be filtered.
     */
    public function filter(string $content, string $format): string {
        return $this
            ->getFormatter($format)
            ->filter($content);
    }


    /**
     * Render a safe, sanitized, short version of some content.
     *
     * @param string $content The content to render.
     * @param string $format The format of the content.
     *
     * @return string
     */
    public function renderExcerpt(string $content, string $format): string {
        return $this
            ->getFormatter($format)
            ->renderExcerpt($content);
    }

    /**
     * Render a plain text version of some content.
     *
     * @param string $content The content to render.
     * @param string $format The format of the content.
     *
     * @return string
     */
    public function renderPlainText(string $content, string $format): string {
        return $this
            ->getFormatter($format)
            ->renderPlainText($content);
    }

    /**
     * Render a version of the content suitable to be quoted in other content.
     *
     * @param string $content The raw content to render.
     * @param string $format The format of the content.
     *
     * @return string
     */
    public function renderQuote(string $content, string $format): string {
        return $this
            ->getFormatter($format)
            ->renderQuote($content);
    }

    /**
     * Parse attachment data from a message.
     *
     * @param string $content The content the parse.
     * @param string $format The format of the content.
     *
     * @return Attachment[]
     */
    public function parseAttachments(string $content, string $format): array {
        return $this
            ->getFormatter($format)
            ->parseAttachments($content);
    }

    /**
     * Parse out a list of headings from the post contents.
     *
     * @param string $content The raw content to parse.
     * @param string $format The format of the content.
     *
     * @return Heading[]
     */
    public function parseHeadings(string $content, string $format): array {
        return $this
            ->getFormatter($format)
            ->parseHeadings($content);
    }

    /**
     * Parse out a list of usernames mentioned in the post contents.
     *
     * @param string $content The content the parse.
     * @param string $format The format of the content.
     *
     * @return string[] A list of usernames.
     */
    public function parseMentions(string $content, string $format): array {
        return $this
            ->getFormatter($format)
            ->parseMentions($content);
    }

    /**
     * Register vanilla's built-in formats.
     *
     * @param Container $dic
     */
    public function registerBuiltInFormats(Container $dic) {
        $this->registerFormat(Formats\RichFormat::FORMAT_KEY, $dic->get(Formats\RichFormat::class))
            ->registerFormat(Formats\HtmlFormat::FORMAT_KEY, $dic->get(Formats\HtmlFormat::class))
            ->registerFormat(Formats\WysiwygFormat::FORMAT_KEY, $dic->get(Formats\WysiwygFormat::class))
            ->registerFormat(Formats\BBCodeFormat::FORMAT_KEY, $dic->get(Formats\BBCodeFormat::class))
            ->registerFormat(Formats\MarkdownFormat::FORMAT_KEY, $dic->get(Formats\MarkdownFormat::class))
            ->registerFormat(Formats\TextFormat::FORMAT_KEY, $dic->get(Formats\TextFormat::class))
            ->registerFormat(Formats\TextExFormat::FORMAT_KEY, $dic->get(Formats\TextExFormat::class))
        ;
    }

    /**
     * Register a format type and the class name handles it.
     *
     * @param string $formatKey
     * @param FormatInterface $format
     *
     * @return $this For method chaining.
     */
    public function registerFormat(string $formatKey, FormatInterface $format): FormatService {
        $this->formats[$formatKey] = $format;
        return $this;
    }

    /**
     * Get an instance of a formatter.
     *
     * @param string $formatKey The key of the format to fetch.
     * @param bool $throw Whether or not to throw an exception if the format couldn't be found.
     *
     * @return FormatInterface
     * @throws FormatterNotFoundException If $throw === true &&  the formatter that was requested could not be found.
     */
    private function getFormatter(string $formatKey, $throw = false): FormatInterface {
        $formatKey = strtolower($formatKey);
        $format = $this->formats[$formatKey] ?? null;
        $errorMessage = "Unable to find a formatter for the formatKey $formatKey.";
        if (!$format) {
            if ($throw) {
                throw new FormatterNotFoundException($errorMessage);
            } else {
                return new NotFoundFormat($formatKey);
            }
        }

        return $format;
    }
}
