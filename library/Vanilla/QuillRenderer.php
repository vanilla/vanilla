<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

use \Vanilla\QuillOperation;

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
        $delta = json_decode($deltaString, true);
        $html = "";

        foreach($delta as $opArray) {
            $operation = new QuillOperation($opArray);

            switch ($operation->insertType) {
                case QuillOperation::INSERT_TYPE_STRING:
                    $html .= $this->renderStringInsert($operation);
                    break;
                case QuillOperation::INSERT_TYPE_IMAGE:
                    $html .= $this->renderImageInsert($operation);
            }
        }

        return $html;
    }

    /**
     * Render a string type operation
     *
     * @param QuillOperation $operation
     */
    private function renderStringInsert(QuillOperation $operation) {
        $tags = ["p"];

        if ($operation->bold) {

        }

        if ($operation->header > 0) {
            $tags[] = "h".$operation->header;
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

        return "<p>".$operation->content."</p>";
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
