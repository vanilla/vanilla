/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Module from "quill/core/module";
import Parchment from "parchment";
import Quill, { RangeStatic, Sources } from "quill/core";
import api, { uploadImage } from "@dashboard/apiv2";
import { getPastedImage, getDraggedImage } from "@dashboard/dom";
import ExternalEmbedBlot, { IEmbedValue } from "./blots/embeds/ExternalEmbedBlot";
import { IEmbedData } from "@dashboard/embeds";

/**
 * A Quill module for managing insertion of embeds/loading/error states.
 */
export default class EmbedInsertionModule extends Module {
    private lastSelection: RangeStatic = { index: 0, length: 0 };

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

        const scrapePromise = api.post("/media/scrape", formData).then(result => result.data);
        this.createEmbed({
            loaderData: {
                type: "link",
                link: url,
                loadedCount: 0,
            },
            dataPromise: scrapePromise,
        });
    }

    /**
     * Create an async embed. The embed will be responsible for handling it's loading state and error states.
     */
    public createEmbed = (embedValue: IEmbedValue, callback?: () => void) => {
        const externalEmbed = Parchment.create("embed-external", embedValue) as ExternalEmbedBlot;
        const [currentLine] = this.quill.getLine(this.lastSelection.index);
        const referenceBlot = currentLine.split(this.lastSelection.index);
        const newSelection = {
            index: this.lastSelection.index + 2,
            length: 0,
        };
        this.quill.update(Quill.sources.USER);
        externalEmbed.insertInto(this.quill.scroll, referenceBlot);
        externalEmbed.registerLoadCallback(() => {
            // This LOVELY null selection then setImmediate call are needed because the Twitter embed
            // seems to resolve it's promise before it's fully rendered. As a result the paragraph menu
            // position would get set based on the unrendered twitter card height.
            this.quill.setSelection(null as any, Quill.sources.USER);
            setImmediate(() => {
                this.quill.setSelection(newSelection, Quill.sources.USER);
                callback && callback();
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

    private pasteHandler = (event: ClipboardEvent) => {
        const image = getPastedImage(event);
        if (image) {
            const imagePromise = uploadImage(image);
            this.createEmbed({ loaderData: { type: "image", loadedCount: 0 }, dataPromise: imagePromise });
        }
    };

    private dragHandler = (event: DragEvent) => {
        const image = getDraggedImage(event);
        if (image) {
            const imagePromise = uploadImage(image);
            this.createEmbed({ loaderData: { type: "image", loadedCount: 0 }, dataPromise: imagePromise });
        }
    };

    /**
     * Setup image upload listeners and handlers.
     */
    private setupImageUploads() {
        this.quill.root.addEventListener("drop", this.dragHandler, false);
        this.quill.root.addEventListener("paste", this.pasteHandler, false);
    }
}
