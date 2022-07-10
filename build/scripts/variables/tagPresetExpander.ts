/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ITypeExpander } from "@vanilla/variable-parser";

export const tagPresetExpander: ITypeExpander = {
    type: "tagPreset",
    expandType: (variable) => {
        return [
            {
                ...variable,
                key: variable.key + ".fontColor",
                title: variable.title + " - " + "Font Color",
                description: `The tag's default text color`,
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                key: variable.key + ".bgColor",
                title: variable.title + " - " + "Background Color",
                description: `The tag's default background color`,
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                key: variable.key + ".borderColor",
                title: variable.title + " - " + "Border Color",
                description: `The tag's default border color`,
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                key: variable.key + ".fontColorHover",
                title: variable.title + " - " + "Font Color (Hover)",
                description: `The tag's text color when hovered, active, or focused`,
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                key: variable.key + ".bgColorHover",
                title: variable.title + " - " + "Background Color (Hover)",
                description: `The tag's background color when hovered, active, or focused`,
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                key: variable.key + ".borderColorHover",
                title: variable.title + " - " + "Border Color (Hover)",
                description: `The tag's border color when hovered, active, or focused`,
                type: "string",
                format: "color-hex",
            },
        ];
    },
};
