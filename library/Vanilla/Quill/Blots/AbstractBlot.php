<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

use Vanilla\Quill\Block;

/**
 * Class AbstractBlot
 *
 * @package Vanilla\Quill\Blot
 */
abstract class AbstractBlot {

    const NEWLINE_TYPE_START = "start";
    const NEWLINE_TYPE_ONLY = "only";

    /** @var string */
    protected $currentBlockTagName;

    /** @var string */
    protected $classnames;

    /** @var string */
    protected $content = "";

    /** @var array  */
    protected $currentOperation = [];

    /** @var array  */
    protected $previousOperation = [];

    /** @var array  */
    protected $nextOperation = [];

    /** @var Format */
    protected $formats = [];

    /**
     * Determine if the operations match this Blot type.
     *
     * @param array[] $operations An array of operations to check.
     *
     * @return bool
     */
    abstract public static function matches(array $operations): bool;

    abstract public function render(): string;

    abstract public function shouldClearCurrentBlock(Block $block): bool;

    public function getNewLineType(): string {
        if ($this->content === "\n") {
            return static::NEWLINE_TYPE_ONLY;
        }

        if (\preg_match("/^\\n/", $this->content)) {
            return static::NEWLINE_TYPE_START;
        }

        return "";
    }

    protected function trimNewLines() {
        return;
    }

    /**
     * Determine whether or not this blot uses both current and next operation.
     *
     * @return bool
     */
    abstract public function hasConsumedNextOp(): bool;

    public function isEmpty(): bool {
        if ($this->content === "\n" || $this->content === "") {
            return true;
        }

        return false;
    }

    /**
     * Create a blot.
     *
     * @param array $currentOperation The current operation.
     * @param array $previousOperation The next operation. Used to determine closing tags.
     * @param array $nextOperation The previous operation. Used to determine opening tags.
     * @param int $opIndex
     */
    public function __construct(array $currentOperation, array $previousOperation, array $nextOperation) {
        $this->previousOperation = $previousOperation;
        $this->currentOperation = $currentOperation;
        $this->nextOperation = $nextOperation;
        $this->trimNewLines();
    }
}
