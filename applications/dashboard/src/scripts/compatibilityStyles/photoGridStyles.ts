/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cssRaw } from "typestyle";
import {
    absolutePosition,
    borders,
    buttonStates,
    colorOut,
    IActionStates,
    importantColorOut,
    importantUnit,
    IStateSelectors,
    margins,
    negative,
    paddings,
    pointerEvents,
    setAllLinkColors,
    singleBorder,
    textInputSizingFromFixedHeight,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, important, percent } from "csx";
import { cssOut, nestedWorkaround, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { inputClasses, inputVariables } from "@library/forms/inputStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { forumLayoutVariables } from "./forumLayoutStyles";
import { NestedCSSProperties } from "typestyle/lib/types";
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
    const globalVars = globalVariables();
    const inputVars = inputVariables();
    const formVars = formElementsVariables();
    const layoutVars = forumLayoutVariables();
    const mainColors = globalVars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const metaFg = colorOut(globalVars.meta.colors.fg);
    const mediaQueries = layoutVars.mediaQueries();

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
