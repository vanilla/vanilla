/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData } from "@dashboard/embeds";
import { ensureScript } from "@dashboard/dom";
import { onContent } from "@dashboard/application";

// Setup instagram embeds.
onContent(convertInstagramEmbeds);
registerEmbed("instagram", renderInstagram);

/**
 * Renders posted instagram embeds.
 */
async function convertInstagramEmbeds() {
    await ensureScript("//platform.instagram.com/en_US/embeds.js");
    window.instgrm.Embeds.process();
}

/**
 * Render a single instagram embed.
 */
export async function renderInstagram(element: Element, data: IEmbedData) {
    await ensureScript("//platform.instagram.com/en_US/embeds.js");

    if (!window.instgrm) {
        throw new Error("The Instagram post failed to load");
    }

    // Ensure we have a status id to look up.
    if (data.attributes.permaLink == null) {
        throw new Error("Attempted to embed a Instagram post failed link is invalid");
    }

    const blockQuote = document.createElement("blockquote");
    blockQuote.classList.add("instagram-media");
    blockQuote.dataset.instgrmPermalink = data.attributes.permaLink;
    blockQuote.dataset.instgrmVersion = data.attributes.versionNumber;
    blockQuote.dataset.instgrmCaptioned = data.attributes.isCaptioned;

    element.appendChild(blockQuote);
    setImmediate(() => {
        window.instgrm.Embeds.process();
    });
}
