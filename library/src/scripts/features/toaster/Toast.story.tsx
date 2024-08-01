/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { StoryContent } from "@library/storybook/StoryContent";
import { STORY_IPSUM_SHORT } from "@library/storybook/storyData";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryObj } from "@storybook/react";
import React, { useState } from "react";
import { Toast } from "./Toast";

export default {
    title: "Components",
};

const StatefulToastStory = (props: React.ComponentProps<typeof Toast> & { showExternalControl?: boolean }) => {
    const [visibility, setVisibility] = useState(true);

    return (
        <>
            {props.showExternalControl && (
                <StoryContent>
                    <Button buttonType={ButtonTypes.PRIMARY} onClick={() => setVisibility(!visibility)}>
                        {visibility ? "Hide" : "Reveal"} Toast
                    </Button>
                </StoryContent>
            )}
            <Toast visibility={visibility} onVisibilityChange={setVisibility} {...props}>
                <>{props.children}</>
            </Toast>
        </>
    );
};

export const ToastStory: StoryObj<any> = () => {
    return (
        <StoryContent>
            <StoryHeading>Always visible</StoryHeading>
            <StoryParagraph>This is a toast notification that is always visible</StoryParagraph>
            <StatefulToastStory visibility={true}>My content can be any react element</StatefulToastStory>
            <StoryHeading>Show and Hide</StoryHeading>
            <StoryParagraph>This is a toast notification that can be shown and hidden programmatically</StoryParagraph>
            <StatefulToastStory showExternalControl>My content can be any react element</StatefulToastStory>
            <StoryHeading>Automatic Dismiss</StoryHeading>
            <StoryParagraph>This is a toast notification that will automatically close after 3 seconds</StoryParagraph>
            <StatefulToastStory showExternalControl autoCloseDuration={3000}>
                Here is some short lived content This is a toast notification that will automatically close after 3
                seconds This is a toast notification that will automatically close after 3 seconds This is a toast
                notification that will automatically close after 3 seconds This is a toast notification that will
                automatically close after 3 seconds This is a toast notification that will automatically close after 3
                seconds This is a toast notification that will automatically close after 3 seconds This is a toast
                notification that will automatically close after 3 seconds v This is a toast notification that will
                automatically close after 3 seconds
            </StatefulToastStory>
            <StoryHeading>Wide Toast</StoryHeading>
            <StoryParagraph>This is a wider toast variation</StoryParagraph>
            <StatefulToastStory wide>
                <div style={{ display: "flex", gap: 8 }}>
                    <div style={{ flex: 1 }}>
                        <strong>Title for the Toast</strong>
                        <p>{STORY_IPSUM_SHORT}</p>
                    </div>
                    <Button buttonType={ButtonTypes.TEXT}>Action</Button>
                </div>
            </StatefulToastStory>
        </StoryContent>
    );
};

ToastStory.storyName = "Toast";
