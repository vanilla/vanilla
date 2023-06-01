<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace IPBFormatter\Formats;

use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\Formats\HtmlFormatParsed;
use Vanilla\Formatting\Html\HtmlEnhancer;
use Vanilla\Formatting\Html\HtmlPlainTextConverter;
use Vanilla\Formatting\Html\HtmlSanitizer;

/**
 * Class for rendering content with the source format IPB.
 */
class IPBFormat extends HtmlFormat
{
    const FORMAT_KEY = "ipb";

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
        $plainTextConverter->setAddNewLinesAfterDiv(true);
        $htmlSanitizer->setShouldEncodeCodeBlocks(false);
        parent::__construct($htmlSanitizer, $htmlEnhancer, $plainTextConverter, false);

        $this->bbcodeParser = $bbcodeParser;
        $this->bbcodeParser->nbbc()->setIgnoreNewlines(true);
        $this->bbcodeParser->nbbc()->setEscapeContent(false);
    }

    /**
     * @inheritdoc
     */
    public function renderHtml($content, bool $enhance = true): string
    {
        if ($content instanceof HtmlFormatParsed) {
            return $content->getProcessedHtml();
        } else {
            $ipb = $this->prepareBBCode($content);
            $rendered = $this->bbcodeParser->format($ipb);
        }
        return parent::renderHtml($rendered, $enhance);
    }

    /**
     * @inheritdoc
     */
    public function renderQuote($content): string
    {
        if ($content instanceof HtmlFormatParsed) {
            $rendered = $content->getProcessedHtml();
        } else {
            $ipb = $this->prepareBBCode($content);
            $rendered = $this->bbcodeParser->format($ipb);
        }
        return parent::renderQuote($rendered);
    }

    /**
     * Massage BBCode to account for IPB customizations.
     *
     * @param string $bbCode
     * @return string
     */
    private function prepareBBCode(string $bbCode): string
    {
        $ipbCode = str_replace(["&quot;", "&#39;", "&#58;", "Â"], ['"', "'", ":", ""], $bbCode);
        $ipbCode = str_replace("<#EMO_DIR#>", "default", $ipbCode);
        $ipbCode = str_replace("<{POST_SNAPBACK}>", '<span class="SnapBack">»</span>', $ipbCode);

        /**
         * IPB inserts line break markup tags at line breaks.  They need to be removed in code blocks.
         * The original newline/line break should be left intact, so whitespace will be preserved in the pre tag.
         */
        $ipbCode = preg_replace_callback(
            "/\[code\].*?\[\/code\]/is",
            function ($codeBlocks) {
                return str_replace(["<br />"], [""], $codeBlocks[0]);
            },
            $ipbCode
        );

        /**
         * IPB formats some quotes as HTML.  They're converted here for the sake of uniformity in presentation.
         * Attribute order seems to be standard.  Spacing between the opening of the tag and the first attribute is variable.
         */
        $ipbCode = preg_replace_callback(
            '#<blockquote\s+(class="ipsBlockquote" )?data-author="([^"]+)" data-cid="(\d+)" data-time="(\d+)">(.*?)</blockquote>#is',
            function ($blockQuotes) {
                $author = $blockQuotes[2];
                $cid = $blockQuotes[3];
                $time = $blockQuotes[4];
                $quoteContent = $blockQuotes[5];

                // $Time will over as a timestamp. Convert it to a date string.
                $date = date("F j Y, g:i A", $time);

                return "[quote name=\"{$author}\" url=\"{$cid}\" date=\"{$date}\"]{$quoteContent}[/quote]";
            },
            $ipbCode
        );

        return $ipbCode;
    }
}
