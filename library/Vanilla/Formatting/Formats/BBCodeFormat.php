<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Formatting\Html\HtmlEnhancer;
use Vanilla\Formatting\Html\HtmlSanitizer;

class BBCodeFormat extends HtmlFormat {

    /** @var \BBCode */
    private $bbcodeParser;

    public function __construct(
        \BBCode $bbcodeParser,
        HtmlSanitizer $htmlSanitizer,
        HtmlEnhancer $htmlEnhancer
    ) {
        // The BBCode parser already encodes code blocks.
        $htmlSanitizer->setShouldEncodeCodeBlocks(false);
        parent::__construct($htmlSanitizer, $htmlEnhancer, false);
        $this->bbcodeParser = $bbcodeParser;
    }

    public function renderHtml(string $value): string {
        $renderedBBCode = $this->bbcodeParser->format($value);
        return parent::renderHtml($renderedBBCode);
    }


}
