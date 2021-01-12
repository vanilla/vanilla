/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { flexHelper } from "@library/styles/styleHelpersPositioning";

export const dateIconVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const formElementVars = formElementsVariables(forcedVars);
    const makeThemeVars = variableFactory("dateIcon", forcedVars);

    return {};
});

export const dateIconClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = dateIconVariables();
    const style = styleFactory("titleBar");

    const root = style({
        display: "block",
    });

    return {
        root,
    };
});
