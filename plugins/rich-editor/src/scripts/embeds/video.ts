/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData } from "@core/embeds";
import { simplifyFraction } from "@core/utility";
import { t } from "@core/application";

export default function init() {
    registerEmbed("youtube", renderer);
    registerEmbed("vimeo", renderer);
}

export async function renderer(node: HTMLElement, data: IEmbedData) {
    node.classList.add("embed-video");
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
            ratioContainer.style.paddingTop = (data.height || 3) / (data.width || 4) * 100 + "%";
    }

    const playIcon = `<svg class="embedVideo-playIcon" xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 24 24"><title>${t(
        "Play Video",
    )}</title><path class="embedVideo-playIconPath embedVideo-playIconPath-circle" style="fill: currentColor; stroke-width: .3;" d="M11,0A11,11,0,1,0,22,11,11,11,0,0,0,11,0Zm0,20.308A9.308,9.308,0,1,1,20.308,11,9.308,9.308,0,0,1,11,20.308Z"/><polygon class="embedVideo-playIconPath embedVideo-playIconPath-triangle" style="fill: currentColor; stroke-width: .3;" points="8.609 6.696 8.609 15.304 16.261 11 8.609 6.696"/></svg>`;

    // TODO: Change the data-url here so that the embeds actually work.
    ratioContainer.innerHTML = `<button type="button" data-url="${data.url}" aria-label="${
        data.name
    }" class="embedVideo-playButton iconButton js-playVideo" style="background-image: url(${
        data.photoUrl
    });">${playIcon}</button>`;

    node.appendChild(ratioContainer);
}
