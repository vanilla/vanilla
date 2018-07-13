/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import ReactDOM from "react-dom";
import { registerEmbed, IEmbedData, IEmbedElements } from "@dashboard/embeds";
import { cssSpecialChars } from "@dashboard/utility";

export default function LinkEmbed(embedData: IEmbedData) {
    const { name, attributes, url, photoUrl, body } = embedData;
    const title = name ? <h3 className="embedLink-title">name</h3> : null;
    const userPhoto =
        attributes.userPhoto && attributes.userName ? (
            <span className="embedLink-userPhoto PhotoWrap">
                <img
                    src={attributes.userPhoto}
                    alt={attributes.userName}
                    className="ProfilePhoto ProfilePhotoMedium"
                    tabIndex={-1}
                />
            </span>
        ) : null;

    const source = <span className="embedLink-source meta">{url}</span>;

    let linkImage: JSX.Element | null = null;
    if (photoUrl) {
        const imageStyle: React.CSSProperties = {
            backgroundImage: `url('${cssSpecialChars(photoUrl)}')`,
        };
        linkImage = <div className="embedLink-image" aria-hidden="true" style={imageStyle} />;
    }

    const userName = attributes.userName ? <span className="embedLink-userName">{attributes.userName}</span> : null;
    const dateTime =
        attributes.timestamp && attributes.humanTime ? (
            <time className="embedLink-dateTime meta" dateTime={attributes.timestamp}>
                {attributes.humanTime}
            </time>
        ) : null;

    return (
        <a href={url} rel="noreferrer">
            <article className="embedLink-body">
                {linkImage}
                <div className="embedLink-main">
                    <div className="embedLink-header">
                        {title}
                        {userPhoto}
                        {userName}
                        {dateTime}
                        {source}
                    </div>
                    <div className="embedLink-excerpt">{body}</div>
                </div>
            </article>
        </a>
    );
}
