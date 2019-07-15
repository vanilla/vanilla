/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { FOCUS_CLASS, IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { escapeHTML, getData, setData } from "@library/dom/domUtils";
import { mountEmbed } from "@library/embeddedContent/embedService";
import { t } from "@library/utility/appUtils";
import ProgressEventEmitter from "@library/utility/ProgressEventEmitter";
import FocusableEmbedBlot from "@rich-editor/quill/blots/abstract/FocusableEmbedBlot";
import ErrorBlot, { ErrorBlotType, IErrorData } from "@rich-editor/quill/blots/embeds/ErrorBlot";
import LoadingBlot from "@rich-editor/quill/blots/embeds/LoadingBlot";
import { forceSelectionUpdate } from "@rich-editor/quill/utility";
import { logError } from "@vanilla/utils";

const DATA_KEY = "__embed-data__";

interface ILoaderData {
    type: "image" | "link" | "file";
    file?: File;
    link?: string;
    progressEventEmitter?: ProgressEventEmitter;
}

interface IEmbedUnloadedValue {
    loaderData: ILoaderData;
    dataPromise: Promise<IBaseEmbedProps>;
}

interface IEmbedLoadedValue {
    loaderData: ILoaderData;
    data: IBaseEmbedProps;
}

const WARNING_HTML = title => `
<svg class="embedLinkLoader-failIcon" title="${title}" aria-label="${title}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
    <title>${title}</title>
    <circle cx="8" cy="8" r="8" style="fill: #f5af15"/>
    <circle cx="8" cy="8" r="7.5" style="fill: none;stroke: #000;stroke-opacity: 0.122"/>
    <path d="M11,10.4V8h2v2.4L12.8,13H11.3Zm0,4h2v2H11Z" transform="translate(-4 -4)" style="fill: #fff"/>
</svg>`;

export type IEmbedValue = IEmbedLoadedValue | IEmbedUnloadedValue;

/**
 * The primary entrypoint for rendering embeds in Quill.
 *
 * If you're trying to render an embed, you likely want to use the {EmbedInsertionModule}.
 */
export default class ExternalEmbedBlot extends FocusableEmbedBlot {
    public static blotName = "embed-external";
    public static className = "embed-external";
    public static tagName = "div";
    public static readonly LOADING_VALUE = { loading: true };

    /**
     * Create the initial HTML for the external embed.
     *
     * An embed always starts with a loader (even if its only there for a second).
     */
    public static create(value: IEmbedValue): HTMLElement {
        return LoadingBlot.create(value);
    }

    /**
     * Get the loading value, otherwise get the primary value.
     */
    public static value(element: Element) {
        const isLoader = element.classList.contains(LoadingBlot.className);
        if (isLoader) {
            return LoadingBlot.value(element);
        } else {
            const value = getData(element, DATA_KEY, false);
            return value;
        }
    }

    /**
     * Create an warning state for the embed element. This occurs when the data fetching has succeeded,
     * but the browser rendering has not.
     *
     * In other words, the blot has all of the data in needs to render in another browser, but not the
     * current one.
     *
     * A usual case for this is having tracking protection on in Firefox (twitter + instagram scripts blocked) .
     *
     * @param linkText - The text of the link that failed to be embeded.
     */
    public static createEmbedWarningFallback(linkText: string) {
        const div = document.createElement("div");
        div.classList.add("embedExternal");
        div.classList.add("embedLinkLoader");
        div.classList.add("embedLinkLoader-error");

        const sanitizedText = escapeHTML(linkText);

        // In the future this message should point to a knowledge base article.
        const warningTitle = t("This embed could not be loaded in your browser.");
        div.innerHTML = `<a href="#" class="embedLinkLoader-link ${FOCUS_CLASS}" tabindex="-1">${sanitizedText}&nbsp;${WARNING_HTML(
            warningTitle,
        )}</a>`;
        return div;
    }

    /**
     * Create a successful embed element.
     */
    public static createEmbedFromData(data: IBaseEmbedProps, loaderElement: Element | null): Element {
        const jsEmbed = FocusableEmbedBlot.create(data);

        jsEmbed.classList.add("js-embed");
        jsEmbed.classList.add("embedResponsive");
        jsEmbed.tabIndex = -1;

        // Append these nodes.
        loaderElement && jsEmbed.appendChild(loaderElement);

        try {
            mountEmbed(jsEmbed, data, true).then(() => {
                // Remove the focus class. It should be handled by the mounted embed at this point.
                loaderElement && loaderElement.remove();
                jsEmbed.classList.remove(FOCUS_CLASS);
                jsEmbed.removeAttribute("tabindex");
                forceSelectionUpdate();
            });
        } catch (e) {
            const warning = ExternalEmbedBlot.createEmbedWarningFallback(data.url);
            // Cleanup existing HTML.
            jsEmbed.innerHTML = "";

            // Add the warning.
            jsEmbed.appendChild(warning);
            forceSelectionUpdate();
        }

        return jsEmbed;
    }

    /**
     * This should only ever be called internally (or through Parchment.create())
     *
     * @param domNode - The node to attach the blot to.
     * @param value - The value the embed is being created with.
     * @param needsSetup - Whether or not replace with a final form. This should be false only for internal use.
     */
    constructor(domNode, value: IEmbedValue, needsSetup = true) {
        super(domNode);
        if (needsSetup) {
            void this.replaceLoaderWithFinalForm(value);
        }
    }

    /**
     * Replace the embed's loader with it's final state. This could take the form of a registered embed,
     * or an error state.
     *
     * @see @dashboard/embeds
     */
    public replaceLoaderWithFinalForm(value: IEmbedValue) {
        let finalBlot: ExternalEmbedBlot | ErrorBlot;

        const resolvedPromise = this.resolveDataFromValue(value);
        if (!(resolvedPromise instanceof Promise)) {
            const quill = this.quill;
            this.remove();
            quill && quill.update();
            return;
        }

        resolvedPromise
            .then(data => {
                // DOM node has been removed from the document.
                if (!document.contains(this.domNode)) {
                    return;
                }

                const newValue: IEmbedValue = {
                    data,
                    loaderData: value.loaderData,
                };

                const loader = (this.domNode as Element).querySelector(".embedLinkLoader");
                const embedElement = ExternalEmbedBlot.createEmbedFromData(data, loader);
                setData(embedElement, DATA_KEY, newValue);
                finalBlot = new ExternalEmbedBlot(embedElement, newValue, false);
                this.replaceWith(finalBlot);
            })
            .catch(e => {
                logError(e);
                // DOM node has been removed from the document.
                if (!document.contains(this.domNode)) {
                    return;
                }
                const errorData: IErrorData = {
                    error: e,
                    type: value.loaderData.type === "file" ? ErrorBlotType.FILE : ErrorBlotType.STANDARD,
                    file: value.loaderData.file,
                };
                this.replaceWith(new ErrorBlot(ErrorBlot.create(errorData), errorData));
            });
    }

    /**
     * Normalize data and dataPromise into Promise<data>
     */
    private resolveDataFromValue(value: IEmbedValue): Promise<IBaseEmbedProps> {
        if ("data" in value) {
            return Promise.resolve(value.data);
        } else {
            return value.dataPromise;
        }
    }
}
