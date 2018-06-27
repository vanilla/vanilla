/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData } from "@dashboard/embeds";
import { simplifyFraction } from "@dashboard/utility";
import { t } from "@dashboard/application";
import { delegateEvent } from "@dashboard/dom";

// Register the various video embeds.
registerEmbed("youtube", videoRenderer);
registerEmbed("vimeo", videoRenderer);
registerEmbed("wistia", videoRenderer);
registerEmbed("twitch", videoRenderer);

// Setup the click handler for all videos in the page.
delegateEvent("click", ".js-playVideo", handlePlayVideo);

/**
 * Render a video embed in the editor. The JS for handling playing/stopping these videos
 * lives in @dashboard/user-content.
 */
export async function videoRenderer(node: HTMLElement, data: IEmbedData) {
    node.classList.add("embedVideo");
    data.name = data.name || "";

    const ratioContainer = document.createElement("div");
    ratioContainer.classList.add("embedVideo-ratio");

    const ratio = simplifyFraction(data.height || 3, data.width || 4);

    switch (ratio.shorthand) {
        case "21:9":
            ratioContainer.classList.add("is21by9");
            break;
        case "16:9":
            ratioContainer.classList.add("is16by9");
            break;
        case "4:3":
            ratioContainer.classList.add("is4by3");
            break;
        case "1:1":
            ratioContainer.classList.add("is1by1");
            break;
        default:
            ratioContainer.style.paddingTop = ((data.height || 3) / (data.width || 4)) * 100 + "%";
    }

    const playIcon = `<svg class="embedVideo-playIcon" xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 24 24"><title>${t(
        "Play Video",
    )}</title><path class="embedVideo-playIconPath embedVideo-playIconPath-circle" style="fill: currentColor; stroke-width: .3;" d="M11,0A11,11,0,1,0,22,11,11,11,0,0,0,11,0Zm0,20.308A9.308,9.308,0,1,1,20.308,11,9.308,9.308,0,0,1,11,20.308Z"/><polygon class="embedVideo-playIconPath embedVideo-playIconPath-triangle" style="fill: currentColor; stroke-width: .3;" points="8.609 6.696 8.609 15.304 16.261 11 8.609 6.696"/></svg>`;

    // TODO: Change the data-url here so that the embeds actually work.
    ratioContainer.innerHTML = `<button type="button" data-url="${data.attributes.embedUrl}" aria-label="${
        data.name
    }" class="embedVideo-playButton iconButton js-playVideo" style="background-image: url(${
        data.photoUrl
    });">${playIcon}</button>`;

    node.appendChild(ratioContainer);
}

/**
 * Handle a click on a video.
 */
function handlePlayVideo() {
    // Inside of delegate event `this` is the current target of the event.
    const playButton: HTMLElement = this;
    const container = playButton.closest(".embedVideo-ratio");
    if (container) {
        // Replace the preview with a functional iframe.
        container.innerHTML = `<iframe frameborder="0" allow="autoplay; encrypted-media" class="embedVideo-iframe" src="${
            playButton.dataset.url
        }" allowfullscreen></iframe>`;
    }
}
