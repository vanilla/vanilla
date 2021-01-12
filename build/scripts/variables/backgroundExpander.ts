/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ITypeExpander } from "@vanilla/variable-parser";

export const backgroundExpander: ITypeExpander = {
    type: "background",
    expandType: (variable) => {
        return [
            {
                ...variable,
                title: variable.title + " - " + "color",
                description: `Set the background color of the component.`,
                key: variable.key + ".color",
                type: "string",
                format: "hex-color",
            },
            {
                ...variable,
                title: variable.title + " - " + "attachment",
                description: `Set the background attachment of the component. [MDN Reference](https://developer.mozilla.org/en-US/docs/Web/CSS/background-attachment)`,
                key: variable.key + ".attachment",
                type: "string",
                enum: ["fixed", "local", "scroll"],
            },
            {
                ...variable,
                title: variable.title + " - " + "attachment",
                description: `Set the background attachment of the component. [MDN Reference](https://developer.mozilla.org/en-US/docs/Web/CSS/background-attachment)`,
                key: variable.key + ".attachment",
                type: "string",
            },
            {
                ...variable,
                title: variable.title + " - " + "position",
                description: `Set the background position of the component. [MDN Reference](https://developer.mozilla.org/en-US/docs/Web/CSS/background-position)`,
                key: variable.key + ".position",
                type: "string",
            },
            {
                ...variable,
                title: variable.title + " - " + "repeat",
                description: `Set the background repeat of the component. [MDN Reference](https://developer.mozilla.org/en-US/docs/Web/CSS/background-repeat)`,
                key: variable.key + ".repeat",
                type: "string",
            },
            {
                ...variable,
                title: variable.title + " - " + "size",
                description: `Set the background size of the component. [MDN Reference](https://developer.mozilla.org/en-US/docs/Web/CSS/background-size)`,
                key: variable.key + ".size",
                type: "string",
            },
            {
                ...variable,
                title: variable.title + " - " + "repeat",
                description: `Define how the background repeats. [MDN Reference](https://developer.mozilla.org/en-US/docs/Web/CSS/background-repeat)`,
                key: variable.key + ".repeat",
                type: "string",
            },
            {
                ...variable,
                title: variable.title + " - " + "image",
                description: `Set a background image or linear-gradient for the component.Note: Some special parsing may occur on this value.\n- URL's beginning with a "~" character will be relative to the current theme directory. For example if the current file-based them is in /addons/themes/my-theme, ~/design/my-image.png would be equivalent to /addons/themes/my-theme/design/my-image.png\n- Please pass _URLs_ for this variable. __Do not__ pass urls wrapped in "url()".`,
                key: variable.key + ".image",
                type: "string",
            },
            {
                ...variable,
                title: variable.title + " - " + "Ignore Default Background",
                description: `Some components have a built-in background. In these scenarios setting this variable to true will remove the default background, even if other background properties are not specified.`,
                key: variable.key + ".unsetBackground",
                type: "boolean",
            },
        ];
    },
};
