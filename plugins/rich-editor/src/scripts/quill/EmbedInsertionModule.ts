/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Module from "quill/core/module";
import Parchment from "parchment";
import Quill from "quill/core";
import api, { uploadFile } from "@library/apiv2";
import { getPastedFile, getDraggedFile } from "@library/dom/domUtils";
import ExternalEmbedBlot, { IEmbedValue } from "@rich-editor/quill/blots/embeds/ExternalEmbedBlot";
import { insertBlockBlotAt } from "@rich-editor/quill/utility";
import { isFileImage } from "@vanilla/utils";
import ProgressEventEmitter from "@library/utility/ProgressEventEmitter";

/**
 * A Quill module for managing insertion of embeds/loading/error states.
 */
export default class EmbedInsertionModule extends Module {
    constructor(public quill: Quill, options = {}) {
        super(quill, options);
        this.quill = quill;
        this.setupImageUploads();
    }

    /**
     * Initiate a media scrape, and insert the appropriate embed blots depending on response.
     *
     * @param url - The URL to scrape.
     */
    public scrapeMedia(url: string) {
        const formData = new FormData();
        formData.append("url", url);

        const scrapePromise = api.post("/media/scrape", formData).then(result => result.data);
        this.createEmbed({
            loaderData: {
                type: "link",
                link: url,
            },
            dataPromise: scrapePromise,
        });
    }

    /**
     * Create an async embed. The embed will be responsible for handling it's loading state and error states.
     */
    public createEmbed = (embedValue: IEmbedValue) => {
        const externalEmbed = Parchment.create("embed-external", embedValue) as ExternalEmbedBlot;
        const embedPosition = this.quill.getLastGoodSelection().index;
        insertBlockBlotAt(this.quill, embedPosition, externalEmbed);
        this.quill.update(Quill.sources.USER);
        this.quill.setSelection(externalEmbed.offset(this.quill.scroll) + externalEmbed.length(), 0);
    };

    private pasteHandler = (event: ClipboardEvent) => {
        const file = getPastedFile(event);
        if (!file) {
            return;
        }

        if (isFileImage(file)) {
            this.createImageEmbed(file);
        } else {
            this.createFileEmbed(file);
        }
    };

    private dragHandler = (event: DragEvent) => {
        const file = getDraggedFile(event);

        if (!file) {
            return;
        }

        if (isFileImage(file)) {
            this.createImageEmbed(file);
        } else {
            this.createFileEmbed(file);
        }
    };

    public createImageEmbed(file: File) {
        const imagePromise = uploadFile(file).then(data => {
            data.embedType = "image";
            return data;
        });
        this.createEmbed({ loaderData: { type: "image" }, dataPromise: imagePromise });
    }

    public createFileEmbed(file: File) {
        const progressEventEmitter = new ProgressEventEmitter();

        const filePromise = uploadFile(file, { onUploadProgress: progressEventEmitter.emit }).then(data => {
            return {
                url: data.url,
                embedType: "file",
                attributes: data,
            };
        });
        this.createEmbed({ loaderData: { type: "file", file, progressEventEmitter }, dataPromise: filePromise });
    }

    /**
     * Setup image upload listeners and handlers.
     */
    private setupImageUploads() {
        this.quill.root.addEventListener("drop", this.dragHandler, false);
        this.quill.root.addEventListener("paste", this.pasteHandler, false);
    }
}
