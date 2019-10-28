/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Quill, { Blot, RangeStatic } from "quill/core";
import Parchment from "parchment";
import KeyboardModule from "quill/modules/keyboard";
import Module from "quill/core/module";
import { delegateEvent } from "@vanilla/dom-utils";
import { SelectableEmbedBlot } from "@rich-editor/quill/blots/abstract/SelectableEmbedBlot";
import {
    insertNewLineAtEndOfScroll,
    insertNewLineAtStartOfScroll,
    getBlotAtIndex,
    rangeContainsBlot,
    forceSelectionUpdate,
    isEmbedSelected,
} from "@rich-editor/quill/utility";
import MentionAutoCompleteBlot from "@rich-editor/quill/blots/embeds/MentionAutoCompleteBlot";
import { isEditorWalledEvent } from "@rich-editor/editor/pieces/EditorEventWall";

/**
 * A module for managing focus of Embeds. For this to work for a new Embed,
 * ensure that your embed extends FocusEmbedBlot
 *
 * @see {FocusableEmbedBlot}
 */
export default class EmbedFocusModule extends Module {
    /**
     * @param quill - The quill instance to tie into.
     * @param options - The quill options.
     *
     * @throws If the necessary surrounding HTML cannot be located.
     */
    constructor(quill: Quill, options = {}) {
        super(quill, options);
        this.setupEmbedClickHandler();
        this.quill.root.addEventListener("keydown", this.keyDownListener);
        this.quill.on("selection-change", (newRange: RangeStatic) => {
            if (!isEmbedSelected(this.quill, newRange)) {
                EmbedFocusModule.clearEmbedSelections(this.quill);
            }
        });
    }

    public static clearEmbedSelections(quill: Quill) {
        const domNodes = quill.root.querySelectorAll("." + SelectableEmbedBlot.SELECTED_CLASS);
        domNodes.forEach(node => {
            const blot = Quill.find(node);
            if (blot instanceof SelectableEmbedBlot) {
                blot.clearSelection();
            }
        });
    }

    /**
     * Determine if there is an active mention open.
     */
    public get inActiveMention() {
        const fullDocumentRange = {
            index: 0,
            length: this.quill.scroll.length() - 1,
        };
        return rangeContainsBlot(this.quill, MentionAutoCompleteBlot, fullDocumentRange);
    }

    /**
     * Handle arrow keys while an embed is Focused.
     *
     * @returns true if the event was handled
     *
     * @if
     * - And Embed is Focused
     * - An arrow key is pressed
     *
     * @andif
     * - The Embed is at the beginnning or the end of the document.
     * @then
     * - Insert a new line and move the selection before or after the Embed.
     *
     * @else
     * Move the selection/focus to the next blot.
     */
    public handleArrowKeyFromEmbed = (directionKeyCode: number, blotForActiveElement: SelectableEmbedBlot): boolean => {
        // Check if we are at the beginning or end of the scroll.
        const position = blotForActiveElement.offset(this.quill.scroll);
        const isEndOfScroll = position + blotForActiveElement.length() === this.quill.scroll.length();
        const isStartOfScroll = position === 0;
        const isUpOrLeft = [KeyboardModule.keys.LEFT, KeyboardModule.keys.UP].includes(directionKeyCode as any);
        const isDownOrRight = [KeyboardModule.keys.RIGHT, KeyboardModule.keys.DOWN].includes(directionKeyCode as any);
        if (isStartOfScroll && isUpOrLeft) {
            insertNewLineAtStartOfScroll(this.quill);
            return true;
        } else if (isEndOfScroll && isDownOrRight) {
            insertNewLineAtEndOfScroll(this.quill);
            return true;
        }

        // Check if we have a blot to move to.
        const blotToMoveTo = this.getNextBlotFromArrowKey(blotForActiveElement, directionKeyCode);
        if (blotToMoveTo) {
            this.arrowToBlot(blotToMoveTo);
            return true;
        }

        return false;
    };

    /**
     * @if
     * - The next or previous Blot is an Embed Blot
     * @then
     * - Set focus on that Blot
     * @else
     * - Move the focus into the next text content. If the previous selection was in inside of that blot
     * restore that selection.
     *
     * @param blotToMoveTo The next blot to move towards.
     * @param useSelectionHistory Whether or not to use previous selection history to try and restore a selection position.
     */
    public arrowToBlot(blotToMoveTo: Blot) {
        if (blotToMoveTo instanceof SelectableEmbedBlot) {
            blotToMoveTo.select();
        } else {
            // We want to mimic normal movement behaviour as if our Blot was text, so
            // We check if we need to put the cursor in the middle of the next or previous line.
            const newElementStart = blotToMoveTo.offset(this.quill.scroll);
            this.quill.setSelection(newElementStart, 0);
        }
    }

    /**
     * Check if the focused element in the document is of an Embed blot and return it.
     */
    public getSelectedEmbed(): SelectableEmbedBlot | null {
        const selected = this.quill.root.querySelector("." + SelectableEmbedBlot.SELECTED_CLASS);
        if (!selected) {
            return null;
        }

        const embed = Parchment.find(selected);
        if (embed instanceof SelectableEmbedBlot) {
            return embed;
        } else {
            return null;
        }
    }

    /**
     * Handle deletions while quill is focused.
     *
     * @if
     * - Backspace is pressed
     * - The current Blot is an empty Blot.
     * - The previous blot is an embed blot.
     *
     * @then
     * - Delete the current empty line.
     * - Set focus on the previous embed Blot.
     *
     * @returns true if the event was handled
     */
    public handleDeleteOnQuill = (): boolean => {
        const selection = this.quill.getSelection();
        const currentBlot = selection && (this.quill.getLine(selection.index)[0] as Blot | null);

        if (!currentBlot) {
            return false;
        }

        const previousBlot = currentBlot.prev;
        const isCurrentBlotEmpty = currentBlot.domNode.textContent === "";

        if (previousBlot instanceof SelectableEmbedBlot && isCurrentBlotEmpty) {
            currentBlot.remove();
            previousBlot.select();
            return true;
        }

        return false;
    };

    /**
     * Determine which Blot we want to move to based on the arrow key pressed.
     *
     * @param currentBlot The blot to check.
     * @param keyCode The keycode that was pressed.
     */
    private getNextBlotFromArrowKey(currentBlot: Blot, keyCode: number) {
        if (!currentBlot) {
            return null;
        }
        switch (keyCode) {
            case KeyboardModule.keys.DOWN:
                return currentBlot.next as Blot;
            case KeyboardModule.keys.UP:
                return currentBlot.prev as Blot;
            case KeyboardModule.keys.RIGHT: {
                // -1 needed for because end of blot is non-inclusive.
                const endOfBlot = currentBlot.offset() + currentBlot.length() - 1;
                if (this.quill.getLastGoodSelection().index === endOfBlot) {
                    // If we're at the end of the line.
                    return currentBlot.next as Blot;
                }
                break;
            }
            case KeyboardModule.keys.LEFT:
                if (this.quill.getLastGoodSelection().index === currentBlot.offset()) {
                    // If we're at the start of the line.
                    return currentBlot.prev as Blot;
                }
                break;
        }
    }

    /**
     * Setup a click handler to focus embeds.
     */
    private setupEmbedClickHandler() {
        delegateEvent(
            "click",
            "a",
            (event, clickedElement) => {
                if (isEditorWalledEvent(event)) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
            },
            this.quill.container,
        );
        delegateEvent(
            "click",
            ".js-embed",
            (event, clickedElement) => {
                if (!isEditorWalledEvent(event)) {
                    const embed = Parchment.find(clickedElement);
                    if (embed instanceof SelectableEmbedBlot) {
                        embed.select();
                    }
                }
            },
            this.quill.container,
        );
    }

    /**
     * Keydown listener on the current quill instance.
     */
    private keyDownListener = (event: KeyboardEvent) => {
        if (isEditorWalledEvent(event)) {
            return;
        }

        const blotForActiveElement = this.getSelectedEmbed();
        const [currentLineBlot] = this.quill.getLine(this.quill.getLastGoodSelection().index);
        const blotToMoveTo = this.getNextBlotFromArrowKey(currentLineBlot, event.keyCode);

        // Handle arrow keys.
        if (this.isKeyCodeArrowKey(event.keyCode) && !event.shiftKey && !this.inActiveMention) {
            // If we're in an embed we need special handling.
            if (blotForActiveElement instanceof SelectableEmbedBlot) {
                const eventWasHandled = this.handleArrowKeyFromEmbed(event.keyCode, blotForActiveElement);

                if (eventWasHandled) {
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
                // If we're in quill and moving to an embed blot we need to focus it.
            } else if (this.quill.hasFocus() && blotToMoveTo instanceof SelectableEmbedBlot) {
                event.preventDefault();
                event.stopPropagation();
                blotToMoveTo.select();
            }
        }

        // Handle delete/backspace
        if (this.isKeyCodeDelete(event.keyCode)) {
            // If we're in an embed blot we want to delete it.
            if (blotForActiveElement instanceof SelectableEmbedBlot) {
                event.preventDefault();
                event.stopPropagation();
                blotForActiveElement.remove();
                forceSelectionUpdate();

                // Otherwise if we're just in quill normally, we special handling in case the previous item is an embed blot.
            } else if (this.quill.hasFocus()) {
                const eventWasHandled = this.handleDeleteOnQuill();
                if (eventWasHandled) {
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
            }
        }

        // Handle the enter key.
        if (KeyboardModule.match(event, KeyboardModule.keys.ENTER)) {
            if (blotForActiveElement instanceof SelectableEmbedBlot) {
                event.preventDefault();
                event.stopPropagation();
                blotForActiveElement.insertNewlineAfter();
            }
        }
    };

    /**
     * Detect if an keyCode is of an arrow key.
     */
    private isKeyCodeArrowKey(keyCode: number) {
        const { UP, DOWN, LEFT, RIGHT } = KeyboardModule.keys;
        return [UP, DOWN, LEFT, RIGHT].includes(keyCode as any);
    }

    /**
     * Detect if an keyCode is of Delete or Backspace
     */
    private isKeyCodeDelete(keyCode: number) {
        const { BACKSPACE, DELETE } = KeyboardModule.keys;
        return [BACKSPACE, DELETE].includes(keyCode as any);
    }
}
