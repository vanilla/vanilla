/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { BlockEmbed } from "quill/blots/block";

export default class FocusableEmbedBlot extends BlockEmbed {
    public static readonly FOCUS_CLASS = "embed-focusableElement";

    public static create(value) {
        const node = super.create(value) as HTMLElement;
        node.setAttribute("contenteditable", false);
        node.classList.add(FocusableEmbedBlot.FOCUS_CLASS);
        return node;
    }

    public domNode: HTMLElement;

    constructor(domNode) {
        super(domNode);
        this.getFocusableElement().setAttribute("tabindex", -1);
    }

    public focus() {
        this.getFocusableElement().focus();
    }

    private getFocusableElement(): HTMLElement {
        if (this.domNode.classList.contains(FocusableEmbedBlot.FOCUS_CLASS)) {
            return this.domNode;
        } else {
            const childToFocus = this.domNode.querySelector("." + FocusableEmbedBlot.FOCUS_CLASS);
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
