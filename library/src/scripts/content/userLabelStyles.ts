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
import { css, CSSObject } from "@emotion/css";

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
        ...globalVars.fontSizeAndWeightVars("medium", "bold"),
    });

    return {
        avatar,
        name,
    };
});

export const userLabelClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = userLabelVariables();

    const root = css({
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
        justifyContent: "space-between",
        width: percent(100),
        minHeight: styleUnit(vars.avatar.size),
    });

    const fixLineHeight = css({});

    const compact = css({
        ...{
            [`&.${fixLineHeight}`]: lineHeightAdjustment(),
        },
    });

    const main = css({
        display: "flex",
        flexDirection: "column",
        flexWrap: "nowrap",
        alignItems: "flex-start",
        justifyContent: "space-between",
        width: calc(`100% - ${styleUnit(vars.avatar.size + vars.avatar.margin)}`),
        flexBasis: calc(`100% - ${styleUnit(vars.avatar.size + vars.avatar.margin)}`),
        minHeight: styleUnit(vars.avatar.size),
    });

    const avatar = css({
        ...objectFitWithFallback(),
        overflow: "hidden",
        ...Mixins.border({
            color: globalVars.mixBgAndFg(0.1),
            width: 1,
            radius: vars.avatar.borderRadius,
        }),
    });
    const avatarLink = css({
        display: "block",
        position: "relative",
        width: styleUnit(vars.avatar.size),
        height: styleUnit(vars.avatar.size),
        flexBasis: styleUnit(vars.avatar.size),
    });
    const topRow = css({});
    const bottomRow = css({});
    const isCompact = css({});

    const userName = css({
        ...{
            "&&": {
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("medium", "bold"),
                    lineHeight: globalVars.lineHeights.condensed,
                }),
            },
            [`&&.${isCompact}`]: {
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("small"),
                }),
            },
        },
    });

    return { root, avatar, avatarLink, topRow, bottomRow, userName, main, compact, isCompact, fixLineHeight };
});
