/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles";
import { suggestedTextStyleHelper } from "@library/features/search/suggestedTextStyles";

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

    cssOut(`.form-group .suggestedTextInput-option`, suggestedTextStyleHelper().option);

    return {
        formList,
        helpAsset,
    };
});
