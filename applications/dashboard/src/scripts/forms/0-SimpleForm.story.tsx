/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import Paragraph from "@library/layout/Paragraph";
import { StoryUnorderedList } from "@library/storybook/StoryUnorderedList";
import { StoryListItem } from "@library/storybook/StoryListItem";
import { StoryLink } from "@library/storybook/StoryLink";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";

const buttonStory = storiesOf("Dashboard/Forms", module).addDecorator(dashboardCssDecorator);

buttonStory.add("FormGroup", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Form Elements</StoryHeading>
            <form>
                <ul>
                    <DashboardFormGroup label="label" description="Here's some info text for this field.">
                        <DashboardInput />
                    </DashboardFormGroup>
                    <DashboardFormGroup label="Another (fake) Label">
                        <DashboardInput />
                    </DashboardFormGroup>
                </ul>
            </form>
        </StoryContent>
    );
});
