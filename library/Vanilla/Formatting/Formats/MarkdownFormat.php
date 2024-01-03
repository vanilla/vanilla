<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Formatting\FormatConfig;
use Vanilla\Formatting\FormatRegexReplacements;
use Vanilla\Formatting\Html\HtmlEnhancer;
use Vanilla\Formatting\Html\HtmlPlainTextConverter;
use Vanilla\Formatting\Html\HtmlSanitizer;

/**
 * Class for rendering content of the markdown format.
 */
class MarkdownFormat extends HtmlFormat
{
    const FORMAT_KEY = "markdown";

    /** @var \MarkdownVanilla */
    private $markdownParser;

    /**
     * Constructor for dependency Injection.
     *
     * @param \MarkdownVanilla $markdownParser
     * @param HtmlSanitizer $htmlSanitizer
     * @param HtmlEnhancer $htmlEnhancer
     * @param HtmlPlainTextConverter $plainTextConverter
     * @param FormatConfig $formatConfig
     */
    public function __construct(
        \MarkdownVanilla $markdownParser,
        HtmlSanitizer $htmlSanitizer,
        HtmlEnhancer $htmlEnhancer,
        HtmlPlainTextConverter $plainTextConverter,
        FormatConfig $formatConfig
    ) {
        // The Markdown parser already encodes code blocks.
        $htmlSanitizer->setShouldEncodeCodeBlocks(false);
        parent::__construct($htmlSanitizer, $htmlEnhancer, $plainTextConverter, false);
        $this->markdownParser = $markdownParser;
        if ($formatConfig->useVanillaMarkdownFlavor()) {
            $this->markdownParser->addAllFlavor();
        }
    }

    /**
     * This override does not format spoiler so that it can be done early.
     *
     * @inheritdoc
     */
    protected function legacySpoilers(string $html): string
    {
        return $html;
    }

    /**
     * @inheritdoc
     */
    public function renderHtml($content, bool $enhance = true): string
    {
        if ($content instanceof HtmlFormatParsed) {
            $processed = $content->getProcessedHtml();
            return $processed;
        } else {
            $content = parent::legacySpoilers($content);
            $processed = $this->markdownParser->transform($content);
        }

        return parent::renderHtml($processed, $enhance);
    }

    /**
     * @inheritdoc
     */
    public function renderQuote($content): string
    {
        if ($content instanceof HtmlFormatParsed) {
            $processed = $content->getProcessedHtml();
        } else {
            $processed = $this->markdownParser->transform($content);
        }
        return parent::renderQuote($processed);
    }

    /**
     * @inheritdoc
     */
    public function removeUserPII(string $username, string $body): string
    {
        $regex = new FormatRegexReplacements();
        $regex->addReplacement(...$this->getNonRichAtMentionReplacePattern($username, $this->anonymizeUsername));
        $regex->addReplacement(...$this->getUrlReplacementPattern($username, $this->anonymizeUrl));
        return $regex->replace($body);
    }
}
