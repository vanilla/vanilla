/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import CodeBlock from "quill/formats/code";
import Text from "quill/blots/text";
import Break from "quill/blots/break";
import Cursor from "quill/blots/cursor";

export default class CodeBlockBlot extends CodeBlock {
    public static blotName = "codeBlock";
    public static tagName = "code";
    public static className = "codeBlock";
    public static allowedChildren = [Text, Break, Cursor];

    public static create(value) {
        const domNode = super.create(value) as HTMLElement;
        domNode.setAttribute("spellcheck", false);
        domNode.classList.add("code");
        domNode.classList.add("codeBlock");
        return domNode;
    }
}
