/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Quill, { Blot } from "quill/core";
import Parchment from "parchment";
import KeyboardModule from "quill/modules/keyboard";
import Module from "quill/core/module";
import { RangeStatic } from "quill/core";
import { delegateEvent, TabHandler } from "@vanilla/dom-utils";
import FocusableEmbedBlot from "@rich-editor/quill/blots/abstract/FocusableEmbedBlot";
import {
    insertNewLineAtEndOfScroll,
    insertNewLineAtStartOfScroll,
    getBlotAtIndex,
    rangeContainsBlot,
    forceSelectionUpdate,
} from "@rich-editor/quill/utility";
import MentionAutoCompleteBlot from "@rich-editor/quill/blots/embeds/MentionAutoCompleteBlot";
import { isEditorWalledEvent } from "@rich-editor/editor/pieces/EditorEventWall";
import { FOCUS_CLASS } from "@library/embeddedContent/embedService";

/**
 * A module for managing focus of Embeds. For this to work for a new Embed,
 * ensure that your embed extends FocusEmbedBlot
 *
 * @see {FocusableEmbedBlot}
 */
export default class EmbedFocusModule extends Module {
    /** The previous selection */
    private lastSelection: RangeStatic = {
        index: 0,
        length: 0,
    };

    private editorRoot: HTMLElement;
    private formWrapper: HTMLElement;

    /**
     * @param quill - The quill instance to tie into.
     * @param options - The quill options.
     *
     * @throws If the necessary surrounding HTML cannot be located.
     */
    constructor(quill: Quill, options = {}) {
        super(quill, options);

        this.editorRoot = this.quill.root.closest(".richEditor") as HTMLElement;
        this.formWrapper = (this.editorRoot.closest(".FormWrapper") ||
            this.editorRoot.closest(".FormTitleWrapper")) as HTMLElement;

        if (!this.editorRoot) {
            throw new Error("Cannot initialize the EmbedFocusModule without an editorRoot (.richEditor class)");
        }

        if (!this.formWrapper) {
            throw new Error(
                "Cannot initialize the EmbedFocusModule without a FormWrapper (.FormWrapper or .FormTitleWrapper class)",
            );
        }

        // Add a tabindex onto the contenteditable so that our utilities know it is focusable.
        this.quill.root.setAttribute("tabindex", 0);

        // Track user selection events.
        quill.on("selection-change", (range, oldRange, source) => {
            if (range && source !== Quill.sources.SILENT) {
                this.lastSelection = range;
                this.editorRoot.classList.toggle("isFocused", true);
            }
        });

        this.setupEmbedClickHandler();

        this.quill.root.addEventListener("keydown", this.keyDownListener);
        this.formWrapper.addEventListener("keydown", this.tabListener);
        this.editorRoot.addEventListener("keydown", this.escapeMobileFullScreen);
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

    public escapeMobileFullScreen = (event: KeyboardEvent) => {
        const position = window.getComputedStyle(this.editorRoot).getPropertyValue("position");
        const editorIsFullscreen = this.editorRoot.classList.contains("isFocused") && position === "fixed";
        if (editorIsFullscreen && KeyboardModule.match(event, { key: KeyboardModule.keys.ESCAPE, shiftKey: false })) {
            this.quill.root.focus();
            const tabHandler = new TabHandler(this.formWrapper);
            const nextEl = tabHandler.getNext();
            if (nextEl) {
                nextEl.focus();
                this.editorRoot.classList.toggle("isFocused", false);
            }
        }
    };

    /**
     * Manually handle tab presses.
     *
     * Because it can be next to impossible to control focus once it shifts into an embedded iframe
     * or shadow dom root, the EmbedFocusManager is now manually handling all tab and shift-tab shortcuts
     * to move focus between the various editor elements.
     *
     * Once you are outside of the editor there is nothing to worry about.
     * This only affects while the cursor is in the editor or inside of an embed blot.
     *
     * @returns true if the event was handled.
     */
    public handleTab = (): boolean => {
        let fromElement = document.activeElement;
        if (this.quill.root.contains(document.activeElement)) {
            fromElement = this.quill.root;
        }
        const blotForActiveElement = this.getEmbedBlotForFocusedElement();
        const focusItemIsEmbedBlot = blotForActiveElement instanceof FocusableEmbedBlot;

        // Focus the next available editor ui component.
        const tabHandler = new TabHandler(this.formWrapper, undefined, [this.quill.root]);
        const nextElement = tabHandler.getNext(fromElement, false, false);

        if (!nextElement) {
            return false;
        }

        // We need to place the selection at the end of quill.
        if (nextElement === this.quill.root) {
            this.focusFirstLine();
        } else {
            nextElement.focus();
        }

        return true;
    };

    /**
     * Manually handle tab presses.
     *
     * Because it can be next to impossible to control focus once it shifts into an embedded iframe
     * or shadow dom root, the EmbedFocusManager is now manually handling all tab and shift-tab shortcuts
     * to move focus between the various editor elements.
     *
     * @returns true if the event was handled.
     */
    public handleShiftTab = () => {
        // Treat a focus inside of quill as if quill is focused.
        let fromElement = document.activeElement;
        if (this.quill.root.contains(document.activeElement)) {
            fromElement = this.quill.root;
        }

        const tabHandler = new TabHandler(this.formWrapper, undefined, [this.quill.root]);
        const prevElement = tabHandler.getNext(fromElement, true, false);

        if (!prevElement) {
            return false;
        }

        // We need to place the selection at the end of quill.
        if (prevElement === this.quill.root) {
            this.focusLastLine();
        } else {
            prevElement.focus();
        }

        return true;
    };

    /**
     * Focus the last line of the editor.
     *
     * Handles embed blots in addition to plain text.
     */
    public focusLastLine() {
        const lastIndex = this.quill.scroll.length() - 1;
        const lastEmbedBlot = getBlotAtIndex(this.quill, lastIndex, FocusableEmbedBlot);
        if (lastEmbedBlot) {
            lastEmbedBlot.focus();
        } else {
            this.quill.focus();
            this.quill.setSelection(lastIndex, 0, Quill.sources.USER);
        }
    }

    /**
     * Focus the first line of the editor.
     *
     * Handles embed blots in addition to plain text.
     */
    public focusFirstLine() {
        const firstEmbedBlot = this.quill.scroll.children.head;
        if (firstEmbedBlot instanceof FocusableEmbedBlot) {
            firstEmbedBlot.focus();
        } else {
            this.quill.focus();
            this.quill.setSelection(0, 0, Quill.sources.USER);
        }
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
    public handleArrowKeyFromEmbed = (directionKeyCode: number, blotForActiveElement: FocusableEmbedBlot): boolean => {
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
        if (blotToMoveTo instanceof FocusableEmbedBlot) {
            blotToMoveTo.focus();
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
    public getEmbedBlotForFocusedElement() {
        if (!(document.activeElement instanceof Element)) {
            return;
        }

        let activeElement = document.activeElement;
        if (!activeElement.classList.contains("js-embed")) {
            const closestEmbed = activeElement.closest(".js-embed");
            if (!closestEmbed) {
                return;
            }

            activeElement = closestEmbed;
        }

        return Parchment.find(activeElement);
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

        if (previousBlot instanceof FocusableEmbedBlot && isCurrentBlotEmpty) {
            currentBlot.remove();
            previousBlot.focus();
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
                if (this.lastSelection.index === endOfBlot) {
                    // If we're at the end of the line.
                    return currentBlot.next as Blot;
                }
                break;
            }
            case KeyboardModule.keys.LEFT:
                if (this.lastSelection.index === currentBlot.offset()) {
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
            "." + FOCUS_CLASS,
            (event, clickedElement) => {
                if (isEditorWalledEvent(event)) {
                    return;
                }
                const embed = Parchment.find(clickedElement.closest(".js-embed"));
                if (embed instanceof FocusableEmbedBlot) {
                    embed.focus();
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

        if (!this.editorRoot.contains(document.activeElement)) {
            return;
        }

        const blotForActiveElement = this.getEmbedBlotForFocusedElement();
        const [currentLineBlot] = this.quill.getLine(this.lastSelection.index);
        const blotToMoveTo = this.getNextBlotFromArrowKey(currentLineBlot, event.keyCode);

        // Handle arrow keys.
        if (this.isKeyCodeArrowKey(event.keyCode) && !event.shiftKey && !this.inActiveMention) {
            // If we're in an embed we need special handling.
            if (blotForActiveElement instanceof FocusableEmbedBlot) {
                const eventWasHandled = this.handleArrowKeyFromEmbed(event.keyCode, blotForActiveElement);

                if (eventWasHandled) {
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
                // If we're in quill and moving to an embed blot we need to focus it.
            } else if (this.quill.hasFocus() && blotToMoveTo instanceof FocusableEmbedBlot) {
                event.preventDefault();
                event.stopPropagation();
                blotToMoveTo.focus();
            }
        }

        // Handle delete/backspace
        if (this.isKeyCodeDelete(event.keyCode)) {
            // If we're in an embed blot we want to delete it.
            if (blotForActiveElement instanceof FocusableEmbedBlot) {
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
            if (blotForActiveElement instanceof FocusableEmbedBlot) {
                event.preventDefault();
                event.stopPropagation();
                blotForActiveElement.insertNewlineAfter();
            }
        }
    };

    /**
     * Keydown listener on the current quill instance.
     */
    private tabListener = (event: KeyboardEvent) => {
        if (!this.formWrapper!.contains(document.activeElement)) {
            return;
        }

        let eventWasHandled = false;

        if (KeyboardModule.match(event, { key: KeyboardModule.keys.TAB, shiftKey: true })) {
            eventWasHandled = this.handleShiftTab();
        } else if (KeyboardModule.match(event, { key: KeyboardModule.keys.TAB, shiftKey: false })) {
            eventWasHandled = this.handleTab();
        }

        if (eventWasHandled) {
            event.preventDefault();
            event.stopPropagation();
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
