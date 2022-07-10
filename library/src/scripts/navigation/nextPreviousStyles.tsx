/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { px } from "csx";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";

export const nextPreviousVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = variableFactory("nextPrevious");

    const fonts = themeVars("fonts", {
        label: globalVars.fonts.size.small,
        title: globalVars.fonts.size.medium,
    });

    const colors = themeVars("colors", {
        title: globalVars.mixBgAndFg(0.9),
        label: globalVars.mixBgAndFg(0.85),
        chevron: globalVars.mixBgAndFg(0.75),
        hover: {
            fg: globalVars.mainColors.primary,
        },
    });
    return {
        fonts,
        colors,
    };
});

export const nextPreviousClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = nextPreviousVariables();
    const style = styleFactory("nextPrevious");

    const root = style({
        display: "flex",
        alignItems: "flex-start",
        flexWrap: "wrap",
        justifyContent: "space-between",
    });

    const directionLabel = style("directionLabel", {
        display: "block",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("small"),
            lineHeight: globalVars.lineHeights.condensed,
            color: ColorsUtils.colorOut(vars.colors.label),
        }),
        marginBottom: styleUnit(2),
    });

    const title = style("title", {
        display: "block",
        position: "relative",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium", "semiBold"),
            lineHeight: globalVars.lineHeights.condensed,
            color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        }),
    });

    const chevron = style("chevron", {
        position: "absolute",
        top: px((vars.fonts.title * globalVars.lineHeights.condensed) / 2),
        transform: `translateY(-50%)`,
        color: ColorsUtils.colorOut(vars.colors.chevron),
    });

    const chevronLeft = style("chevronLeft", {
        left: px(0),
        marginLeft: px(-globalVars.icon.sizes.default),
    });

    const chevronRight = style("chevronRight", {
        right: px(0),
        marginRight: px(-globalVars.icon.sizes.default),
    });

    const activeStyles = {
        ...{
            ".adjacentLinks-icon, .adjacentLinks-title": {
                color: ColorsUtils.colorOut(vars.colors.hover.fg),
            },
        },
    };

    // Common to both left and right
    const adjacent = style("adjacent", {
        display: "block",
        ...Mixins.padding({
            vertical: 8,
        }),
        color: ColorsUtils.colorOut(vars.colors.title),
        ...{
            "&.focus-visible": activeStyles,
            "&:hover": activeStyles,
            "&:focus": activeStyles,
            "&:active": activeStyles,
        },
    });

    const previous = style("previous", {
        paddingLeft: px(globalVars.icon.sizes.default),
        paddingRight: px(globalVars.icon.sizes.default / 2),
    });

    const next = style("next", {
        marginLeft: "auto",
        textAlign: "right",
        paddingRight: px(globalVars.icon.sizes.default),
        paddingLeft: px(globalVars.icon.sizes.default / 2),
    });

    return {
        root,
        adjacent,
        previous,
        next,
        title,
        chevron,
        directionLabel,
        chevronLeft,
        chevronRight,
    };
});
