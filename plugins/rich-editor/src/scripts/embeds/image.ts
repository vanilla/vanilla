/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData } from "@core/embeds";
import FocusableEmbedBlot from "../Quill/Blots/Abstract/FocusableEmbedBlot";

export default function init() {
    registerEmbed("image", renderer);
}

export async function renderer(node: HTMLElement, data: IEmbedData) {
    node.classList.add("embed-image");
    node.classList.add("embedImage");
    node.classList.remove(FocusableEmbedBlot.FOCUS_CLASS);

    const image = document.createElement("img");
    image.classList.add("embedImage-img");
    image.classList.add(FocusableEmbedBlot.FOCUS_CLASS);
    image.setAttribute("src", data.photoUrl || "");
    image.setAttribute("alt", data.name || "");

    node.appendChild(image);
}
