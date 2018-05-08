/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Module from "quill/core/module";
import { closeEditorFlyouts } from "./utility";
import Parchment from "parchment";
import FileUploader from "@core/FileUploader";
import Quill, { RangeStatic, Blot, Sources } from "quill/core";
import api from "@core/apiv2";
import ExternalEmbedBlot from "./Blots/Embeds/ExternalEmbedBlot";
import { IEmbedData } from "@core/embeds";

/**
 * A Quill module for managing insertion of embeds/loading/error states.
 */
export default class EmbedInsertionModule extends Module {
    private lastSelection: RangeStatic = { index: 0, length: 0 };
    private fileUploader: FileUploader;

    constructor(public quill: Quill, options = {}) {
        super(quill, options);
        this.quill = quill;
        this.setupImageUploads();
        this.setupSelectionListener();
    }

    /**
     * Initiate a media scrape, and insert the appropriate embed blots depending on response.
     *
     * @param url - The URL to scrape.
     */
    public scrapeMedia(url: string) {
        const formData = new FormData();
        formData.append("url", url);

        const responseData = api.post("/media/scrape", formData).then(result => result.data);
        this.createEmbed(responseData);
    }

    /**
     * Create an async embed. The embed will be responsible for handling it's loading state and error states.
     *
     * @param dataPromise - A promise that will either return the data needed for rendering, or throw an error.
     */
    private createEmbed = (dataPromise: Promise<IEmbedData>) => {
        const externalEmbed = Parchment.create("embed-external", dataPromise) as ExternalEmbedBlot;
        const [currentLine] = this.quill.getLine(this.lastSelection.index);
        const referenceBlot = currentLine.split(this.lastSelection.index);
        const newSelection = {
            index: this.lastSelection.index + 2,
            length: 0,
        };
        externalEmbed.insertInto(this.quill.scroll, referenceBlot);
        externalEmbed.registerLoadCallback(() => {
            // This LOVELY null selection then setImmediate call are needed because the Twitter embed
            // seems to resolve it's promise before it's fully rendered. As a result the paragraph menu
            // position would get set based on the unrendered twitter card height.
            this.quill.setSelection(null as any, Quill.sources.USER);
            setImmediate(() => {
                this.quill.setSelection(newSelection, Quill.sources.USER);
            });
        });
        this.quill.update(Quill.sources.USER);
        this.quill.setSelection(newSelection, Quill.sources.USER);
    };

    /**
     * Setup a selection listener for quill.
     */
    private setupSelectionListener() {
        this.quill.on(Quill.events.EDITOR_CHANGE, this.handleEditorChange);
    }

    /**
     * Handle changes from the editor.
     *
     * @param type - The event type. See {quill/core/emitter}
     * @param range - The new range.
     */
    private handleEditorChange = (type: string, range: RangeStatic, oldRange: RangeStatic, source: Sources) => {
        if (
            range &&
            (type === Quill.events.SELECTION_CHANGE || type === Quill.events.TEXT_CHANGE) &&
            source !== Quill.sources.SILENT
        ) {
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
     */
    private setupImageUploads() {
        this.fileUploader = new FileUploader(this.createEmbed);
        this.quill.root.addEventListener("drop", this.fileUploader.dropHandler, false);
        this.quill.root.addEventListener("paste", this.fileUploader.pasteHandler, false);
        this.setupImageUploadButton();
    }

    /**
     * Setup the the fake file input for image uploads.
     */
    private setupImageUploadButton() {
        const fakeImageUpload = this.quill.container.closest(".richEditor")!.querySelector(".js-fakeFileUpload");
        const imageUpload = this.quill.container.closest(".richEditor")!.querySelector(".js-fileUpload");

        if (fakeImageUpload && imageUpload instanceof HTMLInputElement) {
            fakeImageUpload.addEventListener("click", () => {
                closeEditorFlyouts("imageUploadButton");
                imageUpload.click();
            });

            imageUpload.addEventListener("change", () => {
                const file = imageUpload.files && imageUpload.files[0];

                if (file) {
                    this.fileUploader.uploadFile(file);
                }
            });
        }
    }
}
