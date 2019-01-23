/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Code } from "quill/formats/code";

export default class CodeBlot extends Code {
    constructor(domNode) {
        super(domNode);
        domNode.classList.add("code");
        domNode.classList.add("isInline");
        domNode.classList.add("codeInline");
        domNode.setAttribute("spellcheck", false);
    }
}
