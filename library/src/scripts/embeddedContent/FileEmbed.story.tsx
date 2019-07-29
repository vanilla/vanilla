/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EmbedContainer, EmbedContainerSize } from "@library/embeddedContent/EmbedContainer";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { FileEmbed } from "@library/embeddedContent/FileEmbed";
import DateTime from "@library/content/DateTime";
import { formatUrl } from "@library/utility/appUtils";

const reactionsStory = storiesOf("Embeds", module);

// tslint:disable:jsx-use-translation-function

const ipsum = `
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce blandit lorem ac dui porta, scelerisque placerat felis finibus. Fusce vitae porttitor augue. Integer sagittis justo vitae nibh aliquet, a viverra ipsum laoreet. Interdum et malesuada fames ac ante ipsum primis in faucibus. Curabitur elit ligula, fermentum nec felis vel, aliquam interdum justo. Suspendisse et egestas neque. Vivamus volutpat odio eget enim tincidunt, in pretium arcu consectetur. Nulla sodales molestie pharetra.
`;

const date = "2019-06-05 20:59:01";

reactionsStory.add("FileEmbed", () => {
    return (
        <>
            <StoryHeading depth={1}>COMPONENT: FileEmbed</StoryHeading>

            <StoryHeading>Image</StoryHeading>
            <FileEmbed
                type="image/png"
                size={1000000}
                name="stuck_out_tongue_closed_eyes.png"
                embedType="file"
                dateInserted={date}
                url={formatUrl("/resources/emoji/stuck_out_tongue_closed_eyes.png")}
            />

            <StoryHeading>PDF</StoryHeading>
            <FileEmbed
                type="application/pdf"
                size={123141}
                name="My PDF.png"
                embedType="file"
                dateInserted={date}
                url="#"
            />

            <StoryHeading>Archive</StoryHeading>
            <FileEmbed
                type="application/x-rar-compressed"
                size={324}
                name="Word Document.rar"
                embedType="file"
                dateInserted={date}
                url="#"
            />

            <StoryHeading>Word</StoryHeading>
            <FileEmbed
                type="application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                size={515234123}
                name="Word Document.docx"
                embedType="file"
                dateInserted={date}
                url="#"
            />
        </>
    );
});
