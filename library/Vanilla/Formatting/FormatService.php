<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Garden\Container\Container;
use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\Exception\FormatterNotFoundException;
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
        $formatter = $this->getFormatter($format);
        $result = $formatter->renderHTML($content);
        return $result;
    }

    /**
     * Render a safe, sanitized, short version of some content.
     *
     * @param string $content The content to render.
     * @param string $format The format of the content.
     * @param string $query A string to try and ensure is in the outputted excerpt.
     *
     * @return string
     */
    public function renderExcerpt(string $content, string $format, string $query = null): string {
        $formatter = $this->getFormatter($format);
        $result = $formatter->renderExcerpt($content, $query);
        return $result;
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
        $formatter = $this->getFormatter($format);
        $result = $formatter->renderPlainText($content);
        return $result;
    }

    /**
     * Render a version of the content suitable to be quoted in other content.
     *
     * @param string|array $content The raw content to render.
     * @param string $format The format of the content.
     *
     * @return string
     */
    public function renderQuote($content, string $format): string {
        // Sometimes quotes come in as an array (normally from nested JSON).
        $stringContent = is_array($content) ? json_encode($content) : $content;

        $formatter = $this->getFormatter($format);
        $result = $formatter->renderQuote($stringContent);
        return $result;
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
        $formatter = $this->getFormatter($format);
        $result = $formatter->parseAttachments($content);
        return $result;
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
        $formatter = $this->getFormatter($format);
        $result = $formatter->parseHeadings($content);
        return $result;
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
        $formatter = $this->getFormatter($format);
        $result = $formatter->parseMentions($content);
        return $result;
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
        $formatKey = strtolower($formatKey);
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
    public function getFormatter(string $formatKey, $throw = false): FormatInterface {
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
