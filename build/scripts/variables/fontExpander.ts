/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ITypeExpander } from "@vanilla/variable-parser";

export const fontExpander: ITypeExpander = {
    type: "font",
    expandType: (variable) => {
        return [
            {
                ...variable,
                title: variable.title + " - " + "Color",
                description:
                    "Sets the color the components's text. Hexidecimal colors preferred (Either #RRGGBB or #RRGGBBAA)",
                key: variable.key + ".color",
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                title: variable.title + " - " + "Size",
                description:
                    "Indicates the desired height of glyphs in the applied font. Numerical units will be applied as pixels, for example '12' will be interpreted as '12px'.",
                key: variable.key + ".size",
                type: ["number", "string"],
            },
            {
                ...variable,
                title: variable.title + " - " + "Weight",
                description: "Specifies weight/boldness of glyphs in the applied font.",
                key: variable.key + ".weight",
                type: "number",
            },
            {
                ...variable,
                title: variable.title + " - " + "Line Height",
                description: "Sets the distance between lines of text.",
                key: variable.key + ".lineHeight",
                type: ["number", "string"],
            },
            {
                ...variable,
                title: variable.title + " - " + "Text Shadow",
                description:
                    "Enables shadow effects to be applied to the text of the element. Allows any formats of [the CSS 'text-shadow' property.](https://developer.mozilla.org/en-US/docs/Web/CSS/text-shadow)",
                key: variable.key + ".shadow",
                type: "string",
            },
            {
                ...variable,
                title: variable.title + " - " + "Alignment",
                description:
                    "Describes how text contents are horizontally aligned if the contents do not completely fill their container.",
                key: variable.key + ".align",
                type: ["number", "string"],
            },
            {
                ...variable,
                title: variable.title + " - " + "Family",
                description: "Text alignment",
                key: variable.key + ".family",
                type: ["left", "right", "center"],
            },
            {
                ...variable,
                title: variable.title + " - " + "Transform",
                description:
                    "Specifies how to capitalize an element's text. It can be used to make text appear in all-uppercase or all-lowercase, or with each word capitalized. ",
                key: variable.key + ".transform",
                type: ["string"],
                enum: ["capitalize", "full-size-kana", "full-width", "lowercase", "none", "uppercase"],
            },
            {
                ...variable,
                title: variable.title + " - " + "Letter Spacing",
                description: "Sets the spacing behavior between text characters.",
                key: variable.key + ".letterSpacing",
                type: ["string", "number"],
            },
        ];
    },
};
