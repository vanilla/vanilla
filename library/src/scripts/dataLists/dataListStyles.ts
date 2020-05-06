/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { IThemeVariables } from "@library/theming/themeReducer";
import { globalVariables } from "@library/styles/globalStyleVars";

export const dataListVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeVars = variableFactory("dateTime", forcedVars);
    const globalVars = globalVariables();

    return {};
});

export const dataListClasses = useThemeCache((props: { compact?: boolean } = {}) => {
    const style = styleFactory("events");
    const vars = dataListVariables();
    const globalVars = globalVariables();

    const root = style({});

    const row = style({});

    const key = style({});

    const value = style({});

    return {
        root,
        row,
        key,
        value,
    };
});
