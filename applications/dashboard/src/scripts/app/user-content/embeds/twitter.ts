/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { ensureScript } from "@dashboard/dom";
import { onContent, onReady } from "@dashboard/application";
import { registerEmbed, IEmbedData } from "@dashboard/embeds";
import { logError } from "@dashboard/utility";

// Setup twitter embeds
onContent(convertTwitterEmbeds);
registerEmbed("twitter", renderTweet);

// Because the handling of this has absolutely no effect on anything else, the promise can be floating.
// We don't care when it completes.
// tslint:disable-next-line:no-floating-promises
convertTwitterEmbeds().then();

/**
 * Convert all of the twitter embeds in the page. This is for transforming twitter embeds that were
 * server rendered.
 *
 * @see library/Vanilla/Embeds/EmbedManager.php
 */
async function convertTwitterEmbeds() {
    const tweets = Array.from(document.querySelectorAll(".twitter-card"));
    if (tweets.length > 0) {
        await ensureScript("//platform.twitter.com/widgets.js");
        if (window.twttr) {
            const promises = tweets.map(contentElement => {
                // Get embed data out of the data attributes.
                const statusID = contentElement.getAttribute("data-tweetid");
                const url = contentElement.getAttribute("data-tweeturl") || "";

                const renderData: IEmbedData = {
                    type: "twitter",
                    url,
                    attributes: { statusID },
                };

                return renderTweet(null, contentElement as HTMLElement, renderData);
            });

            // Render all the pages twitter embeds at the same time.
            await Promise.all(promises);
        }
    }
}

/**
 * Render a single twitter embed.
 */
export async function renderTweet(rootElement: HTMLElement | null, contentElement: HTMLElement, data: IEmbedData) {
    // Ensure the twitter library is loaded.
    await ensureScript("//platform.twitter.com/widgets.js");

    if (!window.twttr) {
        throw new Error("The Twitter widget failed to load.");
    }

    // Ensure we have a status id to look up.
    if (data.attributes.statusID == null) {
        throw new Error("Attempted to embed a tweet but the statusID could not be found");
    }

    // Check that we haven't already started to load this embed (In the case multiple onContents are fired off).
    if (
        !contentElement.classList.contains("twitter-card-loaded") &&
        !contentElement.classList.contains("twitter-card-preload")
    ) {
        contentElement.classList.add("twitter-card-preload");

        // Render the embed.
        const options = { conversation: "none" };
        await window.twttr.widgets.createTweet(data.attributes.statusID, contentElement, options);

        // Remove a url if there is one around.
        const url = contentElement.querySelector(".tweet-url");
        if (url) {
            url.remove();
        }

        // Fade it in.
        contentElement.classList.add("twitter-card-loaded");
    }
}
