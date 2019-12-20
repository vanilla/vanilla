import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import { StoryContent } from "@library/storybook/StoryContent";
import { DataTabs } from "@library/sectioning/Tabs";
import React from "react";

const story = storiesOf("Tabs", module);

story.add("Tabs", () => {
    const tabData = [
        { label: "Styles", panelData: "styles" },
        { label: "Header", panelData: "header" },
        { label: "Footer", panelData: "footer" },
        { label: "CSS", panelData: "css" },
    ];

    return (
        <StoryContent>
            <StoryHeading>Simple Tab List </StoryHeading>
            <DataTabs data={tabData} />
        </StoryContent>
    );
});
