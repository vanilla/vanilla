<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Formatting\Html\HtmlEnhancer;
use Vanilla\Formatting\Html\HtmlSanitizer;

class MarkdownFormat extends HtmlFormat {

    const FORMAT_KEY = "Markdown";

    /** @var \MarkdownVanilla */
    private $markdownParser;

    public function __construct(
        \MarkdownVanilla $markdownParser,
        HtmlSanitizer $htmlSanitizer,
        HtmlEnhancer $htmlEnhancer
    ) {
        // The markdown parser already encodes code blocks.
        $htmlSanitizer->setShouldEncodeCodeBlocks(false);
        parent::__construct($htmlSanitizer, $htmlEnhancer, false);
        $this->markdownParser = $markdownParser;
        $this->markdownParser->addAllFlavor();
    }

    public function renderHtml(string $value): string {
        $markdownParsed = $this->markdownParser->transform($value);
        return parent::renderHtml($markdownParsed);
    }
}
