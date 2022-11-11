/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Container from "@library/layout/components/Container";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryHeading } from "@library/storybook/StoryHeading";
import FormTree from "@library/tree/FormTree";
import { itemsToTree } from "@library/tree/utils";
import { IFormControl, JsonSchema } from "@vanilla/json-schema-forms";
import React, { useState } from "react";

export default {
    title: "Forms/Front End Form Fields",
};

export const Tree = storyWithConfig(
    {
        useWrappers: false,
    },
    () => {
        type StoryValue = {
            type: string;
            label: string;
            isHidden?: boolean;
        };
        const itemSchema: JsonSchema = {
            type: "object",
            properties: {
                type: {
                    type: "string",
                    "x-control": {
                        inputType: "dropDown",
                        label: "Item Type",
                        choices: {
                            staticOptions: {
                                label1: "Label 1",
                                label2: "Label 2",
                                label3: "Label 3",
                            },
                        },
                    } as IFormControl,
                },
                label: {
                    type: "string",
                    "x-control": {
                        inputType: "textBox",
                        label: "Item Label",
                    },
                },
            },
        };
        const [value, onChange] = useState(
            itemsToTree([
                {
                    type: "label1",
                    label: "This is a custom label",
                },
                {
                    type: "label2",
                    label: "Another custom label",
                },
                {
                    type: "label3",
                    label: "Hello label",
                },
            ] as StoryValue[]),
        );

        const hideableProps = {
            markItemHidden: (item) => {
                return {
                    ...item,
                    isHidden: true,
                };
            },
            isItemHidden: (item) => {
                return item.isHidden ?? false;
            },
            isItemHideable: () => true,
        };

        return (
            <div>
                <Container maxWidth={1000}>
                    <StoryHeading>Delete and Add</StoryHeading>
                    <FormTree<StoryValue> itemSchema={itemSchema} onChange={onChange} value={value} />
                    <StoryHeading>Hide and Show</StoryHeading>
                    <FormTree<StoryValue>
                        {...hideableProps}
                        itemSchema={itemSchema}
                        onChange={onChange}
                        value={value}
                    />
                </Container>
                <Container maxWidth={400}>
                    <StoryHeading>Delete and Add (Compact)</StoryHeading>
                    <FormTree<StoryValue> itemSchema={itemSchema} onChange={onChange} value={value} />
                    <StoryHeading>Hide and Show (Compact)</StoryHeading>
                    <FormTree<StoryValue>
                        {...hideableProps}
                        itemSchema={itemSchema}
                        onChange={onChange}
                        value={value}
                    />
                </Container>
            </div>
        );
    },
);
