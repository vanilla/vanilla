/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { cssRule } from "typestyle";
import { colorOut } from "@library/styles/styleHelpersColors";
import { borders, singleBorder } from "@library/styles/styleHelpersBorders";
import { absolutePosition, margins, paddings, unit, userSelect } from "@library/styles/styleHelpers";
import { em, percent } from "csx";

export const codeBlockVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("blockQuote");

    const fonts = makeThemeVars("fonts", {
        size: em(0.85),
    });

    const border = makeThemeVars("border", {
        radius: 0,
    });

    const colors = makeThemeVars("colors", {});

    return {
        fonts,
        colors,
    };
});

export const codeBlockCSS = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = codeBlockVariables();
    cssRule(".blockquote", {});
});
