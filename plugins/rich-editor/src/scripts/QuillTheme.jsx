/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

// Quill
import Theme from "quill/core/theme";
import { closeEditorFlyouts } from "./quill-utilities";
import KeyboardBindings from "./KeyboardBindings";
import Parchment from "parchment";

// React
import React from "react";
import ReactDOM from "react-dom";
import InlineEditorToolbar from "./components/InlineEditorToolbar";
import ParagraphEditorToolbar from "./components/ParagraphEditorToolbar";
import EditorEmojiPicker from "./components/EditorEmojiPicker";

import FileUploader from "@core/FileUploader";
import {logError} from "@core/utility";
import EditorProvider from "./components/EditorProvider";

export default class VanillaTheme extends Theme {

    /** @var {Quill} */
    quill;

    /** @var A File:Blot map. */
    currentUploads = new Map();

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

        // Add keyboard bindings to options.
        const keyboardBindings = new KeyboardBindings(this.quill);
        this.options.modules.keyboard.bindings = {
            ...this.options.modules.keyboard.bindings,
            ...keyboardBindings.bindings,
        };

        this.options.modules.embed = true;

        // Mount react components
        this.mountToolbar();
        this.mountEmojiMenu();
        this.mountParagraphMenu();
    }

    /**
     * Mount an inline toolbar (react component).
     */
    mountToolbar() {
        const container = this.quill.container.closest(".richEditor").querySelector(".js-InlineEditorToolbar");
        console.log(this.quill);
        ReactDOM.render(
            <EditorProvider quill={this.quill}>
                <InlineEditorToolbar/>
            </EditorProvider>,
            container
        );
    }

    /**
     * Mount the paragraph formatting toolbar (react component).
     */
    mountParagraphMenu() {
        const container = this.quill.container.closest(".richEditor").querySelector(".js-ParagraphEditorToolbar");
        ReactDOM.render(
            <EditorProvider quill={this.quill}>
                <ParagraphEditorToolbar/>
            </EditorProvider>,
            container
        );
    }

    /**
     * Mount Emoji Menu (react component).
     */
    mountEmojiMenu() {
        const container = this.quill.container.closest(".richEditor").querySelector(".js-emojiHandle");
        ReactDOM.render(
            <EditorProvider quill={this.quill}>
                <EditorEmojiPicker/>
            </EditorProvider>,
            container
        );
    }
}
