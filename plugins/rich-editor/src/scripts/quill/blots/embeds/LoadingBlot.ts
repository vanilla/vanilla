/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { t } from "@dashboard/application";
import FocusableEmbedBlot from "../abstract/FocusableEmbedBlot";
import { FOCUS_CLASS } from "@dashboard/embeds";
import { escapeHTML, setData, getData } from "@dashboard/dom";
import { IEmbedValue } from "@rich-editor/quill/blots/embeds/ExternalEmbedBlot";
import { logError } from "@dashboard/utility";

export default class LoadingBlot extends FocusableEmbedBlot {
    public static blotName = "embed-loading";
    public static className = "embed-loading";
    public static tagName = "div";

    public static create(value: IEmbedValue) {
        let node: HTMLElement;
        switch (value.loaderData.type) {
            case "link":
                node = this.createLinkLoader(value.loaderData.link || "");
                break;
            case "image":
                node = this.createImageLoader();
                break;
            default:
                throw new Error("Could not determine loader type for embed blot.");
        }

        setData(node, this.LOADER_DATA_KEY, value);
        return node;
    }

    public static value(element: Element): IEmbedValue {
        const storedValue = getData(element, this.LOADER_DATA_KEY, null);

        if (!storedValue) {
            throw new Error("A loading blot should have data set");
        }

        return {
            ...storedValue,
            loaderData: {
                ...storedValue.loaderData,
                loaded: false,
            },
        };
    }

    protected static readonly LOADER_DATA_KEY = "loadingDataKey";

    private static createImageLoader() {
        const div = super.create();
        div.classList.remove(FOCUS_CLASS);
        div.setAttribute("data-loader", true);
        div.classList.add("embed");
        div.classList.add("embedLinkLoader");
        div.innerHTML = `<div class='embedLoader'>
                            <div class='embedLoader-box ${FOCUS_CLASS}' aria-label='${t(
            "Loading...",
        )}'><div class='embedLoader-loader'></div>
                            </div>
                        </div>`;
        return div;
    }

    private static createLinkLoader(text: string) {
        const div = super.create();
        div.classList.remove(FOCUS_CLASS);
        div.setAttribute("data-loader", true);
        div.classList.add("embed");
        div.classList.add("embedLinkLoader");
        div.classList.add("js-linkLoader");

        const sanitizedText = escapeHTML(text);
        div.innerHTML = `
            <a href="#" class="embedLinkLoader-link ${FOCUS_CLASS}">${sanitizedText}</a>
            <div class='embedLoader-loader'></div>
        `;
        return div;
    }
}
