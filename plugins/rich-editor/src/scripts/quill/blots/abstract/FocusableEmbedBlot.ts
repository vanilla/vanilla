/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { BlockEmbed } from "quill/blots/block";
import { FOCUS_CLASS } from "@dashboard/embeds";

export default class FocusableEmbedBlot extends BlockEmbed {
    public static tagName = "div";
    public static className = "embed";

    public static create(value) {
        const node = super.create(value) as HTMLElement;
        node.setAttribute("contenteditable", false);
        node.classList.add(FOCUS_CLASS);
        return node;
    }

    public domNode: HTMLElement;

    constructor(domNode) {
        super(domNode);
        this.getFocusableElement().setAttribute("tabindex", -1);
        domNode.classList.add("embed");
    }

    public focus() {
        this.getFocusableElement().focus();
    }

    private getFocusableElement(): HTMLElement {
        if (this.domNode.classList.contains(FOCUS_CLASS)) {
            return this.domNode;
        } else {
            const childToFocus = this.domNode.querySelector("." + FOCUS_CLASS);
            if (childToFocus instanceof HTMLElement) {
                return childToFocus;
            } else {
                throw new Error(
                    "Attempting to focus a DOM Node that either does not exist or is not an HTMLElement." +
                        childToFocus,
                );
            }
        }
    }
}
