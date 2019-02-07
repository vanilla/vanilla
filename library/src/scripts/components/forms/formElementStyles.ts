/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, mixBgAndFg } from "@library/styles/styleHelpers";

export function formElementsVariables(theme?: object) {
    const vars = globalVariables(theme);
    const themeVars = componentThemeVariables(theme, "formElements");

    const sizing = {
        height: 36,
        halfHeight: 18,
        maxWidth: 528,
        ...themeVars.subComponentStyles("sizing"),
    };

    const spacing = {
        margin: 12,
        horizontalPadding: 12,
        verticalPadding: 6,
        ...themeVars.subComponentStyles("spacing"),
    };

    const border = {
        width: 1,
        fullWidth: 2,
        color: vars.border.color,
        style: "solid",
        radius: vars.border.radius,
        ...themeVars.subComponentStyles("border"),
    };

    const giantInput = {
        height: 82,
        fontSize: 24,
        ...themeVars.subComponentStyles("giantInput"),
    };

    const largeInput = {
        height: 48,
        fontSize: 16,
        ...themeVars.subComponentStyles("largeInput"),
    };

    const miniInput = {
        width: 100,
        ...themeVars.subComponentStyles("miniInput"),
    };

    const colors = {
        fg: mixBgAndFg(0.8),
        bg: vars.mainColors.bg,
        ...themeVars.subComponentStyles("colors"),
    };

    const placeholder = {
        color: mixBgAndFg(0.5),
        ...themeVars.subComponentStyles("placeholder"),
    };

    return { sizing, spacing, border, giantInput, largeInput, miniInput, colors, placeholder };
}
