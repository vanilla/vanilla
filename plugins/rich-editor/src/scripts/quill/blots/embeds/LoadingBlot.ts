/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { t } from "@dashboard/application";
import FocusableEmbedBlot from "../abstract/FocusableEmbedBlot";
import { FOCUS_CLASS } from "@dashboard/embeds";
import { escapeHTML, setData, getData } from "@dashboard/dom";
import { IEmbedValue } from "@rich-editor/quill/blots/embeds/ExternalEmbedBlot";

const LOADER_DATA_KEY = "loadingDataKey";

/**
 * A loading blot. This should not be created on its own. Instead it should be created throught the ExternalEmbedBlot
 */
export default class LoadingBlot extends FocusableEmbedBlot {
    public static blotName = "embed-loading";
    public static className = "js-embedLoader";
    public static tagName = "div";

    /**
     * Create one of two types of loaders.
     */
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

        setData(node, LOADER_DATA_KEY, value);
        return node;
    }

    /**
     * Get the value out of a domNode. Also change "loaded" to false.
     */
    public static value(element: Element): IEmbedValue {
        const storedValue = getData(element, LOADER_DATA_KEY, null);

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

    /**
     * Create an image upload loader. This is full size loader that does takes up a lot of space.
     */
    private static createImageLoader() {
        const div = super.create();
        div.classList.remove(FOCUS_CLASS);
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

    /**
     * Create an inline link loader.
     */
    private static createLinkLoader(linkText: string) {
        const div = super.create();
        div.classList.remove(FOCUS_CLASS);
        div.classList.add("embed");
        div.classList.add("embedLinkLoader");

        const sanitizedText = escapeHTML(linkText);
        div.innerHTML = `<a href="#" class="embedLinkLoader-link ${FOCUS_CLASS}">${sanitizedText}&nbsp;<span aria-hidden="true" class='embedLinkLoader-loader'></span></a>`;
        return div;
    }
}
