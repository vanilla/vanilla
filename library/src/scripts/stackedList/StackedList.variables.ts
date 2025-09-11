import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import type { IFont } from "@library/styles/cssUtilsTypes";
import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";

export const stackedListVariables = useThemeCache((componentName: string) => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory(componentName);

    const sizing = makeThemeVars("sizing", {
        width: userPhotoVariables().sizing.medium,
        offset: 15,
    });

    const plus = makeThemeVars("plus", {
        font: Variables.font({
            ...globalVars.fontSizeAndWeightVars("medium", "bold"),
            lineHeight: globalVars.lineHeights.condensed,
        }) as IFont,
        margin: 5,
    });

    return {
        sizing,
        plus,
    };
});
