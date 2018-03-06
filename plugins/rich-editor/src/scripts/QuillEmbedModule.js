/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

// Quill
import Module from "quill/core/module";
import { closeEditorFlyouts } from "./quill-utilities";
import Parchment from "parchment";
import FileUploader from "@core/FileUploader";
import {logError} from "@core/utility";
import Emitter from "quill/core/emitter";

/**
 * A Quill module for managing insertion of embeds/loading/error states.
 */
export default class QuillEmbedModule extends Module {

    /** @var {Quill} */
    quill;

    /** @var A File:Blot map. */
    currentUploads = new Map();

    /** @var {RangeStatic} */
    lastSelection = { index: 0, length: 0 };

    constructor(quill, options = {}) {
        super(quill, options);
        this.quill = quill;
        this.setupImageUploads();
        this.setupSelectionListener();
    }

    setupSelectionListener() {
        this.quill.on(Emitter.events.EDITOR_CHANGE, this.handleEditorChange);
    }

    /**
     * Handle changes from the editor.
     *
     * @param {string} type - The event type. See {quill/core/emitter}
     * @param {RangeStatic} range - The new range.
     */
    handleEditorChange = (type, range) => {
        if (range) {
            if (typeof range.index !== "number") {
                range = this.quill.getSelection();
            }

            if (range != null) {
                this.lastSelection = range;
            }
        }
    };

    /**
     * Setup image upload listeners and handlers.
     * @private
     */
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

    /**
     * Handler for the beginning of an image upload.
     * @private
     *
     * @param {File} file - The file being uploaded.
     */
    onImageUploadStart = (file) => {
        this.quill.insertEmbed(this.lastSelection.index, "embed-loading", {}, Emitter.sources.USER);
        const [blot] = this.quill.getLine(this.lastSelection.index);
        this.insertTrailingNewline();

        blot.registerDeleteCallback(() => {
            if (this.currentUploads.has(file)) {
                this.currentUploads.delete(file);
            }
        });

        this.currentUploads.set(file, blot);
    };

    /**
     * Handler for a successful image upload.
     * @private
     *
     * @param {File} file - The file being uploaded.
     * @param {Object} response - The axios response from the ajax request.
     */
    onImageUploadSuccess = (file, response) => {
        const imageEmbed = Parchment.create("embed-image", { url: response.data.url });
        const completedBlot = this.currentUploads.get(file);

        // The loading blot may have been undone/deleted since we created it.
        if (completedBlot) {
            completedBlot.replaceWith(imageEmbed);
        }

        this.currentUploads.delete(file);
    };

    /**
     * Handler for a failed image upload.
     * @private
     *
     * @param {File} file - The file being uploaded.
     * @param {Error} error - The error thrown from the bad upload.
     */
    onImageUploadFailure = (file, error) => {
        logError(error.message);

        if (file == null) {
            this.quill.insertEmbed(this.lastSelection.index, "embed-error", { errors: [error] }, Emitter.sources.USER);
            return;
        }

        const errorBlot = Parchment.create("embed-error", { errors: [error] }, Emitter.sources.USER);
        const loadingBlot = this.currentUploads.get(file);

        // The loading blot may have been undone/deleted since we created it.
        if (loadingBlot) {
            loadingBlot.replaceWith(errorBlot);
        }

        loadingBlot.replaceWith(errorBlot);
        this.currentUploads.delete(file);
    };

    /**
     * Setup the the fake file input for image uploads.
     */
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

    insertTrailingNewline() {
        this.quill.setSelection(this.lastSelection.index + 1);
    }
}
