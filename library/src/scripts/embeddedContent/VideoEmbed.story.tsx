/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { VideoEmbed as Video } from "@library/embeddedContent/VideoEmbed";

export default {
    component: Video,
    title: "Embeds",
};

export const VideoEmbed = () => {
    return (
        <Video
            embedType="youtube"
            url="https://www.youtube.com/watch?v=jGwO_UgTS7I"
            name="Lecture 1 - Welcome | Stanford CS229: Machine Learning (Autumn 2018)"
            width={640}
            height={360}
            photoUrl="https://i.ytimg.com/vi/jGwO_UgTS7I/hqdefault.jpg"
            frameSrc="https://www.youtube.com/embed/jGwO_UgTS7I?feature=oembed&autoplay=1"
        />
    );
};
