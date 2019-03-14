/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import BaseEmbed from "@library/content/embeds/BaseEmbed";
import { registerEmbedComponent } from "@library/content/embeds/embedUtils";

export function initSoundcloudEmbeds() {
    registerEmbedComponent("soundcloud", SoundcloudEmbed);
}

export class SoundcloudEmbed extends BaseEmbed {
    public render() {
        const { attributes } = this.props.data;
        const { postID, visual, showArtwork, embedUrl } = attributes;

        // Ensure this is a track.
        if (postID == null) {
            throw new Error("Soundcloud embed fail, the track could not be found");
        }

        const url = embedUrl + postID + "&visual=" + (visual || "false") + "&show_artwork=" + (showArtwork || "false");

        return <iframe width="100%" scrolling="no" frameBorder="no" src={url} />;
    }
}
