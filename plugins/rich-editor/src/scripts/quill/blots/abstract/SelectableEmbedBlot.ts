/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Quill from "quill/core";
import { BlockEmbed } from "quill/blots/block";
import { logWarning } from "@vanilla/utils";
import Parchment from "parchment";
import { getBlotAtIndex } from "@rich-editor/quill/utility";
import EmbedSelectionModule from "@rich-editor/quill/EmbedSelectionModule";

/**
 * A blot that can be selectable in Quill and still behave as part of the Quill editor.
 *
 * @see {EmbedSelectModule}
 */
export class SelectableEmbedBlot extends BlockEmbed {
    public static readonly SELECTED_CLASS = "embed-isSelected";
    public static tagName = "div";
    public static className = "js-embed";
    public static blotName = "embed-focusable";

    /**
     * Create the basic HTML structure for the Blot.
     */
    public static create(value?: any) {
        const node = super.create(value) as HTMLElement;
        node.setAttribute("contenteditable", false);
        return node;
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

        const hadFocus = this.selectableElement.classList.contains(SelectableEmbedBlot.SELECTED_CLASS);
        const offset = this.offset(this.quill.scroll);
        super.remove();
        this.quill.update(Quill.sources.USER);

        // If the blot had focus before the removal we need to place the focus either on quill and set the selection
        // To the blot that will take this ones place, or focus another FocusableBlot coming in.
        if (hadFocus) {
            const potentialNewEmbedToFocus = getBlotAtIndex(this.quill, offset, SelectableEmbedBlot);
            if (potentialNewEmbedToFocus) {
                potentialNewEmbedToFocus.select();
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
    public select() {
        if (!this.quill) {
            return logWarning("Attempted to select a an embed blot that has not been mounted yet.");
        }

        this.clearOtherSelectedEmbeds();
        this.selectableElement.classList.add(SelectableEmbedBlot.SELECTED_CLASS);
        const selfPosition = this.offset(this.scroll);
        this.quill.setSelection(selfPosition, 0, Quill.sources.API);
    }

    public get isSelected(): boolean {
        return this.selectableElement.classList.contains(SelectableEmbedBlot.SELECTED_CLASS);
    }

    private clearOtherSelectedEmbeds() {
        if (!this.quill) {
            return logWarning("Attempted to select a an embed blot that has not been mounted yet.");
        }

        EmbedSelectionModule.clearEmbedSelections(this.quill);
    }

    public clearSelection() {
        this.selectableElement.classList.remove(SelectableEmbedBlot.SELECTED_CLASS);
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

    protected get selectableElement(): HTMLElement {
        return this.domNode as HTMLElement;
    }
}
