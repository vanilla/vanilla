/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData, FOCUS_CLASS } from "@dashboard/embeds";

// Setup image embeds.
registerEmbed("soundcloud", soundCloudRenderer);

/**
 */
export async function soundCloudRenderer(element: HTMLElement, data: IEmbedData) {
    element.classList.add("embed-image");
    element.classList.add("embedImage");

    const height = data.height ? data.height : "";
    const width = data.width ? data.width : "";

    const iframe = document.createElement("iframe");
    iframe.setAttribute("width", "100%");
    iframe.setAttribute("height", height.toString());
    iframe.setAttribute("scrolling", "no");
    iframe.setAttribute("frameborder", "no");
    iframe.setAttribute(
        "src",
        "https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/" +
            data.attributes.track +
            "&visual=true&show_artwork=true",
    );

    element.appendChild(iframe);
}
