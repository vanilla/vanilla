/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { storiesOf } from "@storybook/react";
import React from "react";
import EmbedFlyout from "@library/editor/flyouts/EmbedFlyout";
import { StoryHeading } from "@library/storybook/StoryHeading";

const story = storiesOf("Embeds/Pieces", module);

story.add("EmbedFlyout", () => {
    return (
        <>
            <StoryHeading depth={1}>Insert Media Button</StoryHeading>
            <EmbedFlyout isVisible createEmbed={(url) => {}} createIframe={(options) => {}} />
        </>
    );
});
