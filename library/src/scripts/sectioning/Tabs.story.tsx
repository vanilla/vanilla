/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import { StoryContent } from "@library/storybook/StoryContent";
import { Tabs } from "@library/sectioning/Tabs";
import React from "react";
import TextEditor from "@library/textEditor/TextEditor";
import { StoryTextContent } from "@library/storybook/storyData";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";

export default {
    title: "Components/Tabs",
};

export function TextEditors() {
    const tabData = [
        { label: "Header", contents: <TextEditor language={"html"} /> },
        { label: "Footer", contents: <TextEditor language={"html"} /> },
        { label: "CSS", contents: <TextEditor language={"css"} /> },
        { label: "JS", contents: <TextEditor language={"javascript"} /> },
    ];

    return (
        <>
            <StoryContent>
                <StoryHeading>Simple Tab List </StoryHeading>
            </StoryContent>
            <Tabs data={tabData} />
        </>
    );
}

export function TabWithErrors() {
    return (
        <>
            <StoryContent>
                <StoryHeading>Standard Tab Styles</StoryHeading>
            </StoryContent>
            <Tabs
                data={[
                    {
                        label: "Tab 1",
                        panelData: "",
                        error: (
                            <>
                                <strong>Name</strong> is a required field.
                            </>
                        ),
                        contents: <StoryTextContent firstTitle={"Hello Tab 1"} />,
                    },
                    {
                        label: "Tab 2",
                        panelData: "",
                        contents: <StoryTextContent firstTitle={"Hello Tab 2"} />,
                    },
                    {
                        label: "Tab 3",
                        panelData: "",
                        contents: <StoryTextContent firstTitle={"Hello Tab 3"} />,
                    },
                    {
                        label: "Tab 4",
                        panelData: "",
                        disabled: true,
                        warning: (
                            <>
                                My tab is <strong>DISABLED</strong>.
                            </>
                        ),
                        contents: <StoryTextContent firstTitle={"Tab 4 (Disabled)"} />,
                    },
                ]}
            />
        </>
    );
}

export function TabBrowse(tabProps?: Partial<React.ComponentProps<typeof Tabs>>) {
    return (
        <>
            <StoryContent>
                <StoryHeading>Browse Tab Styles</StoryHeading>
            </StoryContent>
            <Tabs
                {...tabProps}
                tabType={TabsTypes.BROWSE}
                data={[
                    {
                        label: "Tab 1",
                        contents: <StoryTextContent firstTitle={"Hello Tab 1"} />,
                    },
                    {
                        label: "Tab 2",
                        contents: <StoryTextContent firstTitle={"Hello Tab 2"} />,
                    },
                    {
                        label: "Tab 3",
                        contents: <StoryTextContent firstTitle={"Hello Tab 3"} />,
                    },
                ]}
            />
        </>
    );
}

export function TabBrowseLarge() {
    return (
        <TabBrowse
            largeTabs
            extendContainer
            extraButtons={<Button baseClass={ButtonTypes.PRIMARY}>Extra Button</Button>}
        />
    );
}
