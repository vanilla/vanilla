/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { IEmbedData, renderEmbed } from "@core/embeds";
import AsyncLoadingEmbedBlot from "../Abstract/AsyncLoadingEmbedBlot";
import FocusableEmbedBlot from "../Abstract/FocusableEmbedBlot";
import { setData, getData } from "@core/dom";
import LoadingBlot from "./LoadingBlot";

export default class ExternalEmbedBlot extends AsyncLoadingEmbedBlot {
    public static blotName = "embed-external";
    public static className = "embed-external";
    public static tagName = "div";

    public static async createAsync(dataPromise: Promise<IEmbedData>): Promise<ExternalEmbedBlot> {
        const data = await dataPromise;
        const rootNode = document.createElement("div");
        const embedNode = document.createElement("div");
        rootNode.classList.add("embed");
        rootNode.classList.add(this.className);
        rootNode.classList.add("embedExternal");
        embedNode.classList.add("embedExternal-content");
        embedNode.classList.add(FocusableEmbedBlot.FOCUS_CLASS);
        rootNode.appendChild(embedNode);

        setData(rootNode, "data", data);

        const finalNode = await renderEmbed(embedNode, data);
        return new ExternalEmbedBlot(rootNode);
    }

    public static value(node) {
        return getData(node, "data");
    }
}
