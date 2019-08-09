<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Formatting\Html\HtmlEnhancer;
use Vanilla\Formatting\Html\HtmlPlainTextConverter;
use Vanilla\Formatting\Html\HtmlSanitizer;

/**
 * Class for rendering content with the source format BBCode.
 */
class BBCodeFormat extends HtmlFormat {

    const FORMAT_KEY = "bbcode";

    /** @var \BBCode */
    private $bbcodeParser;

    /**
     * Constructor for Dependency Injection
     *
     * @param \BBCode $bbcodeParser
     * @param HtmlSanitizer $htmlSanitizer
     * @param HtmlEnhancer $htmlEnhancer
     * @param HtmlPlainTextConverter $plainTextConverter
     */
    public function __construct(
        \BBCode $bbcodeParser,
        HtmlSanitizer $htmlSanitizer,
        HtmlEnhancer $htmlEnhancer,
        HtmlPlainTextConverter $plainTextConverter
    ) {
        // The BBCode parser already encodes code blocks.
        $plainTextConverter->setAddNewLinesAfterDiv(true);
        $htmlSanitizer->setShouldEncodeCodeBlocks(false);
        parent::__construct($htmlSanitizer, $htmlEnhancer, $plainTextConverter, false);
        $this->bbcodeParser = $bbcodeParser;
    }

    /**
     * @inheritdoc
     */
    public function renderHtml(string $value, bool $enhance = true): string {
        $renderedBBCode = $this->bbcodeParser->format($value);
        return parent::renderHtml($renderedBBCode, $enhance);
    }

    /**
     * @inheritdoc
     */
    public function renderQuote(string $value): string {
        $renderedBBCode = $this->bbcodeParser->format($value);
        return parent::renderQuote($renderedBBCode);
    }
}
