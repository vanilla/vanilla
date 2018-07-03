<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots\Embeds;

use Vanilla\Quill\BlotGroup;
use Vanilla\Quill\Blots\AbstractBlot;

abstract class AbstractInlineEmbedBlot extends AbstractBlot {

    const ZERO_WIDTH_WHITESPACE = "&#65279;";

    /**
     * Get the wrapping HTML tag name for this blot.
     *
     * @return string
     */
    abstract protected static function getContainerHTMLTag(): string;

    /**
     * Get the key to pull the main content out of the currentBlot.
     *
     * @return string
     */
    abstract protected static function getInsertKey(): string;

    /**
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        return valr(static::getInsertKey(), $operations[0]);
    }

    /**
     * Get the class for the wrapping HTML tag. This will generally not be a
     */
    abstract protected function getContainerHMTLAttributes(): array;

    /**
     * @inheritDoc
     */
    public function __construct(array $currentOperation, array $previousOperation, array $nextOperation) {
        parent::__construct($currentOperation, $previousOperation, $nextOperation);
        $potentialContent = valr(static::getInsertKey(), $this->currentOperation);
        $this->content = is_string($potentialContent) ? $potentialContent : "";
    }

    /**
     * @inheritDoc
     */
    public function render(): string {
        $result = "<" . static::getContainerHTMLTag();

        $attributes = $this->getContainerHMTLAttributes();
        foreach ($attributes as $attrKey => $attr) {
            $result .= " $attrKey=\"$attr\"";
        }

        $sanitizedContent = htmlspecialchars($this->content);
        $result .= ">";
        $result .= $sanitizedContent;
        $result .= "</" . static::getContainerHTMLTag() . ">";

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function shouldClearCurrentGroup(BlotGroup $group): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function hasConsumedNextOp(): bool {
        return false;
    }
}
