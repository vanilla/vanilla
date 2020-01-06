import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import { StoryContent } from "@library/storybook/StoryContent";
import { DataTabs } from "@library/sectioning/Tabs";
import React from "react";
import TextEditor from "@library/textEditor/TextEditor";

const story = storiesOf("Tabs", module);

story.add("Tabs", () => {
    const tabData = [
        { label: "Header", panelData: "header", contents: <TextEditor language={"html"} /> },
        { label: "Footer", panelData: "footer", contents: <TextEditor language={"html"} /> },
        { label: "CSS", panelData: "css", contents: <TextEditor language={"css"} /> },
        { label: "JS", panelData: "js", contents: <TextEditor language={"javascript"} /> },
    ];

    return (
        <>
            <StoryContent>
                <StoryHeading>Simple Tab List </StoryHeading>
            </StoryContent>
            <DataTabs data={tabData} />
        </>
    );
});
