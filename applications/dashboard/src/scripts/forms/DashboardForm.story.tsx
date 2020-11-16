/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardCheckBox } from "@dashboard/forms/DashboardCheckBox";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardImageUploadGroup } from "@dashboard/forms/DashboardImageUploadGroup";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { DashboardCheckGroup, DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import React, { useState } from "react";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { t } from "@vanilla/library/src/scripts/utility/appUtils";
import Translate from "@vanilla/library/src/scripts/content/Translate";

export default {
    title: "Dashboard/Forms",
    decorators: [dashboardCssDecorator],
};

export function SimpleInputItems() {
    const [dropdownValue, setDropdownValue] = useState<IComboBoxOption | null>(null);
    const [image, setImage] = useState<string | null>(null);
    return (
        <>
            <DashboardFormSubheading>Form Subheading</DashboardFormSubheading>
            <DashboardFormGroup label="label" description="Here's some info text for this field.">
                <DashboardInput inputProps={{ placeholder: "Placeholder Here" }} />
            </DashboardFormGroup>
            <DashboardFormGroup label="Another (fake) Label">
                <DashboardInput inputProps={{ placeholder: "Placeholder Here" }} />
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
            <DashboardFormSubheading>Label Variants</DashboardFormSubheading>
            <DashboardFormGroup
                label="Normal label and form input"
                description="Here's some info text for this field. I'm giving a little description of what this field does and how it affects the user."
                labelType={DashboardLabelType.STANDARD} // Default
            >
                <DashboardInput inputProps={{ placeholder: "Placeholder Here" }} />
            </DashboardFormGroup>
            <DashboardFormGroup
                label="Wide label and narrow form input, useful for small text inputs or toggles"
                description="Here's some info text for this field. I'm giving a little description of what this field does and how it affects the user."
                labelType={DashboardLabelType.WIDE}
            >
                <DashboardInput inputProps={{ placeholder: "Placeholder Here" }} />
            </DashboardFormGroup>
        </>
    );
}

const options = [
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
];

export function DropDowns() {
    const [dropdownValue, setDropdownValue] = useState<IComboBoxOption | null>(null);
    const [dropdown2Value, setDropdown2Value] = useState<IComboBoxOption | null>(options[0]);
    const [dropdown3Value, setDropdown3Value] = useState<IComboBoxOption | null>(options[1]);
    const [image, setImage] = useState<string | null>(null);
    return (
        <>
            <DashboardFormSubheading>Dropdowns</DashboardFormSubheading>
            <DashboardFormGroup label="Select input">
                <DashboardSelect options={options} value={dropdownValue!} onChange={setDropdownValue} />
            </DashboardFormGroup>
            <DashboardFormGroup label="Select input (with value)">
                <DashboardSelect options={options} value={dropdown2Value!} onChange={setDropdown2Value} />
            </DashboardFormGroup>
            <DashboardFormGroup label="Select input (opened)">
                <DashboardSelect forceOpen options={options} value={dropdown3Value!} onChange={setDropdown3Value} />
            </DashboardFormGroup>
        </>
    );
}

const longDescription =
    "Here's some info text for this field. I'm giving a little description of what this field does and how it affects the user.";
export function RadioGroups() {
    const [group1, setGroup1] = useState("option1");
    const [group2, setGroup2] = useState("option1");
    const [group3, setGroup3] = useState("option1");

    return (
        <StoryContent>
            <StoryHeading depth={1}>Radio Groups & Toggles</StoryHeading>
            <form>
                <DashboardFormList>
                    <DashboardFormSubheading>Radio Groups</DashboardFormSubheading>
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
                    <DashboardFormGroup label="Radio Group Grid">
                        <DashboardRadioGroup onChange={setGroup3} value={group3} isGrid>
                            <DashboardRadioButton value={"option1"} label="Option 1" />
                            <DashboardRadioButton
                                value={"option2"}
                                label="Option 2 with a little bit more text to test layout responsiveness"
                            />
                            <DashboardRadioButton value={"option3"} label="Option 3" />
                            <DashboardRadioButton value={"option4"} label="Option 4" disabled />
                        </DashboardRadioGroup>
                    </DashboardFormGroup>
                    <DashboardFormGroup
                        label="Checkbox Group Vertical (default)"
                        description={longDescription}
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
                </DashboardFormList>
            </form>
        </StoryContent>
    );
}

export function Toggles() {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Toggles</StoryHeading>
            <form>
                <DashboardFormList>
                    <DashboardFormSubheading>Toggles</DashboardFormSubheading>
                    <DashboardFormGroup
                        labelType={DashboardLabelType.WIDE}
                        label="Toggle On"
                        description={longDescription}
                    >
                        <DashboardToggle onChange={() => {}} checked={true} />
                    </DashboardFormGroup>
                    <DashboardFormGroup
                        labelType={DashboardLabelType.WIDE}
                        label="Toggle Off"
                        description={longDescription}
                    >
                        <DashboardToggle onChange={() => {}} checked={false} />
                    </DashboardFormGroup>
                    <DashboardFormGroup
                        labelType={DashboardLabelType.WIDE}
                        label="Toggle On (in progress)"
                        description={longDescription}
                    >
                        <DashboardToggle onChange={() => {}} checked={true} inProgress />
                    </DashboardFormGroup>
                    <DashboardFormGroup
                        labelType={DashboardLabelType.WIDE}
                        label="Toggle Off (in progress)"
                        description={longDescription}
                    >
                        <DashboardToggle onChange={() => {}} checked={false} inProgress />
                    </DashboardFormGroup>
                </DashboardFormList>
            </form>
        </StoryContent>
    );
}

export function BlurEnableForm() {
    const [isEnabled, setIsEnabled] = useState(false);
    return (
        <StoryContent>
            <StoryHeading depth={1}>Blur Enabled Form</StoryHeading>
            <form>
                <DashboardFormGroup
                    labelType={DashboardLabelType.WIDE}
                    label={t("Enable Custom Permissions")}
                    description={
                        <Translate
                            source="When enabled, this will use custom permissions instead of the global defaults. <0>Read More</0>"
                            c0={(text) => <a href="#">{text}</a>}
                        />
                    }
                >
                    <DashboardToggle checked={isEnabled} onChange={setIsEnabled} />
                </DashboardFormGroup>
                <DashboardFormList isBlurred={!isEnabled}>
                    <SimpleInputItems />
                </DashboardFormList>
            </form>
        </StoryContent>
    );
}
