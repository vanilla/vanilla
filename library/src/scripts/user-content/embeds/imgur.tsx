/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import BaseEmbed from "@library/user-content/embeds/BaseEmbed";
import { ensureScript } from "@library/dom";
import { onContent } from "@library/application";
import { registerEmbedComponent } from "@library/embeds";

export function initImgurEmbeds() {
    registerEmbedComponent("imgur", ImgurEmbed);
    onContent(convertImgurEmbeds);
}

export class ImgurEmbed extends BaseEmbed {
    public render() {
        const { data } = this.props;
        const { attributes } = data;
        const { postID, isAlbum } = attributes;

        const dataID = isAlbum ? `a/${postID}` : postID;
        const url = `//imgur.com/${postID}`;

        return <blockquote className="imgur-embed-pub" data-id={dataID} />;
    }

    public componentDidMount() {
        void convertImgurEmbeds().then(this.props.onRenderComplete);
    }

    public componentDidUpdate() {
        void convertImgurEmbeds().then(this.props.onRenderComplete);
    }
}

/**
 * Renders posted imgur embeds.
 */
export async function convertImgurEmbeds() {
    const images = Array.from(document.querySelectorAll(".imgur-embed-pub"));
    if (images.length > 0) {
        await ensureScript("//s.imgur.com/min/embed.js");

        if (!window.imgurEmbed) {
            throw new Error("The Imgur post failed to load");
        }

        if (window.imgurEmbed.createIframe) {
            const imagesLength = images.length;
            for (let i = 0; i < imagesLength; i++) {
                window.imgurEmbed.createIframe();
            }
        } else {
            window.imgurEmbed.tasks = images.length;
        }
    }
}
