/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Quill, { RangeStatic, Blot } from "quill/core";
import KeyboardModule, { BindingObject, HandlerContext } from "quill/modules/keyboard";
import Delta from "quill-delta";
import Emitter from "quill/core/emitter";
import {
    isBlotFirstInScroll,
    stripFormattingFromFirstBlot,
    insertNewLineAfterBlotAndTrim,
    insertNewLineAtEndOfScroll,
    insertNewLineAtStartOfScroll,
    rangeContainsBlot,
} from "@rich-editor/quill/utility";
import { isAllowedUrl } from "@library/utility/appUtils";
import LineBlot from "@rich-editor/quill/blots/abstract/LineBlot";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import EmbedInsertionModule from "@rich-editor/quill/EmbedInsertionModule";
import BlockBlot from "quill/blots/block";
import CodeBlot from "@rich-editor/quill/blots/inline/CodeBlot";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";
import { ListLineBlot } from "@rich-editor/quill/blots/lists/ListLineBlot";
import Formatter from "@rich-editor/quill/Formatter";
import LinkBlot from "quill/formats/link";
import { SelectableEmbedBlot } from "@rich-editor/quill/blots/abstract/SelectableEmbedBlot";

export default class KeyboardBindings {
    private static MULTI_LINE_BLOTS = [
        SpoilerLineBlot.blotName,
        BlockquoteLineBlot.blotName,
        CodeBlockBlot.blotName,
        ListLineBlot.blotName,
    ];
    public bindings: {
        [key: string]: BindingObject;
    } = {};

    constructor(private quill: Quill) {
        // Keyboard behaviours
        this.resetDefaultBindings();
        this.addFormatEscapeHandlers();
        this.addBlockNewLineHandlers();
        this.addBlockArrowKeyHandlers();
        this.addBlockBackspaceHandlers();
        this.addLinkTransformKeyboardBindings();
        this.overwriteFormatHandlers();
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
    public handleMultilineEnter = (range: RangeStatic) => {
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
    public handleCodeBlockEnter = (range: RangeStatic) => {
        const [line] = this.quill.getLine(range.index);
        if (!(line instanceof CodeBlockBlot)) {
            return;
        }

        const { textContent } = line.domNode;
        const codeEndsWithNewlines = /\n\n\n$/.test(textContent ?? "");
        const endOfLine = line.offset(this.quill.scroll) + line.length() - 1;

        if (!codeEndsWithNewlines || range.index !== endOfLine) {
            return true;
        }

        insertNewLineAfterBlotAndTrim(this.quill, range, 2);

        return false;
    };

    /**
     * Handle backspacing for multi-line blots.
     *
     * @param range - The range that was altered.
     *
     * @returns False to prevent default.
     */
    public handleMultiLineBackspace(range: RangeStatic) {
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
    public handleCodeBlockBackspace(range: RangeStatic) {
        const [line] = this.quill.getLine(range.index);

        // Check if this is an empty code block.
        const { textContent } = line.domNode;

        if (textContent !== "\n") {
            return true;
        }

        const delta = new Delta().retain(range.index).retain(1, { [CodeBlockBlot.blotName]: false });
        this.quill.updateContents(delta, Emitter.sources.USER);

        return false;
    }

    /**
     * Strips the formatting from the first Blot if it is a block-quote, codeBlock, or spoiler.
     *
     * @param range - The range that was altered.
     *
     * @returns False to prevent default.
     */
    public handleBlockStartDelete = (range: RangeStatic) => {
        const [line] = this.quill.getLine(range.index);

        if (!isBlotFirstInScroll(line, this.quill)) {
            return true;
        }

        stripFormattingFromFirstBlot(this.quill);
        // Return false to prevent default behaviour.
        return false;
    };

    /**
     * Insert a normal newline before the current range.
     *
     * @param range - A Quill range.
     *
     * @returns false to prevent default.
     */
    public insertNewlineBeforeRange(range: RangeStatic) {
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
    public insertNewlineAfterRange(range: RangeStatic) {
        const isAtLastPosition = range.index + 1 === this.quill.scroll.length();
        if (isAtLastPosition) {
            insertNewLineAtEndOfScroll(this.quill);
        }

        return true;
    }

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
            line instanceof LineBlot || line instanceof CodeBlockBlot || line instanceof SelectableEmbedBlot;

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
     * Nullify the tab key and remove a weird code block binding for consistency.
     */
    private resetDefaultBindings() {
        // Nullify the tab key.
        (this.bindings as any).tab = false;
        this.bindings["indent code-block"] = false;
        this.bindings["outdent code-block"] = false;
        this.bindings["remove tab"] = false;
        this.bindings["code exit"] = false;
        this.bindings.indent = false;
        this.bindings.outdent = false;
        this.bindings["outdent backspace"] = false;
        this.bindings["list empty enter"] = false;
        this.bindings["checklist enter"] = false;
    }

    private addLinkTransformKeyboardBindings() {
        this.bindings["transform text to embed"] = {
            key: KeyboardModule.keys.ENTER,
            collapsed: true,
            handler: this.transformLinkOnlyLineToEmbed,
        };
    }

    /**
     * Add keyboard bindings for removing the current formatting when switching lines.
     *
     * Expected scenarios are explained here:
     * @see https://github.com/vanilla/vanilla-cloud/pull/1293
     */
    private addFormatEscapeHandlers() {
        // Exit a link form if you type space.
        // https://github.com/vanilla/support/issues/1440
        this.bindings["Link Space"] = {
            key: 32, // Space
            collapsed: true,
            format: ["link"],
            handler: (range: RangeStatic) => {
                const [linkBlot, linkOffset] = this.quill.scroll.descendant(
                    (blot: Blot) => blot instanceof LinkBlot,
                    range.index - 1, // Account for the inserted space.
                );

                if (linkBlot && linkOffset === linkBlot.length() - 1) {
                    // If our cursor is at the end of the link.
                    this.quill.format("link", false);
                }
                return true;
            },
        };

        // Prevent you from being stuck in an infinitely continuing inline code.
        // Eg.
        this.bindings["Inline Code End of Line Right"] = {
            key: KeyboardModule.keys.RIGHT,
            collapsed: true,
            format: [CodeBlot.blotName],
            handler: (range: RangeStatic) => {
                const [line, lineOffset] = this.quill.getLine(range.index);
                if (lineOffset === line.length() - 1) {
                    this.quill.format(CodeBlot.blotName, false);
                    this.quill.insertText(range.index, " ", Quill.sources.USER);
                    this.quill.setSelection(range.index + 1, 0);
                    return false; // Prevent default action.
                }
                return true;
            },
        };

        // Prevent an inline code from extending from one line to another.
        this.bindings["Inline Format End of Line Enter"] = {
            key: KeyboardModule.keys.ENTER,
            collapsed: true,
            format: Formatter.INLINE_FORMAT_NAMES,
            handler: (range: RangeStatic) => {
                const [blot, blotOffset] = this.quill.scroll.descendant(
                    (blot: Blot) => Formatter.INLINE_FORMAT_NAMES.includes(blot.statics.blotName),
                    range.index - 1, // Account for the inserted newline.
                );

                if (blot && blotOffset === blot.length() - 1) {
                    // We're at the end of line. Don't allow inline formats to pass onto the next line.

                    setImmediate(() => {
                        // Set Immediate needed to run after quill's built-in enter handler.
                        Formatter.INLINE_FORMAT_NAMES.forEach((formatName) => {
                            this.quill.format(formatName, false, Quill.sources.API);
                        });
                    });
                }
                return true;
            },
        };
    }

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
            format: [CodeBlockBlot.blotName],
            handler: this.handleCodeBlockEnter,
        };

        this.bindings["List Enter"] = {
            key: KeyboardModule.keys.ENTER,
            collapsed: true,
            format: [ListLineBlot.blotName],
            handler: (range: RangeStatic) => {
                const formatter = new Formatter(this.quill, range);
                const listItems = formatter.getListItems();

                let handled = false;
                listItems.forEach((item) => {
                    if (item.domNode.textContent === "") {
                        if (item.getValue().depth > 0) {
                            item.outdent();
                        } else {
                            item.replaceWith("block", "");
                        }
                        handled = true;
                    }
                });

                if (handled) {
                    this.quill.update(Quill.sources.SILENT);
                    this.quill.setSelection(range, Quill.sources.USER);
                    return false;
                } else {
                    return true;
                }
            },
        };
    }

    /**
     * Transform plain text of a link alone on its own line with no other formatting into a link embed.
     */
    private transformLinkOnlyLineToEmbed = (range: RangeStatic) => {
        const line: Blot = this.quill.getLine(range.index)[0];

        // Bail out if we weren't at the end of the line.
        if (range.index < line.offset() + line.length() - 1) {
            return true;
        }

        // Bail out if blot isn't a plain Block.
        if (line.statics.blotName !== "block") {
            return true;
        }

        // Bail out if the blot contents are not plain text or a link.
        const firstBlotName = (line as any).children.head.statics.blotName;
        if ((line as BlockBlot).children.length > 1 || !["text", "link"].includes(firstBlotName)) {
            return true;
        }

        let textContent = line.domNode.textContent || "";
        textContent = textContent.trim();
        if (isAllowedUrl(textContent)) {
            const embedInsertionModule: EmbedInsertionModule = this.quill.getModule("embed/insertion");
            const index = line.offset();
            this.quill.deleteText(index, line.length(), Quill.sources.USER);
            this.quill.insertText(index, "\n", Quill.sources.USER);
            this.quill.setSelection(index, 0, Quill.sources.USER);
            embedInsertionModule.scrapeMedia(textContent);
            return false;
        }

        return true;
    };

    /**
     * Overwrite quill's built in format handlers.
     */
    private overwriteFormatHandlers() {
        this.bindings.bold = this.makeFormatHandler("bold");
        this.bindings.italic = this.makeFormatHandler("italic");
        this.bindings.underline = this.makeFormatHandler("underline");
    }

    /**
     * Create a keyboard shortcut to enable/disable a format. These differ from Quill's built in
     * keyboard shortcuts because they do not work if the selection contains a codeBlock or inline-code.
     */
    private makeFormatHandler(format): BindingObject {
        return {
            key: format[0].toUpperCase(),
            shortKey: true,
            handler: (range: RangeStatic, context: HandlerContext) => {
                if (
                    rangeContainsBlot(this.quill, CodeBlockBlot, range) ||
                    rangeContainsBlot(this.quill, CodeBlot, range)
                ) {
                    return;
                }

                this.quill.format(format, !context.format![format], Quill.sources.USER);
            },
        };
    }

    /**
     * Add custom handlers for backspace inside of Blots.
     */
    private addBlockBackspaceHandlers() {
        const handleListBackspace = (range: RangeStatic) => {
            const formatter = new Formatter(this.quill, range);
            const listItem = formatter.getListItems()[0];
            if (listItem instanceof ListLineBlot && !listItem.domNode.textContent) {
                // We have an empty list item and we are at the start of it.
                if (listItem.hasNestedList()) {
                    listItem.outdent(true);
                    this.quill.update(Quill.sources.USER);
                }
                this.quill.deleteText(listItem.offset(this.quill.scroll), listItem.length());
                const newPosition = range.index - 1;
                if (newPosition > 0) {
                    this.quill.setSelection({ index: newPosition, length: 0 }, Quill.sources.USER);
                }
                return false;
            }
            return true;
        };

        this.bindings["List Backspace"] = {
            key: KeyboardModule.keys.BACKSPACE,
            offset: 0,
            collapsed: true,
            format: [ListLineBlot.blotName],
            handler: handleListBackspace,
        };
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
            format: [CodeBlockBlot.blotName],
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
}
