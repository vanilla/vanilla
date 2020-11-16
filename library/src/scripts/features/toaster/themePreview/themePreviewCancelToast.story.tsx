/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import Toast from "../Toast";
import { ButtonTypes } from "@library/forms/buttonTypes";

const formsStory = storiesOf("Theme UI/Toast", module);

formsStory.add("Theme Preview (Cancel)", () =>
    (() => {
        return (
            <>
                <StoryContent>
                    <StoryHeading depth={1}>Theme preview toast: Cancel loading</StoryHeading>
                </StoryContent>
                <Toast
                    links={[
                        {
                            name: "Apply",
                            type: ButtonTypes.TEXT,
                        },
                        {
                            name: "Cancel",
                            type: ButtonTypes.TEXT_PRIMARY,
                            isLoading: true,
                        },
                    ]}
                    message={
                        <>
                            You are previewing the <b>Foundation</b> theme.
                        </>
                    }
                />
            </>
        );
    })(),
);
