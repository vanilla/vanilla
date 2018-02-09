<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill;

/**
 * Quill Operations still need one more pass before they are easily renderable.
 */
class BlotFactory {

    /** @var Blot[] */
    private $blocks = [];

    /** @var int */
    private $currentIndex = 0;

    /** @var int  */
    private $blockStartIndex = 0;

    /** @var string */
    private $currentListType = Operation::LIST_TYPE_NONE;

    /** @var Operation[]  */
    private $operations = [];

    /**
     * QuillBlock constructor.
     *
     * @param Operation[] $operations The operations to build blocks from.
     */
    public function __construct(array $operations) {
        $this->operations = $operations;
        $this->operations[] = new Operation([]);

        foreach ($operations as $currentIndex => $operation) {
            if ($this->blockStartIndex < 0) {
                $this->blockStartIndex = $currentIndex;
            }
            $this->currentIndex = $currentIndex;
            $this->parseNewLine($operation);
            $this->parseBackProperties($operation);
        }

        // Clear the last block.
        $this->currentIndex = count($operations);

        $this->clearBlock(false);
    }

    /**
     * @return Blot[]
     */
    public function getBlocks(): array {
        return $this->blocks;
    }

    /**
     * Reset the properties we know about the current block.
     *
     * @param int $index - The block index to set thin
     */
    private function resetBlock($index = -1) {
        // Add the current block the blocks array.

        $this->blockStartIndex = $index;
        $this->currentListType = Operation::LIST_TYPE_NONE;
    }

    /**
     * Take the currently block currently being built and apply it to the stack of blocks.
     *
     * @param bool $includeSelf Whether or not the newly created block should contain the current operation.
     */
    public function clearBlock($includeSelf = true) {
        if ($this->blockStartIndex < 0) {
            return;
        }
        $length = $this->currentIndex - $this->blockStartIndex;
        if($includeSelf) {
            $length += 1;
        }
        $this->blocks[] = new Blot(array_slice($this->operations, $this->blockStartIndex, $length));
    }

    /**
     * Use the newline type to clear blocks.
     *
     * @param Operation $operation - The operation to check.
     */
    private function parseNewLine(Operation &$operation) {
        switch ($operation->getNewlineType()) {
            case Operation::NEWLINE_TYPE_ATTRIBUTOR:
                $isListItem = $operation->getListType() !== Operation::LIST_TYPE_NONE;
                $isStartingNewListType = $this->currentListType === Operation::LIST_TYPE_NONE && $isListItem;
                $isContinuingActiveListType = $this->currentListType !== Operation::LIST_TYPE_NONE
                    && $operation->getListType() === $this->currentListType;
                if ($isStartingNewListType || $isContinuingActiveListType) {
                    return;
                }

                // The previous block is complete including this operation.
                $this->clearBlock();
                $this->resetBlock();
                break;
            case Operation::NEWLINE_TYPE_ONLY:
                // The previous block is complete with the last operation. This operation is it's own block.
                $this->clearBlock(false);
                $this->resetBlock();
                break;
            case Operation::NEWLINE_TYPE_END:
                // Close the block including this item.
                $this->clearBlock();
                $this->resetBlock();

                if ($this->currentListType !== Operation::LIST_TYPE_NONE) {
                    // Add a newline block
                    $this->blocks[] = self::generateNewLineBlock();
                }
                break;
            case Operation::NEWLINE_TYPE_START:
                // The previous block is complete before this operation. Close the old one.
                $this->clearBlock(false);
                $this->resetBlock();

                $nextOp = $this->operations[$this->currentIndex + 1];
                $isQuoteText = $nextOp && $nextOp->getAttribute("blockquote");

                // Don't add the newline block for quotes.
                if (!$isQuoteText) {
                    $this->blocks[] = self::generateNewLineBlock();
                }

                // Replace the newline attribute and try again
                $operation->setNewlineType(Operation::NEWLINE_TYPE_NONE);
                $this->resetBlock($this->currentIndex);
                break;
            case Operation::NEWLINE_TYPE_NONE:
                return;
        }
    }

    /**
     * Apply the properties of the next operation to the current one if applicable. It is pretty dumb that this needs
     * to be done at all.
     *
     * @param Operation $currentOperation The operation to apply properties too.
     */
    public function parseBackProperties(Operation $currentOperation) {
        $nextOp = array_key_exists($this->currentIndex + 1, $this->operations)
            ? $this->operations[$this->currentIndex + 1]
            : false;

        if ($nextOp) {
            $nextListType = $nextOp->getListType();

            $currentOperation->setListType($nextListType);

            $nextListTypeIsNotNone = $nextListType !== Operation::LIST_TYPE_NONE;
            $nextListTypeWillChange = $nextListType !== $this->currentListType;
            if ($nextListTypeIsNotNone && $nextListTypeWillChange) {
                // The previous block is complete with the last operation. This operation is it's own block.
                $this->clearBlock(false);
                $this->resetBlock($this->currentIndex);
                $this->currentListType = $nextListType;
            }
        }

        if ($nextOp && $nextOp->getIndent()) {
            $currentOperation->setIndent($nextOp->getIndent());
        }
    }

    /**
     * Generate a block with only a newline op.
     *
     * @return Blot
     */
    private static function generateNewLineBlock(): Blot {
        return new Blot([new Operation(["insert" => "\n"])]);
    }
}
