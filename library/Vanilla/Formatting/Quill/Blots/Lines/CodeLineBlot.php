<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Formatting\Quill\Blots\Lines;

/**
 * Blot for handling code blocks.
 *
 * Newlines are handled slightly differently here than for regular text because of the `whitespace: pre` that is applied
 * to code blocks. We do not want the newlines to be transformed into breaks.
 */
class CodeLineBlot extends AbstractLineBlot {

    protected $lineBreakText = "\n";

    public function render(): string {
        $extraNewLines = substr_count($this->currentOperation["insert"], "\n");
        return str_repeat("\n", $extraNewLines);
    }

    /**
     * @inheritdoc
     */
    public static function matches(array $operation): bool {
        return static::opAttrsContainKeyWithValue($operation, "codeBlock");
    }

    public function renderLineStart(): string {
        return "";
    }

    public function renderLineEnd(): string {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function getGroupOpeningTag(): string {
        return '<code class="code codeBlock" spellcheck="false">';
    }

    /**
     * @inheritDoc
     */
    public function getGroupClosingTag(): string {
        return "</code>";
    }
}
