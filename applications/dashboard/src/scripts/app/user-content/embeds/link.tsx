/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import BaseEmbed from "@dashboard/app/user-content/embeds/BaseEmbed";
import { sanitizeUrl } from "@dashboard/utility";
import { getData, setData } from "@dashboard/dom";
import debounce from "lodash/debounce";
import shave from "shave";
import { registerEmbedComponent } from "@dashboard/embeds";

export function initLinkEmbeds() {
    registerEmbedComponent("link", LinkEmbed);
    truncateEmbedLinks();

    // Retruncate links when the window resizes.
    window.addEventListener("resize", () => debounce(truncateEmbedLinks, 200)());
}

export class LinkEmbed extends BaseEmbed {
    public render() {
        const { name, attributes, url, photoUrl, body } = this.props.data;
        const title = name ? <h3 className="embedText-title embedLink-title">{name}</h3> : null;

        const source = <span className="embedLink-source meta">{url}</span>;

        let linkImage: JSX.Element | null = null;
        if (photoUrl) {
            linkImage = <img src={photoUrl} className="embedLink-image" aria-hidden="true" />;
        }
        const dateTime =
            attributes.timestamp && attributes.humanTime ? (
                <time className="embedLink-dateTime meta" dateTime={attributes.timestamp}>
                    {attributes.humanTime}
                </time>
            ) : null;

        const sanitizedUrl = sanitizeUrl(url);
        return (
            <a className="embedLink-link" href={sanitizedUrl} rel="noreferrer">
                <article className="embedText-body embedLink-body">
                    {linkImage}
                    <div className="embedText-main embedLink-main">
                        <div className="embedText-header embedLink-header">
                            {title}
                            {dateTime}
                            {source}
                        </div>
                        <div className="embedLink-excerpt">{body}</div>
                    </div>
                </article>
            </a>
        );
    }
}

/**
 * Truncate embed link excerpts in a container
 *
 * @param container - Element containing embeds to truncate
 */
function truncateEmbedLinks(container = document.body) {
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
function truncateTextBasedOnMaxHeight(excerpt: Element) {
    const maxHeight = parseInt(getComputedStyle(excerpt)["max-height"], 10);
    if (maxHeight && maxHeight > 0) {
        shave(excerpt, maxHeight);
    }
}
