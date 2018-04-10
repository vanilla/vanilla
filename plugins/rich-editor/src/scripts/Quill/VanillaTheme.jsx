/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

// Quill
import ThemeBase from "quill/core/theme";
import { closeEditorFlyouts } from "./utility";
import KeyboardBindings from "./KeyboardBindings";

// React
import React from "react";
import ReactDOM from "react-dom";
import InlineToolbar from "../Editor/InlineToolbar";
import ParagraphToolbar from "../Editor/ParagraphToolbar";
import EmojiPicker from "../Editor/EmojiPicker";
import EmbedDialogue from "../Editor/EmbedDialogue";
import EditorProvider from "../Editor/ContextProvider";

export default class VanillaTheme extends ThemeBase {

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
    }

    init() {
        this.quill.embed = this.addModule("embed/insertion");
        this.quill.embedFocus = this.addModule("embed/focus");

        // Mount react components
        this.mountToolbar();
        this.mountEmojiMenu();
        this.mountParagraphMenu();
        this.mountEmbedDialogue();
    }

    /**
     * Mount an inline toolbar (react component).
     */
    mountToolbar() {
        const container = this.quill.container.closest(".richEditor").querySelector(".js-InlineEditorToolbar");
        ReactDOM.render(
            <EditorProvider quill={this.quill}>
                <InlineToolbar/>
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
                <ParagraphToolbar/>
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
                <EmojiPicker/>
            </EditorProvider>,
            container
        );
    }

    mountEmbedDialogue() {
        const container = this.quill.container.closest(".richEditor").querySelector(".js-EmbedDialogue");
        ReactDOM.render(
            <EditorProvider quill={this.quill}>
                <EmbedDialogue />
            </EditorProvider>,
            container
        );
    }
}
