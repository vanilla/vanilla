/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React, { useState } from "react";
import { Toast } from "./Toast";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import Button from "@library/forms/Button";

const formsStory = storiesOf("Components/Toast", module);

const StatefulToastStory = (props) => {
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
                <>{props.content}</>
            </Toast>
        </>
    );
};

formsStory.add("Persistent", () =>
    (() => {
        return (
            <>
                <StoryContent>
                    <StoryHeading depth={1}>Toast message</StoryHeading>
                    <StoryParagraph>This is a toast notification that is always visible</StoryParagraph>
                </StoryContent>
                <StatefulToastStory content={<>My content can be any react element</>} visibility={true} />
            </>
        );
    })(),
);

formsStory.add("Dismissible", () =>
    (() => {
        return (
            <>
                <StoryContent>
                    <StoryHeading depth={1}>Toast message</StoryHeading>
                    <StoryParagraph>
                        This is a toast notification that can be shown and hidden programmatically
                    </StoryParagraph>
                </StoryContent>
                <StatefulToastStory content={<>My content can be any react element</>} showExternalControl />
            </>
        );
    })(),
);

formsStory.add("Auto Dismiss", () =>
    (() => {
        return (
            <>
                <StoryContent>
                    <StoryHeading depth={1}>Toast message</StoryHeading>
                    <StoryParagraph>
                        This is a toast notification that will automatically close after 3 seconds
                    </StoryParagraph>
                </StoryContent>
                <StatefulToastStory
                    content={
                        <>
                            Here is some short lived content This is a toast notification that will automatically close
                            after 3 seconds This is a toast notification that will automatically close after 3 seconds
                            This is a toast notification that will automatically close after 3 seconds This is a toast
                            notification that will automatically close after 3 seconds This is a toast notification that
                            will automatically close after 3 seconds This is a toast notification that will
                            automatically close after 3 seconds This is a toast notification that will automatically
                            close after 3 seconds v This is a toast notification that will automatically close after 3
                            seconds
                        </>
                    }
                    showExternalControl
                    autoCloseDuration={3000}
                />
            </>
        );
    })(),
);
