/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React, { CSSProperties } from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { t } from "@library/utility/appUtils";
import InputBlock from "@library/forms/InputBlock";
import InputTextBlock from "@library/forms/InputTextBlock";
import MultiUserInput from "@library/features/users/MultiUserInput";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import DateRange from "@library/forms/DateRange";
import Checkbox from "@library/forms/Checkbox";
import StoryExampleDropDownSearch from "@library/flyouts/StoryExampleDropDownSearch";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import RadioButton from "@library/forms/RadioButton";
import "@library/forms/datePicker.scss";
import RadioButtonGroup from "@library/forms/RadioButtonGroup";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import { StorySmallContent } from "@library/storybook/StorySmallContent";
import { FormToggle } from "@library/forms/FormToggle";
import { flexHelper } from "@library/styles/styleHelpers";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { suggestedTextStyleHelper } from "@library/features/search/suggestedTextStyles";

const story = storiesOf("Forms/User Facing", module);

story.add("Elements", () => {
    let activeItem = "Tab A";

    /**
     * Simple form setter.
     */
    const handleUserChange = (options: IComboBoxOption[]) => {
        // Do something
        doNothing();
    };

    // To avoid clashing with other components also using these radio buttons, you need to generate a unique ID for the group.

    const radioButtonGroup1 = uniqueIDFromPrefix("radioButtonGroupA");

    cssOut(`.suggestedTextInput-option`, suggestedTextStyleHelper().option);

    return (
        <StoryContent>
            <StoryHeading depth={1}>Form Elements</StoryHeading>
            <StoryHeading>Checkbox</StoryHeading>
            <CheckboxGroup label={"Check Box States"}>
                <Checkbox label="Normal" />
                <Checkbox label="Hover/Focus" fakeFocus />
                <Checkbox label="Checked" defaultChecked />
                <Checkbox label="Disabled" disabled />
                <Checkbox label="Checked & Disabled" defaultChecked disabled />
            </CheckboxGroup>
            <StoryHeading>Hidden Label</StoryHeading>
            <Checkbox label="Tooltip Label" tooltipLabel />
            <StoryHeading>Checkboxes - In a Group</StoryHeading>
            <CheckboxGroup label={"A sleuth of check boxes"}>
                <Checkbox label="Option A" />
                <Checkbox label="Option B" />
                <Checkbox label="Option C" />
                <Checkbox label="Option D" />
            </CheckboxGroup>
            <StoryHeading>Toggles</StoryHeading>
            <div style={flexHelper().middle() as CSSProperties}>
                <StoryToggle accessibleLabel="Enabled" enabled={true} />
                <StoryToggle accessibleLabel="Disabled" enabled={false} />
                <StoryToggle accessibleLabel="Indeterminate" indeterminate enabled={true} />
                <StoryToggle accessibleLabel="Indeterminate" indeterminate enabled={false} />
            </div>
            <StoryHeading>Toggles (Slim)</StoryHeading>
            <div style={flexHelper().middle() as CSSProperties}>
                <StoryToggle accessibleLabel="Enabled" enabled={true} slim />
                <StoryToggle accessibleLabel="Disabled" enabled={false} slim />
                <StoryToggle accessibleLabel="Indeterminate" indeterminate enabled={true} slim />
                <StoryToggle accessibleLabel="Indeterminate" indeterminate enabled={false} slim />
            </div>
            <StoryHeading>Radio Buttons - In a Group</StoryHeading>
            <RadioButtonGroup label={"Gaggle of radio buttons"}>
                <RadioButton label={"Option A"} name={radioButtonGroup1} defaultChecked />
                <RadioButton label={"Option B"} name={radioButtonGroup1} />
                <RadioButton label={"Option C (hovered)"} name={radioButtonGroup1} fakeFocus />
                <RadioButton label={"Option D"} name={radioButtonGroup1} />
                <RadioButton label={"Option E"} name={radioButtonGroup1} />
            </RadioButtonGroup>
            <StoryHeading>InputBlock</StoryHeading>
            <StoryParagraph>Helper component to add label to various inputs</StoryParagraph>
            <StoryHeading>Example of label that can be used for any input</StoryHeading>
            <InputBlock label={"My Label"}>
                <div>{"[Some Input]"}</div>
            </InputBlock>
            <StoryHeading>Input Text Block</StoryHeading>
            <InputTextBlock label={t("Text Input")} inputProps={{ value: "Some Text!" }} />
            <StoryHeading>Input Text (password type)</StoryHeading>
            <StoryParagraph>You can set the `type` of the text input to any standard HTML5 values.</StoryParagraph>
            <InputTextBlock label={t("Password")} type={"password"} inputProps={{ type: "password" }} />
            <StoryHeading>Radio Buttons styled as tabs</StoryHeading>
            <StoryParagraph>
                The state for this component needs to be managed by the parent. (Will not update here when you click)
            </StoryParagraph>
            <StoryHeading>Tokens Input</StoryHeading>
            <MultiUserInput
                onChange={handleUserChange}
                value={[
                    {
                        value: "Astérix",
                        label: "Astérix",
                    },
                    {
                        value: "Obélix",
                        label: "Obélix",
                    },
                    {
                        value: "Idéfix",
                        label: "Idéfix",
                    },
                    {
                        value: "Panoramix",
                        label: "Panoramix",
                    },
                ]}
            />
            <StoryHeading>Dropdown with search</StoryHeading>
            <StoryExampleDropDownSearch />
            <StoryHeading>Date Range</StoryHeading>
            <StorySmallContent>
                <DateRange onStartChange={doNothing} onEndChange={doNothing} start={undefined} end={undefined} />
            </StorySmallContent>
        </StoryContent>
    );
});

const doNothing = () => {
    return;
};

function StoryToggle(props: Omit<React.ComponentProps<typeof FormToggle>, "onChange">) {
    return (
        <InputBlock label={props.accessibleLabel}>
            <FormToggle {...props} enabled={false} onChange={doNothing} />
        </InputBlock>
    );
}
