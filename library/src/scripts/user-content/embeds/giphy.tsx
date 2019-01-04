/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import BaseEmbed from "@library/user-content/embeds/BaseEmbed";
import { registerEmbedComponent } from "@library/embeds";

export function initGiphyEmbeds() {
    registerEmbedComponent("giphy", GiphyEmbed);
}

export class GiphyEmbed extends BaseEmbed {
    public render() {
        const { data } = this.props;
        const { attributes, width, height } = data;
        const { postID } = attributes;
        const paddingBottom = ((height || 1) / (width || 1)) * 100 + "%";
        const ratioStyle: React.CSSProperties = {
            maxWidth: width ? width + "px" : "100%",
            paddingBottom,
        };

        if (!postID) {
            throw new Error("Giphy embed fail, the post could not be found");
        }
        const src = `https://giphy.com/embed/${postID}`;

        return (
            <div className="embedExternal-ratio" style={ratioStyle}>
                <iframe src={src} className="giphy-embed embedGiphy-iframe" />
            </div>
        );
    }
}
