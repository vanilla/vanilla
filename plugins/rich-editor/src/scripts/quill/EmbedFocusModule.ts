/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill, { Blot } from "quill/core";
import Parchment from "parchment";
import KeyboardModule from "quill/modules/keyboard";
import Module from "quill/core/module";
import { RangeStatic } from "quill/core";
import { delegateEvent, getNextTabbableElement } from "@dashboard/dom";
import FocusableEmbedBlot from "./blots/abstract/FocusableEmbedBlot";
import { insertNewLineAtEndOfScroll, insertNewLineAtStartOfScroll, getBlotAtIndex } from "./utility";

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

    private editorRoot?: HTMLElement;
    private formWrapper?: HTMLElement;

    constructor(quill: Quill, options = {}) {
        super(quill, options);

        this.editorRoot = this.quill.root.closest(".richEditor") as HTMLElement;
        this.formWrapper = this.editorRoot.closest(".FormWrapper") as HTMLElement;

        // Add event listeners.
        quill.on("selection-change", (range, oldRange, source) => {
            if (range && range.index && source !== Quill.sources.SILENT && this.editorRoot) {
                this.lastSelection = range;
                this.editorRoot.classList.toggle("isFocused", true);
            }
        });
        this.setupEmbedClickHandler();
        this.setupMobileHandler();

        this.quill.root.addEventListener("keydown", this.keyDownListener);
        this.editorRoot.addEventListener("keydown", this.tabListener);
        this.editorRoot.addEventListener("keydown", this.escapeMobileFullScreen);
    }

    public get inActiveMention() {
        const fullDocumentRange = {
            index: 0,
            length: this.quill.scroll.length() - 1,
        };
        return rangeContainsBlot(this.quill, MentionAutoCompleteBlot, fullDocumentRange);
    }

    public escapeMobileFullScreen = (event: KeyboardEvent) => {
        if (!this.editorRoot) {
            return;
        }
        const position = window.getComputedStyle(this.editorRoot).getPropertyValue("position");
        const editorIsFullscreen = this.editorRoot.classList.contains("isFocused") && position === "fixed";
        if (editorIsFullscreen && KeyboardModule.match(event, { key: KeyboardModule.keys.ESCAPE, shiftKey: false })) {
            this.quill.root.focus();
            const nextEl: any = getNextTabbableElement({
                root: this.formWrapper,
                fromElement: this.quill.root,
            });
            nextEl.focus();
            this.editorRoot.classList.toggle("isFocused", false);
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
        const blotForActiveElement = this.getEmbedBlotForFocusedElement();
        const focusItemIsEmbedBlot = blotForActiveElement instanceof FocusableEmbedBlot;
        if (this.quill.hasFocus() || focusItemIsEmbedBlot) {
            // Focus the next available editor ui component.
            const nextElement = getNextTabbableElement({
                root: this.editorRoot,
                excludedRoots: [this.quill.root],
                allowLooping: false,
                fromElement: this.quill.root, // Always move as if from the quill root.
            });

            if (nextElement) {
                nextElement.focus();
                return true;
            }
        }

        return false;
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
    public handleShiftTab = () => {
        const prevElement = getNextTabbableElement({
            root: this.editorRoot.closest("form")!,
            excludedRoots: [this.quill.root],
            reverse: true,
            allowLooping: false,
        });

        if (!prevElement) {
            return false;
        }

        // We need to place the selection at the end of quill.
        if (prevElement === this.quill.root) {
            const lastIndex = this.quill.scroll.length() - 1;
            const lastEmbedBlot = getBlotAtIndex(this.quill, lastIndex, FocusableEmbedBlot);
            if (lastEmbedBlot) {
                this.focusEmbedBlot(lastEmbedBlot);
            } else {
                this.quill.setSelection(lastIndex, 0, Quill.sources.USER);
            }
        } else {
            prevElement.focus();
        }

        return true;
    };

    /**
     * Handle delete and backspace presses while an Embed is focused.
     *
     * @if
     * - Backspace or Delete is pressed
     * - An Embed blot is focused
     *
     * @then
     * - Delete the embed blot.
     */
    public handleDeleteOnEmbed = (blotForActiveElement: FocusableEmbedBlot) => {
        const offset = blotForActiveElement.offset();
        blotForActiveElement.remove();
        this.quill.update(Quill.sources.USER);

        const [potentialNewEmbedToFocus] = this.quill.scroll.descendant(FocusableEmbedBlot as any, offset);
        if (potentialNewEmbedToFocus) {
            this.focusEmbedBlot(potentialNewEmbedToFocus as any);
        } else {
            this.quill.setSelection(offset, 0, Quill.sources.USER);
        }
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
    public handleEnterOnEmbed = (blotForActiveElement: FocusableEmbedBlot) => {
        const newBlot = Parchment.create("block", "");
        newBlot.insertInto(this.quill.scroll, blotForActiveElement.next);
        this.quill.update(Quill.sources.USER);
        this.quill.setSelection(blotForActiveElement.offset() + 1, 0, Quill.sources.USER);
    };

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
     * @andif
     * - The next or previous Blot is an Embed Blot
     * @then
     * - Set focus on that Blot
     *
     * @else
     * - Move the focus to the next or previous text content.
     */
    public handleArrowKeyFromEmbed = (directionKeyCode: number, blotForActiveElement: FocusableEmbedBlot): boolean => {
        // Check if we are at the beginning or end of the scroll.
        const position = blotForActiveElement.offset();
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
        const blotToMoveTo = this.findEmbedBlotToFocus(blotForActiveElement, directionKeyCode);
        if (!blotToMoveTo) {
            return false;
        }

        if (blotToMoveTo instanceof FocusableEmbedBlot) {
            this.focusEmbedBlot(blotToMoveTo);
            return true;
        } else {
            // We want to mimic normal movement behaviour as if our Blot was text, so
            // We check if we need to put the cursor in the middle of the next or previous line.
            const newElementStart = blotToMoveTo.offset();
            const newElementEnd = newElementStart + blotToMoveTo.length();
            const previousIndex = this.lastSelection.index;
            const shouldUsePreviousIndex = previousIndex >= newElementStart && previousIndex < newElementEnd;
            const newIndex = shouldUsePreviousIndex ? previousIndex : newElementStart;
            this.quill.setSelection(newIndex, 0);
            return true;
        }
    };

    /**
     * Check if the focused element in the document is of an Embed blot and return it.
     */
    public getEmbedBlotForFocusedElement() {
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
    public findEmbedBlotToFocus(currentBlot: Blot, keyCode: number) {
        switch (keyCode) {
            case KeyboardModule.keys.DOWN:
                return currentBlot.next as Blot;
            case KeyboardModule.keys.UP:
                return currentBlot.prev as Blot;
            case KeyboardModule.keys.RIGHT:
                // -1 needed for because end of blot is non-inclusive.
                const endOfBlot = currentBlot.offset() + currentBlot.length() - 1;
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

    private setupEmbedClickHandler() {
        delegateEvent(
            "click",
            ".js-richText .embed",
            (event, clickedElement) => {
                const embed = Parchment.find(clickedElement);
                if (embed instanceof FocusableEmbedBlot) {
                    this.focusEmbedBlot(embed);
                    event.preventDefault();
                    event.stopPropagation();
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
                const nextEl: any = getNextTabbableElement({
                    root: this.formWrapper,
                    fromElement: clickedElement,
                });
                nextEl.focus();
                this.editorRoot.classList.toggle("isFocused", false);
            },
            this.editorRoot,
        );
    }

    /**
     * This needs to be exported in set in the quill options, before the keyboard module is instantiated.
     *
     * @returns true if the event was handled
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
    private handleDeleteOnQuill = (): boolean => {
        const [currentBlot] = this.quill.getLine(this.quill.getSelection().index);
        const previousBlot = currentBlot.prev;
        const isPreviousBlotEmbed = previousBlot instanceof FocusableEmbedBlot;
        const isCurrentBlotEmpty = currentBlot.domNode.textContent === "";

        if (isPreviousBlotEmbed && isCurrentBlotEmpty) {
            (currentBlot as Blot).remove();
            this.quill.update(Quill.sources.USER);
            this.focusEmbedBlot(previousBlot);
            return true;
        }

        return false;
    };

    /**
     * Keydown listener on the current quill instance.
     */
    private keyDownListener = (event: KeyboardEvent) => {
        if (!this.quill.container.closest(".richEditor")!.contains(document.activeElement)) {
            return;
        }

        const selection = this.quill.getSelection();
        const blotForActiveElement = this.getEmbedBlotForFocusedElement();
        const [currentLineBlot] = this.quill.getLine(selection.index);
        const blotToMoveTo = this.findEmbedBlotToFocus(currentLineBlot, event.keyCode);

        if (this.isKeyCodeArrowKey(event.keyCode) && !event.shiftKey && !this.inActiveMention) {
            if (blotForActiveElement instanceof FocusableEmbedBlot) {
                const eventWasHandled = this.handleArrowKeyFromEmbed(event.keyCode, blotForActiveElement);

                if (eventWasHandled) {
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
            }

            if (document.activeElement === this.quill.root && blotToMoveTo instanceof FocusableEmbedBlot) {
                event.preventDefault();
                event.stopPropagation();
                return this.focusEmbedBlot(blotToMoveTo);
            }
        }

        if (this.isKeyCodeDelete(event.keyCode)) {
            if (blotForActiveElement instanceof FocusableEmbedBlot) {
                event.preventDefault();
                event.stopPropagation();
                return this.handleDeleteOnEmbed(blotForActiveElement);
            }

            if (selection) {
                const eventWasHandled = this.handleDeleteOnQuill();
                if (eventWasHandled) {
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
            }
        }

        if (event.keyCode === KeyboardModule.keys.ENTER) {
            if (blotForActiveElement instanceof FocusableEmbedBlot) {
                event.preventDefault();
                event.stopPropagation();
                return this.handleEnterOnEmbed(blotForActiveElement);
            }
        }
    };

    /**
     * Keydown listener on the current quill instance.
     */
    private tabListener = (event: KeyboardEvent) => {
        if (!this.editorRoot!.contains(document.activeElement)) {
            return;
        }

        let eventWasHandled = false;

        if (KeyboardModule.match(event, KeyboardModule.keys.TAB)) {
            if (event.shiftKey) {
                eventWasHandled = this.handleShiftTab();
            } else {
                eventWasHandled = this.handleTab();
            }
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

    /**
     * Focus an Embed blot.
     */
    private focusEmbedBlot(blot: FocusableEmbedBlot) {
        const blotPosition = blot.offset();
        this.quill.setSelection(blotPosition, 0, Quill.sources.SILENT);
        blot.focus();
    }
}
