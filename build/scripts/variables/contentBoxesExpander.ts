/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ITypeExpander } from "@vanilla/variable-parser";
import { boxExpander } from "./boxExpander";

export const contentBoxesExpander: ITypeExpander = {
    type: "contentBoxes",
    expandType: (variable) => {
        return [
            ...boxExpander.expandType({
                ...variable,
                title: "Depth 1",
                key: variable.key + ".depth1",
            }),
            ...boxExpander.expandType({
                ...variable,
                title: "Depth 2",
                key: variable.key + ".depth2",
            }),
            ...boxExpander.expandType({
                ...variable,
                title: "Depth 3",
                key: variable.key + ".depth3",
            }),
        ];
    },
};
