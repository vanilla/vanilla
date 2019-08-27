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
import { borders } from "@library/styles/styleHelpersBorders";
import { absolutePosition, margins, paddings, unit, userSelect } from "@library/styles/styleHelpers";
import { percent } from "csx";

export const atMentionVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("spoiler");

    const font = makeThemeVars("font", {
        size: globalVars.fonts.size.large,
    });

    return {
        font,
    };
});

export const atMentionCSS = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = atMentionVariables();
    // cssRule(".blockQuote", {
    //     $nest: {
    //         "& .": {
    //         },
    //     },
    // });
});
