/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EmbedContainer } from "@library/embeddedContent/components/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/components/EmbedContent";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import React, { useLayoutEffect } from "react";
import { ensureScript } from "@vanilla/dom-utils";
import { useThrowError } from "@vanilla/react-utils";
import { InstagramPlaceholder } from "@library/embeddedContent/InstagramEmbedPlaceholder";

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
        convertInstagramEmbeds().catch(throwError);
    }, [throwError]);

    const permaLink = `https://www.instagram.com/p/${props.postID}`;

    const link = (
        <a href={permaLink} rel="nofollow noreferrer ugc">
            {permaLink}
        </a>
    );

    return (
        <EmbedContainer>
            <EmbedContent type={props.embedType}>
                <div className="embedExternal embedInstagram">
                    <div className="embedExternal-content">
                        <InstagramPlaceholder postID={props.postID} />
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

        return window.instgrm.Embeds.process();
    }
}
