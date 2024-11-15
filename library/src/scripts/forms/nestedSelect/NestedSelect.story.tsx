/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ApiV2Context } from "@library/apiv2";
import { INestedSelectProps, NestedSelect } from "@library/forms/nestedSelect";
import {
    MOCK_DEEP_NESTED_LIST,
    MOCK_NESTED_LIST,
    MOCK_SIMPLE_LIST,
    STORY_COUNTRY_LOOKUP,
    STORY_POKEMON_LOOKUP,
} from "@library/forms/nestedSelect/NestedSelect.fixtures";
import { StoryContent } from "@library/storybook/StoryContent";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { Select } from "@vanilla/json-schema-forms";
import { RecordID } from "@vanilla/utils";
import get from "lodash-es/get";
import set from "lodash-es/set";
import { useMemo, useState } from "react";

const queryClient = new QueryClient();

export default {
    title: "Forms/Nested Select Input",
    decorators: [
        (Story) => (
            <QueryClientProvider client={queryClient}>
                <StoryContent>
                    <Story />
                </StoryContent>
            </QueryClientProvider>
        ),
    ],
};

export const SimpleList = () => (
    <>
        <StoryHeading>Simple List</StoryHeading>
        <StoryParagraph>
            List of options provided are not grouped and should display without any headers and should not be indented.
        </StoryParagraph>
        <MockNestedSelect />
        <MockNestedSelect compact />
    </>
);

export const NestedList = () => (
    <>
        <StoryHeading>Nested List</StoryHeading>
        <StoryParagraph>
            List of options are grouped and nested only 2 levels. If a group parent is considered a header, then it will
            display differently and cannot be clicked. Root level headers are centered. All other headers are aligned
            left with appropriate indenting. Suggestions will include the group breadcrumbs and be aligned left.
        </StoryParagraph>
        <MockNestedSelect listType="nested" />
        <MockNestedSelect compact listType="nested" />
    </>
);

export const DeepNestedList = () => (
    <>
        <StoryHeading>Deep Nested List</StoryHeading>
        <StoryParagraph>
            List of options are grouped in a deeper nesting. If a group parent is considered a header, then it will
            display differently and cannot be clicked. Root level headers are centered. All other headers are aligned
            left with appropriate indenting. Suggestions will include the group breadcrumbs and be aligned left.
        </StoryParagraph>
        <MockNestedSelect listType="deepNested" />
        <MockNestedSelect compact listType="deepNested" />
    </>
);

export const Lookup = () => (
    <>
        <StoryHeading>Simple Lookup List</StoryHeading>
        <StoryParagraph>
            List of options generated from API lookup options that are not grouped and should display without any
            headers and should not be indented.
        </StoryParagraph>
        <MockNestedSelect listType="lookup" />
        <StoryHeading>Nested Lookup List</StoryHeading>
        <StoryParagraph>
            List of options generated from API lookup options that are grouped and nested. If a group parent is
            considered a header, then it will display differently and cannot be clicked. Root level headers are
            centered. All other headers are aligned left with appropriate indenting. Suggestions will include the group
            breadcrumbs and be aligned left.
        </StoryParagraph>
        <MockNestedSelect listType="nestedLookup" />
    </>
);

export const Clearable = () => (
    <>
        <StoryHeading>Clearable</StoryHeading>
        <StoryParagraph>
            Select input with default values and passed values. In this example, the clear all button is displayed and
            when the selected options are cleared, the selection will return to the default value.
        </StoryParagraph>
        <MockNestedSelect
            listType="nested"
            singleValueOptions={{
                value: "all",
                default: "",
            }}
            multipleValueOptions={{
                value: ["banana", "kiwi", "carrot"],
                default: ["all-vegetables"],
            }}
            isClearable
            noteAfterInput="Text that displays after the input as a note."
        />
    </>
);

export const Createable = () => (
    <>
        <StoryHeading>Creatable</StoryHeading>
        <StoryParagraph>Createable allows creating custom values.</StoryParagraph>
        <MockNestedSelect
            singleValueOptions={{ value: "watermelon" }}
            multipleValueOptions={{ value: ["apple", "kiwi", "strawberry"] }}
            createable
            labelNote="Text that displays after the label as a note."
        />
    </>
);

export const Disabled = () => (
    <>
        <StoryHeading>Disabled</StoryHeading>
        <StoryParagraph>Disabled select input with passed values</StoryParagraph>
        <MockNestedSelect
            singleValueOptions={{ value: "watermelon" }}
            multipleValueOptions={{ value: ["apple", "kiwi", "strawberry"] }}
            disabled
            labelNote="Text that displays after the label as a note."
        />
    </>
);

export const Errors = () => (
    <>
        <StoryHeading>Errors</StoryHeading>
        <StoryParagraph>Display the component with error state.</StoryParagraph>
        <MockNestedSelect
            listType="deepNested"
            labelNote="Some text that displays as a note for the label"
            noteAfterInput="Text that appears as a note after the input"
            singleValueOptions={{ value: "fruit" }}
            multipleValueOptions={{
                value: [
                    "iceCream-flavor-banana",
                    "iceCream-topping-chocolate",
                    "iceCream-topping-peanuts",
                    "iceCream-topping-cream",
                ],
            }}
            isClearable
            errors={[{ field: "select", message: "This is an error message" }]}
        />
    </>
);

export const DarkBackground = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            global: {
                mainColors: {
                    bg: "#303030",
                    fg: "#efefef",
                },
            },
        },
    },
    () => (
        <>
            <StoryHeading>Dark Background</StoryHeading>
            <StoryParagraph>
                The global background and foreground colors have been changed in the theme variables.
            </StoryParagraph>
            <SimpleList />
            <NestedList />
            <DeepNestedList />
            <Clearable />
            <Disabled />
        </>
    ),
);

export const CustomTheme = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            global: {
                mainColors: {
                    primary: "#0a9b8c",
                },
            },
            input: {
                colors: {
                    fg: "#456789",
                    bg: "#abcdef",
                },
            },
        },
    },
    () => (
        <>
            <StoryHeading>Custom Theme Variables</StoryHeading>
            <StoryParagraph>
                The background and foreground colors have been changed specifically on the input theme variables. The
                primary color in the global theme variables has also been changed.
            </StoryParagraph>
            <SimpleList />
            <NestedList />
            <DeepNestedList />
            <Clearable />
            <Disabled />
        </>
    ),
);

const mockClasses = {
    root: css({
        display: "flex",
        flexDirection: "row",
        flexWrap: "wrap",
        alignItems: "flex-start",
        justifyContent: "space-between",
        gap: 24,
        padding: "16px 0",
    }),
    inputWrapper: css({
        flex: 1,
        minWidth: 300,
    }),
    valueBox: css({
        background: "rgba(0,0,0,0.025)",
        color: "rgba(0,0,0,0.6)",
        border: "solid 1px",
        borderColor: "rgba(0,0,0,0.125)",
        borderRadius: 4,
        padding: 8,
        fontSize: 12,
        whiteSpace: "wrap",
    }),
};

function MockNestedSelect(
    _props: Omit<
        INestedSelectProps,
        "options" | "optionsLookup" | "onChange" | "value" | "multiple" | "defaultValue"
    > & {
        listType?: "simple" | "nested" | "deepNested" | "lookup" | "nestedLookup";
        singleValueOptions?: {
            value?: RecordID;
            default?: RecordID;
        };
        multipleValueOptions?: {
            value?: RecordID[];
            default?: RecordID[];
        };
    },
) {
    const { listType = "simple", singleValueOptions = {}, multipleValueOptions = {}, ...props } = _props;
    const [singleValue, setSingleValue] = useState<RecordID | undefined>(singleValueOptions.value);
    const [multipleValue, setMultipleValue] = useState<RecordID[] | undefined>(multipleValueOptions.value);

    const options = useMemo(() => {
        let options: Select.Option[] | undefined = undefined;

        switch (listType) {
            case "lookup":
                return { optionsLookup: STORY_POKEMON_LOOKUP };

            case "nestedLookup":
                return { optionsLookup: STORY_COUNTRY_LOOKUP };

            case "nested":
                options = MOCK_NESTED_LIST;
                break;

            case "deepNested":
                options = MOCK_DEEP_NESTED_LIST;
                break;

            default:
                options = MOCK_SIMPLE_LIST;
                break;
        }

        return { options };
    }, [listType]);

    return (
        <ApiV2Context>
            <div className={mockClasses.root}>
                <div className={mockClasses.inputWrapper}>
                    <NestedSelect
                        {...props}
                        {...options}
                        value={singleValue}
                        onChange={(value?: RecordID) => setSingleValue(value)}
                        label={props.compact ? "Compact Single Select" : "Single Select"}
                        placeholder="Select an Option"
                        defaultValue={singleValueOptions.default}
                    />
                    <pre className={mockClasses.valueBox}>
                        <code>
                            <strong>Value: </strong>
                            {JSON.stringify(singleValue)}
                        </code>
                    </pre>
                </div>
                <div className={mockClasses.inputWrapper}>
                    <NestedSelect
                        {...props}
                        {...options}
                        multiple
                        value={multipleValue}
                        onChange={(value?: RecordID[]) => setMultipleValue(value)}
                        label={props.compact ? "Compact Multiple Select" : "Multiple Select"}
                        placeholder="Select Multiple Options"
                        defaultValue={multipleValueOptions.default}
                    />
                    <pre className={mockClasses.valueBox}>
                        <code>
                            <strong>Value: </strong>
                            {JSON.stringify(multipleValue)}
                        </code>
                    </pre>
                </div>
            </div>
        </ApiV2Context>
    );
}
