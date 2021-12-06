/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ITypeExpander } from "@vanilla/variable-parser";
import { fontExpander } from "./fontExpander";

export const stackedListExpander: ITypeExpander = {
    type: "stackedList",
    expandType: (variable) => {
        return [
            {
                ...variable,
                key: variable.key + ".sizing.width",
                title: variable.title + " - " + "Width",
                description: "Item width.",
                type: "number",
            },
            {
                ...variable,
                key: variable.key + ".sizing.offset",
                title: variable.title + " - " + "Offset",
                description: "Item offset.",
                type: "number",
            },
            ...fontExpander.expandType({
                ...variable,
                key: variable.key + ".plus.font",
            }),
            {
                ...variable,
                key: variable.key + ".plus.margin",
                title: variable.title + " - Plus - Margin",
                description: "Horizontal margin between the list and the `plus` link.",
                type: "number",
            },
        ];
    },
};
