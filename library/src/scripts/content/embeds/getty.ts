/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IEmbedData, IEmbedElements, registerEmbedRenderer } from "@library/content/embeds/embedUtils";
import { ensureScript } from "@library/dom/domUtils";
import { onContent, onReady } from "@library/utility/appUtils";
import { IScrapeData } from "@dashboard/@types/api/media";

export function initGettyEmbeds() {
    registerEmbedRenderer("getty", renderGetty);
    onReady(convertGettyEmbeds);
    onContent(convertGettyEmbeds);
}

/**
 * Render a single getty embed.
 */
export async function renderGetty(elements: IEmbedElements, data: IScrapeData) {
    const contentElement = elements.content;
    const url = data.attributes.post;
    const newLink = document.createElement("a");
    newLink.classList.add("gie-single");
    newLink.setAttribute("href", "https://www.gettyimages.ca/detail/" + url);
    newLink.setAttribute("id", data.attributes.id);
    contentElement.style.width = `${data.width}px`;
    contentElement.appendChild(newLink);
    setImmediate(() => {
        void loadGettyImages(data);
    });
}

/**
 * Renders posted getty embeds.
 */
export async function convertGettyEmbeds() {
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
                embedType: "getty",
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
    await ensureScript("https://embed-cdn.gettyimages.com/widgets.js");
}
