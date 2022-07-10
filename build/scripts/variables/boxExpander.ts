/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ITypeExpander } from "@vanilla/variable-parser";
import { backgroundExpander } from "./backgroundExpander";
import { spacingExpander } from "./spacingExpander";

export const boxExpander: ITypeExpander = {
    type: "box",
    expandType: (variable) => {
        return [
            {
                ...variable,
                title: "Border Type",
                description: `Choose one of a few border types.

- none: Apply no styling on the box.
- border: Apply a standard border around the item. Automatically applies spacing as well. See the \`global.border\` variables to change this border.
- shadow: Apply a standard "widget" box shadow. Automatically applies spacing as well. See the \`shadow.widget\` variables to change this shadow.
- separator: Apply top and bottom separators to the item. Automatically applies vertical spacing.
`,
                key: variable.key + ".borderType",
                enum: ["border", "none", "shadow", "separator"],
                type: "string",
            },
            {
                ...variable,
                title: variable.title + " - " + "Item Spacing",
                description: `For certain border types, there is space between in items. This is that space in pixels.`,
                key: variable.key + ".itemSpacing",
                type: "number",
                default: "0",
            },
            {
                ...variable,
                title: variable.title + " - " + "Apply Item Spacing on all items ",
                description: `Apply the item spacing even on the first and last items.`,
                key: variable.key + ".itemSpacingOnAllItems",
                type: "boolean",
                default: "false",
            },
            ...spacingExpander.expandType({
                ...variable,
                title: "Spacing",
                key: variable.key + ".spacing",
                commonDescription: "_This value will be defaulted based on the set borderType.",
            }),
            ...backgroundExpander.expandType({
                ...variable,
                title: "Background",
                key: variable.key + ".background",
            }),
        ];
    },
};
