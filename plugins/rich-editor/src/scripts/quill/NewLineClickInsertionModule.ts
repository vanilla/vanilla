/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import BlockBlot from "quill/blots/block";
import Quill from "quill/core";
import ExternalEmbedBlot from "./blots/embeds/ExternalEmbedBlot";

/**
 * Module to insert a newline on click at the bottom of the document
 * if there is an embed in the last position.
 */
export default class NewLineClickInsertionModule {
    constructor(private quill: Quill) {
        // this.quill.root.addEventListener("click", this.handleClick);
    }

    /**
     * @internal
     */
    public handleClick = (event: MouseEvent) => {
        // Make sure that we were directly clicked and are not handling a bubbled event.
        if (event.currentTarget !== this.quill.root) {
            return;
        }

        // Check that we don't have an embed blot at the end.
        const lastBlot = this.quill.scroll.children.tail;
        if (!(lastBlot instanceof ExternalEmbedBlot)) {
            return;
        }

        // Filter out click events that aren't below the last item in the document.
        const blotRect = (lastBlot.domNode as HTMLElement).getBoundingClientRect();
        const bottomOfBlot = blotRect.bottom;

        if (event.y <= bottomOfBlot) {
            // The click not on the bottom section of the document.
            return;
        }

        const newline = new BlockBlot(BlockBlot.create("\n"));
        newline.insertInto(this.quill.scroll);
        this.quill.update(Quill.sources.USER);
        this.quill.setSelection(this.quill.scroll.length(), 0, Quill.sources.USER);
    };
}
