/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

// Quill
import Theme from "quill/core/theme";
import Keyboard from "quill/modules/keyboard";
import Delta from "quill-delta";
import Emitter from "quill/core/emitter";
import WrapperBlot, { LineBlot } from "./blots/abstract/WrapperBlot";
import CodeBlockBlot from "./blots/CodeBlockBlot";
import { closeEditorFlyouts } from "./quill-utilities";

// React
import React from "react";
import ReactDOM from "react-dom";
import InlineEditorToolbar from "./components/InlineEditorToolbar";
import ParagraphEditorToolbar from "./components/ParagraphEditorToolbar";
import EditorEmojiPicker from "./components/EditorEmojiPicker";

export default class VanillaTheme extends Theme {

    static MULTI_LINE_BLOTS = ['spoiler-line', 'blockquote-line', 'code-block'];

    /** @var {Quill} */
    quill;

    /**
     * Constructor.
     *
     * @param {Quill} quill - The quill instance the theme is applying to.
     * @param {QuillOptionsStatic} options - The current options for the instance.
     */
    constructor(quill, options) {
        const themeOptions = {
            ...options,
            placeholder: "Create a new post...",
        };

        super(quill, themeOptions);
        this.quill.root.classList.add("richEditor-text");
        this.quill.root.classList.add("userContent");
        this.quill.root.addEventListener("focusin", closeEditorFlyouts);

        // Keyboard behaviours
        this.setupTabBehaviour();
        this.setupNewlineBlockEscapes();
        this.setupKeyboardArrowBlockEscapes();
        this.setupBlockDeleteHandler();

        // Mount react components
        this.mountToolbar();
        this.mountEmojiMenu();
        this.mountParagraphMenu();
    }

    /**
     * Nullify the tab key.
     */
    setupTabBehaviour() {
        // Nullify the tab key.
        this.options.modules.keyboard.bindings.tab = false;
    }

    setupBlockDeleteHandler() {
        this.options.modules.keyboard.bindings["Block Escape Backspace"] = {
            key: Keyboard.keys.BACKSPACE,
            collapsed: true,
            format: this.constructor.MULTI_LINE_BLOTS,
            handler: (range) => {
                let [line] = this.quill.getLine(range.index);

                const isOnlyChild = !line.prev && !line.next;

                if (line instanceof LineBlot) {
                    line = line.getContentBlot();
                }

                // Check if this is the first line in the SpoilerContentBlot.
                const isLineEmpty = line.children.length === 1 && line.domNode.textContent === "";

                if (isLineEmpty && isOnlyChild) {
                    const delta = new Delta()
                        .retain(range.index)
                        .delete(1);
                    this.quill.updateContents(delta, Emitter.sources.USER);
                }

                return true;
            },
        };
    }

    /**
     * Add keyboard bindings that allow the user to
     * @private
     */
    setupNewlineBlockEscapes() {
        this.options.modules.keyboard.bindings["Block Escape Enter"] = {
            key: Keyboard.keys.ENTER,
            collapsed: true,
            format: this.constructor.MULTI_LINE_BLOTS,
            handler: (range) => {
                const [line, offset] = this.quill.getLine(range.index);
                const isWrapped = line.parent instanceof WrapperBlot;
                const isNewLine = line.domNode.textContent === "";
                const isPreviousNewline = line.prev && line.prev.domNode.textContent === "";
                const isOnlyNewLine = isNewLine && line.parent.children.length === 1;
                const passesCodeBlockCriteria = !(line instanceof CodeBlockBlot) || isPreviousNewline;

                if (isWrapped && isNewLine && !isOnlyNewLine && passesCodeBlockCriteria) {
                    const positionUpToPreviousNewline = range.index + line.length() - offset;
                    const delta = new Delta()
                        .retain(positionUpToPreviousNewline)
                        .insert("\n", { 'spoiler-line': false, 'blockquote-line': false });
                    this.quill.updateContents(delta, Emitter.sources.USER);

                    // Now we need to clean up that extra newline.
                    const deleteDelta = new Delta()
                        .retain(positionUpToPreviousNewline - 1)
                        .delete(1);
                    this.quill.updateContents(deleteDelta);
                    this.quill.setSelection(positionUpToPreviousNewline - 1);

                    return false;
                } else {
                    return true;
                }
            },
        };

    }

    /**
     * Insert a normal newline before the current range.
     * @private
     *
     * @param {RangeStatic} range - A Quill range.
     *
     * @returns {boolean} false to prevent default.
     */
    insertNewlineBeforeRange(range) {
        // eslint-disable-next-line
        let [line, offset] = this.quill.getLine(range.index);
        const isAtStartOfLine = offset === line.offset();

        if (line instanceof LineBlot) {
            line = line.getWrapperBlot();
        }

        const isFirstBlot = line.parent === line.scroll && line === line.parent.children.head;

        if (isFirstBlot && isAtStartOfLine) {
            // const index = quill.
            const newContents = [
                {
                    insert: "\n",
                },
                ...this.quill.getContents()["ops"],
            ];
            this.quill.setContents(newContents);
        }

        return true;
    }

    /**
     * Insert a normal newline after the current range.
     * @private
     *
     * @param {RangeStatic} range - A Quill range.
     *
     * @returns {boolean} false to prevent default.
     */
    insertNewlineAfterRange(range) {
        // eslint-disable-next-line
        let [line, offset] = this.quill.getLine(range.index);
        const length = line.length();

        // Check that we are at the end of the line.
        const isAtEndOfLine = offset + 1 === length;

        if (line instanceof LineBlot) {
            line = line.getWrapperBlot();
        }

        const isLastBlot = line.parent === line.scroll && line === line.parent.children.tail;

        if (isLastBlot && isAtEndOfLine) {
            // const index = quill.
            const newContents = [
                ...this.quill.getContents()["ops"],
                {
                    insert: "\n",
                },
            ];
            this.quill.setContents(newContents);
            this.quill.setSelection(range.index + 1, 0);
        }

        return true;
    }

    /**
     * Add keyboard bindings that allow the user to escape multi-line blocks with arrow keys.
     */
    setupKeyboardArrowBlockEscapes() {
        const commonCriteria = {
            collapsed: true,
            format: this.constructor.MULTI_LINE_BLOTS,
        };

        this.options.modules.keyboard.bindings["Block Escape Up"] = {
            ...commonCriteria,
            key: Keyboard.keys.UP,
            handler: this.insertNewlineBeforeRange,
        };

        this.options.modules.keyboard.bindings["Block Escape Left"] = {
            ...commonCriteria,
            key: Keyboard.keys.LEFT,
            handler: this.insertNewlineBeforeRange,
        };

        this.options.modules.keyboard.bindings["Block Escape Down"] = {
            ...commonCriteria,
            key: Keyboard.keys.DOWN,
            handler: this.insertNewlineAfterRange,
        };

        this.options.modules.keyboard.bindings["Block Escape Right"] = {
            ...commonCriteria,
            key: Keyboard.keys.RIGHT,
            handler: this.insertNewlineAfterRange,
        };
    }

    /**
     * Mount an inline toolbar (react component).
     */
    mountToolbar() {
        const container = this.quill.container.closest(".richEditor").querySelector(".js-InlineEditorToolbar");
        ReactDOM.render(<InlineEditorToolbar quill={this.quill}/>, container);
    }

    /**
     * Mount the paragraph formatting toolbar (react component).
     */
    mountParagraphMenu() {
        const container = this.quill.container.closest(".richEditor").querySelector(".js-ParagraphEditorToolbar");
        ReactDOM.render(<ParagraphEditorToolbar quill={this.quill}/>, container);
    }

    /**
     * Mount Emoji Menu (react component).
     */
    mountEmojiMenu() {
        const container = this.quill.container.closest(".richEditor").querySelector(".js-emojiHandle");
        ReactDOM.render(<EditorEmojiPicker quill={this.quill}/>, container);
    }
}
