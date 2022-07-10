/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ensureScript } from "@vanilla/dom-utils";
import { EmbedContent } from "@library/embeddedContent/components/EmbedContent";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import React, { useLayoutEffect } from "react";
import { EmbedContainer } from "@library/embeddedContent/components/EmbedContainer";

interface IProps extends IBaseEmbedProps {
    account: string;
    videoID: string;
    playerID: string;
    playerEmbed: string;
}

/**
 * A class for rendering Brightcove video embeds.
 */
export function BrightcoveEmbed(props: IProps): JSX.Element {
    const playerId = "player-" + Math.random().toString(36).substr(2, 9);

    const SCRIPT_PLAYER =
        "https://players.brightcove.net/" +
        props.account +
        "/" +
        props.playerID +
        "_" +
        props.playerEmbed +
        "/index.min.js";
    //Load JS based on account and player information.
    useLayoutEffect(() => {
        void ensureScript(SCRIPT_PLAYER);
    });
    const VideoJS = "video-js";
    // Aspect Ratio: Using `vjs-fluid` class - Waits for the video metadata to load then calculate the correct aspect ratio to use.
    return (
        <>
            <EmbedContainer className="embedVideo">
                <EmbedContent type={props.embedType}>
                    <VideoJS
                        id={playerId}
                        data-account={props.account}
                        data-player={props.playerID}
                        data-embed={props.playerEmbed}
                        data-video-id={props.videoID}
                        class="video-js vjs-fluid"
                        controls
                    ></VideoJS>
                </EmbedContent>
            </EmbedContainer>
        </>
    );
}
