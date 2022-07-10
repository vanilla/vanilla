/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { EMBED_FOCUS_CLASS } from "@library/embeddedContent/embedConstants";
import { getData, setData } from "@vanilla/dom-utils";
import { mountEmbed } from "@library/embeddedContent/embedService";
import ProgressEventEmitter from "@library/utility/ProgressEventEmitter";
import ErrorBlot, { ErrorBlotType, IErrorData } from "@rich-editor/quill/blots/embeds/ErrorBlot";
import LoadingBlot from "@rich-editor/quill/blots/embeds/LoadingBlot";
import { forceSelectionUpdate } from "@rich-editor/quill/utility";
import { logError } from "@vanilla/utils";
import { SelectableEmbedBlot } from "@rich-editor/quill/blots/abstract/SelectableEmbedBlot";
import { extractServerError } from "@library/apiv2";

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

export type IEmbedValue = IEmbedLoadedValue | IEmbedUnloadedValue;

/**
 * The primary entrypoint for rendering embeds in Quill.
 *
 * If you're trying to render an embed, you likely want to use the {EmbedInsertionModule}.
 */
export default class ExternalEmbedBlot extends SelectableEmbedBlot {
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
     * Get the data representing the  blot.
     */
    public getData(): IEmbedLoadedValue {
        const value = this.value();
        return value[ExternalEmbedBlot.blotName];
    }

    /**
     * Callback for syncing some values back into the blot data from a react rendered embed.
     */
    private syncMountedValues = (newValues: object) => {
        const existingValue: IEmbedLoadedValue = this.getData();
        const mergedValue = {
            ...existingValue,
            data: {
                ...existingValue.data,
                ...newValues,
            },
        };

        setData(this.domNode as Element, DATA_KEY, mergedValue);
        void this.renderReact();
    };

    private async renderReact() {
        const data = this.value()["embed-external"].data;
        const mountNode = this.domNode as HTMLElement;
        mountNode.classList.add("isMounted");

        const fullData = {
            ...data,
            syncBackEmbedValue: this.syncMountedValues,
            quill: this.quill,
            isSelected: this.isSelected,
            selectSelf: this.select,
            deleteSelf: this.remove,
            inEditor: true,
        };

        await mountEmbed(mountNode, fullData, true);
    }

    public select = () => {
        super.select();
        void this.renderReact();
    };

    public clearSelection = () => {
        super.clearSelection();
        void this.renderReact();
    };

    /**
     * Create a successful embed element.
     */
    public async createEmbedFromData(data: IBaseEmbedProps, loaderElement: Element | null, newValueToSet: any) {
        const jsEmbed = SelectableEmbedBlot.create(data);
        setData(jsEmbed, DATA_KEY, newValueToSet);

        jsEmbed.classList.add("js-embed");
        jsEmbed.classList.add("embedResponsive");

        // Append these nodes.
        loaderElement && jsEmbed.appendChild(loaderElement);

        // Replace the old dom node.
        const oldNode = this.domNode;
        const parent = oldNode.parentNode!;
        parent.insertBefore(jsEmbed, oldNode);
        parent.removeChild(oldNode);

        // Move the blot reference from the old node to the new one.

        delete this.domNode["__blot"];
        jsEmbed["__blot"] = { blot: this };

        // Assign the new domNode.
        this.domNode = jsEmbed;
        this.attach();

        await this.renderReact();

        // Remove the focus class. It should be handled by the mounted embed at this point.
        loaderElement && loaderElement.remove();
        jsEmbed.classList.remove(EMBED_FOCUS_CLASS);
        // Trigger an update.
        forceSelectionUpdate();
    }

    /**
     * This should only ever be called internally (or through Parchment.create())
     *
     * @param domNode - The node to attach the blot to.
     * @param value - The value the embed is being created with.
     */
    constructor(domNode, value: IEmbedValue) {
        super(domNode);
        void this.replaceLoaderWithFinalForm(value);
    }

    /**
     * Replace the embed's loader with it's final state. This could take the form of a registered embed,
     * or an error state.
     *
     * @see @library/embedService
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
            .then((data) => {
                // DOM node has been removed from the document.
                if (!document.contains(this.domNode)) {
                    return;
                }

                const newValue: IEmbedValue = {
                    data,
                    loaderData: value.loaderData,
                };

                const loader = (this.domNode as Element).querySelector(".embedLinkLoader");
                void this.createEmbedFromData(data, loader, newValue);
            })
            .catch((e) => {
                logError(e);
                // DOM node has been removed from the document.
                if (!document.contains(this.domNode)) {
                    return;
                }
                const errorData: IErrorData = {
                    error: extractServerError(e),
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
