/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import React, { useEffect, useLayoutEffect } from "react";
import { ensureScript } from "@vanilla/dom-utils";
import { useThrowError } from "@vanilla/react-utils";

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
    const throwError = useThrowError();
    useLayoutEffect(() => {
        void convertInstagramEmbeds().catch(throwError);
    });

    const permaLink = `https://www.instagram.com/p/${props.postID}`;

    return (
        <EmbedContainer inEditor={props.inEditor}>
            <EmbedContent type={props.embedType} inEditor={props.inEditor}>
                <div className="embedExternal embedInstagram">
                    <div className="embedExternal-content">
                        <blockquote
                            className="instagram-media"
                            data-instgrm-captioned
                            data-instgrm-permalink={permaLink}
                            data-instgrm-version={props.version}
                        >
                            <a href={permaLink}>{permaLink}</a>
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
        await ensureScript("https://platform.instagram.com/en_US/embeds.js");

        if (!window.instgrm) {
            throw new Error("The Instagram post failed to load");
        }

        window.instgrm.Embeds.process();
    }
}
