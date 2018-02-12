<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

use Vanilla\Quill\Block;

abstract class AbstractListBlot extends TextBlot {

    /** @var bool */
    private $shouldClearCurrentBlock = false;

    /**
     * Get the type of list.
     *
     * @return string
     */
    abstract protected static function getListType(): string;

    /**
     * @inheritDoc
     */
    public function __construct(array $currentOperation, array $previousOperation, array $nextOperation) {
        parent::__construct($currentOperation, $previousOperation, $nextOperation);
        if (\stringBeginsWith($this->content, "\n")) {
            $this->content = \ltrim($this->content, "\n");
            $this->shouldClearCurrentBlock = true;
        }
    }

    /**
     * @inheritDoc
     */
    public function render(): string {
        $classString = "";
        $indentLevel = valr("attributes.indent", $this->nextOperation);
        if ($indentLevel) {
            $classString = " class=\"ql-indent-$indentLevel\"";
        }

        return "<li$classString>" . parent::render() . "</li>";
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        $found = false;

        foreach($operations as $op) {
            if(valr("attributes.list", $op) === static::getListType()) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * @inheritDoc
     */
    public function shouldClearCurrentBlock(Block $block): bool {
       return $this->shouldClearCurrentBlock;
    }

    /**
     * @inheritDoc
     */
    public function hasConsumedNextOp(): bool {
        return true;
    }
}
