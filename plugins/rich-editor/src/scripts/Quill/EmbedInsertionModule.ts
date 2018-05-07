/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Module from "quill/core/module";
import KeyboardModule from "quill/modules/keyboard";
import { closeEditorFlyouts } from "./utility";
import Parchment from "parchment";
import EmbedLoadingBlot from "./Blots/Embeds/LoadingBlot";
import FileUploader from "@core/FileUploader";
import { logError } from "@core/utility";
import { t } from "@core/application";
import Quill, { RangeStatic, Blot } from "quill/core";
import Emitter from "quill/core/emitter";
import api from "@core/apiv2";
import { IEmbedData, renderEmbed } from "@core/embeds";
import ExternalEmbedBlot from "./Blots/Embeds/ExternalEmbedBlot";

/**
 * A Quill module for managing insertion of embeds/loading/error states.
 */
export default class EmbedInsertionModule extends Module {
    private currentUploads: Map<File | string, Blot> = new Map();
    private lastSelection: RangeStatic = { index: 0, length: 0 };
    private pauseSelectionTracking = false;
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

        this.createLoadingEmbed(url);

        api
            .post("/media/scrape", formData)
            .then(result => {
                switch (result.data.type) {
                    case "link":
                        return this.createSiteEmbed(result.data);
                    case "image":
                        return this.createExternalImageEmbed(result.data);
                    default:
                        // this.createErrorEmbed(url, new Error(t("That type of embed is not currently supported.")));
                        return this.createExternalEmbed(result.data);
                }
            })
            .catch(error => {
                if (error.response && error.response.data && error.response.data.message) {
                    const message = error.response.data.message;

                    if (message.startsWith("Failed to load URL")) {
                        return this.createErrorEmbed(
                            url,
                            new Error(t("There was an error processing that embed link.")),
                        );
                    } else {
                        return this.createErrorEmbed(url, new Error(message));
                    }
                }

                this.createErrorEmbed(url, error);
            });
    }

    /**
     * Create a video embed.
     */
    private createVideoEmbed(scrapeResult: IEmbedData) {
        const linkEmbed = Parchment.create("embed-video", scrapeResult);
        const completedBlot = this.currentUploads.get(scrapeResult.url);

        // The loading blot may have been undone/deleted since we created it.
        if (completedBlot) {
            completedBlot.replaceWith(linkEmbed);
        }

        this.currentUploads.delete(scrapeResult.url);
    }

    /**
     * Create a site embed.
     */
    private createSiteEmbed(scrapeResult: IEmbedData) {
        const { url, photoUrl, name, body } = scrapeResult;

        const linkEmbed = Parchment.create("embed-link", {
            url,
            name,
            linkImage: photoUrl,
            excerpt: body,
        });
        const completedBlot = this.currentUploads.get(url);

        // The loading blot may have been undone/deleted since we created it.
        if (completedBlot) {
            completedBlot.replaceWith(linkEmbed);
        }

        this.currentUploads.delete(url);
    }

    /**
     * Create a site embed.
     */
    private createExternalEmbed(scrapeResult: IEmbedData) {
        const completedBlot = this.currentUploads.get(scrapeResult.url);
        return ExternalEmbedBlot.createAsync(scrapeResult).then(externalEmbed => {
            // The loading blot may have been undone/deleted since we created it.
            if (completedBlot) {
                completedBlot.replaceWith(externalEmbed);
            }

            this.currentUploads.delete(scrapeResult.url);
        });
    }

    private createExternalImageEmbed(scrapeResult: IEmbedData) {
        const { url, photoUrl, name } = scrapeResult;

        const linkEmbed = Parchment.create("embed-image", {
            url: photoUrl,
            alt: name,
        });
        const completedBlot = this.currentUploads.get(url);

        // The loading blot may have been undone/deleted since we created it.
        if (completedBlot) {
            completedBlot.replaceWith(linkEmbed);
        }

        this.currentUploads.delete(url);
    }

    /**
     * Transform an loading embed into an error embed.
     *
     * @param lookupKey - The lookup key for the loading embed.
     * @param error - The error thrown from the bad upload.
     */
    private createErrorEmbed = (lookupKey: any, error: Error) => {
        logError(error.message);

        if (lookupKey == null) {
            this.quill.insertEmbed(
                this.lastSelection.index,
                "embed-error",
                { message: error.message },
                Emitter.sources.USER,
            );
            return;
        }

        const errorBlot = Parchment.create("embed-error", { message: error.message });
        const loadingBlot = this.currentUploads.get(lookupKey);

        // The loading blot may have been undone/deleted since we created it.
        if (loadingBlot) {
            loadingBlot.replaceWith(errorBlot);
        }

        this.currentUploads.delete(lookupKey);
    };

    /**
     * Place an loading embed into the document and keep a reference to it.
     *
     * @param lookupKey - The lookup key for the loading embed.
     */
    private createLoadingEmbed = (lookupKey: any) => {
        this.pauseSelectionTracking = true;
        const loadingBlot: EmbedLoadingBlot = Parchment.create("embed-loading", {}) as EmbedLoadingBlot;
        const [currentLine] = this.quill.getLine(this.lastSelection.index);
        const referenceBlot = currentLine.split(this.lastSelection.index);
        loadingBlot.insertInto(this.quill.scroll, referenceBlot);
        this.quill.update(Emitter.sources.USER);

        const newSelection = {
            index: this.lastSelection.index + 2,
            length: 0,
        };
        this.quill.setSelection(newSelection, Quill.sources.USER);

        loadingBlot.registerDeleteCallback(() => {
            if (this.currentUploads.has(lookupKey)) {
                this.currentUploads.delete(lookupKey);
                this.quill.update();

                // Restore the selection.
                this.quill.setSelection(newSelection, Quill.sources.USER);
            }
        });

        this.currentUploads.set(lookupKey, loadingBlot);
        this.pauseSelectionTracking = false;
    };

    /**
     * Setup a selection listener for quill.
     */
    private setupSelectionListener() {
        this.quill.on(Emitter.events.EDITOR_CHANGE, this.handleEditorChange);
    }

    /**
     * Handle changes from the editor.
     *
     * @param type - The event type. See {quill/core/emitter}
     * @param range - The new range.
     */
    private handleEditorChange = (type: string, range: RangeStatic) => {
        if (range && !this.pauseSelectionTracking) {
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
        this.fileUploader = new FileUploader(this.createLoadingEmbed, this.onImageUploadSuccess, this.createErrorEmbed);

        this.quill.root.addEventListener("drop", this.fileUploader.dropHandler, false);
        this.quill.root.addEventListener("paste", this.fileUploader.pasteHandler, false);
        this.setupImageUploadButton();
    }

    /**
     * Handler for a successful image upload.
     *
     * @param file - The file being uploaded.
     * @param response - The axios response from the api request.
     */
    private onImageUploadSuccess = (file: File, response: any) => {
        const imageEmbed = Parchment.create("embed-image", { url: response.data.url });
        const completedBlot = this.currentUploads.get(file);

        // The loading blot may have been undone/deleted since we created it.
        if (completedBlot) {
            completedBlot.replaceWith(imageEmbed);
        }

        this.currentUploads.delete(file);
    };

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
