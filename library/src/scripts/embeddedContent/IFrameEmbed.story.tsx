/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { BrightcoveEmbed } from "@library/embeddedContent/BrightcoveEmbed";
import { IFrameEmbed as IFrame } from "@library/embeddedContent/IFrameEmbed";
import React from "react";

export default {
    component: IFrame,
    title: "Embeds",
};

export const IFrameEmbedNoAttributes = () => {
    return <IFrame embedType={"iframe"} url={"/iframe.html?args=&id=layout-panellayout--dark-mode&viewMode=story"} />;
};

export const IFrameEmbedSizeAttributes = () => {
    return (
        <IFrame
            embedType={"iframe"}
            url={"/iframe.html?args=&id=layout-panellayout--dark-mode&viewMode=story"}
            width="400"
            height="300"
        />
    );
};

export const Brightcove = () => {
    return (
        <BrightcoveEmbed
            embedType={"brightcove"}
            url={"https://players.brightcove.net/20318290001/default_default/index.html?videoId=6041890955001"}
        />
    );
};
