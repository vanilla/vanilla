/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";

export const themeEditorVariables = useThemeCache(() => {
    // Fetch external global variables
    const globalVars = globalVariables();
    const formElVars = formElementsVariables();
    const makeThemeVars = variableFactory("themeEditor");
    return {};
});

export const themeEditorClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = themeEditorVariables();
    const style = styleFactory("themeEditor");

    const root = style({});
    const label = style({});
    const undoWrap = style({});
    const inputWrap = style({});

    return {
        root,
        label,
        undoWrap,
        inputWrap,
    };
});
