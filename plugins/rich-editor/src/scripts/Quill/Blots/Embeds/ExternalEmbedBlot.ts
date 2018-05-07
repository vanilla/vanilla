/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { setData, getData } from "@core/dom";
import Parchment from "parchment";
import uniqueId from "lodash/uniqueId";
import LoadingBlot from "../Embeds/LoadingBlot";
import { IEmbedData, renderEmbed } from "@core/embeds";
import FocusableEmbedBlot from "../Abstract/FocusableEmbedBlot";
import ErrorBlot from "./ErrorBlot";
import { t } from "@core/application";
import Quill, { Blot } from "quill/core";

const loadingDataKey = "__loading-data__";

const loaderKeys = new Set();

export default class ExternalEmbedBlot extends FocusableEmbedBlot {
    public static readonly FOCUS_CLASS = "embed-focusableElement";
    public static blotName = "embed-external";
    public static className = "embed-external";
    public static tagName = "div";

    /**
     * @throws Always throws an error because this blot must be created asynchronously.
     */
    public static create(data: any): any {
        const node = LoadingBlot.create(data);
        setData(node, loadingDataKey, data);
        return node;
    }

    public static createNode(data: any) {
        return FocusableEmbedBlot.create(data);
    }

    public static async createAsync(dataPromise: Promise<IEmbedData> | IEmbedData): Promise<Blot> {
        let data;
        try {
            data = await dataPromise;
            const rootNode = this.createNode(data);
            const embedNode = document.createElement("div");
            const descriptionNode = document.createElement("span");
            rootNode.classList.add("embedExternal");
            rootNode.classList.remove(this.FOCUS_CLASS);
            descriptionNode.innerHTML = t("richEditor.externalEmbed.description");
            descriptionNode.classList.add("sr-only");
            descriptionNode.id = uniqueId("richEditor-embed-description-");

            embedNode.classList.add("embedExternal-content");
            embedNode.classList.add(this.FOCUS_CLASS);
            embedNode.setAttribute("aria-label", "External embed content - " + data.type);
            embedNode.setAttribute("aria-describedby", descriptionNode.id);

            rootNode.appendChild(embedNode);
            rootNode.appendChild(descriptionNode);

            await renderEmbed(embedNode, data);
            setData(rootNode, loadingDataKey, data);
            return new ExternalEmbedBlot(rootNode, false);
        } catch (e) {
            return new ErrorBlot(ErrorBlot.create(e));
        }
    }

    public static value(node) {
        return getData(node, loadingDataKey, "loading");
    }

    constructor(domNode, needsSetup = true) {
        super(domNode);
        const loadingData = getData(domNode, loadingDataKey, false);

        if (loadingData && needsSetup) {
            // This is intentionally a floating promise. We want to immediately return the loading blot if this was created using ExternalEmbedBlot.create(), in which case a loading blot will be returned immediately, but will be replaced with a final blot later.
            // tslint:disable-next-line:no-floating-promises
            this.statics.createAsync(loadingData).then(blot => {
                if (this.domNode.parentNode && this.scroll) {
                    this.replaceWith(blot);
                }
            });
        }
    }
}
