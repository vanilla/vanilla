/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ITypeExpander } from "@vanilla/variable-parser";

export const clickableExpander: ITypeExpander = {
    type: "clickable",
    expandType: (variable) => {
        return [
            {
                ...variable,
                title: variable.title + " - " + "Default Color",
                description:
                    "Set the color for the default state. Hexidecimal colors preferred (Either #RRGGBB or #RRGGBBAA)",
                key: variable.key + ".default",
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                title: variable.title + " - " + "Focus Color",
                description:
                    "Set the color for when the item is focused. Hexidecimal colors preferred (Either #RRGGBB or #RRGGBBAA)",
                key: variable.key + ".focus",
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                title: variable.title + " - " + "Focus Color (Click)",
                description:
                    "Set the color for when the item is focused (specifically after being clicked). Hexidecimal colors preferred (Either #RRGGBB or #RRGGBBAA)",
                key: variable.key + ".clickFocus",
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                title: variable.title + " - " + "Focus Color (Keyboard)",
                description:
                    "Set the color for when the item is focused (specifically using the keyboard). This can often be used to provide a very obvious focus color for users navigating with a keyboard, and a more subtle one for users with a pointing device. Hexidecimal colors preferred (Either #RRGGBB or #RRGGBBAA)",
                key: variable.key + ".keyboardFocus",
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                title: variable.title + " - " + "Active Color",
                description:
                    "Set the color for the item when it is active (like when it is being touched or clicked.). Hexidecimal colors preferred (Either #RRGGBB or #RRGGBBAA)",
                key: variable.key + ".active",
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                title: variable.title + " - " + "Visited Color",
                description:
                    "Set the color for the item if it is a link and has previously been visited. Hexidecimal colors preferred (Either #RRGGBB or #RRGGBBAA)",
                key: variable.key + ".visited",
                type: "string",
                format: "color-hex",
            },
            {
                ...variable,
                title: variable.title + " - " + "All State Colors",
                description:
                    "Set a color for the item during any non-default state (hover, active, focus, visited). Hexidecimal colors preferred (Either #RRGGBB or #RRGGBBAA)",
                key: variable.key + ".allStates",
                type: "string",
                format: "color-hex",
            },
        ];
    },
};
