/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill, { Blot } from "quill/core";
import Delta from "quill-delta";
import Parchment from "parchment";
import KeyboardModule, { default as Keyboard } from "quill/modules/keyboard";
import Module from "quill/core/module";
import { RangeStatic } from "quill/core";
import { delegateEvent, getNextTabbableElement } from "@dashboard/dom";
import FocusableEmbedBlot from "./blots/abstract/FocusableEmbedBlot";
import {
    normalizeBlotIntoBlock,
    insertNewLineAtEndOfScroll,
    insertNewLineAtStartOfScroll,
    getBlotAtIndex,
} from "./utility";

/**
 * A module for managing focus of Embeds. For this to work for a new Embed,
 * ensure that you're embed extends FocusEmbedBlot
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
    private paragraphMenuHandle: HTMLElement;
    private inlineToolbarFirstActiveItem: HTMLElement;
    private emojiPickerButton: HTMLElement;

    constructor(quill: Quill, options = {}) {
        super(quill, options);

        this.editorRoot = this.quill.root.closest(".richEditor") as HTMLElement;
        this.formWrapper = this.editorRoot.closest(".FormWrapper") as HTMLElement;

        // Add event listeners.
        quill.on("selection-change", (range, oldRange, source) => {
            if (range && range.index && source !== Quill.sources.SILENT) {
                this.lastSelection = range;
                this.editorRoot.classList.toggle("isFocused", true);
            }
        });
        this.setupEmbedClickHandler();
        this.setupMobileHandler();

        this.quill.root.addEventListener("keydown", this.keyDownListener);
        this.editorRoot.addEventListener("keydown", this.tabListener);
        this.editorRoot.addEventListener("keydown", this.escapeMobileFullScreen);
        window.quill = quill;
    }
    
    private setupEmbedClickHandler() {
        delegateEvent(
            "click",
            ".js-richText .embed",
            (event, clickedElement) => {
                const embed = Parchment.find(clickedElement);
                if (embed instanceof FocusableEmbedBlot) {
                    this.focusEmbedBlot(embed);
                    event.preventDefault();
                }
            },
            this.quill.container,
        );
    }

    private setupMobileHandler() {
        delegateEvent(
            "click",
            ".js-richText .richEditor-text",
            (event, clickedElement) => {
                this.editorRoot.classList.toggle("isFocused", true);
            },
            this.quill.container,
        );

        delegateEvent(
            "click",
            ".js-richEditor-next",
            (event, clickedElement) => {
                const nextEl = getNextTabbableElement(this.formWrapper, true, clickedElement);
                nextEl.focus();
                this.editorRoot.classList.toggle("isFocused", false);
            },
            this.editorRoot,
        );
    }

    /**
     * This needs to be exported in set in the quill options, before the keyboard module is instantiated.
     *
     * @if
     * - Backspace is pressed
     * - The current Blot is an empty Blot.
     * - The previous blot is an embed blot.
     *
     * @then
     * - Delete the current empty line.
     * - Set focus on the previous embed Blot.
     * - Prevent handleDeleteOnEmbed on from running.
     */
    private handleDeleteOnQuill = (event: KeyboardEvent) => {
        if (!this.isKeyCodeDelete(event.keyCode)) {
            return true;
        }
        const [currentBlot] = this.quill.getLine(this.quill.getSelection().index);
        const previousBlot = currentBlot.prev;
        const isPreviousBlotEmbed = previousBlot instanceof FocusableEmbedBlot;
        const isCurrentBlotEmpty = currentBlot.domNode.textContent === "";

        if (isPreviousBlotEmbed && isCurrentBlotEmpty) {
            (currentBlot as Blot).remove();
            this.quill.update(Quill.sources.USER);
            this.focusEmbedBlot(previousBlot);
            return false;
        }

        return true;
    };

    /**
     * Keydown listener on the current quill instance.
     */
    private keyDownListener = (event: KeyboardEvent) => {
        if (!this.quill.container.closest(".richEditor")!.contains(document.activeElement)) {
            return;
        }

        const exclusiveHandlers = [
            this.handleArrowKeyFromEmbed,
            this.handleArrowKeyAwayFromQuill,
            this.handleDeleteOnQuill,
            this.handleDeleteOnEmbed,
            this.handleEnterOnEmbed,
        ];

        // Run all of the event listeners until one returns false;
        return exclusiveHandlers.reduce((shouldContinue, currentHandler) => {
            return shouldContinue ? currentHandler(event) : false;
        }, true);
    };

    /**
     * Keydown listener on the current quill instance.
     */
    private tabListener = (event: KeyboardEvent) => {
        if (!this.quill.container.closest(".richEditor")!.contains(document.activeElement)) {
            return;
        }

        const exclusiveHandlers = [this.handleTab, this.handleShiftTab];

        // Run all of the event listeners until one returns false;
        return exclusiveHandlers.reduce((shouldContinue, currentHandler) => {
            return shouldContinue ? currentHandler(event) : false;
        }, true);
    };

    private escapeMobileFullScreen = (event: KeyboardEvent) => {
        if (KeyboardModule.match(event, { key: KeyboardModule.keys.ESCAPE, shiftKey: false })) {
            this.quill.root.focus();
            const nextEl = getNextTabbableElement(this.formWrapper, true, this.quill.root);
            nextEl.focus();
            this.editorRoot.classList.toggle("isFocused", false);
        }
    };

    /**
     * Ensure that we have references to certain editor elements that we may want to focus.
     *
     * This is necessary because they are not mounted yet when this class is constructed.
     */
    private ensureEditorElements() {
        if (!this.paragraphMenuHandle) {
            this.paragraphMenuHandle = this.editorRoot.querySelector(".richEditorParagraphMenu-handle") as HTMLElement;
        }

        // Not cached because it could
        this.inlineToolbarFirstActiveItem = this.editorRoot.querySelector(
            ".richEditor-inlineToolbarContainer .richEditor-menuItem:not([disabled])",
        ) as HTMLElement;

        if (!this.emojiPickerButton) {
            this.emojiPickerButton = this.editorRoot.querySelector(".emojiPicker > .richEditor-button") as HTMLElement;
        }

        return this.paragraphMenuHandle && this.inlineToolbarFirstActiveItem && this.emojiPickerButton;
    }

    /**
     * Manually handle tab presses.
     *
     * Because it can be next to impossible to control focus once it shifts into an embedded iframe
     * or shadow dom root, the EmbedFocusManager is now manually handling all tab and shift-tab shortcuts
     * to move focus between the various editor elements.
     *
     * Once you are outside of the editor there is nothing to worry about.
     * This only affects while the cursor is in the editor or inside of an embed blot.
     */
    private handleTab = (event: KeyboardEvent) => {
        if (
            !KeyboardModule.match(event, {
                key: KeyboardModule.keys.TAB,
                shiftKey: false,
            })
        ) {
            return true;
        }

        if (!this.ensureEditorElements()) {
            return true;
        }

        const blotForActiveElement = this.getEmbedBlotForFocusedElement();
        const focusItemIsEmbedBlot = blotForActiveElement instanceof FocusableEmbedBlot;
        if (this.quill.hasFocus() || focusItemIsEmbedBlot) {
            event.preventDefault();
            // Focus the next available editor ui component.
            const selection = this.quill.getSelection();
            if (!focusItemIsEmbedBlot) {
                if (selection && selection.length > 0) {
                    this.inlineToolbarFirstActiveItem.focus();
                } else {
                    this.paragraphMenuHandle.focus();
                }
            } else {
                this.emojiPickerButton.focus();
            }
            return false;
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
     * Once you are outside of the editor there is nothing to worry about.
     * This only affects while the cursor is in the editor or inside of an embed blot.
     */
    private handleShiftTab = (event: KeyboardEvent) => {
        if (
            !KeyboardModule.match(event, {
                key: KeyboardModule.keys.TAB,
                shiftKey: true,
            })
        ) {
            return true;
        }

        if (!this.ensureEditorElements()) {
            return true;
        }

        const definiteLastItemIsFocused = [this.paragraphMenuHandle, this.inlineToolbarFirstActiveItem].includes(
            document.activeElement as HTMLElement,
        );
        const emojiPickerIsConditionallyFocused =
            document.activeElement === this.emojiPickerButton &&
            this.paragraphMenuHandle.classList.contains("isHidden");

        if (definiteLastItemIsFocused || emojiPickerIsConditionallyFocused) {
            event.preventDefault();
            // Focus the last item in the editor whether it is normal text or an embed blot.
            const selection = this.quill.getSelection();
            const documentLength = this.quill.scroll.length();
            const newIndex = selection ? selection.index + selection.length : documentLength - 1;
            const lastEmbedBlot = getBlotAtIndex(this.quill, newIndex, FocusableEmbedBlot);
            if (lastEmbedBlot) {
                this.focusEmbedBlot(lastEmbedBlot);
            } else {
                this.quill.focus();
                this.quill.setSelection(newIndex, 0, Quill.sources.USER);
            }
        }

        return true;
    };

    /**
     * Handle delete and backspace presses while an Embed is focussed.
     *
     * @if
     * - Backspace or Delete is pressed
     * - An Embed blot is focused
     *
     * @then
     * - Delete the embed blot.
     */
    private handleDeleteOnEmbed = (event: KeyboardEvent) => {
        if (!this.isKeyCodeDelete(event.keyCode)) {
            return true;
        }

        const blotForActiveElement = this.getEmbedBlotForFocusedElement();
        const focusItemIsEmbedBlot = blotForActiveElement instanceof FocusableEmbedBlot;
        if (blotForActiveElement && focusItemIsEmbedBlot) {
            event.preventDefault();
            const offset = blotForActiveElement.offset();
            blotForActiveElement.remove();
            this.quill.update(Quill.sources.USER);

            const [potentialNewEmbedToFocus] = this.quill.scroll.descendant(FocusableEmbedBlot as any, offset);
            if (potentialNewEmbedToFocus) {
                this.focusEmbedBlot(potentialNewEmbedToFocus as any);
            } else {
                this.quill.setSelection(offset, 0, Quill.sources.USER);
            }

            return false;
        }

        return true;
    };

    /**
     * Handle enter presses while an embed is selected
     *
     * @if
     * - Enter is pressed
     * - An Embed blot is focused.
     *
     * @then
     * - Insert a newline after the embed.
     */
    private handleEnterOnEmbed = (event: KeyboardEvent) => {
        if (event.keyCode !== KeyboardModule.keys.ENTER) {
            return true;
        }

        // Only works for items scoped within itself.
        const blotForActiveElement = this.getEmbedBlotForFocusedElement();
        const focusItemIsEmbedBlot = blotForActiveElement instanceof FocusableEmbedBlot;
        if (blotForActiveElement && focusItemIsEmbedBlot) {
            event.preventDefault();
            const newBlot = Parchment.create("block", "");
            newBlot.insertInto(this.quill.scroll, blotForActiveElement.next);
            this.quill.update(Quill.sources.USER);
            this.quill.setSelection(blotForActiveElement.offset() + 1, 0, Quill.sources.USER);
            return false;
        }

        return true;
    };

    /**
     * Handle arrow keys if the quill document has focus.
     *
     * @if
     * - An arrow key is pressed
     * - The quill document has focus
     * - The next or previous item in the document is an Embed
     *
     * @then
     * - Set focus on that embed.
     */
    private handleArrowKeyAwayFromQuill = (event: KeyboardEvent) => {
        if (!this.isKeyCodeArrowKey(event.keyCode) || event.shiftKey) {
            return true;
        }

        if (document.activeElement === this.quill.root) {
            const [currentBlot] = this.quill.getLine(this.quill.getSelection().index);
            const blotToMoveTo = this.findBlotToMoveTo(currentBlot, event.keyCode);

            if (blotToMoveTo instanceof FocusableEmbedBlot) {
                this.focusEmbedBlot(blotToMoveTo);
                event.preventDefault();
                return false;
            }
        }

        return true;
    };

    /**
     * Handle arrow keys while an embed is Focused.
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
     * @andif
     * - The next or previous Blot is an Embed Blot
     * @then
     * - Set focus on that Blot
     *
     * @else
     * - Move the focus to the next or previous text content.
     */
    private handleArrowKeyFromEmbed = (event: KeyboardEvent) => {
        if (!this.isKeyCodeArrowKey(event.keyCode)) {
            return true;
        }

        const blotForActiveElement = this.getEmbedBlotForFocusedElement();
        if (!(blotForActiveElement instanceof FocusableEmbedBlot)) {
            return true;
        }

        // Check if we are at the beginning or end of the scroll.
        const position = blotForActiveElement.offset();
        const isEndOfScroll = position + blotForActiveElement.length() === this.quill.scroll.length();
        const isStartOfScroll = position === 0;
        const isUpOrLeft = [KeyboardModule.keys.LEFT, KeyboardModule.keys.UP].includes(event.keyCode as any);
        const isDownOrRight = [KeyboardModule.keys.RIGHT, KeyboardModule.keys.DOWN].includes(event.keyCode as any);
        if (isStartOfScroll && isUpOrLeft) {
            event.preventDefault();
            insertNewLineAtStartOfScroll(this.quill);
            return false;
        } else if (isEndOfScroll && isDownOrRight) {
            event.preventDefault();
            insertNewLineAtEndOfScroll(this.quill);
            return false;
        }

        // Check if we have a blot to move to.
        const blotToMoveTo = this.findBlotToMoveTo(blotForActiveElement, event.keyCode);
        if (!blotToMoveTo) {
            return true;
        }

        event.preventDefault();

        if (blotToMoveTo instanceof FocusableEmbedBlot) {
            this.focusEmbedBlot(blotToMoveTo);
        } else {
            // We want to mimic normal movement behaviour as if our Blot was text, so
            // We check if we need to put the cursor in the middle of the next or previous line.
            const newElementStart = blotToMoveTo.offset();
            const newElementEnd = newElementStart + blotToMoveTo.length();
            const previousIndex = this.lastSelection.index;
            const shouldUsePreviousIndex = previousIndex >= newElementStart && previousIndex < newElementEnd;
            const newIndex = shouldUsePreviousIndex ? previousIndex : newElementStart;
            this.quill.setSelection(newIndex, 0);
        }

        return false;
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

    /**
     * Focus an Embed blot.
     */
    private focusEmbedBlot(blot: FocusableEmbedBlot) {
        const blotPosition = blot.offset();
        this.quill.setSelection(blotPosition, 0, Quill.sources.SILENT);
        blot.focus();
    }

    /**
     * Check if the focused element in the document is of an Embed blot and return it.
     */
    private getEmbedBlotForFocusedElement() {
        if (!(document.activeElement instanceof Element)) {
            return;
        }

        let activeElement = document.activeElement;
        if (!activeElement.classList.contains("embed")) {
            const closestEmbed = activeElement.closest(".embed");
            if (!closestEmbed) {
                return;
            }

            activeElement = closestEmbed;
        }

        return Parchment.find(activeElement);
    }

    /**
     * Determine which Blot we want to move to based on the arrow key pressed.
     *
     * @param currentBlot The blot to check.
     * @param keyCode The keycode that was pressed.
     */
    private findBlotToMoveTo(currentBlot: Blot, keyCode: number) {
        switch (keyCode) {
            case KeyboardModule.keys.DOWN:
                return currentBlot.next as Blot;
            case KeyboardModule.keys.UP:
                return currentBlot.prev as Blot;
            case KeyboardModule.keys.RIGHT:
                // -1 needed for because end of blot is non-inclusive.
                const endOfBlot = currentBlot.offset() + currentBlot.length() - 1;
                const currentBlotOffset = currentBlot.offset();
                const currentBlotLength = currentBlot.length();
                const currentSelection = this.quill.getSelection();
                if (this.quill.getSelection().index === endOfBlot) {
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
}
