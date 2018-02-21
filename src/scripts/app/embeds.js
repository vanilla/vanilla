/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */


import { delegateEvent } from "@core/dom-utility";

/**
 * Handle a click on a video.
 *
 * @param {Event} event - The event.
 */
function handlePlayVideo() {
    const playButton = this;
    if (!(playButton instanceof HTMLElement)) {
        return;
    }
    const container = playButton.closest(".embedVideo-ratio");
    container.innerHTML = `<iframe frameborder="0" allow="autoplay; encrypted-media" class="embedVideo-iframe" src="${playButton.dataset.url}" allowfullscreen></iframe>`;
}

export function setupEmbeds() {
    delegateEvent('click', '.js-playVideo', handlePlayVideo);
}

