/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { setData, getData } from "@core/dom";
import FocusableEmbedBlot from "../Abstract/FocusableEmbedBlot";

export default class ImageBlot extends FocusableEmbedBlot {
    public static blotName = "embed-image";
    public static className = "embed-image";
    public static tagName = "div";

    public static create(data) {
        const node = super.create(data);
        node.classList.add("embed");
        node.classList.add("embed-image");
        node.classList.add("embedImage");
        node.classList.remove(FocusableEmbedBlot.FOCUS_CLASS);

        const image = document.createElement("img");
        image.classList.add("embedImage-img");
        image.classList.add(FocusableEmbedBlot.FOCUS_CLASS);
        image.setAttribute("src", data.url);
        image.setAttribute("alt", data.alt || "");

        node.appendChild(image);
        return node;
    }

    public static value(node) {
        const image = node.querySelector(".embedImage-img");

        if (image instanceof HTMLElement) {
            return {
                url: image.getAttribute("src"),
                alt: image.getAttribute("alt") || "",
            };
        }
    }
}
