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

        // Add keyboard bindings to options.
        const keyboardBindings = new KeyboardBindings(this.quill);
        this.options.modules.keyboard.bindings = {
            ...this.options.modules.keyboard.bindings,
            ...keyboardBindings.bindings,
        };

        this.setupImageUploads();

        // Mount react components
        this.mountToolbar();
        this.mountEmojiMenu();
        this.mountParagraphMenu();
    }

    currentUploads = new Map();

    onImageUploadStart = (file) => {
        const selection = this.quill.getSelection();
        const startIndex = selection ? selection.index : this.quill.scroll.length();
        this.quill.insertEmbed(startIndex, "embed-loading", {});
        const [blot] = this.quill.getLine(startIndex);

        blot.registerDeleteCallback(() => {
            if (this.currentUploads.has(file)) {
                this.currentUploads.delete(file);
            }
        });

        this.currentUploads.set(file, blot);
    };

    onImageUploadSuccess = (file, response) => {
        const imageEmbed = Parchment.create("embed-image", { url: response.data.url });
        const completedBlot = this.currentUploads.get(file);

        // The loading blot may have been undone/deleted since we created it.
        if (completedBlot) {
            completedBlot.replaceWith(imageEmbed);
        }

        this.currentUploads.delete(file);
    };

    onImageUploadFailure = (file, error) => {
        logError(error.message);
        const imageEmbed = Parchment.create("embed-error", { errors: [error] });
        const errorBlot = this.currentUploads.get(file);

        // The loading blot may have been undone/deleted since we created it.
        if (errorBlot) {
            errorBlot.replaceWith(imageEmbed);
        }

        errorBlot.replaceWith(imageEmbed);
        this.currentUploads.delete(file);
    };

    setupImageUploads() {
        this.fileUploader = new FileUploader(
            this.onImageUploadStart,
            this.onImageUploadSuccess,
            this.onImageUploadFailure,
        );

        this.quill.root.addEventListener('drop', this.fileUploader.dropHandler, false);
        this.quill.root.addEventListener('paste', this.fileUploader.pasteHandler, false);
        this.setupImageUploadButton();
    }

    setupImageUploadButton() {
        const fakeImageUpload = this.quill.container.closest(".richEditor").querySelector(".js-fakeFileUpload");
        const imageUpload = this.quill.container.closest(".richEditor").querySelector(".js-fileUpload");

        fakeImageUpload.addEventListener("click", () => {
            closeEditorFlyouts();
            imageUpload.click();
        });

        imageUpload.addEventListener("change", () => {
            const file = imageUpload.files[0];
            this.fileUploader.uploadFile(file);
        });
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
