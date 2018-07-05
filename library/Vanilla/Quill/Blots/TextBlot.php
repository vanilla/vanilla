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

    public function isOwnGroup(): bool {
        return false;
    }

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

        $this->content = $this->currentOperation["insert"] ?? "";
        // Sanitize
        $this->content = htmlspecialchars($this->content);

        // If we still have a trailing newline it signifies the end of the group, even if we don't want to render it.
        // Strip off the newline character place a break marker.
        if (preg_match("/\\n$/", $this->content)) {
            $this->currentOperation[BlotGroup::BREAK_MARKER] = true;
            $this->content = rtrim($this->content, "\n");
        }

        if(stringBeginsWith($this->content, "\n")) {
            $this->currentOperation[BlotGroup::BREAK_MARKER] = true;
            $this->content = \ltrim($this->content, "\n");

        }
    }

    /**
     * @inheritDoc
     */
    public function render(): string {
        $sanitizedContent = $this->content === "" ? "<br>" : htmlentities($this->content, ENT_QUOTES);
        return $this->renderOpeningFormatTags().$sanitizedContent.$this->renderClosingFormatTags();
    }

    protected static function operationsContainKeyWithValue(array $operations, string $lookupKey, $expectedValue = true) {
        $found = false;

        foreach ($operations as $op) {
            $value = valr("attributes.$lookupKey", $op);

            if (
                (is_array($expectedValue) && in_array($value, $expectedValue))
                || $value === $expectedValue
            ) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * @inheritDoc
     */
    public function shouldClearCurrentGroup(BlotGroup $group): bool {
        return $this->isOwnGroup() || array_key_exists(BlotGroup::BREAK_MARKER, $this->currentOperation);
    }

    /**
     * @inheritDoc
     */
    public function hasConsumedNextOp(): bool {
//        return false;
        return $this::matches([$this->nextOperation]) && !$this::matches([$this->currentOperation]);
    }
}
