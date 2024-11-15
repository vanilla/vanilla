import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { EmbedRenderError } from "@library/embeddedContent/components/EmbedRenderError";
import AttachmentError from "@library/content/attachments/AttachmentError";
import AttachmentLoading from "@library/content/attachments/AttachmentLoading";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { AttachmentType } from "@library/content/attachments/AttatchmentType";
import StandardEmbedError from "@library/vanilla-editor/StandardEmbedError";

export default {
    title: "User Content",
};

export const Errors = () => {
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
            <StandardEmbedError id={"error123"} onDismissClick={doNothing} error={{ message: "Standard Error" }} />
            <StoryHeading>Loading State</StoryHeading>
            <AttachmentLoading
                type={AttachmentType.WORD}
                size={25}
                dateUploaded={now.toISOString()}
                name={"Joe's super day"}
            />
        </StoryContent>
    );
};
