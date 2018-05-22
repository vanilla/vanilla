/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { delegateEvent, ensureScript } from "@core/dom";
import { setData, getData } from "@core/dom";
import debounce from "lodash/debounce";
import shave from "shave";
import { onContent } from "@core/application";

export default function init() {
    delegateEvent("click", ".js-playVideo", handlePlayVideo);
    truncateEmbedLinks();

    // Legacy twitter embeds.
    if (document.querySelectorAll(".twitter-card").length > 0) {
        return ensureScript("//platform.twitter.com/widgets.js").then(() => {
            if (window.twttr) {
                window.twttr.ready(convertLegacyTweetEmbeds);

                onContent(() => {
                    window.twttr.ready(convertLegacyTweetEmbeds);
                });
            }
        });
    }

    // Resize
    window.addEventListener("resize", () => debounce(truncateEmbedLinks, 200)());
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

/**
 * Handle a click on a video.
 *
 * @param event - The event.
 */
function handlePlayVideo() {
    const playButton = this;
    if (!(playButton instanceof HTMLElement)) {
        return;
    }
    const container = playButton.closest(".embedVideo-ratio");
    if (container) {
        container.innerHTML = `<iframe frameborder="0" allow="autoplay; encrypted-media" class="embedVideo-iframe" src="${
            playButton.dataset.url
        }" allowfullscreen></iframe>`;
    }
}

function convertLegacyTweetEmbeds() {
    document.querySelectorAll(".twitter-card").forEach(card => {
        if (!card.classList.contains("twitter-card-loaded") && !card.classList.contains("twitter-card-preload")) {
            const tweetUrl = card.getAttribute("data-tweeturl");
            const tweetID = card.getAttribute("data-tweetid");
            if (window.twttr) {
                card.classList.add("twitter-card-preload");
                window.twttr.widgets.createTweet(
                    tweetID,
                    card,
                    () => {
                        const url = card.querySelector(".tweet-url");
                        if (url) {
                            url.remove();
                        }
                        // Fade it in.
                        card.classList.add("twitter-card-loaded");
                    },
                    {
                        conversation: "none",
                    },
                );
            }
        }
    });
}
