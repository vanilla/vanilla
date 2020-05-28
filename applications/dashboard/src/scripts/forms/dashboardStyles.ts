/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";

export const dashboardClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("dashboard");

    const formList = style({
        padding: 0,
    });

    const helpAsset = style("helpAsset", {
        fontSize: "inherit !important",
        marginBottom: globalVars.gutter.size,
    });

    const tokenInput = style("tokenInput", {
        fontSize: "inherit",
    });

    return {
        formList,
        helpAsset,
        tokenInput,
    };
});
