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
import { Blot } from "quill/core";

const DATA_KEY = "__embed-data__";

interface ILoaderData {
    type: "image" | "link";
    link?: string;
    loaded?: boolean;
}

interface IEmbedUnloadedValue {
    loaderData: ILoaderData;
    dataPromise: Promise<IEmbedData>;
}

interface IEmbedLoadedValue {
    loaderData: ILoaderData;
    data: IEmbedData;
}

const WARNING_HTML = title => `
<svg class="resolved2-unresolved" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
    <title>${title}</title>
    <circle cx="8" cy="8" r="8" style="fill: #f5af15"/>
    <circle cx="8" cy="8" r="7.5" style="fill: none;stroke: #000;stroke-opacity: 0.122"/>
    <path d="M11,10.4V8h2v2.4L12.8,13H11.3Zm0,4h2v2H11Z" transform="translate(-4 -4)" style="fill: #fff"/>
</svg>`;

export type IEmbedValue = IEmbedLoadedValue | IEmbedUnloadedValue;

export default class ExternalEmbedBlot extends FocusableEmbedBlot {
    public static blotName = "embed-external";
    public static className = "embed-external";
    public static tagName = "div";
    public static readonly LOADING_VALUE = { loading: true };

    public static create(value: IEmbedValue): HTMLElement {
        const node = LoadingBlot.create(value);
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

    /**
     * @throws {Error} If the rendering fails
     */
    public static async createSuccessfulEmbedElement(data: IEmbedData): Promise<Element> {
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

    public static createErrorEmbedElement(text: string, message: string) {
        const div = FocusableEmbedBlot.create();
        div.classList.remove(FOCUS_CLASS);
        div.classList.add("embed");
        div.classList.add("embedLinkLoader");
        div.classList.add("embedLinkLoader-error");
        div.classList.add(FOCUS_CLASS);

        const sanitizedText = escapeHTML(text);

        // In the future this message should point to a knowledge base article.
        const warningTitle = t("This embed could not be loaded in your browser.");
        div.innerHTML = `
            <a href="#" class="embedLinkLoader-link">${sanitizedText}</a>
            <div class='embedLinkLoader-icon'>${WARNING_HTML(warningTitle)}</div>
        `;
        return div;
    }

    private loadCallback?: () => void;

    constructor(domNode, value: IEmbedValue, needsSetup = true) {
        super(domNode);
        if (!needsSetup) {
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

        let data: IEmbedData | null = null;
        if ("data" in value) {
            data = value.data;
        } else {
            try {
                data = await value.dataPromise;
            } catch (e) {
                logError(e);
                return this.completeWithBlot(new ErrorBlot(ErrorBlot.create(e)));
            }
        }

        let embedElement: Element;
        const newValue: IEmbedValue = {
            data,
            loaderData: {
                ...value.loaderData,
                loaded: true,
            },
        };

        try {
            embedElement = await ExternalEmbedBlot.createSuccessfulEmbedElement(data);
        } catch (e) {
            logError(e);
            embedElement = ExternalEmbedBlot.createErrorEmbedElement(data.url, e.message);
        }

        setData(embedElement, DATA_KEY, newValue);
        finalBlot = new ExternalEmbedBlot(embedElement, newValue, false);
        this.completeWithBlot(finalBlot);
    }

    public registerLoadCallback(callback: () => void) {
        this.loadCallback = callback;
    }

    private completeWithBlot(blot: Blot) {
        this.replaceWith(blot);
        if (this.loadCallback) {
            this.loadCallback();
            this.loadCallback = undefined;
        }
    }
}
