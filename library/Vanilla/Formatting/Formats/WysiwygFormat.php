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

    /** @var HtmlSanitizer */
    private $htmlSanitizer;

    /** @var HtmlEnhancer */
    private $htmlEnhancer;

    /** @var HtmlPlainTextConverter */
    private $plainTextConverter;

    /**
     * Constructor for dependency Injection.
     *
     * @param HtmlSanitizer $htmlSanitizer
     * @param HtmlEnhancer $htmlEnhancer
     * @param HtmlPlainTextConverter $plainTextConverter
     */
    public function __construct(
        HtmlSanitizer $htmlSanitizer,
        HtmlEnhancer $htmlEnhancer,
        HtmlPlainTextConverter $plainTextConverter
    ) {
        $this->htmlSanitizer = $htmlSanitizer;
        $this->htmlEnhancer = $htmlEnhancer;
        $this->plainTextConverter = $plainTextConverter;

        parent::__construct($htmlSanitizer, $htmlEnhancer, $plainTextConverter, false);
    }


    /**
     * @inheritdoc
     */
    public function renderHtml(string $content, bool $enhance = true): string {
        $result = $this->htmlSanitizer->filter($content);

        $result = FormatUtil::replaceButProtectCodeBlocks('/\\\r\\\n/', '', $result);

        $result = $this->legacySpoilers($result);

        if ($enhance) {
            $result = $this->htmlEnhancer->enhance($result);
        }

        $result = self::cleanupEmbeds($result);

        return $result;
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
