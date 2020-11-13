import React from "react";
import { storiesOf } from "@storybook/react";
import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";
import Button from "@library/forms/Button";
import { StoryTiles } from "@library/storybook/StoryTiles";
import { StoryTileAndText } from "@library/storybook/StoryTileAndText";
import { DashboardPager } from "@dashboard/components/DashboardPager";

const formsStory = storiesOf("Dashboard", module).addDecorator(dashboardCssDecorator);

formsStory.add("Pagers", () => (
    <StoryContent>
        <StoryHeading depth={1}>Dashboard Pagers</StoryHeading>
        <StoryParagraph>
            The pager is a dumb component that just takes properties describing paging information. It fires events when
            page buttons are clicked. You can specify a <code>pageCount</code> for the pager. If you don&apos;t have a
            count then use the <code>hasNext</code> prop instead.
        </StoryParagraph>
        <StoryTiles>
            <StoryTileAndTextCompact text="With Page Count">
                <DashboardPager page={2} pageCount={3} />
            </StoryTileAndTextCompact>
            <StoryTileAndTextCompact text="Without Page Count">
                <DashboardPager page={2} hasNext={true} />
            </StoryTileAndTextCompact>
            <StoryTileAndTextCompact text="First Page">
                <DashboardPager page={1} pageCount={3} />
            </StoryTileAndTextCompact>
            <StoryTileAndTextCompact text="Last Page">
                <DashboardPager page={5} hasNext={false} />
            </StoryTileAndTextCompact>
            <StoryTileAndTextCompact text="Last Page With Count">
                <DashboardPager page={10} pageCount={10} />
            </StoryTileAndTextCompact>
            <StoryTileAndTextCompact text="One Page">
                <DashboardPager page={1} pageCount={1} />
            </StoryTileAndTextCompact>
        </StoryTiles>
    </StoryContent>
));
