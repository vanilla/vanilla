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
import { percent } from "csx";

export const blockQuoteVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("blockQuote");
    const colors = makeThemeVars("colors", {
        fg: globalVars.mixBgAndFg(0.7),
        border: {
            color: globalVars.mixBgAndFg(0.23),
        },
    });

    return {
        colors,
    };
});

export const blockQuoteCSS = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = blockQuoteVariables();
    cssRule(".blockquote", {
        display: "block",
        margin: unit(0),
        ...paddings({
            all: 3,
            left: 18,
        }),
        borderLeft: singleBorder({
            color: vars.colors.border.color,
            width: 6,
        }),
        boxSizing: "border-box",
        verticalAlign: "middle",
    });
    cssRule(".blockquote-content", {
        $nest: {
            "& > *:first-child": {
                marginTop: unit(0),
            },
            "& > *:last-child": {
                marginBottom: unit(0),
            },
        },
    });
});
