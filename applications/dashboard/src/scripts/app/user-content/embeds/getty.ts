/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData } from "@dashboard/embeds";
import { ensureScript } from "@dashboard/dom";
import { onContent, onReady } from "@dashboard/application";

// Setup getty embeds
onReady(convertgettyEmbeds);
onContent(convertgettyEmbeds);
registerEmbed("getty", rendergetty);

/**
 * Renders posted getty embeds.
 */
async function convertgettyEmbeds() {
    const gettyPosts = document.querySelectorAll(".js-gettyEmbed");
    if (gettyPosts.length > 0) {
        for (const post of gettyPosts) {
            const url = post.getAttribute("href") || "";
            const jsonData = post.getAttribute("data-json") || "";
            const gettyPost = JSON.parse(jsonData);
            const id = gettyPost.attributes.id;
            const height = gettyPost.height;
            const sig = gettyPost.attributes.sig;
            const width = gettyPost.width;
            const items = gettyPost.attributes.items;
            const capt = gettyPost.attributes.isCaptioned;
            const tld = gettyPost.attributes.tld;
            const i360 = gettyPost.attributes.is360;
            const data: IEmbedData = {
                type: "getty",
                url,
                height,
                width,
                attributes: { id, sig, items, capt, tld, i360 },
            };
            await loadGettyImage(data);
            post.classList.remove("js-gettyEmbed");
        }
    }
}

/**
 * Render a single getty embed.
 */
export async function rendergetty(element: HTMLElement, data: IEmbedData) {
    const url = data.attributes.post;
    const newLink = document.createElement("a");
    newLink.classList.add("gie-single");
    newLink.setAttribute("href", "http://www.gettyimages.ca/detail/" + url);
    newLink.setAttribute("id", data.attributes.id);

    element.appendChild(newLink);

    setImmediate(() => {
        void loadGettyImage(data);
    });
}

async function loadGettyImage(data) {
    const fallbackCallback = c => {
        (window.gie.q = window.gie.q || []).push(c);
    };
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
