<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

/**
 * Quill Operations still need one more pass before they are easily renderable.
 */
class QuillBlockFactory {

    /** @var QuillBlock[] */
    public $blocks = [];

    private $currentIndex = 0;
    private $blockStartIndex = 0;
    private $currentListType = QuillBlock::LIST_TYPE_NONE;

    /** @var QuillOperation[]  */
    private $operations = [];

    /**
     * QuillBlock constructor.
     *
     * @param QuillOperation[] $operations The operations to build blocks from.
     * @param string $blockType
     */
    public function __construct(array $operations) {
        $this->operations = $operations;
        $this->operations[] = new QuillOperation([]);

        foreach($operations as $currentIndex => $operation) {
            if ($this->blockStartIndex < 0) {
                $this->blockStartIndex = $currentIndex;
            }
            $this->currentIndex = $currentIndex;
            $this->parseNewLine($operation);
            $this->parseBackProperties($operation);
        }
    }

    /**
     * Reset the properties we know about the current block.
     *
     * @param int $index
     * @return bool
     */
    private function resetBlock($index = -1) {
        // Add the current block the blocks array.

        $this->blockStartIndex = $index;
        $this->currentListType = QuillBlock::LIST_TYPE_NONE;
    }

    /**
     * Take the currently block currently being built and apply it to the stack of blocks.
     *
     * @param bool $includeSelf Whether or not the newly created block should contain the current operation.
     */
    public function clearBlock($includeSelf = true) {
        $length = $this->currentIndex - $this->blockStartIndex;
        if($includeSelf) {
            $length += 1;
        }
        $this->blocks[] = new QuillBlock(array_slice($this->operations, $this->blockStartIndex, $length));
    }

    /**
     * Use the newline type to clear blocks.
     *
     * @param QuillOperation $operation - The operation to check.
     */
    private function parseNewLine(QuillOperation &$operation) {
        switch ($operation->newline) {
            case QuillOperation::NEWLINE_TYPE_ATTRIBUTOR:
                // The previous block is complete including this operation.
                if ($operation->list) {
                    return;
                }
                $this->clearBlock();
                $this->resetBlock();
                break;
            case QuillOperation::NEWLINE_TYPE_ONLY:
                // The previous block is complete with the last operation. This operation is it's own block.
                $this->clearBlock(false);
                $this->resetBlock();
                break;
            case QuillOperation::NEWLINE_TYPE_END:
                // Close the block including this item.
                $this->clearBlock();
                $this->resetBlock();
                break;
            case QuillOperation::NEWLINE_TYPE_START:
                // The previous block is complete before this operation. Close the old one.
                $this->clearBlock(false);
                $this->resetBlock();

                // Don't add the newline block for quotes.
                // Clone off a newline op.
                $newlineOp = clone $operation;
                $newlineOp->content = "\n";
                $newlineOp->newline = QuillOperation::NEWLINE_TYPE_ONLY;

                // Create a new block with just the newline.
                $this->blocks[] = new QuillBlock([$newlineOp]);

                // Strip the newline off the of the block.
                $operation->content = preg_replace("/^\\n/", "", $operation->content);
                $operation->newline = QuillOperation::NEWLINE_TYPE_NONE;
                $this->resetBlock($this->currentIndex);
                break;
            case QuillOperation::NEWLINE_TYPE_NONE:
                return;
        }
    }

    /**
     * Apply the properties of the next operation to the current one if applicable. It is pretty dumb that this needs
     * to be done at all.
     *
     * @param number $index
     */
    public function parseBackProperties(QuillOperation &$currentOperation) {
        $nextOp = array_key_exists($this->currentIndex + 1, $this->operations)
            ? $this->operations[$this->currentIndex + 1]
            : false;

        if ($nextOp && $nextOp->list) {
            $listType =  $nextOp->attributes["list"];

            $currentOperation->list = true;
            $currentOperation->attributes["list"] = $listType;

            if ($listType !== $this->currentListType) {
                // The previous block is complete with the last operation. This operation is it's own block.
                $this->clearBlock(false);
                $this->resetBlock($this->currentIndex);
                $this->currentListType = $listType;
            }
        }

        if ($nextOp && $nextOp->indent) {
            $currentOperation->indent = $nextOp->indent;
        }
    }
}
