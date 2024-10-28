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
import DashboardCurrencyInput from "@dashboard/forms/DashboardCurrencyInput";
import DashboardRatioInput from "@dashboard/forms/DashboardRatioInput";
import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import React, { useState } from "react";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { t } from "@library/utility/appUtils";
import Translate from "@library/content/Translate";
import { DashboardFormListItem } from "@dashboard/forms/DashboardFormListItem";
import { Icon } from "@vanilla/icons";
import { DashboardMediaAddonListItem } from "@dashboard/forms/DashboardMediaAddonListItem";
import { AutoWidthInput } from "@library/forms/AutoWidthInput";
import { autoWidthInputClasses } from "@library/forms/AutoWidthInput.classes";
import { cx } from "@emotion/css";
import { DashboardColorPicker } from "@dashboard/forms/DashboardFormColorPicker";
import ToggleInputInLegacyForm from "@library/forms/ToggleInputInLegacyForm";

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
            <DashboardFormGroup label="label for color picker" description="This will allow user to pick a color">
                <DashboardColorPicker value={""} onChange={(color) => null} />
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
                    <DashboardFormGroup fieldset label="Radio Group Vertical (default)">
                        <DashboardRadioGroup onChange={setGroup1} value={group1}>
                            <DashboardRadioButton name={"radiogroup1_option1"} value={"option1"} label="Option 1" />
                            <DashboardRadioButton name={"radiogroup1_option2"} value={"option2"} label="Option 2" />
                            <DashboardRadioButton name={"radiogroup1_option3"} value={"option3"} label="Option 3" />
                        </DashboardRadioGroup>
                    </DashboardFormGroup>
                    <DashboardFormGroup fieldset label="Radio Group Inline">
                        <DashboardRadioGroup onChange={setGroup2} value={group2} isInline>
                            <DashboardRadioButton name={"radiogroup2_option1"} value={"option1"} label="Option 1" />
                            <DashboardRadioButton name={"radiogroup2_option2"} value={"option2"} label="Option 2" />
                            <DashboardRadioButton
                                name={"radiogroup2_option3"}
                                value={"option3"}
                                label="Option 3"
                                disabled
                            />
                        </DashboardRadioGroup>
                    </DashboardFormGroup>
                    <DashboardFormGroup fieldset label="Radio Group Grid">
                        <DashboardRadioGroup onChange={setGroup3} value={group3} isGrid>
                            <DashboardRadioButton name={"radiogroup3_option1"} value={"option1"} label="Option 1" />
                            <DashboardRadioButton
                                name={"radiogroup3_option2"}
                                value={"option2"}
                                label="Option 2 with a little bit more text to test layout responsiveness"
                            />
                            <DashboardRadioButton name={"radiogroup3_option3"} value={"option3"} label="Option 3" />
                            <DashboardRadioButton
                                name={"radiogroup3_option4"}
                                value={"option4"}
                                label="Option 4"
                                disabled
                            />
                        </DashboardRadioGroup>
                    </DashboardFormGroup>
                    <DashboardFormGroup
                        fieldset
                        label="Checkbox Group Vertical (default)"
                        description={longDescription}
                        labelType={DashboardLabelType.WIDE}
                    >
                        <DashboardCheckGroup>
                            <DashboardCheckBox name={"checkboxgroup1_option1"} label="Check 1" />
                            <DashboardCheckBox name={"checkboxgroup1_option2"} label="Option 2" />
                            <DashboardCheckBox name={"checkboxgroup1_option3"} label="Option 3" disabled />
                        </DashboardCheckGroup>
                    </DashboardFormGroup>
                    <DashboardFormGroup fieldset label="Checkbox Group Inline" description="Check out this description">
                        <DashboardCheckGroup isInline>
                            <DashboardCheckBox name={"checkboxgroup2_option1"} label="Option 1" />
                            <DashboardCheckBox name={"checkboxgroup2_option1"} label="Option 2" />
                            <DashboardCheckBox name={"checkboxgroup2_option1"} label="Option 3" disabled />
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

                    <ToggleInputInLegacyForm
                        {...{
                            fieldName: "someName",
                            initialValue: false,
                            label: "Toggling this will show a confirmation modal",
                            description: longDescription,
                            modal: {
                                content: "Some content to agree to or not",
                                title: "Are you sure?",
                            },
                            isDashboardSection: true,
                        }}
                    />
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

export function ListItemsWithStatus() {
    const [isEnabled, setIsEnabled] = useState(false);
    return (
        <StoryContent>
            <StoryHeading depth={1}>List Items with Status</StoryHeading>
            <DashboardFormList>
                <DashboardFormListItem
                    title={"Google Translate"}
                    status={"Configured"}
                    action={(e) => e}
                    actionLabel={"Edit Google translate"}
                    actionIcon={<Icon icon={"dashboard-edit"} />}
                />
                <DashboardFormListItem title={"Deep L"} status={"Not Configured"} />
                <DashboardFormListItem
                    title={"Transifex"}
                    action={(e) => e}
                    actionLabel={"Edit Transifex"}
                    actionIcon={<Icon icon={"dashboard-edit"} />}
                />
            </DashboardFormList>
        </StoryContent>
    );
}

export function MediaAddonListItems() {
    const [isEnabled, setIsEnabled] = useState({
        one: true,
        two: false,
        three: true,
    });
    return (
        <>
            <StoryHeading depth={1}>Media Addon List Items</StoryHeading>
            <DashboardFormList>
                <DashboardMediaAddonListItem
                    iconUrl={"https://staff.vanillaforums.com/locales/vf_en_GB/en_GB.svg"}
                    title={"English (United Kingdom)"}
                    description={
                        "Official English (United Kingdom) language translations for Vanilla. Help contribute to this translation by going to its translation site here. This locale is for British English. If you want to use the default American English you don't need to enable a specific locale pack."
                    }
                    isEnabled={isEnabled.one}
                    onChange={(bool) => setIsEnabled((prevState) => ({ ...prevState, one: bool }))}
                    action={() => null}
                    actionLabel={"Configure"}
                    actionIcon={<Icon icon={"dashboard-edit"} />}
                />
                <DashboardMediaAddonListItem
                    title={"A Locale Pack without and icon"}
                    description={"Custom translations made for a specific client"}
                    isEnabled={isEnabled.two}
                    onChange={(bool) => setIsEnabled((prevState) => ({ ...prevState, two: bool }))}
                />
                <DashboardMediaAddonListItem
                    iconUrl={"https://staff.vanillaforums.com/locales/vf_fr_CA/fr_CA.svg"}
                    title={"Français (Canada) / French (Canada)"}
                    description={
                        "Official French (Canada) language translations for Vanilla. Help contribute to this translation by going to its translation site here."
                    }
                    isEnabled={isEnabled.three}
                    onChange={(bool) => setIsEnabled((prevState) => ({ ...prevState, three: bool }))}
                    action={() => null}
                    actionLabel={"Configure"}
                    actionIcon={<Icon icon={"dashboard-edit"} />}
                />
            </DashboardFormList>
        </>
    );
}

export function InputWithAutoWidth() {
    return (
        <>
            <StoryHeading depth={1}>Auto with input component</StoryHeading>
            <StoryHeading depth={2}>Input width should increase while typing but not exceed 300px</StoryHeading>
            <form>
                <AutoWidthInput
                    placeholder={"Type your text here"}
                    className={cx(autoWidthInputClasses().themeInput)}
                />
            </form>
        </>
    );
}

export function CurrencyInput() {
    const [value, setValue] = useState<string | number>("3.75");

    return (
        <>
            <StoryHeading depth={1}>Currency Input</StoryHeading>
            <form>
                <DashboardFormGroup labelType={DashboardLabelType.VERTICAL} label={t("Currency")}>
                    <DashboardCurrencyInput
                        value={value}
                        onChange={(newValue) => {
                            setValue(newValue);
                        }}
                    />
                </DashboardFormGroup>
            </form>
        </>
    );
}

export function RatioInput() {
    const [value, setValue] = useState(34);

    return (
        <>
            <StoryHeading depth={1}>Ratio Input</StoryHeading>
            <form>
                <DashboardFormGroup labelType={DashboardLabelType.VERTICAL} label={t("Ratio")}>
                    <DashboardRatioInput
                        value={value}
                        onChange={(newValue) => {
                            setValue(newValue);
                        }}
                    />
                </DashboardFormGroup>
            </form>
        </>
    );
}
