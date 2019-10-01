/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { EmbedRenderError } from "@library/embeddedContent/EmbedRenderError";
import AttachmentError from "@library/content/attachments/AttachmentError";
import StandardEmbedError from "@rich-editor/quill/blots/embeds/StandardEmbedError";
import AttachmentLoading from "@library/content/attachments/AttachmentLoading";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { AttachmentType } from "@library/content/attachments/AttatchmentType";

storiesOf("User Content", module).add("Errors", () => {
    const now = new Date();
    const doNothing = () => {
        return;
    };
    return (
        <StoryContent>
            <StoryHeading depth={1}>Errors</StoryHeading>
            <StoryHeading>Render Error</StoryHeading>
            <EmbedRenderError url={"https://google.ca"} />
            <StoryHeading>Attachment Error</StoryHeading>
            <AttachmentError message={"Sample message"} name={"Name of Error"} dateUploaded={now.toISOString()} />
            <StoryHeading>Embed Error</StoryHeading>
            <StandardEmbedError id={"error123"} onDismissClick={doNothing}>
                Standard Error
            </StandardEmbedError>
            <StoryHeading>Loading State</StoryHeading>
            <AttachmentLoading
                type={AttachmentType.WORD}
                size={25}
                dateUploaded={now.toISOString()}
                name={"Joe's super day"}
            />
        </StoryContent>
    );
});
