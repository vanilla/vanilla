/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { importantUnit, margins, unit } from "@library/styles/styleHelpers";
import { calc, important } from "csx";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";

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
        marginLeft: importantUnit(-vars.spacer.default),
        width: important(calc(`100% + ${unit(vars.spacer.default * 2)}`)),
    });

    cssOut(`.PhotoGrid a`, {
        ...margins({
            all: importantUnit(vars.spacer.default),
        }),
    });
};
