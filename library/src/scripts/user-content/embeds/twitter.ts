/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { ensureScript } from "@library/dom";
import { onContent } from "@library/application";
import { IEmbedData, IEmbedElements, registerEmbedRenderer } from "@library/embeds";
import { IScrapeData } from "@dashboard/@types/api";

export function initTwitterEmbeds() {
    registerEmbedRenderer("twitter", renderTweet);
    onContent(convertTwitterEmbeds);
    void convertTwitterEmbeds().then();
}

/**
 * Render a single twitter embed.
 */
export async function renderTweet(elements: IEmbedElements, data: IScrapeData) {
    const contentElement = elements.content;
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
        !contentElement.classList.contains("js-twitterCardLoaded") &&
        !contentElement.classList.contains("js-twitterCardPreload")
    ) {
        contentElement.classList.add("js-twitterCardPreload");

        // Render the embed.
        const options = { conversation: "none" };
        await window.twttr.widgets.createTweet(data.attributes.statusID, contentElement, options);
        // Remove a url if there is one around.
        const url = contentElement.querySelector(".tweet-url");
        if (url) {
            url.remove();
        }

        // Fade it in.
        contentElement.classList.add("js-twitterCardLoaded");
    }
}

/**
 * Convert all of the twitter embeds in the page. This is for transforming twitter embeds that were
 * server rendered.
 *
 * @see library/Vanilla/Embeds/EmbedManager.php
 */
export async function convertTwitterEmbeds() {
    const tweets = Array.from(document.querySelectorAll(".js-twitterCard"));
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

                return renderTweet({ content: contentElement as HTMLElement, root: null as any }, renderData);
            });

            // Render all the pages twitter embeds at the same time.
            await Promise.all(promises);
        }
    }
}
