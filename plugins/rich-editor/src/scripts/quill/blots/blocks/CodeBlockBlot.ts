/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import BaseCodeBlock from "quill/formats/code";
import { CodeBlock } from "quill/modules/syntax";

export default class CodeBlockBlot extends CodeBlock {
    public static create(value) {
        const domNode = super.create(value) as HTMLElement;
        domNode.setAttribute("spellcheck", false);
        domNode.classList.add("code");
        domNode.classList.add("codeBlock");
        return domNode;
    }

    ///
    /// This is a patch to get the fix actually provided in
    /// https://github.com/quilljs/quill/commit/ba9f820514ce7c268ce58bbe6d1c4e8f77bf056f
    ///
    /// Moving to Quill 2.0 upon release shall render this unnecessary.
    ///
    private baseCodeBlockReplace = BaseCodeBlock.prototype.replaceWith.bind(this);
    public replaceWith(format, value) {
        const replacement = this.baseCodeBlockReplace(format, value);
        replacement.attach();
        const element = replacement.domNode as HTMLElement;
        const innerSpans = element.querySelectorAll("*");
        innerSpans.forEach(span => {
            span.setAttribute("class", "");
        });
        return replacement;
    }
}
