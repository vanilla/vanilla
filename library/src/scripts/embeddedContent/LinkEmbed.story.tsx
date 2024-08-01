/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";
import { LinkEmbed } from "@library/embeddedContent/LinkEmbed";
import { EmbedContext } from "@library/embeddedContent/IEmbedContext";
import { css } from "@emotion/css";

export default {
    title: "Embeds",
};

const ipsum = `
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce blandit lorem ac dui porta, scelerisque placerat felis finibus. Fusce vitae porttitor augue. Integer sagittis justo vitae nibh aliquet, a viverra ipsum laoreet. Interdum et malesuada fames ac ante ipsum primis in faucibus. Curabitur elit ligula, fermentum nec felis vel, aliquam interdum justo. Suspendisse et egestas neque. Vivamus volutpat odio eget enim tincidunt, in pretium arcu consectetur. Nulla sodales molestie pharetra.
`;

export const _LinkEmbed = () => {
    return (
        <>
            <StoryHeading depth={1}>COMPONENT: LinkEmbed</StoryHeading>

            <StoryHeading>w/ Image</StoryHeading>
            <LinkEmbed
                url="https://vanillaforums.com/en/"
                photoUrl="https://us.v-cdn.net/6030677/uploads/ZQ4WYB5DOIVQ/microsoftteams-image-2818-29.png"
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

            <StoryHeading>Inline Variant </StoryHeading>
            <p style={{ fontSize: 16 }}>
                some text here
                <span style={{ marginLeft: 4, marginRight: 4 }}>
                    <LinkEmbed
                        url="https://test.com/test/test/test"
                        name="Online Community Software and Customer Forum Software by Vanilla Forums"
                        embedType="link"
                        body={ipsum}
                        embedStyle="rich_embed_inline"
                    />
                </span>
                some more text here
            </p>
            <StoryHeading>Inline Variant With Favicon</StoryHeading>
            <p style={{ fontSize: 16 }}>
                some text here
                <span style={{ marginLeft: 4, marginRight: 4 }}>
                    <LinkEmbed
                        url="https://test.com/test/test/test"
                        name="Online Community Software and Customer Forum Software by Vanilla Forums"
                        embedType="link"
                        body={ipsum}
                        faviconUrl="https://us.v-cdn.net/6030677/uploads/ZQ4WYB5DOIVQ/microsoftteams-image-2818-29.png"
                        embedStyle="rich_embed_inline"
                    />
                </span>
                some more text here
            </p>

            <StoryHeading>In Editor</StoryHeading>
            <EmbedContext.Provider value={{ inEditor: true, isSelected: true }}>
                <LinkEmbed
                    url="https://vanillaforums.com/en/"
                    photoUrl="https://us.v-cdn.net/6030677/uploads/ZQ4WYB5DOIVQ/microsoftteams-image-2818-29.png"
                    name="Online Community Software and Customer Forum Software by Vanilla Forums"
                    embedType="link"
                    body="Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback."
                />
            </EmbedContext.Provider>
        </>
    );
};

_LinkEmbed.story = {
    name: "LinkEmbed",
};
