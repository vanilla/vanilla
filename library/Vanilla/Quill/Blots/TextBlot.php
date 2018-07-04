<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

use Vanilla\Quill\Blots\Embeds\EmojiBlot;
use Vanilla\Quill\Formats;
use Vanilla\Quill\BlotGroup;
use Vanilla\Quill\FormattableTextTrait;
use Vanilla\Quill\Renderer;

class TextBlot extends AbstractBlot {

    use FormattableTextTrait;

    /**
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        return is_string(val("insert", $operations[0]));
    }

    /**
     * @inheritDoc
     */
    public function __construct(array $currentOperation, array $previousOperation, array $nextOperation) {
        parent::__construct($currentOperation, $previousOperation, $nextOperation);
        $this->parseFormats($this->currentOperation, $this->previousOperation, $this->nextOperation);

        $insert = val("insert", $this->currentOperation, "");
        $this->content = htmlentities($insert, \ENT_QUOTES);

        if (preg_match("/\\n$/", $this->content)) {
            $this->currentOperation[BlotGroup::BREAK_MARKER] = true;
            $this->content = rtrim($this->content, "\n");
        }
    }

    /**
     * @inheritDoc
     */
    public function render(): string {
        $result = "";
        $result .= $this->renderOpeningFormatTags();
        $result .= $this->createLineBreaks($this->content);
        $result .= $this->renderClosingFormatTags();
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function shouldClearCurrentGroup(BlotGroup $group): bool {
        return array_key_exists(BlotGroup::BREAK_MARKER, $this->currentOperation);
    }

    /**
     * @inheritDoc
     */
    public function hasConsumedNextOp(): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    protected function createLineBreaks(string $input): string {
        if ($this->content === "") {
            return "<br>";
        }

        if (preg_match("/^\\n.+/", $this->content)) {
            return preg_replace("/^\\n/", "<br></p><p>", $input);
        } else {
            return $input;
        }
    }
}
