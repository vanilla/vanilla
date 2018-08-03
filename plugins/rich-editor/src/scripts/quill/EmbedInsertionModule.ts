/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Module from "quill/core/module";
import Parchment from "parchment";
import Quill, { RangeStatic } from "quill/core";
import api, { uploadImage } from "@dashboard/apiv2";
import { getPastedImage, getDraggedImage } from "@dashboard/dom";
import ExternalEmbedBlot, { IEmbedValue } from "./blots/embeds/ExternalEmbedBlot";
import getStore from "@dashboard/state/getStore";
import { getIDForQuill, insertBlockBlotAt } from "@rich-editor/quill/utility";
import { IStoreState } from "@rich-editor/@types/store";
import { IQuoteEmbedData } from "@dashboard/embeds";
import { ICommentEmbed, IDiscussionEmbed } from "@dashboard/@types/api";

/**
 * A Quill module for managing insertion of embeds/loading/error states.
 */
export default class EmbedInsertionModule extends Module {
    private store = getStore<IStoreState>();

    constructor(public quill: Quill, options = {}, editorID) {
        super(quill, options);
        this.quill = quill;
        this.setupImageUploads();
    }

    private get state() {
        const id = getIDForQuill(this.quill);
        return this.store.getState().editor.instances[id];
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

    public async insertQuoteEmbed(resourceType: "comment" | "discussion", resourceID: number) {
        // const value = api.get(`/comments/${resourceID}/embed`);
        // console.log(value);
        const data: IEmbedValue = {
            loaderData: {
                url: "Loading Quote Data",
                type: "link",
            },
            dataPromise: this.getQuoteEmbedData(resourceType, resourceID),
        };
        // this.createEmbed(data);
    }

    /**
     * Create an async embed. The embed will be responsible for handling it's loading state and error states.
     */
    public createEmbed = (embedValue: IEmbedValue) => {
        const externalEmbed = Parchment.create("embed-external", embedValue) as ExternalEmbedBlot;
        const selection = this.state.lastGoodSelection || { index: this.quill.scroll.length(), length: 0 };
        insertBlockBlotAt(this.quill, selection.index, externalEmbed);
        this.quill.update(Quill.sources.USER);
        externalEmbed.focus();
    };

    private async getQuoteEmbedData(resourceType: "comment" | "discussion", resourceID: number) {
        const response = await api.get(`/${resourceType}s/${resourceID}/embed`);

        console.log(response);

        // const post: IPost = response.data;
        // const editPost: IPostEdit = editResponse.data;

        // const quoteData: IQuoteEmbedData = {
        //     type: "quote",
        //     url: post.url,
        //     name: "name" in post ? post.name : undefined,
        //     attributes: {
        //         subDelta: JSON.parse(editPost.body),
        //         userName: post.insertUser.name,
        //         userPhoto: post.insertUser.photoUrl,
        //         timestamp: post.dateUpdated || post.dateInserted,
        //         resourceID,
        //         resourceType,
        //     },
        // };

        // return quoteData;
    }

    private pasteHandler = (event: ClipboardEvent) => {
        const image = getPastedImage(event);
        if (image) {
            const imagePromise = uploadImage(image);
            this.createEmbed({ loaderData: { type: "image" }, dataPromise: imagePromise });
        }
    };

    private dragHandler = (event: DragEvent) => {
        const image = getDraggedImage(event);
        if (image) {
            const imagePromise = uploadImage(image);
            this.createEmbed({ loaderData: { type: "image" }, dataPromise: imagePromise });
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
