/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardCheckBox } from "@dashboard/forms/DashboardCheckBox";
import { DashboardColorPicker } from "@dashboard/forms/DashboardFormColorPicker";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { DashboardImageUploadGroup } from "@dashboard/forms/DashboardImageUploadGroup";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { DashboardCheckGroup, DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import { css } from "@emotion/css";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { AdvancedRangePicker } from "@library/forms/rangePicker/AdvancedRangePicker";
import { RangePicker } from "@library/forms/rangePicker/RangePicker";
import { IDateModifierRange } from "@library/forms/rangePicker/types";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import {
    AutoComplete,
    FormGroup,
    FormGroupInput,
    FormGroupLabel,
    INumberBoxProps,
    NumberBox as VanillaUINumberBox,
    TextBox,
} from "@vanilla/ui";
import React, { useState } from "react";

export default {
    title: "Forms/Admin Form Fields",
    decorators: [dashboardCssDecorator],
};

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

function StoryNumberBox(extraProps: Partial<INumberBoxProps>) {
    const [value, setValue] = useState(0);
    return <VanillaUINumberBox value={value} onValueChange={setValue} {...extraProps} />;
}

interface StoryAutoCompleteProps extends React.ComponentProps<typeof AutoComplete> {
    label?: string;
}

function StoryAutoComplete(props: StoryAutoCompleteProps) {
    const { label, ...autoCompleteProps } = props;
    const defaultValue = props.hasOwnProperty("value") ? props.value : 1;
    const [value, setValue] = useState<any | any[] | undefined>(props.multiple ? [defaultValue] : defaultValue);
    const setDataValue = (values) => {
        setValue(Array.isArray(values) ? [...values] : values);
    };
    return (
        <>
            <FormGroup>
                <FormGroupLabel>{label}</FormGroupLabel>
                <FormGroupInput>
                    {(props) => (
                        <AutoComplete
                            {...autoCompleteProps}
                            {...props}
                            value={value}
                            onChange={setDataValue}
                            options={[
                                { value: 0, label: "Pizza" },
                                { value: 1, label: "Apple", group: "Fruits" },
                                { value: 2, label: "Banana", group: "Fruits" },
                                { value: 3, label: "Cherry", group: "Fruits" },
                                { value: 4, label: "Broccoli", group: "Vegetables" },
                                { value: 5, label: "Carrot", group: "Vegetables" },
                            ]}
                        />
                    )}
                </FormGroupInput>
            </FormGroup>
            {/* <span>{`${JSON.stringify(value)}`}</span> */}
        </>
    );
}

export function Inputs() {
    const textBoxContainerStyles = css({
        width: 350,
        height: 150,
        marginRight: 40,
    });
    return (
        <>
            <StoryHeading depth={2}>Dashboard Inputs</StoryHeading>
            <DashboardFormGroup
                label="Label Here"
                description="Some optional description here - this element/component is normally wrapped in a dashboard form group (which receives label/description for it)."
            >
                <DashboardInput inputProps={{ placeholder: "Placeholder Here" }} />
            </DashboardFormGroup>
            <br />
            <DashboardFormGroup
                label="Multiline  Label"
                description="We can pass number of rows (and a lot more) to determine text area height."
            >
                <DashboardInput
                    inputProps={{ placeholder: "Placeholder Here", multiline: true }}
                    multiLineProps={{ rows: 5 }}
                />
            </DashboardFormGroup>
            <br />
            <StoryHeading depth={2}>Inputs (Vanilla UI Library)</StoryHeading>
            <div style={{ display: "flex", flexWrap: "wrap" }}>
                <div className={textBoxContainerStyles}>
                    <h3>Default Textbox</h3>
                    <TextBox placeholder="Placeholder here" />
                </div>
                <div className={textBoxContainerStyles}>
                    <h3>Active state</h3>
                    <TextBox placeholder="Placeholder here" autoFocus />
                </div>
                <div className={textBoxContainerStyles}>
                    <h3>Disabled</h3>
                    <TextBox placeholder="Placeholder here" disabled />
                </div>
                <div className={textBoxContainerStyles}>
                    <h3>Small variant</h3>
                    <TextBox placeholder="Placeholder here" size="small" />
                </div>
                <div className={textBoxContainerStyles}>
                    <h3>In form group with label</h3>
                    <FormGroup>
                        <FormGroupLabel>Form Group Label here</FormGroupLabel>
                        <FormGroupInput>
                            {(labelProps) => {
                                return <TextBox {...labelProps} placeholder="Placeholder here" />;
                            }}
                        </FormGroupInput>
                    </FormGroup>
                </div>
            </div>
        </>
    );
}

export function NumberBox() {
    const numberBoxContainerStyles = css({
        width: 250,
        height: 150,
        marginRight: 40,
    });
    return (
        <>
            <StoryHeading depth={2}>Numeric Input With Stepper (Vanilla UI Library)</StoryHeading>
            <div style={{ display: "flex", flexWrap: "wrap" }}>
                <div className={numberBoxContainerStyles}>
                    <h3>Default</h3>
                    <StoryNumberBox />
                </div>
                <div className={numberBoxContainerStyles}>
                    <h3>Active</h3>
                    <StoryNumberBox autoFocus />
                </div>
                <div className={numberBoxContainerStyles}>
                    <h3>Disabled</h3>
                    <StoryNumberBox disabled />
                </div>
                <div className={numberBoxContainerStyles}>
                    <h3>Small variant</h3>
                    <StoryNumberBox size="small" />
                </div>
            </div>
        </>
    );
}

export function Dropdowns() {
    const [value, setValue] = useState<IComboBoxOption | null>(null);

    const autocompleteStoryStyles = css({
        display: "flex",
        flexDirection: "column",
        maxWidth: "300px",

        button: { paddingLeft: "4px", paddingRight: "4px" },
        h4: { margin: "8px 0" },
    });

    return (
        <>
            <StoryHeading depth={1}>Dropdowns</StoryHeading>
            <br /> <br />
            <StoryHeading depth={2}>Dashboard Single Select Dropdown</StoryHeading>
            <DashboardFormGroup label="Label Here" description="Some description here.">
                <DashboardSelect options={options} value={value!} onChange={setValue} />
            </DashboardFormGroup>
            <DashboardFormGroup label="With selected value and clear button" description="Some description here.">
                <DashboardSelect
                    options={options}
                    value={{
                        label: "Success",
                        value: 5,
                    }}
                    onChange={setValue}
                />
            </DashboardFormGroup>
            <br /> <br />
            <StoryHeading depth={2}>
                Single and multiselect dropdowns (Vanilla-UI - Atocomplete - Combobox)
            </StoryHeading>
            <div className={autocompleteStoryStyles}>
                <h3>Single Select</h3>
                <StoryAutoComplete label="Label here" />
                <br />
                <h3>Multi Select</h3>
                <StoryAutoComplete label="Label here" multiple placeholder={"Placeholder here"} />
            </div>
            <h2>{`Note: See more Autocomplete variations under "Forms/Admin Form Fields/Autocomplete"`}</h2>
        </>
    );
}

export function Toggles() {
    return (
        <StoryContent>
            <StoryHeading>Toggles</StoryHeading>
            <DashboardFormGroup
                labelType={DashboardLabelType.WIDE}
                label="Toggle On"
                description={"Some description here"}
            >
                <DashboardToggle onChange={() => {}} checked={true} />
            </DashboardFormGroup>
            <br />
            <h2
                style={{ color: "#4FA095" }}
            >{`TODO: Move all toggle variations from "Dashboard/Forms" here and delete Toggles() from there`}</h2>
        </StoryContent>
    );
}

export function RadioGroups() {
    const [group1, setGroup1] = useState("option1");

    return (
        <StoryContent>
            <StoryHeading>Radio Groups </StoryHeading>
            <DashboardFormGroup label="Radio Group Vertical (default)">
                <DashboardRadioGroup onChange={setGroup1} value={group1}>
                    <DashboardRadioButton value={"option1"} label="Option 1" />
                    <DashboardRadioButton value={"option2"} label="Option 2" />
                    <DashboardRadioButton value={"option3"} label="Option 3" />
                </DashboardRadioGroup>
            </DashboardFormGroup>
            <br />
            <h2
                style={{ color: "#4FA095" }}
            >{`TODO: Move all radio group variations from "Dashboard/Forms" here and delete RadioGroups() from there`}</h2>
        </StoryContent>
    );
}

export function Checkboxes() {
    return (
        <StoryContent>
            <StoryHeading>Checkboxes </StoryHeading>
            <DashboardFormGroup
                label="Checkbox Group Vertical (default)"
                description={"Some description here"}
                labelType={DashboardLabelType.WIDE}
            >
                <DashboardCheckGroup>
                    <DashboardCheckBox label="Check 1" />
                    <DashboardCheckBox label="Option 2" />
                    <DashboardCheckBox label="Option 3" disabled />
                </DashboardCheckGroup>
            </DashboardFormGroup>
            <br />
            <h2
                style={{ color: "#4FA095" }}
            >{`TODO: Move all checkbox variations from "Dashboard/Forms" here and delete RadioGroups() from there`}</h2>
        </StoryContent>
    );
}

export function ColorPicker() {
    return (
        <StoryContent>
            <StoryHeading>Color Picker </StoryHeading>
            <DashboardFormGroup label="Label for color picker" description="This will allow user to pick a color">
                <DashboardColorPicker value={""} onChange={(color) => null} />
            </DashboardFormGroup>
        </StoryContent>
    );
}

export function ImageUpload() {
    const puppyImage = require("../../../../applications/dashboard/styleguide/public/resources/images/puppy.jpg");

    //these styles are taken from widget settings styles from layout editor,
    //plus some tweaks, probably should go to admin-new.scss at some point when we sure that these are the ones
    const uploadStyles = css({
        "& .form-group": {
            "& .file-upload": {
                "& > input": {
                    height: 36,
                },
                "& > input, & .file-upload-choose, & .file-upload-browse": {
                    borderColor: "#dddee0",
                    "&:focus, &:hover, &:active, &.focus-visible": {
                        borderColor: "#037dbc",
                    },
                },
                "& > input:hover ~ .file-upload-choose, & > input:hover ~ .file-upload-browse": {
                    borderColor: "#037dbc",
                },
                "& > input:hover ~ .file-upload-browse": {
                    borderColor: "#037dbc",
                    backgroundColor: "#037dbc",
                },

                "& .file-upload-choose": {
                    padding: "0 8px",
                    maxWidth: "calc(100% - 63px)",
                    overflow: "hidden",
                    textOverflow: "ellipsis",
                    whiteSpace: "nowrap",
                    borderTopRightRadius: 0,
                    borderBottomRightRadius: 0,
                },

                "& .file-upload-browse": {
                    minWidth: 64,
                    background: "#ffffff",
                    color: "#555a62",
                    "&:focus, &:hover, &:active, &.focus-visible": {
                        background: "#037dbc",
                        borderColor: "#037dbc",
                        color: "#ffffff",
                    },
                    "&:before": {
                        width: 0,
                    },
                },
            },
        },
    });

    return (
        <StoryContent>
            <StoryHeading>Image Upload</StoryHeading>
            <br />
            <div className={uploadStyles}>
                <h3>Default state</h3>
                <DashboardImageUploadGroup
                    label="Upload your image"
                    description="An image upload can have a description. Ideally it should describe things like the expected image dimensions etc"
                    onChange={() => {}}
                />
                <br /> <br />
                <h3>Already uploaded, with preview</h3>
                <DashboardImageUploadGroup
                    label="Upload your image"
                    description="An image upload can have a description. Ideally it should describe things like the expected image dimensions etc"
                    onChange={() => {}}
                    value={puppyImage}
                />
            </div>
            <br />
            <h2
                style={{ color: "#4FA095" }}
            >{`TODO: Check if we have version with different/no preview and include it if yes`}</h2>
        </StoryContent>
    );
}

export function DateRangePicker() {
    const [range, setRange] = useState<IDateModifierRange>({
        from: { operations: [{ type: "subtract", amount: 3, unit: "weeks" }] },
        to: {},
    });

    const rangePickerContainerStyles = css({
        "& section": {
            display: "flex",
            justifyContent: "flex-start",
        },
    });

    return (
        <StoryContent>
            <StoryHeading depth={1}>Date Range Picker</StoryHeading>
            <br /> <br />
            <StoryHeading>Calendar</StoryHeading>
            <br />
            <div className={rangePickerContainerStyles}>
                <RangePicker setRange={setRange} range={range} />
            </div>
            <br /> <br />
            <StoryHeading>Advanced Range Picker</StoryHeading>
            <br />
            <div className={rangePickerContainerStyles}>
                <AdvancedRangePicker setRange={setRange} range={range} />
            </div>
        </StoryContent>
    );
}

export function Buttons() {
    <StoryHeading depth={1}>Buttons</StoryHeading>;
    return (
        <>
            <StoryHeading depth={1}>Buttons</StoryHeading>
            <h3>Submit/Save button</h3>
            <Button legacyMode buttonType={ButtonTypes.OUTLINE} onClick={() => null}>
                Save
            </Button>
        </>
    );
}
export function Labels() {
    return (
        <>
            <StoryHeading depth={1}>Labels in form groups</StoryHeading>
            <br />
            <StoryHeading depth={2}>Dashboard Labels (only side-by-side support for now)</StoryHeading>
            <DashboardFormSubheading>This is a dashboard Form Subheading</DashboardFormSubheading>
            <DashboardFormGroup
                label="Normal label and form input"
                description="Here's some info text for this field. I'm giving a little description of what this field does and how it affects the user."
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
            <DashboardFormGroup
                label="Label with tooltip"
                description="Here's some info text for this field. I'm giving a little description of what this field does and how it affects the user."
                tooltip="This is a tooltip"
            >
                <DashboardInput inputProps={{ placeholder: "Placeholder Here" }} />
            </DashboardFormGroup>
            <br />
            <br />
            <StoryHeading depth={2}> Labels in form groups (Vanilla-UI)</StoryHeading>
            <br />
            <div style={{ display: "flex" }}>
                <div style={{ maxWidth: "400px" }}>
                    <h3>Default</h3>
                    <section>
                        <FormGroup>
                            <FormGroupLabel>First Name</FormGroupLabel>
                            <FormGroupInput>{(props) => <TextBox {...props} />}</FormGroupInput>
                        </FormGroup>

                        <FormGroup>
                            <FormGroupLabel>City</FormGroupLabel>
                            <FormGroupInput>
                                {(props) => (
                                    <AutoComplete
                                        {...props}
                                        options={[{ value: "Montreal" }, { value: "Detroit" }, { value: "Toronto" }]}
                                        clear
                                    />
                                )}
                            </FormGroupInput>
                        </FormGroup>
                    </section>
                </div>
                <div style={{ maxWidth: "400px", marginLeft: "100px" }}>
                    <h3>Side-by-side</h3>
                    <section>
                        <FormGroup sideBySide>
                            <FormGroupLabel>First Name</FormGroupLabel>
                            <FormGroupInput>{(props) => <TextBox {...props} />}</FormGroupInput>
                        </FormGroup>

                        <FormGroup sideBySide>
                            <FormGroupLabel>City</FormGroupLabel>
                            <FormGroupInput>
                                {(props) => (
                                    <AutoComplete
                                        {...props}
                                        options={[{ value: "Montreal" }, { value: "Detroit" }, { value: "Toronto" }]}
                                        clear
                                    />
                                )}
                            </FormGroupInput>
                        </FormGroup>
                    </section>
                </div>
                <div style={{ maxWidth: "400px", marginLeft: "100px" }}>
                    <h3>With Description</h3>
                    <section>
                        <FormGroup>
                            <FormGroupLabel description="Some description here">First Name</FormGroupLabel>
                            <FormGroupInput>{(props) => <TextBox {...props} />}</FormGroupInput>
                        </FormGroup>

                        <FormGroup>
                            <FormGroupLabel description="Some description here">City</FormGroupLabel>
                            <FormGroupInput>
                                {(props) => (
                                    <AutoComplete
                                        {...props}
                                        options={[{ value: "Montreal" }, { value: "Detroit" }, { value: "Toronto" }]}
                                        clear
                                    />
                                )}
                            </FormGroupInput>
                        </FormGroup>
                    </section>
                </div>
            </div>
        </>
    );
}

export function CodeBox() {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Code Box</StoryHeading>

            <h4>
                {`Looks like for now we canot render our dashboard code editor in storybook until already created bug is
                addressed (see the comment in <TextEditor/> component)`}
            </h4>
            {/* <DashboardFormGroup label="Label" description="Description">
                <DashboardCodeEditor
                    value={""}
                    onChange={() => {}}
                    language={"text/html"}
                    jsonSchemaUri={'html: "<div>Something here</div>"'}
                />
            </DashboardFormGroup> */}
        </StoryContent>
    );
}
