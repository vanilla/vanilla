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
 * Class for rendering content of the markdown format.
 */
class WysiwygFormat extends HtmlFormat {

    const FORMAT_KEY = "Wysiwyg";


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
        parent::__construct($htmlSanitizer, $htmlEnhancer, $plainTextConverter, false);
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
