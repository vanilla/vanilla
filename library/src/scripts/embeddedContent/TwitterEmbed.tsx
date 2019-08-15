/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ensureScript } from "@vanilla/dom-utils";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { twitterEmbedClasses } from "@library/embeddedContent/twitterEmbedStyles";
import { visibility } from "@library/styles/styleHelpers";
import classNames from "classnames";
import React, { useEffect, useState, useLayoutEffect } from "react";
import { EmbedRenderError } from "@library/embeddedContent/EmbedRenderError";

interface IProps extends IBaseEmbedProps {
    statusID: string;
}

const TWITTER_SCRIPT = "https://platform.twitter.com/widgets.js";

function useErrorPropagationEffect(callback: () => void | Promise<void>, args: any[]) {
    const [state, setState] = useState();

    useEffect(() => {
        const handleError = (e: Error) => {
            console.log("handling error");
            setState(() => {
                throw e;
            });
        };

        try {
            var result = callback();
        } catch (e) {
            handleError(e);
        }
        if (result instanceof Promise) {
            result.catch(handleError);
        }
    }, [callback, setState, ...args]);
}

/**
 * A class for rendering Twitter embeds.
 */
export function TwitterEmbed(props: IProps): JSX.Element {
    const [twitterLoaded, setTwitterLoaded] = useState(false);
    const classes = twitterEmbedClasses();
    const { onRenderComplete } = props;

    useLayoutEffect(() => {
        // Don't count our tweet as rendered until the tweet is fully loaded.
        onRenderComplete && onRenderComplete();
    }, [twitterLoaded, onRenderComplete]);

    useErrorPropagationEffect(() => {
        void convertTwitterEmbeds().then(() => {
            // We need to track the load status for the internal representation.
            // Otherwise we end up with a flash of the URL next to the rendered tweet.
            setTwitterLoaded(true);
        });
    }, [setTwitterLoaded]);

    return (
        <>
            {!twitterLoaded && (
                <div className={classNames("embedLinkLoader")}>
                    <a href={props.url} className="embedLinkLoader-link" rel="nofollow">
                        {props.url} <span aria-hidden="true" className="embedLinkLoader-loader" />
                    </a>
                </div>
            )}
            <EmbedContent
                type={props.embedType}
                inEditor={props.inEditor}
                isSmall
                className={twitterLoaded ? undefined : visibility().displayNone}
            >
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
        </>
    );
}

TwitterEmbed.async = true;

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

        // Remove a url if there is one around.
        const url = contentElement.querySelector(".tweet-url");
        if (url) {
            url.remove();
        }

        // Render the embed.
        const options = { conversation: "none" };
        await window.twttr.widgets.createTweet(statusID, contentElement, options);

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
        const promises = tweets.map(contentElement => {
            return renderTweet(contentElement as HTMLElement);
        });

        // Render all the pages twitter embeds at the same time.
        await Promise.all(promises);
    }
}
