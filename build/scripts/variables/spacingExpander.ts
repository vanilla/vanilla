/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ITypeExpander } from "@vanilla/variable-parser";

export const spacingExpander: ITypeExpander = {
    type: "spacing",
    expandType: (variable) => {
        return [
            {
                ...variable,
                title: variable.title + " - " + "All",
                description: `Set the spacing for all sides of the component. Equivalent to setting the top, bottom, left, and right. Numeric units will be interpretted as pixels.`,
                key: variable.key + ".all",
                type: ["string", "number"],
            },
            {
                ...variable,
                title: variable.title + " - " + "Vertical",
                description: `Set the vertical spacing of the component. Equivalent to setting the top & bottom. Numeric units will be interpretted as pixels.`,
                key: variable.key + ".vertical",
                type: ["string", "number"],
            },
            {
                ...variable,
                title: variable.title + " - " + "Horizontal",
                description: `Set the horizontal spacing of the component. Equivalent to setting the left & right. Numeric units will be interpretted as pixels.`,
                key: variable.key + ".horizontal",
                type: ["string", "number"],
            },
            {
                ...variable,
                title: variable.title + " - " + "Left",
                description: `Set the left spacing of the component. Numeric units will be interpretted as pixels.`,
                key: variable.key + ".left",
                type: ["string", "number"],
            },
            {
                ...variable,
                title: variable.title + " - " + "Right",
                description: `Set the right spacing of the component. Numeric units will be interpretted as pixels.`,
                key: variable.key + ".right",
                type: ["string", "number"],
            },
            {
                ...variable,
                title: variable.title + " - " + "Top",
                description: `Set the top spacing of the component. Numeric units will be interpretted as pixels.`,
                key: variable.key + ".top",
                type: ["string", "number"],
            },
            {
                ...variable,
                title: variable.title + " - " + "Bottom",
                description: `Set the bottom spacing of the component. Numeric units will be interpretted as pixels.`,
                key: variable.key + ".bottom",
                type: ["string", "number"],
            },
        ];
    },
};
