/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { setData, getData, escapeHTML } from "@dashboard/dom";
import uniqueId from "lodash/uniqueId";
import { IEmbedData, renderEmbed, FOCUS_CLASS } from "@dashboard/embeds";
import FocusableEmbedBlot from "../abstract/FocusableEmbedBlot";
import ErrorBlot from "./ErrorBlot";
import { t } from "@dashboard/application";
import { logError } from "@dashboard/utility";
import LoadingBlot from "@rich-editor/quill/blots/embeds/LoadingBlot";

const DATA_KEY = "__embed-data__";

interface ILoaderData {
    type: "image" | "link";
    link?: string;
    skipSetup?: boolean;
}

interface IEmbedUnloadedValue {
    loaderData: ILoaderData;
    data: IEmbedData;
}

interface IEmbedLoadedValue {
    loaderData: ILoaderData;
    dataPromise: Promise<IEmbedData>;
}

export type IEmbedValue = IEmbedLoadedValue | IEmbedUnloadedValue;

export default class ExternalEmbedBlot extends FocusableEmbedBlot {
    public static blotName = "embed-external";
    public static className = "embed-external";
    public static tagName = "div";
    public static readonly LOADING_VALUE = { loading: true };

    public static create(value: IEmbedValue): HTMLElement {
        const node = LoadingBlot.create(value);
        // value.loaderData.loadedCount++;
        return node;
    }

    public static value(element: Element) {
        const isLoader = element.getAttribute("data-loader");
        if (isLoader) {
            return LoadingBlot.value(element);
        } else {
            const value = getData(element, DATA_KEY, false);
            return value;
        }
    }

    public static async createEmbedNode(data: IEmbedData) {
        const rootNode = FocusableEmbedBlot.create(data);
        const embedNode = document.createElement("div");
        const descriptionNode = document.createElement("span");
        rootNode.classList.add("embedExternal");
        rootNode.classList.remove(FOCUS_CLASS);
        descriptionNode.innerHTML = t("richEditor.externalEmbed.description");
        descriptionNode.classList.add("sr-only");
        descriptionNode.id = uniqueId("richEditor-embed-description-");

        embedNode.classList.add("embedExternal-content");
        embedNode.classList.add(FOCUS_CLASS);
        embedNode.setAttribute("aria-label", "External embed content - " + data.type);
        embedNode.setAttribute("aria-describedby", descriptionNode.id);

        rootNode.appendChild(embedNode);
        rootNode.appendChild(descriptionNode);

        await renderEmbed(embedNode, data);
        return rootNode;
    }

    private loadCallback?: () => void;

    constructor(domNode, value: IEmbedValue, needsSetup = true) {
        super(domNode);
        if (!needsSetup || value.loaderData.skipSetup) {
            return;
        }

        void this.replaceLoaderWithFinalForm(value);
    }

    /**
     * Replace the embed's loader with it's final state. This could take the form of a registered embed,
     * or an error state.
     *
     * @see @dashboard/embeds
     */
    public async replaceLoaderWithFinalForm(value: IEmbedValue) {
        let finalBlot: ExternalEmbedBlot | ErrorBlot;

        try {
            let data: IEmbedData;
            if ("data" in value) {
                data = value.data;
            } else {
                data = await value.dataPromise;
            }
            const embedNode = await ExternalEmbedBlot.createEmbedNode(data);
            const newValue: IEmbedValue = {
                data,
                loaderData: {
                    ...value.loaderData,
                    skipSetup: false,
                },
            };

            setData(embedNode, DATA_KEY, newValue);
            finalBlot = new ExternalEmbedBlot(embedNode, newValue, false);
        } catch (e) {
            logError(e);
            finalBlot = new ErrorBlot(ErrorBlot.create(e));
        }

        this.replaceWith(finalBlot);
        if (this.loadCallback) {
            this.loadCallback();
            this.loadCallback = undefined;
        }
    }

    public registerLoadCallback(callback: () => void) {
        this.loadCallback = callback;
    }
}
