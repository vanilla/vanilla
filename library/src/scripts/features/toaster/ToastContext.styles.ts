/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";

export const toastManagerClasses = () => {
    const globalVars = globalVariables();
    const area = css({
        display: "flex",
        flexDirection: "column-reverse",
        position: "fixed",
        top: 0,
        left: 0,
        height: "100vh",
        width: "auto",
        pointerEvents: "none",
        ...Mixins.padding({
            all: globalVars.gutter.size,
        }),

        "& > *": {
            pointerEvents: "auto",
        },
    });
    return { area };
};
