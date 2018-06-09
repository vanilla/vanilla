/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData, FOCUS_CLASS } from "@dashboard/embeds";

// Setup soundcloud embeds.
registerEmbed("soundcloud", soundCloudRenderer);

/**
 * Renders soundcloud embeds.
 */
export async function soundCloudRenderer(element: HTMLElement, data: IEmbedData) {
    const height = data.height ? data.height : "";

    const iframe = document.createElement("iframe");
    iframe.setAttribute("id", "sc-widget");
    iframe.setAttribute("width", "100%");
    iframe.setAttribute("height", height.toString());
    iframe.setAttribute("scrolling", "no");
    iframe.setAttribute("frameborder", "no");
    iframe.setAttribute(
        "src",
        data.attributes.url +
            data.attributes.track +
            "&visual=" +
            data.attributes.visual +
            "&show_artwork=" +
            data.attributes.showArtwork,
    );
    element.appendChild(iframe);
}
