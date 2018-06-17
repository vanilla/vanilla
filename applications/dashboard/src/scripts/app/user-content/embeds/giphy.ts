/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData, FOCUS_CLASS } from "@dashboard/embeds";
import { watchFocusInDomTree } from "@dashboard/dom";

registerEmbed("giphy", giphyRenderer);

/**
 * Renders giphy embeds.
 */
export async function giphyRenderer(element: HTMLElement, data: IEmbedData) {
    // Ensure this is a track.
    if (data.attributes.postID == null) {
        throw new Error("Giphy embed fail, the post could not be found");
    }

    const div = document.createElement("div");
    div.style.width = "100%";
    div.style.height = "100%";
    div.style.paddingBottom = "56%";
    div.style.position = "relative";

    const iframe = document.createElement("iframe");
    iframe.setAttribute("id", "sc-widget");
    iframe.setAttribute("width", "100%");
    iframe.setAttribute("height", "100%");
    iframe.setAttribute("style", "no");
    iframe.setAttribute("frameborder", "0");
    iframe.setAttribute("allowFullScreen", "");
    iframe.setAttribute("src", "https://giphy.com/embed/" + data.attributes.postID);
    iframe.style.position = "absolute";
    const p = document.createElement("p");
    p.setAttribute("href", data.attributes.url);

    // div.appendChild(iframe);

    // <div style="width:100%;height:0;padding-bottom:56%;position:relative;">
    // <iframe src="https://giphy.com/embed/1xpSTx94OQxxABLr1c"
    // width="100%" height="100%" style="position:absolute" frameBorder="0" class="giphy-embed" allowFullScreen></iframe></div>
    // <p><a href="https://giphy.com/gifs/bravotv-southern-charm-kathryn-c-dennis-1xpSTx94OQxxABLr1c">via GIPHY</a></p>
    element.appendChild(iframe);
    element.appendChild(p);
}
