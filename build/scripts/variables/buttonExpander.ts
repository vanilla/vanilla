/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ITypeExpander } from "@vanilla/variable-parser";

import { borderExpander } from "./borderExpander";
import { fontExpander } from "./fontExpander";
import { spacingExpander } from "./spacingExpander";

const buttonOptionsExpander: ITypeExpander = {
    type: "buttonOptions",
    expandType: (variable) => {
        return [
            {
                ...variable,
                title: variable.title + " - " + "Foreground color",
                description:
                    "Sets the button's foreground color. Hexidecimal colors preferred (Either #RRGGBB or #RRGGBBAA).",
                key: variable.key + ".colors.fg",
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                title: variable.title + " - " + "Background color",
                description:
                    "Sets the button's background color. Hexidecimal colors preferred (Either #RRGGBB or #RRGGBBAA).",
                key: variable.key + ".colors.bg",
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                title: variable.title + " - " + "Opacity",
                description: "Sets the button's opacity",
                key: variable.key + ".opacity",
                type: "number",
            },
            ...borderExpander.expandType({
                ...variable,
                title: variable.title + " - " + "Borders",
                key: variable.key + ".borders",
            }),
            ...fontExpander.expandType({
                ...variable,
                title: variable.title + " - " + "Fonts",
                commonDescription: "Specify the fonts used in the button.",
                key: variable.key + ".fonts",
            }),
            {
                ...variable,
                title: variable.title + " - " + "Name",
                description: "A unique name to identify the button.",
                key: variable.key + ".name",
                type: "string",
            },
            {
                ...variable,
                title: variable.title + " - " + "Preset name",
                description: "Preset name.",
                key: variable.key + ".presetName",
                type: "string",
                enum: ["solid", "outline", "transparent", "advanced", "hide"],
            },
            {
                ...variable,
                title: variable.title + " - " + "Minimum height",
                description: "Specifies the minimum height of the button, in pixels.",
                key: variable.key + ".sizing.minHeight",
                type: "number",
            },
            {
                ...variable,
                title: variable.title + " - " + "Minimum width",
                description: "Specifies the minimum width of the button, in pixels.",
                key: variable.key + ".sizing.minWidth",
                type: "number",
            },
            ...spacingExpander.expandType({
                ...variable,
                title: variable.title + " - " + "Padding",
                commonDescription: "These values set the button's inner padding, in pixels.",
                key: variable.key + ".padding",
            }),
        ];
    },
};

export const buttonExpander: ITypeExpander = {
    type: "button",
    expandType: (variable) => [
        ...buttonOptionsExpander.expandType({
            ...variable,
            commonDescription: "These options control the button's default appearance.",
        }),
        ...buttonOptionsExpander.expandType({
            ...variable,
            title: variable.title + " - " + "State",
            commonDescription:
                "These options control the button's appearance in any hover, focus, active, focusAccessible states.",
            key: variable.key + ".state",
        }),
        ...buttonOptionsExpander.expandType({
            ...variable,
            title: variable.title + " - " + "Hover",
            commonDescription: "These options control the button's appearance in the hovered state.",
            key: variable.key + ".hover",
        }),
        ...buttonOptionsExpander.expandType({
            ...variable,
            title: variable.title + " - " + "Focus",
            commonDescription: "These options control the button's appearance in the focused state.",
            key: variable.key + ".focus",
        }),
        ...buttonOptionsExpander.expandType({
            ...variable,
            title: variable.title + " - " + "Active",
            commonDescription: "These options control the button's appearance in the active state.",
            key: variable.key + ".active",
        }),
        ...buttonOptionsExpander.expandType({
            ...variable,
            title: variable.title + " - " + "Focus Accessible",
            commonDescription: "These options control the button's appearance in the focus accessible state.",
            key: variable.key + ".focusAccessible",
        }),
        ...buttonOptionsExpander.expandType({
            ...variable,
            title: variable.title + " - " + "Disabled",
            commonDescription: "These options control the button's appearance in the disabled state.",
            key: variable.key + ".disabled",
        }),
    ],
};
