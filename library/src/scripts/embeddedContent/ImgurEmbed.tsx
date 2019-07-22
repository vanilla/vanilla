/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ensureScript } from "@vanilla/dom-utils";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import React, { useEffect } from "react";

interface IProps extends IBaseEmbedProps {
    height: number;
    imgurID: string;
    isAlbum: boolean;
    width: number;
}

/**
 * A class for rendering Imgur embeds.
 */
export function ImgurEmbed(props: IProps): JSX.Element {
    useEffect(() => {
        void convertImgurEmbeds();
    });

    return (
        <EmbedContent type={props.embedType} inEditor={props.inEditor}>
            <blockquote className="imgur-embed-pub" data-id={props.imgurID} />
        </EmbedContent>
    );
}

async function convertImgurEmbeds() {
    const images = Array.from(document.querySelectorAll(".imgur-embed-pub"));
    if (images.length > 0) {
        await ensureScript("https://s.imgur.com/min/embed.js");

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
