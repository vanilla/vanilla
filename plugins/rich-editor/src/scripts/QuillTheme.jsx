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

import WrapperBlot from "./blots/WrapperBlot";

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
        this.setupTabBehaviour();
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
        this.options.modules.keyboard.bindings["Block Escape Enter"] = {
            key: Keyboard.keys.ENTER,
            collapsed: true,
            format: ['spoiler-line'],
            handler: (range) => {
                console.log("Line handler");
                const [line, offset] = this.quill.getLine(range.index);
                const isWrapped = line.parent instanceof WrapperBlot;
                const isNewLine = line.domNode.textContent === "";
                const isPrevNewline = line.prev && line.prev.domNode.textContent === "";

                console.log(isNewLine);

                if (isWrapped && isNewLine && isPrevNewline) {
                    const delta = new Delta()
                        .retain(range.index + line.length() - offset)
                        .insert("\n", { 'spoiler-line': null });
                    this.quill.updateContents(delta, Emitter.sources.USER);
                    this.quill.setSelection(range.index + line.length() - offset);

                    return false;
                } else {
                    return true;
                }
            },
        };

        this.options.modules.keyboard.bindings["Block Escape Backspace"] = {
            key: Keyboard.keys.BACKSPACE,
            collapsed: true,
            format: ['spoiler-line'],
            handler: (range) => {
                const [line] = this.quill.getLine(range.index);

                // Check if this is the first line in the SpoilerContentBlot.
                const isFirstLine = line === line.parent.children.head;

                if (isFirstLine) {
                    // The fact that this is always the grandparent of the line is enforced at the Blot level.
                    const spoilerBlot = line.parent.parent;

                    const delta = new Delta()
                        .retain(spoilerBlot.offset())
                        .retain(spoilerBlot.length(), { 'spoiler-line': false });
                    this.quill.updateContents(delta, Emitter.sources.USER);

                    // Return false to prevent default behaviour.
                    return false;
                } else {
                    // Return true to allow default behaviour.
                    return true;
                }
            },
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
