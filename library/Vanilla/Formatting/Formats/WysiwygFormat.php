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

/**
 * Class for rendering content of the markdown format.
 */
class WysiwygFormat extends HtmlFormat {

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
        HtmlPlainTextConverter $plainTextConverter
    ) {
        parent::__construct($htmlSanitizer, $htmlEnhancer, $plainTextConverter, false);
    }

    /**
     * @inheritdoc
     */
    public function renderHtml(string $content, bool $enhance = true): string {
        $result = FormatUtil::replaceButProtectCodeBlocks('/\\\r\\\n/', '', $content);
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
    protected function legacySpoilers(string $html): string {
        return $html;
    }
}
