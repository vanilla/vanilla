/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, debugHelper } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { formElementsVariables } from "@library/components/forms/formElementStyles";

export function attachmentVariables(theme?: object) {
    const globalVars = globalVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const themeVars = componentThemeVariables(theme, "attachment");
    const something = {
        ...themeVars.subComponentStyles("something"),
    };

    return { something };
}

export function attachmentClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const vars = attachmentVariables(theme);
    const debug = debugHelper("attachment");

    const root = style({
        ...debug.name(),
    });
    return { root };
}
