/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    borders,
    colorOut,
    getHorizontalPaddingForTextInput,
    getVerticalPaddingForTextInput,
    margins,
    negative,
    textInputSizingFromFixedHeight,
    unit,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, important, percent, translateY } from "csx";
import { cssOut, nestedWorkaround, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { inputClasses, inputVariables } from "@library/forms/inputStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";

export const paginationCSS = () => {
    const globalVars = globalVariables();
    const inputVars = inputVariables();
    const formVars = formElementsVariables();
    const mainColors = globalVars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const metaFg = colorOut(globalVars.meta.colors.fg);

    cssOut(
        `
        .Pager span,
        .Pager > a`,
        {
            ...borders(),
            backgroundColor: colorOut(globalVars.mainColors.bg),
            color: colorOut(globalVars.mainColors.fg),
        },
    );
};
