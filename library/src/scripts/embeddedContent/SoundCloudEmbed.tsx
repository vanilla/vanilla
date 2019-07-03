/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";

interface IProps extends IBaseEmbedProps {
    playlistID?: number;
    showArtwork: boolean;
    trackID?: number;
    userID?: number;
    useVisualPlayer: boolean;
}

/**
 * A class for rendering SoundCloud embeds.
 */
export function SoundCloudEmbed(props: IProps): JSX.Element {
    const frameSource = soundCloudPlayerUrl(props);

    return (
        <EmbedContainer inEditor={props.inEditor}>
            <EmbedContent type={props.embedType} inEditor={props.inEditor}>
                <div className="embedExternal embedSoundCloud">
                    <div className="embedExternal-content">
                        <iframe width="100%" scrolling="no" src={frameSource} />
                    </div>
                </div>
            </EmbedContent>
        </EmbedContainer>
    );
}

function soundCloudPlayerUrl(props: IProps): string {
    let resourceType: string;
    let resourceID: number;

    if (props.playlistID) {
        resourceType = "playlists";
        resourceID = props.playlistID;
    } else if (props.trackID) {
        resourceType = "tracks";
        resourceID = props.trackID;
    } else if (props.userID) {
        resourceType = "users";
        resourceID = props.userID;
    } else {
        throw new Error("Unable to determine SoundCloud resource type.");
    }

    const parameters = {
        show_artwork: props.showArtwork,
        visual: props.useVisualPlayer,
        url: `https://api.soundcloud.com/${resourceType}/${resourceID}`,
    };
    const query = Object.keys(parameters)
        .map(key => encodeURIComponent(key) + "=" + encodeURIComponent(parameters[key]))
        .join("&");

    return `https://w.soundcloud.com/player/?${query}`;
}
