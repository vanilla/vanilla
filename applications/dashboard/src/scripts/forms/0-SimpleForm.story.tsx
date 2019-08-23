/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";

const formsStory = storiesOf("Dashboard/Forms", module).addDecorator(dashboardCssDecorator);

formsStory.add("FormGroup", () =>
    (() => {
        return (
            <StoryContent>
                <StoryHeading depth={1}>Simple Form Elements</StoryHeading>
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
                <StoryHeading depth={1}>Label Variants</StoryHeading>
                <form>
                    <ul>
                        <DashboardFormGroup
                            label="Normal label and form input"
                            description="Here's some info text for this field. I'm giving a little description of what this field does and how it affects the user."
                            labelType={DashboardLabelType.STANDARD} // Default
                        >
                            <DashboardInput />
                        </DashboardFormGroup>
                        <DashboardFormGroup
                            label="Wide label and narrow form input, useful for small text inputs or toggles"
                            description="Here's some info text for this field. I'm giving a little description of what this field does and how it affects the user."
                            labelType={DashboardLabelType.WIDE}
                        >
                            <DashboardInput />
                        </DashboardFormGroup>
                    </ul>
                </form>
            </StoryContent>
        );
    })(),
);
