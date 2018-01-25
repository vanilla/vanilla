<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
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

    /** @var QuillOperation[] */
    private $operations = [];

    /** @var string */
    private $listType = QuillOperation::LIST_TYPE_NONE;

    /** @var int */
    private $headerLevel = 0;

    /** @var int */
    private $indentLevel = 0;

    /** @var string  */
    private $blockType = self::TYPE_PARAGRAPH;

    /**
     * QuillBlock constructor.
     *
     * @param QuillOperation[] $operations The operations to build blocks from.
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
            foreach ($this->operations as $op) {
                if ($op->getIndent() > 0) {
                    $this->indentLevel = $op->getIndent();
                }
            }
            $this->blockType = self::TYPE_PARAGRAPH;
        }
    }

    /**
     * @return string
     */
    public function getBlockType(): string {
        return $this->blockType;
    }

    /**
     * @return QuillOperation[]
     */
    public function getOperations(): array {
        return $this->operations;
    }

    /**
     * @return string
     */
    public function getListType(): string {
        return $this->listType;
    }

    /**
     * @return int
     */
    public function getHeaderLevel(): int {
        return $this->headerLevel;
    }

    /**
     * @return int;
     */
    public function getIndentLevel(): int {
        return $this->indentLevel;
    }

    /**
     * Extract a single attribute from this block's operations.
     *
     * @param string $attributeName - The attribute to extract.
     */
    private function extractAttribute(string $attributeName) {
        $result = false;

        foreach ($this->operations as $op) {
            if ($op->getAttribute($attributeName)) {
                $result = $op->getAttribute($attributeName);
                break;
            }
        }

        return $result;
    }
}
