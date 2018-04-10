/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill, { Blot } from "quill/core";
import Delta from "quill-delta";
import Parchment from "parchment";
import KeyboardModule from "quill/modules/keyboard";
import Module from "quill/core/module";
import { RangeStatic } from "quill/core";
import { delegateEvent } from "@core/dom";
import FocusableEmbedBlot from "./Blots/Abstract/FocusableEmbedBlot";
import { normalizeBlotIntoBlock } from "./utility";

export default class EmbedFocusModule extends Module {

    private keyboard: KeyboardModule;

    private lastSelection: RangeStatic;

    private lastFocusedElement: HTMLElement;

    constructor(private quill: Quill, options = {}) {
        super(quill as any, options);

        this.keyboard = quill.getModule("keyboard");
        this.setupClickHandlers();
        quill.on("selection-change", (range, oldRange, source) => {
            if (range && range.index && source !== Quill.sources.SILENT) {
                this.lastSelection = range;
            }
        });
    }

    /**
     *
     */
    private setupClickHandlers() {
        delegateEvent("click", ".js-richText .embed", (event, clickedElement) => {
            clickedElement.focus();
            event.preventDefault();
        }, this.quill.container);

        delegateEvent("focus", ".js-richText .embed", (event, blurredElement) => {
            this.lastFocusedElement = blurredElement;
        }, this.quill.container);

        document.addEventListener("keydown", this.documentKeyDownListener);
    }

    private documentKeyDownListener = (event: KeyboardEvent) => {
        this.handleKeyDownAwayFromEmbed(event);
        this.handleKeyDownAwayFromQuill(event);
        this.handleDeleteOnEmbed(event);
    }


    private handleDeleteOnEmbed(event: KeyboardEvent) {
        if (event.keyCode !== KeyboardModule.keys.DELETE && event.keyCode !== KeyboardModule.keys.BACKSPACE) {
            return;
        }

        // Only works for items scoped within itself.
        const blotForActiveElement = Parchment.find(document.activeElement);
        const focusItemIsEmbedBlot = blotForActiveElement instanceof FocusableEmbedBlot;
        if (blotForActiveElement && focusItemIsEmbedBlot) {
            const delta = new Delta()
                .retain(blotForActiveElement.offset())
                .delete(1);

            this.quill.updateContents(delta);
        }
    }

    private handleKeyDownAwayFromQuill = (event: KeyboardEvent) => {
        if (document.activeElement === this.quill.root) {
            const [currentBlot] = this.quill.getLine(this.quill.getSelection().index);
            const blotToMoveTo = this.findBlotToMoveTo(currentBlot, event.keyCode);

            if ((blotToMoveTo instanceof FocusableEmbedBlot)) {
                this.focusEmbedBlot(blotToMoveTo);
                event.preventDefault();
                return;
            }
        }
    }

    private handleKeyDownAwayFromEmbed(event: KeyboardEvent) {
        const blotForActiveElement = Parchment.find(document.activeElement);
        if (!(blotForActiveElement instanceof FocusableEmbedBlot)) {
            return;
        }

        const blotToMoveTo = this.findBlotToMoveTo(blotForActiveElement, event.keyCode);

        if (!blotToMoveTo) {
            return;
        }

        if (blotToMoveTo instanceof FocusableEmbedBlot) {
            this.focusEmbedBlot(blotToMoveTo);
            event.preventDefault();
            return;
        }

        const newElementStart = blotToMoveTo.offset();
        const newElementEnd = newElementStart + blotToMoveTo.length();
        const previousIndex = this.lastSelection.index;
        const shouldUsePreviousIndex = previousIndex >= newElementStart && previousIndex < newElementEnd;
        const newIndex = shouldUsePreviousIndex ? previousIndex : newElementStart;
        this.quill.setSelection(newIndex, 0);
        event.preventDefault();
    }

    private focusEmbedBlot(blot: FocusableEmbedBlot) {
        const blotPosition = blot.offset();
        this.quill.setSelection(blotPosition, 0, Quill.sources.SILENT);
        blot.focus();
    }

    private findBlotToMoveTo(currentBlot: Blot, keyCode: number) {
        switch(keyCode) {
        case KeyboardModule.keys.DOWN:
            return currentBlot.next as Blot;
        case KeyboardModule.keys.UP:
            return currentBlot.prev as Blot;
        case KeyboardModule.keys.RIGHT:
            const endOfBlot = currentBlot.offset() + currentBlot.length();
            const currentBlotOffset = currentBlot.offset();
            const currentBlotLength = currentBlot.length();
            const currentSelection = this.quill.getSelection();
            if (this.quill.getSelection().index === endOfBlot - 1) {
                // If we're at the end of the line.
                return currentBlot.next;
            }
            break;

        case KeyboardModule.keys.LEFT:
            if (this.quill.getSelection().index === currentBlot.offset()) {
                // If we're at the start of the line.
                return currentBlot.prev;
            }
            break;
        }
    }

    private setFocusOnPreviousEmbed(range: RangeStatic) {
        const [line] = this.quill.getLine(range.index);
        const previousBlot = normalizeBlotIntoBlock(line).prev;
        if (previousBlot instanceof FocusableEmbedBlot) {
            previousBlot.focus();
            return false;
        }

        return true;
    }
}
