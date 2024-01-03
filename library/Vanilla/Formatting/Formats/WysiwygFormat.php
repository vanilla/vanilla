<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Formatting\FormatRegexReplacements;
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
    public function renderHtml($content, bool $enhance = true): string
    {
        if ($content instanceof HtmlFormatParsed) {
            $result = $content;
        } else {
            $result = FormatUtil::replaceButProtectCodeBlocks('/\\\r\\\n/', "", $content);
        }

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
        $regex = new FormatRegexReplacements();
        $regex->addReplacement(...$this->getNonRichAtMentionReplacePattern($username, $this->anonymizeUsername));
        $regex->addReplacement(...$this->getUrlReplacementPattern($username, $this->anonymizeUrl));
        $regex->addReplacement(
            sprintf(
                '~<div\s+class="QuoteAuthor">\s*<a\s+href="(.+?)"\s+class="(.+?)"\s+data-userid="(\d+)">%s</a>~',
                preg_quote($username)
            ),
            sprintf(
                '<div class="QuoteAuthor"><a href="%s" class="$2" data-userid="-1">%s</a>',
                $this->anonymizeUrl,
                $this->anonymizeUsername
            )
        );
        $regex->addReplacement(
            "~" . preg_quote(sprintf(t("%s said:"), '<a rel="nofollow">' . $username . "</a>")) . "~",
            sprintf(t("%s said:"), '<a rel="nofollow">' . $this->anonymizeUsername . "</a>")
        );

        return $regex->replace($body);
    }

    /**
     * @inheritDoc
     */
    public function parseAllMentions($body): array
    {
        if ($body instanceof HtmlFormatParsed) {
            $body = $body->getRawHtml();
        }

        return $this->getNonRichMentions($body, [
            '<div\s+class="QuoteAuthor">\s*<a\s+href=".+?"\s+class=".+?"\s+data-userid="\d+">(.+?)</a>',
            sprintf(t("%s said:"), '<a rel="nofollow">(.+?)</a>'),
        ]);
    }
}
