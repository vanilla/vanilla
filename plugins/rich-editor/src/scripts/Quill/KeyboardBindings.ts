/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill, { RangeStatic, Blot } from "quill/core";
import KeyboardModule from "quill/modules/keyboard";
import Delta from "quill-delta";
import Emitter from "quill/core/emitter";
import {
    isBlotFirstInScroll,
    stripFormattingFromFirstBlot,
    insertNewLineAfterBlotAndTrim,
    insertNewLineAtEndOfScroll,
    insertNewLineAtStartOfScroll,
} from "./utility";
import LineBlot from "./Blots/Abstract/LineBlot";
import CodeBlockBlot from "./Blots/Blocks/CodeBlockBlot";
import FocusableEmbedBlot from "./Blots/Abstract/FocusableEmbedBlot";
import Parchment from "parchment";

export default class KeyboardBindings {
    private static MULTI_LINE_BLOTS = ["spoiler-line", "blockquote-line", "code-block"];

    private bindings = {};

    constructor(private quill: Quill) {
        // Keyboard behaviours
        this.resetDefaultBindings();
        this.addBlockNewLineHandlers();
        this.addBlockArrowKeyHandlers();
        this.addBlockBackspaceHandlers();
    }

    /**
     * Nullify the tab key and remove a weird code block binding for consistency.
     */
    private resetDefaultBindings() {
        // Nullify the tab key.
        (this.bindings as any).tab = false;
        this.bindings["indent code-block"] = false;
        this.bindings["outdent code-block"] = false;
        this.bindings["remove tab"] = false;
        this.bindings["code exit"] = false;
        this.bindings["embed left"] = false;
        this.bindings["embed right"] = false;
    }

    /**
     * Add custom handlers for backspace inside of Blots.
     */
    private addBlockBackspaceHandlers() {
        this.bindings["Block Backspace With Selection"] = {
            key: KeyboardModule.keys.BACKSPACE,
            collapsed: false,
            handler: this.clearFirstPositionMultiLineBlot,
        };

        this.bindings["Block Delete"] = {
            key: KeyboardModule.keys.BACKSPACE,
            offset: 0,
            collapsed: true,
            format: KeyboardBindings.MULTI_LINE_BLOTS,
            handler: this.handleBlockStartDelete,
        };

        this.bindings["MultiLine Backspace"] = {
            key: KeyboardModule.keys.BACKSPACE,
            collapsed: true,
            format: ["spoiler-line", "blockquote-line"],
            handler: this.handleMultiLineBackspace,
        };

        this.bindings["CodeBlock Backspace"] = {
            key: KeyboardModule.keys.BACKSPACE,
            collapsed: true,
            format: ["code-block"],
            handler: this.handleCodeBlockBackspace,
        };
    }

    /**
     * Add keyboard options.bindings that allow the user to escape multi-line blocks with arrow keys.
     */
    private addBlockArrowKeyHandlers() {
        const commonCriteria = {
            collapsed: true,
            format: KeyboardBindings.MULTI_LINE_BLOTS,
        };

        this.bindings["Block Up"] = {
            ...commonCriteria,
            key: KeyboardModule.keys.UP,
            handler: this.insertNewlineBeforeRange,
        };

        this.bindings["Block Left"] = {
            ...commonCriteria,
            key: KeyboardModule.keys.LEFT,
            handler: this.insertNewlineBeforeRange,
        };

        this.bindings["Block Down"] = {
            ...commonCriteria,
            key: KeyboardModule.keys.DOWN,
            handler: this.insertNewlineAfterRange,
        };

        this.bindings["Block Right"] = {
            ...commonCriteria,
            key: KeyboardModule.keys.RIGHT,
            handler: this.insertNewlineAfterRange,
        };
    }

    /**
     * Special handling for the ENTER key for Mutliline Blots.
     *
     * @if
     * If there is 1 trailing newline line after the first line,
     * and the user is on the last line,
     * and the user types ENTER,
     *
     * @then
     * Enter a newline after the Blot,
     * move the cursor there,
     * and trim the trailing newlines from the Blot.
     *
     * @param range - The range when the enter key is pressed.
     *
     * @returns False to prevent default.
     */
    private handleMultilineEnter = (range: RangeStatic) => {
        const [line] = this.quill.getLine(range.index);

        const contentBlot = line.getWrapper();
        if (line !== contentBlot.children.tail) {
            return true;
        }

        const { textContent } = line.domNode;
        const currentLineIsEmpty = textContent === "";
        if (!currentLineIsEmpty) {
            return true;
        }

        const previousLine = line.prev;
        if (!previousLine) {
            return true;
        }

        insertNewLineAfterBlotAndTrim(this.quill, range);

        return false;
    };

    /**
     * Special handling for the ENTER key for Code Blocks.
     *
     * @if
     * If there are 2 tailing newlines after the first line,
     * and the user is on the last line,
     * and the user types ENTER,
     *
     * @then
     * Enter a newline after the Blot,
     * move the cursor there,
     * and trim the trailing newlines from the Blot.
     *
     * @param range - The range when the enter key is pressed.
     *
     * @returns False to prevent default.
     */
    private handleCodeBlockEnter = (range: RangeStatic) => {
        const [line] = this.quill.getLine(range.index);

        const { textContent } = line.domNode;
        const currentLineIsEmpty = /\n\n\n$/.test(textContent);
        if (!currentLineIsEmpty) {
            return true;
        }

        insertNewLineAfterBlotAndTrim(this.quill, range, 2);

        return false;
    };

    /**
     * Add keyboard options.bindings that allow the user to
     */
    private addBlockNewLineHandlers() {
        this.bindings["MutliLine Enter"] = {
            key: KeyboardModule.keys.ENTER,
            collapsed: true,
            format: ["spoiler-line", "blockquote-line"],
            handler: this.handleMultilineEnter,
        };

        this.bindings["CodeBlock Enter"] = {
            key: KeyboardModule.keys.ENTER,
            collapsed: true,
            format: ["code-block"],
            handler: this.handleCodeBlockEnter,
        };
    }

    /**
     * Handle backspacing for multi-line blots.
     *
     * @param range - The range that was altered.
     *
     * @returns False to prevent default.
     */
    private handleMultiLineBackspace(range: RangeStatic) {
        const [line] = this.quill.getLine(range.index);

        // Check if this is an empty multi-line blot
        const hasSiblings = line.prev || line.next;

        if (hasSiblings) {
            return true;
        }

        const contentBlot = line.getWrapper();
        if (contentBlot.domNode.textContent !== "") {
            return true;
        }

        const delta = new Delta().retain(range.index).retain(1, { [line.constructor.blotName]: false });
        this.quill.updateContents(delta, Emitter.sources.USER);
        return false;
    }

    /**
     * Handle backspacing for CodeBlock blots.
     *
     * @param range - The range that was altered.
     *
     * @returns False to prevent default.
     */
    private handleCodeBlockBackspace(range: RangeStatic) {
        const [line] = this.quill.getLine(range.index);

        // Check if this is an empty code block.
        const { textContent } = line.domNode;

        if (textContent !== "\n") {
            return true;
        }

        const delta = new Delta().retain(range.index).retain(1, { "code-block": false });
        this.quill.updateContents(delta, Emitter.sources.USER);

        return false;
    }

    /**
     * Strips the formatting from the first Blot if it is a block-quote, code-block, or spoiler.
     *
     * @param range - The range that was altered.
     *
     * @returns False to prevent default.
     */
    private handleBlockStartDelete = (range: RangeStatic) => {
        const [line] = this.quill.getLine(range.index);

        if (!isBlotFirstInScroll(line, this.quill)) {
            return true;
        }

        stripFormattingFromFirstBlot(this.quill);
        // Return false to prevent default behaviour.
        return false;
    };

    /**
     * Delete the entire first Blot if the whole thing and something else is selected.
     *
     * We want deleting all of the content of the Blot to be different from the deleting the whole document or a large part of it.
     *
     * @param range - The range that was altered.
     *
     * @returns False to prevent default.
     */
    private clearFirstPositionMultiLineBlot = (range: RangeStatic) => {
        const [line] = this.quill.getLine(range.index);
        const selection = this.quill.getSelection();

        const rangeStartsBeforeSelection = range.index < selection.index;
        const rangeEndsAfterSelection = range.index + range.length > selection.index + selection.length;
        const isFirstLineSelected = selection.index === 0;
        const selectionIsEntireScroll = isFirstLineSelected;
        const blotMatches =
            line instanceof LineBlot || line instanceof CodeBlockBlot || line instanceof FocusableEmbedBlot;

        if ((rangeStartsBeforeSelection || rangeEndsAfterSelection || selectionIsEntireScroll) && blotMatches) {
            let delta = new Delta();

            const newSelection = range;

            if (isFirstLineSelected) {
                delta = delta.insert("\n");
                newSelection.length += 1;
            }

            this.quill.updateContents(delta, Emitter.sources.USER);
            this.quill.setSelection(newSelection);
            stripFormattingFromFirstBlot(this.quill);
            this.quill.setSelection(newSelection);
        }

        return true;
    };

    /**
     * Insert a normal newline before the current range.
     *
     * @param range - A Quill range.
     *
     * @returns false to prevent default.
     */
    private insertNewlineBeforeRange(range: RangeStatic) {
        const cursorAtFirstPosition = range.index === 0;

        if (cursorAtFirstPosition) {
            insertNewLineAtStartOfScroll(this.quill);
        }

        return true;
    }

    /**
     * Insert a normal newline after the current range.
     *
     * @param range - A Quill range.
     *
     * @returns false to prevent default.
     */
    private insertNewlineAfterRange(range: RangeStatic) {
        const isAtLastPosition = range.index + 1 === this.quill.scroll.length();
        if (isAtLastPosition) {
            insertNewLineAtEndOfScroll(this.quill);
        }

        return true;
    }
}
