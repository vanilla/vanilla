/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardCheckBox } from "@dashboard/forms/DashboardCheckBox";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { DashboardCheckGroup, DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React, { useState } from "react";

const formsStory = storiesOf("Dashboard/Forms", module).addDecorator(dashboardCssDecorator);

formsStory.add("RadioGroups", () =>
    (() => {
        const [group1, setGroup1] = useState("option1");
        const [group2, setGroup2] = useState("option1");

        return (
            <StoryContent>
                <StoryHeading depth={1}>Radio Groups</StoryHeading>
                <form>
                    <ul>
                        <DashboardFormGroup label="Radio Group Vertical (default)">
                            <DashboardRadioGroup onChange={setGroup1} value={group1}>
                                <DashboardRadioButton value={"option1"} label="Option 1" />
                                <DashboardRadioButton value={"option2"} label="Option 2" />
                                <DashboardRadioButton value={"option3"} label="Option 3" />
                            </DashboardRadioGroup>
                        </DashboardFormGroup>
                        <DashboardFormGroup label="Radio Group Inline">
                            <DashboardRadioGroup onChange={setGroup2} value={group2} isInline>
                                <DashboardRadioButton value={"option1"} label="Option 1" />
                                <DashboardRadioButton value={"option2"} label="Option 2" />
                                <DashboardRadioButton value={"option3"} label="Option 3" disabled />
                            </DashboardRadioGroup>
                        </DashboardFormGroup>
                        <DashboardFormGroup
                            label="Checkbox Group Vertical (default)"
                            description="Here's some info text for this field. I'm giving a little description of what this field does and how it affects the user."
                            labelType={DashboardLabelType.WIDE}
                        >
                            <DashboardCheckGroup>
                                <DashboardCheckBox label="Check 1" />
                                <DashboardCheckBox label="Option 2" />
                                <DashboardCheckBox label="Option 3" disabled />
                            </DashboardCheckGroup>
                        </DashboardFormGroup>
                        <DashboardFormGroup label="Checkbox Group Inline" description="Check out this description">
                            <DashboardCheckGroup isInline>
                                <DashboardCheckBox label="Option 1" />
                                <DashboardCheckBox label="Option 2" />
                                <DashboardCheckBox label="Option 3" disabled />
                            </DashboardCheckGroup>
                        </DashboardFormGroup>
                    </ul>
                </form>
            </StoryContent>
        );
    })(),
);
