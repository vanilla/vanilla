/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onContent } from "@library/application";
import hljs from "highlight.js";
import "highlight.js/styles/github.css";

export function initCodeHighlighting() {
    highlightCodeBlocks();
    onContent(highlightCodeBlocks);
}

function highlightCodeBlocks() {
    const blocks = document.querySelectorAll(".code.codeBlock");
    blocks.forEach(hljs.highlightBlock);
}
