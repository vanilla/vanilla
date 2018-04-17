/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import CodeBlock from "quill/formats/code";

export default class CodeBlockBlot extends CodeBlock {
    public static blotName = "code-block";
    public static tagName = "code";
    public static className = "code-block";

    public static create(value) {
        const domNode = super.create(value) as HTMLElement;
        domNode.setAttribute("spellcheck", false);
        domNode.classList.add("code");
        domNode.classList.add("isBlock");
        domNode.classList.add("code-block");
        return domNode;
    }
}
