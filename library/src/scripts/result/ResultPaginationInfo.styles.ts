/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";

export const resultPaginationInfoClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("resultPaginationInfo");

    const root = style({
        ...Mixins.font(globalVars.meta.text),
        marginTop: globalVars.gutter.half,
    });

    const alignRight = style("alignRight", {
        marginLeft: "auto",
    });

    return { root, alignRight };
});
