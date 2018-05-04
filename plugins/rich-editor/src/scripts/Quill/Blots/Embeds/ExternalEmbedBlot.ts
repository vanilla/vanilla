/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { IEmbedData, renderEmbed } from "@core/embeds";
import FocusableEmbedBlot from "../Abstract/FocusableEmbedBlot";
import { setData, getData } from "@core/dom";

export default class ExternalEmbedBlot extends FocusableEmbedBlot {
    public static blotName = "embed-external";
    public static className = "embed-external";
    public static tagName = "div";

    public static create(data: IEmbedData) {
        const node = super.create(data);
        const embedNode = document.createElement("div");
        node.classList.add("embedExternal");
        node.classList.remove(FocusableEmbedBlot.FOCUS_CLASS);
        embedNode.classList.add("embedExternal-content");
        embedNode.classList.add(FocusableEmbedBlot.FOCUS_CLASS);
        renderEmbed(embedNode, data);
        node.appendChild(embedNode!);
        setData(node, "data", data);
        return node;
    }

    public static value(node) {
        return getData(node, "data");
    }
}
