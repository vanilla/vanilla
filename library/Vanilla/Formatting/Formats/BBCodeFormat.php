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
class BBCodeFormat extends HtmlFormat
{
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
    public function renderHtml($content, bool $enhance = true): string
    {
        if ($content instanceof HtmlFormatParsed) {
            return $content->getProcessedHtml();
        } else {
            $rendered = $this->bbcodeParser->format($content);
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
            $rendered = $this->bbcodeParser->format($content);
        }
        return parent::renderQuote($rendered);
    }

    /**
     * @inheritdoc
     */
    public function removeUserPII(string $username, string $body): string
    {
        [$pattern["atMention"], $replacement["atMention"]] = $this->getNonRichAtMentionReplacePattern(
            $username,
            $this->anonymizeUsername
        );

        [$pattern["url"], $replacement["url"]] = $this->getUrlReplacementPattern($username, $this->anonymizeUrl);

        $pattern["quote"] = "~quote=\"$username;~";
        $replacement["quote"] = "quote=\"$this->anonymizeUsername;";

        $body = preg_replace($pattern, $replacement, $body);
        return $body;
    }

    /**
     * @inheritDoc
     */
    public function parseAllMentions($body): array
    {
        $matches = [];
        $atMention = $this->getNonRichAtMention();
        $urlMention = $this->getUrlPattern();
        $quoteMention = '\[(quote|QUOTE)="?(?<quote_mentions>.+?);.*?"?\]';

        $pattern = "~($atMention|$urlMention|$quoteMention)~";
        preg_match_all($pattern, $body, $matches, PREG_UNMATCHED_AS_NULL);

        return $this->normalizeMatches($matches);
    }
}
