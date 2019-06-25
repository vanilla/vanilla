/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import React, { useEffect } from "react";
import { ensureScript } from "@library/dom/domUtils";

interface IProps extends IBaseEmbedProps {
    version: number;
    postID: string;
    height?: number;
    width?: number;
    name?: string;
}

/**
 * A class for rendering Instagram embeds.
 */
export function InstagramEmbed(props: IProps): JSX.Element {
    useEffect(() => {
        void convertInstagramEmbeds();
    });

    const permaLink = `https://www.instagram.com/p/${props.postID}`;

    return (
        <EmbedContainer inEditor={props.inEditor}>
            <EmbedContent type="codepen" inEditor={props.inEditor}>
                <div class="embedExternal embedInstagram">
                    <div class="embedExternal-content">
                        <blockquote
                            className="instagram-media"
                            data-instgrm-captioned
                            data-instgrm-permalink={permaLink}
                            data-instgrm-version={props.version}
                        >
                            <a href={permaLink} children={permaLink} />
                        </blockquote>
                    </div>
                </div>
            </EmbedContent>
        </EmbedContainer>
    );
}

async function convertInstagramEmbeds() {
    const instagramEmbeds = document.querySelectorAll(".instagram-media");
    if (instagramEmbeds.length > 0) {
        await ensureScript("//platform.instagram.com/en_US/embeds.js");

        if (!window.instgrm) {
            throw new Error("The Instagram post failed to load");
        }

        window.instgrm.Embeds.process();
    }
}
