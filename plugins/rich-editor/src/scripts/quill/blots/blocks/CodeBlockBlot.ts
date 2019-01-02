/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { CodeBlock } from "quill/modules/syntax";

export default class CodeBlockBlot extends CodeBlock {
    public static create(value) {
        const domNode = super.create(value) as HTMLElement;
        domNode.setAttribute("spellcheck", false);
        domNode.classList.add("code");
        domNode.classList.add("codeBlock");
        return domNode;
    }
}
