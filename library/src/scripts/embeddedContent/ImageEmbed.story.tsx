/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { FileEmbed } from "@library/embeddedContent/FileEmbed";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { formatUrl } from "@library/utility/appUtils";
import { storiesOf } from "@storybook/react";
import React from "react";
import { ImageEmbed } from "@library/embeddedContent/ImageEmbed";
import { EmbedContext } from "@library/embeddedContent/embedService";

const reactionsStory = storiesOf("Embeds", module);

// tslint:disable:jsx-use-translation-function

const ipsum = `
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce blandit lorem ac dui porta, scelerisque placerat felis finibus. Fusce vitae porttitor augue. Integer sagittis justo vitae nibh aliquet, a viverra ipsum laoreet. Interdum et malesuada fames ac ante ipsum primis in faucibus. Curabitur elit ligula, fermentum nec felis vel, aliquam interdum justo. Suspendisse et egestas neque. Vivamus volutpat odio eget enim tincidunt, in pretium arcu consectetur. Nulla sodales molestie pharetra.
`;

const date = "2019-06-05 20:59:01";

reactionsStory.add("ImageEmbed", () => {
    return (
        <>
            <StoryHeading depth={1}>COMPONENT: ImageEmbed</StoryHeading>

            <StoryHeading>Normal</StoryHeading>
            <ImageEmbed
                type="image/png"
                size={1000000}
                name="hero image.png"
                embedType="image"
                dateInserted={date}
                url="https://success.vanillaforums.com/themes/success/design/images/home_bg_image.jpeg"
            />

            <StoryHeading>In Editor</StoryHeading>
            <EmbedContext.Provider value={{ inEditor: true, isSelected: true }}>
                <ImageEmbed
                    type="image/png"
                    size={1000000}
                    name="hero image.png"
                    embedType="image"
                    dateInserted={date}
                    url="https://success.vanillaforums.com/themes/success/design/images/home_bg_image.jpeg"
                />
            </EmbedContext.Provider>
        </>
    );
});
