/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { objectFitWithFallback } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { calc, percent } from "csx";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { CSSObject } from "@emotion/css";

export const userLabelVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("userLabel");
    const globalVars = globalVariables();
    const { mainColors } = globalVars;

    const avatar = makeThemeVars("spacing", {
        size: 40,
        borderRadius: "50%",
        margin: 8,
    });

    const name = makeThemeVars("name", {
        fontSize: globalVars.fonts.size.medium,
        fontWeight: globalVars.fonts.weights.bold,
    });

    return {
        avatar,
        name,
    };
});

export const userLabelClasses = useThemeCache(() => {
    const style = styleFactory("userLabel");
    const globalVars = globalVariables();
    const vars = userLabelVariables();

    const root = style({
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
        justifyContent: "space-between",
        width: percent(100),
        minHeight: styleUnit(vars.avatar.size),
    });

    const fixLineHeight = style("fixLineHeight", {});

    const compact = style("compact", {
        ...{
            [`&.${fixLineHeight}`]: lineHeightAdjustment(),
        },
    });

    const main = style("main", {
        display: "flex",
        flexDirection: "column",
        flexWrap: "nowrap",
        alignItems: "flex-start",
        justifyContent: "space-between",
        width: calc(`100% - ${styleUnit(vars.avatar.size + vars.avatar.margin)}`),
        flexBasis: calc(`100% - ${styleUnit(vars.avatar.size + vars.avatar.margin)}`),
        minHeight: styleUnit(vars.avatar.size),
    });

    const avatar = style("avatar", {
        ...objectFitWithFallback(),
        overflow: "hidden",
        ...Mixins.border({
            color: globalVars.mixBgAndFg(0.1),
            width: 1,
            radius: vars.avatar.borderRadius,
        }),
    });
    const avatarLink = style("avatarLink", {
        display: "block",
        position: "relative",
        width: styleUnit(vars.avatar.size),
        height: styleUnit(vars.avatar.size),
        flexBasis: styleUnit(vars.avatar.size),
    });
    const topRow = style("topRow", {});
    const bottomRow = style("bottomRow", {});
    const isCompact = style("isCompact", {});

    const userName = style("userName", {
        ...{
            "&&": {
                fontWeight: globalVars.fonts.weights.bold,
                fontSize: styleUnit(globalVars.fonts.size.medium),
                lineHeight: globalVars.lineHeights.condensed,
            },
            [`&&.${isCompact}`]: {
                fontSize: styleUnit(globalVars.fonts.size.small),
            },
        },
    });

    return { root, avatar, avatarLink, topRow, bottomRow, userName, main, compact, isCompact, fixLineHeight };
});
