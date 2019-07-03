/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ensureScript } from "@library/dom/domUtils";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import React from "react";
import { onContent } from "@library/utility/appUtils";

interface IProps extends IBaseEmbedProps {
    statusID: string;
}

/**
 * A class for rendering Twitter embeds.
 */
export function TwitterEmbed(props: IProps): JSX.Element {
    return (
        <EmbedContainer inEditor={props.inEditor}>
            <EmbedContent type={props.embedType} inEditor={props.inEditor}>
                <div className="embedExternal embedTwitter">
                    <div
                        className="embedExternal-content js-twitterCard"
                        data-tweeturl={props.url}
                        data-tweetid={props.statusID}
                    >
                        <a href={props.url} className="tweet-url" rel="nofollow">
                            {props.url}
                        </a>
                    </div>
                </div>
            </EmbedContent>
        </EmbedContainer>
    );
}

onContent(convertTwitterEmbeds);

/**
 * Render a single twitter embed.
 */
async function renderTweet(contentElement: HTMLElement) {
    if (!window.twttr) {
        throw new Error("The Twitter widget failed to load.");
    }

    // Ensure we have a status id to look up.
    const statusID = contentElement.getAttribute("data-tweetid");
    if (statusID == null) {
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
        await window.twttr.widgets.createTweet(statusID, contentElement, options);

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
async function convertTwitterEmbeds() {
    const tweets = Array.from(document.querySelectorAll(".js-twitterCard"));
    if (tweets.length > 0) {
        await ensureScript("//platform.twitter.com/widgets.js");
        if (window.twttr) {
            const promises = tweets.map(contentElement => {
                return renderTweet(contentElement as HTMLElement);
            });

            // Render all the pages twitter embeds at the same time.
            await Promise.all(promises);
        }
    }
}
