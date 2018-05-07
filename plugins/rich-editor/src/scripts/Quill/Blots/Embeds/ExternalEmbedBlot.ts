/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { setData, getData } from "@core/dom";
import LoadingBlot from "../Embeds/LoadingBlot";
import { IEmbedData, renderEmbed } from "@core/embeds";
import FocusableEmbedBlot from "../Abstract/FocusableEmbedBlot";

const DATA_KEY = "__loading-data__";

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
        setData(node, DATA_KEY, data);
        return node;
    }

    public static createNode(data: any) {
        const node = document.createElement("div");
        node.setAttribute("contenteditable", false);
        node.classList.add("embed");
        node.classList.add(this.FOCUS_CLASS);
        return node as HTMLElement;
    }

    public static async createAsync(dataPromise: Promise<IEmbedData> | IEmbedData): Promise<ExternalEmbedBlot> {
        const data = await dataPromise;
        const rootNode = document.createElement("div");
        const embedNode = document.createElement("div");
        rootNode.setAttribute("contenteditable", false);
        rootNode.classList.add("embed");
        rootNode.classList.add(this.className);
        rootNode.classList.add("embedExternal");
        embedNode.classList.add("embedExternal-content");
        embedNode.classList.add(this.FOCUS_CLASS);
        rootNode.appendChild(embedNode);

        setData(rootNode, "data", data);

        const finalNode = await renderEmbed(embedNode, data);
        return new ExternalEmbedBlot(rootNode);
    }

    public static value(node) {
        return getData(node, "data");
    }

    constructor(domNode) {
        super(domNode);
        const loadingData = getData(domNode, DATA_KEY, null);

        if (loadingData) {
            // This is intentionally a floating promise. We want to immediately return the loading blot if this was created using ExternalEmbedBlot.create(), in which case a loading blot will be returned immediately, but will be replaced with a final blot later.
            // tslint:disable-next-line:no-floating-promises
            this.statics.createAsync(loadingData).then(blot => {
                this.replaceWith(blot);
            });
        }
    }
}
