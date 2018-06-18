/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData, FOCUS_CLASS } from "@dashboard/embeds";

registerEmbed("giphy", giphyRenderer);

/**
 * Renders giphy embeds.
 */
export async function giphyRenderer(element: HTMLElement, data: IEmbedData) {
    if (data.attributes.postID == null) {
        throw new Error("Giphy embed fail, the post could not be found");
    }

    element.classList.add("embed");
    element.classList.add("embedGiphy");
    element.style.width = `${data.width}px` || "100%";

    const paddingBottom = ((data.height || 1) / (data.width || 2)) * 100 + "%";
    const giphyWrapper = document.createElement("div");
    giphyWrapper.style.paddingBottom = paddingBottom;
    giphyWrapper.classList.add("embedExternal-ratio");

    const iframe = document.createElement("iframe");
    iframe.classList.add("giphy-embed");
    iframe.classList.add("embedGiphy-iframe");
    iframe.setAttribute("src", "https://giphy.com/embed/" + data.attributes.postID);

    giphyWrapper.appendChild(iframe);
    element.appendChild(giphyWrapper);
}
