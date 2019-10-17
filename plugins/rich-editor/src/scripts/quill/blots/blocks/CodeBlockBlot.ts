/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import BaseCodeBlock from "quill/formats/code";
import { CodeBlock } from "quill/modules/syntax";
import { highlightText } from "@library/content/code";

export default class CodeBlockBlot extends CodeBlock {
    public static create(value) {
        const domNode = super.create(value) as HTMLElement;
        domNode.setAttribute("spellcheck", false);
        domNode.classList.add("code");
        domNode.classList.add("codeBlock");
        return domNode;
    }

    private cachedText = "";

    /**
     * Override highlight to use our own highlighter.
     */
    public highlight() {
        let text = this.domNode.textContent;
        if (text && this.cachedText !== text) {
            if (text.trim().length > 0 || this.cachedText == null) {
                highlightText(text).then(result => {
                    this.domNode.innerHTML = result;
                    this.domNode.normalize();
                    this.attach();
                });
            }
            this.cachedText = text;
        }
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
