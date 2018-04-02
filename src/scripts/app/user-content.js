/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { delegateEvent } from "@core/dom-utility";
import { setData, getData } from "@core/dom-utility";
import debounce from "lodash/debounce";
import shave from 'shave';

/**
 * Truncate embed link excerpts in a container
 *
 * @param {Element} container - Element containing embeds to truncate
 */
export function truncateEmbeds(container = document.body) {
    const embeds = container.querySelectorAll('.embedLink-excerpt');
    embeds.forEach(el => {
        let untruncatedText = getData(el, 'untruncatedText');

        if (!untruncatedText) {
            untruncatedText = el.innerHTML;
            setData(el, 'untruncatedText', untruncatedText);
        } else {
            el.innerHTML = untruncatedText;
        }
        truncateTextBasedOnMaxHeight(el);
    });
}

/**
 * Truncate element text based on max-height
 *
 * @param {Element} excerpt - The excerpt to truncate.
 */
export function truncateTextBasedOnMaxHeight(excerpt) {
    const maxHeight = parseInt(getComputedStyle(excerpt)['max-height'], 10);
    if(maxHeight && maxHeight > 0) {
        shave(excerpt, maxHeight);
    }
}

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


/**
 * Init handler for embeds
 */
export function setupEmbeds() {
    delegateEvent('click', '.js-playVideo', handlePlayVideo);
    truncateEmbeds();

    // Resize
    window.addEventListener("resize", debounce(truncateEmbeds, 200));
}

function handleToggleSpoiler() {
    const toggleButton = this;
    if (!(toggleButton instanceof HTMLElement)) {
        return;
    }

    const spoilerContainer = toggleButton.closest(".spoiler");
    spoilerContainer.classList.toggle("isShowingSpoiler");
}

export function setupSpoilers() {
    delegateEvent('click', '.js-toggleSpoiler', handleToggleSpoiler);
}
