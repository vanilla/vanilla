<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Blots\Lines;

/**
 * Blot for handling code line terminators.
 *
 * Newlines are handled slightly differently here than for regular text because of the `whitespace: pre` that is applied
 * to code blocks. We do not want the newlines to be transformed into breaks.
 */
class CodeLineTerminatorBlot extends AbstractLineTerminatorBlot {

    protected $lineBreakText = "\n";

    public function render(): string {
        $extraNewLines = substr_count($this->currentOperation["insert"], "\n");
        return str_repeat("\n", $extraNewLines);
    }

    /**
     * @inheritdoc
     */
    public static function matches(array $operation): bool {
        return static::opAttrsContainKeyWithValue($operation, "codeBlock")
            || static::opAttrsContainKeyWithValue($operation, "code-block");
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
        return '<pre class="code codeBlock" spellcheck="false" tabindex="0">';
    }

    /**
     * @inheritDoc
     */
    public function getGroupClosingTag(): string {
        return "</pre>";
    }
}
