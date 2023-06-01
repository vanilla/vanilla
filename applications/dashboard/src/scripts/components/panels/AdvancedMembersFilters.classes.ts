/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";

export default function advancedMembersFiltersClasses() {
    const globalVars = globalVariables();

    const root = css({
        height: "100%",
        maxHeight: "80vh",
    });

    const description = css({
        ...Mixins.margin({
            bottom: globalVars.gutter.half,
        }),
    });

    return { root, description };
}
