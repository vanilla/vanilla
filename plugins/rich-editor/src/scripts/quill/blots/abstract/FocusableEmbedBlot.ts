/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Quill from "quill/core";
import { BlockEmbed } from "quill/blots/block";
import { FOCUS_CLASS } from "@library/embeddedContent/embedService";
import { logWarning } from "@vanilla/utils";
import Parchment from "parchment";
import { getBlotAtIndex } from "@rich-editor/quill/utility";

/**
 * A blot that can take focus and still behave as part of the Quill editor.
 *
 * @see {EmbedFocusModule}
 */
export default class FocusableEmbedBlot extends BlockEmbed {
    public static tagName = "div";
    public static className = "js-embed";
    public static blotName = "embed-focusable";

    /**
     * Create the basic HTML structure for the Blot.
     */
    public static create(value?: any) {
        const node = super.create(value) as HTMLElement;
        node.setAttribute("contenteditable", false);
        node.classList.add(FOCUS_CLASS);
        return node;
    }

    constructor(domNode) {
        super(domNode);
        this.focusableElement.setAttribute("tabindex", -1);
    }

    /**
     * In addition to removing the FocusableBlot, we either:
     * - Place selection on another FocusableBlot that will be in the same spot as this one after deletion.
     * - Place the selection back in quill where this blot was.
     */
    public remove() {
        if (!this.quill) {
            return logWarning("Attempted to focus a an embed blot that has not been mounted yet.");
        }

        const hadFocus = this.focusableElement === document.activeElement;
        const offset = this.offset(this.quill.scroll);
        super.remove();
        this.quill.update(Quill.sources.USER);

        // If the blot had focus before the removal we need to place the focus either on quill and set the selection
        // To the blot that will take this ones place, or focus another FocusableBlot coming in.
        if (hadFocus) {
            const potentialNewEmbedToFocus = getBlotAtIndex(this.quill, offset, FocusableEmbedBlot);
            if (potentialNewEmbedToFocus) {
                potentialNewEmbedToFocus.focus();
            } else {
                this.quill.setSelection(offset, 0, Quill.sources.USER);
            }
        }
    }

    /**
     * Inserts a newline after this blot and places the cursor there.
     */
    public insertNewlineAfter() {
        if (!this.quill) {
            return logWarning("Attempted to focus a an embed blot that has not been mounted yet.");
        }

        const newBlot = Parchment.create("block", "");
        newBlot.insertInto(this.quill.scroll, this.next);
        this.quill.update(Quill.sources.USER);
        this.quill.setSelection(this.offset() + 1, 0, Quill.sources.USER);
    }

    /**
     * Focus this blot and set quill's selection to where it is.
     *
     * The actual act of focusing this blot will cause quill to set it's internal selection to null, but this will still fire out the change anyways. Many of our own listeners cache the last valid selection, and null is not a valid selection.
     */
    public focus() {
        if (!this.quill) {
            return logWarning("Attempted to focus a an embed blot that has not been mounted yet.");
        }

        const selfPosition = this.offset(this.scroll);
        this.quill.setSelection(selfPosition, 0, Quill.sources.API);
        this.focusableElement.focus();
    }

    /**
     * Get the focusable element inside of this blot. This is not necessarily the one we set here in child nodes. It will be whatever gets the FOCUS_CLASS.
     */
    private get focusableElement(): HTMLElement {
        if (!(this.domNode instanceof HTMLElement)) {
            throw new Error("A focusable embed blot must be initialize with an HTMLElement.");
        }
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

    /**
     * Get the attached quill instance.
     *
     * This will _NOT_ work before attach() is called.
     */
    protected get quill(): Quill | null {
        if (!this.scroll || !this.scroll.domNode.parentNode) {
            return null;
        }

        return Quill.find(this.scroll.domNode.parentNode!);
    }
}
