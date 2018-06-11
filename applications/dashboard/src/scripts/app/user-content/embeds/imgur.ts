/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData } from "@dashboard/embeds";
import { ensureScript } from "@dashboard/dom";
import { onContent } from "@dashboard/application";

// Setup imgur embeds.
onContent(convertImgurEmbeds);
registerEmbed("imgur", renderImgur);

/**
 * Renders posted imgur embeds.
 */
async function convertImgurEmbeds() {
    await ensureScript("//s.imgur.com/min/embed.js");

    if (!window.imgurEmbed) {
        throw new Error("The Imgur post failed to load");
    }
}

/**
 * Render a single imgur embed.
 */
export async function renderImgur(element: Element, data: IEmbedData) {
    await ensureScript("//s.imgur.com/min/embed.js");

    const url = "imgur.com/" + data.attributes.postID;
    console.log(data.attributes.postID);
    const isAblum = data.attributes.isAblum;
    const dataSet = isAblum ? "a/" + data.attributes.postID : data.attributes.postID;

    if (!window.imgurEmbed) {
        throw new Error("The Imgur post failed to load");
    }

    const blockQuote = document.createElement("blockquote");
    blockQuote.classList.add("imgur-embed-pub");
    blockQuote.setAttribute("lang", "en");
    blockQuote.dataset.id = dataSet;
    blockQuote.setAttribute("href", url);

    element.appendChild(blockQuote);
}
