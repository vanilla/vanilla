/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ITypeExpander } from "@vanilla/variable-parser";

export const borderExpander: ITypeExpander = {
    type: "border",
    expandType: (variable) => {
        return [
            {
                ...variable,
                title: variable.title + " - " + "color",
                description: `Set the color of the border.`,
                key: variable.key + ".color",
                type: "string",
                format: "hex-color",
            },
            {
                ...variable,
                title: variable.title + " - " + "width",
                description: `Set the width of the border. Numerical units are interpretted as pixels.`,
                key: variable.key + ".width",
                type: ["string", "number"],
            },
            {
                ...variable,
                title: variable.title + " - " + "style",
                description: `Set the style of the border.`,
                key: variable.key + ".style",
                type: "string",
                enum: ["dashed", "dotted", "double", "groove", "hidden", "inset", "none", "outset", "ridge", "solid"],
            },
            {
                ...variable,
                title: variable.title + " - " + "radius",
                description: `Set the radius of the border. Numerical units are interpretted as pixels.`,
                key: variable.key + ".radius",
                type: ["string", "number"],
            },
        ];
    },
};
