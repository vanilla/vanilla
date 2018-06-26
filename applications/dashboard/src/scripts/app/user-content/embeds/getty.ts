/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData } from "@dashboard/embeds";
import { ensureScript } from "@dashboard/dom";
import { onContent, onReady } from "@dashboard/application";

// Setup getty embeds
onReady(convertGettyEmbeds);
onContent(convertGettyEmbeds);
registerEmbed("getty", renderGetty);

/**
 * Renders posted getty embeds.
 */
async function convertGettyEmbeds() {
    const gettyPosts = document.querySelectorAll(".js-gettyEmbed");
    if (gettyPosts.length > 0) {
        for (const post of gettyPosts) {
            const url = post.getAttribute("href") || "";
            const id = post.getAttribute("id");
            const sig = post.getAttribute("data-sig");
            const height = Number(post.getAttribute("data-height")) || 1;
            const width = Number(post.getAttribute("data-width")) || 1;
            const items = post.getAttribute("data-items");
            const capt = post.getAttribute("data-capt");
            const tld = post.getAttribute("data-tld");
            const i360 = post.getAttribute("data-is360");
            const data: IEmbedData = {
                type: "getty",
                url,
                height,
                width,
                attributes: { id, sig, items, capt, tld, i360 },
            };
            await loadGettyImages(data);
            post.classList.remove("js-gettyEmbed");
        }
    }
}

/**
 * Render a single getty embed.
 */
export async function renderGetty(element: HTMLElement, data: IEmbedData) {
    const url = data.attributes.post;
    const newLink = document.createElement("a");
    newLink.classList.add("gie-single");
    newLink.setAttribute("href", "http://www.gettyimages.ca/detail/" + url);
    newLink.setAttribute("id", data.attributes.id);

    element.appendChild(newLink);

    setImmediate(() => {
        void loadGettyImages(data);
    });
}

/**
 * Loads getty embeds into a global object and fire getty's widget.js.
 * @param data
 * @returns {Promise<void>}
 */
async function loadGettyImages(data) {
    const fallbackCallback = c => {
        (window.gie.q = window.gie.q || []).push(c);
    };
    // This is part of Getty's embed code, we need to ensure embeds are loaded and sent to their widget.js script.
    window.gie = window.gie || fallbackCallback;

    window.gie(() => {
        window.gie.widgets.load({
            id: data.attributes.id,
            sig: data.attributes.sig,
            w: data.width + "px",
            h: data.height + "px",
            items: data.attributes.items,
            caption: data.attributes.isCaptioned,
            tld: data.attributes.tld,
            is360: data.attributes.is360,
        });
    });

    /// DO NOT IGNORE
    /// This will turn totally sideways if window.gie is not populated before the script is initially loaded.
    await ensureScript("//embed-cdn.gettyimages.com/widgets.js");
}
