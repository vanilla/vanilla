<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

/**
 * A PHP quill.js renderer for Vanilla.
 */
class QuillRenderer {

    /**
     * Render an HTML string from a quill string delta.
     *
     * @param string $deltaString - A Quill insert-only delta. https://quilljs.com/docs/delta/.
     */
    public function renderDelta(string $deltaString) {
        $html = "";
        $blocks = $this->makeOperations($deltaString);

        foreach($blocks as $block) {
            $html .= $this->renderBlock($block);
        }

        return $html;
    }

    /**
     * Make the quill operations array.
     *
     * @param string $deltaString - A Quill insert-only delta. https://quilljs.com/docs/delta/.
     *
     * @returns QuillOperation[]
     */
    private function makeOperations(string $deltaString): array {
        $delta = json_decode($deltaString, true);

        /** @var QuillOperation[] $operations */
        $operations = [];

        foreach($delta as $opArray) {
            $operations[] = new QuillOperation($opArray);
        }

        $blocks = [];

        $currentIndex = 0;
        foreach($operations as $key => $operation) {
            $newlineRegexp = "/\\n$/";

            // Join together everything from the last newline ending operation to the current one in one block.
            if (preg_match($newlineRegexp, $operation->content)) {
                $block = [
                    "blockType" => $operation->blockType,
                    "indent" => $operation->indent,
                    "headerLevel" => $operation->headerLevel,
                    "listType" => $operation->listType,
                    "operations" => [],
                ];

                for ($i = $currentIndex; $i < $key; $i++) {
                    $block["operations"][] = $operations[$i];
                }

                $blocks[] = $block;
                $currentIndex = $key + 1;
            }
        }

        $blocks = $this->mergeListBlocks($blocks);

        return $blocks;
    }

    /**
     * Merge adjacent list blocks of the same type together.
     *
     * @param array[] $blocks - The blocks to look through.
     *
     * @return array[]
     */
    private function mergeListBlocks(array $blocks) {
        $results = [];

        // Store a block in progress.
        $buildingBlock = false;

        foreach ($blocks as $block) {
            $isList = $block["blockType"] === QuillOperation::BLOCK_TYPE_LIST;
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

    /**
     * Render an block element.
     *
     * @param array $block The block of operations to render.
     */
    private function renderBlock(array $block) {
        switch($block["blockType"]) {
            case QuillOperation::BLOCK_TYPE_PARAGRAPH:
                $containerTag = "p";
                break;
            case QuillOperation::BLOCK_TYPE_BLOCKQUOTE:
                $containerTag = "blockquote";
                break;
            case QuillOperation::BLOCK_TYPE_HEADER:
                $containerTag = "h" . $block["headerLevel"];
                break;
            case QuillOperation::BLOCK_TYPE_LIST:
                $containerTag = $block["listType"] === QuillOperation::LIST_TYPE_BULLET ? "ul" : "ol";
                break;
            case QuillOperation::BLOCK_TYPE_CODE:
                $containerTag = "pre";
                break;
            default:
                return;
        }

        $result = "<$containerTag>";

        foreach($block["operations"] as $op) {
            $result .= $this->renderInlineElements($op);
        }

        $result .= "</$containerTag>";
        return $result;
    }

    /**
     * Render a string type operation
     *
     * @param QuillOperation $operation
     */
    private function renderInlineElements(QuillOperation $operation) {
        $tags = [];

        if ($operation->listType) {
            $tags[] = "li";
        }

        if ($operation->bold) {
            $tags[] = "strong";
        }

        if ($operation->italic) {
            $tags[] = "em";
        }

        if ($operation->strike) {
            $tags[] = "s";
        }

        $beforeTags = [];
        $afterTags = [];
        foreach ($tags as $tag) {
            array_push($beforeTags, "<$tag>");
            array_unshift($afterTags, "</$tag>");
        }

        return implode("", $beforeTags) . $operation->content . implode("", $afterTags);
    }

    /**
     * Render an image type operation
     *
     * @param QuillOperation $operation
     */
    private function renderImageInsert(QuillOperation $operation) {
        return "<p>".$operation->content."</p>";
    }
}
