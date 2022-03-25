/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { metasVariables } from "@library/metas/Metas.variables";

export const resultPaginationInfoClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const metasVars = metasVariables();
    const style = styleFactory("resultPaginationInfo");

    const root = style({
        ...Mixins.font(metasVars.font),
    });

    const alignRight = style("alignRight", {
        marginLeft: "auto",
    });

    return { root, alignRight };
});
