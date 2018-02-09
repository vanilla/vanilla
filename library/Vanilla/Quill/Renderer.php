<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill;

/**
 * A PHP quill.js renderer for Vanilla.
 */
class Renderer {

    /**
     * Render an HTML string from a quill string delta.
     *
     * @param string $deltaString - A Quill insert-only delta. https://quilljs.com/docs/delta/.
     *
     * @return string;
     */
    public function renderDelta(string $deltaString): string {
        $html = "";
        $blocks = $this->makeBlocks($deltaString);

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
     * @returns Blot[]
     */
    private function makeBlocks(string $deltaString): array {
        $delta = json_decode($deltaString, true);

        /** @var Operation[] $operations */
        $operations = [];

        foreach($delta as $opArray) {
            $operations[] = new Operation($opArray);
        }
        $blockFactory = new BlotFactory($operations);

        return $blockFactory->getBlocks();
    }

    /**
     * Render an block element.
     *
     * @param Blot $block The block of operations to render.
     *
     * @return string
     */
    private function renderBlock(Blot $block): string {
        $attributes = [];
        $addNewLine = false;
        $result = "";

        // Don't render no-ops
        if (count($block->getOperations()) < 1) {
            return "";
        }

        switch($block->getBlockType()) {
            case Blot::TYPE_PARAGRAPH:
                $containerTag = "p";

                foreach ($block->getOperations() as $op) {
                    // Replace only a newline with just a break.
                    $op->setContent(preg_replace("/^\\n$/", "<br>", $op->getContent()));
                    // Replace 2 or more newlines with an opening and closing <p> tags and a <br> tag.
                    $op->setContent(preg_replace("/[\\n]{2,}/", "</p><p><br></p><p>", $op->getContent()));
                    // Replace all newlines with opening and closing <p> tags.
                    $op->setContent(str_replace("\n", "</p><p>", $op->getContent()));
                }

                if ($block->getIndentLevel() > 0) {
                    $attributes["class"] = 'ql-indent-'.$block->getIndentLevel();
                }
                break;
            case Blot::TYPE_BLOCKQUOTE:
                $containerTag = "blockquote";
                break;
            case Blot::TYPE_HEADER:
                $containerTag = "h" . $block->getHeaderLevel();
                break;
            case Blot::TYPE_LIST:
                $containerTag = $block->getListType() === Operation::LIST_TYPE_BULLET ? "ul" : "ol";
                break;
            case Blot::TYPE_CODE:
                $containerTag = "pre";
                $attributes = [
                    "class" => "ql-syntax",
                    "spellcheck" => "false",
                ];
                $addNewLine = true;
                break;
            default:
                return "";
        }

        $result .= "<$containerTag";
        foreach ($attributes as $attrKey => $attrValue) {
            $result .= " $attrKey=\"$attrValue\"";
        }
        $result .= ">";

        foreach($block->getOperations() as $key => $op) {
            $result .= $this->renderOperation($op);
        }

        if ($addNewLine) {
            $result .= "\n";
        }

        $result .= "</$containerTag>";
        return $result;
    }

    /**
     * Render an operation
     *
     * @param Operation $operation
     *
     * @return string
     */
    private function renderOperation(Operation $operation): string {
        // Don't render ops without content.
        if ($operation->getContent() === "") {
            return "";
        }

        $tags = [];

        if ($operation->getListType() !== Operation::LIST_TYPE_NONE) {
            $listTag = ["name" => "li"];
            $indent = $operation->getIndent();
            if ($indent > 0) {
                $listTag["attributes"] = [
                    "class" => "ql-indent-$indent",
                ];
            }
            $tags[] = $listTag;
        }

        $link = $operation->getAttribute("link");
        if ($link) {
            $tags[] = [
                "name" => "a",
                "attributes" => [
                    "href" => $link,
                    "target" => "_blank"
                ],
            ];
        }

        if ($operation->getAttribute("bold")) {
            $tags[] = ["name" => "strong"];
        }

        if ($operation->getAttribute("italic")) {
            $tags[] = ["name" => "em"];
        }

        if ($operation->getAttribute("strike")) {
            $tags[] = ["name" => "s"];
        }

        $beforeTags = [];
        $afterTags = [];
        foreach ($tags as $tag) {
            $openingTag = "<{$tag['name']}";

            if (val("attributes", $tag)) {
                foreach ($tag["attributes"] as $attrKey => $attr) {
                    $openingTag .= " $attrKey=\"$attr\"";
                }
            }
            $openingTag .= ">";
            array_push($beforeTags, $openingTag);
            array_unshift($afterTags, "</{$tag['name']}>");
        }

        return implode("", $beforeTags) . $operation->getContent() . implode("", $afterTags);
    }

    /**
     * Render an image type operation
     *
     * @param Operation $operation
     */
    private function renderImageInsert(Operation $operation) {
        return "<p>".$operation->getContent()."</p>";
    }
}
