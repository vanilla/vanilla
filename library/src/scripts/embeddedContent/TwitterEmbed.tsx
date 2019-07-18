/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ensureScript } from "@library/dom/domUtils";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import React, { useEffect } from "react";
import { twitterEmbedClasses } from "@library/embeddedContent/twitterEmbedStyles";
import classNames from "classnames";

interface IProps extends IBaseEmbedProps {
    statusID: string;
}

const TWITTER_SCRIPT = "https://platform.twitter.com/widgets.js";

/**
 * A class for rendering Twitter embeds.
 */
export function TwitterEmbed(props: IProps): JSX.Element {
    const classes = twitterEmbedClasses();

    useEffect(() => {
        void convertTwitterEmbeds().then(props.onRenderComplete);
    }, []);

    return (
        <EmbedContent type={props.embedType} inEditor={props.inEditor}>
            <div
                className={classNames("js-twitterCard", classes.card)}
                data-tweeturl={props.url}
                data-tweetid={props.statusID}
            >
                <a href={props.url} className="tweet-url" rel="nofollow">
                    {props.url}
                </a>
            </div>
        </EmbedContent>
    );
}

TwitterEmbed.async = true;
TwitterEmbed.preloadScript = TWITTER_SCRIPT;

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
export async function convertTwitterEmbeds() {
    const tweets = Array.from(document.querySelectorAll(".js-twitterCard"));
    if (tweets.length > 0) {
        await ensureScript(TWITTER_SCRIPT);
        if (window.twttr) {
            const promises = tweets.map(contentElement => {
                return renderTweet(contentElement as HTMLElement);
            });

            // Render all the pages twitter embeds at the same time.
            await Promise.all(promises);
        }
    }
}
