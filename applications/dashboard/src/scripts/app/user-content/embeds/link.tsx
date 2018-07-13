/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import ReactDOM from "react-dom";
import { registerEmbed, IEmbedData, IEmbedElements } from "@dashboard/embeds";
import { getData, setData } from "@dashboard/dom";
import { cssSpecialChars } from "@dashboard/utility";
import debounce from "lodash/debounce";
import shave from "shave";
import LinkEmbed from "@dashboard/app/user-content/embeds/LinkEmbed";

// Setup link embeds.
registerEmbed("link", renderLinkEmbed);
truncateEmbedLinks();

// Retruncate links when the window resizes.
window.addEventListener("resize", () => debounce(truncateEmbedLinks, 200)());

/**
 * Render a a link embed.
 */
export async function renderLinkEmbed(elements: IEmbedElements, data: IEmbedData) {
    ReactDOM.render(<LinkEmbed {...data} />, elements.content);
}

/**
 * Truncate embed link excerpts in a container
 *
 * @param container - Element containing embeds to truncate
 */
export function truncateEmbedLinks(container = document.body) {
    const embeds = container.querySelectorAll(".embedLink-excerpt");
    embeds.forEach(el => {
        let untruncatedText = getData(el, "untruncatedText");

        if (!untruncatedText) {
            untruncatedText = el.innerHTML;
            setData(el, "untruncatedText", untruncatedText);
        } else {
            el.innerHTML = untruncatedText;
        }
        truncateTextBasedOnMaxHeight(el);
    });
}

/**
 * Truncate element text based on max-height
 *
 * @param excerpt - The excerpt to truncate.
 */
export function truncateTextBasedOnMaxHeight(excerpt: Element) {
    const maxHeight = parseInt(getComputedStyle(excerpt)["max-height"], 10);
    if (maxHeight && maxHeight > 0) {
        shave(excerpt, maxHeight);
    }
}
