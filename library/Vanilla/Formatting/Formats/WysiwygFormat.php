<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Formatting\FormatUtil;
use Vanilla\Formatting\Html\HtmlEnhancer;
use Vanilla\Formatting\Html\HtmlPlainTextConverter;
use Vanilla\Formatting\Html\HtmlSanitizer;
use Vanilla\Formatting\Html\Processor\ZendeskWysiwygProcessor;

/**
 * Class for rendering content of the markdown format.
 */
class WysiwygFormat extends HtmlFormat
{
    const FORMAT_KEY = "wysiwyg";

    const ALT_FORMAT_KEY = "raw";

    /**
     * Constructor for dependency Injection
     *
     * @inheritdoc
     */
    public function __construct(
        HtmlSanitizer $htmlSanitizer,
        HtmlEnhancer $htmlEnhancer,
        HtmlPlainTextConverter $plainTextConverter,
        ZendeskWysiwygProcessor $zendeskWysiwygProcessor
    ) {
        parent::__construct($htmlSanitizer, $htmlEnhancer, $plainTextConverter, false);
        $this->addHtmlProcessor($zendeskWysiwygProcessor);
    }

    /**
     * @inheritdoc
     */
    public function renderHtml(string $content, bool $enhance = true): string
    {
        $result = FormatUtil::replaceButProtectCodeBlocks('/\\\r\\\n/', "", $content);
        return parent::renderHtml($result, $enhance);
    }

    /**
     * Legacy Spoilers don't get applied to WYSIWYG.
     * Stub out the method.
     *
     * @param string $html
     *
     * @return string
     */
    protected function legacySpoilers(string $html): string
    {
        return $html;
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

        $pattern["quote"] =
            '~<div class="QuoteAuthor">\s*<a href="([^"]+?)" class="([^"]+?)" data-userid="(\d+)">' .
            $username .
            "</a>~";
        $replacement["quote"] =
            '<div class="QuoteAuthor"><a href="' .
            $this->anonymizeUrl .
            '" class="$2" data-userid="$3">' .
            $this->anonymizeUsername .
            "</a>";

        return preg_replace($pattern, $replacement, $body);
    }

    /**
     * @inheritDoc
     */
    public function parseAllMentions(string $body): array
    {
        $matches = [];
        $atMention = $this->getNonRichAtMention();
        $urlMention = $this->getUrlPattern();

        $quoteMention =
            '<div class="QuoteAuthor">\s*<a href="[^"]+?" class="[^"]+?" data-userid="\d+">(?<quote_mentions>[^<]+?)</a>';

        $pattern = "~($atMention|$urlMention|$quoteMention)~";
        preg_match_all($pattern, $body, $matches, PREG_UNMATCHED_AS_NULL);

        return $this->normalizeMatches($matches);
    }
}
