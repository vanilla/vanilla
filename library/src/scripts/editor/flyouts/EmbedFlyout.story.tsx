import React from "react";
import EmbedFlyout from "@library/editor/flyouts/EmbedFlyout";
import { StoryHeading } from "@library/storybook/StoryHeading";

export default {
    title: "Embeds/Pieces",
};

export const _EmbedFlyout = () => {
    return (
        <>
            <StoryHeading depth={1}>Insert Media Button</StoryHeading>
            <EmbedFlyout isVisible createEmbed={(url) => {}} createIframe={(options) => {}} />
        </>
    );
};

_EmbedFlyout.story = {
    name: "EmbedFlyout",
};
