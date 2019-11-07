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
import React, { useState } from "react";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { DashboardImageUploadGroup } from "@dashboard/forms/DashboardImageUploadGroup";

const formsStory = storiesOf("Dashboard/Forms", module).addDecorator(dashboardCssDecorator);

formsStory.add("FormGroup", () =>
    (() => {
        const [dropdownValue, setDropdownValue] = useState<IComboBoxOption | null>(null);
        const [image, setImage] = useState<string | null>(null);

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
                        <DashboardFormGroup label="Select input">
                            <DashboardSelect
                                options={[
                                    {
                                        label: "Development",
                                        value: 4,
                                    },
                                    {
                                        label: "Information Security",
                                        value: 7,
                                    },
                                    {
                                        label: "Internal Testing",
                                        value: 6,
                                    },
                                    {
                                        label: "Success",
                                        value: 5,
                                    },
                                    {
                                        label: "Support",
                                        value: 8,
                                    },
                                ]}
                                value={dropdownValue!}
                                onChange={setDropdownValue}
                            />
                        </DashboardFormGroup>
                        <DashboardImageUploadGroup
                            value={image}
                            onChange={setImage}
                            imageUploader={() => {
                                // Always returns null for the story.
                                // Stubbed out because we may not have a real API available.
                                return Promise.resolve(null) as any;
                            }}
                            label="Image Upload"
                            description="An image upload can have a description. Ideally it should describe things like the expected image dimensions"
                        />
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
