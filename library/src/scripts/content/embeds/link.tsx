/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import BaseEmbed from "@library/content/embeds/BaseEmbed";
import { sanitizeUrl } from "@vanilla/utils";
import { registerEmbedComponent } from "@library/content/embeds/embedUtils";
import { metasClasses } from "@library/styles/metasStyles";
import classNames from "classnames";

export function initLinkEmbeds() {
    registerEmbedComponent("link", LinkEmbed);
}

export class LinkEmbed extends BaseEmbed {
    public render() {
        const { name, attributes, url, photoUrl, body } = this.props.data;
        const classesMetas = metasClasses();
        const title = name ? <h3 className="embedText-title">{name}</h3> : null;

        const source = <span className={classNames("embedLink-source", classesMetas.metaStyle)}>{url}</span>;

        let linkImage: JSX.Element | null = null;
        if (photoUrl) {
            linkImage = <img src={photoUrl} className="embedLink-image" aria-hidden="true" />;
        }
        const dateTime =
            attributes.timestamp && attributes.humanTime ? (
                <time
                    className={classNames("embedLink-dateTime", classesMetas.metaStyle)}
                    dateTime={attributes.timestamp}
                >
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
