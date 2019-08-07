<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Formatting\FormatConfig;
use Vanilla\Formatting\Html\HtmlEnhancer;
use Vanilla\Formatting\Html\HtmlPlainTextConverter;
use Vanilla\Formatting\Html\HtmlSanitizer;

/**
 * Class for rendering content of the markdown format.
 */
class MarkdownFormat extends HtmlFormat {

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
        // The markdown parser already encodes code blocks.
        $htmlSanitizer->setShouldEncodeCodeBlocks(false);
        parent::__construct($htmlSanitizer, $htmlEnhancer, $plainTextConverter, false);
        $this->markdownParser = $markdownParser;
        if ($formatConfig->useVanillaMarkdownFlavor()) {
            $this->markdownParser->addAllFlavor();
        }
    }

    /**
     * @inheritdoc
     */
    public function renderHtml(string $value, bool $enhance = true): string {
        $markdownParsed = $this->markdownParser->transform($value);
        return parent::renderHtml($markdownParsed, $enhance);
    }

    /**
     * @inheritdoc
     */
    public function renderQuote(string $value): string {
        $markdownParsed = $this->markdownParser->transform($value);
        return parent::renderQuote($markdownParsed);
    }
}
