/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ITypeExpander } from "@vanilla/variable-parser";
import { fontExpander } from "./fontExpander";
import { spacingExpander } from "./spacingExpander";
import { stackedListExpander } from "./stackedListExpander";

export const contributionItemsExpander: ITypeExpander = {
    type: "contributionItems",
    expandType: (variable) => {
        return [
            {
                ...variable,
                key: variable.key + ".sizing.width",
                title: variable.title + " - " + "Width",
                description: `Item width.`,
                type: "number",
            },
            ...spacingExpander.expandType({
                ...variable,
                key: variable.key + ".spacing",
                title: variable.title + " - " + "spacing",
                commonDescription: `Spacing between items.`,
            }),
            {
                ...variable,
                key: variable.key + ".count.display",
                title: variable.title + " - " + "Count - Display",
                description: `Display the item count.`,
                type: "boolean",
            },
            {
                ...variable,
                key: variable.key + ".count.fontSize",
                title: variable.title + " - " + "Count - Font size",
                description: `The count's font size.`,
                type: "number",
            },
            {
                ...variable,
                key: variable.key + ".count.backgroundColor",
                title: variable.title + " - " + "Count - Background color",
                description: `The count's background color.`,
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                key: variable.key + ".count.borderColor",
                title: variable.title + " - " + "Count - Border color",
                description: `the count's border color.`,
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                key: variable.key + ".name.display",
                title: variable.title + " - " + "Name - Display",
                description: `Display the item name.`,
                type: "boolean",
            },
            ...fontExpander.expandType({
                ...variable,
                key: variable.key + ".name.font",
            }),
            ...spacingExpander.expandType({
                ...variable,
                key: variable.key + ".name.spacing",
            }),
            {
                ...variable,
                key: variable.key + ".limit.maxItems",
                title: variable.title + " - " + "Limit - Maximum items",
                description: `Maximum mumber of items to display in the list.`,
                type: "number",
            },
            ...stackedListExpander.expandType({
                ...variable,
                key: variable.key + ".stackedList",
            }),
        ];
    },
};
