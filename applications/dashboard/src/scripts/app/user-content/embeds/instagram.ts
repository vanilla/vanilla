/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData } from "@dashboard/embeds";

// Setup image embeds.
registerEmbed("instagram", renderInstagram);

export async function renderInstagram(element: HTMLElement, data: IEmbedData) {
    element.classList.add("embed-image");
    element.classList.add("embedImage");

    // set height to 510 as we currently set it in class.format
    const height = data.height ? data.height : 510;
    const width = data.width ? data.width : 412;

    const iframe = document.createElement("iframe");
    iframe.classList.add("embedImage-img");
    iframe.setAttribute("width", width);
    iframe.setAttribute("height", height);
    iframe.setAttribute("src", `https://instagram.com/p/${data.attributes.postID}/embed/`);

    element.appendChild(iframe);
}
