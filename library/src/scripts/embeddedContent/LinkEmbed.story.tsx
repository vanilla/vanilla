/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { LinkEmbed } from "@library/embeddedContent/LinkEmbed";

const story = storiesOf("Embeds", module);

// tslint:disable:jsx-use-translation-function

const ipsum = `
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce blandit lorem ac dui porta, scelerisque placerat felis finibus. Fusce vitae porttitor augue. Integer sagittis justo vitae nibh aliquet, a viverra ipsum laoreet. Interdum et malesuada fames ac ante ipsum primis in faucibus. Curabitur elit ligula, fermentum nec felis vel, aliquam interdum justo. Suspendisse et egestas neque. Vivamus volutpat odio eget enim tincidunt, in pretium arcu consectetur. Nulla sodales molestie pharetra.
`;

const date = "2019-06-05 20:59:01";

story.add("LinkEmbed", () => {
    return (
        <>
            <StoryHeading depth={1}>COMPONENT: LinkEmbed</StoryHeading>

            <StoryHeading>w/ Image</StoryHeading>
            <LinkEmbed
                url="https://vanillaforums.com/en/"
                photoUrl="https://vanillaforums.com/images/metaIcons/vanillaForums.png"
                name="Online Community Software and Customer Forum Software by Vanilla Forums"
                embedType="link"
                body="Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback."
            />

            <StoryHeading>No Image</StoryHeading>
            <LinkEmbed
                url="https://test.com/test/test/test"
                name="Online Community Software and Customer Forum Software by Vanilla Forums"
                embedType="link"
                body={ipsum}
            />
        </>
    );
});
