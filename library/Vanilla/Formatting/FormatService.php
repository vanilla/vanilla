<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Garden\Container\Container;
use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Contracts\Formatting\FormatParsedInterface;
use Vanilla\Contracts\Formatting\Heading;
use Vanilla\Formatting\Exception\FormatterNotFoundException;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Formatting\Formats\NotFoundFormat;
use Vanilla\ImageSrcSet\ImageSrcSetService;
use Vtiful\Kernel\Format;

/**
 * Simple service for calling out to formatters registered in FormatFactory.
 */
class FormatService
{
    /** @var array */
    private $formats = [];

    /** @var FormatInterface[] */
    private $formatInstances = [];

    /** @var ImageSrcSetService */
    private $imageSrcSetService;

    /**
     * DI.
     *
     * @param ImageSrcSetService $imageSrcSetService
     */
    public function __construct(ImageSrcSetService $imageSrcSetService)
    {
        $this->imageSrcSetService = $imageSrcSetService;
    }

    /**
     * Preparse a format to call multiple other format methods more efficiently.
     *
     * @param string|FormatParsedInterface $content The content to render.
     * @param string|null $format The format of the content.
     * @param array|null $context context for logging.
     *
     * @return FormatParsedInterface
     */
    public function parse(string $content, ?string $format = null, ?array $context = null): FormatParsedInterface
    {
        return $this->getFormatter($format)
            ->setContext($context)
            ->parse($content);
    }

    /**
     * Render a safe, sanitized, HTML version of some content.
     *
     * @param string|FormatParsedInterface $content The content to render.
     * @param string|null $format The format of the content.
     * @param array|null $context context for logging.
     *
     * @return string
     */
    public function renderHTML($content, ?string $format = null, ?array $context = null): string
    {
        if ($content instanceof FormatParsedInterface) {
            $format = $content->getFormatKey();
        }
        if (empty($content) && empty($format)) {
            return "";
        }

        return $this->getFormatter($format)
            ->setContext($context)
            ->renderHTML($content);
    }

    /**
     * Format a particular string.
     *
     * @param string|FormatParsedInterface $content The content to render.
     * @param string|null $format The format of the content.
     *
     * @return string
     *
     * @throws FormattingException If the post content wasn't valid and couldn't be filtered.
     * @throws FormatterNotFoundException If the format doesn't have a match.
     */
    public function filter($content, ?string $format): string
    {
        if ($content instanceof FormatParsedInterface) {
            $format = $content->getFormatKey();
        }
        return $this->getFormatter($format, true)->filter($content);
    }

    /**
     * Render a safe, sanitized, short version of some content.
     *
     * @param string|FormatParsedInterface $content The content to render.
     * @param string|null $format The format of the content.
     *
     * @return string
     */
    public function renderExcerpt($content, ?string $format): string
    {
        if ($content instanceof FormatParsedInterface) {
            $format = $content->getFormatKey();
        }
        if (empty($format) && empty($content)) {
            return "";
        }

        return $this->getFormatter($format)->renderExcerpt($content);
    }

    /**
     * Render a plain text version of some content.
     *
     * @param string|FormatParsedInterface $content The content to render.
     * @param string|null $format The format of the content.
     *
     * @return string
     */
    public function renderPlainText($content, ?string $format): string
    {
        if ($content instanceof FormatParsedInterface) {
            $format = $content->getFormatKey();
        }
        $formatter = $this->getFormatter($format);
        return $formatter->renderPlainText($content);
    }

    /**
     * Get the length of content with all formatting removed.
     *
     * @param string|FormatParsedInterface $content The content to render.
     * @param string|null $format The format of the content.
     *
     * @return int The number of visible characters in $content.
     */
    public function getPlainTextLength($content, ?string $format): int
    {
        if ($content instanceof FormatParsedInterface) {
            $format = $content->getFormatKey();
        }
        return $this->getFormatter($format)->getPlainTextLength($content);
    }

    /**
     * Render a version of the content suitable to be quoted in other content.
     *
     * @param string|FormatParsedInterface $content The raw content to render.
     * @param string|null $format The format of the content.
     *
     * @return string
     */
    public function renderQuote($content, ?string $format): string
    {
        if ($content instanceof FormatParsedInterface) {
            $format = $content->getFormatKey();
        }
        return $this->getFormatter($format)->renderQuote($content);
    }

    /**
     * Parse attachment data from a message.
     *
     * @param string|FormatParsedInterface $content The content the parse.
     * @param string|null $format The format of the content.
     *
     * @return Attachment[]
     */
    public function parseAttachments($content, ?string $format): array
    {
        if ($content instanceof FormatParsedInterface) {
            $format = $content->getFormatKey();
        }
        return $this->getFormatter($format)->parseAttachments($content);
    }

    /**
     * Parse out the main image from some content.
     *
     * @param string|FormatParsedInterface $content
     * @param string|null $format
     *
     * @return array|null
     */
    public function parseMainImage($content, ?string $format): ?array
    {
        $images = $this->parseImages($content, $format);
        if (empty($images)) {
            return null;
        }

        $result = $images[0];
        $result["urlSrcSet"] = $this->imageSrcSetService->getResizedSrcSet($result["url"]);
        return $result;
    }

    /**
     * Parse images out of the post contents.
     *
     * @param string|FormatParsedInterface $content
     * @param string|null $format The format of the content.
     *
     * @return string[]
     */
    public function parseImageUrls($content, ?string $format): array
    {
        if ($content instanceof FormatParsedInterface) {
            $format = $content->getFormatKey();
        }
        return $this->getFormatter($format)->parseImageUrls($content);
    }

    /**
     * Parse image attributes out of the post contents.
     *
     * @param string|FormatParsedInterface $content
     * @param string|null $format The format of the content.
     *
     * @return array
     */
    public function parseImages($content, ?string $format): array
    {
        if ($content instanceof FormatParsedInterface) {
            $format = $content->getFormatKey();
        }
        return $this->getFormatter($format)->parseImages($content);
    }

    /**
     * Parse out a list of headings from the post contents.
     *
     * @param string|FormatParsedInterface $content The raw content to parse.
     * @param string|null $format The format of the content.
     *
     * @return Heading[]
     */
    public function parseHeadings($content, ?string $format): array
    {
        if ($content instanceof FormatParsedInterface) {
            $format = $content->getFormatKey();
        }
        return $this->getFormatter($format)->parseHeadings($content);
    }

    /**
     * Parse out a list of usernames mentioned in the post contents.
     *
     * @param string|FormatParsedInterface $content The content the parse.
     * @param string|null $format The format of the content.
     *
     * @return string[] A list of usernames.
     */
    public function parseMentions($content, ?string $format, bool $skipTaggedContent = true): array
    {
        if ($content instanceof FormatParsedInterface) {
            $format = $content->getFormatKey();
        }
        return $this->getFormatter($format)->parseMentions($content, $skipTaggedContent);
    }

    /**
     * Register vanilla's built-in formats.
     *
     * @param Container $dic
     */
    public function registerBuiltInFormats(Container $dic)
    {
        $this->registerFormat(Formats\RichFormat::FORMAT_KEY, Formats\RichFormat::class)
            ->registerFormat(Formats\HtmlFormat::FORMAT_KEY, Formats\HtmlFormat::class)
            ->registerFormat(Formats\BBCodeFormat::FORMAT_KEY, Formats\BBCodeFormat::class)
            ->registerFormat(Formats\MarkdownFormat::FORMAT_KEY, Formats\MarkdownFormat::class)
            ->registerFormat(Formats\TextFormat::FORMAT_KEY, Formats\TextFormat::class)
            ->registerFormat(Formats\TextExFormat::FORMAT_KEY, Formats\TextExFormat::class)
            ->registerFormat(Formats\WysiwygFormat::FORMAT_KEY, Formats\WysiwygFormat::class)
            ->registerFormat(Formats\WysiwygFormat::ALT_FORMAT_KEY, Formats\WysiwygFormat::class)
            ->registerFormat(Formats\DisplayFormat::FORMAT_KEY, Formats\DisplayFormat::class)
            ->registerFormat(Formats\Rich2Format::FORMAT_KEY, Formats\Rich2Format::class);
    }

    /**
     * Register a format type and the class name handles it.
     *
     * @param string $formatKey
     * @param FormatInterface|string $format
     *
     * @return $this For method chaining.
     */
    public function registerFormat(string $formatKey, $format): FormatService
    {
        if (is_object($format)) {
            $this->formatInstances[$formatKey] = $format;
            $format = get_class($format);
        }

        $this->formats[$formatKey] = $format;
        return $this;
    }

    /**
     * Check if we have a registered format.
     *
     * @param string|null $formatKey
     * @return bool
     */
    public function hasFormat(?string $formatKey): bool
    {
        if ($formatKey === null) {
            return false;
        }
        return array_key_exists(strtolower($formatKey), $this->formats);
    }

    /**
     * Get an instance of a formatter.
     *
     * @param string|null $formatKey The key of the format to fetch.
     * @param bool $throw Whether or not to throw an exception if the format couldn't be found.
     *
     * @return FormatInterface
     * @throws FormatterNotFoundException If $throw === true &&  the formatter that was requested could not be found.
     */
    public function getFormatter(?string $formatKey, $throw = false): FormatInterface
    {
        $formatKey = strtolower($formatKey) ?? null;
        $instance = $this->formatInstances[$formatKey] ?? null;

        if ($instance === null) {
            $formatClass = $this->formats[$formatKey] ?? null;
            $errorMessage = "Unable to find a formatter for the formatKey $formatKey.";
            if (!$formatClass) {
                if ($throw) {
                    throw new FormatterNotFoundException($errorMessage);
                } else {
                    return new NotFoundFormat($formatKey);
                }
            }

            $instance = $this->constructFormat($formatClass);
        }

        return $instance;
    }

    /**
     * Constuct a formatter.
     *
     * @param string $formatClass
     *
     * @return FormatInterface
     */
    protected function constructFormat(string $formatClass): FormatInterface
    {
        $instance = \Gdn::getContainer()->get($formatClass);
        return $instance;
    }

    /**
     * Anonymize the username from body by replacing the username with the replacement value.
     *
     * @param string $username
     * @param string $body
     * @param string|null $format The format of the content.
     *
     * @return string
     */
    public function removeUserPII(string $username, string $body, ?string $format): string
    {
        return $this->getFormatter($format)->removeUserPII($username, $body);
    }

    /**
     * Parse out every user mention from a post.
     *
     * @param string|FormatParsedInterface $body
     * @return array Username mentioned in the post.
     */
    public function parseAllMentions($body, ?string $format): array
    {
        if ($body instanceof FormatParsedInterface) {
            $format = $body->getFormatKey();
        }
        return $this->getFormatter($format)->parseAllMentions($body);
    }
}
