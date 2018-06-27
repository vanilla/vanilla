/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData, FOCUS_CLASS, IEmbedElements } from "@dashboard/embeds";

// Setup soundcloud embeds.
registerEmbed("soundcloud", soundCloudRenderer);

/**
 * Renders soundcloud embeds.
 */
export async function soundCloudRenderer(elements: IEmbedElements, data: IEmbedData) {
    const contentElement = elements.content;
    const showArtwork = data.attributes.showArtwork ? data.attributes.showArtwork : "false";
    const visual = data.attributes.visual ? data.attributes.visual : "false";

    // Ensure this is a track.
    if (data.attributes.track == null) {
        throw new Error("Soundcloud embed fail, the track could not be found");
    }

    const iframe = document.createElement("iframe");
    iframe.setAttribute("id", "sc-widget");
    iframe.setAttribute("width", "100%");
    iframe.setAttribute("scrolling", "no");
    iframe.setAttribute("frameborder", "no");
    iframe.setAttribute(
        "src",
        "https://w.soundcloud.com/player/?url=https://api.soundcloud.com/tracks/" +
            data.attributes.track +
            "&visual=" +
            showArtwork +
            "&show_artwork=" +
            visual,
    );
    contentElement.appendChild(iframe);
}
