/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { importantUnit } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { calc, important } from "csx";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Mixins } from "@library/styles/Mixins";

export const photoGridVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("groups");

    const spacer = makeThemeVars("spacer", {
        default: 6,
    });

    return {
        spacer,
    };
});

export const photoGridCSS = () => {
    const vars = photoGridVariables();

    cssOut(`.PhotoGrid`, {
        display: "flex",
        flexWrap: "wrap",
        alignItems: "center",
        ...Mixins.margin({
            vertical: importantUnit(-vars.spacer.default),
            left: importantUnit(-vars.spacer.default),
        }),
        width: important(calc(`100% + ${styleUnit(vars.spacer.default * 2)}`)),
    });

    cssOut(`.PhotoGrid .PhotoWrap`, {
        ...Mixins.margin({
            all: importantUnit(vars.spacer.default),
        }),
    });
};
