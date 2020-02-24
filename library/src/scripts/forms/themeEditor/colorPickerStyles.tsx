/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";

export const colorPickerVariables = useThemeCache(() => {
    // Fetch external global variables
    const globalVars = globalVariables();
    const formElVars = formElementsVariables();
    const makeThemeVars = variableFactory("colorPicker");
    return {};
});

export const colorPickerClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = colorPickerVariables();
    const style = styleFactory("colorPicker");
    const root = style({});
    const manualInput = style({});
    const swatch = style({});

    return {
        root,
        manualInput,
        swatch,
    };
});
