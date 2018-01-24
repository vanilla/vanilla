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

    private $blockInitialized = false;
    private $blockListType;
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
            $this->parseNewLine($currentIndex, $operation);
            $this->parseBackProperties($currentIndex, $operation);
        }

//        $length = count($operations) - $this->blockStartIndex - 1;
//        echo $this->blockStartIndex
//        print $length;

//        $this->blocks[] = new QuillBlock(array_slice($this->operations, $this->blockStartIndex, $length));
    }

    private function clearBlock($index = -1) {
        $this->blockStartIndex = $index;
        $this->currentListType = QuillBlock::LIST_TYPE_NONE;
        return false;
    }

    private function parseNewLine($currentIndex, QuillOperation &$operation) {
        switch ($operation->newline) {
            case QuillOperation::NEWLINE_TYPE_ATTRIBUTOR:
                // The previous block is complete including this operation.
                if ($operation->list) {
                    return;
                }
                $length = $currentIndex - $this->blockStartIndex + 1;

                $this->blocks[] = new QuillBlock(array_slice($this->operations, $this->blockStartIndex, $length));
                $this->clearBlock();
                break;
            case QuillOperation::NEWLINE_TYPE_ONLY:
                // The previous block is complete with the last operation. This operation is it's own block.
                $length = $currentIndex - $this->blockStartIndex;

                $this->blocks[] = new QuillBlock(array_slice($this->operations, $this->blockStartIndex, $length));
                $this->clearBlock();
                break;
            case QuillOperation::NEWLINE_TYPE_END:
                // Close the block including this item.
                $length = $currentIndex - $this->blockStartIndex + 1;

                $this->blocks[] = new QuillBlock(array_slice($this->operations, $this->blockStartIndex, $length));
                $this->clearBlock();
                break;
            case QuillOperation::NEWLINE_TYPE_START:
                // The previous block is complete before this operation. Close the old one.
                $length = $currentIndex - $this->blockStartIndex;

                $this->blocks[] = new QuillBlock(array_slice($this->operations, $this->blockStartIndex, $length));
                $this->clearBlock();

//                // Clone off a newline op.
//                $newlineOp = clone $operation;
//                $newlineOp->content = "\n";
//                $newlineOp->newline = QuillOperation::NEWLINE_TYPE_ONLY;
//
//                // Create a new block with just the newline.
//                $this->blocks[] = new QuillBlock([$newlineOp]);

                // Strip the newline off the of the block.
                $operation->content = preg_replace("/^\\n/", "", $operation->content);
                $operation->newline = QuillOperation::NEWLINE_TYPE_NONE;
                $this->clearBlock($currentIndex);
                break;
            case QuillOperation::NEWLINE_TYPE_NONE:
                return;
        }
    }

    /**
     * @param number $index We need to look at the next operation to determine if the current item is a list.
     */
    public function parseBackProperties($currentIndex, QuillOperation &$currentOperation) {
        $nextOp = array_key_exists($currentIndex + 1, $this->operations) ? $this->operations[$currentIndex + 1] : false;
        if ($nextOp && $nextOp->list) {
            $listType =  $nextOp->attributes["list"];

            $currentOperation->list = true;
            $currentOperation->attributes["list"] = $listType;

            if ($listType !== $this->currentListType) {
                // The previous block is complete with the last operation. This operation is it's own block.
                $length = $currentIndex - $this->blockStartIndex;

                $this->blocks[] = new QuillBlock(array_slice($this->operations, $this->blockStartIndex, $length));
                $this->clearBlock($currentIndex);
                $this->currentListType = $listType;
            }
        }

        if ($nextOp && $nextOp->indent) {
            $currentOperation->indent = $nextOp->indent;
        }
    }
}
