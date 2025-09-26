<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Garden\Container\Container;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Contracts\Formatting\FormatParsedInterface;
use Vanilla\Contracts\Formatting\Heading;
use Vanilla\Dashboard\Models\UserMentionsModel;
use Vanilla\Formatting\Exception\FormatterNotFoundException;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Formatting\Formats\NotFoundFormat;
use Vanilla\ImageSrcSet\ImageSrcSetService;
use Vanilla\Utility\Timers;

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

    private Timers $timers;

    /**
     * DI.
     */
    public function __construct(ImageSrcSetService $imageSrcSetService, Timers $timers, private FormatConfig $config)
    {
        $this->imageSrcSetService = $imageSrcSetService;
        $this->timers = $timers;
    }

    /**
     * Preparse a format to call multiple other format methods more efficiently.
     *
     * @param string|FormatParsedInterface $content The content to render.
     * @param string|null $format The format of the content.
     * @param array|null $context context for logging.
     *
     * @return FormatParsedInterface
     * @throws FormatterNotFoundException
     */
    public function parse(string $content, ?string $format = null, ?array $context = null): FormatParsedInterface
    {
        $formatter = $this->getFormatter($format)->setContext($context);
        $span = $this->timers->startGeneric(
            "FormatService::parse()",
            [
                "formatter" => get_class($formatter),
            ] +
                ($context ?? [])
        );

        try {
            return $formatter->parse($content);
        } finally {
            $span->finish();
        }
    }

    /**
     * Render a safe, sanitized, HTML version of some content.
     *
     * @param string|FormatParsedInterface $content The content to render.
     * @param string|null $format The format of the content.
     * @param array|null $context context for logging.
     *
     * @return string
     * @throws FormatterNotFoundException
     */
    public function renderHTML($content, ?string $format = null, ?array $context = null): string
    {
        if ($content instanceof FormatParsedInterface) {
            $format = $content->getFormatKey();
        }
        if (empty($content) && empty($format)) {
            return "";
        }

        $formatter = $this->getFormatter($format)->setContext($context);

        $span = $this->timers->startGeneric(
            "FormatService::renderHtml()",
            [
                "formatter" => get_class($formatter),
                "isPreparsed" => $content instanceof FormatParsedInterface,
            ] +
                ($context ?? [])
        );

        try {
            return $formatter->renderHTML($content);
        } finally {
            $span->finish();
        }
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
     * @param int|null $length The max-length for the excerpt
     *
     * @return string
     * @throws FormatterNotFoundException
     */
    public function renderExcerpt($content, ?string $format, ?int $length = null): string
    {
        if ($content instanceof FormatParsedInterface) {
            $format = $content->getFormatKey();
        }
        if (empty($format) && empty($content)) {
            return "";
        }

        $formatter = $this->getFormatter($format);

        $span = $this->timers->startGeneric("FormatService::renderHtml()", [
            "formatter" => get_class($formatter),
            "isPreparsed" => $content instanceof FormatParsedInterface,
        ]);

        try {
            $excerpt = $formatter->renderExcerpt($content, $length);
            return $formatter->applySanitizeProcessor($excerpt);
        } finally {
            $span->finish();
        }
    }

    /**
     * Render a plain text version of some content.
     *
     * @param string|FormatParsedInterface $content The content to render.
     * @param string|null $format The format of the content.
     *
     * @return string
     * @throws FormatterNotFoundException
     */
    public function renderPlainText($content, ?string $format): string
    {
        if ($content instanceof FormatParsedInterface) {
            $format = $content->getFormatKey();
        }
        $formatter = $this->getFormatter($format);
        $content = $formatter->renderPlainText($content);
        return $formatter->applySanitizeProcessor($content);
    }

    /**
     * Get the length of content with all formatting removed.
     *
     * @param string|FormatParsedInterface $content The content to render.
     * @param string|null $format The format of the content.
     *
     * @return int The number of visible characters in $content.
     * @throws FormatterNotFoundException
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
     * @throws FormatterNotFoundException
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
     * @throws FormatterNotFoundException
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
     * @throws FormatterNotFoundException
     */
    public function parseImageUrls($content, ?string $format): array
    {
        if ($content instanceof FormatParsedInterface) {
            $format = $content->getFormatKey();
        }

        if (is_null($format)) {
            $format = $this->config->getDefaultFormat();
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
     * @throws FormatterNotFoundException
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
     * @throws FormatterNotFoundException
     */
    public function parseMentions($content, ?string $format, bool $skipTaggedContent = true): array
    {
        $canParseMentions = UserMentionsModel::canParseMentions();
        if (!$canParseMentions) {
            return [];
        }
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
        if ($formatKey) {
            $formatKey = strtolower($formatKey) ?? null;
        }

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
     * @throws ContainerException
     * @throws NotFoundException
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
     * @throws FormatterNotFoundException
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
     * @throws FormatterNotFoundException
     */
    public function parseAllMentions($body, ?string $format): array
    {
        $canParseMentions = UserMentionsModel::canParseMentions();
        if (!$canParseMentions) {
            return [];
        }
        if ($body instanceof FormatParsedInterface) {
            $format = $body->getFormatKey();
        }
        return $this->getFormatter($format)->parseAllMentions($body);
    }
}
