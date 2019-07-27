<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Formatting\Html\HtmlEnhancer;

/**
 * Class for rendering content of the markdown format.
 */
class TextExFormat extends TextFormat {

    const FORMAT_KEY = "TextEx";

    /** @var HtmlEnhancer */
    private $htmlEnhancer;

    /**
     * DI.
     *
     * @param HtmlEnhancer $htmlEnhancer
     */
    public function __construct(HtmlEnhancer $htmlEnhancer) {
        $this->htmlEnhancer = $htmlEnhancer;
    }


    /**
     * @inheritdoc
     */
    public function renderHTML(string $content): string {
        $result = parent::renderHTML($content);
        $result = $this->htmlEnhancer->enhance($result);
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function parseHeadings(string $content): array {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseMentions(string $content): array {
        return [];
    }
}
