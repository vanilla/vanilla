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
    public static function renderDelta(string $deltaString) {
        $delta = json_decode($deltaString);

        if ($delta[""])
    }

    /**
     * The render a string type insert.
     *
     * @param array $operation A quill operation (insert and metadata).
     *
     * @returns string The rendered result.
     *
     * @example
     * {
     *    insert: "Some string contents"
     * }
     */
    private static function string(array $operation): string {

    }
}
