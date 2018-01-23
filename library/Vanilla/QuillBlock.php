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
class QuillBlock {
    // Block Types
    const TYPE_PARAGRAPH = "paragraph";
    const TYPE_CODE = "code";
    const TYPE_BLOCKQUOTE = "blockquote";
    const TYPE_HEADER = "header";
    const TYPE_LIST = "list";

    // List types
    const LIST_TYPE_BULLET = "bullet";
    const LIST_TYPE_ORDERED = "ordered";
    const LIST_TYPE_NONE = "none";

    private $allowedBlockTypes = [
        self::TYPE_BLOCKQUOTE,
        self::TYPE_CODE,
        self::TYPE_HEADER,
        self::TYPE_PARAGRAPH,
        self::TYPE_LIST,
    ];

    /** @var QuillOperation[] */
    public $operations = [];

    /** @var string */
    public $listType = self::LIST_TYPE_NONE;

    /** @var string */
    public $headerLevel = 0;

    /**
     * QuillBlock constructor.
     *
     * @param QuillOperation[] $operations The operations to build blocks from.
     * @param string $blockType
     */
    public function __construct(array $operations) {
        $this->operations = $operations;

        // Get Block Type
        if ($this->extractAttribute("code-block")) {
            $this->blockType = self::TYPE_CODE;
        } elseif ($this->extractAttribute("list")) {
            $this->blockType = self::TYPE_LIST;
            $this->listType = $this->extractAttribute("list");
        } elseif ($this->extractAttribute("header")) {
            $this->blockType = self::TYPE_HEADER;
            $this->headerLevel = $this->extractAttribute("header");
        } elseif ($this->extractAttribute("blockquote")) {
            $this->blockType = self::TYPE_BLOCKQUOTE;
        } else {
            $this->blockType = self::TYPE_PARAGRAPH;
        }
    }

    /**
     * Extract a single attribute from this block's operations.
     *
     * @param string $attributeName - The attribute to extract.
     */
    private function extractAttribute(string $attributeName) {
        $result = false;

        foreach ($this->operations as $op) {
            if (array_key_exists($attributeName, $op->attributes)) {
                $result = $op->attributes[$attributeName];
            }
        }

        return $result;
    }

    /**
     * Generate multiple blocks from a list of operations.
     *
     * @param QuillOperation[] $operations The operations to build blocks from.
     *
     * @returns QuillBlock[]
     */
    public static function generateFromOps(array $operations) {
        /** @var QuillBlock[] $blocks */
        $blocks = [];

        $blockStartIndex = -1;
        $blockListType = null;
        foreach($operations as $currentIndex => $operation) {
            // We are starting a new block.
            if ($blockStartIndex === -1) {
                $blockStartIndex = $currentIndex;
            }

            // Clear the block when list type changes.
            $operationListType = val("list", $operation->attributes, self::LIST_TYPE_NONE);
            if (!$blockListType || ($operation->list && $operationListType !== $blockListType)) {
                // The previous block is closed with this operation. Close the old one.
                $length = $currentIndex - $blockStartIndex + 1;
                $blocks[] = new QuillBlock(array_slice($operations, $blockStartIndex, $length));
                $blockStartIndex = -1;
                $blockListType = $operationListType;
            }

            if ($operation->content === "" && $operation->list) {
                // List information comes in the next insert after it's text. This is really dumb but whatever.
                // Update the previous insert with the list info.
                $operations[$currentIndex - 1]->list = $operation->list;
                $operations[$currentIndex - 1]->indent = $operation->indent;
                $operations[$currentIndex - 1]->attributes["list"] = $operationListType;
                continue;
            }

            if ($operation->newline === QuillOperation::NEWLINE_TYPE_END) {
                // The block is complete including this operation. Include this op and close the current block.
                $length = $currentIndex - $blockStartIndex + 1;
                $blocks[] = new QuillBlock(array_slice($operations, $blockStartIndex, $length));
                $blockStartIndex = -1;
                continue;
            } elseif ($operation->newline === QuillOperation::NEWLINE_TYPE_START) {
                // The previous block is complete before this operation. Close the old one.
                $length = $currentIndex - $blockStartIndex;
                $blocks[] = new QuillBlock(array_slice($operations, $blockStartIndex, $length));

                if (!$operation->list) {
                    // Clone off a newline op.
                    $newlineOp = clone $operation;
                    $newlineOp->content = "";

                    // Create a new block with just the newline.
                    $blocks[] = new QuillBlock([$operation]);
                }

                // Adjust the newline status of the current block before continuing.
                $operation->newline = QuillOperation::NEWLINE_TYPE_NONE;
                $blockStartIndex = $currentIndex;
            } elseif ($operation->newline === QuillOperation::NEWLINE_TYPE_ONLY) {
                // The previous block is complete before this operation. Close the old one.
                $length = $currentIndex - $blockStartIndex;
                $blocks[] = new QuillBlock(array_slice($operations, $blockStartIndex, $length));

                // Increment the counter by one. We don't want to include this item twice!
                $blockStartIndex = $currentIndex + 1;
                // Create a new block with just the newline.
                $blocks[] = new QuillBlock([$operation]);
            }
        }
        return $blocks;
    }

    private function validateOperations() {
        // Ensure the same blocktype

//        $operationIsInline
    }

    /**
     * Merge adjacent list blocks of the same type together.
     *
     * @param QuillBlock[] $blocks - The blocks to look through.
     *
     * @return array[]
     */
    private function mergeListBlocks(array $blocks) {
        $results = [];

        // Store a block in progress.
        $buildingBlock = false;

        foreach ($blocks as $block) {
            $isList = $block["blockType"] === self::TYPE_LIST;
            $isSameTypeAsPreviousBlock = $buildingBlock["listType"] === val("listType", $block);
            $updateExistingBuildingBlock = $isList && $isSameTypeAsPreviousBlock && $buildingBlock;

            // Add to existing
            if ($updateExistingBuildingBlock) {
                foreach($block["operations"] as $op) {
                    $buildingBlock["operations"][] = $op;
                }
            } else {
                // Clear the existing buildingBlock.
                if ($buildingBlock) {
                    $results[] = $buildingBlock;
                    $buildingBlock = false;
                }

                // Start the building block over.
                if ($isList) {
                    $buildingBlock = $block;
                    // Ignore the building block.
                } else {
                    $results[] = $block;
                }
            }
        }

        // Clear the block one last time.
        if ($buildingBlock) {
            $results[] = $buildingBlock;
        }

        return $results;
    }

}
