/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

// Quill
import Quill, { QuillOptionsStatic, Blot } from "quill/core";
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
import EmbedFocusModule from "./EmbedFocusModule";

export default class VanillaTheme extends ThemeBase {
    private currentUploads: Map<File | string, Blot>;
    private jsBodyBoxContainer: Element;

    /**
     * Constructor.
     *
     * @param quill - The quill instance the theme is applying to.
     * @param options - The current options for the instance.
     */
    constructor(quill: Quill, options: QuillOptionsStatic) {
        const themeOptions = {
            ...options,
            placeholder: "Create a new post...",
        };

        super(quill, themeOptions);
        this.currentUploads = new Map();
        this.quill.root.classList.add("richEditor-text");
        this.quill.root.classList.add("userContent");
        this.quill.root.addEventListener("focusin", () => closeEditorFlyouts());

        // Add keyboard bindings to options.
        const embedFocus = new EmbedFocusModule(this.quill, this.options);

        const keyboardBindings = new KeyboardBindings(this.quill);
        this.options.modules.keyboard.bindings = {
            ...this.options.modules.keyboard.bindings,
            ...keyboardBindings.bindings,
            ...embedFocus.earlyKeyBoardBindings,
        };

        // Find the editor root.
        this.jsBodyBoxContainer = this.quill.container.closest(".richEditor") as Element;
        if (!this.jsBodyBoxContainer) {
            throw new Error("Could not find .richEditor to mount editor components into.");
        }
    }

    public init() {
        (this.quill as any).embed = this.addModule("embed/insertion");

        // Mount react components
        this.mountToolbar();
        this.mountEmojiMenu();
        this.mountParagraphMenu();
        this.mountEmbedDialogue();
    }

    /**
     * Mount an inline toolbar (react component).
     */
    private mountToolbar() {
        const container = this.jsBodyBoxContainer.querySelector(".js-InlineEditorToolbar");
        ReactDOM.render(
            <EditorProvider quill={this.quill}>
                <InlineToolbar />
            </EditorProvider>,
            container,
        );
    }

    /**
     * Mount the paragraph formatting toolbar (react component).
     */
    private mountParagraphMenu() {
        const container = this.jsBodyBoxContainer.querySelector(".js-ParagraphEditorToolbar");
        ReactDOM.render(
            <EditorProvider quill={this.quill}>
                <ParagraphToolbar />
            </EditorProvider>,
            container,
        );
    }

    /**
     * Mount Emoji Menu (react component).
     */
    private mountEmojiMenu() {
        const container = this.jsBodyBoxContainer.querySelector(".js-emojiHandle");
        ReactDOM.render(
            <EditorProvider quill={this.quill}>
                <EmojiPicker />
            </EditorProvider>,
            container,
        );
    }

    private mountEmbedDialogue() {
        const container = this.jsBodyBoxContainer.querySelector(".js-EmbedDialogue");
        ReactDOM.render(
            <EditorProvider quill={this.quill}>
                <EmbedDialogue />
            </EditorProvider>,
            container,
        );
    }
}
