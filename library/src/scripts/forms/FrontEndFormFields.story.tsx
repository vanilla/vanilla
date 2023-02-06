/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { Standard } from "@library/embeddedContent/components/Buttons.story";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import Checkbox from "@library/forms/Checkbox";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import DateRange from "@library/forms/DateRange";
import { FormToggle } from "@library/forms/FormToggle";
import InputBlock from "@library/forms/InputBlock";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import InputTextBlock, { IInputTextProps } from "@library/forms/InputTextBlock";
import PasswordInput from "@library/forms/PasswordInput";
import RadioButton from "@library/forms/RadioButton";
import RadioButtonGroup from "@library/forms/RadioButtonGroup";
import SelectOne from "@library/forms/select/SelectOne";
import { Tokens } from "@library/forms/select/Tokens";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StorySmallContent } from "@library/storybook/StorySmallContent";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import React, { useState } from "react";

export default {
    title: "Forms/Front End Form Fields",
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

function StoryTextBlockInput(extraProps: Partial<IInputTextProps> & { multiline?: boolean }) {
    const [value, setValue] = useState("");
    return (
        <InputTextBlock
            label={"Label here"}
            inputProps={{
                onChange: (event) => {
                    setValue(event.target.value);
                },
                value: undefined,
                placeholder: "Plaholder here",
                multiline: extraProps.multiline,
            }}
            {...extraProps}
        />
    );
}

function TokenInputsStory() {
    const [values, setValues] = useState<IComboBoxOption[]>([
        {
            label: "Development",
            value: 4,
        },
        {
            label: "Information Security",
            value: 7,
        },
    ]);
    return (
        <Tokens
            label={"Label here"}
            placeholder={"Select..."}
            options={options}
            onChange={(values) => setValues(values)}
            value={values}
            className={inputBlockClasses().root}
        />
    );
}

function TogglesStory(props: { label: string; accessibleLabel: string; initialValue: boolean; slim?: boolean }) {
    const [value, setValue] = useState<boolean>(props.initialValue);
    const toggleContainerStyles = css({
        width: 170,
        height: 150,
    });
    return (
        <div className={toggleContainerStyles}>
            <InputBlock label={props.label}>
                <FormToggle
                    accessibleLabel={props.accessibleLabel}
                    enabled={value}
                    onChange={(newValue) => setValue(newValue)}
                    slim={props.slim ?? false}
                    indeterminate={props.accessibleLabel === "Indeterminate" ? true : false}
                />
            </InputBlock>
        </div>
    );
}

export function Inputs() {
    return (
        <StoryContent>
            <StoryHeading>Text Inputs</StoryHeading>
            <h3>Input text Block</h3>
            <br />
            <StoryTextBlockInput />
            <br /> <br />
            <h3>Password type</h3>
            <br />
            <InputTextBlock label={"Password"} type={"password"} inputProps={{ type: "password" }} />
            <br /> <br />
            <h3>Multiline - textarea</h3>
            <br />
            <StoryTextBlockInput
                multiline
                multiLineProps={{
                    rows: 5,
                    maxRows: 5,
                }}
            />
        </StoryContent>
    );
}

export function Dropdowns() {
    return (
        <StoryContent>
            <StoryHeading>Select dropdown, reused in some dashboard sections as well</StoryHeading>
            <br />
            <div className={"input-wrap"}>
                <SelectOne
                    className={inputBlockClasses().root}
                    onChange={() => {
                        return;
                    }}
                    value={undefined}
                    label={"Label here"}
                    options={options}
                />
            </div>
        </StoryContent>
    );
}

export function TokenInputs() {
    return (
        <StoryContent>
            <StoryHeading>Token Inputs</StoryHeading>
            <TokenInputsStory />
        </StoryContent>
    );
}

export function Toggles() {
    return (
        <StoryContent>
            <StoryHeading>Form Toggle in Input Block</StoryHeading>
            <div style={{ display: "flex", flexWrap: "wrap" }}>
                <TogglesStory label="Label - click me!" accessibleLabel="Enabled" initialValue />
                <TogglesStory label="Label - disabled" accessibleLabel="Disabled" initialValue={false} />
                <TogglesStory label="Label - indeterminate" accessibleLabel="Indeterminate" initialValue />
                <TogglesStory label="Label - indeterminate" accessibleLabel="Indeterminate" initialValue={false} />
                <TogglesStory label="Label - smaller size" accessibleLabel="Enabled" initialValue slim />
            </div>
        </StoryContent>
    );
}

export function RadioGroups() {
    // To avoid clashing with other components also using these radio buttons, you need to generate a unique ID for the group.
    const radioButtonGroup1 = uniqueIDFromPrefix("radioButtonGroupA");
    return (
        <StoryContent>
            <StoryHeading>Radio Buttons - In a Group</StoryHeading>
            <RadioButtonGroup label={"Gaggle of radio buttons"}>
                <RadioButton label={"Option A"} name={radioButtonGroup1} defaultChecked />
                <RadioButton label={"Option B"} name={radioButtonGroup1} />
                <RadioButton label={"Option C (hovered)"} name={radioButtonGroup1} fakeFocus />
                <RadioButton label={"Option D"} name={radioButtonGroup1} />
                <RadioButton label={"Option E"} name={radioButtonGroup1} />
            </RadioButtonGroup>
        </StoryContent>
    );
}

export function Checkboxes() {
    return (
        <StoryContent>
            <StoryHeading>Checkboxes - In a Group</StoryHeading>
            <h3>Different states</h3>
            <br />
            <CheckboxGroup label={"Label here"}>
                <Checkbox label="Normal" />
                <Checkbox label="Hover/Focus" fakeFocus />
                <Checkbox label="Checked" defaultChecked />
                <Checkbox label="Disabled" disabled />
                <Checkbox label="Disabled with note" disabled disabledNote="This is disabled for a good reason." />
                <Checkbox label="Checked & Disabled" defaultChecked disabled />
            </CheckboxGroup>
            <br /> <br />
            <h3>Hidden Label</h3>
            <br />
            <CheckboxGroup label={"Some info"}>
                <Checkbox label="Tooltip Label" tooltipLabel />
            </CheckboxGroup>
        </StoryContent>
    );
}

export function Buttons() {
    return (
        <>
            <Standard />
            <h2>{`Note: See more button variations under "Elements/Buttons"`}</h2>
        </>
    );
}

export function DatePicker() {
    return (
        <StoryContent>
            <StoryHeading>Date Picker</StoryHeading>
            <StorySmallContent>
                <DateRange
                    label={"Pick your dates - label"}
                    onStartChange={() => {}}
                    onEndChange={() => {}}
                    start={undefined}
                    end={undefined}
                />
            </StorySmallContent>
        </StoryContent>
    );
}

export function Labels() {
    return (
        <StoryContent>
            <StoryHeading>We normally use label/input vertical combination in front end</StoryHeading>
            <InputTextBlock
                label={"Label here"}
                inputProps={{
                    value: undefined,
                    placeholder: "Plaholder here",
                }}
            />

            <StoryHeading>InputBlock</StoryHeading>
            <StoryParagraph>Helper component to add label to various inputs</StoryParagraph>
            <StoryHeading>Example of label that can be used for any input</StoryHeading>
            <InputBlock label={"My Label"}>
                <div>{"[Some Input]"}</div>
            </InputBlock>
        </StoryContent>
    );
}

export function Password() {
    return (
        <StoryContent>
            <StoryHeading>Password Input</StoryHeading>
            <PasswordInput />
            <StoryHeading>Password Input with error</StoryHeading>
            <PasswordInput hasError={true} />
            <StoryHeading>Password Input with show/hide button</StoryHeading>
            <PasswordInput showUnmask={true} value="myAwesomePassword" />
            <StoryHeading>Password Input with show/hide button and error</StoryHeading>
            <PasswordInput showUnmask={true} value="myAwesomePassword" hasError={true} />
        </StoryContent>
    );
}
