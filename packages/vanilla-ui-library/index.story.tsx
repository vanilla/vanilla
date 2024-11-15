/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { MOCK_SIMPLE_LIST, STORY_COUNTRY_LOOKUP } from "@library/forms/nestedSelect/NestedSelect.fixtures";
import type { Meta } from "@storybook/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { useState } from "react";
import { SchemaForm, SchemaFormBuilder } from "./index";

const meta: Meta = {
    title: "@vanilla/ui-library",
    parameters: {},
};
export default meta;

export function FormBuilder() {
    const [value, setValue] = useState({});
    return (
        <div style={{ maxWidth: 800, margin: "0 auto" }}>
            <QueryClientProvider client={queryClient}>
                <SchemaForm schema={schema()} instance={value} onChange={setValue}></SchemaForm>
            </QueryClientProvider>
        </div>
    );
}

export function FormBuilderVertical() {
    const [value, setValue] = useState({});
    return (
        <div style={{ maxWidth: 600, margin: "0 auto" }}>
            <QueryClientProvider client={queryClient}>
                <SchemaForm
                    forceVerticalLabels={true}
                    schema={schema()}
                    instance={value}
                    onChange={setValue}
                ></SchemaForm>
            </QueryClientProvider>
        </div>
    );
}

function schema() {
    const schema = SchemaFormBuilder.create()
        .subHeading("Hello subheading")
        .toggle("toggle", "This is a toggle", "This is a toggle description.")
        .checkBoxRight(
            "check1",
            "This checkbox aligns to the right",
            "This is ideal for scenarios where you would want a toggle but your form has a submit",
        )
        .textBox("textWithNested", "Text Box", "This is a text box")
        .withoutBorder()
        .checkBox("check2", "This checkbox is nested.")
        .asNested()
        .checkBox("check3", "Check with description & tooltip")
        .withLabelType("none")
        .withDescription("Description here")
        .withTooltip("Hello tooltip")
        .radioGroup("radioGroup", "Radio Group", "Radio groups allow selecting one options", [
            {
                label: "Option 1",
                value: "1",
            },
            {
                label: "Option 2",
                description: "This one has a description",
                value: "2",
            },
            {
                label: "Option 3",
                value: "3",
                tooltip: "This one has a tooltip",
            },
        ])
        .withDefault("1")
        .radioPicker(
            "radioPicker",
            "Radio Picker",
            "Radio pickers are like a group but place everything in a picker.",
            [
                {
                    label: "Option 1",
                    value: "1",
                },
                {
                    label: "Option 2",
                    description: "This one has a description",
                    value: "2",
                },
                {
                    label: "Option 3",
                    value: "3",
                    tooltip: "This one has a tooltip",
                },
            ],
        )
        .withDefault("1")
        .subHeading("Text Inputs")
        .textBox("normalText", "Normal Text Box", "This is a normal text box")
        .textArea("textarea", "Multiline Text Box", "This is a multiline text box")
        .password("password", "Password", "This is a password box")
        .ratio("ratio", "Ratio", "This one is a number as a denominator of 1.")
        .currency("currency", "Currency", "This one is form inputting currency.")
        .subHeading("Dates")
        .datePicker("singleDate", "Single Date", "This is a single date picker.")
        .dateRange(
            "dateRange",
            "Date Range",
            "This is a date range picker. Notably the output is an object with `start` and `end` properties.",
        )
        .timeDuration("timeDuration", "Time Duration", "This is a time duration picker.")
        .subHeading("Selects")
        .selectStatic("selectSelect", "Select", "This is a select that holds a single value.", MOCK_SIMPLE_LIST)
        .selectStatic(
            "selectMulti",
            "Select (Multiple)",
            "This is a select that holds multiple values.",
            MOCK_SIMPLE_LIST,
            true,
        )
        .selectLookup(
            "selectSingleLookup",
            "Select Lookup",
            "This is a select that looks up values.",
            STORY_COUNTRY_LOOKUP,
        )

        .selectLookup(
            "selectMultiLookup",
            "Select Lookup (Multiple)",
            "This is a select that looks up values.",
            STORY_COUNTRY_LOOKUP,
            true,
        )
        .getSchema();
    return schema;
}

const queryClient = new QueryClient();
