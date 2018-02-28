/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import ReactDOM from "react-dom";
import Theme from "quill/core/theme";
import Keyboard from "quill/modules/keyboard";
import Delta from "quill-delta";
import Emitter from "quill/core/emitter";
import InlineEditorToolbar from "./components/InlineEditorToolbar";
import ParagraphEditorToolbar from "./components/ParagraphEditorToolbar";
import EditorEmojiPicker from "./components/EditorEmojiPicker";
import { closeEditorFlyouts } from "./quill-utilities";

import WrapperBlot, { LineBlot } from "./blots/abstract/WrapperBlot";

export default class VanillaTheme extends Theme {

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
        // this.options.modules.keyboard.bindings["Block Escape Backspace"] = {
        //     key: Keyboard.keys.BACKSPACE,
        //     collapsed: true,
        //     format: ['spoiler-line'],
        //     handler: (range) => {
        //         const [line] = this.quill.getLine(range.index);
        //
        //         // Check if this is the first line in the SpoilerContentBlot.
        //         const isFirstLine = line === line.parent.children.head;
        //
        //         if (isFirstLine) {
        //             // The fact that this is always the grandparent of the line is enforced at the Blot level.
        //             const spoilerBlot = line.parent.parent;
        //
        //             const delta = new Delta()
        //                 .retain(spoilerBlot.offset())
        //                 .retain(spoilerBlot.length(), { 'spoiler-line': false });
        //             this.quill.updateContents(delta, Emitter.sources.USER);
        //
        //             // Return false to prevent default behaviour.
        //             return false;
        //         } else {
        //             // Return true to allow default behaviour.
        //             return true;
        //         }
        //     },
        // };
    }

    /**
     * Add keyboard bindings that allow the user to
     * @private
     */
    setupNewlineBlockEscapes() {
        this.options.modules.keyboard.bindings["Block Escape Enter"] = {
            key: Keyboard.keys.ENTER,
            collapsed: true,
            format: ['spoiler-line', 'blockquote-line'],
            handler: (range) => {
                const [line, offset] = this.quill.getLine(range.index);
                const isWrapped = line.parent instanceof WrapperBlot;
                const isNewLine = line.domNode.textContent === "";
                const isOnlyNewLine = isNewLine && line.parent.children.length === 1;

                if (isWrapped && isNewLine && !isOnlyNewLine) {
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
        let [line] = this.quill.getLine(range.index);

        if (line instanceof LineBlot) {
            line = line.getWrapperBlot();
        }

        const isFirstBlot = line.parent === line.scroll && line === line.parent.head;

        if (isFirstBlot) {
            // const index = quill.
            const delta = new Delta()
                .insert("\n");

            this.quill.updateContents(delta);
        }


        // this.quill.setSelection(positionUpToPreviousNewline - 1);

        return true;
    }

    /**
     * Add keyboard bindings that allow the user to escape multi-line blocks with arrow keys.
     */
    setupKeyboardArrowBlockEscapes() {
        this.options.modules.keyboard.bindings["Block Escape Up"] = {
            key: Keyboard.keys.UP,
            collapsed: true,
            offset: 0, // Only apply if on the first character of a line.
            format: ['spoiler-line', 'blockquote-line', 'code-block'],
            handler: this.insertNewlineBeforeRange,
        };

        this.options.modules.keyboard.bindings["Block Escape Left"] = {
            key: Keyboard.keys.LEFT,
            collapsed: true,
            offset: 0, // Only apply if on the first character of a line.
            format: ['spoiler-line', 'blockquote-line', 'code-block'],
            handler: this.insertNewlineBeforeRange,
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
