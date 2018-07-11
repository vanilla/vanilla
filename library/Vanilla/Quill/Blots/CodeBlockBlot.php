<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

/**
 * Blot for handling code blocks.
 *
 * Newlines are handled slightly differently here than for regular text because of the `whitespace: pre` that is applied
 * to code blocks. We do not want the newlines to be transformed into breaks.
 */
class CodeBlockBlot extends TextBlot {

    /**
     * @inheritdoc
     */
    public static function matches(array $operations): bool {
        return static::opAttrsContainKeyWithValue($operations, "code-block");
    }

    /**
     * @inheritDoc
     */
    public function render(): string {
        $result = htmlspecialchars($this->content);

        // Add newlines which live in the next operation.
        if ($this->nextOperation) {
            $sanitizedNewlines = htmlspecialchars($this->nextOperation["insert"]);
            $result .= $sanitizedNewlines;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getGroupOpeningTag(): string {
        return '<code class="code-block code isBlock" spellcheck="false">';
    }

    /**
     * @inheritDoc
     */
    public function getGroupClosingTag(): string {
        return "</code>";
    }
}
