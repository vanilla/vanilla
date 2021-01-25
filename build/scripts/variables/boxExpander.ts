/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ITypeExpander } from "@vanilla/variable-parser";
import { backgroundExpander } from "./backgroundExpander";

export const boxExpander: ITypeExpander = {
    type: "box",
    expandType: (variable) => {
        return [
            {
                ...variable,
                title: variable.title + " - " + "Border Type",
                description: `Choose one of a few border types.`,
                key: variable.key + ".borderType",
                enum: ["border", "none", "shadow"],
                type: "string",
            },
            ...backgroundExpander.expandType({
                ...variable,
                title: variable.title + " - " + "Background",
                key: variable.key + ".background",
            }),
        ];
    },
};
