/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssRule } from "@library/styles/styleShim";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { userSelect } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { percent } from "csx";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { Mixins } from "@library/styles/Mixins";

export const blockQuoteVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("blockQuote");
    const colors = makeThemeVars("colors", {
        fg: globalVars.mainColors.fg,
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
        margin: styleUnit(0),
        ...Mixins.padding({
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
        ...{
            "& > *:first-child": {
                marginTop: styleUnit(0),
            },
            "& > *:last-child": {
                marginBottom: styleUnit(0),
            },
        },
    });
    cssOut(`.embedLink-excerpt`, {
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
    });
});
