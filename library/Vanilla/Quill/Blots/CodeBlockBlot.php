<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

class CodeBlockBlot extends AbstractBlockBlot {
    /**
     * @inheritDoc
     */
    public function isOwnGroup(): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    protected static function getAttributeKey(): string {
        return "code-block";
    }

    /**
     * @inheritDoc
     */
    public function render(): string {
        $result = $this->content;

        // Add newlines which live in the next operation.
        if ($this->nextOperation) {
            $sanizizedNextInsert = htmlspecialchars($this->nextOperation["insert"]);
            $result .= $sanizizedNextInsert;
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
